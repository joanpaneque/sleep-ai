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
        ]);

        Log::info('Validation passed');

        $channelName = $request->name;
        $channelDescription = $request->description;

        // Manejo más robusto del archivo
        $introName = null;

        // Si no se está eliminando la intro y hay archivo
        if (!$request->remove_intro && $request->hasFile('intro')) {
            Log::info('File detected');
            $intro = $request->file('intro');
            Log::info('File details:', [
                'original_name' => $intro->getClientOriginalName(),
                'size' => $intro->getSize(),
                'mime_type' => $intro->getMimeType(),
                'is_valid' => $intro->isValid()
            ]);

            if ($intro->isValid()) {
                $introName = time() . '_' . uniqid() . '.' . $intro->getClientOriginalExtension();
                Log::info('Generated filename:', ['filename' => $introName]);

                // Usar Storage facade para guardar en storage/app/public/intros
                try {
                    $path = Storage::disk('public')->putFileAs('intros', $intro, $introName);
                    Log::info('Storage result:', ['path' => $path]);

                    // Verificar que el archivo se guardó correctamente
                    if (!$path) {
                        Log::error('Failed to save file');
                        return back()->withErrors(['intro' => 'Error al guardar el archivo de intro']);
                    }

                    // Verificar que el archivo existe
                    $exists = Storage::disk('public')->exists('intros/' . $introName);
                    Log::info('File exists check:', ['exists' => $exists]);

                } catch (\Exception $e) {
                    Log::error('Exception saving file:', ['error' => $e->getMessage()]);
                    return back()->withErrors(['intro' => 'Error al guardar el archivo: ' . $e->getMessage()]);
                }
            } else {
                Log::error('File is not valid');
                return back()->withErrors(['intro' => 'El archivo no es válido']);
            }
        } else if ($request->remove_intro) {
            Log::info('Intro removal requested');
            $introName = null;
        } else {
            Log::error('No file detected in request');
        }

        Log::info('Creating channel with data:', [
            'name' => $channelName,
            'description' => $channelDescription,
            'intro' => $introName
        ]);

        $channel = Channel::create([
            'name' => $channelName,
            'description' => $channelDescription,
            'intro' => $introName
        ]);

        Log::info('Channel created successfully:', ['id' => $channel->id]);

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

        return Inertia::render('Channels/Edit', [
            'channel' => $channel
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
        ]);

        Log::info('Validation passed');

        $channelName = $request->name;
        $channelDescription = $request->description;
        $introName = $channel->intro; // Mantener el archivo actual por defecto

        // Manejo del archivo de intro (opcional en update)
        if ($request->remove_intro) {
            Log::info('Intro removal requested for update');
            // Eliminar el archivo anterior si existe
            if ($channel->intro && Storage::disk('public')->exists('intros/' . $channel->intro)) {
                Storage::disk('public')->delete('intros/' . $channel->intro);
                Log::info('Old file deleted:', ['filename' => $channel->intro]);
            }
            $introName = null;
        } else if ($request->hasFile('intro')) {
            Log::info('New file detected for update');
            $intro = $request->file('intro');
            Log::info('File details:', [
                'original_name' => $intro->getClientOriginalName(),
                'size' => $intro->getSize(),
                'mime_type' => $intro->getMimeType(),
                'is_valid' => $intro->isValid()
            ]);

            if ($intro->isValid()) {
                // Eliminar el archivo anterior si existe
                if ($channel->intro && Storage::disk('public')->exists('intros/' . $channel->intro)) {
                    Storage::disk('public')->delete('intros/' . $channel->intro);
                    Log::info('Old file deleted:', ['filename' => $channel->intro]);
                }

                $introName = time() . '_' . uniqid() . '.' . $intro->getClientOriginalExtension();
                Log::info('Generated filename:', ['filename' => $introName]);

                // Usar Storage facade para guardar en storage/app/public/intros
                try {
                    $path = Storage::disk('public')->putFileAs('intros', $intro, $introName);
                    Log::info('Storage result:', ['path' => $path]);

                    // Verificar que el archivo se guardó correctamente
                    if (!$path) {
                        Log::error('Failed to save file');
                        return back()->withErrors(['intro' => 'Error al guardar el archivo de intro']);
                    }

                    // Verificar que el archivo existe
                    $exists = Storage::disk('public')->exists('intros/' . $introName);
                    Log::info('File exists check:', ['exists' => $exists]);

                } catch (\Exception $e) {
                    Log::error('Exception saving file:', ['error' => $e->getMessage()]);
                    return back()->withErrors(['intro' => 'Error al guardar el archivo: ' . $e->getMessage()]);
                }
            } else {
                Log::error('File is not valid');
                return back()->withErrors(['intro' => 'El archivo no es válido']);
            }
        }

        Log::info('Updating channel with data:', [
            'name' => $channelName,
            'description' => $channelDescription,
            'intro' => $introName
        ]);

        $channel->update([
            'name' => $channelName,
            'description' => $channelDescription,
            'intro' => $introName
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
