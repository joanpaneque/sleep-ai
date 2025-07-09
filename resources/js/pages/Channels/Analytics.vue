<script setup>
import { ref, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '../Layout/AppLayout.vue'

const props = defineProps({
    channel: {
        type: Object,
        required: true
    },
    success: String,
    error: String
})

const loading = ref(false)
const channelStats = ref(null)
const videos = ref([])

const statsLoading = ref(false)
const videosLoading = ref(false)

// Fetch channel analytics from database
const fetchChannelAnalytics = async () => {
    statsLoading.value = true
    try {
        const response = await fetch(`/channels/${props.channel.id}/dashboard-db`)
        const data = await response.json()

        if (data.success) {
            channelStats.value = data.data
        } else {
            console.error('Error fetching analytics:', data.message)
        }
    } catch (error) {
        console.error('Error:', error)
    } finally {
        statsLoading.value = false
    }
}

// Fetch channel videos
const fetchChannelVideos = async () => {
    videosLoading.value = true
    try {
        const response = await fetch(`/channels/${props.channel.id}/videos-db?limit=50&order_by=published_at&order_direction=desc`)
        const data = await response.json()

        if (data.success) {
            videos.value = data.data.videos
        }
    } catch (error) {
        console.error('Error:', error)
    } finally {
        videosLoading.value = false
    }
}

// Open video analytics modal
const openVideoAnalytics = async (video) => {
    // Navigate to dedicated video analytics page
    router.get(route('channels.videos.analytics-page', [props.channel.id, video.id]))
}

// Trigger manual sync
const triggerSync = async () => {
    loading.value = true
    try {
        const response = await fetch(`/channels/${props.channel.id}/sync`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })

        const data = await response.json()

        if (data.success) {
            // Refresh data after sync
            await fetchChannelAnalytics()
            await fetchChannelVideos()
        }
    } catch (error) {
        console.error('Error:', error)
    } finally {
        loading.value = false
    }
}

// Format numbers
const formatNumber = (num) => {
    if (num >= 1000000000) return (num / 1000000000).toFixed(1) + 'B'
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
    return num?.toString() || '0'
}

// Format duration
const formatDuration = (seconds) => {
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    const secs = seconds % 60

    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
    } else {
        return `${minutes}:${secs.toString().padStart(2, '0')}`
    }
}

// Format date
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

// Performance color
const getPerformanceColor = (score) => {
    if (score >= 80) return 'text-green-400'
    if (score >= 60) return 'text-blue-400'
    if (score >= 40) return 'text-yellow-400'
    if (score >= 20) return 'text-orange-400'
    return 'text-red-400'
}

// Performance level
const getPerformanceLevel = (score) => {
    if (score >= 80) return 'Excelente'
    if (score >= 60) return 'Bueno'
    if (score >= 40) return 'Regular'
    if (score >= 20) return 'Bajo'
    return 'Muy Bajo'
}

onMounted(() => {
    fetchChannelAnalytics()
    fetchChannelVideos()
})
</script>

