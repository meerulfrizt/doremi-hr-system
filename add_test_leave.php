<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$projectId = env('FIREBASE_PROJECT_ID', 'doremi-admin');
$baseUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents";

function getFirestoreToken() {
    $serviceAccountPath = env('FIREBASE_SERVICE_ACCOUNT_JSON', storage_path('firebase-auth.json'));
    if (!file_exists($serviceAccountPath)) {
        return null;
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
        return null;
    }
    return $response->json('access_token');
}

$token = getFirestoreToken();

if (!$token) {
    die("Gagal mendapatkan token Firebase. Sila pastikan firebase-auth.json ada di storage/.");
}

$uid = "uid-fatin-nabilah";
$name = "Fatin Nabilah Binti Othman";

$leavesToInsert = [
    [
        'type' => 'Medical Leave (MC)',
        'start' => '2026-06-03',
        'end' => '2026-06-04',
        'days' => 2,
        'reason' => 'Demam panas',
        'status' => 'Approved'
    ],
    [
        'type' => 'Emergency Leave (EL)',
        'start' => '2026-06-15',
        'end' => '2026-06-15',
        'days' => 1,
        'reason' => 'Kecemasan keluarga',
        'status' => 'Pending'
    ],
    [
        'type' => 'Medical Leave (MC)',
        'start' => '2026-06-22',
        'end' => '2026-06-22',
        'days' => 1,
        'reason' => 'Sakit perut',
        'status' => 'Approved'
    ]
];

echo "Adding test leave records for {$name}...\n";

foreach ($leavesToInsert as $l) {
    $fields = [
        'uid' => ['stringValue' => $uid],
        'staff_name' => ['stringValue' => $name],
        'leaveType' => ['stringValue' => $l['type']],
        'startDate' => ['stringValue' => $l['start']],
        'endDate' => ['stringValue' => $l['end']],
        'totalDays' => ['integerValue' => $l['days']],
        'reason' => ['stringValue' => $l['reason']],
        'status' => ['stringValue' => $l['status']],
        'attachment_url' => ['stringValue' => ''],
        'appliedOn' => ['stringValue' => now()->subDays(rand(1, 10))->toIso8601String()]
    ];

    $res = Http::withoutVerifying()
        ->withHeaders(['Authorization' => "Bearer {$token}"])
        ->post("{$baseUrl}/leaves", ['fields' => $fields]);

    if ($res->successful()) {
        echo "✅ Added: {$l['type']} ({$l['days']} days)\n";
    } else {
        echo "❌ Failed to add: " . $res->body() . "\n";
    }
}

echo "Siap! Sila refresh dashboard anda.";
