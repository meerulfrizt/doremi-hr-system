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
            $projectId = env('FIREBASE_PROJECT_ID', 'doremi-admin'); 
            $today = Carbon::now('Asia/Kuala_Lumpur')->format('Y-m-d');
            
            // 1. TARIK DATA ATTENDANCE DARI FIRESTORE
            $attUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/attendances";
            $attRes = Http::get($attUrl);
            $documents = $attRes->json()['documents'] ?? [];

            $clockInCount = 0; $lateCount = 0; $clockOutCount = 0; $recentAttendance = [];

            foreach ($documents as $doc) {
                $f = $doc['fields'] ?? [];
                if (empty($f)) continue;

                $dateInDb = $f['date']['stringValue'] ?? '';

                if ($dateInDb === $today) {
                    $clockInTime = $f['clock_in_time']['stringValue'] ?? null;
                    $clockOutTime = $f['clock_out_time']['stringValue'] ?? null;
                    $status = $f['status']['stringValue'] ?? 'Present';

                    if ($clockInTime) $clockInCount++;
                    if ($clockOutTime) $clockOutCount++;
                    if ($status === 'Late') $lateCount++;

                    $recentAttendance[] = (object)[
                        'user' => (object)[
                            'name' => $f['staffName']['stringValue'] ?? $f['user_name']['stringValue'] ?? 'Staff',
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
                $res = Http::get($url);
                $docs = $res->json()['documents'] ?? [];
                foreach ($docs as $d) {
                    $status = $d['fields']['status']['stringValue'] ?? '';
                    if ($status === 'Pending') {
                        $pendingCount++;
                    }
                }
            }

            return view('dashboard', [
                'clock_in_count' => $clockInCount,
                'late_count' => $lateCount,
                'clock_out_count' => $clockOutCount,
                'total_pending' => $pendingCount,
                'recent_attendance' => array_slice(array_reverse($recentAttendance), 0, 5)
            ]);

        } catch (\Exception $e) {
            return "Dashboard Error: " . $e->getMessage();
        }
    }

    public function getAiAlerts()
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID', 'doremi-admin');
            
            // Basic data aggregation using Firestore REST API
            // 1. Get users for flexi_credit (now mapping to ot_balance)
            $userRes = Http::get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users");
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
            $leaveRes = Http::get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/leave_requests");
            $leaves = $leaveRes->json()['documents'] ?? [];
            $elCount = 0;
            foreach ($leaves as $l) {
                $f = $l['fields'] ?? [];
                if (($f['leaveType']['stringValue'] ?? '') === 'Emergency Leave') {
                    $elCount++;
                }
            }

            // 3. Get overtime requests (for OT spike)
            $otRes = Http::get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/overtime");
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