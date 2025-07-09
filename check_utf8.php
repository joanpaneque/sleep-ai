<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\YoutubeVideoStat;

echo "Verificando datos UTF-8...\n";

$videos = YoutubeVideoStat::all();
$problematic = 0;

foreach ($videos as $video) {
    $hasIssue = false;
    
    if ($video->title && !mb_check_encoding($video->title, 'UTF-8')) {
        echo "PROBLEM Video: {$video->youtube_video_id} - Title issue\n";
        echo "Title: " . bin2hex(substr($video->title, 0, 50)) . "\n";
        $hasIssue = true;
    }
    
    if ($video->description && !mb_check_encoding($video->description, 'UTF-8')) {
        echo "PROBLEM Video: {$video->youtube_video_id} - Description issue\n";
        echo "Description start: " . bin2hex(substr($video->description, 0, 50)) . "\n";
        $hasIssue = true;
    }
    
    if ($hasIssue) {
        $problematic++;
    }
}

echo "Total problematic videos: {$problematic}\n";

// Test JSON encoding
echo "\nTesting JSON encoding...\n";
$testVideo = $videos->first();
if ($testVideo) {
    $testData = [
        'id' => $testVideo->youtube_video_id,
        'title' => $testVideo->title,
        'description' => substr($testVideo->description, 0, 200)
    ];
    
    $json = json_encode($testData);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "JSON Error: " . json_last_error_msg() . "\n";
    } else {
        echo "JSON encoding successful\n";
    }
} 