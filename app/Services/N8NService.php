<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Channel;

class N8NService {


    private static $serverURL = "https://n8n.sleepai.online/webhook-test/process-video";

    private static $languageVoices = [
        'en' => [
            "language" => "en-US",
            "voice" => "en-US-Chirp3-HD-Charon",
            "language_name" => "English"
        ],
        'es' => [
            "language" => "es-ES",
            "voice" => "es-ES-Chirp3-HD-Charon",
            "language_name" => "Español de España"
        ]
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
        ]);

        $processedData = [
            'video_title' => $data['title'],
            'channel_id' => $data['channel_id'],
            'video_id' => $video->id,
            'stories_amount' => $data['stories_amount'],
            'characters_amount' => $data['characters_amount'],
            'language' => self::$languageVoices[$language]
        ];

        $response = self::callWebhook($processedData);
        return ['video_id' => $video->id, 'server_response' => $response];
    }


    private static function callWebhook($data)
    {
        $response = Http::post(self::$serverURL, $data);
        return $response->json();
    }
}