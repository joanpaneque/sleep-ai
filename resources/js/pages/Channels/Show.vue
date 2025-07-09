<script setup>
import { Link, usePoll, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/pages/Layout/AppLayout.vue'

const props = defineProps({
    channel: Object
})

const editChannel = () => {
    router.get(route('channels.edit', props.channel.id))
}

const softDeleteVideo = (video) => {
    router.post(route('channels.videos.soft-delete', video.id), {}, {
        preserveScroll: true
    })
}

const queueVideo = (video) => {
    router.post(route('channels.videos.queue', video.id), {}, {
        preserveScroll: true
    })
}

// Modal state
const showDeleteModal = ref(false)
const videoToDelete = ref(null)

const confirmDelete = (video) => {
    videoToDelete.value = video
    showDeleteModal.value = true
}

const handleDelete = () => {
    if (videoToDelete.value) {
        softDeleteVideo(videoToDelete.value)
        showDeleteModal.value = false
        videoToDelete.value = null
    }
}

const cancelDelete = () => {
    showDeleteModal.value = false
    videoToDelete.value = null
}

usePoll(2000);

// Function to get status color
const getStatusColor = (status) => {
    switch (status) {
        case 'completed':
            return 'text-green-400 bg-green-500/20 border-green-500/30'
        case 'processing':
        case 'generating_script':
        case 'generating_content':
        case 'rendering':
            return 'text-yellow-400 bg-yellow-500/20 border-yellow-500/30'
        case 'failed':
            return 'text-red-400 bg-red-500/20 border-red-500/30'
        case 'pending':
            return 'text-gray-400 bg-gray-500/20 border-gray-500/30'
        case 'stopped':
            return 'text-orange-400 bg-orange-500/20 border-orange-500/30'
        default:
            return 'text-gray-400 bg-gray-500/20 border-gray-500/30'
    }
}

// Function to get progress bar color
const getProgressColor = (status) => {
    switch (status) {
        case 'completed':
            return 'from-green-500 to-green-400'
        case 'processing':
        case 'generating_script':
        case 'generating_content':
        case 'rendering':
            return 'from-yellow-500 to-yellow-400'
        case 'failed':
            return 'from-red-500 to-red-400'
        case 'stopped':
            return 'from-orange-500 to-orange-400'
        default:
            return 'from-blue-500 to-blue-400'
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
            return 'Generando contenido'
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

// Computed properties for statistics
const activeVideos = props.channel.videos.filter(v => !v.is_deleted)
const deletedVideos = props.channel.videos.filter(v => v.is_deleted)
const completedVideos = activeVideos.filter(v => v.status === 'completed')
const processingVideos = activeVideos.filter(v => ['processing', 'generating_script', 'generating_content', 'rendering'].includes(v.status))
</script>

<template>
    <AppLayout :title="channel.name">
        <!-- Header -->
        <div class="bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 border-b border-gray-700">
            <div class="max-w-8xl mx-auto px-6 lg:px-8 py-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">{{ channel.name }}</h1>
                            <p class="text-gray-400">{{ channel.description || 'Canal de videos' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <!-- Edit Channel Button -->
                        <button
                            @click="editChannel"
                            class="group/edit px-4 py-2 bg-gray-700/50 hover:bg-emerald-500/20 border border-gray-600/50 hover:border-emerald-500/50 rounded-lg transition-all duration-200 shadow-sm hover:shadow-lg hover:shadow-emerald-500/20 hover:scale-105"
                            title="Editar canal"
                        >
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-gray-300 group-hover/edit:text-emerald-400 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span class="text-sm font-medium text-gray-300 group-hover/edit:text-emerald-400 transition-colors duration-200">
                                    Editar Canal
                                </span>
                            </div>
                        </button>

                        <!-- Create Video Button -->
                        <Link
                            :href="route('channels.videos.create', channel.id)"
                            class="group/create px-4 py-2 bg-gray-700/50 hover:bg-indigo-500/20 border border-gray-600/50 hover:border-indigo-500/50 rounded-lg transition-all duration-200 shadow-sm hover:shadow-lg hover:shadow-indigo-500/20 hover:scale-105"
                            title="Crear nuevo video"
                        >
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-gray-300 group-hover/create:text-indigo-400 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="text-sm font-medium text-gray-300 group-hover/create:text-indigo-400 transition-colors duration-200">
                                    Nuevo Video
                                </span>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-8xl mx-auto px-6 lg:px-8 py-8">
            <!-- Channel Statistics -->
            <div class="mb-8">
                <div class="bg-gradient-to-r from-gray-900/60 to-gray-800/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-8 shadow-2xl">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-2xl font-bold text-white mb-2">Estadísticas del Canal</h3>
                            <p class="text-gray-400">Resumen de actividad y contenido</p>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-400">Última actualización</div>
                            <div class="text-white font-medium">{{ new Date().toLocaleString() }}</div>
                        </div>
                    </div>

                    <!-- Statistics Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-gradient-to-r from-blue-500/10 to-blue-600/10 border border-blue-500/20 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-blue-400">{{ activeVideos.length }}</div>
                            <div class="text-xs text-gray-400 mt-1">Videos Activos</div>
                        </div>
                        <div class="bg-gradient-to-r from-green-500/10 to-green-600/10 border border-green-500/20 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-green-400">{{ completedVideos.length }}</div>
                            <div class="text-xs text-gray-400 mt-1">Completados</div>
                        </div>
                        <div class="bg-gradient-to-r from-yellow-500/10 to-yellow-600/10 border border-yellow-500/20 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-yellow-400">{{ processingVideos.length }}</div>
                            <div class="text-xs text-gray-400 mt-1">Procesando</div>
                        </div>
                        <div class="bg-gradient-to-r from-red-500/10 to-red-600/10 border border-red-500/20 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-red-400">{{ deletedVideos.length }}</div>
                            <div class="text-xs text-gray-400 mt-1">Eliminados</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Videos Section -->
            <div class="mb-8" v-if="activeVideos.length > 0">
                <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 shadow-xl">
                    <h2 class="text-2xl font-semibold text-white mb-6 flex items-center">
                        <svg class="w-6 h-6 text-purple-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Videos Activos
                    </h2>
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        <div
                            v-for="video in activeVideos"
                            :key="video.id"
                            class="group p-6 bg-gray-700/30 hover:bg-gray-700/50 rounded-xl transition-all duration-200 border border-gray-600/30 hover:border-gray-500/50"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-3 mb-3">
                                        <span class="text-sm text-gray-400">ID: {{ video.id }}</span>
                                        <span class="text-sm text-gray-400">{{ video.duration }}</span>
                                        <span :class="['px-3 py-1 rounded-full text-xs font-medium border', getStatusColor(video.status)]">
                                            {{ getStatusText(video.status) }}
                                        </span>
                                    </div>
                                    <h3 class="font-semibold text-white text-lg mb-2">{{ video.title }}</h3>
                                    <div class="flex items-center text-sm text-gray-400 mb-3">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>{{ formatDate(video.created_at) }}</span>
                                    </div>

                                    <!-- Progress Bar or Processing Text -->
                                    <div class="mb-4">
                                        <div v-if="video.status_progress === null && ['generating_script', 'generating_content', 'rendering'].includes(video.status)" class="flex items-center text-sm text-gray-400">
                                            <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Procesando...
                                        </div>
                                        <div v-else-if="video.status_progress !== null" class="w-full">
                                            <div class="flex justify-between text-xs text-gray-400 mb-2">
                                                <span>Progreso</span>
                                                <span>{{ video.status_progress }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-700 rounded-full h-2">
                                                <div
                                                    :class="['h-2 rounded-full transition-all duration-500 ease-out bg-gradient-to-r', getProgressColor(video.status)]"
                                                    :style="{ width: video.status_progress + '%' }"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Video Actions -->
                                <div class="ml-6 flex-shrink-0 flex flex-wrap gap-2">
                                    <a
                                        v-if="video.url"
                                        :href="video.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="group/play p-2 bg-gray-700/50 hover:bg-indigo-500/20 border border-gray-600/50 hover:border-indigo-500/50 rounded-lg transition-all duration-200 shadow-sm hover:shadow-lg hover:shadow-indigo-500/20 hover:scale-105"
                                        @click.stop
                                        title="Ver video"
                                    >
                                        <svg class="w-4 h-4 text-gray-300 group-hover/play:text-indigo-400 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </a>
                                    <a
                                        v-if="video.url"
                                        :href="`https://sleepai.online/storage/channels/${channel.id}/${video.id}/timestamps.txt`"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="group/time p-2 bg-gray-700/50 hover:bg-emerald-500/20 border border-gray-600/50 hover:border-emerald-500/50 rounded-lg transition-all duration-200 shadow-sm hover:shadow-lg hover:shadow-emerald-500/20 hover:scale-105"
                                        @click.stop
                                        title="Ver timestamps"
                                    >
                                        <svg class="w-4 h-4 text-gray-300 group-hover/time:text-emerald-400 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </a>
                                    <a
                                        v-if="video.thumbnail"
                                        :href="video.thumbnail"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="group/thumb p-2 bg-gray-700/50 hover:bg-purple-500/20 border border-gray-600/50 hover:border-purple-500/50 rounded-lg transition-all duration-200 shadow-sm hover:shadow-lg hover:shadow-purple-500/20 hover:scale-105"
                                        @click.stop
                                        title="Ver thumbnail"
                                    >
                                        <svg class="w-4 h-4 text-gray-300 group-hover/thumb:text-purple-400 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </a>
                                    <button
                                        v-if="video.url || video.status === 'failed' || video.status === 'stopped'"
                                        @click.stop="confirmDelete(video)"
                                        class="group/delete p-2 bg-gray-700/50 hover:bg-red-500/20 border border-gray-600/50 hover:border-red-500/50 rounded-lg transition-all duration-200 shadow-sm hover:shadow-lg hover:shadow-red-500/20 hover:scale-105"
                                        title="Eliminar video"
                                    >
                                        <svg class="w-4 h-4 text-gray-300 group-hover/delete:text-red-400 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deleted Videos Section -->
            <div v-if="deletedVideos.length > 0" class="mb-8">
                <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 shadow-xl">
                    <h2 class="text-2xl font-semibold text-white mb-6 flex items-center">
                        <svg class="w-6 h-6 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Videos Eliminados
                    </h2>
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        <div
                            v-for="video in deletedVideos"
                            :key="video.id"
                            class="group p-6 bg-gray-700/20 hover:bg-gray-700/30 rounded-xl transition-all duration-200 border border-gray-600/20 opacity-75"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-3 mb-3">
                                        <span class="text-sm text-gray-500">ID: {{ video.id }}</span>
                                        <span class="text-sm text-gray-500">{{ video.duration }}</span>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium text-red-400 bg-red-500/20 border border-red-500/30">
                                            Eliminado
                                        </span>
                                    </div>
                                    <h3 class="font-semibold text-gray-300 text-lg mb-2">{{ video.title }}</h3>
                                    <div class="flex items-center text-sm text-gray-500">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>{{ formatDate(video.created_at) }}</span>
                                    </div>
                                </div>

                                <div class="ml-6 flex-shrink-0">
                                    <button
                                        @click.stop="queueVideo(video)"
                                        class="group/reprocess px-3 py-2 bg-gray-700/50 hover:bg-blue-500/20 border border-gray-600/50 hover:border-blue-500/50 rounded-lg transition-all duration-200 shadow-sm hover:shadow-lg hover:shadow-blue-500/20 hover:scale-105"
                                        title="Volver a procesar"
                                    >
                                        <div class="flex items-center space-x-1.5">
                                            <svg class="w-4 h-4 text-gray-300 group-hover/reprocess:text-blue-400 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            <span class="text-xs font-medium text-gray-300 group-hover/reprocess:text-blue-400 transition-colors duration-200">
                                                Volver a procesar
                                            </span>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="activeVideos.length === 0 && deletedVideos.length === 0" class="text-center py-12">
                <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-12 shadow-xl">
                    <svg class="w-16 h-16 text-gray-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <h3 class="text-xl font-semibold text-white mb-2">No hay videos en este canal</h3>
                    <p class="text-gray-400 mb-6">Comienza creando tu primer video</p>
                    <Link
                        :href="route('channels.videos.create', channel.id)"
                        class="group/create-first px-6 py-4 bg-gray-700/50 hover:bg-indigo-500/20 border border-gray-600/50 hover:border-indigo-500/50 rounded-xl transition-all duration-200 shadow-sm hover:shadow-lg hover:shadow-indigo-500/20 hover:scale-105"
                    >
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-gray-300 group-hover/create-first:text-indigo-400 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="text-lg font-semibold text-gray-300 group-hover/create-first:text-indigo-400 transition-colors duration-200">
                                Crear Primer Video
                            </span>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 border border-gray-700 rounded-2xl p-8 max-w-md w-full mx-4 shadow-2xl">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white">Confirmar Eliminación</h3>
                </div>
                <p class="text-gray-300 mb-6">
                    ¿Estás seguro de que quieres eliminar el video "<strong class="text-white">{{ videoToDelete?.title }}</strong>"?
                    <br><br>
                    <span class="text-red-400 font-medium">Se borrarán todos los archivos de video asociados. Esta acción no se puede deshacer.</span>
                </p>
                <div class="flex justify-end space-x-3">
                    <button
                        @click="cancelDelete"
                        class="group/cancel px-4 py-2 bg-gray-700/50 hover:bg-gray-600/50 border border-gray-600/50 hover:border-gray-500/50 rounded-lg transition-all duration-200 shadow-sm hover:shadow-lg"
                    >
                        <span class="text-sm font-medium text-gray-300 group-hover/cancel:text-white transition-colors duration-200">Cancelar</span>
                    </button>
                    <button
                        @click="handleDelete"
                        class="group/confirm px-4 py-2 bg-gray-700/50 hover:bg-red-500/20 border border-gray-600/50 hover:border-red-500/50 rounded-lg transition-all duration-200 shadow-sm hover:shadow-lg hover:shadow-red-500/20 hover:scale-105"
                    >
                        <span class="text-sm font-medium text-gray-300 group-hover/confirm:text-red-400 transition-colors duration-200">Eliminar Video</span>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom scrollbar for better aesthetics */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: rgba(31, 41, 55, 0.5);
}

::-webkit-scrollbar-thumb {
    background: rgba(107, 114, 128, 0.5);
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(107, 114, 128, 0.8);
}

/* Enhanced focus states for accessibility */
button:focus-visible {
    outline: 2px solid rgb(59, 130, 246);
    outline-offset: 2px;
}
</style>
