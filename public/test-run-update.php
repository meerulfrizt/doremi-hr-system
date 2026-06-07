<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $app->handle(Illuminate\Http\Request::capture());

use App\Http\Controllers\LeaveController;
use Illuminate\Http\Request;

$controller = new LeaveController();
$request = new Request();
$request->replace([
    'status' => 'Approved',
    'uid' => 'IJkWa4Ms6FTag6huoLLJKXZ2Nal1',
    'total_days' => 2,
    'type' => 'Medical'
]);

echo "Executing LeaveController::updateStatus for leave ID: lwDS7JCoFmpviRDXMbsI...\n";
try {
    $res = $controller->updateStatus($request, 'lwDS7JCoFmpviRDXMbsI');
    echo "Response status code: " . $res->status() . "\n";
    echo "Response body: " . $res->getContent() . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
