<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DummyHRSeeder extends Seeder
{
    private string $projectId;
    private string $baseUrl;
    private float $hqLat = 3.0972881;
    private float $hqLng = 101.683066;

    public function __construct()
    {
        $this->projectId = env('FIREBASE_PROJECT_ID', 'doremi-admin2');
        $this->baseUrl   = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
    }

    public function run(): void
    {
        $this->command->info("Seeding Firestore project: {$this->projectId}");
        $token = $this->getFirestoreToken();

        $users = $this->seedUsers($token);
        $this->seedAttendance($token, $users);
        $this->seedOvertime($token, $users);
        $this->seedLeaves($token, $users);
        $this->seedFlexi($token, $users);
        $this->seedTasks($token, $users);
        $this->seedNotifications($token, $users);

        $this->command->info('Seeding complete!');
    }

    private function postDocument(string $token, string $collection, array $fields, ?string $docId = null)
    {
        $url = "{$this->baseUrl}/{$collection}";
        if ($docId) {
            $url .= "?documentId={$docId}";
        }
        $payload = ['fields' => $fields];
        $res = Http::withoutVerifying()
            ->withHeaders(['Authorization' => "Bearer {$token}"])
            ->post($url, $payload);
            
        if (!$res->successful()) {
            $this->command->error("Failed to seed {$collection}: " . $res->body());
        }
        return $res;
    }

    // 8 STAFF PROFILES
    private function seedUsers($token)
    {
        $users = [
            // EXCELLENT
            [
                'uid' => 'uid-ahmad-faris',
                'full_name' => "Ahmad Faris Bin Zulkifli", 'email' => "ahmad.faris@doremi.com",
                'phone_number' => "011-23456789", 'department' => "Audio", 'job_title' => "Audio Engineer",
                'al_balance' => 12, 'el_balance' => 10, 'mc_balance' => 14, 'ot_balance' => 0.0, 'flexi_credit' => 22.5,
                'join_date' => "2023-03-15", 'address' => "No 12, Jalan Mawar, Taman Melati, 53100 Kuala Lumpur",
                'performance_tier' => "excellent", 'updated_at' => "2026-05-01T00:00:00Z"
            ],
            [
                'uid' => 'uid-nur-hafizah',
                'full_name' => "Nur Hafizah Binti Kamarudin", 'email' => "hafizah.kamarudin@doremi.com",
                'phone_number' => "012-33445566", 'department' => "Technical Ops", 'job_title' => "Technical Lead",
                'al_balance' => 12, 'el_balance' => 10, 'mc_balance' => 14, 'ot_balance' => 0.0, 'flexi_credit' => 19.0,
                'join_date' => "2022-06-01", 'address' => "Unit 5-12, Residensi Aman, Jalan Ipoh, 51200 Kuala Lumpur",
                'performance_tier' => "excellent", 'updated_at' => "2026-05-01T00:00:00Z"
            ],
            // POOR
            [
                'uid' => 'uid-sara-irdina',
                'full_name' => "Sara Irdina Binti Mahmud", 'email' => "sara.irdina@doremi.com",
                'phone_number' => "013-98765432", 'department' => "Events & Crew", 'job_title' => "Event Coordinator",
                'al_balance' => 9, 'el_balance' => 7, 'mc_balance' => 11, 'ot_balance' => 0.0, 'flexi_credit' => 1.5,
                'join_date' => "2022-08-01", 'address' => "No 5, Jalan Kenanga, Taman Putra, 68000 Ampang",
                'performance_tier' => "poor", 'updated_at' => "2026-05-01T00:00:00Z"
            ],
            [
                'uid' => 'uid-khairul-anwar',
                'full_name' => "Khairul Anwar Bin Nordin", 'email' => "khairul.anwar@doremi.com",
                'phone_number' => "019-11223344", 'department' => "Logistics", 'job_title' => "Logistics Officer",
                'al_balance' => 8, 'el_balance' => 6, 'mc_balance' => 10, 'ot_balance' => 0.0, 'flexi_credit' => 0.5,
                'join_date' => "2023-01-15", 'address' => "No 22, Jalan Dahlia, Taman Seri Gombak, 68100 Batu Caves",
                'performance_tier' => "poor", 'updated_at' => "2026-05-01T00:00:00Z"
            ],
            // AVERAGE
            [
                'uid' => 'uid-muhammad-haziq',
                'full_name' => "Muhammad Haziq Bin Roslan", 'email' => "haziq.roslan@doremi.com",
                'phone_number' => "017-55443322", 'department' => "Technical Ops", 'job_title' => "Systems Technician",
                'al_balance' => 12, 'el_balance' => 10, 'mc_balance' => 13, 'ot_balance' => 0.0, 'flexi_credit' => 8.0,
                'join_date' => "2023-11-20", 'address' => "B-3-5, Residensi Damai, Jalan Cheras, 56100 Kuala Lumpur",
                'performance_tier' => "average", 'updated_at' => "2026-05-01T00:00:00Z"
            ],
            [
                'uid' => 'uid-nurul-ain',
                'full_name' => "Nurul Ain Binti Zainudin", 'email' => "ain.zainudin@doremi.com",
                'phone_number' => "016-77889900", 'department' => "Logistics", 'job_title' => "Warehouse Coordinator",
                'al_balance' => 11, 'el_balance' => 10, 'mc_balance' => 14, 'ot_balance' => 0.0, 'flexi_credit' => 6.5,
                'join_date' => "2024-01-10", 'address' => "No 88, Jalan Bakawali, Taman Seri Muda, 40400 Shah Alam",
                'performance_tier' => "average", 'updated_at' => "2026-05-01T00:00:00Z"
            ],
            [
                'uid' => 'uid-amirul-hakim',
                'full_name' => "Amirul Hakim Bin Suffian", 'email' => "amirul.hakim@doremi.com",
                'phone_number' => "014-22334455", 'department' => "Audio", 'job_title' => "Sound Technician",
                'al_balance' => 12, 'el_balance' => 9, 'mc_balance' => 14, 'ot_balance' => 0.0, 'flexi_credit' => 10.5,
                'join_date' => "2023-07-03", 'address' => "No 3, Jalan Teratai, Taman Keramat, 54000 Kuala Lumpur",
                'performance_tier' => "average", 'updated_at' => "2026-05-01T00:00:00Z"
            ],
            [
                'uid' => 'uid-fatin-nabilah',
                'full_name' => "Fatin Nabilah Binti Othman", 'email' => "fatin.nabilah@doremi.com",
                'phone_number' => "018-66778899", 'department' => "Events & Crew", 'job_title' => "Stage Manager",
                'al_balance' => 12, 'el_balance' => 10, 'mc_balance' => 12, 'ot_balance' => 0.0, 'flexi_credit' => 7.0,
                'join_date' => "2024-03-01", 'address' => "D-12-3, Pangsapuri Bayu, Jalan Ampang, 50450 Kuala Lumpur",
                'performance_tier' => "average", 'updated_at' => "2026-05-01T00:00:00Z"
            ]
        ];

        $userMap = [];
        foreach ($users as $u) {
            $this->command->line("Seeding user: {$u['full_name']}");
            $this->postDocument($token, 'users', [
                'full_name' => ['stringValue' => $u['full_name']],
                'email' => ['stringValue' => $u['email']],
                'phone_number' => ['stringValue' => $u['phone_number']],
                'department' => ['stringValue' => $u['department']],
                'job_title' => ['stringValue' => $u['job_title']],
                'role' => ['stringValue' => 'staff'],
                'status' => ['stringValue' => 'Active'],
                'al_balance' => ['integerValue' => $u['al_balance']],
                'el_balance' => ['integerValue' => $u['el_balance']],
                'mc_balance' => ['integerValue' => $u['mc_balance']],
                'ot_balance' => ['doubleValue' => $u['ot_balance']],
                'flexi_credit' => ['doubleValue' => $u['flexi_credit']],
                'join_date' => ['stringValue' => $u['join_date']],
                'profile_image' => ['stringValue' => ''],
                'address' => ['stringValue' => $u['address']],
                'performance_tier' => ['stringValue' => $u['performance_tier']],
                'updated_at' => ['timestampValue' => $u['updated_at']]
            ], $u['uid']);
            $userMap[$u['uid']] = $u;
        }
        return $userMap;
    }

    // ATTENDANCE
    private function seedAttendance($token, $users)
    {
        $dates = [
            '2026-05-05', '2026-05-06', '2026-05-07', '2026-05-08', '2026-05-09',
            '2026-05-12', '2026-05-13', '2026-05-14', '2026-05-15', '2026-05-16'
        ];
        
        $poorAbsentDates = ['2026-05-05', '2026-05-08', '2026-05-12', '2026-05-15'];

        foreach ($users as $uid => $u) {
            $this->command->line("Seeding attendance for: {$u['full_name']}");
            
            $avgAbsentDays = array_rand(array_flip($dates), 2);
            $avgLateDays = array_rand(array_flip($dates), 3);

            foreach ($dates as $date) {
                $isAbsent = false;
                $isLate = false;
                
                $checkInTime = '';
                $checkOutTime = '';
                
                if ($u['performance_tier'] === 'excellent') {
                    $checkInTime = "{$date}T08:" . str_pad(rand(30, 55), 2, '0', STR_PAD_LEFT) . ":00Z";
                    $checkOutTime = "{$date}T18:" . str_pad(rand(10, 59), 2, '0', STR_PAD_LEFT) . ":00Z";
                } elseif ($u['performance_tier'] === 'poor') {
                    if (in_array($date, $poorAbsentDates)) {
                        $isAbsent = true;
                    } else {
                        $isLate = true;
                        $checkInTime = "{$date}T09:" . str_pad(rand(20, 59), 2, '0', STR_PAD_LEFT) . ":00Z";
                        $checkOutTime = "{$date}T16:" . str_pad(rand(0, 45), 2, '0', STR_PAD_LEFT) . ":00Z";
                    }
                } else {
                    if (in_array($date, (array)$avgAbsentDays)) {
                        $isAbsent = true;
                    } else {
                        if (in_array($date, (array)$avgLateDays)) {
                            $isLate = true;
                            $checkInTime = "{$date}T09:" . str_pad(rand(5, 30), 2, '0', STR_PAD_LEFT) . ":00Z";
                        } else {
                            $checkInTime = "{$date}T08:" . str_pad(rand(50, 59), 2, '0', STR_PAD_LEFT) . ":00Z";
                        }
                        $checkOutTime = "{$date}T17:" . str_pad(rand(0, 45), 2, '0', STR_PAD_LEFT) . ":00Z";
                    }
                }

                if ($isAbsent) {
                    $this->postAtt($token, $uid, $u['full_name'], 'Check-In', 'Absent', "{$date}T00:00:00Z");
                } else {
                    $this->postAtt($token, $uid, $u['full_name'], 'Check-In', $isLate ? 'Late' : 'Present', $checkInTime);
                    $this->postAtt($token, $uid, $u['full_name'], 'Check-Out', 'Present', $checkOutTime);
                }
            }
        }
    }
    
    private function postAtt($token, $uid, $name, $type, $status, $timestamp)
    {
        $latOff = (mt_rand(-50, 50) / 100000);
        $lngOff = (mt_rand(-50, 50) / 100000);
        $this->postDocument($token, 'attendances', [
            'uid' => ['stringValue' => $uid],
            'name' => ['stringValue' => $name],
            'type' => ['stringValue' => $type],
            'status' => ['stringValue' => $status],
            'timestamp' => ['timestampValue' => $timestamp],
            'location' => [
                'geoPointValue' => [
                    'latitude' => $this->hqLat + $latOff,
                    'longitude' => $this->hqLng + $lngOff
                ]
            ],
            'selfie_url' => ['stringValue' => '']
        ]);
    }
    
    // OVERTIME
    private function seedOvertime($token, $users)
    {
        $this->command->line("Seeding Overtime...");
        $otData = [
            [
                'uid' => 'uid-ahmad-faris', 'name' => 'Ahmad Faris Bin Zulkifli', 'date' => '2026-05-12', 'hours' => 3.5,
                'reason' => 'Sound system setup for PWTC event', 'status' => 'Approved', 'aiVerdict' => 'PRODUCTIVE',
                'aiReason' => 'OT aligns with confirmed event schedule. Rest gap maintained above 8 hours.'
            ],
            [
                'uid' => 'uid-nur-hafizah', 'name' => 'Nur Hafizah Binti Kamarudin', 'date' => '2026-05-13', 'hours' => 2.5,
                'reason' => 'Equipment calibration before event', 'status' => 'Approved', 'aiVerdict' => 'PRODUCTIVE',
                'aiReason' => 'Consistent OT pattern aligned with project deliverables.'
            ],
            [
                'uid' => 'uid-sara-irdina', 'name' => 'Sara Irdina Binti Mahmud', 'date' => '2026-05-13', 'hours' => 2.0,
                'reason' => 'Post-event cleanup', 'status' => 'Pending', 'aiVerdict' => 'HEALTH HAZARD',
                'aiReason' => 'Rest gap of 5.5 hours detected between previous OT and today check-in.'
            ],
            [
                'uid' => 'uid-khairul-anwar', 'name' => 'Khairul Anwar Bin Nordin', 'date' => '2026-05-09', 'hours' => 4.0,
                'reason' => 'Warehouse stock check', 'status' => 'Rejected', 'aiVerdict' => 'SUSPICIOUS',
                'aiReason' => 'OT claimed on Friday eve of public holiday with no deliverable recorded.'
            ],
            [
                'uid' => 'uid-muhammad-haziq', 'name' => 'Muhammad Haziq Bin Roslan', 'date' => '2026-05-07', 'hours' => 1.5,
                'reason' => 'Server maintenance', 'status' => 'Approved', 'aiVerdict' => 'NEEDS REVIEW',
                'aiReason' => 'Irregular OT pattern without clear project alignment. Requires manual verification.'
            ]
        ];

        foreach ($otData as $ot) {
            $this->postDocument($token, 'overtime', [
                'uid' => ['stringValue' => $ot['uid']],
                'name' => ['stringValue' => $ot['name']],
                'date' => ['stringValue' => $ot['date']],
                'hours' => ['doubleValue' => $ot['hours']],
                'reason' => ['stringValue' => $ot['reason']],
                'status' => ['stringValue' => $ot['status']],
                'aiVerdict' => ['stringValue' => $ot['aiVerdict']],
                'aiReason' => ['stringValue' => $ot['aiReason']],
                'created_at' => ['timestampValue' => "{$ot['date']}T12:00:00Z"]
            ]);
        }
    }

    // LEAVES
    private function seedLeaves($token, $users)
    {
        $this->command->line("Seeding Leaves...");
        $leaves = [
            ['uid' => 'uid-ahmad-faris', 'name' => 'Ahmad Faris Bin Zulkifli', 'leave_type' => 'AL', 'start' => '2026-05-19', 'end' => '2026-05-19', 'reason' => 'Family matters', 'status' => 'Approved', 'url' => ''],
            ['uid' => 'uid-sara-irdina', 'name' => 'Sara Irdina Binti Mahmud', 'leave_type' => 'MC', 'start' => '2026-05-14', 'end' => '2026-05-14', 'reason' => 'Fever and flu', 'status' => 'Pending', 'url' => 'https://res.cloudinary.com/demo/image/upload/sample_mc.jpg'],
            ['uid' => 'uid-khairul-anwar', 'name' => 'Khairul Anwar Bin Nordin', 'leave_type' => 'EL', 'start' => '2026-05-05', 'end' => '2026-05-05', 'reason' => 'Personal emergency', 'status' => 'Rejected', 'url' => ''],
            ['uid' => 'uid-nurul-ain', 'name' => 'Nurul Ain Binti Zainudin', 'leave_type' => 'AL', 'start' => '2026-05-26', 'end' => '2026-05-27', 'reason' => 'Vacation', 'status' => 'Pending', 'url' => ''],
            ['uid' => 'uid-fatin-nabilah', 'name' => 'Fatin Nabilah Binti Othman', 'leave_type' => 'MC', 'start' => '2026-05-08', 'end' => '2026-05-08', 'reason' => 'Gastric pain', 'status' => 'Approved', 'url' => 'https://res.cloudinary.com/demo/image/upload/sample_mc2.jpg']
        ];
        
        foreach ($leaves as $lv) {
            $this->postDocument($token, 'leaves', [
                'uid' => ['stringValue' => $lv['uid']],
                'name' => ['stringValue' => $lv['name']],
                'leave_type' => ['stringValue' => $lv['leave_type']],
                'start_date' => ['stringValue' => $lv['start']],
                'end_date' => ['stringValue' => $lv['end']],
                'reason' => ['stringValue' => $lv['reason']],
                'status' => ['stringValue' => $lv['status']],
                'document_url' => ['stringValue' => $lv['url']],
                'created_at' => ['timestampValue' => "{$lv['start']}T08:00:00Z"]
            ]);
        }
    }

    // FLEXI
    private function seedFlexi($token, $users)
    {
        $this->command->line("Seeding Flexi...");
        $flexi = [
            ['uid' => 'uid-ahmad-faris', 'name' => 'Ahmad Faris Bin Zulkifli', 'type' => 'Early Out', 'date' => '2026-05-20', 'hours' => 1.5, 'reason' => 'Medical appointment', 'status' => 'Approved'],
            ['uid' => 'uid-muhammad-haziq', 'name' => 'Muhammad Haziq Bin Roslan', 'type' => 'Late In', 'date' => '2026-05-21', 'hours' => 1.0, 'reason' => 'Car breakdown', 'status' => 'Pending'],
            ['uid' => 'uid-nur-hafizah', 'name' => 'Nur Hafizah Binti Kamarudin', 'type' => 'Early Out', 'date' => '2026-05-16', 'hours' => 2.0, 'reason' => 'Child school event', 'status' => 'Approved']
        ];
        
        foreach ($flexi as $f) {
            $this->postDocument($token, 'flexi_requests', [
                'uid' => ['stringValue' => $f['uid']],
                'name' => ['stringValue' => $f['name']],
                'type' => ['stringValue' => $f['type']],
                'date' => ['stringValue' => $f['date']],
                'hours' => ['doubleValue' => $f['hours']],
                'reason' => ['stringValue' => $f['reason']],
                'status' => ['stringValue' => $f['status']],
                'created_at' => ['timestampValue' => "{$f['date']}T09:00:00Z"]
            ]);
        }
    }

    // TASKS
    private function seedTasks($token, $users)
    {
        $this->command->line("Seeding Tasks...");
        $tasks = [
            ['uid' => 'uid-ahmad-faris', 'name' => 'Ahmad Faris Bin Zulkifli', 'date' => '2026-05-20', 'location' => 'PWTC Kuala Lumpur', 'lat' => 3.152815, 'lng' => 101.712957, 'radius' => 200, 'status' => 'Active'],
            ['uid' => 'uid-muhammad-haziq', 'name' => 'Muhammad Haziq Bin Roslan', 'date' => '2026-05-07', 'location' => 'KLCC Convention Centre', 'lat' => 3.153480, 'lng' => 101.711960, 'radius' => 200, 'status' => 'Expired']
        ];
        
        foreach ($tasks as $t) {
            $this->postDocument($token, 'assigned_tasks', [
                'uid' => ['stringValue' => $t['uid']],
                'staff_name' => ['stringValue' => $t['name']],
                'task_date' => ['stringValue' => $t['date']],
                'location_name' => ['stringValue' => $t['location']],
                'latitude' => ['doubleValue' => $t['lat']],
                'longitude' => ['doubleValue' => $t['lng']],
                'radius_meters' => ['integerValue' => $t['radius']],
                'status' => ['stringValue' => $t['status']],
                'created_by' => ['stringValue' => 'admin'],
                'created_at' => ['timestampValue' => "{$t['date']}T00:00:00Z"]
            ]);
        }
    }

    // NOTIFICATIONS
    private function seedNotifications($token, $users)
    {
        $this->command->line("Seeding Notifications...");
        $notes = [
            ['uid' => 'uid-ahmad-faris', 'title' => 'OT Request Approved', 'msg' => 'Your OT request for May 12 (3.5h) has been approved.', 'type' => 'ot_approved', 'status' => 'read'],
            ['uid' => 'uid-sara-irdina', 'title' => 'Leave Request Pending', 'msg' => 'Your MC leave for May 14 is under review.', 'type' => 'leave_pending', 'status' => 'unread'],
            ['uid' => 'uid-khairul-anwar', 'title' => 'OT Request Rejected', 'msg' => 'Your OT request for May 9 has been rejected.', 'type' => 'ot_rejected', 'status' => 'read'],
            ['uid' => 'uid-nurul-ain', 'title' => 'Leave Request Pending', 'msg' => 'Your AL leave for May 26–27 is under review.', 'type' => 'leave_pending', 'status' => 'unread'],
            ['uid' => 'uid-fatin-nabilah', 'title' => 'Leave Request Approved', 'msg' => 'Your MC leave for May 8 has been approved.', 'type' => 'leave_approved', 'status' => 'read'],
            ['uid' => 'uid-nur-hafizah', 'title' => 'Flexi Request Approved', 'msg' => 'Your Early Out request for May 16 has been approved.', 'type' => 'flexi_approved', 'status' => 'unread']
        ];

        foreach ($notes as $n) {
            $this->postDocument($token, 'notifications', [
                'uid' => ['stringValue' => $n['uid']],
                'title' => ['stringValue' => $n['title']],
                'message' => ['stringValue' => $n['msg']],
                'type' => ['stringValue' => $n['type']],
                'status' => ['stringValue' => $n['status']],
                'request_id' => ['stringValue' => 'dummy-id'],
                'created_at' => ['timestampValue' => Carbon::now()->toIso8601ZuluString()]
            ]);
        }
    }

    private function getFirestoreToken(): string
    {
        return Cache::remember('seeder_firestore_token_2', 2700, function () {
            $credentialPath = env('FIREBASE_CREDENTIALS', 'storage/firebase-auth.json');
            $serviceAccountPath = base_path($credentialPath);
            if (!file_exists($serviceAccountPath)) {
                throw new \RuntimeException("Firebase service account JSON not found at: {$serviceAccountPath}");
            }
            $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
            $now = time();
            $expiry = $now + 3600;
            $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claimSet = base64_encode(json_encode([
                'iss' => $serviceAccount['client_email'],
                'sub' => $serviceAccount['client_email'],
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $expiry,
                'scope' => 'https://www.googleapis.com/auth/datastore',
            ]));
            $header = rtrim(strtr($header, '+/', '-_'), '=');
            $claimSet = rtrim(strtr($claimSet, '+/', '-_'), '=');
            $sigInput = "{$header}.{$claimSet}";
            $privateKey = $serviceAccount['private_key'];
            openssl_sign($sigInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            $signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
            $jwt = "{$sigInput}.{$signature}";
            $tokenRes = Http::withoutVerifying()->asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);
            if (!$tokenRes->successful()) {
                throw new \RuntimeException("Failed to get Firestore token: " . $tokenRes->body());
            }
            return $tokenRes->json('access_token');
        });
    }
}



