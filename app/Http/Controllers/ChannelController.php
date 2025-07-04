<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Channel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ChannelController extends Controller
{
            /**
     * Calculate storage usage statistics
     */
    private function getStorageStats()
    {
        $publicPath = storage_path('app/public');
        $appDataSize = 0;

        // Calculate total size of storage/app/public directory (our app data)
        if (is_dir($publicPath)) {
            $appDataSize = $this->getDirectorySize($publicPath);
        }

        // Get disk space information (entire disk)
        $freeSpace = disk_free_space($publicPath);
        $totalDiskSpace = disk_total_space($publicPath);
        $usedDiskSpace = $totalDiskSpace - $freeSpace;

        // Calculate system weight (everything except our app data)
        $systemWeight = $usedDiskSpace - $appDataSize;

        // Calculate available space for the app (total disk - system weight)
        $availableSpaceForApp = $totalDiskSpace - $systemWeight;

        // Calculate percentage used of available space for app
        $usedPercentage = $availableSpaceForApp > 0 ? round(($appDataSize / $availableSpaceForApp) * 100, 1) : 0;

        return [
            'used_space_mb' => round($appDataSize / (1024 * 1024), 2),
            'free_space_mb' => round($freeSpace / (1024 * 1024), 2),
            'total_space_mb' => round($availableSpaceForApp / (1024 * 1024), 2),
            'used_percentage' => $usedPercentage,
            'used_space_formatted' => $this->formatBytes($appDataSize),
            'free_space_formatted' => $this->formatBytes($freeSpace),
            'total_space_formatted' => $this->formatBytes($availableSpaceForApp),
            'app_data_size' => $this->formatBytes($appDataSize),
            'system_weight' => $this->formatBytes($systemWeight),
            'disk_total_space' => $this->formatBytes($totalDiskSpace)
        ];
    }

    /**
     * Calculate directory size recursively
     */
    private function getDirectorySize($directory)
    {
        $size = 0;

        if (is_dir($directory)) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        }

        return $size;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $channels = Channel::all();
        $storageStats = $this->getStorageStats();

        return Inertia::render('Channels/Index', [
            'channels' => $channels,
            'storage_stats' => $storageStats
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Channels/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Debug logging
        Log::info('Channel store method called');
        Log::info('Request data:', $request->all());
        Log::info('Files:', $request->allFiles());

        // validate with spanish messages
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'intro' => 'nullable|file|mimes:mp4,mov,avi,wmv,flv,mpeg,mpg,m4v,webm,mkv|max:512000',
            'remove_intro' => 'nullable|boolean',
            'background_video' => 'nullable|file|mimes:mp4,mov,avi,wmv,flv,mpeg,mpg,m4v,webm,mkv|max:512000',
            'remove_background_video' => 'nullable|boolean',
            'frame_image' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp|max:51200',
            'remove_frame_image' => 'nullable|boolean',
            'image_style_prompt' => 'nullable|string|max:1000',
            'thumbnail' => 'nullable|string',
            'thumbnail_image_prompt' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'El nombre es requerido',
            'name.string' => 'El nombre debe ser una cadena de texto',
            'name.max' => 'El nombre no puede tener más de 255 caracteres',
            'description.required' => 'La descripción es requerida',
            'description.string' => 'La descripción debe ser una cadena de texto',
            'description.max' => 'La descripción no puede tener más de 255 caracteres',
            'intro.file' => 'La intro debe ser un archivo',
            'intro.mimes' => 'La intro debe ser un archivo de video',
            'intro.max' => 'La intro no puede tener más de 500MB',
            'background_video.file' => 'El video de fondo debe ser un archivo',
            'background_video.mimes' => 'El video de fondo debe ser un archivo de video',
            'background_video.max' => 'El video de fondo no puede tener más de 500MB',
            'frame_image.file' => 'La imagen del marco debe ser un archivo',
            'frame_image.mimes' => 'La imagen del marco debe ser un archivo de imagen',
            'frame_image.max' => 'La imagen del marco no puede tener más de 50MB',
            'image_style_prompt.string' => 'El prompt de estilo debe ser texto',
            'image_style_prompt.max' => 'El prompt de estilo no puede tener más de 1000 caracteres',
            'thumbnail_image_prompt.string' => 'El prompt de imagen del thumbnail debe ser texto',
            'thumbnail_image_prompt.max' => 'El prompt de imagen del thumbnail no puede tener más de 1000 caracteres',
        ]);

        Log::info('Validation passed');

        $channelName = $request->name;
        $channelDescription = $request->description;
        $imageStylePrompt = $request->image_style_prompt;
        $thumbnailImagePrompt = $request->thumbnail_image_prompt;

        // Manejo más robusto del archivo intro
        $introName = null;

        // Si no se está eliminando la intro y hay archivo
        if (!$request->remove_intro && $request->hasFile('intro')) {
            Log::info('Intro file detected');
            $intro = $request->file('intro');
            Log::info('Intro file details:', [
                'original_name' => $intro->getClientOriginalName(),
                'size' => $intro->getSize(),
                'mime_type' => $intro->getMimeType(),
                'is_valid' => $intro->isValid()
            ]);

            if ($intro->isValid()) {
                $introName = time() . '_' . uniqid() . '.' . $intro->getClientOriginalExtension();
                Log::info('Generated intro filename:', ['filename' => $introName]);

                // Usar Storage facade para guardar en storage/app/public/intros
                try {
                    $path = Storage::disk('public')->putFileAs('intros', $intro, $introName);
                    Log::info('Intro storage result:', ['path' => $path]);

                    // Verificar que el archivo se guardó correctamente
                    if (!$path) {
                        Log::error('Failed to save intro file');
                        return back()->withErrors(['intro' => 'Error al guardar el archivo de intro']);
                    }

                    // Verificar que el archivo existe
                    $exists = Storage::disk('public')->exists('intros/' . $introName);
                    Log::info('Intro file exists check:', ['exists' => $exists]);

                } catch (\Exception $e) {
                    Log::error('Exception saving intro file:', ['error' => $e->getMessage()]);
                    return back()->withErrors(['intro' => 'Error al guardar el archivo: ' . $e->getMessage()]);
                }
            } else {
                Log::error('Intro file is not valid');
                return back()->withErrors(['intro' => 'El archivo no es válido']);
            }
        } else if ($request->remove_intro) {
            Log::info('Intro removal requested');
            $introName = null;
        } else {
            Log::info('No intro file detected in request');
        }

        // Manejo del archivo background_video
        $backgroundVideoName = null;

        if (!$request->remove_background_video && $request->hasFile('background_video')) {
            Log::info('Background video file detected');
            $backgroundVideo = $request->file('background_video');
            Log::info('Background video file details:', [
                'original_name' => $backgroundVideo->getClientOriginalName(),
                'size' => $backgroundVideo->getSize(),
                'mime_type' => $backgroundVideo->getMimeType(),
                'is_valid' => $backgroundVideo->isValid()
            ]);

            if ($backgroundVideo->isValid()) {
                $backgroundVideoName = time() . '_' . uniqid() . '.' . $backgroundVideo->getClientOriginalExtension();
                Log::info('Generated background video filename:', ['filename' => $backgroundVideoName]);

                try {
                    $path = Storage::disk('public')->putFileAs('backgrounds', $backgroundVideo, $backgroundVideoName);
                    Log::info('Background video storage result:', ['path' => $path]);

                    if (!$path) {
                        Log::error('Failed to save background video file');
                        return back()->withErrors(['background_video' => 'Error al guardar el video de fondo']);
                    }

                    $exists = Storage::disk('public')->exists('backgrounds/' . $backgroundVideoName);
                    Log::info('Background video file exists check:', ['exists' => $exists]);

                } catch (\Exception $e) {
                    Log::error('Exception saving background video file:', ['error' => $e->getMessage()]);
                    return back()->withErrors(['background_video' => 'Error al guardar el video de fondo: ' . $e->getMessage()]);
                }
            } else {
                Log::error('Background video file is not valid');
                return back()->withErrors(['background_video' => 'El video de fondo no es válido']);
            }
        } else if ($request->remove_background_video) {
            Log::info('Background video removal requested');
            $backgroundVideoName = null;
        }

        // Manejo del archivo frame_image
        $frameImageName = null;

        if (!$request->remove_frame_image && $request->hasFile('frame_image')) {
            Log::info('Frame image file detected');
            $frameImage = $request->file('frame_image');
            Log::info('Frame image file details:', [
                'original_name' => $frameImage->getClientOriginalName(),
                'size' => $frameImage->getSize(),
                'mime_type' => $frameImage->getMimeType(),
                'is_valid' => $frameImage->isValid()
            ]);

            if ($frameImage->isValid()) {
                $frameImageName = time() . '_' . uniqid() . '.' . $frameImage->getClientOriginalExtension();
                Log::info('Generated frame image filename:', ['filename' => $frameImageName]);

                try {
                    $path = Storage::disk('public')->putFileAs('frames', $frameImage, $frameImageName);
                    Log::info('Frame image storage result:', ['path' => $path]);

                    if (!$path) {
                        Log::error('Failed to save frame image file');
                        return back()->withErrors(['frame_image' => 'Error al guardar la imagen del marco']);
                    }

                    $exists = Storage::disk('public')->exists('frames/' . $frameImageName);
                    Log::info('Frame image file exists check:', ['exists' => $exists]);

                } catch (\Exception $e) {
                    Log::error('Exception saving frame image file:', ['error' => $e->getMessage()]);
                    return back()->withErrors(['frame_image' => 'Error al guardar la imagen del marco: ' . $e->getMessage()]);
                }
            } else {
                Log::error('Frame image file is not valid');
                return back()->withErrors(['frame_image' => 'La imagen del marco no es válida']);
            }
        } else if ($request->remove_frame_image) {
            Log::info('Frame image removal requested');
            $frameImageName = null;
        }

        // Manejo del thumbnail HTML
        $thumbnailTemplateName = null;

        Log::info('Thumbnail field check:', [
            'has_thumbnail' => $request->has('thumbnail'),
            'filled_thumbnail' => $request->filled('thumbnail'),
            'thumbnail_value' => $request->thumbnail,
            'thumbnail_length' => $request->thumbnail ? strlen($request->thumbnail) : 0
        ]);

        if ($request->filled('thumbnail')) {
            Log::info('Thumbnail HTML content detected');
            $thumbnailContent = $request->thumbnail;

            // Generar nombre único para el archivo
            $thumbnailTemplateName = time() . '_' . uniqid() . '.html';
            Log::info('Generated thumbnail template filename:', ['filename' => $thumbnailTemplateName]);

            try {
                // Crear el directorio si no existe
                if (!Storage::disk('public')->exists('thumbnail_templates')) {
                    Storage::disk('public')->makeDirectory('thumbnail_templates');
                }

                // Guardar el contenido HTML en el archivo
                $saved = Storage::disk('public')->put('thumbnail_templates/' . $thumbnailTemplateName, $thumbnailContent);
                Log::info('Thumbnail template storage result:', ['saved' => $saved]);

                if (!$saved) {
                    Log::error('Failed to save thumbnail template file');
                    return back()->withErrors(['thumbnail' => 'Error al guardar el template de thumbnail']);
                }

                // Verificar que el archivo existe
                $exists = Storage::disk('public')->exists('thumbnail_templates/' . $thumbnailTemplateName);
                Log::info('Thumbnail template file exists check:', ['exists' => $exists]);

            } catch (\Exception $e) {
                Log::error('Exception saving thumbnail template file:', ['error' => $e->getMessage()]);
                return back()->withErrors(['thumbnail' => 'Error al guardar el template de thumbnail: ' . $e->getMessage()]);
            }
        }

        Log::info('Creating channel with data:', [
            'name' => $channelName,
            'description' => $channelDescription,
            'intro' => $introName,
            'background_video' => $backgroundVideoName,
            'frame_image' => $frameImageName,
            'image_style_prompt' => $imageStylePrompt,
            'thumbnail_template' => $thumbnailTemplateName,
            'thumbnail_image_prompt' => $thumbnailImagePrompt
        ]);

        $channel = Channel::create([
            'name' => $channelName,
            'description' => $channelDescription,
            'intro' => $introName,
            'background_video' => $backgroundVideoName,
            'frame_image' => $frameImageName,
            'image_style_prompt' => $imageStylePrompt,
            'thumbnail_template' => $thumbnailTemplateName,
            'thumbnail_image_prompt' => $thumbnailImagePrompt
        ]);

        Log::info('Channel created successfully:', [
            'id' => $channel->id,
            'thumbnail_template_saved' => $channel->thumbnail_template,
            'thumbnail_template_variable' => $thumbnailTemplateName
        ]);

        return redirect()->route('channels.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Inertia::render('Channels/Show', [
            'channel' => Channel::with(['videos' => function($query) {
                $query->orderBy('created_at', 'desc');
            }])->findOrFail($id)
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $channel = Channel::findOrFail($id);

        // Leer el contenido del thumbnail template si existe
        $thumbnailContent = null;
        if ($channel->thumbnail_template && Storage::disk('public')->exists('thumbnail_templates/' . $channel->thumbnail_template)) {
            try {
                $thumbnailContent = Storage::disk('public')->get('thumbnail_templates/' . $channel->thumbnail_template);
                Log::info('Thumbnail template content loaded for edit:', ['filename' => $channel->thumbnail_template]);
            } catch (\Exception $e) {
                Log::error('Error reading thumbnail template file:', ['error' => $e->getMessage(), 'filename' => $channel->thumbnail_template]);
            }
        }

        return Inertia::render('Channels/Edit', [
            'channel' => $channel,
            'thumbnail' => $thumbnailContent
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $channel = Channel::findOrFail($id);

        // Debug logging
        Log::info('Channel update method called for ID: ' . $id);
        Log::info('Request data:', $request->all());
        Log::info('Files:', $request->allFiles());

        // validate with spanish messages
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'intro' => 'nullable|file|mimes:mp4,mov,avi,wmv,flv,mpeg,mpg,m4v,webm,mkv|max:512000',
            'remove_intro' => 'nullable|boolean',
            'background_video' => 'nullable|file|mimes:mp4,mov,avi,wmv,flv,mpeg,mpg,m4v,webm,mkv|max:512000',
            'remove_background_video' => 'nullable|boolean',
            'frame_image' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp|max:51200',
            'remove_frame_image' => 'nullable|boolean',
            'image_style_prompt' => 'nullable|string|max:1000',
            'thumbnail' => 'nullable|string',
            'thumbnail_image_prompt' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'El nombre es requerido',
            'name.string' => 'El nombre debe ser una cadena de texto',
            'name.max' => 'El nombre no puede tener más de 255 caracteres',
            'description.required' => 'La descripción es requerida',
            'description.string' => 'La descripción debe ser una cadena de texto',
            'description.max' => 'La descripción no puede tener más de 255 caracteres',
            'intro.file' => 'La intro debe ser un archivo',
            'intro.mimes' => 'La intro debe ser un archivo de video',
            'intro.max' => 'La intro no puede tener más de 500MB',
            'background_video.file' => 'El video de fondo debe ser un archivo',
            'background_video.mimes' => 'El video de fondo debe ser un archivo de video',
            'background_video.max' => 'El video de fondo no puede tener más de 500MB',
            'frame_image.file' => 'La imagen del marco debe ser un archivo',
            'frame_image.mimes' => 'La imagen del marco debe ser un archivo de imagen',
            'frame_image.max' => 'La imagen del marco no puede tener más de 50MB',
            'image_style_prompt.string' => 'El prompt de estilo debe ser texto',
            'image_style_prompt.max' => 'El prompt de estilo no puede tener más de 1000 caracteres',
            'thumbnail_image_prompt.string' => 'El prompt de imagen del thumbnail debe ser texto',
            'thumbnail_image_prompt.max' => 'El prompt de imagen del thumbnail no puede tener más de 1000 caracteres',
        ]);

        Log::info('Validation passed');

        $channelName = $request->name;
        $channelDescription = $request->description;
        $imageStylePrompt = $request->image_style_prompt;
        $thumbnailImagePrompt = $request->thumbnail_image_prompt;
        $introName = $channel->intro; // Mantener el archivo actual por defecto
        $backgroundVideoName = $channel->background_video; // Mantener el archivo actual por defecto
        $frameImageName = $channel->frame_image; // Mantener el archivo actual por defecto
        $thumbnailTemplateName = $channel->thumbnail_template; // Mantener el archivo actual por defecto

        // Manejo del archivo de intro (opcional en update)
        if ($request->remove_intro) {
            Log::info('Intro removal requested for update');
            // Eliminar el archivo anterior si existe
            if ($channel->intro && Storage::disk('public')->exists('intros/' . $channel->intro)) {
                Storage::disk('public')->delete('intros/' . $channel->intro);
                Log::info('Old intro file deleted:', ['filename' => $channel->intro]);
            }
            $introName = null;
        } else if ($request->hasFile('intro')) {
            Log::info('New intro file detected for update');
            $intro = $request->file('intro');
            Log::info('Intro file details:', [
                'original_name' => $intro->getClientOriginalName(),
                'size' => $intro->getSize(),
                'mime_type' => $intro->getMimeType(),
                'is_valid' => $intro->isValid()
            ]);

            if ($intro->isValid()) {
                // Eliminar el archivo anterior si existe
                if ($channel->intro && Storage::disk('public')->exists('intros/' . $channel->intro)) {
                    Storage::disk('public')->delete('intros/' . $channel->intro);
                    Log::info('Old intro file deleted:', ['filename' => $channel->intro]);
                }

                $introName = time() . '_' . uniqid() . '.' . $intro->getClientOriginalExtension();
                Log::info('Generated intro filename:', ['filename' => $introName]);

                // Usar Storage facade para guardar en storage/app/public/intros
                try {
                    $path = Storage::disk('public')->putFileAs('intros', $intro, $introName);
                    Log::info('Intro storage result:', ['path' => $path]);

                    // Verificar que el archivo se guardó correctamente
                    if (!$path) {
                        Log::error('Failed to save intro file');
                        return back()->withErrors(['intro' => 'Error al guardar el archivo de intro']);
                    }

                    // Verificar que el archivo existe
                    $exists = Storage::disk('public')->exists('intros/' . $introName);
                    Log::info('Intro file exists check:', ['exists' => $exists]);

                } catch (\Exception $e) {
                    Log::error('Exception saving intro file:', ['error' => $e->getMessage()]);
                    return back()->withErrors(['intro' => 'Error al guardar el archivo: ' . $e->getMessage()]);
                }
            } else {
                Log::error('Intro file is not valid');
                return back()->withErrors(['intro' => 'El archivo no es válido']);
            }
        }

        // Manejo del archivo background_video
        if ($request->remove_background_video) {
            Log::info('Background video removal requested for update');
            if ($channel->background_video && Storage::disk('public')->exists('backgrounds/' . $channel->background_video)) {
                Storage::disk('public')->delete('backgrounds/' . $channel->background_video);
                Log::info('Old background video file deleted:', ['filename' => $channel->background_video]);
            }
            $backgroundVideoName = null;
        } else if ($request->hasFile('background_video')) {
            Log::info('New background video file detected for update');
            $backgroundVideo = $request->file('background_video');

            if ($backgroundVideo->isValid()) {
                // Eliminar el archivo anterior si existe
                if ($channel->background_video && Storage::disk('public')->exists('backgrounds/' . $channel->background_video)) {
                    Storage::disk('public')->delete('backgrounds/' . $channel->background_video);
                    Log::info('Old background video file deleted:', ['filename' => $channel->background_video]);
                }

                $backgroundVideoName = time() . '_' . uniqid() . '.' . $backgroundVideo->getClientOriginalExtension();

                try {
                    $path = Storage::disk('public')->putFileAs('backgrounds', $backgroundVideo, $backgroundVideoName);

                    if (!$path) {
                        Log::error('Failed to save background video file');
                        return back()->withErrors(['background_video' => 'Error al guardar el video de fondo']);
                    }

                } catch (\Exception $e) {
                    Log::error('Exception saving background video file:', ['error' => $e->getMessage()]);
                    return back()->withErrors(['background_video' => 'Error al guardar el video de fondo: ' . $e->getMessage()]);
                }
            } else {
                Log::error('Background video file is not valid');
                return back()->withErrors(['background_video' => 'El video de fondo no es válido']);
            }
        }

        // Manejo del archivo frame_image
        if ($request->remove_frame_image) {
            Log::info('Frame image removal requested for update');
            if ($channel->frame_image && Storage::disk('public')->exists('frames/' . $channel->frame_image)) {
                Storage::disk('public')->delete('frames/' . $channel->frame_image);
                Log::info('Old frame image file deleted:', ['filename' => $channel->frame_image]);
            }
            $frameImageName = null;
        } else if ($request->hasFile('frame_image')) {
            Log::info('New frame image file detected for update');
            $frameImage = $request->file('frame_image');

            if ($frameImage->isValid()) {
                // Eliminar el archivo anterior si existe
                if ($channel->frame_image && Storage::disk('public')->exists('frames/' . $channel->frame_image)) {
                    Storage::disk('public')->delete('frames/' . $channel->frame_image);
                    Log::info('Old frame image file deleted:', ['filename' => $channel->frame_image]);
                }

                $frameImageName = time() . '_' . uniqid() . '.' . $frameImage->getClientOriginalExtension();

                try {
                    $path = Storage::disk('public')->putFileAs('frames', $frameImage, $frameImageName);

                    if (!$path) {
                        Log::error('Failed to save frame image file');
                        return back()->withErrors(['frame_image' => 'Error al guardar la imagen del marco']);
                    }

                } catch (\Exception $e) {
                    Log::error('Exception saving frame image file:', ['error' => $e->getMessage()]);
                    return back()->withErrors(['frame_image' => 'Error al guardar la imagen del marco: ' . $e->getMessage()]);
                }
            } else {
                Log::error('Frame image file is not valid');
                return back()->withErrors(['frame_image' => 'La imagen del marco no es válida']);
            }
        }

                // Manejo del thumbnail HTML
        if ($request->has('thumbnail')) {
            if ($request->filled('thumbnail')) {
                Log::info('Thumbnail HTML content detected for update');
                $thumbnailContent = $request->thumbnail;

                // Eliminar el archivo anterior si existe
                if ($channel->thumbnail_template && Storage::disk('public')->exists('thumbnail_templates/' . $channel->thumbnail_template)) {
                    Storage::disk('public')->delete('thumbnail_templates/' . $channel->thumbnail_template);
                    Log::info('Old thumbnail template file deleted:', ['filename' => $channel->thumbnail_template]);
                }

                // Generar nombre único para el archivo
                $thumbnailTemplateName = time() . '_' . uniqid() . '.html';
                Log::info('Generated thumbnail template filename:', ['filename' => $thumbnailTemplateName]);

                try {
                    // Crear el directorio si no existe
                    if (!Storage::disk('public')->exists('thumbnail_templates')) {
                        Storage::disk('public')->makeDirectory('thumbnail_templates');
                    }

                    // Guardar el contenido HTML en el archivo
                    $saved = Storage::disk('public')->put('thumbnail_templates/' . $thumbnailTemplateName, $thumbnailContent);
                    Log::info('Thumbnail template storage result:', ['saved' => $saved]);

                    if (!$saved) {
                        Log::error('Failed to save thumbnail template file');
                        return back()->withErrors(['thumbnail' => 'Error al guardar el template de thumbnail']);
                    }

                    // Verificar que el archivo existe
                    $exists = Storage::disk('public')->exists('thumbnail_templates/' . $thumbnailTemplateName);
                    Log::info('Thumbnail template file exists check:', ['exists' => $exists]);

                } catch (\Exception $e) {
                    Log::error('Exception saving thumbnail template file:', ['error' => $e->getMessage()]);
                    return back()->withErrors(['thumbnail' => 'Error al guardar el template de thumbnail: ' . $e->getMessage()]);
                }
            } else {
                // Si el campo thumbnail está presente pero vacío, eliminar el template
                Log::info('Empty thumbnail field detected, removing template');

                // Eliminar el archivo anterior si existe
                if ($channel->thumbnail_template && Storage::disk('public')->exists('thumbnail_templates/' . $channel->thumbnail_template)) {
                    Storage::disk('public')->delete('thumbnail_templates/' . $channel->thumbnail_template);
                    Log::info('Thumbnail template file deleted:', ['filename' => $channel->thumbnail_template]);
                }

                $thumbnailTemplateName = null;
            }
        }

        Log::info('Updating channel with data:', [
            'name' => $channelName,
            'description' => $channelDescription,
            'intro' => $introName,
            'background_video' => $backgroundVideoName,
            'frame_image' => $frameImageName,
            'image_style_prompt' => $imageStylePrompt,
            'thumbnail_template' => $thumbnailTemplateName,
            'thumbnail_image_prompt' => $thumbnailImagePrompt
        ]);

        $channel->update([
            'name' => $channelName,
            'description' => $channelDescription,
            'intro' => $introName,
            'background_video' => $backgroundVideoName,
            'frame_image' => $frameImageName,
            'image_style_prompt' => $imageStylePrompt,
            'thumbnail_template' => $thumbnailTemplateName,
            'thumbnail_image_prompt' => $thumbnailImagePrompt
        ]);

        Log::info('Channel updated successfully:', ['id' => $channel->id]);

        return redirect()->route('channels.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $channel = Channel::find($id);
        $channel->delete();

        return redirect()->route('channels.index');
    }
}
