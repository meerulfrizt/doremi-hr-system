<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $app->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Http;

$projectId = "doremi-admin";
$leaveId = "lwDS7JCoFmpviRDXMbsI";
$uid = "IJkWa4Ms6FTag6huoLLJKXZ2Nal1";

// Resolve Firestore Access Token using the controller helper
$controller = new class extends \App\Http\Controllers\Controller {
    public function getToken() {
        return $this->getFirestoreAccessToken();
    }
};

try {
    $accessToken = $controller->getToken();
    
    // 1. Reset leave status back to "Pending"
    echo "Resetting leave status for ID {$leaveId} to 'Pending'...\n";
    $leaveUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/leaves/{$leaveId}?updateMask.fieldPaths=status";
    $res1 = Http::withoutVerifying()
        ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
        ->patch($leaveUrl, [
            'fields' => ['status' => ['stringValue' => 'Pending']]
        ]);
    
    if ($res1->successful()) {
        echo "Successfully reset leave status to Pending!\n";
    } else {
        echo "Failed to reset leave status: " . $res1->body() . "\n";
    }
    
    // 2. Restore mc_balance back to 11
    echo "Restoring mc_balance for user {$uid} to 11...\n";
    $userUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$uid}?updateMask.fieldPaths=mc_balance";
    $res2 = Http::withoutVerifying()
        ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
        ->patch($userUrl, [
            'fields' => ['mc_balance' => ['integerValue' => 11]]
        ]);
        
    if ($res2->successful()) {
        echo "Successfully restored mc_balance to 11!\n";
    } else {
        echo "Failed to restore mc_balance: " . $res2->body() . "\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
