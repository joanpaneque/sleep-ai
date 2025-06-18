<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class N8NService {


    private static $serverURL = "https://n8n.sleepai.online/webhook-test/process-video";

    // make a post request to the serverURL with the data using Http::
    public static function createVideo($data)
    {
        $response = Http::post(self::$serverURL, $data);
        return $response->json();
    }
}