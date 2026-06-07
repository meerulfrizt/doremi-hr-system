<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SeedFirestoreDummyData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firestore:seed-dummy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed Firestore users and overtime_requests collections with dummy data via REST API';

    private $projectId = env('FIREBASE_PROJECT_ID', 'doremi-admin');

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Clearing old dummy data...');
        // Clear existing dummy users
        $usersResponse = Http::retry(3, 1000)->withOptions(['connect_timeout' => 30])->timeout(30)->get("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/users");
        if ($usersResponse->successful() && isset($usersResponse->json()['documents'])) {
            foreach ($usersResponse->json()['documents'] as $doc) {
                $email = $doc['fields']['email']['stringValue'] ?? '';
                if (str_ends_with($email, '@doremi.com')) {
                    Http::retry(3, 1000)->withOptions(['connect_timeout' => 30])->timeout(30)->delete("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/users/" . basename($doc['name']));
                }
            }
        }
        
        // Clear existing OT requests
        $otResponse = Http::retry(3, 1000)->withOptions(['connect_timeout' => 30])->timeout(30)->get("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/overtime");
        if ($otResponse->successful() && isset($otResponse->json()['documents'])) {
            foreach ($otResponse->json()['documents'] as $doc) {
                Http::retry(3, 1000)->withOptions(['connect_timeout' => 30])->timeout(30)->delete("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/overtime/" . basename($doc['name']));
            }
        }

        // Clear existing Flexi requests
        $flexiResponse = Http::retry(3, 1000)->withOptions(['connect_timeout' => 30])->timeout(30)->get("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/flexi");
        if ($flexiResponse->successful() && isset($flexiResponse->json()['documents'])) {
            foreach ($flexiResponse->json()['documents'] as $doc) {
                Http::retry(3, 1000)->withOptions(['connect_timeout' => 30])->timeout(30)->delete("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/flexi/" . basename($doc['name']));
            }
        }

        $this->info('Starting Firestore dummy data seeding...');

        $dummyUsers = [
            [
                'full_name' => 'Ayoh Aje',
                'email' => 'ayoh.aje@doremi.com',
                'department' => 'Audio',
                'phone_number' => '+60123456789',
            ],
            // [
            //     'full_name' => 'Siti Nurhaliza',
            //     'email' => 'siti@doremi.com',
            //     'department' => 'Creative',
            //     'phone_number' => '+60198765432',
            // ],
            // [
            //     'full_name' => 'Mamat Khalid',
            //     'email' => 'mamat@doremi.com',
            //     'department' => 'Lighting',
            //     'phone_number' => '+60111122334',
            // ],
            // [
            //     'full_name' => 'Zizan Razak',
            //     'email' => 'zizan@doremi.com',
            //     'department' => 'Audio',
            //     'phone_number' => '+60134445555',
            // ],
            // [
            //     'full_name' => 'Lisa Surihani',
            //     'email' => 'lisa@doremi.com',
            //     'department' => 'Creative',
            //     'phone_number' => '+60178889999',
            // ]
        ];

        $now = Carbon::now()->toIso8601ZuluString();

        foreach ($dummyUsers as $index => $user) {
            $this->info("Creating user: {$user['full_name']}");

            // Prepare User payload following Firestore REST API strict format
            $userPayload = [
                'fields' => [
                    'Address' => ['stringValue' => 'klate'],
                    'al_balance' => ['integerValue' => '12'],
                    'department' => ['stringValue' => $user['department']],
                    'el_balance' => ['integerValue' => '8'],
                    'email' => ['stringValue' => $user['email']],

                    'full_name' => ['stringValue' => $user['full_name']],
                    'join_date' => ['stringValue' => '2026-03-16'],
                    'mc_balance' => ['integerValue' => '12'],
                    'ot_balance' => ['doubleValue' => 10.0],
                    'phone_number' => ['stringValue' => $user['phone_number']],
                    'profile_image' => ['stringValue' => ''],
                    'role' => ['stringValue' => 'staff'],
                    'status' => ['stringValue' => 'Active'],
                    'updated_at' => ['timestampValue' => $now]
                ]
            ];

            $userResponse = Http::retry(3, 1000)->withOptions(['connect_timeout' => 30])->timeout(30)->post("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/users", $userPayload);

            if ($userResponse->successful()) {
                $userData = $userResponse->json();
                // Extract document ID from the "name" field (e.g., projects/.../documents/users/ID)
                $nameParts = explode('/', $userData['name']);
                $userId = end($nameParts);
                
                $this->info("  -> User created successfully! ID: {$userId}");

                // Create 2 Overtime Requests for this user
                $this->createOvertimeRequests($userId, $user['full_name'], $now);
                
                // Create 1 Flexi Request for this user
                $this->createFlexiRequests($userId, $user['full_name'], $now);
            } else {
                $this->error("  -> Failed to create user: " . $userResponse->body());
            }
        }

        $this->info('Seeding completed!');
    }

    private function createFlexiRequests($uid, $staffName, $timestamp)
    {
        $flexiPayload = [
            'fields' => [
                'uid' => ['stringValue' => $uid],
                'staffName' => ['stringValue' => $staffName],
                'date' => ['stringValue' => '2026-05-06'],
                'startTime' => ['stringValue' => '08:00'],
                'endTime' => ['stringValue' => '10:00'],
                'totalHours' => ['doubleValue' => 2.0],
                'reason' => ['stringValue' => 'Personal matter in the morning'],
                'status' => ['stringValue' => 'Pending'],
                'created_at' => ['timestampValue' => $timestamp]
            ]
        ];

        $flexResponse = Http::retry(3, 1000)->withOptions(['connect_timeout' => 30])->timeout(30)->post("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/flexi", $flexiPayload);

        if ($flexResponse->successful()) {
            $this->info("    -> Flexi Request created.");
        } else {
            $this->error("    -> Failed to create Flexi Request: " . $flexResponse->body());
        }
    }

    private function createOvertimeRequests($uid, $staffName, $timestamp)
    {
        $otRequests = [
            [
                'date' => '2026-05-04',
                'startTime' => '18:00',
                'endTime' => '21:00',
                'totalHours' => 3.0,
                'reason' => 'Setup event at IOI City Mall',
            ],
            [
                'date' => '2026-05-05',
                'startTime' => '19:00',
                'endTime' => '23:00',
                'totalHours' => 4.0,
                'reason' => 'Dismantle equipment after concert',
            ]
        ];

        foreach ($otRequests as $index => $req) {
            $otPayload = [
                'fields' => [
                    'uid' => ['stringValue' => $uid],
                    'staffName' => ['stringValue' => $staffName],
                    'date' => ['stringValue' => $req['date']],
                    'startTime' => ['stringValue' => $req['startTime']],
                    'endTime' => ['stringValue' => $req['endTime']],
                    'totalHours' => ['doubleValue' => $req['totalHours']],
                    'reason' => ['stringValue' => $req['reason']],
                    'status' => ['stringValue' => 'Pending'],
                    'created_at' => ['timestampValue' => $timestamp]
                ]
            ];

            $otResponse = Http::retry(3, 1000)->withOptions(['connect_timeout' => 30])->timeout(30)->post("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/overtime", $otPayload);

            if ($otResponse->successful()) {
                $this->info("    -> OT Request " . ($index + 1) . " created.");
            } else {
                $this->error("    -> Failed to create OT Request: " . $otResponse->body());
            }
        }
    }
}
