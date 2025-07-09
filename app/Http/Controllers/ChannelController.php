<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Channel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use App\Helpers\OAuthWebhookToken;
use App\Models\Video;
use App\Services\YoutubeService;
use App\Models\YoutubeVideoStat;
use Illuminate\Support\Facades\Http; // Added for getVideoDetails

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

        return Inertia::render('Channels/Index', [
            'channels' => $channels
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Generar un token único para el webhook
        do {
            $webhookToken = OAuthWebhookToken::generateToken();
            $tokenValidation = OAuthWebhookToken::checkToken($webhookToken);
        } while (!$tokenValidation['valid']);

        return Inertia::render('Channels/Create', [
            'webhook_token' => $webhookToken,
            'app_url' => config('app.url')
        ]);
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
            'google_oauth_webhook_token' => 'required|string|size:16',
            'google_client_id' => 'nullable|string|max:255',
            'google_client_secret' => 'nullable|string|max:255',
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
            'google_oauth_webhook_token.required' => 'El token del webhook es requerido',
            'google_oauth_webhook_token.string' => 'El token del webhook debe ser texto',
            'google_oauth_webhook_token.size' => 'El token del webhook debe tener exactamente 16 caracteres',
            'google_client_id.string' => 'El Client ID de Google debe ser texto',
            'google_client_id.max' => 'El Client ID de Google no puede tener más de 255 caracteres',
            'google_client_secret.string' => 'El Client Secret de Google debe ser texto',
            'google_client_secret.max' => 'El Client Secret de Google no puede tener más de 255 caracteres',
        ]);

        // Validar el token del webhook
        $webhookToken = $request->google_oauth_webhook_token;
        $tokenValidation = OAuthWebhookToken::checkToken($webhookToken);

        if (!$tokenValidation['valid']) {
            Log::error('Invalid webhook token provided:', [
                'token' => $webhookToken,
                'errors' => $tokenValidation['errors']
            ]);
            return back()->withErrors(['google_oauth_webhook_token' => 'Token del webhook inválido: ' . implode(', ', $tokenValidation['errors'])]);
        }

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
            'thumbnail_image_prompt' => $thumbnailImagePrompt,
            'google_oauth_webhook_token' => $webhookToken,
            'google_client_id' => $request->google_client_id,
            'google_client_secret' => $request->google_client_secret,
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
            'thumbnail' => $thumbnailContent,
            'app_url' => config('app.url')
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
            'google_oauth_webhook_token' => 'nullable|string|size:16',
            'google_client_id' => 'nullable|string|max:255',
            'google_client_secret' => 'nullable|string|max:255',
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
            'google_oauth_webhook_token.string' => 'El token del webhook debe ser texto',
            'google_oauth_webhook_token.size' => 'El token del webhook debe tener exactamente 16 caracteres',
            'google_client_id.string' => 'El Client ID de Google debe ser texto',
            'google_client_id.max' => 'El Client ID de Google no puede tener más de 255 caracteres',
            'google_client_secret.string' => 'El Client Secret de Google debe ser texto',
            'google_client_secret.max' => 'El Client Secret de Google no puede tener más de 255 caracteres',
        ]);

        // Validar el token del webhook si se proporciona
        if ($request->has('google_oauth_webhook_token') && $request->filled('google_oauth_webhook_token')) {
            $webhookToken = $request->google_oauth_webhook_token;
            $tokenValidation = OAuthWebhookToken::checkToken($webhookToken);

            if (!$tokenValidation['valid']) {
                Log::error('Invalid webhook token provided for update:', [
                    'token' => $webhookToken,
                    'errors' => $tokenValidation['errors']
                ]);
                return back()->withErrors(['google_oauth_webhook_token' => 'Token del webhook inválido: ' . implode(', ', $tokenValidation['errors'])]);
            }
        }

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

        // Preparar datos para actualizar
        $updateData = [
            'name' => $channelName,
            'description' => $channelDescription,
            'intro' => $introName,
            'background_video' => $backgroundVideoName,
            'frame_image' => $frameImageName,
            'image_style_prompt' => $imageStylePrompt,
            'thumbnail_template' => $thumbnailTemplateName,
            'thumbnail_image_prompt' => $thumbnailImagePrompt,
            'google_client_id' => $request->google_client_id,
            'google_client_secret' => $request->google_client_secret,
        ];

        // Solo actualizar el token del webhook si se proporciona uno nuevo
        if ($request->has('google_oauth_webhook_token') && $request->filled('google_oauth_webhook_token')) {
            $updateData['google_oauth_webhook_token'] = $request->google_oauth_webhook_token;
        }

        $channel->update($updateData);

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

    /**
     * Show the settings page for the specified channel.
     */
    public function settings(string $id)
    {
        $channel = Channel::findOrFail($id);

        return Inertia::render('Channels/Settings', [
            'channel' => $channel,
            'success' => session('success'),
            'error' => session('error')
        ]);
    }

    /**
     * Show the channel analytics page
     */
    public function analytics(string $id)
    {
        $channel = Channel::findOrFail($id);

        return Inertia::render('Channels/Analytics', [
            'channel' => $channel,
            'success' => session('success'),
            'error' => session('error')
        ]);
    }

    /**
     * Show individual video analytics page
     */
    public function videoAnalyticsPage(string $channelId, string $videoId)
    {
        $channel = Channel::findOrFail($channelId);

        return Inertia::render('Channels/VideoAnalytics', [
            'channel' => $channel,
            'videoId' => $videoId,
            'success' => session('success'),
            'error' => session('error')
        ]);
    }

    /**
     * Get video details from YouTube API
     */
    public function getVideoDetails(string $channelId, string $videoId)
    {
        try {
            $channel = Channel::findOrFail($channelId);

            if (!$channel->google_access_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Canal no tiene tokens de acceso'
                ]);
            }

            // Check if token is expired
            if ($channel->google_access_token_expires_at && now() > $channel->google_access_token_expires_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de acceso expirado'
                ]);
            }

            $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
                'part' => 'snippet,statistics,contentDetails,status',
                'id' => $videoId,
                'access_token' => $channel->google_access_token
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (empty($data['items'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Video no encontrado'
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'data' => $data['items'][0]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener datos del video: ' . $response->body()
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Test the YouTube API connection for the specified channel
     */
    public function testConnection(string $id)
    {
        $channel = Channel::findOrFail($id);
        $youtubeService = new YoutubeService();

        $result = $youtubeService->testConnection($channel);

        if ($result['success']) {
            return response()->json($result);
        } else {
            return response()->json($result, 400);
        }
    }

    /**
     * Get detailed channel information from YouTube
     */
    public function getYoutubeChannelInfo(string $id)
    {
        $channel = Channel::findOrFail($id);
        $youtubeService = new YoutubeService();

        $channelInfo = $youtubeService->getChannelInfo($channel);

        if ($channelInfo) {
            return response()->json([
                'success' => true,
                'data' => $channelInfo
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener información del canal'
            ], 400);
        }
    }

    /**
     * Get channel statistics from YouTube
     */
    public function getYoutubeChannelStats(string $id)
    {
        $channel = Channel::findOrFail($id);
        $youtubeService = new YoutubeService();

        $stats = $youtubeService->getChannelStatistics($channel);

        if ($stats) {
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron obtener las estadísticas del canal'
            ], 400);
        }
    }

    /**
     * Get channel videos from YouTube
     */
    public function getYoutubeChannelVideos(string $id, Request $request)
    {
        $channel = Channel::findOrFail($id);
        $youtubeService = new YoutubeService();

        $maxResults = $request->get('max_results', 25);
        $videos = $youtubeService->getChannelVideos($channel, $maxResults);

        if ($videos) {
            return response()->json([
                'success' => true,
                'data' => $videos
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron obtener los videos del canal'
            ], 400);
        }
    }

    /**
     * Get channel playlists from YouTube
     */
    public function getYoutubeChannelPlaylists(string $id, Request $request)
    {
        $channel = Channel::findOrFail($id);
        $youtubeService = new YoutubeService();

        $maxResults = $request->get('max_results', 25);
        $playlists = $youtubeService->getChannelPlaylists($channel, $maxResults);

        if ($playlists) {
            return response()->json([
                'success' => true,
                'data' => $playlists
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron obtener las playlists del canal'
            ], 400);
        }
    }

    /**
     * Get comprehensive channel dashboard data from YouTube
     * This demonstrates how to use multiple YoutubeService methods together
     */
    public function getYoutubeChannelDashboard(string $id)
    {
        $channel = Channel::findOrFail($id);
        $youtubeService = new YoutubeService();

        try {
            // Get basic channel info
            $channelInfo = $youtubeService->getChannelInfo($channel);
            if (!$channelInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener información básica del canal'
                ], 400);
            }

            // Get detailed statistics
            $statistics = $youtubeService->getChannelStatistics($channel);

            // Get recent videos (last 10)
            $recentVideos = $youtubeService->getChannelVideos($channel, 10);

            // Get playlists
            $playlists = $youtubeService->getChannelPlaylists($channel, 10);

            // Get branding settings
            $branding = $youtubeService->getChannelBranding($channel);

            $dashboardData = [
                'channel_info' => [
                    'id' => $channelInfo['id'] ?? null,
                    'title' => $channelInfo['snippet']['title'] ?? 'Sin título',
                    'description' => $channelInfo['snippet']['description'] ?? '',
                    'published_at' => $channelInfo['snippet']['publishedAt'] ?? null,
                    'thumbnail' => $channelInfo['snippet']['thumbnails']['high']['url'] ?? null,
                    'country' => $channelInfo['snippet']['country'] ?? null,
                ],
                'statistics' => $statistics,
                'recent_videos' => [
                    'total_results' => $recentVideos['pageInfo']['totalResults'] ?? 0,
                    'items' => $recentVideos['items'] ?? []
                ],
                'playlists' => [
                    'total_results' => $playlists['pageInfo']['totalResults'] ?? 0,
                    'items' => $playlists['items'] ?? []
                ],
                'branding' => $branding,
                'last_updated' => now()->toISOString()
            ];

            Log::info('YouTube dashboard data retrieved successfully', [
                'channel_id' => $channel->id,
                'youtube_channel_id' => $channelInfo['id'] ?? 'unknown'
            ]);

            return response()->json([
                'success' => true,
                'data' => $dashboardData
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting YouTube dashboard data', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del dashboard: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get video statistics only
     */
    public function getVideoStatistics(string $channelId, string $videoId)
    {
        $channel = Channel::findOrFail($channelId);
        $youtubeService = new YoutubeService();

        $stats = $youtubeService->getVideoStatistics($channel, $videoId);

        if ($stats) {
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron obtener las estadísticas del video'
            ], 400);
        }
    }

    /**
     * Get video comments
     */
    public function getVideoComments(string $channelId, string $videoId, Request $request)
    {
        $channel = Channel::findOrFail($channelId);
        $youtubeService = new YoutubeService();

        $maxResults = $request->get('max_results', 20);
        $order = $request->get('order', 'time'); // time or relevance

        $comments = $youtubeService->getVideoComments($channel, $videoId, $maxResults, $order);

        if ($comments) {
            return response()->json([
                'success' => true,
                'data' => $comments
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron obtener los comentarios del video'
            ], 400);
        }
    }

    /**
     * Get video performance metrics
     */
    public function getVideoPerformanceMetrics(string $channelId, string $videoId)
    {
        $channel = Channel::findOrFail($channelId);
        $youtubeService = new YoutubeService();

        $metrics = $youtubeService->getVideoPerformanceMetrics($channel, $videoId);

        if ($metrics) {
            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron obtener las métricas de rendimiento del video'
            ], 400);
        }
    }

    /**
     * Get video engagement analysis
     */
    public function getVideoEngagementAnalysis(string $channelId, string $videoId)
    {
        $channel = Channel::findOrFail($channelId);
        $youtubeService = new YoutubeService();

        $analysis = $youtubeService->getVideoEngagementAnalysis($channel, $videoId);

        if ($analysis) {
            return response()->json([
                'success' => true,
                'data' => $analysis
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener el análisis de engagement del video'
            ], 400);
        }
    }

    /**
     * Get channel's trending videos
     */
    public function getChannelTrendingVideos(string $id, Request $request)
    {
        $channel = Channel::findOrFail($id);
        $youtubeService = new YoutubeService();

        $days = $request->get('days', 30);
        $maxResults = $request->get('max_results', 10);

        $trendingVideos = $youtubeService->getChannelTrendingVideos($channel, $days, $maxResults);

        if ($trendingVideos) {
            return response()->json([
                'success' => true,
                'data' => $trendingVideos
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron obtener los videos trending del canal'
            ], 400);
        }
    }

    /**
     * Get channel's best performing videos
     */
    public function getChannelBestPerformingVideos(string $id, Request $request)
    {
        $channel = Channel::findOrFail($id);
        $youtubeService = new YoutubeService();

        $maxResults = $request->get('max_results', 10);
        $metric = $request->get('metric', 'viewCount'); // viewCount, likeCount, commentCount

        $bestVideos = $youtubeService->getChannelBestPerformingVideos($channel, $maxResults, $metric);

        if ($bestVideos) {
            return response()->json([
                'success' => true,
                'data' => $bestVideos
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron obtener los mejores videos del canal'
            ], 400);
        }
    }

    /**
     * Get video analytics for a specific period
     */
    public function getVideoAnalytics(string $channelId, string $videoId, Request $request)
    {
        $channel = Channel::findOrFail($channelId);
        $youtubeService = new YoutubeService();

        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $metrics = $request->get('metrics', ['views', 'likes', 'comments', 'shares', 'estimatedMinutesWatched']);

        $analytics = $youtubeService->getVideoAnalytics($channel, $videoId, $startDate, $endDate, $metrics);

        if ($analytics) {
            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron obtener los analytics del video'
            ], 400);
        }
    }

    /**
     * Get channel statistics from local database (synced data)
     */
    public function getChannelStatsFromDB(string $id)
    {
        $channel = Channel::with('latestYoutubeStats')->findOrFail($id);

        $stats = $channel->latestYoutubeStats;

        if (!$stats) {
            return response()->json([
                'success' => false,
                'message' => 'No hay estadísticas sincronizadas para este canal. Ejecuta la sincronización primero.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'channel_info' => [
                    'id' => $stats->youtube_channel_id,
                    'title' => $stats->title,
                    'description' => $stats->description,
                    'country' => $stats->country,
                    'published_at' => $stats->published_at,
                    'profile_image_url' => $stats->profile_image_url,
                    'banner_image_url' => $stats->banner_image_url
                ],
                'statistics' => [
                    'subscriber_count' => $stats->subscriber_count,
                    'video_count' => $stats->video_count,
                    'view_count' => $stats->view_count,
                    'hidden_subscriber_count' => $stats->hidden_subscriber_count,
                    'formatted_subscriber_count' => $stats->formatted_subscriber_count,
                    'formatted_view_count' => $stats->formatted_view_count
                ],
                'metrics' => [
                    'avg_views_per_video' => $stats->avg_views_per_video,
                    'engagement_rate' => $stats->engagement_rate,
                    'growth_rate_30d' => $stats->growth_rate_30d,
                    'videos_last_30d' => $stats->videos_last_30d
                ],
                'sync_info' => [
                    'last_synced_at' => $stats->last_synced_at,
                    'sync_successful' => $stats->sync_successful,
                    'is_recent' => $stats->isRecent()
                ]
            ]
        ]);
    }

    /**
     * Get channel videos statistics from local database
     */
    public function getChannelVideosFromDB(string $id, Request $request)
    {
        $channel = Channel::findOrFail($id);

        $limit = $request->get('limit', 20);
        $orderBy = $request->get('order_by', 'published_at');
        $orderDirection = $request->get('order_direction', 'desc');
        $days = $request->get('days'); // Filter by days if provided

        $query = YoutubeVideoStat::where('channel_id', $channel->id)
            ->where('sync_successful', true);

        // Filter by date if specified
        if ($days) {
            $query->where('published_at', '>=', now()->subDays($days));
        }

        $videos = $query->orderBy($orderBy, $orderDirection)
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_count' => $videos->count(),
                'videos' => $videos->map(function($video) {
                    return [
                        'id' => $video->youtube_video_id,
                        'title' => $video->title,
                        'description' => substr($video->description, 0, 200) . '...',
                        'published_at' => $video->published_at,
                        'duration' => $video->formatted_duration,
                        'thumbnail' => $video->thumbnail_high,
                        'statistics' => [
                            'view_count' => $video->view_count,
                            'like_count' => $video->like_count,
                            'comment_count' => $video->comment_count,
                            'formatted_view_count' => $video->formatted_view_count,
                            'formatted_like_count' => $video->formatted_like_count,
                            'formatted_comment_count' => $video->formatted_comment_count
                        ],
                        'metrics' => [
                            'engagement_rate' => (float) ($video->engagement_rate ?? 0),
                            'like_rate' => (float) ($video->like_rate ?? 0),
                            'comment_rate' => (float) ($video->comment_rate ?? 0),
                            'views_per_day' => (float) ($video->views_per_day ?? 0),
                            'performance_score' => (int) ($video->performance_score ?? 0),
                            'performance_level' => $video->performance_level ?? 'Sin datos'
                        ],
                        'last_synced_at' => $video->last_synced_at
                    ];
                })
            ]
        ]);
    }

    /**
     * Get top performing videos from local database
     */
    public function getTopPerformingVideosFromDB(string $id, Request $request)
    {
        $channel = Channel::findOrFail($id);

        $metric = $request->get('metric', 'view_count');
        $limit = $request->get('limit', 10);
        $days = $request->get('days'); // Filter by days if provided

        $allowedMetrics = ['view_count', 'like_count', 'comment_count', 'engagement_rate', 'performance_score'];

        if (!in_array($metric, $allowedMetrics)) {
            return response()->json([
                'success' => false,
                'message' => 'Métrica no válida. Use: ' . implode(', ', $allowedMetrics)
            ], 400);
        }

        $query = YoutubeVideoStat::where('channel_id', $channel->id)
            ->where('sync_successful', true);

        // Filter by date if specified
        if ($days) {
            $query->where('published_at', '>=', now()->subDays($days));
        }

        $videos = $query->orderBy($metric, 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'metric_used' => $metric,
                'total_count' => $videos->count(),
                'videos' => $videos->map(function($video) use ($metric) {
                    return [
                        'id' => $video->youtube_video_id,
                        'title' => $video->title,
                        'published_at' => $video->published_at,
                        'thumbnail' => $video->thumbnail_high,
                        'metric_value' => $video->{$metric},
                        'statistics' => [
                            'view_count' => $video->view_count,
                            'like_count' => $video->like_count,
                            'comment_count' => $video->comment_count
                        ],
                        'metrics' => [
                            'engagement_rate' => (float) ($video->engagement_rate ?? 0),
                            'performance_score' => (int) ($video->performance_score ?? 0),
                            'performance_level' => $video->performance_level ?? 'Sin datos'
                        ]
                    ];
                })
            ]
        ]);
    }

    /**
     * Get channel dashboard data from local database
     */
    public function getChannelDashboardFromDB(string $id)
    {
        $channel = Channel::with(['latestYoutubeStats', 'latestYoutubeVideoStats'])->findOrFail($id);

        $channelStats = $channel->latestYoutubeStats;

        if (!$channelStats) {
            return response()->json([
                'success' => false,
                'message' => 'No hay estadísticas sincronizadas para este canal. Ejecuta la sincronización primero.'
            ], 404);
        }

        // Get top 5 videos by views
        $topVideos = YoutubeVideoStat::where('channel_id', $channel->id)
            ->where('sync_successful', true)
            ->orderBy('view_count', 'desc')
            ->limit(5)
            ->get();

        // Get recent videos (last 30 days)
        $recentVideos = YoutubeVideoStat::where('channel_id', $channel->id)
            ->where('sync_successful', true)
            ->where('published_at', '>=', now()->subDays(30))
            ->orderBy('published_at', 'desc')
            ->limit(10)
            ->get();

        // Calculate average metrics from recent videos
        $avgEngagement = $recentVideos->avg('engagement_rate') ?? 0;
        $avgPerformance = $recentVideos->avg('performance_score') ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'channel_info' => [
                    'id' => $channelStats->youtube_channel_id,
                    'title' => $channelStats->title,
                    'description' => $channelStats->description,
                    'country' => $channelStats->country,
                    'profile_image_url' => $channelStats->profile_image_url,
                    'banner_image_url' => $channelStats->banner_image_url
                ],
                'statistics' => [
                    'subscriber_count' => $channelStats->subscriber_count,
                    'video_count' => $channelStats->video_count,
                    'view_count' => $channelStats->view_count,
                    'formatted_subscriber_count' => $channelStats->formatted_subscriber_count,
                    'formatted_view_count' => $channelStats->formatted_view_count
                ],
                'metrics' => [
                    'avg_views_per_video' => $channelStats->avg_views_per_video,
                    'videos_last_30d' => $channelStats->videos_last_30d,
                    'avg_engagement_rate' => round($avgEngagement, 2),
                    'avg_performance_score' => round($avgPerformance, 0)
                ],
                'top_videos' => $topVideos->map(function($video) {
                    return [
                        'id' => $video->youtube_video_id,
                        'title' => $video->title,
                        'view_count' => $video->view_count,
                        'formatted_view_count' => $video->formatted_view_count,
                        'thumbnail' => $video->thumbnail_high,
                        'performance_score' => (int) ($video->performance_score ?? 0)
                    ];
                }),
                'recent_videos' => $recentVideos->map(function($video) {
                    return [
                        'id' => $video->youtube_video_id,
                        'title' => $video->title,
                        'published_at' => $video->published_at,
                        'view_count' => $video->view_count,
                        'engagement_rate' => (float) ($video->engagement_rate ?? 0),
                        'thumbnail' => $video->thumbnail_high
                    ];
                }),
                'sync_info' => [
                    'last_synced_at' => $channelStats->last_synced_at,
                    'is_recent' => $channelStats->isRecent(),
                    'sync_successful' => $channelStats->sync_successful
                ]
            ]
        ]);
    }

    /**
     * Trigger manual sync for a specific channel
     */
    public function triggerSync(string $id)
    {
        $channel = Channel::findOrFail($id);

        if (!$channel->hasValidOAuthTokens() && !$channel->hasExpiredTokens()) {
            return response()->json([
                'success' => false,
                'message' => 'El canal no tiene tokens OAuth configurados'
            ], 400);
        }

        try {
            // Run the sync command for this specific channel
            Artisan::call('youtube:sync-all', [
                '--channel' => $channel->id,
                '--force' => true
            ]);

            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Sincronización iniciada exitosamente',
                'output' => $output
            ]);

        } catch (\Exception $e) {
            Log::error('Error triggering manual sync', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar la sincronización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start the OAuth authentication process with Google
     */
    public function startOAuth(string $id)
    {
        $channel = Channel::findOrFail($id);

        // Check if channel has required OAuth credentials
        if (!$channel->google_client_id || !$channel->google_client_secret || !$channel->google_oauth_webhook_token) {
            return response()->json([
                'success' => false,
                'message' => 'El canal no tiene configuradas las credenciales OAuth necesarias (Client ID, Client Secret y Webhook Token)',
                'error' => 'MISSING_OAUTH_CREDENTIALS'
            ], 400);
        }

        // Build the OAuth authorization URL
        $redirectUri = config('app.url') . '/webhook/oauth/' . $channel->google_oauth_webhook_token;

        $scopes = [
            'https://www.googleapis.com/auth/youtube.readonly',
            'https://www.googleapis.com/auth/youtube.upload',
            'https://www.googleapis.com/auth/youtube'
        ];

        $params = [
            'client_id' => $channel->google_client_id,
            'redirect_uri' => $redirectUri,
            'scope' => implode(' ', $scopes),
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => 'channel_' . $channel->id . '_' . time()
        ];

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

        Log::info('OAuth authentication URL generated', [
            'channel_id' => $channel->id,
            'channel_name' => $channel->name,
            'redirect_uri' => $redirectUri,
            'scopes' => $scopes
        ]);

        return response()->json([
            'success' => true,
            'message' => 'URL de autenticación generada correctamente',
            'auth_url' => $authUrl,
            'redirect_uri' => $redirectUri
        ]);
    }

    /**
     * Generate a new OAuth webhook token (without saving to database)
     */
    public function regenerateToken()
    {
        Log::info('Generating new OAuth webhook token');

        try {
            // Generar un token único para el webhook
            do {
                $webhookToken = OAuthWebhookToken::generateToken();
                $tokenValidation = OAuthWebhookToken::checkToken($webhookToken);
            } while (!$tokenValidation['valid']);

            Log::info('New OAuth webhook token generated successfully:', [
                'new_token' => $webhookToken
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token generado exitosamente',
                'token' => $webhookToken
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating OAuth webhook token:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle OAuth webhook requests from Google
     */
    public function handleOAuthWebhook(Request $request, string $token)
    {
        // Log all request information
        Log::info('OAuth Webhook received', [
            'timestamp' => now()->toISOString(),
            'method' => $request->method(),
            'token' => $token,
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'query_parameters' => $request->query(),
            'body_parameters' => $request->all(),
            'raw_body' => $request->getContent(),
            'content_type' => $request->header('Content-Type'),
        ]);

        // Try to find the channel with this token
        $channel = Channel::where('google_oauth_webhook_token', $token)->first();

        if ($channel) {
            Log::info('Channel found for webhook token', [
                'channel_id' => $channel->id,
                'channel_name' => $channel->name,
                'token' => $token
            ]);
        } else {
            Log::warning('No channel found for webhook token', [
                'token' => $token,
                'available_tokens' => Channel::whereNotNull('google_oauth_webhook_token')
                    ->pluck('google_oauth_webhook_token')
                    ->toArray()
            ]);
        }

        // Log specific OAuth-related data if present
        $oauthData = [];
        if ($request->has('code')) {
            $authCode = $request->input('code');
            $oauthData['authorization_code'] = $authCode;

            // Output to console for debugging
            echo "\n=== GOOGLE OAUTH CODE RECEIVED ===\n";
            echo "Code: " . $authCode . "\n";
            echo "Channel: " . ($channel ? $channel->name : 'Not found') . "\n";
            echo "Timestamp: " . now()->toISOString() . "\n";
            echo "=====================================\n\n";

            // Exchange authorization code for access token if channel exists
            if ($channel && $channel->google_client_id && $channel->google_client_secret) {
                try {
                    $exchangeSuccess = $this->exchangeCodeForTokens($channel, $authCode);

                    // If exchange was successful, redirect to settings page
                    if ($exchangeSuccess) {
                        echo "REDIRECTING to settings page...\n";
                        return redirect()->route('channels.settings', $channel->id)
                            ->with('success', 'Autenticación con Google completada exitosamente');
                    }
                } catch (\Exception $e) {
                    Log::error('Error exchanging code for tokens', [
                        'channel_id' => $channel->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    echo "ERROR: Failed to exchange code for tokens: " . $e->getMessage() . "\n";

                    // Redirect to settings with error message
                    return redirect()->route('channels.settings', $channel->id)
                        ->with('error', 'Error al completar la autenticación: ' . $e->getMessage());
                }
            } else {
                Log::warning('Cannot exchange code for tokens - missing channel or credentials', [
                    'channel_found' => $channel ? true : false,
                    'has_client_id' => $channel ? !empty($channel->google_client_id) : false,
                    'has_client_secret' => $channel ? !empty($channel->google_client_secret) : false
                ]);
                echo "WARNING: Cannot exchange code - missing channel or credentials\n";

                if ($channel) {
                    return redirect()->route('channels.settings', $channel->id)
                        ->with('error', 'No se pueden intercambiar los tokens: faltan credenciales OAuth');
                }
            }
        }
        if ($request->has('state')) {
            $oauthData['state'] = $request->input('state');
        }
        if ($request->has('error')) {
            $oauthData['error'] = $request->input('error');
            $oauthData['error_description'] = $request->input('error_description');
            // Output error to console
            echo "\n=== GOOGLE OAUTH ERROR ===\n";
            echo "Error: " . $request->input('error') . "\n";
            echo "Description: " . $request->input('error_description', 'No description') . "\n";
            echo "==========================\n\n";
        }
        if ($request->has('scope')) {
            $oauthData['scope'] = $request->input('scope');
        }

        if (!empty($oauthData)) {
            Log::info('OAuth specific data received', $oauthData);
        }

        // If we processed an OAuth code but couldn't find the channel, redirect to channels index
        if ($request->has('code') && !$channel) {
            Log::warning('OAuth code received but no channel found for token', [
                'token' => $token,
                'code' => substr($request->input('code'), 0, 20) . '...'
            ]);

            return redirect()->route('channels.index')
                ->with('error', 'No se encontró el canal para el token de webhook proporcionado');
        }

        // Return success response for non-redirect cases
        return response()->json([
            'success' => true,
            'message' => 'Webhook received successfully',
            'timestamp' => now()->toISOString(),
            'token' => $token,
            'channel_found' => $channel ? true : false
        ], 200);
    }

    /**
     * Exchange authorization code for access and refresh tokens
     */
    private function exchangeCodeForTokens(Channel $channel, string $authCode)
    {
        Log::info('Exchanging authorization code for tokens', [
            'channel_id' => $channel->id,
            'channel_name' => $channel->name
        ]);

        echo "=== EXCHANGING CODE FOR TOKENS ===\n";
        echo "Channel: " . $channel->name . "\n";
        echo "Code: " . substr($authCode, 0, 20) . "...\n";

        // Prepare the request to Google's token endpoint
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $redirectUri = config('app.url') . '/webhook/oauth/' . $channel->google_oauth_webhook_token;

        $postData = [
            'client_id' => $channel->google_client_id,
            'client_secret' => $channel->google_client_secret,
            'code' => $authCode,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri
        ];

        // Make the request to Google
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new \Exception('Failed to make request to Google token endpoint');
        }

        $responseData = json_decode($response, true);

        if ($httpCode !== 200 || !$responseData) {
            Log::error('Google token exchange failed', [
                'http_code' => $httpCode,
                'response' => $response
            ]);
            throw new \Exception('Google token exchange failed: ' . ($responseData['error_description'] ?? 'Unknown error'));
        }

        if (!isset($responseData['access_token'])) {
            Log::error('No access token in response', ['response' => $responseData]);
            throw new \Exception('No access token received from Google');
        }

        // Calculate expiration time
        $expiresIn = $responseData['expires_in'] ?? 3600; // Default to 1 hour if not provided
        $expiresAt = now()->addSeconds($expiresIn);

        // Update the channel with the new tokens
        $updateData = [
            'google_access_token' => $responseData['access_token'],
            'google_access_token_expires_at' => $expiresAt
        ];

        if (isset($responseData['refresh_token'])) {
            $updateData['google_refresh_token'] = $responseData['refresh_token'];
        }

        $channel->update($updateData);

        Log::info('Tokens saved successfully', [
            'channel_id' => $channel->id,
            'expires_at' => $expiresAt->toISOString(),
            'has_refresh_token' => isset($responseData['refresh_token'])
        ]);

        echo "SUCCESS: Tokens saved!\n";
        echo "Access token: " . substr($responseData['access_token'], 0, 20) . "...\n";
        echo "Expires at: " . $expiresAt->toISOString() . "\n";
        echo "Refresh token: " . (isset($responseData['refresh_token']) ? 'YES' : 'NO') . "\n";
        echo "===================================\n\n";

        // Return success indicator for redirect
        return true;
    }

    /**
     * Delete all videos for a specific channel
     */
    public function deleteVideos(Channel $channel)
    {
        Log::info('Deleting videos for channel:', ['channel_id' => $channel->id, 'channel_name' => $channel->name]);

        try {
            // Mark all videos of this channel as deleted
            $videosUpdated = $channel->videos()->update(['is_deleted' => true]);
            Log::info('Videos marked as deleted:', ['count' => $videosUpdated]);

            // Delete the channel directory from storage
            $channelPath = 'channels/' . $channel->id;
            if (Storage::disk('public')->exists($channelPath)) {
                Storage::disk('public')->deleteDirectory($channelPath);
                Log::info('Channel directory deleted:', ['path' => $channelPath]);
            } else {
                Log::info('Channel directory does not exist:', ['path' => $channelPath]);
            }

            return redirect()->back()->with('success', "Todos los videos del canal '{$channel->name}' han sido eliminados correctamente.");

        } catch (\Exception $e) {
            Log::error('Error deleting channel videos:', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Error al eliminar los videos del canal: ' . $e->getMessage());
        }
    }

    /**
     * Show global analytics page
     */
    public function globalAnalytics()
    {
        return Inertia::render('Analytics/Index', [
            'success' => session('success'),
            'error' => session('error')
        ]);
    }

    /**
     * Get global statistics from all channels
     */
    public function getGlobalStats()
    {
        try {
            $totalChannels = Channel::count();
            $totalVideos = YoutubeVideoStat::where('sync_successful', true)->count();
            $totalViews = YoutubeVideoStat::where('sync_successful', true)->sum('view_count');
            $totalLikes = YoutubeVideoStat::where('sync_successful', true)->sum('like_count');
            $totalComments = YoutubeVideoStat::where('sync_successful', true)->sum('comment_count');

            // Calculate averages
            $avgViewsPerVideo = $totalVideos > 0 ? round($totalViews / $totalVideos) : 0;
            $avgLikesPerVideo = $totalVideos > 0 ? round($totalLikes / $totalVideos) : 0;
            $avgCommentsPerVideo = $totalVideos > 0 ? round($totalComments / $totalVideos) : 0;

            // Get recent videos (last 30 days)
            $recentVideos = YoutubeVideoStat::where('sync_successful', true)
                ->where('published_at', '>=', now()->subDays(30))
                ->count();

            // Calculate global engagement rate
            $globalEngagementRate = $totalViews > 0 ? (($totalLikes + $totalComments) / $totalViews) * 100 : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'totals' => [
                        'channels' => $totalChannels,
                        'videos' => $totalVideos,
                        'views' => $totalViews,
                        'likes' => $totalLikes,
                        'comments' => $totalComments,
                        'recent_videos' => $recentVideos
                    ],
                    'averages' => [
                        'views_per_video' => $avgViewsPerVideo,
                        'likes_per_video' => $avgLikesPerVideo,
                        'comments_per_video' => $avgCommentsPerVideo,
                        'global_engagement_rate' => round($globalEngagementRate, 3)
                    ],
                    'formatted' => [
                        'total_views' => $this->formatNumber($totalViews),
                        'total_likes' => $this->formatNumber($totalLikes),
                        'total_comments' => $this->formatNumber($totalComments),
                        'avg_views_per_video' => $this->formatNumber($avgViewsPerVideo)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas globales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all videos from all channels
     */
    public function getAllVideos(Request $request)
    {
        try {
            $limit = $request->get('limit', 100);
            $orderBy = $request->get('order_by', 'view_count');
            $orderDirection = $request->get('order_direction', 'desc');
            $days = $request->get('days'); // Filter by days if provided

            $allowedOrderBy = ['view_count', 'like_count', 'comment_count', 'published_at', 'engagement_rate', 'performance_score'];

            if (!in_array($orderBy, $allowedOrderBy)) {
                $orderBy = 'view_count';
            }

            $query = YoutubeVideoStat::where('sync_successful', true);

            // Filter by date if specified
            if ($days) {
                $query->where('published_at', '>=', now()->subDays($days));
            }

            $videos = $query->orderBy($orderBy, $orderDirection)
                ->limit($limit)
                ->get();

            // Get all channels for reference
            $channels = Channel::all(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_count' => $videos->count(),
                    'order_by' => $orderBy,
                    'order_direction' => $orderDirection,
                    'channels' => $channels,
                    'videos' => $videos->map(function($video) {
                        return [
                            'id' => $video->youtube_video_id,
                            'channel_id' => $video->channel_id,
                            'title' => $video->title,
                            'description' => substr($video->description, 0, 200) . '...',
                            'published_at' => $video->published_at,
                            'duration' => $video->formatted_duration,
                            'thumbnail' => $video->thumbnail_high,
                            'statistics' => [
                                'view_count' => $video->view_count,
                                'like_count' => $video->like_count,
                                'comment_count' => $video->comment_count,
                                'formatted_view_count' => $video->formatted_view_count,
                                'formatted_like_count' => $video->formatted_like_count,
                                'formatted_comment_count' => $video->formatted_comment_count
                            ],
                            'metrics' => [
                                'engagement_rate' => (float) ($video->engagement_rate ?? 0),
                                'like_rate' => (float) ($video->like_rate ?? 0),
                                'comment_rate' => (float) ($video->comment_rate ?? 0),
                                'views_per_day' => (float) ($video->views_per_day ?? 0),
                                'performance_score' => (int) ($video->performance_score ?? 0),
                                'performance_level' => $video->performance_level ?? 'Sin datos'
                            ],
                            'last_synced_at' => $video->last_synced_at
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener todos los videos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trigger global sync for all channels
     */
    public function triggerGlobalSync()
    {
        try {
            // Run the sync command for all channels
            Artisan::call('youtube:sync-all', [
                '--force' => true
            ]);

            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Sincronización global iniciada correctamente',
                'output' => $output
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar la sincronización global: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to format numbers
     */
    private function formatNumber($num)
    {
        if ($num >= 1000000000) return number_format($num / 1000000000, 1) . 'B';
        if ($num >= 1000000) return number_format($num / 1000000, 1) . 'M';
        if ($num >= 1000) return number_format($num / 1000, 1) . 'K';
        return number_format($num);
    }
}
