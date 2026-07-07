<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $app->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Http;

$projectId = "doremi-admin";
$uid = "IJkWa4Ms6FTag6huoLLJKXZ2Nal1";
$url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$uid}";
$response = Http::withoutVerifying()->get($url);
$data = $response->json();

echo "USER FIELDS FOR NEYMAR JR:\n";
if (isset($data['fields'])) {
    foreach ($data['fields'] as $key => $val) {
        if (str_contains($key, 'balance')) {
            echo "- $key: " . json_encode($val) . "\n";
        }
    }
} else {
    print_r($data);
}
