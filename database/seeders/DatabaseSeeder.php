<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. BERSIHKAN DATA LAMA (TRUNCATE)
        // ==========================================
        // Kita kosongkan table dulu supaya tak bercampur data lama
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->truncate();
        DB::table('leave_requests')->truncate();
        DB::table('attendances')->truncate();
        DB::table('overtime_requests')->truncate();
        DB::table('flexible_hours_requests')->truncate();
        DB::table('notifications')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ==========================================
        // 2. VIP ACCOUNTS (Admin & Supervisor)
        // ==========================================
        
        // Admin HR
        DB::table('users')->insert([
            'name' => 'Admin HR',
            'email' => 'admin@doremi.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'department' => 'HR',
            'annual_leave_balance' => 20,
            'medical_leave_balance' => 20,
            'flexi_balance' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Supervisor Audio
        $svId = DB::table('users')->insertGetId([
            'name' => 'Supervisor Audio',
            'email' => 'sv_audio@doremi.com',
            'password' => Hash::make('password'),
            'role' => 'supervisor',
            'department' => 'Audio', 
            'annual_leave_balance' => 18,
            'medical_leave_balance' => 20,
            'flexi_balance' => 5.5,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ==========================================
        // 3. STAFF LIST (8 Orang Staff Real)
        // ==========================================
        
        $staff_list = [
            ['name' => 'Raja Balqis', 'dept' => 'Audio', 'email' => 'balqis@doremi.com'],
            ['name' => 'Amirul Faris', 'dept' => 'Lighting', 'email' => 'amirul@doremi.com'],
            ['name' => 'Hatar Zahfri', 'dept' => 'Video', 'email' => 'hatar@doremi.com'],
            ['name' => 'Alicia Wong', 'dept' => 'Marketing', 'email' => 'alicia@doremi.com'],
            ['name' => 'Muthu Kumar', 'dept' => 'Logistics', 'email' => 'muthu@doremi.com'],
            ['name' => 'Kenji Tan', 'dept' => 'Staging', 'email' => 'kenji@doremi.com'],
            ['name' => 'Nurul Izzah', 'dept' => 'General', 'email' => 'izzah@doremi.com'],
            ['name' => 'David Ooi', 'dept' => 'Audio', 'email' => 'david@doremi.com'],
        ];

        foreach ($staff_list as $staff) {
            
            // Create User
            $userId = DB::table('users')->insertGetId([
                'name' => $staff['name'],
                'email' => $staff['email'],
                'password' => Hash::make('password'),
                'role' => 'employee',
                'department' => $staff['dept'],
                'annual_leave_balance' => rand(10, 16),
                'medical_leave_balance' => 14,
                'flexi_balance' => rand(0, 8) + (rand(0, 9) / 10), // Contoh: 4.5 jam
                'created_at' => now(), 'updated_at' => now(),
            ]);

            // --- A. Generate Attendance (Hari Ini) ---
            $clockInHour = rand(8, 10); // Random masuk pukul 8, 9 atau 10
            DB::table('attendances')->insert([
                'user_id' => $userId,
                'date' => Carbon::today(),
                'clock_in_time' => Carbon::today()->setHour($clockInHour)->setMinute(rand(0, 59)),
                'status' => 'Present',
                'created_at' => now(),
            ]);

            // --- B. Generate Overtime (Rawak - 40% Chance) ---
            if (rand(1, 100) > 60) {
                $statusOT = ['Pending', 'Verified', 'Verified'][rand(0, 2)]; // Lebih banyak verified utk graf
                $event = ['Konsert Search', 'Corporate Dinner', 'Wedding Setup'][rand(0, 2)];

                DB::table('overtime_requests')->insert([
                    'user_id' => $userId,
                    'event_name' => $event,
                    'date' => Carbon::now()->subDays(rand(1, 10)),
                    'start_time' => '18:00:00',
                    'end_time' => '23:00:00',
                    'duration_hours' => rand(3, 6),
                    'reason' => 'Setup for ' . $event,
                    'status' => $statusOT,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            // --- C. Generate Leave Request (PENTING UNTUK GRAF CANTIK) ---
            // Kita buat gelung (loop) supaya setiap staff ada 2-4 rekod cuti APPROVED
            // Ini akan penuhkan graf "Leave Distribution"
            
            $numLeaves = rand(2, 4);
            
            for ($i = 0; $i < $numLeaves; $i++) {
                // Pemberat: 50% Annual, 30% MC, 20% Emergency
                $dice = rand(1, 100);
                if ($dice <= 50) $type = 'Annual';
                elseif ($dice <= 80) $type = 'MC';
                else $type = 'Emergency';

                $days = rand(1, 3);
                $pastDate = Carbon::now()->subMonths(rand(1, 3))->addDays(rand(1, 20));

                DB::table('leave_requests')->insert([
                    'user_id' => $userId,
                    'leave_type' => $type,
                    'start_date' => $pastDate,
                    'end_date' => $pastDate->copy()->addDays($days - 1),
                    'total_days' => $days,
                    'reason' => 'Personal matter / medical checkup',
                    'status' => 'Approved', // Kita set Approved supaya masuk kiraan graf
                    'admin_remark' => 'Approved by HR',
                    'created_at' => $pastDate, 
                    'updated_at' => $pastDate,
                ]);
            }

            // Tambah 1 request PENDING (Utk Dashboard Card) - 30% chance
            if (rand(1, 100) > 70) {
                DB::table('leave_requests')->insert([
                    'user_id' => $userId,
                    'leave_type' => 'Annual',
                    'start_date' => Carbon::now()->addDays(rand(5, 10)),
                    'end_date' => Carbon::now()->addDays(rand(6, 12)),
                    'total_days' => 2,
                    'reason' => 'Family vacation planning',
                    'status' => 'Pending',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            // --- D. Flexible Hours (Moved INSIDE loop) ---
            if (rand(1, 100) > 60) {
                DB::table('flexible_hours_requests')->insert([
                    'user_id' => $userId,
                    'date' => Carbon::tomorrow(),
                    'start_time' => '16:00:00',
                    'end_time' => '18:00:00',
                    'total_hours' => 2.0,
                    'reason' => 'Pick up kids',
                    'status' => 'Pending',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        // ==========================================
        // 4. NOTIFICATION
        // ==========================================
        DB::table('notifications')->insert([
            'user_id' => $svId, 
            'title' => 'New Staff Registered',
            'message' => '8 new staff profiles created in directory.',
            'type' => 'info',
            'status' => 'unread',
            'created_at' => now(),
        ]);

        // ==========================================
// 5. FEBRUARY DUMMY DATA (Manual Overtime)
// ==========================================

// Get specific user IDs from the emails you just created
$amirulId = DB::table('users')->where('email', 'amirul@doremi.com')->value('id');
$balqisId = DB::table('users')->where('email', 'balqis@doremi.com')->value('id');
$davidId = DB::table('users')->where('email', 'david@doremi.com')->value('id');

$february_data = [
    [
        'user_id' => $amirulId,
        'event_name' => 'Tech Summit Setup',
        'date' => '2026-02-01',
        'start_time' => '18:00:00',
        'end_time' => '21:00:00',
        'duration_hours' => 3,
        'reason' => 'Late night equipment rigging',
        'status' => 'Pending' // Will show as "Pending Review" in Summary
    ],
    [
        'user_id' => $balqisId,
        'event_name' => 'Gala Dinner Support',
        'date' => '2026-02-02',
        'start_time' => '19:00:00',
        'end_time' => '23:00:00',
        'duration_hours' => 4,
        'reason' => 'Audio console monitoring',
        'status' => 'Verified' // Already locked and processed
    ],
    [
        'user_id' => $davidId,
        'event_name' => 'Live Concert Breakdown',
        'date' => '2026-02-02',
        'start_time' => '23:00:00',
        'end_time' => '02:00:00',
        'duration_hours' => 3,
        'reason' => 'Packing up audio cables after show',
        'status' => 'Pending'
    ],
];
// ==========================================
// 6. FEBRUARY DUMMY DATA (Flexible Hours)
// ==========================================

// Use the same staff IDs retrieved earlier
$amirulId = DB::table('users')->where('email', 'amirul@doremi.com')->value('id');
$balqisId = DB::table('users')->where('email', 'balqis@doremi.com')->value('id');
$davidId = DB::table('users')->where('email', 'david@doremi.com')->value('id');

$february_flexi = [
    [
        'user_id' => $amirulId,
        'date' => '2026-02-03',
        'start_time' => '16:00:00',
        'end_time' => '18:00:00',
        'total_hours' => 2.0,
        'reason' => 'Pick up kids from school',
        'status' => 'Pending',
        'admin_remark' => null
    ],
    [
        'user_id' => $balqisId,
        'date' => '2026-02-03',
        'start_time' => '15:30:00',
        'end_time' => '17:30:00',
        'total_hours' => 2.0,
        'reason' => 'Family emergency',
        'status' => 'Approved',
        'admin_remark' => 'Approved - balance deducted'
    ],
    [
        'user_id' => $davidId,
        'date' => '2026-02-04',
        'start_time' => '14:00:00',
        'end_time' => '16:00:00',
        'total_hours' => 2.0,
        'reason' => 'Personal errand',
        'status' => 'Pending',
        'admin_remark' => null
    ]
];

foreach ($february_flexi as $flexi) {
    DB::table('flexible_hours_requests')->insert(array_merge($flexi, [
        'created_at' => now(),
        'updated_at' => now()
    ]));
}

foreach ($february_data as $ot) {
    DB::table('overtime_requests')->insert(array_merge($ot, [
        'created_at' => now(),
        'updated_at' => now()
    ]));
}
    }
}