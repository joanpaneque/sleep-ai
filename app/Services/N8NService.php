<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Channel;
use App\Models\Video;

class N8NService {


    private static $serverURL = "https://n8n.sleepai.online/webhook-test/process-video";
    

    public static $languageVoices = [
        'en' => [
            "language" => "en-US",
            "voice" => "en-US-Chirp3-HD-Charon",
            "language_name" => "English"
        ],
        'es' => [
            "language" => "es-ES",
            "voice" => "es-ES-Chirp3-HD-Charon",
            "language_name" => "Español de España"
        ],
        'de' => [
            "language" => "de-DE",
            "voice" => "de-DE-Chirp3-HD-Charon",
            "language_name" => "Deutsch (Germany)"
        ],
        'fr' => [
            "language" => "fr-FR",
            "voice" => "fr-FR-Chirp3-HD-Charon",
            "language_name" => "Français (France)"
        ],
        'pt' => [
            "language" => "pt-BR",
            "voice" => "pt-BR-Chirp3-HD-Charon",
            "language_name" => "Português (Brazil)"
        ],
        'en-UHD' => [
            "language" => "en-UHD",
            "voice" => "en-UHD",
            "language_name" => "English (Ultra HD)"
        ],
    ];

    public static function createVideo($data)
    {
        $language = $data['language'] ?? 'en';

        $channel = Channel::find($data['channel_id']);
        if (!$channel) {
            return response()->json(['error' => 'Channel not found'], 404);
        }

        // create the video
        $video = $channel->videos()->create([
            'title' => $data['title'],
            'description' => $data['description'],
            'stories_amount' => $data['stories_amount'],
            'characters_amount' => $data['characters_amount'],
            'stories_amount' => $data['stories_amount'],
            'language' => json_encode(self::$languageVoices[$language])
        ]);

        // get video from database with channel
        $video = Video::find($video->id);

        $response = self::callWebhook($video);
        return ['video_id' => $video->id, 'server_response' => $response];
    }


    public static function callWebhook($video)
    {
        if (self::isVideoRendering()) {
            return false;
        }

        $video->status = 'generating_script';
        $video->status_progress = null;
        $video->save();

        // convert to array
        $videoArray = $video->toArray();
        $channel = Channel::find($videoArray['channel_id']);
        $videoArray['channel'] = $channel->toArray();

        $response = Http::post(self::$serverURL, $videoArray);
        return $response->json();
    }


    private static function isVideoRendering()
    {
        $videos = Video::whereIn('status', ['generating_script', 'generating_content', 'rendering'])->get();
        return $videos->count() > 7;
    }

    // get the video that has been waiting the longest in pending status (the oldest)
    private static function getOldestPendingVideo()
    {
        return Video::where('status', 'pending')->orderBy('created_at', 'asc')->first();
    }


    public static function processNextVideo()
    {
        $video = self::getOldestPendingVideo();
        if ($video) {
            self::callWebhook($video);
        }
    }
}