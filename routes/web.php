<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIController; 
use App\Http\Controllers\EmployeeAIController;
use App\Http\Controllers\HRAIController;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\FlexibleController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\OvertimeRequestController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AssignedTaskController;
use Kreait\Firebase\Contract\Database;

Route::get('/', function () {
    return redirect('/login'); 
});

require __DIR__.'/auth.php';

// ── TEMP DEBUG (remove after fixing) ─────────────────────────────────────────
Route::get('/debug/firestore', function () {
    $pid  = 'doremi-admin2';
    $base = "https://firestore.googleapis.com/v1/projects/{$pid}/databases/(default)/documents";

    // Attendance
    $attRes  = \Illuminate\Support\Facades\Http::withoutVerifying()->get("{$base}/attendances?pageSize=5")->json();
    $attTotal = count($attRes['documents'] ?? []);
    $attHasMore = isset($attRes['nextPageToken']) ? 'YES (pagination needed!)' : 'NO';
    $sampleAtt = ($attRes['documents'][0]['fields'] ?? null);

    // OT
    $otRes  = \Illuminate\Support\Facades\Http::withoutVerifying()->get("{$base}/overtime?pageSize=3")->json();
    $otSample = ($otRes['documents'][0]['fields'] ?? null);

    // Leaves
    $lvRes  = \Illuminate\Support\Facades\Http::withoutVerifying()->get("{$base}/leaves?pageSize=3")->json();
    $lvSample = ($lvRes['documents'][0]['fields'] ?? null);

    // Full attendance count (no pageSize limit)
    $attFullRes = \Illuminate\Support\Facades\Http::withoutVerifying()->get("{$base}/attendances")->json();
    $attFullCount = count($attFullRes['documents'] ?? []);
    $attFullHasMore = isset($attFullRes['nextPageToken']) ? 'YES' : 'NO';

    return response()->json([
        'attendance_sample_5'      => ['count_returned' => $attTotal, 'has_more' => $attHasMore, 'sample_fields' => $sampleAtt],
        'attendance_no_limit'      => ['count_returned' => $attFullCount, 'has_nextPage' => $attFullHasMore],
        'overtime_sample'          => $otSample,
        'leaves_sample'            => $lvSample,
        'raw_att_response_keys'    => array_keys($attFullRes ?? []),
    ]);
});

