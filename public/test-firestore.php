<?php
// Quick Firestore diagnostic — run via http://localhost:8000/test-firestore.php
// DELETE this file after debugging!

$projectId = 'doremi-admin2';
$base      = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents";

$collections = ['users', 'leaves', 'overtime', 'flexi', 'attendances', 'assigned_tasks'];

echo "<h2>Firestore Diagnostic — project: <b>{$projectId}</b></h2>";
echo "<table border='1' cellpadding='8' style='border-collapse:collapse;font-family:monospace'>";
echo "<tr style='background:#222;color:#fff'><th>Collection</th><th>HTTP Status</th><th>Result</th></tr>";

foreach ($collections as $col) {
    $url  = "{$base}/{$col}?pageSize=1";
    $opts = ['http' => ['ignore_errors' => true]];
    $ctx  = stream_context_create($opts);
    $raw  = @file_get_contents($url, false, $ctx);
    $code = isset($http_response_header[0]) ? $http_response_header[0] : 'No response';
    $data = json_decode($raw, true);

    $status = strpos($code, '200') !== false ? '✅ 200 OK' : '❌ ' . $code;
    $bgcolor = strpos($code, '200') !== false ? '#d4edda' : '#f8d7da';

    if (strpos($code, '200') !== false) {
        $count = isset($data['documents']) ? count($data['documents']) : 0;
        $detail = "Found {$count} doc(s)";
        if ($count === 0) {
            $detail = "⚠️ Collection EMPTY or no documents";
            $bgcolor = '#fff3cd';
        }
    } else {
        $detail = isset($data['error']['message']) ? $data['error']['message'] : substr($raw, 0, 150);
    }

    echo "<tr style='background:{$bgcolor}'>";
    echo "<td><b>{$col}</b></td>";
    echo "<td>{$status}</td>";
    echo "<td>{$detail}</td>";
    echo "</tr>";
}

echo "</table>";
echo "<p style='color:red;font-size:11px'>⚠️ Delete /public/test-firestore.php after debugging!</p>";