<template>
    <AppLayout>
        <!-- Page Header -->
        <div class="sticky top-0 z-30 bg-gray-950/80 backdrop-blur-xl border-b border-gray-800/50">
            <div class="max-w-8xl mx-auto px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button
                            @click="router.get(route('channels.index'))"
                            class="p-2 hover:bg-gray-700/50 rounded-xl transition-all duration-200"
                        >
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <div>
                            <h1 class="text-3xl font-bold text-white mb-2">Analíticas - {{ channel.name }}</h1>
                            <p class="text-gray-400">Dashboard completo de rendimiento del canal</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button
                            @click="triggerSync"
                            :disabled="loading"
                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 disabled:opacity-50 text-white text-sm font-medium rounded-xl transition-all duration-300 shadow-lg shadow-green-600/25"
                        >
                            <svg v-if="loading" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ loading ? 'Sincronizando...' : 'Sincronizar' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-8xl mx-auto px-6 lg:px-8 py-8">
            <!-- Success/Error Messages -->
            <div v-if="success" class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-xl">
                <p class="text-green-400">{{ success }}</p>
            </div>
            <div v-if="error" class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-xl">
                <p class="text-red-400">{{ error }}</p>
            </div>

            <!-- Channel Statistics -->
            <div v-if="channelStats" class="mb-8">
                <h2 class="text-2xl font-bold text-white mb-6">Estadísticas del Canal</h2>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-red-500 to-red-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1H5C3.89 1 3 1.89 3 3V19A2 2 0 0 0 5 21H19A2 2 0 0 0 21 19V9Z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white mb-1">{{ formatNumber(channelStats.statistics.subscriber_count) }}</p>
                        <p class="text-gray-400 text-sm">Suscriptores</p>
                    </div>

                    <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white mb-1">{{ channelStats.statistics.video_count }}</p>
                        <p class="text-gray-400 text-sm">Videos</p>
                    </div>

                    <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white mb-1">{{ formatNumber(channelStats.statistics.view_count) }}</p>
                        <p class="text-gray-400 text-sm">Visualizaciones</p>
                    </div>

                    <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6-4l2 2 4-4" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white mb-1">{{ formatNumber(channelStats.metrics.avg_views_per_video) }}</p>
                        <p class="text-gray-400 text-sm">Promedio/Video</p>
                    </div>
                </div>

                <!-- Top Videos -->
                <div v-if="channelStats.top_videos && channelStats.top_videos.length > 0" class="mb-8">
                    <h3 class="text-xl font-semibold text-white mb-4">Videos Más Vistos</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="video in channelStats.top_videos" :key="video.id"
                             class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-xl p-4 hover:border-gray-600/50 transition-all duration-300">
                            <div class="flex items-start space-x-3">
                                <img v-if="video.thumbnail"
                                     :src="video.thumbnail"
                                     :alt="video.title"
                                     class="w-16 h-12 object-cover rounded-lg">
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-white font-medium text-sm line-clamp-2 mb-1">{{ video.title }}</h4>
                                    <p class="text-gray-400 text-xs">{{ formatNumber(video.view_count) }} vistas</p>
                                    <div class="flex items-center mt-1">
                                        <span :class="getPerformanceColor(video.performance_score)" class="text-xs font-medium">
                                            {{ video.performance_score }}/100
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Videos List -->
            <div>
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-white">Videos del Canal</h2>
                    <div class="text-sm text-gray-400">{{ videos.length }} videos</div>
                </div>

                <!-- Videos Grid -->
                <div v-if="!videosLoading && videos.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div v-for="video in videos" :key="video.id"
                         @click="openVideoAnalytics(video)"
                         class="group bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 hover:border-gray-600/50 rounded-2xl overflow-hidden transition-all duration-300 cursor-pointer hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-900/20">

                        <!-- Thumbnail -->
                        <div class="relative">
                            <img v-if="video.thumbnail"
                                 :src="video.thumbnail"
                                 :alt="video.title"
                                 class="w-full h-48 object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-gray-900/60 to-transparent"></div>

                            <!-- Duration -->
                            <div class="absolute bottom-2 right-2 bg-gray-900/80 text-white text-xs px-2 py-1 rounded">
                                {{ video.duration }}
                            </div>

                            <!-- Performance Score -->
                            <div class="absolute top-2 left-2 bg-gray-900/80 text-white text-xs px-2 py-1 rounded">
                                <span :class="getPerformanceColor(video.metrics?.performance_score || 0)">
                                    {{ video.metrics?.performance_score || 0 }}/100
                                </span>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-4">
                            <h3 class="text-white font-semibold text-sm line-clamp-2 mb-2 group-hover:text-blue-300 transition-colors duration-300">
                                {{ video.title }}
                            </h3>

                            <div class="space-y-2 text-xs text-gray-400">
                                <div class="flex items-center justify-between">
                                    <span>{{ formatDate(video.published_at) }}</span>
                                    <span :class="getPerformanceColor(video.metrics?.performance_score || 0)">
                                        {{ getPerformanceLevel(video.metrics?.performance_score || 0) }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span>{{ formatNumber(video.statistics?.view_count || 0) }} vistas</span>
                                    <span>{{ formatNumber(video.statistics?.like_count || 0) }} likes</span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span>{{ formatNumber(video.statistics?.comment_count || 0) }} comentarios</span>
                                    <span>{{ (Number(video.metrics?.engagement_rate) || 0).toFixed(2) }}% engagement</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div v-else-if="videosLoading" class="flex justify-center items-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                </div>

                <!-- No videos -->
                <div v-else class="text-center py-12">
                    <p class="text-gray-400">No hay videos sincronizados para este canal</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
