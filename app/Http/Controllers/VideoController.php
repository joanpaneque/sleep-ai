<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class VideoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Videos/Index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Videos/Create', [
            'channel' => Channel::find($id)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // validate with spanish messages
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'channel_id' => 'required|exists:channels,id'
        ], [
            'title.required' => 'El título es requerido',
            'title.string' => 'El título debe ser una cadena de texto',
            'title.max' => 'El título no puede tener más de 255 caracteres',
            'description.required' => 'La descripción es requerida',
            'description.string' => 'La descripción debe ser una cadena de texto',
            'description.max' => 'La descripción no puede tener más de 255 caracteres',
            'channel_id.required' => 'El canal es requerido',
            'channel_id.exists' => 'El canal no existe',
        ]);

        $videoTitle = $request->title;
        $videoDescription = $request->description;
        $videoChannelId = $request->channel_id;

        $video = Video::create([
            'title' => $videoTitle,
            'description' => $videoDescription,
            'channel_id' => $videoChannelId
        ]);

        return redirect()->route('videos.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Inertia::render('Videos/Show', [
            'video' => Video::findOrFail($id)
        ]);
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

        return redirect()->route('videos.index');
    }
}
