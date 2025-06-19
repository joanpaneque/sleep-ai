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
            'title' => '3 hours of sleep paradoxes to fall asleep to',
            'description' => '3 hours of sleep paradoxes to fall asleep to',
            'channel_id' => 2,
            'stories_amount' => 2,
            'characters_amount' => 200,
            'language' => 'es'
        ]);
        dd($response);
    }
}
