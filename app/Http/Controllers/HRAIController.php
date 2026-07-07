<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class HRAIController extends Controller
{
    private string $projectId = 'doremi-admin2';

    /** Shared Gemini 2.0 Flash caller. Returns text or null on quota/error. */
    private function callGemini(string $prompt, int $maxTokens = 250): ?string
    {
        $key = env('GEMINI_API_KEY');
        if (empty($key)) return null;
        try {
            $res  = Http::withoutVerifying()->timeout(20)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$key}",
                ['contents' => [['parts' => [['text' => $prompt]]]], 'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => $maxTokens]]
            );
            $data = $res->json();
            if (isset($data['error'])) return null;
            return trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '') ?: null;
        } catch (\Exception $e) { return null; }
    }

    // ════════════════════════════════════════════════════════════
    //  FEATURE 1 — OT Smart Verification
    // ════════════════════════════════════════════════════════════
    public function otVerify(string $userId)
    {
        try {
            $docs    = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/overtime")->json()['documents'] ?? [];
            $cutoff  = now()->subDays(90)->startOfDay();
            $records = []; $totalHrs = 0.0; $weekBuckets = [];

            // ── PHP PRE-PROCESSING: Get all attendances to calculate shift gaps ──
            $attRes = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/attendances")->json()['documents'] ?? [];
            $attendanceMap = [];
            foreach ($attRes as $aDoc) {
                $af = $aDoc['fields'] ?? [];
                $aUid = $af['uid']['stringValue'] ?? ($af['user_id']['stringValue'] ?? '');
                if (trim($aUid) !== trim($userId)) continue;
                $aDate = $af['date']['stringValue'] ?? '';
                $clockInTime = $af['clock_in_time']['stringValue'] ?? null;
                if ($aDate && $clockInTime) {
                    $attendanceMap[$aDate] = $clockInTime;
                }
            }

            foreach ($docs as $doc) {
                $f   = $doc['fields'] ?? [];
                $uid = $f['uid']['stringValue'] ?? ($f['user_id']['stringValue'] ?? '');
                if (trim($uid) !== trim($userId)) continue;
                $dateStr = $f['date']['stringValue'] ?? '';
                try { $date = \Carbon\Carbon::parse($dateStr); } catch (\Exception $e) { continue; }
                if ($date->startOfDay()->lt($cutoff)) continue;
                $hrs = (float)($f['totalHours']['doubleValue'] ?? ($f['totalHours']['integerValue'] ?? 0));
                $records[] = ['date' => $dateStr, 'hours' => $hrs];
                $totalHrs += $hrs;
                $weekBuckets[$date->format('Y-W')] = ($weekBuckets[$date->format('Y-W')] ?? 0) + $hrs;
            }

            $count    = count($records);
            $avgPerWk = $count > 0 ? round($totalHrs / max(count($weekBuckets), 1), 1) : 0;
            $maxWeek  = !empty($weekBuckets) ? max($weekBuckets) : 0;

            if ($count === 0) {
                return response()->json(['success' => true, 'verdict' => 'NO DATA', 'colour' => 'gray',
                    'explanation' => 'No OT records found in the last 90 days.', 'stats' => ['total_hours' => 0, 'submissions' => 0, 'avg_per_week' => 0]]);
            }

            usort($records, fn($a, $b) => strcmp($a['date'], $b['date']));

            // Calculate shift gaps in PHP
            $shiftGaps = [];
            foreach ($docs as $doc) {
                $f   = $doc['fields'] ?? [];
                $uid = $f['uid']['stringValue'] ?? ($f['user_id']['stringValue'] ?? '');
                if (trim($uid) !== trim($userId)) continue;
                
                $otDateStr = $f['date']['stringValue'] ?? '';
                $otEndTimeStr = $f['endTime']['stringValue'] ?? '';
                if (!$otDateStr || !$otEndTimeStr) continue;
                
                try {
                    $otDate = \Carbon\Carbon::parse($otDateStr);
                    $nextDayDateStr = $otDate->copy()->addDay()->format('Y-m-d');
                } catch (\Exception $e) {
                    continue;
                }
                
                if (isset($attendanceMap[$nextDayDateStr])) {
                    $nextCheckinTimeStr = $attendanceMap[$nextDayDateStr];
                    
                    try {
                        $otEndDt = \Carbon\Carbon::parse($otDateStr . ' ' . $otEndTimeStr);
                        $nextCheckinDt = \Carbon\Carbon::parse($nextDayDateStr . ' ' . $nextCheckinTimeStr);
                        
                        $gapHours = (float)($nextCheckinDt->diffInMinutes($otEndDt) / 60.0);
                        
                        $shiftGaps[] = [
                            'date' => $otDateStr,
                            'ot_end_time' => $otEndTimeStr,
                            'next_checkin_time' => $nextCheckinTimeStr,
                            'gap_hours' => round($gapHours, 2)
                        ];
                    } catch (\Exception $e) {}
                }
            }

            // Calculate total_ot_hours_this_month
            $currentMonth = now()->month;
            $currentYear = now()->year;
            $totalOtHoursThisMonth = 0.0;
            foreach ($docs as $doc) {
                $f   = $doc['fields'] ?? [];
                $uid = $f['uid']['stringValue'] ?? ($f['user_id']['stringValue'] ?? '');
                if (trim($uid) !== trim($userId)) continue;
                $dateStr = $f['date']['stringValue'] ?? '';
                if (!$dateStr) continue;
                try {
                    $date = \Carbon\Carbon::parse($dateStr);
                    if ($date->month === $currentMonth && $date->year === $currentYear) {
                        $hrs = (float)($f['totalHours']['doubleValue'] ?? ($f['totalHours']['integerValue'] ?? 0));
                        $totalOtHoursThisMonth += $hrs;
                    }
                } catch (\Exception $e) {}
            }

            // Calculate consecutive_ot_days
            $otDates = [];
            foreach ($docs as $doc) {
                $f   = $doc['fields'] ?? [];
                $uid = $f['uid']['stringValue'] ?? ($f['user_id']['stringValue'] ?? '');
                if (trim($uid) !== trim($userId)) continue;
                $dateStr = $f['date']['stringValue'] ?? '';
                if ($dateStr) {
                    $otDates[] = $dateStr;
                }
            }
            $otDates = array_unique($otDates);
            sort($otDates);
            
            $maxConsecutive = 0;
            $currentConsecutive = 0;
            $prevDate = null;
            foreach ($otDates as $dStr) {
                try {
                    $currDate = \Carbon\Carbon::parse($dStr);
                    if ($prevDate === null) {
                        $currentConsecutive = 1;
                    } else {
                        if ($currDate->diffInDays($prevDate) === 1) {
                            $currentConsecutive++;
                        } else {
                            if ($currentConsecutive > $maxConsecutive) {
                                $maxConsecutive = $currentConsecutive;
                            }
                            $currentConsecutive = 1;
                        }
                    }
                    $prevDate = $currDate;
                } catch (\Exception $e) {}
            }
            if ($currentConsecutive > $maxConsecutive) {
                $maxConsecutive = $currentConsecutive;
            }
            $consecutiveOtDays = $maxConsecutive;

            // Shift gap check for health hazard
            $hasGapHazard = false;
            foreach ($shiftGaps as $sg) {
                if ($sg['gap_hours'] < 8.0) {
                    $hasGapHazard = true;
                    break;
                }
            }
            $isHealthHazard = $hasGapHazard || ($totalOtHoursThisMonth > 40) || ($consecutiveOtDays >= 5);

            $otHistoryJson = json_encode($records, JSON_PRETTY_PRINT);
            $shiftGapsJson = json_encode($shiftGaps, JSON_PRETTY_PRINT);

            // Construct Gemini Prompt
            $prompt = "You are a responsible HR compliance AI for DOREMi event company.
Your role is not just to verify overtime legitimacy, but also to 
protect employee health and wellbeing.

Employee OT Data (last 90 days):
{$otHistoryJson}

Shift Gap Data:
{$shiftGapsJson}
(format: { date, ot_end_time, next_checkin_time, gap_hours })

Analyze and return ONLY this JSON:
{
  \"verdict\": \"PRODUCTIVE\" | \"NEEDS REVIEW\" | \"SUSPICIOUS\" | \"HEALTH HAZARD\",
  \"confidence\": \"HIGH\" | \"MEDIUM\" | \"LOW\",
  \"reason\": \"one sentence, factual\",
  \"recommendation\": \"one clear action for admin\"
}

Verdict rules (in priority order):

1. HEALTH HAZARD — check FIRST before anything else:
   Trigger if ANY of these are true:
   - Any shift gap < 8 hours between OT end and next Check-In
   - Total OT hours this month > 40 hours
   - OT recorded on 5 or more consecutive days without a rest day
   reason must state the specific health risk found.
   recommendation: suggest mandatory rest, task redistribution, 
   or activating Late-In Flexi-Hours for next morning.

2. SUSPICIOUS:
   - OT clustered on Fridays or eve of public holidays
   - OT claimed but no corresponding attendance Check-Out recorded
   - Sudden spike >200% vs previous month average

3. NEEDS REVIEW:
   - Irregular OT without clear pattern
   - First time OT spike — may be legitimate, needs human check

4. PRODUCTIVE:
   - OT aligns with known event schedules or project deadlines
   - Consistent pattern with proper rest gaps
   - Output/deliverable can be cross-referenced";

            $geminiText = $this->callGemini($prompt, 350);
            $source = 'local';
            $verdict = 'NEEDS REVIEW';
            $colour = 'amber';
            $explanation = '';

            if ($geminiText !== null) {
                $cleanJson = trim($geminiText);
                if (strpos($cleanJson, '```') !== false) {
                    $cleanJson = preg_replace('/^```(?:json)?\s+|\s+```$/is', '', $cleanJson);
                }
                
                $decoded = json_decode(trim($cleanJson), true);
                if (json_last_error() === JSON_ERROR_NONE && isset($decoded['verdict'])) {
                    $source = 'gemini';
                    $verdict = strtoupper($decoded['verdict']);
                    $reason = $decoded['reason'] ?? '';
                    $rec = $decoded['recommendation'] ?? '';
                    $explanation = $reason . "\n\nRecommendation: " . $rec;
                    
                    $colourMap = [
                        'PRODUCTIVE' => 'green',
                        'SUSPICIOUS' => 'red',
                        'NEEDS REVIEW' => 'amber',
                        'HEALTH HAZARD' => 'red'
                    ];
                    $colour = $colourMap[$verdict] ?? 'amber';
                }
            }

            if ($source === 'local') {
                if ($isHealthHazard) {
                    $verdict = 'HEALTH HAZARD';
                    $colour = 'red';
                    $reasons = [];
                    if ($hasGapHazard) $reasons[] = "Rest gap between shifts is less than 8 hours.";
                    if ($totalOtHoursThisMonth > 40) $reasons[] = "Total OT hours this month ({$totalOtHoursThisMonth}H) exceeds the 40-hour safety threshold.";
                    if ($consecutiveOtDays >= 5) $reasons[] = "OT was logged on {$consecutiveOtDays} consecutive days without a rest day.";
                    
                    $explanation = "HEALTH HAZARD DETECTED: " . implode(" ", $reasons) . "\n\nRecommendation: Suggest mandatory rest, task redistribution, or activating Late-In Flexi-Hours for next morning.";
                } else {
                    [$verdict, $colour, $explanation] = $this->localOTVerdict($totalHrs, $avgPerWk, $maxWeek);
                }
            }

            return response()->json(['success' => true, 'verdict' => $verdict, 'colour' => $colour,
                'explanation' => $explanation, 'source' => $source,
                'stats' => ['total_hours' => $totalHrs, 'submissions' => $count, 'avg_per_week' => $avgPerWk]]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function localOTVerdict(float $total, float $avg, float $peak): array
    {
        if ($total > 120 || $peak > 25 || $avg > 15)
            return ['SUSPICIOUS', 'red', "Total of {$total}H over 90 days (avg {$avg}H/week, peak {$peak}H) is unusually high. Manual verification of task records is recommended."];
        if ($total <= 60 && $avg <= 8 && $peak <= 15)
            return ['PRODUCTIVE', 'green', "OT of {$total}H over 90 days (avg {$avg}H/week) reflects a healthy, consistent work pattern with no anomalies detected."];
        return ['NEEDS REVIEW', 'amber', "Total of {$total}H over 90 days with a peak of {$peak}H in one week. Workload distribution warrants a review to ensure balance and proper delegation."];
    }

    // ════════════════════════════════════════════════════════════
    //  FEATURE 2 — Leave Pattern Prediction
    // ════════════════════════════════════════════════════════════
    public function leavePattern(string $userId)
    {
        try {
            $docs = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/leaves")->json()['documents'] ?? [];
            $typeCount = ['AL' => 0, 'EL' => 0, 'MC' => 0, 'Other' => 0];
            $dayCount  = [0, 0, 0, 0, 0, 0, 0]; $totalDays = 0; $records = [];

            foreach ($docs as $doc) {
                $f   = $doc['fields'] ?? [];
                $uid = $f['uid']['stringValue'] ?? '';
                if (trim($uid) !== trim($userId)) continue;
                
                $status = strtolower($f['status']['stringValue'] ?? '');
                if ($status !== 'approved') continue;
                
                $dateRaw = $f['start_date']['stringValue'] 
                        ?? $f['startDate']['timestampValue'] 
                        ?? $f['startDate']['stringValue'] 
                        ?? '';
                if (empty($dateRaw)) continue;
                try { $date = \Carbon\Carbon::parse($dateRaw); } catch (\Exception $e) { continue; }
                
                $type  = $f['leave_type']['stringValue'] ?? $f['leaveType']['stringValue'] ?? 'Other';
                $days  = (int)($f['totalDays']['integerValue'] ?? 1);
                $records[] = ['date' => $date->format('Y-m-d'), 'type' => $type];
                $totalDays += $days;
                $dayCount[$date->dayOfWeek]++;
                
                $up = strtoupper(trim($type));
                if (str_contains($up, 'ANNUAL') || $up === 'AL' || str_ends_with($up, ' AL')) $typeCount['AL']++;
                elseif (str_contains($up, 'EMERGENCY') || $up === 'EL' || str_ends_with($up, ' EL')) $typeCount['EL']++;
                elseif (str_contains($up, 'MEDICAL') || str_contains($up, 'MC')) $typeCount['MC']++;
                else $typeCount['Other']++;
            }

            $total = count($records);
            if ($total === 0) {
                return response()->json(['success' => true, 'insight' => 'No leave records found for this employee.',
                    'recommendation' => 'No action required.', 'source' => 'none', 'stats' => ['total_applications' => 0, 'total_days' => 0]]);
            }

            $dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
            $dayStr   = implode(' ', array_map(fn($i) => "{$dayNames[$i]}={$dayCount[$i]}", array_keys($dayCount)));
            $prompt   = "HR leave analyst. Data: Applications={$total}, Days={$totalDays}, AL={$typeCount['AL']}, EL={$typeCount['EL']}, MC={$typeCount['MC']}. Day dist: {$dayStr}. Give 2-3 sentence English insight on leave patterns (Mon/Fri clustering, MC frequency) + 1 sentence admin recommendation.";

            $geminiText = $this->callGemini($prompt, 180);
            if ($geminiText !== null) {
                $sentences = preg_split('/(?<=[.!?])\s+/', trim($geminiText));
                $insight   = implode(' ', array_slice($sentences, 0, 3));
                $rec       = count($sentences) > 3 ? implode(' ', array_slice($sentences, 3)) : 'Continue monitoring leave trends quarterly.';
                $source    = 'gemini';
            } else {
                [$insight, $rec] = $this->localLeaveInsight($total, $totalDays, $typeCount, $dayCount);
                $source = 'local';
            }

            return response()->json(['success' => true, 'insight' => $insight, 'recommendation' => $rec,
                'source' => $source, 'stats' => ['total_applications' => $total, 'total_days' => $totalDays, 'type_breakdown' => $typeCount]]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function localLeaveInsight(int $total, int $totalDays, array $typeCount, array $dayCount): array
    {
        $flags = [];
        $monFri = $dayCount[1] + $dayCount[5];
        if ($total > 0 && ($monFri / $total) > 0.4)
            $flags[] = "A notable clustering of {$monFri} of {$total} leave applications on Mondays and Fridays suggests a pattern of extended weekends.";
        if ($total > 0 && ($typeCount['MC'] / $total) > 0.5)
            $flags[] = "Medical leave accounts for over 50% of all applications ({$typeCount['MC']} of {$total}), which may warrant a wellbeing check-in with the employee.";
        if ($totalDays > 15)
            $flags[] = "A total of {$totalDays} leave days taken is above average and may affect team scheduling.";
        if (empty($flags))
            $flags[] = "Leave pattern appears normal. {$total} application(s) covering {$totalDays} day(s): AL={$typeCount['AL']}, EL={$typeCount['EL']}, MC={$typeCount['MC']} — no significant anomalies detected.";
        $rec = $totalDays > 15
            ? 'HR should proactively plan team coverage for periods this employee is frequently absent.'
            : 'Continue monitoring leave patterns during the next quarterly HR review.';
        return [implode(' ', $flags), $rec];
    }

    // ════════════════════════════════════════════════════════════
    //  FEATURE 3 — Flexi-Credit AI Advisor
    // ════════════════════════════════════════════════════════════
    public function flexiAdvice(string $userId)
    {
        try {
            $uf           = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/users/{$userId}")->json()['fields'] ?? [];
            $flexiBal     = (float)($uf['ot_balance']['integerValue'] ?? ($uf['ot_balance']['doubleValue'] ?? 0));
            $otDocs       = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/overtime")->json()['documents'] ?? [];
            $cutoff       = now()->subDays(30)->startOfDay();
            $otHrs = 0.0; $otCount = 0;

            foreach ($otDocs as $doc) {
                $f   = $doc['fields'] ?? [];
                $uid = $f['uid']['stringValue'] ?? ($f['user_id']['stringValue'] ?? '');
                if (trim($uid) !== trim($userId)) continue;
                try { $d = \Carbon\Carbon::parse($f['date']['stringValue'] ?? '')->startOfDay(); } catch (\Exception $e) { continue; }
                if ($d->lt($cutoff)) continue;
                $otHrs += (float)($f['totalHours']['doubleValue'] ?? ($f['totalHours']['integerValue'] ?? 0));
                $otCount++;
            }

            $burnoutRisk = $flexiBal > 20 || $otHrs > 20;
            $prompt = "HR advisor. FlexiCredit={$flexiBal}H, OT last 30d={$otHrs}H ({$otCount} submissions). In 2-3 sentences: advise whether to use early-out, flag burnout risk if applicable, or commend balance. Professional English only.";
            $text   = $this->callGemini($prompt, 120);
            $source = $text !== null ? 'gemini' : 'local';
            $advice = $text ?? $this->localFlexiAdvice($flexiBal, $otHrs);

            return response()->json(['success' => true, 'advice' => $advice, 'burnout_risk' => $burnoutRisk,
                'source' => $source, 'flexi_balance' => $flexiBal, 'ot_hours_30d' => $otHrs]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function localFlexiAdvice(float $bal, float $ot): string
    {
        if ($bal > 20 && $ot > 20)
            return "This employee has accumulated {$bal}H of flexi credit while logging {$ot}H of overtime in the past 30 days — a strong indicator of burnout risk. HR should immediately recommend scheduling early-out days and review workload distribution with the direct manager.";
        if ($bal > 20)
            return "A flexi credit balance of {$bal}H is significantly above the recommended threshold. HR should encourage the employee to utilize early-out benefits to maintain a healthy work-life balance before the balance accumulates further.";
        if ($ot > 20)
            return "Despite a moderate flexi balance of {$bal}H, this employee logged {$ot}H of overtime in the past 30 days. Consider converting eligible OT to flexi credits and monitoring for signs of workload imbalance.";
        if ($bal >= 10)
            return "Flexi credit balance of {$bal}H is within a moderate range. The employee may wish to plan ahead and utilize flexi credits for personal time-off to maintain engagement and productivity.";
        return "Flexi credit balance of {$bal}H is healthy and within normal parameters. Continue encouraging responsible use of flexible work benefits as part of overall wellbeing support.";
    }

    // ════════════════════════════════════════════════════════════
    //  FEATURE 4 — Auto KPI Review Engine
    // ════════════════════════════════════════════════════════════
    public function kpiReview(string $userId)
    {
        try {
            $projectId = $this->projectId;

            // Fetch user profile
            $uf = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$userId}")->json()['fields'] ?? [];
            $name       = $uf['full_name']['stringValue'] ?? 'This employee';
            $dept       = $uf['department']['stringValue'] ?? 'General';
            $joinDate   = $uf['join_date']['stringValue'] ?? 'N/A';

            // Attendance last 30 days
            $attDocs  = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/attendances")->json()['documents'] ?? [];
            $cutoff30 = now()->subDays(30)->startOfDay();
            $attTotal = 0; $attPresent = 0; $attLate = 0;
            foreach ($attDocs as $doc) {
                $f = $doc['fields'] ?? [];
                $u = $f['uid']['stringValue'] ?? $f['user_id']['stringValue'] ?? '';
                if ($u !== $userId) continue;
                try { $d = \Carbon\Carbon::parse($f['date']['stringValue'] ?? '')->startOfDay(); } catch (\Exception $e) { continue; }
                if ($d->lt($cutoff30)) continue;
                $attTotal++;
                $s = strtolower($f['status']['stringValue'] ?? '');
                if (in_array($s, ['present','on time'])) $attPresent++;
                elseif ($s === 'late') $attLate++;
            }

            // OT last 90 days
            $otDocs = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/overtime")->json()['documents'] ?? [];
            $cutoff90 = now()->subDays(90)->startOfDay();
            $otHrs = 0.0; $otCount = 0;
            foreach ($otDocs as $doc) {
                $f   = $doc['fields'] ?? [];
                $uid = $f['uid']['stringValue'] ?? ($f['user_id']['stringValue'] ?? '');
                if (trim($uid) !== trim($userId)) continue;
                try { $d = \Carbon\Carbon::parse($f['date']['stringValue'] ?? '')->startOfDay(); } catch (\Exception $e) { continue; }
                if ($d->lt($cutoff90)) continue;
                $otHrs += (float)($f['totalHours']['doubleValue'] ?? ($f['totalHours']['integerValue'] ?? 0));
                $otCount++;
            }

            // Leave count this year
            $leaveDocs = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/leaves")->json()['documents'] ?? [];
            $leaveCount = 0; $leaveDays = 0;
            foreach ($leaveDocs as $doc) {
                $f = $doc['fields'] ?? [];
                if (($f['uid']['stringValue'] ?? '') !== $userId) continue;
                $leaveCount++;
                $leaveDays += (int)($f['totalDays']['integerValue'] ?? 1);
            }

            $attRate  = $attTotal > 0 ? round(($attPresent / $attTotal) * 100) : 0;
            $prompt   = "Write a formal, professional HR annual performance review paragraph (150-180 words) in English for an employee named {$name} from {$dept} department (joined: {$joinDate}). Data: Attendance rate={$attRate}% (30 days, {$attLate} late arrivals), OT submitted={$otHrs}H over 90 days ({$otCount} submissions), Leave taken={$leaveDays} days ({$leaveCount} applications). Include: overall performance assessment, attendance & punctuality, dedication (OT), leave management, and a forward-looking recommendation. Formal HR tone, third person, no bullet points.";

            $geminiText = $this->callGemini($prompt, 350);
            $source     = $geminiText !== null ? 'gemini' : 'local';
            $paragraph  = $geminiText ?? $this->localKPIParagraph($name, $dept, $attRate, $attLate, $otHrs, $leaveDays);

            return response()->json(['success' => true, 'paragraph' => $paragraph, 'source' => $source,
                'meta' => ['name' => $name, 'department' => $dept, 'attendance_rate' => $attRate,
                           'late_count' => $attLate, 'ot_hours_90d' => $otHrs, 'leave_days' => $leaveDays]]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function localKPIParagraph(string $name, string $dept, int $attRate, int $late, float $otHrs, int $leaveDays): string
    {
        $attWord  = $attRate >= 90 ? 'excellent' : ($attRate >= 75 ? 'satisfactory' : 'below-average');
        $lateWord = $late === 0 ? 'with no recorded late arrivals' : "with {$late} late arrival(s) noted";
        $otWord   = $otHrs >= 40 ? 'a strong commitment to extended responsibilities' : ($otHrs > 0 ? 'a willingness to contribute beyond core hours' : 'no overtime recorded in the review period');
        $leaveWord = $leaveDays <= 10 ? 'responsible leave management' : 'an above-average leave utilization rate';

        $rating = match(true) {
            $attRate >= 90 && $late <= 2 && $otHrs > 0  => 'commendable',
            $attRate >= 75 && $late <= 5                 => 'satisfactory',
            default                                       => 'requiring improvement',
        };

        return "{$name} of the {$dept} Department has demonstrated {$rating} overall performance during the review period. "
             . "Attendance has been {$attWord} at {$attRate}%, {$lateWord}, reflecting "
             . ($attRate >= 80 ? 'a reliable and punctual work ethic.' : 'an area that requires attention and improvement.')
             . " In terms of dedication, the employee has shown {$otWord}, contributing a total of {$otHrs} overtime hours over the past 90 days. "
             . "Leave management has been characterized by {$leaveWord}, with {$leaveDays} day(s) taken during the assessment period. "
             . "Moving forward, it is recommended that " . strtolower($name) . " "
             . ($attRate < 80 ? 'prioritize punctuality and consistent attendance as a key performance objective.' : 'maintain current performance standards and be considered for additional responsibilities in line with career development goals.')
             . " A follow-up review is advised at the next performance cycle to assess sustained progress.";
    }
}


