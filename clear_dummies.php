<?php
require 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$projectId = 'doremi-admin';

echo "Fetching users...\n";
$usersResponse = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users");
if ($usersResponse->successful() && isset($usersResponse->json()['documents'])) {
    foreach ($usersResponse->json()['documents'] as $doc) {
        $name = basename($doc['name']);
        $email = $doc['fields']['email']['stringValue'] ?? '';
        if (str_ends_with($email, '@doremi.com')) {
            echo "Deleting user: {$email} ({$name})\n";
            Http::withoutVerifying()->delete("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$name}");
        }
    }
}

echo "Fetching overtime...\n";
$otResponse = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/overtime");
if ($otResponse->successful() && isset($otResponse->json()['documents'])) {
    foreach ($otResponse->json()['documents'] as $doc) {
        $name = basename($doc['name']);
        echo "Deleting OT: {$name}\n";
        Http::withoutVerifying()->delete("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/overtime/{$name}");
    }
}

echo "Fetching overtime_requests...\n";
$otReqResponse = Http::withoutVerifying()->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/overtime_requests");
if ($otReqResponse->successful() && isset($otReqResponse->json()['documents'])) {
    foreach ($otReqResponse->json()['documents'] as $doc) {
        $name = basename($doc['name']);
        echo "Deleting OT Request: {$name}\n";
        Http::withoutVerifying()->delete("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/overtime_requests/{$name}");
    }
}

echo "Done clearing.\n";
