<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use App\Services\NotificationService;

class FlexibleController extends Controller
{
    private $projectId;

    public function __construct()
    {
        $this->projectId = env('FIRESTORE_PROJECT_ID', 'doremi-admin2');
    }

    public function index(Request $request)
    {
        try {
            // ZASS: Tukar ke collection 'flexi'
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/flexi";
            $response = Http::withoutVerifying()->get($url);
            $documents = $response->json()['documents'] ?? [];

            // ZASS FIX: Fetch users list to map UID to latest full_name
            $usersUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/users";
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

            $flexList = [];
            $stats = ['pending' => 0, 'approved_today' => 0, 'total_this_month' => 0];

            $today = Carbon::today()->format('Y-m-d');
            $thisMonth = Carbon::now()->month;

            foreach ($documents as $doc) {
                $f = $doc['fields'] ?? null;
                if (!$f) continue;

                $uid = $f['uid']['stringValue'] ?? '';
                $status = $f['status']['stringValue'] ?? 'Pending';
                $dateStr = $f['date']['stringValue'] ?? '';

                if ($status === 'Pending') $stats['pending']++;
                if ($status === 'Approved' && $dateStr === $today) $stats['approved_today']++;
                if ($dateStr !== '') {
                    $carbonDate = Carbon::parse($dateStr);
                    if ($carbonDate->month == $thisMonth && $carbonDate->year == Carbon::now()->year) {
                        $stats['total_this_month']++;
                    }
                }

                // ZASS: Tarik baki credit staf secara live untuk paparan table
                $userResp = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/users/{$uid}");
                $userData = $userResp->json();
                $staffBalance = (float)($userData['fields']['ot_balance']['doubleValue'] ?? ($userData['fields']['ot_balance']['integerValue'] ?? 0));

                $flexList[] = (object) [
                    'id' => basename($doc['name']),
                    'uid' => $uid,
                    'staff_name' => $userMap[$uid] ?? ($f['staffName']['stringValue'] ?? 'Unknown'),
                    'date' => $dateStr,
                    'start_time' => $f['startTime']['stringValue'] ?? '',
                    'end_time' => $f['endTime']['stringValue'] ?? '',
                    'total_hours' => $f['totalHours']['integerValue'] ?? ($f['totalHours']['doubleValue'] ?? 0),
                    'status' => $status,
                    'staff_balance' => $staffBalance // Hantar baki ke Blade
                ];
            }

            $flexCollection = collect($flexList)->sortByDesc('date');

            // Filter logic
            if ($request->filled('status')) {
                $flexCollection = $flexCollection->where('status', $request->status);
            }
            if ($request->filled('date')) {
                $flexCollection = $flexCollection->where('date', $request->date);
            }

            $perPage = 10;
            $currentPage = $request->input('page', 1);
            $pagedData = new LengthAwarePaginator(
                $flexCollection->forPage($currentPage, $perPage)->values(),
                $flexCollection->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return view('flexible', ['flexibleRequests' => $pagedData, 'stats' => $stats]);

        } catch (\Exception $e) {
            return "Firestore Error: " . $e->getMessage();
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $action = $request->input('status'); // 'Approved' atau 'Rejected'
        $uid = $request->input('uid'); 
        $requestedHours = (float)$request->input('total_hours');

        try {
            $accessToken = $this->getFirestoreAccessToken();

            // 1. Jika Rejected, update status di collection 'flexi'
            if ($action === 'Rejected') {
                $res = Http::withoutVerifying()
                    ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
                    ->patch("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/flexi/{$id}?updateMask.fieldPaths=status", [
                        'fields' => ['status' => ['stringValue' => 'Rejected']]
                    ]);
                if (!$res->successful()) {
                    return response()->json(['success' => false, 'message' => 'Firestore reject failed: ' . $res->body()], 500);
                }

                // Send rejection notification
                if ($uid) {
                    try {
                        $dateFormatted = '';
                        $flexiDocUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/flexi/{$id}";
                        $flexiDocResp = Http::withoutVerifying()->get($flexiDocUrl);
                        if ($flexiDocResp->successful() && isset($flexiDocResp->json()['fields'])) {
                            $fields = $flexiDocResp->json()['fields'];
                            $dateRaw = $fields['date']['stringValue'] ?? null;
                            if ($dateRaw) {
                                try {
                                    $dateFormatted = Carbon::parse($dateRaw)->format('d/m/Y');
                                } catch (\Exception $e) {
                                    $dateFormatted = $dateRaw;
                                }
                            }
                        }

                        $notificationService = new NotificationService();
                        $notificationService->send($uid, 'flexi_rejected', [
                            'date' => $dateFormatted,
                            'request_id' => $id,
                        ]);
                    } catch (\Exception $notifEx) {
                        \Illuminate\Support\Facades\Log::error("Failed to send flexi rejection notification: " . $notifEx->getMessage());
                    }
                }

                return response()->json(['success' => true, 'message' => 'Request Rejected.']);
            }

            // 2. Jika Approved, check baki 'flexi_credit' staf
            $userResp = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/users/{$uid}");
            $userData = $userResp->json();
            
            if (!isset($userData['fields'])) {
                return response()->json(['success' => false, 'message' => 'User not found in Firestore.'], 404);
            }

            // ZASS: Ambil field 'ot_balance' (handle integer/double/string) for flexi credit calculation
            $otField = $userData['fields']['ot_balance'] ?? null;
            if ($otField) {
                if (isset($otField['stringValue'])) {
                    $currentCredit = floatval($otField['stringValue']);
                } else {
                    $currentCredit = floatval($otField['doubleValue'] ?? ($otField['integerValue'] ?? 0));
                }
            } else {
                $currentCredit = 0.0;
            }

            if ($currentCredit >= $requestedHours) {
                // A. Tolak baki credit dalam users
                $newCredit = $currentCredit - $requestedHours;
                
                // Determine user ot_balance field type to preserve schema consistency
                if ($otField && isset($otField['stringValue'])) {
                    $otType = 'stringValue';
                    $otValue = (string)$newCredit;
                } elseif ($otField && isset($otField['integerValue'])) {
                    $otType = 'integerValue';
                    $otValue = (int)$newCredit;
                } else {
                    $otType = 'doubleValue';
                    $otValue = (float)$newCredit;
                }

                $res1 = Http::withoutVerifying()
                    ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
                    ->patch("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/users/{$uid}?updateMask.fieldPaths=ot_balance", [
                        'fields' => ['ot_balance' => [$otType => $otValue]]
                    ]);

                if (!$res1->successful()) {
                    return response()->json(['success' => false, 'message' => 'Firestore balance update failed: ' . $res1->body()], 500);
                }

                // B. Update status permohonan jadi Approved dlm collection 'flexi'
                $res2 = Http::withoutVerifying()
                    ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
                    ->patch("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/flexi/{$id}?updateMask.fieldPaths=status", [
                        'fields' => ['status' => ['stringValue' => 'Approved']]
                    ]);

                if (!$res2->successful()) {
                    return response()->json(['success' => false, 'message' => 'Firestore flexi status update failed: ' . $res2->body()], 500);
                }

                // Send approval notification
                if ($uid) {
                    try {
                        $dateFormatted = '';
                        $flexiDocUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/flexi/{$id}";
                        $flexiDocResp = Http::withoutVerifying()->get($flexiDocUrl);
                        if ($flexiDocResp->successful() && isset($flexiDocResp->json()['fields'])) {
                            $fields = $flexiDocResp->json()['fields'];
                            $dateRaw = $fields['date']['stringValue'] ?? null;
                            if ($dateRaw) {
                                try {
                                    $dateFormatted = Carbon::parse($dateRaw)->format('d/m/Y');
                                } catch (\Exception $e) {
                                    $dateFormatted = $dateRaw;
                                }
                            }
                        }

                        $notificationService = new NotificationService();
                        $notificationService->send($uid, 'flexi_approved', [
                            'date' => $dateFormatted,
                            'request_id' => $id,
                        ]);
                    } catch (\Exception $notifEx) {
                        \Illuminate\Support\Facades\Log::error("Failed to send flexi approval notification: " . $notifEx->getMessage());
                    }
                }

                return response()->json(['success' => true, 'message' => "Approved. Remaining flexi credit: {$newCredit} hours."]);
            } else {
                return response()->json(['success' => false, 'message' => "Insufficient credit! Staff has {$currentCredit}H but requested {$requestedHours}H."], 422);
            }

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Firestore Error: ' . $e->getMessage()], 500);
        }
    }
}


