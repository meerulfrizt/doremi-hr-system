<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use App\Services\NotificationService;

class OvertimeRequestController extends Controller
{
    private $projectId;

    public function __construct()
    {
        $this->projectId = env('FIRESTORE_PROJECT_ID', 'doremi-admin2');
    }

    // 1. Overtime Summary Page (Grouping by User)
   public function index(Request $request)
{
    try {
        $projectId = $this->projectId;
        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/overtime";
        
        $response = Http::withoutVerifying()->get($url);
        $documents = $response->json()['documents'] ?? [];

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

        // 1. ZASS: Ambil input dari filter dropdown (Default: Bulan & Tahun semasa)
        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', Carbon::now()->year);
        $displayDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->format('F Y');

        $summary = [];
        $total_hours_today = 0;
        $total_hours_week = 0;
        $pending_total = 0;

        foreach ($documents as $doc) {
            $f = $doc['fields'];
            $dateStr = $f['date']['stringValue'] ?? ''; // Format: YYYY-MM-DD
            
            if (!$dateStr) continue;

            $carbonDate = Carbon::parse($dateStr);
            
            // 2. LOGIC FILTER: Kita cuma proses data yang sepadan dengan Bulan & Tahun pilihan bos
            if ($carbonDate->month == $selectedMonth && $carbonDate->year == $selectedYear) {
                
                $userId = $f['uid']['stringValue'] ?? ($f['user_id']['stringValue'] ?? '');
                $status = $f['status']['stringValue'] ?? 'Pending';
                $hours = floatval($f['totalHours']['doubleValue'] ?? ($f['totalHours']['integerValue'] ?? 0));

                // Kira stats global untuk bulan pilihan
                if ($status === 'Pending') $pending_total++;
                if ($status !== 'Rejected') {
                    if ($dateStr === date('Y-m-d')) $total_hours_today += $hours;

                    // Kira OT hours untuk minggu semasa (Isnin - Ahad)
                    $weekStart = Carbon::now()->startOfWeek(); // defaults to Monday
                    $weekEnd   = Carbon::now()->endOfWeek();   // defaults to Sunday
                    if ($carbonDate->between($weekStart, $weekEnd)) {
                        $total_hours_week += $hours;
                    }
                }

                // Grouping data ikut staff
                if (!isset($summary[$userId]) && $userId != '') {
                    $summary[$userId] = (object) [
                        'user_id' => $userId,
                        'user' => (object) [
                            'name' => $userMap[$userId] ?? ($f['staffName']['stringValue'] ?? 'Unknown Staff'),
                            'department' => $f['department']['stringValue'] ?? 'General'
                        ],
                        'total_ot_hours' => 0,
                        'pending_count' => 0
                    ];
                }

                if (isset($summary[$userId])) {
                    if ($status !== 'Rejected') {
                        $summary[$userId]->total_ot_hours += $hours;
                    }
                    if ($status === 'Pending') {
                        $summary[$userId]->pending_count += 1;
                    }
                }
            }
        }

        $summariesCollection = collect(array_values($summary));

        // Filter tambahan jika nak tengok Pending sahaja
        if ($request->filter == 'pending') {
            $summariesCollection = $summariesCollection->where('pending_count', '>', 0);
        }

        // Pagination
        $perPage = 10;
        $currentPage = $request->input('page', 1);
        $pagedData = $summariesCollection->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $summaries = new LengthAwarePaginator($pagedData, count($summariesCollection), $perPage, $currentPage, ['path' => $request->url()]);

        return view('overtime.index', compact('summaries', 'total_hours_today', 'total_hours_week', 'pending_total', 'selectedMonth', 'selectedYear', 'displayDate'));

    } catch (\Exception $e) {
        return "Firestore Error: " . $e->getMessage();
    }
}

    // 2. Audit Page (Specific User's OT History)
    public function review($user_id)
    {
        try {
            // Fetch specific user profile from 'users' collection
            $userUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/users/{$user_id}";
            $userResponse = Http::withoutVerifying()->get($userUrl);
            $userData = $userResponse->json();

            if (!isset($userData['fields'])) abort(404);

            $user = (object) [
                'id' => $user_id,
                'name' => $userData['fields']['full_name']['stringValue'] ?? ($userData['fields']['name']['stringValue'] ?? 'Staff'),
                'department' => $userData['fields']['department']['stringValue'] ?? 'General'
            ];

            // Fetch OT requests filtered by UID
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/overtime";
            $response = Http::withoutVerifying()->get($url);
            $documents = $response->json()['documents'] ?? [];

            $requestsList = [];
            foreach ($documents as $doc) {
                $f = $doc['fields'];
                $uidInDoc = $f['uid']['stringValue'] ?? ($f['user_id']['stringValue'] ?? '');

                if ($uidInDoc === $user_id) {
                    $requestsList[] = (object) [
                        'id' => basename($doc['name']),
                        'date' => $f['date']['stringValue'] ?? '',
                        'start_time' => $f['startTime']['stringValue'] ?? '--:--',
                        'end_time' => $f['endTime']['stringValue'] ?? '--:--',
                        'event_name' => $f['eventName']['stringValue'] ?? 'Task',
                        'duration_hours' => $f['totalHours']['doubleValue'] ?? ($f['totalHours']['integerValue'] ?? 0),
                        'reason' => $f['reason']['stringValue'] ?? '',
                        'status' => $f['status']['stringValue'] ?? 'Pending',
                        'admin_remarks' => $f['adminRemarks']['stringValue'] ?? ''
                    ];
                }
            }

            $requests = collect($requestsList)->sortByDesc('date');

            return view('overtime.review', compact('user', 'requests'));

        } catch (\Exception $e) {
            return "Firestore Review Error: " . $e->getMessage();
        }
    }

