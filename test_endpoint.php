<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ChannelController;
use App\Models\Channel;
use Illuminate\Http\Request;

echo "Testing getAllVideos endpoint...\n";

// Create a mock request
$request = new Request([
    'limit' => 100,
    'order_by' => 'view_count',
    'order_direction' => 'desc'
]);

// Create controller instance
$controller = new ChannelController();

try {
    // Call the getAllVideos method directly
    $response = $controller->getAllVideos($request);
    
    // Get the response content
    $content = $response->getContent();
    
    echo "Response status: " . $response->getStatusCode() . "\n";
    
    // Check if it's valid JSON
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "JSON Error: " . json_last_error_msg() . "\n";
        echo "Raw content: " . $content . "\n";
    } else {
        echo "JSON is valid\n";
        echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
        if (isset($data['data']['total_count'])) {
            echo "Total videos: " . $data['data']['total_count'] . "\n";
        }
        if (isset($data['message'])) {
            echo "Message: " . $data['message'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} 