<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Video;
use App\Models\Channel;

class VideoSeeder extends Seeder
{
    public function run(): void
    {
        $channels = Channel::all();

        foreach ($channels as $channel) {
            switch ($channel->name) {
                case 'MrBeast':
                    $this->createMrBeastVideos($channel->id);
                    break;
                case 'PewDiePie':
                    $this->createPewDiePieVideos($channel->id);
                    break;
                case 'Markiplier':
                    $this->createMarkiplierVideos($channel->id);
                    break;
                case 'Dude Perfect':
                    $this->createDudePerfectVideos($channel->id);
                    break;
                case 'Veritasium':
                    $this->createVeritasiumVideos($channel->id);
                    break;
            }
        }
    }

    private function createMrBeastVideos($channelId)
    {
        $videos = [
            [
                'title' => 'I Spent 50 Hours In Bed',
                'description' => 'Testing different sleep techniques and documenting the effects on energy levels and productivity.',
                'duration' => '18:24',
                'status' => 'completed',
                'completed_at' => now(),
                'thumbnail' => 'https://example.com/thumbnails/mrbeast1.jpg',
            ],
            [
                'title' => '$10,000 Sleep Challenge',
                'description' => 'Last person to fall asleep wins $10,000. Testing different methods to stay awake.',
                'duration' => '22:15',
                'status' => 'completed',
                'completed_at' => now(),
                'thumbnail' => 'https://example.com/thumbnails/mrbeast2.jpg',
            ],
        ];

        $this->createVideos($videos, $channelId);
    }

    private function createPewDiePieVideos($channelId)
    {
        $videos = [
            [
                'title' => 'I Tried ASMR for Sleep',
                'description' => 'Testing popular ASMR videos and their effectiveness for sleep.',
                'duration' => '15:45',
                'status' => 'completed',
                'completed_at' => now(),
                'thumbnail' => 'https://example.com/thumbnails/pewdiepie1.jpg',
            ],
            [
                'title' => 'Sleep Apps Review',
                'description' => 'Reviewing and testing the most popular sleep tracking apps.',
                'duration' => '20:30',
                'status' => 'generating_content',
                'status_progress' => 65,
                'thumbnail' => 'https://example.com/thumbnails/pewdiepie2.jpg',
            ],
        ];

        $this->createVideos($videos, $channelId);
    }

    private function createMarkiplierVideos($channelId)
    {
        $videos = [
            [
                'title' => 'Sleep Horror Games',
                'description' => 'Playing horror games that focus on sleep paralysis and nightmares.',
                'duration' => '25:10',
                'status' => 'completed',
                'completed_at' => now(),
                'thumbnail' => 'https://example.com/thumbnails/markiplier1.jpg',
            ],
            [
                'title' => 'Sleep Deprivation Challenge',
                'description' => 'Attempting to play games while sleep deprived (safely monitored).',
                'duration' => '19:45',
                'status' => 'rendering',
                'status_progress' => 85,
                'thumbnail' => 'https://example.com/thumbnails/markiplier2.jpg',
            ],
        ];

        $this->createVideos($videos, $channelId);
    }

    private function createDudePerfectVideos($channelId)
    {
        $videos = [
            [
                'title' => 'Sleeping Trick Shots',
                'description' => 'Incredible trick shots performed by people who just woke up.',
                'duration' => '21:30',
                'status' => 'completed',
                'completed_at' => now(),
                'thumbnail' => 'https://example.com/thumbnails/dudeperfect1.jpg',
            ],
            [
                'title' => 'Sleep Battle | OT 23',
                'description' => 'The guys compete in various sleep-related challenges.',
                'duration' => '23:15',
                'status' => 'generating_script',
                'status_progress' => 30,
                'thumbnail' => 'https://example.com/thumbnails/dudeperfect2.jpg',
            ],
        ];

        $this->createVideos($videos, $channelId);
    }

    private function createVeritasiumVideos($channelId)
    {
        $videos = [
            [
                'title' => 'The Science of Sleep',
                'description' => 'Exploring the fascinating science behind why we sleep and what happens in our brains.',
                'duration' => '27:45',
                'status' => 'completed',
                'completed_at' => now(),
                'thumbnail' => 'https://example.com/thumbnails/veritasium1.jpg',
            ],
            [
                'title' => 'Why Do We Dream?',
                'description' => 'The latest scientific research on dreams and their purpose.',
                'duration' => '24:30',
                'status' => 'failed',
                'status_progress' => 0,
                'thumbnail' => 'https://example.com/thumbnails/veritasium2.jpg',
            ],
        ];

        $this->createVideos($videos, $channelId);
    }

    private function createVideos($videos, $channelId)
    {
        foreach ($videos as $video) {
            Video::create(array_merge($video, ['channel_id' => $channelId]));
        }
    }
} 