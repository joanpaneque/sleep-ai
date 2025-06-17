<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Channel;

class ChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $channels = [
            [
                'name' => 'MrBeast',
                'description' => 'Jimmy Donaldson, better known as MrBeast, is known for his expensive stunts, challenges, and philanthropic activities. One of the most subscribed and viewed YouTube creators.',
            ],
            [
                'name' => 'PewDiePie',
                'description' => 'Felix Kjellberg is one of the most iconic YouTubers, known for his gaming content, vlogs, and commentary videos. He was the most subscribed individual creator for many years.',
            ],
            [
                'name' => 'Markiplier',
                'description' => 'Mark Fischbach is famous for his gaming content, particularly horror games, and his highly produced sketch comedy videos and series.',
            ],
            [
                'name' => 'Dude Perfect',
                'description' => 'A group of five friends known for their incredible trick shots, stunts, and family-friendly entertainment content. They hold several Guinness World Records.',
            ],
            [
                'name' => 'Veritasium',
                'description' => 'Derek Muller creates educational content focusing on science, engineering, mathematics, and technology, explaining complex concepts in an engaging way.',
            ],
        ];

        foreach ($channels as $channel) {
            Channel::create($channel);
        }
    }
} 