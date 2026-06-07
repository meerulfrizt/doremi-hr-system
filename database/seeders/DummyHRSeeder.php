<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * DummyHRSeeder
 *
 * Seeds Firestore with exactly 8 Malaysian staff profiles + light June 2026 HR data.
 * Designed to minimize quota usage (Attendance is 1-7 June only).
 *
 * Run via: php artisan db:seed --class=DummyHRSeeder
 */
class DummyHRSeeder extends Seeder
{
    private string $projectId;
    private string $baseUrl;

    // ─── HQ Coordinates (DOREMi HQ) ──────────────────────────────────────────
    private float $hqLat = 3.0972881;
    private float $hqLng = 101.683066;

    public function __construct()
    {
        // Use doremi-admin by default unless overridden
        $this->projectId = env('FIREBASE_PROJECT_ID', 'doremi-admin');
        $this->baseUrl   = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
    }

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════╗');
        $this->command->info('║         DOREMi HR Firestore Seeder (Lite)        ║');
        $this->command->info('╚══════════════════════════════════════════════════╝');
        $this->command->info('');

        $token = $this->getFirestoreToken();

        // 1. Generate 8 users
        $this->command->info('► PART 1: Seeding 8 staff profiles...');
        $staffMap = $this->seedUsers($token);

        // 2. Attendance for June 2026 (7 days)
        $this->command->info('');
        $this->command->info('► PART 2: Seeding attendance records (7 days from June 1-7, 2026)...');
        $this->seedAttendance($token, $staffMap);

        // 3. OT requests
        $this->command->info('');
        $this->command->info('► PART 3: Seeding overtime requests...');
        $this->seedOvertime($token, $staffMap);

        // 4. Leave requests
        $this->command->info('');
        $this->command->info('► PART 4: Seeding leave requests...');
        $this->seedLeaves($token, $staffMap);

        // 5. Flexi requests
        $this->command->info('');
        $this->command->info('► PART 5: Seeding flexi hours requests...');
        $this->seedFlexiRequests($token, $staffMap);

        $this->command->info('');
        $this->command->info('Seeding Complete! You can now check the Firebase Console.');
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PART 1 — USERS
    // ══════════════════════════════════════════════════════════════════════════