    // 3. Bulk Update Firestore Status
    public function bulkUpdate(Request $request)
    {
        $data = $request->input('requests');

        if ($data) {
            try {
                $accessToken = $this->getFirestoreAccessToken();
            } catch (\Exception $e) {
                return redirect()->route('overtime')->with('error', 'Authentication failed: ' . $e->getMessage());
            }

            foreach ($data as $id => $details) {
                // Construct UpdateMask URL for Overtime collection
                $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/overtime/{$id}?updateMask.fieldPaths=status&updateMask.fieldPaths=adminRemarks";
                
                $res1 = Http::withoutVerifying()
                    ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
                    ->patch($url, [
                        'fields' => [
                            'status' => ['stringValue' => $details['status']],
                            'adminRemarks' => ['stringValue' => $details['remarks'] ?? '']
                        ]
                    ]);

                if (!$res1->successful()) {
                    return redirect()->route('overtime')->with('error', 'Failed to update overtime request status: ' . $res1->body());
                }

                // OT Approval Logic: If changing from Pending to Verified (Approved)
                $oldStatus = $details['old_status'] ?? 'Pending';
                $newStatus = $details['status'] ?? 'Pending';
                $uid = $details['uid'] ?? null;
                $durationHours = floatval($details['duration_hours'] ?? 0);

                if ($uid && $durationHours > 0 && $oldStatus !== 'Verified' && $newStatus === 'Verified') {
                    // Fetch user current balances
                    $userUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/users/{$uid}";
                    $userRes = Http::withoutVerifying()->get($userUrl);
                    if ($userRes->successful() && isset($userRes->json()['fields'])) {
                        $fields = $userRes->json()['fields'];
                        
                        $otField = $fields['ot_balance'] ?? null;
                        if ($otField) {
                            if (isset($otField['stringValue'])) {
                                $currentOt = floatval($otField['stringValue']);
                            } else {
                                $currentOt = floatval($otField['doubleValue'] ?? ($otField['integerValue'] ?? 0));
                            }
                        } else {
                            $currentOt = 0.0;
                        }

                        $newOt = $currentOt + $durationHours;

                        // Check if ot_balance is stored as integerValue, doubleValue, or stringValue
                        if ($otField && isset($otField['stringValue'])) {
                            $otType = 'stringValue';
                            $otValue = (string)$newOt;
                        } elseif ($otField && isset($otField['integerValue'])) {
                            $otType = 'integerValue';
                            $otValue = (int)$newOt;
                        } else {
                            $otType = 'doubleValue';
                            $otValue = (float)$newOt;
                        }

                        // 3a. Update users -> ot_balance
                        $userUpdateUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/users/{$uid}?updateMask.fieldPaths=ot_balance";
                        $res2 = Http::withoutVerifying()
                            ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
                            ->patch($userUpdateUrl, [
                                'fields' => [
                                    'ot_balance'   => [$otType => $otValue]
                                ]
                            ]);

                        if (!$res2->successful()) {
                            return redirect()->route('overtime')->with('error', 'Failed to update user overtime balance: ' . $res2->body());
                        }
                    }
                }

                // Send notification to Firestore using NotificationService
                $statusChanged = false;
                $notifType = null;
                if ($oldStatus !== 'Verified' && $newStatus === 'Verified') {
                    $statusChanged = true;
                    $notifType = 'ot_approved';
                } elseif ($oldStatus !== 'Rejected' && $newStatus === 'Rejected') {
                    $statusChanged = true;
                    $notifType = 'ot_rejected';
                }

                if ($statusChanged && $uid) {
                    try {
                        $dateFormatted = '';
                        $hoursVal = $durationHours;

                        // Fetch overtime request details
                        $otDocUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/overtime/{$id}";
                        $otDocResp = Http::withoutVerifying()->get($otDocUrl);
                        if ($otDocResp->successful() && isset($otDocResp->json()['fields'])) {
                            $fields = $otDocResp->json()['fields'];
                            $dateRaw = $fields['date']['stringValue'] ?? null;
                            if ($dateRaw) {
                                try {
                                    $dateFormatted = Carbon::parse($dateRaw)->format('d/m/Y');
                                } catch (\Exception $e) {
                                    $dateFormatted = $dateRaw;
                                }
                            }
                            if ($hoursVal <= 0) {
                                $hoursVal = floatval($fields['totalHours']['doubleValue'] ?? ($fields['totalHours']['integerValue'] ?? 0));
                            }
                        }

                        $notificationService = new NotificationService();
                        $notificationService->send($uid, $notifType, [
                            'date' => $dateFormatted,
                            'hours' => $hoursVal,
                            'request_id' => $id,
                        ]);
                    } catch (\Exception $notifEx) {
                        \Illuminate\Support\Facades\Log::error("Failed to send overtime status notification: " . $notifEx->getMessage());
                    }
                }
            }
        }

        return redirect()->route('overtime')->with('success', 'Overtime records updated successfully in Firestore!');
    }
}


