<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EmployeeAIController extends Controller
{
    private string $projectId = 'doremi-admin2';

    public function analyze(string $id)
    {
        try {
            // ── 1. Pull attendance records from Firestore ─────────────────────
            $firestoreUrl  = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/attendances";
            $firestoreData = Http::withoutVerifying()->get($firestoreUrl)->json();

            $cutoff       = now()->subDays(30)->startOfDay();
            $records      = [];
            $presentCount = 0;
            $lateCount    = 0;
            $absentCount  = 0;
            $lateClockIns = [];

            if (isset($firestoreData['documents'])) {
                foreach ($firestoreData['documents'] as $doc) {
                    $f      = $doc['fields'] ?? [];
                    $userId = $f['uid']['stringValue'] ?? $f['user_id']['stringValue'] ?? '';

                    if (trim($userId) !== trim($id)) {
                        continue;
                    }

                    $dateStr = $f['date']['stringValue'] ?? '';
                    try {
                        $date = \Carbon\Carbon::parse($dateStr)->startOfDay();
                    } catch (\Exception $e) {
                        continue;
                    }

                    if ($date->lt($cutoff)) {
                        continue;
                    }

                    $status  = $f['status']['stringValue'] ?? 'Unknown';
                    $clockIn = $f['clock_in_time']['stringValue'] ?? null;

                    $records[] = [
                        'date'          => $dateStr,
                        'status'        => $status,
                        'clock_in_time' => $clockIn,
                    ];

                    $lower = strtolower($status);
                    if (in_array($lower, ['present', 'on time'])) {
                        $presentCount++;
                    } elseif ($lower === 'late') {
                        $lateCount++;
                        if ($clockIn) {
                            $lateClockIns[] = $clockIn;
                        }
                    } elseif ($lower === 'absent') {
                        $absentCount++;
                    }
                }
            }

            $totalRecords = count($records);
            $stats = [
                'total'   => $totalRecords,
                'present' => $presentCount,
                'late'    => $lateCount,
                'absent'  => $absentCount,
            ];

            if ($totalRecords === 0) {
                return response()->json([
                    'success' => true,
                    'summary' => 'No attendance records were found for this employee in the last 30 days. Unable to generate a performance analysis.',
                    'source'  => 'none',
                    'stats'   => $stats,
                ]);
            }

            usort($records, fn($a, $b) => strcmp($a['date'], $b['date']));

            // ── 2. Try Gemini first ───────────────────────────────────────────
            $apiKey = env('GEMINI_API_KEY');

            if (!empty($apiKey)) {
                $logEntries    = array_slice($records, -15);
                $attendanceLog = '';
                foreach ($logEntries as $r) {
                    $ci = $r['clock_in_time'] ? " (in: {$r['clock_in_time']})" : '';
                    $attendanceLog .= "- {$r['date']}: {$r['status']}{$ci}\n";
                }

                $lateNote = '';
                if ($lateCount > 0 && !empty($lateClockIns)) {
                    $sample   = implode(', ', array_slice($lateClockIns, 0, 5));
                    $lateNote = " | Sample late clock-ins: {$sample}";
                }

                $prompt = "HR analyst. 30-day attendance: Total={$totalRecords}, Present/OnTime={$presentCount}, Late={$lateCount}, Absent={$absentCount}{$lateNote}.\n\nRecent entries:\n{$attendanceLog}\nWrite a professional English HR summary (max 150 words) covering: punctuality, absence/late patterns, and a brief recommendation.";

                try {
                    $geminiRes = Http::withoutVerifying()
                        ->timeout(20)
                        ->post(
                            "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}",
                            [
                                'contents'         => [['parts' => [['text' => $prompt]]]],
                                'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 280],
                            ]
                        );

                    $geminiData = $geminiRes->json();

                    // Only use Gemini result if no quota/error
                    if (!isset($geminiData['error'])) {
                        $summary = trim($geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '');
                        if (!empty($summary)) {
                            return response()->json([
                                'success' => true,
                                'summary' => $summary,
                                'source'  => 'gemini',
                                'stats'   => $stats,
                            ]);
                        }
                    }
                    // If Gemini errored (quota, etc.) — fall through to local fallback
                } catch (\Exception $e) {
                    // Network / timeout — fall through to local fallback
                }
            }

            // ── 3. LOCAL RULE-BASED FALLBACK ─────────────────────────────────
            // Generates a professional HR summary using PHP logic.
            // No API call — always works regardless of quota.
            $summary = $this->buildLocalSummary($totalRecords, $presentCount, $lateCount, $absentCount, $lateClockIns);

            return response()->json([
                'success' => true,
                'summary' => $summary,
                'source'  => 'local',
                'stats'   => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'System error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a professional HR performance summary using rule-based logic.
     * Used when Gemini API quota is exceeded or unavailable.
     */
    private function buildLocalSummary(
        int $total,
        int $present,
        int $late,
        int $absent,
        array $lateClockIns
    ): string {
        $punctualityRate = $total > 0 ? round(($present / $total) * 100) : 0;
        $lateRate        = $total > 0 ? round(($late   / $total) * 100) : 0;
        $absentRate      = $total > 0 ? round(($absent / $total) * 100) : 0;

        // ── Punctuality assessment ────────────────────────────────────────────
        if ($punctualityRate >= 90) {
            $punctualityLine = "demonstrates an excellent punctuality record, arriving on time for {$punctualityRate}% of scheduled workdays";
            $overallRating   = 'Excellent';
        } elseif ($punctualityRate >= 75) {
            $punctualityLine = "maintains a satisfactory punctuality record, being present on time for {$punctualityRate}% of workdays";
            $overallRating   = 'Satisfactory';
        } elseif ($punctualityRate >= 50) {
            $punctualityLine = "shows an inconsistent punctuality pattern, with on-time attendance at {$punctualityRate}% over the review period";
            $overallRating   = 'Below Average';
        } else {
            $punctualityLine = "exhibits a concerning punctuality record, with on-time attendance at only {$punctualityRate}% — significantly below the acceptable threshold";
            $overallRating   = 'Poor';
        }

        // ── Lateness detail ───────────────────────────────────────────────────
        $lateSection = '';
        if ($late === 0) {
            $lateSection = 'No late arrivals were recorded during this period, which reflects strong time management.';
        } elseif ($late <= 2) {
            $lateSection = "There were {$late} late arrival(s) ({$lateRate}% of total days), considered within an acceptable range.";
        } elseif ($late <= 5) {
            $lateSection = "A total of {$late} late arrivals were recorded ({$lateRate}% of total days). This frequency warrants a reminder conversation with the employee.";
        } else {
            $lateSection = "A significant pattern of lateness was identified: {$late} late arrival(s) representing {$lateRate}% of recorded workdays. This requires formal HR attention and a performance improvement discussion.";
        }

        // Average late clock-in time if data available
        if (!empty($lateClockIns) && $late > 0) {
            $sample = implode(', ', array_slice($lateClockIns, 0, 3));
            $extra  = count($lateClockIns) > 3 ? ', and others' : '';
            $lateSection .= " Sample clock-in times on late days: {$sample}{$extra}.";
        }

        // ── Absence detail ────────────────────────────────────────────────────
        if ($absent === 0) {
            $absenceSection = 'The employee had a perfect attendance record with no absences in the last 30 days.';
        } elseif ($absent <= 2) {
            $absenceSection = "{$absent} absence(s) were recorded ({$absentRate}% of total days), which is within a generally acceptable range.";
        } elseif ($absent <= 5) {
            $absenceSection = "{$absent} absences were noted ({$absentRate}% of total days). HR should verify whether these were approved or unplanned.";
        } else {
            $absenceSection = "A high absence rate of {$absent} days ({$absentRate}%) was observed. This is a significant concern that requires immediate HR review and possible intervention.";
        }

        // ── Recommendation ────────────────────────────────────────────────────
        if ($overallRating === 'Excellent') {
            $recommendation = 'This employee displays commendable attendance discipline. Consider recognizing this performance during the next review cycle.';
        } elseif ($overallRating === 'Satisfactory') {
            $recommendation = 'Overall performance is acceptable. Continue to monitor attendance trends and encourage maintaining current standards.';
        } elseif ($overallRating === 'Below Average') {
            $recommendation = 'HR recommends an informal check-in with the employee to discuss attendance expectations and identify any underlying issues.';
        } else {
            $recommendation = 'A formal Performance Improvement Plan (PIP) related to attendance may be warranted. HR should schedule a meeting with the employee and their direct manager promptly.';
        }

        // ── Compose final summary ─────────────────────────────────────────────
        return "OVERALL RATING: {$overallRating}\n\n"
             . "Based on {$total} attendance record(s) in the last 30 days, this employee {$punctualityLine}. "
             . "Of these, {$present} day(s) were on time, {$late} late, and {$absent} absent.\n\n"
             . "PUNCTUALITY: {$lateSection}\n\n"
             . "ATTENDANCE: {$absenceSection}\n\n"
             . "RECOMMENDATION: {$recommendation}";
    }
}


