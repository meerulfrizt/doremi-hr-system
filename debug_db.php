<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$projectId = env('FIREBASE_PROJECT_ID', 'doremi-admin');
$baseUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents";
$token = cache('seeder_firestore_token'); // from previous script

// fetch users
echo "USERS:\n";
$res = Http::withoutVerifying()->withHeaders(['Authorization' => "Bearer {$token}"])->get("$baseUrl/users");
foreach ($res->json()['documents'] ?? [] as $doc) {
    echo basename($doc['name']) . " | " . ($doc['fields']['full_name']['stringValue'] ?? 'Unknown') . "\n";
}

// fetch attendances
echo "\nATTENDANCES:\n";
$res = Http::withoutVerifying()->withHeaders(['Authorization' => "Bearer {$token}"])->get("$baseUrl/attendances");
foreach ($res->json()['documents'] ?? [] as $doc) {
    $f = $doc['fields'] ?? [];
    $name = $f['name']['stringValue'] ?? $f['staffName']['stringValue'] ?? 'Unknown';
    if ($name === 'Staff Member') {
        echo basename($doc['name']) . " | NAME: " . $name . " | UID: " . ($f['uid']['stringValue'] ?? $f['user_id']['stringValue'] ?? 'none') . "\n";
    }
}
