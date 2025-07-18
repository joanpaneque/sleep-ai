<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Video;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\N8NService;
use Illuminate\Support\Facades\Log;

class VideoController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Channel $channel, Video $video)
    {
        return Inertia::render('Videos/Show', [
            'channel' => $channel,
            'video' => $video
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Channel $channel)
    {
        return Inertia::render('Videos/Create', [
            'channel' => $channel,
            'languages' => \App\Services\N8NService::$languageVoices
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Channel $channel)
    {
        // validate with spanish messages
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'channel_id' => 'required|exists:channels,id',
            'stories_amount' => 'required|integer|min:1|max:100',
            'characters_amount' => 'required|integer|min:1|max:1000000',
            'language' => 'required|string'
        ], [
            'title.required' => 'El título es requerido',
            'title.string' => 'El título debe ser una cadena de texto',
            'title.max' => 'El título no puede tener más de 255 caracteres',
            'description.required' => 'La descripción es requerida',
            'description.string' => 'La descripción debe ser una cadena de texto',
            'description.max' => 'La descripción no puede tener más de 255 caracteres',
            'channel_id.required' => 'El canal es requerido',
            'channel_id.exists' => 'El canal no existe',
            'stories_amount.required' => 'La cantidad de historias es requerida',
            'stories_amount.integer' => 'La cantidad de historias debe ser un número entero',
            'stories_amount.min' => 'La cantidad de historias debe ser al menos 1',
            'stories_amount.max' => 'La cantidad de historias no puede ser mayor a 100',
            'characters_amount.required' => 'La cantidad de caracteres es requerida',
            'characters_amount.integer' => 'La cantidad de caracteres debe ser un número entero',
            'characters_amount.min' => 'La cantidad de caracteres debe ser al menos 1',
            'characters_amount.max' => 'La cantidad de caracteres no puede ser mayor a 10000',
            'language.required' => 'El idioma es requerido',
            'language.string' => 'El idioma debe ser una cadena de texto',
            'language.in' => 'El idioma seleccionado no es válido',
        ]);

        $response = N8NService::createVideo([
            'title' => $request->title,
            'description' => $request->description,
            'channel_id' => $request->channel_id,
            'stories_amount' => $request->stories_amount,
            'characters_amount' => $request->characters_amount,
            'language' => $request->language
        ]);

        return redirect()->route('channels.show', $channel->id);
    }
    public function queueVideo(Video $video)
    {
        Log::info('Queueing video', ['video' => $video]);
        if (!$video->is_deleted) {
            Log::info('Video is not deleted, redirecting to channel show');
            return redirect()->route('channels.show', $video->channel_id);
        }

        $video->status = 'pending';
        $video->is_deleted = false;
        $video->save();

        Log::info('Status updated to pending');


        Log::info('Calling webhook');
        N8NService::callWebhook($video->load('channel'));

        Log::info('Redirecting to channel show');
        return redirect()->route('channels.show', $video->channel_id);
    }

    public function updateStatus(Request $request, Video $video)
    {
        $video->status = $request->status;
        $video->status_progress = $request->status_progress;
        $video->save();

        if ($video->status_progress != null) {
            $video->thumbnail = "https://sleepai.online/storage/channels/" . $video->channel_id . "/" . $video->id . "/thumbnail.png";
            $video->save();
        }

        if ($video->status == 'completed') {
            // save the video url
            $video->url = "https://sleepai.online/storage/channels/" . $video->channel_id . "/" . $video->id . "/render/render.mp4";

            // calculate and save the directory size in bytes
            $video->size_in_bytes = $video->calculateDirectorySize();

            $video->save();

            Log::info('Video completed and size calculated', [
                'video_id' => $video->id,
                'size_in_bytes' => $video->size_in_bytes
            ]);

            N8NService::processNextVideo();
        }
    }

    public function getTopics(Video $video)
    {
        // get all the topics from the channel
        $topics = $video->channel->topics;
        // return the json but stringified
        return response()->json(["result" => json_encode($topics)]);
    }

    public function uploadTopics(Request $request, Video $video)
    {

        $topics = $request->topics;

        try {
            foreach ($topics as $topic) {
                $video->topics()->create([  
                    'title' => $topic['titulo'],
                    'description' => $topic['descripcion']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error uploading topics', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error uploading topics'], 500);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $video = Video::findOrFail($id);
        $video->delete();

        return redirect()->route('channels.index');
    }

    public function softDelete(Video $video)
    {
        $video->deleteEverything();

        return redirect()->route('channels.show', $video->channel_id);
    }
}
