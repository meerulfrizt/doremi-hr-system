<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\FirestoreCache;

class EmployeeController extends Controller
{
    /**
     * 1. PAPARKAN SENARAI STAF (Directory)
     */
    public function index(Request $request)
    {
        try {
            // ✅ Guna cache — jimat Firestore reads
            $docs = FirestoreCache::getCollection('users');

            $employees = [];
            foreach ($docs as $doc) {
                $f = $doc['fields'];
                $employees[] = (object) [
                    'id'           => basename($doc['name']),
                    'full_name'    => $f['full_name']['stringValue'] ?? 'N/A',
                    'email'        => $f['email']['stringValue'] ?? 'N/A',
                    'phone_number' => $f['phone_number']['stringValue'] ?? 'N/A',
                    'address'      => $f['address']['stringValue'] ?? 'N/A',
                    'department'   => $f['department']['stringValue'] ?? 'General',
                    'role'         => $f['role']['stringValue'] ?? 'staff',
                ];
            }

            $employeeCollection = collect($employees);

            // Logic Search mengikut nama
            if ($request->filled('search')) {
                $search = strtolower($request->search);
                $employeeCollection = $employeeCollection->filter(fn($e) => 
                    str_contains(strtolower($e->full_name), $search)
                );
            }

            // Collect unique departments for filter dropdown
            $departments = $employeeCollection->pluck('department')->unique()->filter()->sort()->values()->toArray();

            // Filter by department if selected
            if ($request->filled('department')) {
                $employeeCollection = $employeeCollection->filter(
                    fn($e) => strtolower($e->department) === strtolower($request->department)
                );
            }

            // ✅ Kira pending dari cache — 0 reads tambahan
            $pendingTotal = FirestoreCache::getPendingCount();

            return view('directory', [
                'employees'     => $employeeCollection,
                'pending_total' => $pendingTotal,
                'departments'   => $departments,
            ]);

        } catch (\Exception $e) {
            return "Ralat Index: " . $e->getMessage();
        }
    }

    /**
     * 2. BUKA PAGE TAMBAH STAF
     */
    public function create()
    {
        return view('create-employee');
    }

    /**
     * 3. SIMPAN STAF BARU KE FIRESTORE
     */


