<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    private string $projectId;
    private string $baseUrl;

    public function __construct()
    {
        $this->projectId = env('FIREBASE_PROJECT_ID', 'doremi-admin');
        $this->baseUrl   = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
    }

    /**
     * Generate & stream the PDF attendance report.
     * Route: GET /report/print?month=5&year=2026
     */
    public function generate(Request $request)
    {
        // ── 1. Determine target month & year ─────────────────────────────────
        $month = (int) $request->input('month', Carbon::now()->month);
        $year  = (int) $request->input('year',  Carbon::now()->year);

        $targetStart  = Carbon::create($year, $month, 1)->startOfMonth();
        $targetEnd    = Carbon::create($year, $month, 1)->endOfMonth();
        $reportPeriod = $targetStart->format('F Y');          // e.g. "May 2026"
        $monthLabel   = strtolower($targetStart->format('Y-m')); // "2026-05"

        // ── 2. Fetch users map (uid → name + department) ──────────────────────
        $usersMap = $this->fetchUsersMap();

        // ── 3. Fetch & filter attendance for the target month ─────────────────
        $rawAttendances = $this->fetchCollection('attendances');

        // Group into per-staff per-date buckets: [uid][date] = [check_in, check_out, status]
        $grouped = [];
        foreach ($rawAttendances as $doc) {
            $f    = $doc['fields'] ?? [];
            $uid  = $f['uid']['stringValue'] ?? '';
            $type = $f['type']['stringValue'] ?? '';
            $status = $f['status']['stringValue'] ?? 'Present';

            // Parse timestamp (ISO 8601 UTC) → KL time
            $tsRaw = $f['timestamp']['timestampValue'] ?? null;
            if (!$tsRaw) continue;

            try {
                $ts = Carbon::parse($tsRaw)->timezone('Asia/Kuala_Lumpur');
            } catch (\Exception $e) {
                continue;
            }

            // Filter to target month
            if ($ts->month !== $month || $ts->year !== $year) continue;

            $dateKey = $ts->format('Y-m-d');

            if (!isset($grouped[$uid])) $grouped[$uid] = [];
            if (!isset($grouped[$uid][$dateKey])) {
                $grouped[$uid][$dateKey] = [
                    'date'      => $dateKey,
                    'check_in'  => null,
                    'check_out' => null,
                    'status'    => 'Absent',
                ];
            }

            if (strtolower($type) === 'check-in') {
                $grouped[$uid][$dateKey]['check_in'] = $ts->format('Y-m-d H:i:s');
                $grouped[$uid][$dateKey]['status']   = $status;
            } elseif (strtolower($type) === 'check-out') {
                $grouped[$uid][$dateKey]['check_out'] = $ts->format('Y-m-d H:i:s');
            }
        }

        // ── 4. Flatten into reportData rows ───────────────────────────────────
        $reportData = [];
        foreach ($grouped as $uid => $dates) {
            $staffName = $usersMap[$uid]['name']       ?? 'Unknown';
            $dept      = $usersMap[$uid]['department'] ?? 'N/A';

            ksort($dates); // sort by date asc

            foreach ($dates as $dateKey => $rec) {
                // Determine status label
                $status = 'Present';
                if (strtolower($rec['status']) === 'absent') {
                    $status = 'Absent';
                } elseif ($rec['check_in']) {
                    $inTime = Carbon::parse($rec['check_in']);
                    $cutoff = $inTime->copy()->setTime(9, 5, 0);
                    $status = $inTime->gt($cutoff) ? 'Late' : 'Present';
                }

                $reportData[] = (object) [
                    'date'       => $dateKey,
                    'name'       => $staffName,
                    'department' => $dept,
                    'clock_in'   => $rec['check_in'],
                    'clock_out'  => $rec['check_out'],
                    'status'     => $status,
                ];
            }
        }

        // Sort by date then name
        usort($reportData, fn($a, $b) =>
            $a->date === $b->date
                ? strcmp($a->name, $b->name)
                : strcmp($a->date, $b->date)
        );

        // ── 5. Summary stats ──────────────────────────────────────────────────
        $totalStaff = count($usersMap);
        $onTime  = 0; $late = 0; $absent = 0;
        foreach ($reportData as $row) {
            $s = strtolower($row->status);
            if ($s === 'late')   $late++;
            elseif ($s === 'absent') $absent++;
            else $onTime++;
        }

        // ── 6. OT & Leave totals for the month ───────────────────────────────
        $totalOTHours   = $this->calcOTHours($month, $year);
        $totalLeaveDays = $this->calcLeaveDays($month, $year);

        // ── 7. Render & stream PDF ────────────────────────────────────────────
        $pdf = Pdf::loadView('pdf_report', compact(
            'reportData',
            'reportPeriod',
            'totalStaff',
            'onTime',
            'late',
            'absent',
            'totalOTHours',
            'totalLeaveDays'
        ))->setPaper('a4', 'landscape');

        $filename = 'Performance_Report_' . $targetStart->format('M_Y') . '.pdf';
        return $pdf->stream($filename);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════════════════

    /** Fetch all docs from a Firestore collection (no auth — public read rule). */
    private function fetchCollection(string $collection): array
    {
        try {
            $res = Http::withoutVerifying()
                ->timeout(30)
                ->get("{$this->baseUrl}/{$collection}");
            return $res->json()['documents'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /** Build uid → [name, department] map from users collection. */
    private function fetchUsersMap(): array
    {
        $docs = $this->fetchCollection('users');
        $map  = [];
        foreach ($docs as $doc) {
            $uid  = basename($doc['name']);
            $f    = $doc['fields'] ?? [];
            $role = $f['role']['stringValue'] ?? 'staff';
            if (strtolower($role) === 'admin') continue; // skip admin accounts

            $map[$uid] = [
                'name'       => $f['full_name']['stringValue'] ?? 'Unknown',
                'department' => $f['department']['stringValue'] ?? 'N/A',
            ];
        }
        return $map;
    }

    /** Sum approved OT hours for a given month/year from Firestore. */
    private function calcOTHours(int $month, int $year): float
    {
        $docs  = $this->fetchCollection('overtime');
        $total = 0.0;
        foreach ($docs as $doc) {
            $f = $doc['fields'] ?? [];
            $status = $f['status']['stringValue'] ?? '';
            if (!in_array(strtolower($status), ['approved', 'verified'])) continue;

            $date = $f['date']['stringValue'] ?? '';
            if (!$date) continue;
            try {
                $d = Carbon::parse($date);
                if ($d->month !== $month || $d->year !== $year) continue;
            } catch (\Exception $e) { continue; }

            $hours = $f['hours']['doubleValue']
                  ?? $f['hours']['integerValue']
                  ?? $f['duration_hours']['doubleValue']
                  ?? $f['duration_hours']['integerValue']
                  ?? 0;
            $total += (float) $hours;
        }
        return round($total, 1);
    }

    /** Sum approved leave days for a given month/year from Firestore. */
    private function calcLeaveDays(int $month, int $year): int
    {
        $docs  = $this->fetchCollection('leaves');
        $total = 0;
        foreach ($docs as $doc) {
            $f      = $doc['fields'] ?? [];
            $status = $f['status']['stringValue'] ?? '';
            if (strtolower($status) !== 'approved') continue;

            $startStr = $f['start_date']['stringValue'] ?? $f['startDate']['stringValue'] ?? '';
            if (!$startStr) continue;
            try {
                $d = Carbon::parse($startStr);
                if ($d->month !== $month || $d->year !== $year) continue;
            } catch (\Exception $e) { continue; }

            $endStr = $f['end_date']['stringValue'] ?? $f['endDate']['stringValue'] ?? $startStr;
            try {
                $days = Carbon::parse($startStr)->diffInDays(Carbon::parse($endStr)) + 1;
                $total += $days;
            } catch (\Exception $e) {
                $total += 1;
            }
        }
        return $total;
    }
}