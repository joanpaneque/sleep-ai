<script setup>
import { Link, usePoll } from '@inertiajs/vue3'

const props = defineProps({
    channel: Object
})


usePoll(2000);
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

// Function to get status color
const getStatusColor = (status) => {
    switch (status) {
        case 'completed':
            return 'text-green-600 bg-green-100'
        case 'processing':
        case 'generating_script':
        case 'generating_content':
        case 'rendering':
            return 'text-yellow-600 bg-yellow-100'
        case 'failed':
            return 'text-red-600 bg-red-100'
        case 'pending':
            return 'text-gray-600 bg-gray-100'
        case 'stopped':
            return 'text-orange-600 bg-orange-100'
        default:
            return 'text-gray-600 bg-gray-100'
    }
}

// Function to get progress bar color
const getProgressColor = (status) => {
    switch (status) {
        case 'completed':
            return 'bg-green-500'
        case 'processing':
        case 'generating_script':
        case 'generating_content':
        case 'rendering':
            return 'bg-yellow-500'
        case 'failed':
            return 'bg-red-500'
        case 'stopped':
            return 'bg-orange-500'
        default:
            return 'bg-indigo-500'
    }
}

// Function to get Spanish status text
const getStatusText = (status) => {
    switch (status) {
        case 'pending':
            return 'En cola'
        case 'generating_script':
            return 'Generando guión'
        case 'generating_content':
            return 'Generando texto, audio e imágenes'
        case 'rendering':
            return 'Renderizando video'
        case 'completed':
            return 'Completado'
        case 'failed':
            return 'Error'
        case 'stopped':
            return 'Proceso parado'
        default:
            return status
    }
}

// Function to format date nicely in Spanish
const formatDate = (dateString) => {
    if (!dateString) return ''

    const date = new Date(dateString)
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
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
            </div>
        </div>

        <!-- Completed Videos Section -->
        <div class="mb-12">
            <h2 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center">
                <svg class="w-6 h-6 text-indigo-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Videos
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
                            <div class="flex items-center space-x-3">
                                <span class="text-sm text-gray-500">ID: {{ video.id }}</span>
                                <span class="text-sm text-gray-500">{{ video.duration }}</span>
                                <span :class="getStatusColor(video.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                                    {{ getStatusText(video.status) }}
                                </span>
                            </div>
                            <h3 class="font-medium text-gray-900 mt-2 text-lg">{{ video.title }}</h3>
                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ formatDate(video.created_at) }}</span>
                            </div>

                            <!-- Progress Bar or Processing Text -->
                            <div class="mt-3">
                                <div v-if="video.status_progress === null && ['generating_script', 'generating_content', 'rendering'].includes(video.status)" class="flex items-center text-sm text-gray-500">
                                    <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Procesando...
                                </div>
                                <div v-else-if="video.status_progress !== null" class="w-full">
                                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                                        <span>Progreso</span>
                                        <span>{{ video.status_progress }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div
                                            :class="getProgressColor(video.status)"
                                            class="h-2 rounded-full transition-all duration-500 ease-out"
                                            :style="{ width: video.status_progress + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Small Video Preview -->
                        <div v-if="video.url" class="ml-4 flex-shrink-0">
                            <video
                                :src="video.url"
                                class="w-96 h-64 rounded-lg object-cover border border-gray-200"
                                controls
                                preload="metadata"
                            >
                                Tu navegador no soporta el elemento de video.
                            </video>
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

    </div>
</template>