    private function checkEmailExists($email)
    {
        $projectId = env('FIREBASE_PROJECT_ID', env('FIREBASE_PROJECT_ID', 'doremi-admin2'));
        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents:runQuery";
        
        $payload = [
            'structuredQuery' => [
                'from' => [['collectionId' => 'users']],
                'where' => [
                    'fieldFilter' => [
                        'field' => ['fieldPath' => 'email'],
                        'op' => 'EQUAL',
                        'value' => ['stringValue' => $email]
                    ]
                ],
                'limit' => 1
            ]
        ];

        $response = Http::post($url, $payload);
        $data = $response->json();

        if (is_array($data) && count($data) > 0 && isset($data[0]['document'])) {
            return true;
        }
        return false;
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string',
            'email' => 'required|email',
            'department' => 'required|string',
            'job_title' => 'required|string',
            'phone_number' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        try {
            if ($this->checkEmailExists($request->email)) {
                return response()->json([
                    'success' => false,
                    'step' => 'validation',
                    'message' => 'The email has already been taken.'
                ], 422);
            }

            $webApiKey = env('FIREBASE_WEB_API_KEY');
            if (!$webApiKey) {
                throw new \Exception('FIREBASE_WEB_API_KEY not configured.');
            }

            $authUrl = "https://identitytoolkit.googleapis.com/v1/accounts:signUp?key={$webApiKey}";
            $authRes = Http::post($authUrl, [
                'email' => $request->email,
                'password' => 'doremi@123',
                'returnSecureToken' => true
            ]);

            if (!$authRes->successful()) {
                $errorData = $authRes->json();
                $message = $errorData['error']['message'] ?? 'Unknown Firebase Auth Error';
                return response()->json([
                    'success' => false,
                    'step' => 'auth',
                    'message' => $message
                ], 400);
            }

            $uid = $authRes->json('localId');

            $accessToken = $this->getFirestoreAccessToken();
            $projectId = env('FIREBASE_PROJECT_ID', env('FIREBASE_PROJECT_ID', 'doremi-admin2'));
            
            $firestoreUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users?documentId={$uid}";

            $firestorePayload = [
                'fields' => [
                    'full_name'     => ['stringValue' => $request->full_name],
                    'email'         => ['stringValue' => $request->email],
                    'phone_number'  => ['stringValue' => $request->phone_number ?? ''],
                    'department'    => ['stringValue' => $request->department],
                    'Address'       => ['stringValue' => $request->address ?? ''],
                    'job_title'     => ['stringValue' => $request->job_title],
                    'al_balance'    => ['integerValue' => 12],
                    'el_balance'    => ['integerValue' => 10],
                    'mc_balance'    => ['integerValue' => 14],
                    'ot_balance'    => ['doubleValue' => 0.0],
                    'role'          => ['stringValue' => 'staff'],
                    'status'        => ['stringValue' => 'Active'],
                    'join_date'     => ['stringValue' => \Carbon\Carbon::now()->toDateString()],
                    'profile_image' => ['stringValue' => ''],
                    'updated_at'    => ['timestampValue' => \Carbon\Carbon::now()->toIso8601ZuluString()],
                ]
            ];

            $firestoreRes = Http::withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
                ->post($firestoreUrl, $firestorePayload);

            if (!$firestoreRes->successful()) {
                return response()->json([
                    'success' => false,
                    'step' => 'firestore',
                    'message' => $firestoreRes->body(),
                    'note' => "Auth account created — manual cleanup may be needed for UID: {$uid}"
                ], 500);
            }

            // ✅ Clear cache supaya Directory update on the spot
            FirestoreCache::forget('users');

            return response()->json([
                'success' => true,
                'message' => 'Staff registered successfully.',
                'uid' => $uid
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'step' => 'system',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 4. BUKA PAGE EDIT
     */
    public function edit($id)
    {
        try {
            $url = "https://firestore.googleapis.com/v1/projects/doremi-admin2/databases/(default)/documents/users/{$id}";
            $response = Http::get($url);
            $doc = $response->json();

            if (!isset($doc['fields'])) {
                return redirect()->route('directory')->with('error', 'Employee record not found.');
            }

            $f = $doc['fields'];
            $employee = (object) [
                'id'           => $id,
                'full_name'    => $f['full_name']['stringValue'] ?? '',
                'email'        => $f['email']['stringValue'] ?? '',
                'phone_number' => $f['phone_number']['stringValue'] ?? '',
                'address'      => $f['address']['stringValue'] ?? '',
                'department'   => $f['department']['stringValue'] ?? '',
                'role'         => $f['role']['stringValue'] ?? 'staff',
            ];

            return view('edit-employee', compact('employee'));

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * 5. KEMASKINI DATA (UPDATE)
     */
    public function update(Request $request, $id)
    {
        try {
            // Bina updateMask supaya Firestore tahu field mana nak ditukar
            $url = "https://firestore.googleapis.com/v1/projects/doremi-admin2/databases/(default)/documents/users/{$id}?" .
                   "updateMask.fieldPaths=full_name&updateMask.fieldPaths=email&updateMask.fieldPaths=phone_number&" .
                   "updateMask.fieldPaths=address&updateMask.fieldPaths=department&updateMask.fieldPaths=role";

            $payload = [
                'fields' => [
                    'full_name'    => ['stringValue' => $request->full_name],
                    'email'        => ['stringValue' => $request->email],
                    'phone_number' => ['stringValue' => $request->phone_number],
                    'address'      => ['stringValue' => $request->address],
                    'department'   => ['stringValue' => $request->department],
                    'role'         => ['stringValue' => $request->role],
                ]
            ];

            Http::patch($url, $payload);
            
            // ✅ Clear cache supaya Directory update
            FirestoreCache::forget('users');
            
            return redirect()->route('directory')->with('success', 'Employee record updated successfully.');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * 6. PADAM STAF
     */
    public function destroy($id)
    {
        try {
            $url = "https://firestore.googleapis.com/v1/projects/doremi-admin2/databases/(default)/documents/users/{$id}";
            Http::delete($url);
            
            // ✅ Clear cache selepas padam
            FirestoreCache::forget('users');
            
            return redirect()->route('directory')->with('success', 'Employee record deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * 7. PAPARKAN PROFIL PENUH (Show)
     */
    public function show($id)
    {
        try {
            // Tarik Info Staf
            $userUrl = "https://firestore.googleapis.com/v1/projects/doremi-admin2/databases/(default)/documents/users/{$id}";
            $userRes = Http::get($userUrl)->json();

            if (!isset($userRes['fields'])) {
                return redirect()->route('directory')->with('error', 'Employee record not found.');
            }

            $f = $userRes['fields'];
            $employee = (object) [
                'id'           => $id,
                'full_name'    => $f['full_name']['stringValue'] ?? 'N/A',
                'department'   => $f['department']['stringValue'] ?? 'General',
                'role'         => $f['role']['stringValue'] ?? 'staff',
                'email'        => $f['email']['stringValue'] ?? null,
                'phone_number' => $f['phone_number']['stringValue'] ?? null,
                'join_date'    => $f['join_date']['stringValue'] ?? null,
                'flexi_credit' => $f['ot_balance']['integerValue'] ?? ($f['ot_balance']['doubleValue'] ?? 0),

                // Safety catch untuk format data: integerValue atau stringValue
                'al_balance'   => $f['al_balance']['integerValue'] ?? ($f['al_balance']['stringValue'] ?? 0),
                'el_balance'   => $f['el_balance']['integerValue'] ?? ($f['el_balance']['stringValue'] ?? 0),
                'mc_balance'   => $f['mc_balance']['integerValue'] ?? ($f['mc_balance']['stringValue'] ?? 0),
            ];

            // Tarik History Kehadiran
            $attUrl = "https://firestore.googleapis.com/v1/projects/doremi-admin2/databases/(default)/documents/attendances";
            $attRes = Http::get($attUrl)->json();

            $history = [];
            if (isset($attRes['documents'])) {
                foreach ($attRes['documents'] as $doc) {
                    $af = $doc['fields'];
                    $recUid = $af['uid']['stringValue'] ?? $af['user_id']['stringValue'] ?? '';
                    if ($recUid == $id) {
                        $history[] = (object) [
                            'date' => $af['date']['stringValue'] ?? '',
                            'clock_in_time' => $af['clock_in_time']['stringValue'] ?? null,
                            'clock_out_time' => $af['clock_out_time']['stringValue'] ?? null,
                            'status' => $af['status']['stringValue'] ?? 'Absent',
                        ];
                    }
                }
            }

            // Kira Statistik
            $historyCol = collect($history);
            $total_present = $historyCol->whereIn('status', ['Present', 'On Time'])->count();
            $total_late    = $historyCol->where('status', 'Late')->count();
            $total_absent  = $historyCol->where('status', 'Absent')->count();

            return view('show-employee', compact(
                'employee', 
                'history', 
                'total_present', 
                'total_late', 
                'total_absent'
            ));

        } catch (\Exception $e) {
            return back()->with('error', 'Ralat: ' . $e->getMessage());
        }
    }
}