    private function seedUsers($token): array
    {
        $users = [
            // --- EXCELLENT (2) ---
            [
                'uid' => 'uid-ahmad-faris',
                'full_name' => "Ahmad Faris Bin Zulkifli", 'email' => "ahmad.faris@doremi.com",
                'phone_number' => "011-23456789", 'department' => "Audio", 'job_title' => "Audio Engineer",
                'performance_tier' => "excellent", 'al_balance' => 12, 'el_balance' => 10, 'mc_balance' => 14,
                'flexi_credit' => 22.5, 'address' => "No 12, Jalan Mawar, Taman Melati, KL",
                'join_date' => "2023-03-15"
            ],
            [
                'uid' => 'uid-nur-hafizah',
                'full_name' => "Nur Hafizah Binti Kamarudin", 'email' => "hafizah.kamarudin@doremi.com",
                'phone_number' => "012-33445566", 'department' => "Technical Ops", 'job_title' => "Technical Lead",
                'performance_tier' => "excellent", 'al_balance' => 12, 'el_balance' => 10, 'mc_balance' => 14,
                'flexi_credit' => 19.0, 'address' => "Residensi Aman, Jalan Ipoh, KL",
                'join_date' => "2022-06-01"
            ],
            // --- POOR (2) ---
            [
                'uid' => 'uid-sara-irdina',
                'full_name' => "Sara Irdina Binti Mahmud", 'email' => "sara.irdina@doremi.com",
                'phone_number' => "013-98765432", 'department' => "Events & Crew", 'job_title' => "Event Coordinator",
                'performance_tier' => "poor", 'al_balance' => 9, 'el_balance' => 7, 'mc_balance' => 11,
                'flexi_credit' => 1.5, 'address' => "Taman Putra, Ampang",
                'join_date' => "2022-08-01"
            ],
            [
                'uid' => 'uid-khairul-anwar',
                'full_name' => "Khairul Anwar Bin Nordin", 'email' => "khairul.anwar@doremi.com",
                'phone_number' => "019-11223344", 'department' => "Logistics", 'job_title' => "Logistics Officer",
                'performance_tier' => "poor", 'al_balance' => 8, 'el_balance' => 6, 'mc_balance' => 10,
                'flexi_credit' => 0.5, 'address' => "Taman Seri Gombak, Batu Caves",
                'join_date' => "2023-01-15"
            ],
            // --- AVERAGE (4) ---
            [
                'uid' => 'uid-muhammad-haziq',
                'full_name' => "Muhammad Haziq Bin Roslan", 'email' => "haziq.roslan@doremi.com",
                'phone_number' => "017-55443322", 'department' => "Technical Ops", 'job_title' => "Systems Technician",
                'performance_tier' => "average", 'al_balance' => 12, 'el_balance' => 10, 'mc_balance' => 13,
                'flexi_credit' => 8.0, 'address' => "Residensi Damai, Cheras",
                'join_date' => "2023-11-20"
            ],
            [
                'uid' => 'uid-nurul-ain',
                'full_name' => "Nurul Ain Binti Zainudin", 'email' => "ain.zainudin@doremi.com",
                'phone_number' => "016-77889900", 'department' => "Logistics", 'job_title' => "Warehouse Coordinator",
                'performance_tier' => "average", 'al_balance' => 11, 'el_balance' => 10, 'mc_balance' => 14,
                'flexi_credit' => 6.5, 'address' => "Taman Seri Muda, Shah Alam",
                'join_date' => "2024-01-10"
            ],
            [
                'uid' => 'uid-amirul-hakim',
                'full_name' => "Amirul Hakim Bin Suffian", 'email' => "amirul.hakim@doremi.com",
                'phone_number' => "014-22334455", 'department' => "Audio", 'job_title' => "Sound Technician",
                'performance_tier' => "average", 'al_balance' => 12, 'el_balance' => 9, 'mc_balance' => 14,
                'flexi_credit' => 10.5, 'address' => "Taman Keramat, KL",
                'join_date' => "2023-07-03"
            ],
            [
                'uid' => 'uid-fatin-nabilah',
                'full_name' => "Fatin Nabilah Binti Othman", 'email' => "fatin.nabilah@doremi.com",
                'phone_number' => "018-66778899", 'department' => "Events & Crew", 'job_title' => "Stage Manager",
                'performance_tier' => "average", 'al_balance' => 12, 'el_balance' => 10, 'mc_balance' => 12,
                'flexi_credit' => 7.0, 'address' => "Pangsapuri Bayu, Ampang",
                'join_date' => "2024-03-01"
            ]
        ];

        $staffMap = [];
        $i = 1;
        foreach ($users as $u) {
            $this->command->line("  Seeding user {$i}/8: {$u['full_name']} [{$u['performance_tier']}]");
            
            $fields = [
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
                'ot_balance' => ['doubleValue' => 0.0],
                'flexi_credit' => ['doubleValue' => $u['flexi_credit']],
                'join_date' => ['stringValue' => $u['join_date']],
                'profile_image' => ['stringValue' => ''],
                'address' => ['stringValue' => $u['address']],
                'performance_tier' => ['stringValue' => $u['performance_tier']],
                'updated_at' => ['timestampValue' => Carbon::now()->toIso8601ZuluString()]
            ];

            $this->postDocument($token, 'users', $fields, $u['uid']);
            $staffMap[$u['uid']] = $u;
            $i++;
        }
        return $staffMap;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PART 2 — ATTENDANCE
    // ══════════════════════════════════════════════════════════════════════════

    private function seedAttendance($token, $staffMap): void
    {
        // 7 days in June 2026 (June 1 - June 7)
        $dates = [
            '2026-06-01', '2026-06-02', '2026-06-03',
            '2026-06-04', '2026-06-05', '2026-06-06', '2026-06-07'
        ];

        // Specific absent dates for poor staff to reflect their pattern
        $poorAbsentDates = ['2026-06-03', '2026-06-06'];

        foreach ($staffMap as $uid => $staff) {
            $this->command->line("  Seeding attendance for: {$staff['full_name']}");
            
            $tier = $staff['performance_tier'];

            foreach ($dates as $date) {
                $isAbsent = false;
                $isLate = false;
                
                if ($tier === 'excellent') {
                    // Always on time
                    $checkInTime = "{$date}T08:" . str_pad(rand(30, 55), 2, '0', STR_PAD_LEFT) . ":00Z";
                    $checkOutTime = "{$date}T18:" . str_pad(rand(10, 59), 2, '0', STR_PAD_LEFT) . ":00Z";
                } elseif ($tier === 'poor') {
                    // Sometimes absent, mostly late
                    if (in_array($date, $poorAbsentDates)) {
                        $isAbsent = true;
                    } else {
                        $isLate = true;
                        $checkInTime = "{$date}T09:" . str_pad(rand(20, 59), 2, '0', STR_PAD_LEFT) . ":00Z";
                        $checkOutTime = "{$date}T16:" . str_pad(rand(0, 45), 2, '0', STR_PAD_LEFT) . ":00Z";
                    }
                } else {
                    // Average: mostly on time, occasionally late
                    if (rand(1, 10) <= 2) {
                        $isLate = true;
                        $checkInTime = "{$date}T09:" . str_pad(rand(5, 30), 2, '0', STR_PAD_LEFT) . ":00Z";
                    } else {
                        $checkInTime = "{$date}T08:" . str_pad(rand(50, 59), 2, '0', STR_PAD_LEFT) . ":00Z";
                    }
                    $checkOutTime = "{$date}T17:" . str_pad(rand(0, 45), 2, '0', STR_PAD_LEFT) . ":00Z";
                }

                if ($isAbsent) {
                    $this->postAttendanceRecord($token, $uid, $staff['full_name'], 'Check-In', 'Absent', "{$date}T00:00:00Z");
                } else {
                    $this->postAttendanceRecord($token, $uid, $staff['full_name'], 'Check-In', $isLate ? 'Late' : 'Present', $checkInTime);
                    $this->postAttendanceRecord($token, $uid, $staff['full_name'], 'Check-Out', 'Present', $checkOutTime);
                }
            }
        }
    }
    
    private function postAttendanceRecord($token, $uid, $name, $type, $status, $timestamp)
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

    // ══════════════════════════════════════════════════════════════════════════
    // PART 3 — OVERTIME
    // ══════════════════════════════════════════════════════════════════════════

    private function seedOvertime($token, $staffMap)
    {
        $otData = [
            // Excellent gets productive OT
            [
                'uid' => 'uid-ahmad-faris', 'date' => '2026-06-02', 'hours' => 3.5,
                'reason' => 'Sound system setup for PWTC event', 'status' => 'Approved', 'aiVerdict' => 'PRODUCTIVE',
                'aiReason' => 'OT aligns with confirmed event schedule. Rest gap maintained above 8 hours.'
            ],
            [
                'uid' => 'uid-nur-hafizah', 'date' => '2026-06-04', 'hours' => 2.5,
                'reason' => 'Equipment calibration before event', 'status' => 'Approved', 'aiVerdict' => 'PRODUCTIVE',
                'aiReason' => 'Consistent OT pattern aligned with project deliverables.'
            ],
            // Poor gets hazardous or suspicious OT
            [
                'uid' => 'uid-sara-irdina', 'date' => '2026-06-01', 'hours' => 2.0,
                'reason' => 'Post-event cleanup', 'status' => 'Pending', 'aiVerdict' => 'HEALTH HAZARD',
                'aiReason' => 'Rest gap of 5.5 hours detected between previous OT and today check-in.'
            ],
            [
                'uid' => 'uid-khairul-anwar', 'date' => '2026-06-05', 'hours' => 4.0,
                'reason' => 'Warehouse stock check', 'status' => 'Rejected', 'aiVerdict' => 'SUSPICIOUS',
                'aiReason' => 'OT claimed on a regular day with no deliverable recorded.'
            ],
            // Average gets normal OT
            [
                'uid' => 'uid-muhammad-haziq', 'date' => '2026-06-06', 'hours' => 1.5,
                'reason' => 'Server maintenance', 'status' => 'Approved', 'aiVerdict' => 'NEEDS REVIEW',
                'aiReason' => 'Irregular OT pattern without clear project alignment. Requires manual verification.'
            ]
        ];

        foreach ($otData as $ot) {
            $name = $staffMap[$ot['uid']]['full_name'];
            $this->postDocument($token, 'overtime', [
                'uid' => ['stringValue' => $ot['uid']],
                'name' => ['stringValue' => $name],
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

    // ══════════════════════════════════════════════════════════════════════════
    // PART 4 — LEAVES
    // ══════════════════════════════════════════════════════════════════════════

    private function seedLeaves($token, $staffMap)
    {
        $leaves = [
            ['uid' => 'uid-ahmad-faris', 'leave_type' => 'AL', 'start' => '2026-06-04', 'end' => '2026-06-04', 'reason' => 'Family matters', 'status' => 'Approved', 'url' => ''],
            ['uid' => 'uid-sara-irdina', 'leave_type' => 'MC', 'start' => '2026-06-02', 'end' => '2026-06-02', 'reason' => 'Fever and flu', 'status' => 'Pending', 'url' => 'https://res.cloudinary.com/demo/image/upload/sample_mc.jpg'],
            ['uid' => 'uid-khairul-anwar', 'leave_type' => 'EL', 'start' => '2026-06-01', 'end' => '2026-06-01', 'reason' => 'Personal emergency', 'status' => 'Rejected', 'url' => ''],
            ['uid' => 'uid-nurul-ain', 'leave_type' => 'AL', 'start' => '2026-06-06', 'end' => '2026-06-07', 'reason' => 'Vacation', 'status' => 'Pending', 'url' => ''],
            ['uid' => 'uid-fatin-nabilah', 'leave_type' => 'MC', 'start' => '2026-06-03', 'end' => '2026-06-03', 'reason' => 'Gastric pain', 'status' => 'Approved', 'url' => 'https://res.cloudinary.com/demo/image/upload/sample_mc2.jpg']
        ];
        
        foreach ($leaves as $lv) {
            $name = $staffMap[$lv['uid']]['full_name'];
            $this->postDocument($token, 'leaves', [
                'uid' => ['stringValue' => $lv['uid']],
                'name' => ['stringValue' => $name],
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

    // ══════════════════════════════════════════════════════════════════════════
    // PART 5 — FLEXI REQUESTS
    // ══════════════════════════════════════════════════════════════════════════

    private function seedFlexiRequests($token, $staffMap)
    {
        $flexi = [
            [
                'uid' => 'uid-ahmad-faris', 'date' => '2026-06-05', 'hours' => 2.0, 
                'reason' => 'School run morning traffic', 'status' => 'Approved',
                'aiVerdict' => 'PRODUCTIVE', 'aiReason' => 'Staff has sufficient flexi credit. Track record is reliable.'
            ],
            [
                'uid' => 'uid-nur-hafizah', 'date' => '2026-06-03', 'hours' => 1.5, 
                'reason' => 'Doctor appointment before work', 'status' => 'Approved',
                'aiVerdict' => 'PRODUCTIVE', 'aiReason' => 'Staff performance supports flexible arrangement.'
            ],
            [
                'uid' => 'uid-sara-irdina', 'date' => '2026-06-06', 'hours' => 3.0, 
                'reason' => 'Need to rest from event', 'status' => 'Pending',
                'aiVerdict' => 'SUSPICIOUS', 'aiReason' => 'Staff has history of frequent tardiness. Flexi credit is low (1.5h).'
            ],
            [
                'uid' => 'uid-muhammad-haziq', 'date' => '2026-06-01', 'hours' => 1.0, 
                'reason' => 'Car broke down', 'status' => 'Approved',
                'aiVerdict' => 'NEEDS REVIEW', 'aiReason' => 'Valid reason but happens on a Monday. Requires manual review.'
            ]
        ];
        
        foreach ($flexi as $fx) {
            $name = $staffMap[$fx['uid']]['full_name'];
            $this->postDocument($token, 'flexi_requests', [
                'uid' => ['stringValue' => $fx['uid']],
                'name' => ['stringValue' => $name],
                'date' => ['stringValue' => $fx['date']],
                'hours_requested' => ['doubleValue' => $fx['hours']],
                'reason' => ['stringValue' => $fx['reason']],
                'status' => ['stringValue' => $fx['status']],
                'aiVerdict' => ['stringValue' => $fx['aiVerdict']],
                'aiReason' => ['stringValue' => $fx['aiReason']],
                'created_at' => ['timestampValue' => "{$fx['date']}T07:30:00Z"]
            ]);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HTTP UTILITIES
    // ══════════════════════════════════════════════════════════════════════════

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

    private function getFirestoreToken(): string
    {
        return Cache::remember('seeder_firestore_token', 2700, function () {
            $serviceAccountPath = env('FIREBASE_SERVICE_ACCOUNT_JSON', storage_path('firebase-auth.json'));

            if (!file_exists($serviceAccountPath)) {
                throw new \RuntimeException("Firebase service account JSON not found at: {$serviceAccountPath}");
            }

            $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);    

            $jwtHeader = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $now = time();
            $jwtClaim = base64_encode(json_encode([
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/datastore',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now,
            ]));

            $signatureInput = $jwtHeader . '.' . $jwtClaim;
            openssl_sign($signatureInput, $signature, $serviceAccount['private_key'], 'sha256WithRSAEncryption');
            $jwt = $signatureInput . '.' . base64_encode($signature);

            $response = Http::withoutVerifying()->asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException("Failed to obtain access token: " . $response->body());
            }

            return $response->json('access_token');
        });
    }
}
