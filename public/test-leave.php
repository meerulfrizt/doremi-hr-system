<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $app->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Http;

$projectId = "doremi-admin";
$url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/leaves";
$response = Http::withoutVerifying()->get($url);
$data = $response->json();

echo "LEAVES LIST (RAW JSON):\n";
$documents = $data['documents'] ?? [];
if (empty($documents)) {
    echo "No documents found. Raw response:\n";
    print_r($data);
} else {
    foreach ($documents as $doc) {
        $id = basename($doc['name']);
        echo "=== ID: $id ===\n";
        echo json_encode($doc, JSON_PRETTY_PRINT) . "\n\n";
    }
}
