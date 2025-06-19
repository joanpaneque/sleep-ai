<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    channel: Object
})

// Mock data for demonstration
const completedVideos = [
    {
        id: 1,
        title: "Introduction to Sleep Science",
        duration: "12:30",
        publishedAt: "2024-03-15",
        status: "completed"
    },
    {
        id: 2,
        title: "Sleep Meditation Techniques",
        duration: "15:45",
        publishedAt: "2024-03-10",
        status: "completed"
    },
    {
        id: 3,
        title: "Better Sleep Habits",
        duration: "20:15",
        publishedAt: "2024-03-05",
        status: "completed"
    }
]

const scheduledVideos = [
    {
        id: 4,
        title: "Sleep Disorders Explained",
        scheduledFor: "2024-03-25",
        status: "scheduled",
        progress: 75
    },
    {
        id: 5,
        title: "Natural Sleep Remedies",
        scheduledFor: "2024-03-30",
        status: "scheduled",
        progress: 45
    }
]
</script>

<template>
    <div class=" mx-auto px-4 sm:px-6 lg:px-8 py-8 bg-gradient-to-b from-indigo-50 to-white">
        <!-- Back Button and Create Button -->
        <div class="flex justify-between items-center mb-6">
            <Link 
                href="/channels" 
                class="inline-flex items-center text-indigo-600 hover:text-indigo-700 group transition-colors"
            >
                <svg 
                    class="w-5 h-5 mr-2 transform group-hover:-translate-x-1 transition-transform" 
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Volver a Canales
            </Link>

            <Link
                :href="route('channels.videos.create', channel.id)"
                class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-indigo-600 text-white hover:bg-indigo-700 transition-colors"
            >
                <svg 
                    class="w-6 h-6" 
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </Link>
        </div>

        <!-- Channel Header -->
        <div class="mb-12 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 mb-4">
                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ channel.name }}</h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                {{ channel.description || 'Ayudándote a lograr un mejor descanso a través de la ciencia y la atención plena' }}
            </p>
            <div class="flex items-center justify-center mt-4 space-x-8">
                <div class="text-center">
                    <span class="block text-2xl font-semibold text-indigo-600">{{ completedVideos.length }}</span>
                    <span class="text-gray-500">Publicados</span>
                </div>
                <div class="text-center">
                    <span class="block text-2xl font-semibold text-indigo-600">{{ scheduledVideos.length }}</span>
                    <span class="text-gray-500">Próximos</span>
                </div>
            </div>
        </div>

        <!-- Completed Videos Section -->
        <div class="mb-12">
            <h2 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center">
                <svg class="w-6 h-6 text-indigo-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Contenido Publicado
            </h2>
            <div class="space-y-4">
                <Link 
                    v-for="video in props.channel.videos" 
                    :key="video.id"
                    :href="route('channels.videos.show', [channel.id, video.id])"
                    class="block bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition-all duration-200 border border-indigo-50"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center">
                                <span class="text-sm text-gray-500">{{ video.duration }}</span>
                            </div>
                            <h3 class="font-medium text-gray-900 mt-2 text-lg">{{ video.title }}</h3>
                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                <span>{{ video.publishedAt }}</span>
                            </div>
                        </div>
                        <div class="ml-4 p-2 text-indigo-600 hover:bg-indigo-50 rounded-full transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </div>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Scheduled Videos Section -->
        <div>
            <h2 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center">
                <svg class="w-6 h-6 text-indigo-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Contenido Próximo
            </h2>
            <div class="space-y-4">
                <Link 
                    v-for="video in scheduledVideos" 
                    :key="video.id"
                    :href="route('channels.videos.show', [channel.id, video.id])"
                    class="block bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition-all duration-200 border border-indigo-50"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center">
                                <span class="text-sm font-medium text-indigo-600">
                                    Coming {{ video.scheduledFor }}
                                </span>
                            </div>
                            <h3 class="font-medium text-gray-900 mt-2 text-lg">{{ video.title }}</h3>
                            <div class="mt-3">
                                <div class="flex items-center justify-between text-sm text-gray-500 mb-1">
                                    <span>Production Progress</span>
                                    <span class="font-medium text-indigo-600">{{ video.progress }}%</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-1.5">
                                    <div class="bg-indigo-500 h-1.5 rounded-full transition-all duration-500" 
                                         :style="{ width: `${video.progress}%` }"></div>
                                </div>
                            </div>
                        </div>
                        <div class="ml-4 p-2 text-indigo-600 hover:bg-indigo-50 rounded-full transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>