<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $projectId = env('FIRESTORE_PROJECT_ID', 'doremi-admin2'); 
            $today = Carbon::now('Asia/Kuala_Lumpur')->format('Y-m-d');
            
            // 1. TARIK DATA ATTENDANCE DARI FIRESTORE
            $attUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/attendances";
            $attRes = Http::withoutVerifying()->get($attUrl);
            $documents = $attRes->json()['documents'] ?? [];

            // ZASS FIX: Fetch users list to map UID to latest full_name
            $usersUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users";
            $usersResponse = Http::withoutVerifying()->get($usersUrl);
            $userDocs = $usersResponse->json()['documents'] ?? [];
            $userMap = [];
            foreach ($userDocs as $uDoc) {
                $uId = basename($uDoc['name'] ?? '');
                $fullName = $uDoc['fields']['full_name']['stringValue'] ?? ($uDoc['fields']['name']['stringValue'] ?? null);
                if ($uId && $fullName) {
                    $userMap[$uId] = $fullName;
                }
            }

            $clockInCount = 0; $lateCount = 0; $clockOutCount = 0; $recentAttendance = [];

            foreach ($documents as $doc) {
                $f = $doc['fields'] ?? [];
                if (empty($f)) continue;

                $dateInDb = $f['date']['stringValue'] ?? '';

                if ($dateInDb === $today) {
                    $clockInTime = $f['clock_in_time']['stringValue'] ?? null;
                    $clockOutTime = $f['clock_out_time']['stringValue'] ?? null;
                    $status = $f['status']['stringValue'] ?? 'Present';
                    $uid = $f['uid']['stringValue'] ?? $f['user_id']['stringValue'] ?? '';

                    if ($clockInTime) $clockInCount++;
                    if ($clockOutTime) $clockOutCount++;
                    if ($status === 'Late') $lateCount++;

                    $recentAttendance[] = (object)[
                        'user' => (object)[
                            'name' => $userMap[$uid] ?? ($f['staffName']['stringValue'] ?? $f['user_name']['stringValue'] ?? 'Staff'),
                            'department' => $f['department']['stringValue'] ?? 'Crew'
                        ],
                        'clock_in_time' => $clockInTime,
                        'clock_out_time' => $clockOutTime,
                        'status' => $status
                    ];
                }
            }

            // 2. TARIK DATA PENDING (LIVE DARI 3 COLLECTIONS)
            $pendingCount = 0;
            $collections = ['leaves', 'overtime', 'flexi'];
            
            foreach ($collections as $col) {
                $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/{$col}";
                $res = Http::withoutVerifying()->get($url);
                $docs = $res->json()['documents'] ?? [];
                foreach ($docs as $d) {
                    $status = $d['fields']['status']['stringValue'] ?? '';
                    if ($status === 'Pending') {
                        $pendingCount++;
                    }
                }
            }

            // 3. TARIK DATA EXTERNAL TASKS (ASSIGNED TASKS) HARI INI
            $tasksUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/assigned_tasks";
            $tasksRes = Http::withoutVerifying()->get($tasksUrl);
            $taskDocs = $tasksRes->json()['documents'] ?? [];
            $todaysTasks = [];

            foreach ($taskDocs as $td) {
                $f = $td['fields'] ?? [];
                $tDate = $f['task_date']['stringValue'] ?? ($f['date']['stringValue'] ?? null);
                
                if ($tDate === $today) {
                    $staffId = $f['staff_id']['stringValue'] ?? '';
                    $staffName = $userMap[$staffId] ?? 'Crew';
                    $todaysTasks[] = (object)[
                        'staff_name' => $staffName,
                        'location' => $f['location_name']['stringValue'] ?? 'Unknown Location',
                        'time' => $f['start_time']['stringValue'] ?? '--:--'
                    ];
                }
            }

            return view('dashboard', [
                'clock_in_count' => $clockInCount,
                'late_count' => $lateCount,
                'clock_out_count' => $clockOutCount,
                'total_pending' => $pendingCount,
                'recent_attendance' => array_slice(array_reverse($recentAttendance), 0, 5),
                'todays_tasks' => $todaysTasks
            ]);

        } catch (\Exception $e) {
            return "Dashboard Error: " . $e->getMessage();
        }
    }

    public function getAiAlerts()
    {
        try {
            $projectId = env('FIRESTORE_PROJECT_ID', 'doremi-admin2');
            
            // Basic data aggregation using Firestore REST API
            // 1. Get users for flexi_credit (now mapping to ot_balance)
            $userRes = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users");
            $users = $userRes->json()['documents'] ?? [];
            
            $lowFlexiUsers = [];
            $highFlexiUsers = [];
            foreach ($users as $u) {
                $f = $u['fields'] ?? [];
                $name = $f['full_name']['stringValue'] ?? 'Unknown';
                $flexi = $f['ot_balance']['doubleValue'] ?? ($f['ot_balance']['integerValue'] ?? 0);
                if ($flexi < 2) $lowFlexiUsers[] = $name;
                if ($flexi > 20) $highFlexiUsers[] = $name;
            }

            // 2. Get leave requests (for EL count)
            $leaveRes = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/leaves");
            $leaves = $leaveRes->json()['documents'] ?? [];
            $elCount = 0;
            foreach ($leaves as $l) {
                $f = $l['fields'] ?? [];
                
                $status = strtolower($f['status']['stringValue'] ?? '');
                if ($status !== 'approved') continue;
                
                $type = $f['leave_type']['stringValue'] ?? $f['leaveType']['stringValue'] ?? '';
                $up = strtoupper(trim($type));
                if (str_contains($up, 'EMERGENCY') || $up === 'EL' || str_ends_with($up, ' EL')) {
                    $elCount++;
                }
            }

            // 3. Get overtime requests (for OT spike)
            $otRes = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/overtime");
            $ots = $otRes->json()['documents'] ?? [];
            $otCount = count($ots);

            // Construct prompt for Gemini
            $apiKey = env('GEMINI_API_KEY');
            if (empty($apiKey)) {
                return response()->json([
                    ['type' => 'info', 'message' => 'Please set GEMINI_API_KEY in .env to enable AI Smart Alerts.', 'meta' => 'Configuration Missing']
                ]);
            }

            $prompt = "You are an HR AI Analyst. Based on the following current HR data, generate 2-3 short, actionable alerts for the HR manager.
Data:
- Users with critically low flexi-credit (< 2H): " . implode(', ', $lowFlexiUsers) . "
- Users with very high flexi-credit (> 20H): " . implode(', ', $highFlexiUsers) . "
- Total Emergency Leaves requested recently: {$elCount}
- Total Overtime requests recently: {$otCount}

Return your response strictly as a JSON array of objects. Do not include markdown formatting like ```json.
Format:
[
  {
    \"type\": \"danger|warn|info\", 
    \"message\": \"Short actionable message\", 
    \"meta\": \"E.g., 5 Users Affected\"
  }
]";

            $geminiRes = Http::withoutVerifying()
                ->timeout(20)
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}",
                    [
                        'contents' => [['parts' => [['text' => $prompt]]]],
                        'generationConfig' => ['temperature' => 0.4]
                    ]
                );

            $data = $geminiRes->json();
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $text = trim($data['candidates'][0]['content']['parts'][0]['text']);
                // Remove ```json and ``` if Gemini still includes it
                $text = str_replace(['```json', '```'], '', $text);
                $alerts = json_decode(trim($text), true);
                if (is_array($alerts)) {
                    return response()->json($alerts);
                }
            }

            return response()->json([
                ['type' => 'info', 'message' => 'AI analysis completed, but no critical anomalies found.', 'meta' => 'All Good']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                ['type' => 'danger', 'message' => 'AI Service currently unavailable.', 'meta' => 'System Error']
            ], 500);
        }
    }
}


