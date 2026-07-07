<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Services\FirestoreCache;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Jika ada query ?refresh=1, kosongkan cache dan tarik data live
            if ($request->has('refresh')) {
                FirestoreCache::forget('attendances');
                FirestoreCache::getCollection('attendances', true);
                
                // Redirect balik tanpa query 'refresh' untuk kekalkan URL bersih & elak refresh berulang
                return redirect()->route('attendance', $request->except('refresh'));
            }

            // Ambil dari cache (default 10 minit)
            $attDocs   = FirestoreCache::getCollection('attendances');
            $userDocs  = FirestoreCache::getCollection('users');
            $tasksDocs = FirestoreCache::getCollection('assigned_tasks');

            $attRes   = ['documents' => $attDocs];
            $userRes  = ['documents' => $userDocs];
            $tasksRes = ['documents' => $tasksDocs];

            $usersMap = [];
            if (isset($userRes['documents'])) {
                foreach ($userRes['documents'] as $uDoc) {
                    $uId = basename($uDoc['name']);
                    $f = $uDoc['fields'];
                    $usersMap[$uId] = [
                        'name'       => $f['full_name']['stringValue'] ?? ($f['name']['stringValue'] ?? 'Unknown Staff'),
                        'department' => $f['department']['stringValue'] ?? 'N/A'
                    ];
                }
            }

            $tasksList = [];
            if (isset($tasksRes['documents'])) {
                foreach ($tasksRes['documents'] as $tDoc) {
                    $f = $tDoc['fields'];
                    $uid = $f['uid']['stringValue'] ?? '';
                    $startDate = $f['start_date']['stringValue'] ?? ($f['task_date']['stringValue'] ?? '');
                    $endDate = $f['end_date']['stringValue'] ?? $startDate;
                    $status = $f['status']['stringValue'] ?? '';
                    if ($uid && $startDate && $status === 'Active') {
                        $tasksList[] = [
                            'uid' => $uid,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'location_name' => $f['location_name']['stringValue'] ?? 'Unknown Location',
                            'lat' => $f['latitude']['doubleValue'] ?? $f['latitude']['integerValue'] ?? 0,
                            'lng' => $f['longitude']['doubleValue'] ?? $f['longitude']['integerValue'] ?? 0,
                            'radius' => $f['radius_meters']['integerValue'] ?? config('services.hq.radius'),
                        ];
                    }
                }
            }

            $attendanceList = [];
            if (isset($attRes['documents'])) {
                foreach ($attRes['documents'] as $doc) {
                    $f = $doc['fields'];
                    $uId = $f['uid']['stringValue'] ?? $f['user_id']['stringValue'] ?? '';
                    $name = $usersMap[$uId]['name'] ?? ($f['staffName']['stringValue'] ?? $f['name']['stringValue'] ?? 'Unknown');
                    $status = $f['status']['stringValue'] ?? 'Present';
                    $type = $f['work_location']['stringValue'] ?? $f['type']['stringValue'] ?? 'Unknown';

                    $clockIn = $f['clock_in_time']['stringValue'] ?? null;
                    $clockOut = $f['clock_out_time']['stringValue'] ?? null;
                    $photoUrl = $f['photo_url']['stringValue'] ?? null;
                    $checkoutPhotoUrl = $f['checkout_photo_url']['stringValue'] ?? null;
                    $checkoutLocation = $f['checkout_location']['stringValue'] ?? null;

                    $timestamp = $f['timestamp']['timestampValue'] ?? $f['timestamp']['stringValue'] ?? null;
                    $carbonTime = $timestamp ? Carbon::parse($timestamp)->timezone('Asia/Kuala_Lumpur') : null;
                    $dateStr = $f['date']['stringValue'] ?? ($carbonTime ? $carbonTime->format('Y-m-d') : null);

                    $timeStr = '--:--';
                    if ($clockIn) {
                        if (strpos($clockIn, ':') !== false) {
                            try {
                                $timeStr = Carbon::createFromFormat('H:i', $clockIn)->format('h:i A');
                            } catch (\Exception $e) {
                                $timeStr = $clockIn;
                            }
                        } else {
                            $timeStr = $clockIn;
                        }
                    } elseif ($carbonTime) {
                        $timeStr = $carbonTime->format('h:i A');
                    }

                    $clockOutStr = '--:--';
                    if ($clockOut) {
                        if (strpos($clockOut, ':') !== false) {
                            try {
                                $clockOutStr = Carbon::createFromFormat('H:i', $clockOut)->format('h:i A');
                            } catch (\Exception $e) {
                                $clockOutStr = $clockOut;
                            }
                        } else {
                            $clockOutStr = $clockOut;
                        }
                    }

                    $parsedDateStr = null;
                    if ($dateStr && $dateStr !== '--:--') {
                        try {
                            $parsedDateStr = Carbon::parse($dateStr);
                        } catch (\Exception $e) {
                            // Abaikan ralat parse
                        }
                    }

                    $displayDate = $carbonTime ? $carbonTime->format('d M Y') : ($parsedDateStr ? $parsedDateStr->format('d M Y') : ($dateStr ?: '-'));
                    $fullTimestampStr = $carbonTime ? $carbonTime->format('d M Y, h:i A') : ($parsedDateStr ? $parsedDateStr->format('d M Y') : ($dateStr ?: '-'));

                    $lat = $f['coordinates']['geoPointValue']['latitude'] ?? $f['location']['geoPointValue']['latitude'] ?? null;
                    $lng = $f['coordinates']['geoPointValue']['longitude'] ?? $f['location']['geoPointValue']['longitude'] ?? null;

                    $distance = null;
                    $inZone = null;
                    $locationSource = '—';

                    if ($lat !== null && $lng !== null) {
                        $matchedTask = null;
                        foreach ($tasksList as $t) {
                            if ($t['uid'] === $uId && $dateStr >= $t['start_date'] && $dateStr <= $t['end_date']) {
                                $matchedTask = $t;
                                break;
                            }
                        }

                        if ($matchedTask) {
                            $refLat = $matchedTask['lat'];
                            $refLng = $matchedTask['lng'];
                            $radius = $matchedTask['radius'];
                            $locationSource = $matchedTask['location_name'];
                        } else {
                            $refLat = config('services.hq.lat');
                            $refLng = config('services.hq.lng');
                            $radius = config('services.hq.radius');
                            $locationSource = 'DOREMi HQ';
                        }
                        
                        $distance = round($this->haversine($lat, $lng, $refLat, $refLng));
                        $inZone = $distance <= $radius;
                    }

                    $dept = $usersMap[$uId]['department'] ?? 'N/A';

                    $attendanceList[] = (object) [
                        'uid' => $uId,
                        'name' => $name,
                        'department' => $dept,
                        'date' => $dateStr,
                        'display_date' => $displayDate,
                        'timestamp' => $timeStr,
                        'full_timestamp' => $fullTimestampStr,
                        'status' => $status,
                        'type' => $type,
                        'lat' => $lat,
                        'lng' => $lng,
                        'distance' => $distance,
                        'inZone' => $inZone,
                        'locationSource' => $locationSource,
                        'clock_in_time' => $timeStr,
                        'clock_out_time' => $clockOutStr,
                        'photo_url' => $photoUrl,
                        'checkout_photo_url' => $checkoutPhotoUrl,
                        'checkout_location' => $checkoutLocation
                    ];
                }
            }

            $collection = collect($attendanceList);

            if ($request->filled('search')) {
                $search = strtolower($request->search);
                $collection = $collection->filter(fn($i) => str_contains(strtolower($i->name), $search));
            }
            $date = $request->date ?? now()->toDateString();
            if ($date) {
                $collection = $collection->where('date', $date);
            }
            if ($request->filled('status')) {
                $collection = $collection->where('status', $request->status);
            }

            // Sort by timestamp descending
            $sorted = $collection->sortByDesc('full_timestamp');

            $perPage = 10;
            $currentPage = $request->input('page', 1);
            $pagedData = $sorted->slice(($currentPage - 1) * $perPage, $perPage)->all();
            $attendances = new \Illuminate\Pagination\LengthAwarePaginator($pagedData, count($sorted), $perPage, $currentPage, ['path' => $request->url(), 'query' => $request->query()]);

            return view('attendance', compact('attendances'));

        } catch (\Exception $e) {
            return "Ralat: " . $e->getMessage();
        }
    }

    private function haversine($lat1, $lng1, $lat2, $lng2): float {
        $R = 6371000;
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $deltaPhi = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);
        $a = sin($deltaPhi / 2) * sin($deltaPhi / 2) + cos($phi1) * cos($phi2) * sin($deltaLng / 2) * sin($deltaLng / 2);
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function exportPdf(Request $request)
    {
        // Logik tarik data yang sama untuk PDF (tanpa pagination)
        // ... (Boleh copy paste logik fetch di atas jika perlu)
        return "Fungsi PDF dipanggil.";
    }
}