<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\N8NService;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // make a post request to the n8n server
        $response = N8NService::createVideo([
            'video_title' => '3 hours of school secrets',
            'channel_id' => 6,
            'video_id' => 6,
            'stories_amount' => 5,

        ]);
        dd($response);
    }
}