Route::middleware([\App\Http\Middleware\FirebaseAuth::class])->group(function () {

    // --- DASHBOARD & DIRECTORY ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/ai-alerts', [DashboardController::class, 'getAiAlerts'])->name('dashboard.ai_alerts');
    Route::get('/directory', [EmployeeController::class, 'index'])->name('directory');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees/store', [EmployeeController::class, 'store'])->name('employee.store');
    Route::get('/employees/{id}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::get('/employees/{id}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::put('/employees/{id}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{id}', [EmployeeController::class, 'destroy'])->name('employee.destroy');
    Route::post('/employees/{id}/ai-analysis', [EmployeeAIController::class, 'analyze'])->name('employee.ai.analysis');

    // --- HR AI ADD-ONS ---
    Route::post('/api/ai/ot-verify/{userId}',     [HRAIController::class, 'otVerify'])->name('ai.ot.verify');
    Route::post('/api/ai/leave-pattern/{userId}', [HRAIController::class, 'leavePattern'])->name('ai.leave.pattern');
    Route::post('/api/ai/flexi-advice/{userId}',  [HRAIController::class, 'flexiAdvice'])->name('ai.flexi.advice');
    Route::post('/api/ai/kpi-review/{userId}',    [HRAIController::class, 'kpiReview'])->name('ai.kpi.review');

    // --- ATTENDANCE, LEAVE & OT ---
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
    Route::get('/attendance/export-pdf', [AttendanceController::class, 'exportPdf'])->name('attendance.pdf');
    Route::get('/leave', [LeaveController::class, 'index'])->name('leave');
    Route::post('/leave/update/{id}', [LeaveController::class, 'updateStatus'])->name('leave.update');
    Route::get('/overtime', [OvertimeRequestController::class, 'index'])->name('overtime');
    Route::get('/overtime/review/{user_id}', [OvertimeRequestController::class, 'review'])->name('overtime.review');
    Route::post('/overtime/bulk-update', [OvertimeRequestController::class, 'bulkUpdate'])->name('overtime.bulk_update');

    // --- ASSIGNED TASKS ---
    Route::get('/tasks/assigned', [AssignedTaskController::class, 'index'])->name('tasks.assigned');
    Route::post('/tasks/assigned', [AssignedTaskController::class, 'store'])->name('tasks.store');
    Route::patch('/tasks/assigned/{id}/cancel', [AssignedTaskController::class, 'cancel'])->name('tasks.cancel');
    Route::delete('/tasks/assigned/{id}', [AssignedTaskController::class, 'destroy'])->name('tasks.destroy');

    // --- FLEXIBLE & ANALYTICS ---
    Route::get('/flexible', [FlexibleController::class, 'index'])->name('flexible');
    Route::post('/flexible/update/{id}', [FlexibleController::class, 'updateStatus'])->name('flexible.update');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/report/print', [ReportController::class, 'generate'])->name('report.print');
    Route::get('/analytics/export-pdf', [AnalyticsController::class, 'exportPdf'])->name('analytics.pdf');

    // --- AI SYSTEM (Dah dikemaskan) ---
    Route::prefix('admin')->group(function () {
        // Route untuk proses analisis AI (Dinamik mengikut ID)
        Route::get('/analyze/{id}', [AIController::class, 'analyzeStaff'])->name('admin.analyze');
        
        // Route untuk paparan list staff & insights
        Route::get('/staff-list', [AIController::class, 'staffList'])->name('admin.staff.list');
        Route::get('/ai-insights', [AIController::class, 'getInsights'])->name('ai.insights');
        Route::get('/setup-firebase', [AIController::class, 'setupDummy']);
    });

    // --- API NOTIFICATIONS ---
    Route::get('/api/live-notifications', function (Database $database = null) {
        $projectId = env('FIRESTORE_PROJECT_ID', 'doremi-admin2');
        $notifications = [];
        $count = 0;

        try {
            // 1. Fetch Leave Requests from 'leaves'
            $leavesUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/leaves";
            $leavesRes = \Illuminate\Support\Facades\Http::withoutVerifying()->get($leavesUrl);
            $leavesDocs = $leavesRes->json()['documents'] ?? [];
            foreach ($leavesDocs as $doc) {
                $f = $doc['fields'] ?? [];
                $status = $f['status']['stringValue'] ?? 'Pending';
                if (strtolower($status) === 'pending') {
                    $staffName = $f['staffName']['stringValue'] ?? 'Staff';
                    
                    // Support 'leave_type' and 'leaveType'
                    $leaveType = $f['leave_type']['stringValue'] 
                              ?? $f['leaveType']['stringValue'] 
                              ?? 'General';
                    
                    // Support multiple total days types
                    $totalDays = $f['total_days']['integerValue'] 
                              ?? $f['totalDays']['integerValue'] 
                              ?? $f['total_days']['doubleValue'] 
                              ?? $f['totalDays']['doubleValue'] 
                              ?? 1;
                    
                    // Support multiple start date formats
                    $startDate = $f['start_date']['stringValue'] 
                              ?? $f['startDate']['stringValue'] 
                              ?? '';
                    if (!$startDate && isset($f['startDate']['timestampValue'])) {
                        try {
                            $startDate = \Carbon\Carbon::parse($f['startDate']['timestampValue'])->format('d/m/Y');
                        } catch (\Exception $ex) {}
                    }
                    
                    $notifications[] = [
                        'title' => 'Leave Request',
                        'desc' => "{$staffName} applied for {$leaveType} ({$totalDays} Days)",
                        'time' => $startDate ? "Start: {$startDate}" : 'Pending approval'
                    ];
                    $count++;
                }
            }

            // 2. Fetch Overtime Claims from 'overtime'
            $otUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/overtime";
            $otRes = \Illuminate\Support\Facades\Http::withoutVerifying()->get($otUrl);
            $otDocs = $otRes->json()['documents'] ?? [];
            foreach ($otDocs as $doc) {
                $f = $doc['fields'] ?? [];
                $status = $f['status']['stringValue'] ?? 'Pending';
                if (strtolower($status) === 'pending') {
                    $staffName = $f['staffName']['stringValue'] ?? 'Staff';
                    $hours = floatval($f['totalHours']['doubleValue'] ?? ($f['totalHours']['integerValue'] ?? 0));
                    $reason = $f['reason']['stringValue'] ?? 'No reason provided';
                    $date = $f['date']['stringValue'] ?? '';
                    if ($date) {
                        try {
                            $date = \Carbon\Carbon::parse($date)->format('d/m/Y');
                        } catch (\Exception $ex) {}
                    }

                    $notifications[] = [
                        'title' => 'Overtime Claim',
                        'desc' => "{$staffName} claimed {$hours} Hours - \"{$reason}\"",
                        'time' => $date ? "Date: {$date}" : 'Pending approval'
                    ];
                    $count++;
                }
            }

            // 3. Fetch Flexi Claims from 'flexi'
            $flexiUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/flexi_requests";
            $flexiRes = \Illuminate\Support\Facades\Http::withoutVerifying()->get($flexiUrl);
            $flexiDocs = $flexiRes->json()['documents'] ?? [];
            foreach ($flexiDocs as $doc) {
                $f = $doc['fields'] ?? [];
                $status = $f['status']['stringValue'] ?? 'Pending';
                if (strtolower($status) === 'pending') {
                    $staffName = $f['staffName']['stringValue'] ?? 'Staff';
                    $hours = floatval($f['totalHours']['doubleValue'] ?? ($f['totalHours']['integerValue'] ?? 0));
                    $reason = $f['reason']['stringValue'] ?? 'No reason provided';
                    $date = $f['date']['stringValue'] ?? '';
                    if ($date) {
                        try {
                            $date = \Carbon\Carbon::parse($date)->format('d/m/Y');
                        } catch (\Exception $ex) {}
                    }

                    $notifications[] = [
                        'title' => 'Flexible Hours Request',
                        'desc' => "{$staffName} requested {$hours} Hours - \"{$reason}\"",
                        'time' => $date ? "Date: {$date}" : 'Pending approval'
                    ];
                    $count++;
                }
            }

        } catch (\Exception $e) {
            // Log or handle exceptions gracefully
        }

        return response()->json([
            'count' => $count,
            'data' => $notifications
        ]);
    })->name('api.notifications');

});


