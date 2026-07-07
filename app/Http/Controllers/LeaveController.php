<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use App\Services\NotificationService;

class LeaveController extends Controller
{
    private $projectId;

    public function __construct()
    {
        $this->projectId = env('FIRESTORE_PROJECT_ID', 'doremi-admin2');
    }

    public function index(Request $request)
    {
        try {
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/leaves";
            $response = Http::withoutVerifying()->get($url);
            $documents = $response->json()['documents'] ?? [];

            // ZASS FIX: Fetch users list to map UID to latest full_name (since 'staffName' in leaves might be outdated)
            $usersUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/users";
            $usersResponse = Http::withoutVerifying()->get($usersUrl);
            $userDocs = $usersResponse->json()['documents'] ?? [];
            $userMap = [];
            foreach ($userDocs as $uDoc) {
                $uId = basename($uDoc['name'] ?? '');
                $fullName = $uDoc['fields']['full_name']['stringValue'] ?? null;
                if ($uId && $fullName) {
                    $userMap[$uId] = $fullName;
                }
            }

            $selectedMonth = $request->input('month', Carbon::now()->month);
            $selectedYear = $request->input('year', Carbon::now()->year);
            $statusFilter = $request->input('status');

            $leaves = [];
            $stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0];

            foreach ($documents as $doc) {
                $f = $doc['fields'] ?? null;
                if (!$f) continue;

                // Mobile app uses 'start_date' (string), old seeder used 'startDate' (timestamp)
                $dateRaw = $f['start_date']['stringValue']       // mobile app
                        ?? $f['startDate']['timestampValue']     // old seeder / admin
                        ?? $f['startDate']['stringValue']        // old seeder string fallback
                        ?? $f['submitted_at']['timestampValue']  // last resort: use submitted_at
                        ?? null;

                if (!$dateRaw) continue;

                try {
                    $carbonDate = Carbon::parse($dateRaw);
                } catch (\Exception $e) {
                    continue; // skip unparseable dates
                }

                // Filter Bulan & Tahun
                if ($carbonDate->month == $selectedMonth && $carbonDate->year == $selectedYear) {

                    $status = $f['status']['stringValue'] ?? 'Pending';

                    // Update Stats
                    if (strtolower($status) === 'pending')  $stats['pending']++;
                    if (strtolower($status) === 'approved') $stats['approved']++;
                    if (strtolower($status) === 'rejected') $stats['rejected']++;

                    // Filter Status dari dropdown
                    if ($statusFilter && strtolower($status) !== strtolower($statusFilter)) continue;

                    $uid = $f['uid']['stringValue'] ?? '';
                    // Use mapped name from users collection if available, else fallback to staffName
                    $staffName = $userMap[$uid] ?? ($f['staffName']['stringValue'] ?? 'Unknown Staff');

                    // total_days: mobile app saves as int64 (integerValue), support all variants
                    $totalDays = (int)(
                        $f['total_days']['integerValue']
                        ?? $f['totalDays']['integerValue']
                        ?? $f['total_days']['doubleValue']
                        ?? $f['totalDays']['doubleValue']
                        ?? $f['total_days']['stringValue']
                        ?? 0
                    );

                    // leave_type: mobile app uses 'leave_type', old seeder used 'leaveType'
                    $leaveType = $f['leave_type']['stringValue']
                              ?? $f['leaveType']['stringValue']
                              ?? 'General';

                    $leaves[] = (object)[
                        'id'         => basename($doc['name']),
                        'uid'        => $uid,
                        'staff_name' => $staffName,
                        'type'       => $leaveType,
                        'total_days' => $totalDays,
                        'reason'     => $f['reason']['stringValue'] ?? '-',
                        'status'     => $status,
                        'start_date' => $carbonDate->format('d/m/Y'),
                        'attachment_url' => $f['attachment_url']['stringValue'] ?? null,
                    ];
                }
            }

            // Pagination logic
            $leavesCollection = collect($leaves);
            $perPage = 10;
            $currentPage = $request->input('page', 1);
            $pagedData = new LengthAwarePaginator(
                $leavesCollection->forPage($currentPage, $perPage),
                $leavesCollection->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return view('leave', [
                'leaves' => $pagedData,
                'stats' => $stats,
                'selectedMonth' => $selectedMonth,
                'selectedYear' => $selectedYear,
                'statusFilter' => $statusFilter,
                'displayDate' => Carbon::createFromDate($selectedYear, $selectedMonth, 1)->format('F Y')
            ]);

        } catch (\Exception $e) {
            return "Firestore Error: " . $e->getMessage();
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $action = $request->input('status'); // 'Approved' atau 'Rejected'
        $uid = $request->input('uid'); 
        $days = (int)$request->input('total_days');
        $type = $request->input('type');

        try {
            $accessToken = $this->getFirestoreAccessToken();

            // 1. Update status in 'leaves' collection
            $leaveUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/leaves/{$id}?updateMask.fieldPaths=status";
            $res1 = Http::withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
                ->patch($leaveUrl, [
                    'fields' => ['status' => ['stringValue' => $action]]
                ]);

            if (!$res1->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Firestore update failed: ' . $res1->body()
                ], 500);
            }

            // 2. If Approved, check and deduct user's leave balance
            if ($action === 'Approved' && $uid) {
                $userUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/users/{$uid}";
                $userResp = Http::withoutVerifying()->get($userUrl);
                $userData = $userResp->json();
                
                if (isset($userData['fields'])) {
                    $field = "al_balance"; 
                    if (str_contains(strtolower($type), "emergency")) {
                        $field = "el_balance";
                    } elseif (str_contains(strtolower($type), "medical") || str_contains(strtolower($type), "mc")) {
                        $field = "mc_balance";
                    }

                    $balanceField = $userData['fields'][$field] ?? null;
                    if ($balanceField) {
                        if (isset($balanceField['stringValue'])) {
                            $balanceType = 'stringValue';
                            $currentBalance = intval($balanceField['stringValue']);
                        } else {
                            $balanceType = 'integerValue';
                            $currentBalance = intval($balanceField['integerValue'] ?? ($balanceField['doubleValue'] ?? 0));
                        }
                    } else {
                        $balanceType = 'integerValue';
                        $currentBalance = 0;
                    }

                    $newBalance = $currentBalance - $days;
                    $balanceVal = ($balanceType === 'stringValue') ? (string)$newBalance : (int)$newBalance;

                    // Update user balance in Firestore
                    $res2 = Http::withoutVerifying()
                        ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
                        ->patch("{$userUrl}?updateMask.fieldPaths={$field}", [
                            'fields' => [$field => [$balanceType => $balanceVal]]
                        ]);

                    if (!$res2->successful()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Firestore balance update failed: ' . $res2->body()
                        ], 500);
                    }
                }
            }

            // Send notification to Firestore using NotificationService
            if ($uid && ($action === 'Approved' || $action === 'Rejected')) {
                try {
                    $notifType = ($action === 'Approved') ? 'leave_approved' : 'leave_rejected';
                    $startDate = 'General';
                    $leaveType = $type ?? 'General';
                    
                    // Fetch leave details to get start_date & leave_type
                    $leaveDocUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/leaves/{$id}";
                    $leaveDocResp = Http::withoutVerifying()->get($leaveDocUrl);
                    if ($leaveDocResp->successful() && isset($leaveDocResp->json()['fields'])) {
                        $fields = $leaveDocResp->json()['fields'];
                        $dateRaw = $fields['start_date']['stringValue']
                                ?? $fields['startDate']['timestampValue']
                                ?? $fields['startDate']['stringValue']
                                ?? null;
                        if ($dateRaw) {
                            try {
                                $startDate = Carbon::parse($dateRaw)->format('d/m/Y');
                            } catch (\Exception $e) {
                                $startDate = $dateRaw;
                            }
                        }
                        $leaveType = $fields['leave_type']['stringValue']
                                  ?? $fields['leaveType']['stringValue']
                                  ?? $leaveType;
                    }

                    $notificationService = new NotificationService();
                    $notificationService->send($uid, $notifType, [
                        'leave_type' => $leaveType,
                        'start_date' => $startDate,
                        'request_id' => $id,
                    ]);
                } catch (\Exception $notifEx) {
                    \Illuminate\Support\Facades\Log::error("Failed to send leave status notification: " . $notifEx->getMessage());
                }
            }

            return response()->json(['success' => true, 'message' => 'Leave status updated successfully.']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Firestore Error: ' . $e->getMessage()], 500);
        }
    }
}


