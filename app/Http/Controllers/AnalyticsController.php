<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class AnalyticsController extends Controller
{
    private $projectId;

    public function __construct()
    {
        $this->projectId = env('FIRESTORE_PROJECT_ID', 'doremi-admin2');
    }

    public function index(Request $request)
    {
        try {
            // ── Determine filter month & year (default: current month) ──────
            $filterMonth = (int) $request->input('month', Carbon::now()->month);
            $filterYear  = (int) $request->input('year',  Carbon::now()->year);
            $filterYM    = Carbon::create($filterYear, $filterMonth, 1)->format('Y-m'); // e.g. "2026-05"
            $filterLabel = Carbon::create($filterYear, $filterMonth, 1)->format('F Y'); // e.g. "May 2026"
            $currentYear = (string) $filterYear; // used by OT trend chart label

            // ── Fetch all Firestore collections ──────────────────────────────
            $attDocs   = Http::withoutVerifying()->get("{$this->baseUrl()}/attendances")->json()['documents'] ?? [];
            $leaveDocs = Http::withoutVerifying()->get("{$this->baseUrl()}/leaves")->json()['documents'] ?? [];
            $otDocs    = Http::withoutVerifying()->get("{$this->baseUrl()}/overtime")->json()['documents'] ?? [];

            // ── Attendance stats for selected month ──────────────────────────
            $onTime = 0; $late = 0; $absent = 0;
            foreach ($attDocs as $doc) {
                $f = $doc['fields'] ?? null;
                if (!$f) continue;

                // Seeder uses timestampValue; older records may use date stringValue
                $tsRaw   = $f['timestamp']['timestampValue'] ?? null;
                $dateStr = $f['date']['stringValue'] ?? null;
                try {
                    $carbon = $tsRaw
                        ? Carbon::parse($tsRaw)->timezone('Asia/Kuala_Lumpur')
                        : ($dateStr ? Carbon::parse($dateStr) : null);
                } catch (\Exception $e) { continue; }
                if (!$carbon) continue;

                if ($carbon->month !== $filterMonth || $carbon->year !== $filterYear) continue;

                $status = strtolower($f['status']['stringValue'] ?? '');
                if (str_contains($status, 'late'))                                         $late++;
                elseif (str_contains($status, 'present') || str_contains($status, 'on time')) $onTime++;
                else                                                                        $absent++;
            }
            $attendanceStats = [$onTime, $late, $absent];

            // ── Leave stats for selected month ───────────────────────────────
            $leavesThisMonthDays = 0;
            $annual = 0; $mc = 0; $emergency = 0;
            foreach ($leaveDocs as $doc) {
                $f = $doc['fields'] ?? null;
                if (!$f) continue;
                if (($f['status']['stringValue'] ?? '') !== 'Approved') continue;

                $startStr = $f['start_date']['stringValue']
                         ?? $f['startDate']['stringValue']
                         ?? null;
                if (!$startStr && isset($f['startDate']['timestampValue'])) {
                    $startStr = $f['startDate']['timestampValue'];
                }
                if (!$startStr) continue;

                try { $carbonDate = Carbon::parse($startStr); } catch (\Exception $e) { continue; }
                if ($carbonDate->month !== $filterMonth || $carbonDate->year !== $filterYear) continue;

                $endStr = $f['end_date']['stringValue'] ?? $f['endDate']['stringValue'] ?? $startStr;
                try {
                    $days = Carbon::parse($startStr)->diffInDays(Carbon::parse($endStr)) + 1;
                } catch (\Exception $e) {
                    $days = (int)($f['total_days']['integerValue'] ?? $f['totalDays']['integerValue'] ?? 1);
                }
                $type = $f['leave_type']['stringValue'] ?? $f['leaveType']['stringValue'] ?? '';
                
                $up = strtoupper(trim($type));
                if (str_contains($up, 'ANNUAL') || $up === 'AL' || str_ends_with($up, ' AL')) $annual += $days;
                elseif (str_contains($up, 'MEDICAL') || str_contains($up, 'MC')) $mc += $days;
                elseif (str_contains($up, 'EMERGENCY') || $up === 'EL' || str_ends_with($up, ' EL')) $emergency += $days;
                $leavesThisMonthDays += $days;
            }
            $leaveStats = [$annual, $mc, $emergency];

            // ── OT trend for the selected year (12-month bar) ────────────────
            $otTrend = array_fill(1, 12, 0);
            foreach ($otDocs as $doc) {
                $f = $doc['fields'] ?? null;
                if (!$f) continue;
                if (!in_array(($f['status']['stringValue'] ?? ''), ['Verified', 'Approved'])) continue;

                $date = $f['date']['stringValue'] ?? '';
                if (!str_starts_with($date, $currentYear)) continue;

                $m = (int) date('m', strtotime($date));
                $otHours = floatval(
                    $f['totalHours']['doubleValue']
                    ?? $f['totalHours']['integerValue']
                    ?? $f['hours']['doubleValue']
                    ?? $f['hours']['integerValue']
                    ?? $f['duration_hours']['doubleValue']
                    ?? $f['duration_hours']['integerValue']
                    ?? 0
                );
                $otTrend[$m] += $otHours;
            }
            $otTrendData = array_values($otTrend);

            // ── Fetch active users by department ──────────────────────────────
            $userDocs = Http::withoutVerifying()->get("{$this->baseUrl()}/users")->json()['documents'] ?? [];
            $activeByDept = [];
            foreach ($userDocs as $doc) {
                $f = $doc['fields'] ?? null;
                if (!$f) continue;
                $status = strtolower($f['status']['stringValue'] ?? '');
                if ($status === 'active') {
                    $dept = $f['department']['stringValue'] ?? 'Unknown';
                    if (!isset($activeByDept[$dept])) {
                        $activeByDept[$dept] = 0;
                    }
                    $activeByDept[$dept]++;
                }
            }

            $deptIcons = [
                'Audio' => '🎙️',
                'Lighting' => '💡',
                'Visual' => '📺',
                'Technical Ops' => '🛠️',
                'Technical Crew' => '🛠️',
                'Events & Crew' => '📋',
                'Logistics' => '🚚'
            ];

            return view('analytics', compact(
                'attendanceStats', 'leaveStats', 'otTrendData',
                'currentYear', 'leavesThisMonthDays',
                'filterMonth', 'filterYear', 'filterLabel',
                'activeByDept', 'deptIcons'
            ));

        } catch (\Exception $e) {
            return "Analytics Error: " . $e->getMessage();
        }
    }

    private function baseUrl(): string
    {
        return "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
    }

  public function exportPdf(Request $request)
{
    try {
        $projectId = env('FIRESTORE_PROJECT_ID', 'doremi-admin2');
        
        // 1. Ambil input
        $monthInput = $request->input('month'); 
        $yearInput = $request->input('year', Carbon::now()->year);
        $isYearly = ($monthInput === 'all');

        // 2. Set Tajuk & Nama Bulan (Fix Error Carbon)
        if ($isYearly) {
            $reportTitle = "Annual Performance Report $yearInput";
            $monthName = "Full Year";
        } else {
            $monthName = Carbon::create()->month((int)$monthInput)->format('F');
            $reportTitle = "Performance Report $monthName $yearInput";
        }

        // 3. Sedut data Firestore
        $attDocs = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/attendances")->json()['documents'] ?? [];
        $leaveDocs = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/leaves")->json()['documents'] ?? [];
        $otDocs = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/overtime")->json()['documents'] ?? [];

        $onTime = 0; $late = 0; $absent = 0;
        $annual = 0; $mc = 0; $emergency = 0; $totalDaysMonth = 0;

        // 4. FILTERING DATA
        foreach($attDocs as $doc) {
            $f = $doc['fields'] ?? [];
            // Use timestamp field (seeder uses timestampValue, old format uses date stringValue)
            $tsRaw = $f['timestamp']['timestampValue'] ?? null;
            $dateStr = $f['date']['stringValue'] ?? null;
            try {
                $carbonDate = $tsRaw ? Carbon::parse($tsRaw)->timezone('Asia/Kuala_Lumpur') : ($dateStr ? Carbon::parse($dateStr) : null);
            } catch (\Exception $e) { continue; }
            if (!$carbonDate) continue;
            if ($carbonDate->year == $yearInput && ($isYearly || $carbonDate->month == $monthInput)) {
                $status = strtolower($f['status']['stringValue'] ?? '');
                if(str_contains($status, 'late')) $late++;
                elseif(str_contains($status, 'present') || str_contains($status, 'on time')) $onTime++;
                else $absent++;
            }
        }

        foreach($leaveDocs as $doc) {
            $f = $doc['fields'] ?? [];
            if (($f['status']['stringValue'] ?? '') === 'Approved') {
                // Support snake_case (seeder) and camelCase (old)
                $startStr = $f['start_date']['stringValue']
                         ?? $f['startDate']['stringValue']
                         ?? null;
                if (!$startStr && isset($f['startDate']['timestampValue'])) {
                    $startStr = $f['startDate']['timestampValue'];
                }
                if (!$startStr) continue;
                try { $carbonDate = Carbon::parse($startStr); } catch (\Exception $e) { continue; }
                if ($carbonDate->year == $yearInput && ($isYearly || $carbonDate->month == $monthInput)) {
                    $endStr = $f['end_date']['stringValue'] ?? $f['endDate']['stringValue'] ?? $startStr;
                    try {
                        $d = Carbon::parse($startStr)->diffInDays(Carbon::parse($endStr)) + 1;
                    } catch (\Exception $e) {
                        $d = (int)($f['total_days']['integerValue'] ?? $f['totalDays']['integerValue'] ?? 1);
                    }
                    $type = $f['leave_type']['stringValue'] ?? $f['leaveType']['stringValue'] ?? '';
                    
                    $up = strtoupper(trim($type));
                    if (str_contains($up, 'ANNUAL') || $up === 'AL' || str_ends_with($up, ' AL')) $annual += $d;
                    elseif (str_contains($up, 'MEDICAL') || str_contains($up, 'MC')) $mc += $d;
                    elseif (str_contains($up, 'EMERGENCY') || $up === 'EL' || str_ends_with($up, ' EL')) $emergency += $d;
                    $totalDaysMonth += $d;
                }
            }
        }

        // 5. OT TREND
        $otTrend = array_fill(1, 12, 0);
        foreach($otDocs as $doc) {
            $f = $doc['fields'] ?? [];
            $dt = $f['date']['stringValue'] ?? '';
            if(str_starts_with($dt, $yearInput) && in_array(($f['status']['stringValue'] ?? ''), ['Verified', 'Approved'])) {
                $m = (int)date('m', strtotime($dt));
                // Support both 'hours' (seeder) and 'duration_hours' (old format)
                $otHours = floatval(
                    $f['totalHours']['doubleValue']
                    ?? $f['totalHours']['integerValue']
                    ?? $f['hours']['doubleValue']
                    ?? $f['hours']['integerValue']
                    ?? $f['duration_hours']['doubleValue']
                    ?? $f['duration_hours']['integerValue']
                    ?? 0
                );
                $otTrend[$m] += $otHours;
            }
        }
        $otDataString = implode(',', array_values($otTrend));

        // 6. QUICKCHART URLS
        $chartAtt = "https://quickchart.io/chart?c=" . urlencode("{type:'doughnut',data:{labels:['On Time','Late','Absent'],datasets:[{data:[{$onTime},{$late},{$absent}],backgroundColor:['#10b981','#f59e0b','#ef4444']}]}}");
        $chartLeave = "https://quickchart.io/chart?c=" . urlencode("{type:'bar',data:{labels:['Annual','MC','Emergency'],datasets:[{label:'Leaves',data:[{$annual},{$mc},{$emergency}],backgroundColor:['#3b82f6','#f59e0b','#ef4444']}]}}");
        $chartOT = "https://quickchart.io/chart?c=" . urlencode("{type:'line',data:{labels:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],datasets:[{label:'OT Hours',data:[{$otDataString}],borderColor:'#D6001C',fill:false}]}}");

        // 7. ZASS! Pastikan compact() hantar nama variable yang Blade nak
        $currentYear = $yearInput; 

        $pdf = Pdf::setOptions(['isRemoteEnabled' => true])
                ->loadView('pdf_analytics', compact(
                    'onTime', 'late', 'absent', 'annual', 'mc', 'emergency', 
                    'totalDaysMonth', 'chartAtt', 'chartLeave', 'chartOT', 
                    'currentYear', 'reportTitle', 'monthName'
                ));
        
        return $pdf->download("DOREMi_Report_{$monthName}_{$yearInput}.pdf");

    } catch (\Exception $e) {
        return "PDF Error: " . $e->getMessage();
    }
}
}


