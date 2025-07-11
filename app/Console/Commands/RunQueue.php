<?php

namespace App\Console\Commands;

use App\Services\N8NService;
use Illuminate\Console\Command;

class RunQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:run';

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
        // iterate 10 times
        for ($i = 0; $i < 10; $i++) {
            N8NService::processNextVideo();
        }
    }
}
