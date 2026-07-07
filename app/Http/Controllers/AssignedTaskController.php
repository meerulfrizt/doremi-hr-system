<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class AssignedTaskController extends Controller
{
    private $projectId;

    public function __construct()
    {
        $this->projectId = env('FIRESTORE_PROJECT_ID', 'doremi-admin2');
    }

    public function index()
    {
        try {
            $tasksRes = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/assigned_tasks")->json();
            
            $tasks = [];
            $today = now()->format('Y-m-d');
            
            if (isset($tasksRes['documents'])) {
                foreach ($tasksRes['documents'] as $doc) {
                    $docId = basename($doc['name']);
                    $f = $doc['fields'];
                    
                    $startDate = $f['start_date']['stringValue'] ?? $f['task_date']['stringValue'] ?? '';
                    $endDate = $f['end_date']['stringValue'] ?? $startDate;
                    $neverExpire = $f['never_expire']['booleanValue'] ?? false;
                    $status = $f['status']['stringValue'] ?? 'Active';
                    
                    // Auto-expire
                    if (!$neverExpire && $endDate && $endDate < $today && $status === 'Active') {
                        $this->updateStatus($docId, 'Expired');
                        $status = 'Expired';
                    }
                    
                    $tasks[] = (object) [
                        'id' => $docId,
                        'uid' => $f['uid']['stringValue'] ?? '',
                        'staff_name' => $f['staff_name']['stringValue'] ?? 'Unknown',
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'never_expire' => $neverExpire,
                        'location_name' => $f['location_name']['stringValue'] ?? '',
                        'latitude' => $f['latitude']['doubleValue'] ?? $f['latitude']['integerValue'] ?? 0,
                        'longitude' => $f['longitude']['doubleValue'] ?? $f['longitude']['integerValue'] ?? 0,
                        'radius_meters' => $f['radius_meters']['integerValue'] ?? 200,
                        'status' => $status,
                        'created_by' => $f['created_by']['stringValue'] ?? '',
                    ];
                }
            }
            
            // sort by start_date desc
            usort($tasks, function($a, $b) {
                return strcmp($b->start_date, $a->start_date);
            });
            
            // fetch users for dropdown
            $userRes = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/users")->json();
            $users = [];
            if (isset($userRes['documents'])) {
                foreach ($userRes['documents'] as $uDoc) {
                    $uId = basename($uDoc['name']);
                    $users[] = (object) [
                        'uid' => $uId,
                        'name' => $uDoc['fields']['full_name']['stringValue'] ?? 'Unknown'
                    ];
                }
            }

            return view('tasks.assigned', compact('tasks', 'users'));
            
        } catch (\Exception $e) {
            return "Ralat: " . $e->getMessage();
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'uids' => 'required|array',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location_name' => 'required|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'integer|min:50|max:1000'
        ]);

        try {
            // Fetch users map once to get staff names instantly without hitting Firestore repeatedly
            $userRes = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/users")->json();
            $usersMap = [];
            if (isset($userRes['documents'])) {
                foreach ($userRes['documents'] as $uDoc) {
                    $uId = basename($uDoc['name']);
                    $usersMap[$uId] = $uDoc['fields']['full_name']['stringValue'] ?? 'Unknown';
                }
            }

            $successNames = [];
            foreach ($request->uids as $uid) {
                $staffName = $usersMap[$uid] ?? 'Unknown';
                
                $neverExpire = $request->has('never_expire');
                $endDate = $neverExpire ? '2099-12-31' : ($request->end_date ?: $request->start_date);

                $data = [
                    'fields' => [
                        'uid' => ['stringValue' => $uid],
                        'staff_name' => ['stringValue' => $staffName],
                        'task_date' => ['stringValue' => $request->start_date],
                        'start_date' => ['stringValue' => $request->start_date],
                        'end_date' => ['stringValue' => $endDate],
                        'never_expire' => ['booleanValue' => $neverExpire],
                        'location_name' => ['stringValue' => $request->location_name],
                        'latitude' => ['doubleValue' => (float) $request->latitude],
                        'longitude' => ['doubleValue' => (float) $request->longitude],
                        'radius_meters' => ['integerValue' => (int) ($request->radius_meters ?? 200)],
                        'status' => ['stringValue' => 'Active'],
                        'created_by' => ['stringValue' => 'admin'],
                        'created_at' => ['timestampValue' => now()->toIso8601ZuluString()]
                    ]
                ];

                Http::withoutVerifying()->post("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/assigned_tasks", $data);
                $successNames[] = $staffName;
            }

            return back()->with('success', 'Task successfully assigned to: ' . implode(', ', $successNames));
            
        } catch (\Exception $e) {
            return back()->with('error', 'Error creating task: ' . $e->getMessage());
        }
    }

    public function cancel($docId)
    {
        try {
            $this->updateStatus($docId, 'Cancelled');
            return back()->with('success', 'Task has been cancelled.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error cancelling task: ' . $e->getMessage());
        }
    }
    
    private function updateStatus($docId, $status)
    {
        $data = [
            'fields' => [
                'status' => ['stringValue' => $status]
            ]
        ];
        
        // patch status field only (updateMask)
        Http::withoutVerifying()->patch("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/assigned_tasks/{$docId}?updateMask.fieldPaths=status", $data);
    }

    public function destroy($docId)
    {
        try {
            Http::withoutVerifying()->delete("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/assigned_tasks/{$docId}");
            return back()->with('success', 'Task has been deleted permanently.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error deleting task: ' . $e->getMessage());
        }
    }
}


