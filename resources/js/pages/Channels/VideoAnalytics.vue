<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '../Layout/AppLayout.vue'

const props = defineProps({
    channel: {
        type: Object,
        required: true
    },
    videoId: {
        type: String,
        required: true
    }
})

const loading = ref(false)
const videoDetails = ref(null)
const videoAnalytics = ref(null)
const videoComments = ref([])
const commentsLoading = ref(false)

// Fetch video details and analytics
const fetchVideoData = async () => {
    loading.value = true
    try {
        // Get video details
        const detailsResponse = await fetch(`/channels/${props.channel.id}/videos/${props.videoId}/details`)
        const detailsData = await detailsResponse.json()

        if (detailsData.success) {
            videoDetails.value = detailsData.data
        }

        // Get video analytics
        const analyticsResponse = await fetch(`/channels/${props.channel.id}/videos/${props.videoId}/engagement`)
        const analyticsData = await analyticsResponse.json()

        if (analyticsData.success) {
            videoAnalytics.value = analyticsData.data
        }

    } catch (error) {
        console.error('Error:', error)
    } finally {
        loading.value = false
    }
}

// Fetch video comments
const fetchVideoComments = async () => {
    commentsLoading.value = true
    try {
        const response = await fetch(`/channels/${props.channel.id}/videos/${props.videoId}/comments?max_results=50&order=relevance`)
        const data = await response.json()

        if (data.success) {
            videoComments.value = data.data.items || []
        }
    } catch (error) {
        console.error('Error:', error)
    } finally {
        commentsLoading.value = false
    }
}

// Format numbers
const formatNumber = (num) => {
    if (num >= 1000000000) return (num / 1000000000).toFixed(1) + 'B'
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
    return num?.toString() || '0'
}

// Format date
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
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

// Get engagement color
const getEngagementColor = (rate) => {
    if (rate >= 5) return 'text-green-400'
    if (rate >= 2) return 'text-blue-400'
    if (rate >= 1) return 'text-yellow-400'
    if (rate >= 0.5) return 'text-orange-400'
    return 'text-red-400'
}

onMounted(() => {
    fetchVideoData()
    fetchVideoComments()
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
                            @click="router.get(route('channels.analytics', channel.id))"
                            class="p-2 hover:bg-gray-700/50 rounded-xl transition-all duration-200"
                        >
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <div>
                            <h1 class="text-3xl font-bold text-white mb-2">Analíticas del Video</h1>
                            <p class="text-gray-400">Análisis detallado de rendimiento</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-8xl mx-auto px-6 lg:px-8 py-8">
            <!-- Loading -->
            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
            </div>

            <!-- Video Details -->
            <div v-else-if="videoDetails" class="space-y-8">
                <!-- Video Header -->
                <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:space-x-6">
                        <!-- Thumbnail -->
                        <div class="flex-shrink-0 mb-4 lg:mb-0">
                            <img v-if="videoDetails.snippet?.thumbnails?.high?.url"
                                 :src="videoDetails.snippet.thumbnails.high.url"
                                 :alt="videoDetails.snippet?.title"
                                 class="w-full lg:w-80 h-60 lg:h-48 object-cover rounded-xl">
                        </div>

                        <!-- Video Info -->
                        <div class="flex-1 space-y-4">
                            <h2 class="text-2xl font-bold text-white">{{ videoDetails.snippet?.title }}</h2>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <p class="text-gray-400 text-sm">Publicado</p>
                                    <p class="text-white font-semibold">{{ formatDate(videoDetails.snippet?.publishedAt) }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-sm">Duración</p>
                                    <p class="text-white font-semibold">{{ videoDetails.contentDetails?.duration }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-sm">Estado</p>
                                    <p class="text-white font-semibold capitalize">{{ videoDetails.status?.privacyStatus }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-sm">Categoría</p>
                                    <p class="text-white font-semibold">{{ videoDetails.snippet?.categoryId }}</p>
                                </div>
                            </div>

                            <!-- Description -->
                            <div v-if="videoDetails.snippet?.description">
                                <p class="text-gray-400 text-sm mb-2">Descripción</p>
                                <p class="text-gray-300 text-sm line-clamp-3">{{ videoDetails.snippet.description }}</p>
                            </div>

                            <!-- Tags -->
                            <div v-if="videoDetails.snippet?.tags && videoDetails.snippet.tags.length > 0">
                                <p class="text-gray-400 text-sm mb-2">Tags</p>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="tag in videoDetails.snippet.tags.slice(0, 10)" :key="tag"
                                          class="bg-gray-700/50 text-gray-300 text-xs px-2 py-1 rounded">
                                        {{ tag }}
                                    </span>
                                    <span v-if="videoDetails.snippet.tags.length > 10"
                                          class="text-gray-400 text-xs">
                                        +{{ videoDetails.snippet.tags.length - 10 }} más
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Overview -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white mb-1">{{ formatNumber(videoDetails.statistics?.viewCount) }}</p>
                        <p class="text-gray-400 text-sm">Visualizaciones</p>
                    </div>

                    <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-red-500 to-red-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white mb-1">{{ formatNumber(videoDetails.statistics?.likeCount) }}</p>
                        <p class="text-gray-400 text-sm">Likes</p>
                    </div>

                    <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white mb-1">{{ formatNumber(videoDetails.statistics?.commentCount) }}</p>
                        <p class="text-gray-400 text-sm">Comentarios</p>
                    </div>

                    <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6-4l2 2 4-4" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-2xl font-bold" :class="getPerformanceColor(videoAnalytics?.performance_score || 0)">
                            {{ videoAnalytics?.performance_score || 0 }}/100
                        </p>
                        <p class="text-gray-400 text-sm">Performance Score</p>
                    </div>
                </div>

                <!-- Engagement Analytics -->
                <div v-if="videoAnalytics" class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                    <h3 class="text-xl font-bold text-white mb-6">Métricas de Engagement</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="text-center">
                            <p class="text-3xl font-bold" :class="getEngagementColor(videoAnalytics.engagement_metrics?.engagement_rate || 0)">
                                {{ (videoAnalytics.engagement_metrics?.engagement_rate || 0).toFixed(3) }}%
                            </p>
                            <p class="text-gray-400 text-sm mt-1">Engagement Rate</p>
                            <p class="text-gray-500 text-xs mt-1">(Likes + Comentarios) / Vistas</p>
                        </div>

                        <div class="text-center">
                            <p class="text-3xl font-bold text-blue-400">
                                {{ (videoAnalytics.engagement_metrics?.like_to_view_ratio || 0).toFixed(3) }}%
                            </p>
                            <p class="text-gray-400 text-sm mt-1">Like Rate</p>
                            <p class="text-gray-500 text-xs mt-1">Likes / Vistas</p>
                        </div>

                        <div class="text-center">
                            <p class="text-3xl font-bold text-purple-400">
                                {{ (videoAnalytics.engagement_metrics?.comment_to_view_ratio || 0).toFixed(3) }}%
                            </p>
                            <p class="text-gray-400 text-sm mt-1">Comment Rate</p>
                            <p class="text-gray-500 text-xs mt-1">Comentarios / Vistas</p>
                        </div>

                        <div class="text-center">
                            <p class="text-3xl font-bold text-yellow-400">
                                {{ (videoAnalytics.engagement_metrics?.like_to_comment_ratio || 0).toFixed(2) }}
                            </p>
                            <p class="text-gray-400 text-sm mt-1">Like/Comment Ratio</p>
                            <p class="text-gray-500 text-xs mt-1">Likes por comentario</p>
                        </div>
                    </div>

                    <!-- Performance Analysis -->
                    <div class="mt-8 p-4 bg-gray-700/30 rounded-xl">
                        <h4 class="text-lg font-semibold text-white mb-3">Análisis de Rendimiento</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <p class="text-gray-400">Nivel de Performance</p>
                                <p :class="getPerformanceColor(videoAnalytics.performance_score)" class="font-semibold text-lg">
                                    {{ getPerformanceLevel(videoAnalytics.performance_score) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-400">Fecha de Análisis</p>
                                <p class="text-white">{{ formatDate(videoAnalytics.analysis_date) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-400">Score Total</p>
                                <p :class="getPerformanceColor(videoAnalytics.performance_score)" class="font-bold text-lg">
                                    {{ videoAnalytics.performance_score }}/100
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comments Analysis -->
                <div v-if="videoAnalytics?.comment_analysis" class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                    <h3 class="text-xl font-bold text-white mb-6">Análisis de Comentarios</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="text-center bg-gray-700/30 rounded-xl p-4">
                            <p class="text-2xl font-bold text-white">{{ videoAnalytics.comment_analysis.total_comments }}</p>
                            <p class="text-gray-400 text-sm">Total Comentarios</p>
                        </div>
                        <div class="text-center bg-gray-700/30 rounded-xl p-4">
                            <p class="text-2xl font-bold text-white">{{ videoAnalytics.comment_analysis.recent_comments }}</p>
                            <p class="text-gray-400 text-sm">Comentarios Recientes</p>
                        </div>
                        <div class="text-center bg-gray-700/30 rounded-xl p-4">
                            <p class="text-2xl font-bold text-white">{{ videoAnalytics.comment_analysis.avg_comment_length }}</p>
                            <p class="text-gray-400 text-sm">Longitud Promedio</p>
                        </div>
                    </div>

                    <!-- Top Commenters -->
                    <div v-if="Object.keys(videoAnalytics.comment_analysis.top_commenters).length > 0">
                        <h4 class="text-lg font-semibold text-white mb-3">Top Comentaristas</h4>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="(count, commenter) in videoAnalytics.comment_analysis.top_commenters" :key="commenter"
                                  class="bg-gradient-to-r from-gray-600/50 to-gray-700/50 text-white text-sm px-3 py-1 rounded-full border border-gray-600/30">
                                {{ commenter }} ({{ count }} comentarios)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Recent Comments -->
                <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-white">Comentarios Recientes</h3>
                        <button @click="fetchVideoComments"
                                :disabled="commentsLoading"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm rounded-lg transition-colors">
                            {{ commentsLoading ? 'Cargando...' : 'Actualizar' }}
                        </button>
                    </div>

                    <div v-if="commentsLoading" class="flex justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                    </div>

                    <div v-else-if="videoComments.length > 0" class="space-y-4 max-h-96 overflow-y-auto">
                        <div v-for="comment in videoComments.slice(0, 10)" :key="comment.id"
                             class="bg-gray-700/30 rounded-xl p-4">
                            <div class="flex items-start space-x-3">
                                <img v-if="comment.snippet?.topLevelComment?.snippet?.authorProfileImageUrl"
                                     :src="comment.snippet.topLevelComment.snippet.authorProfileImageUrl"
                                     :alt="comment.snippet?.topLevelComment?.snippet?.authorDisplayName"
                                     class="w-8 h-8 rounded-full">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-1">
                                        <p class="text-white font-medium text-sm">
                                            {{ comment.snippet?.topLevelComment?.snippet?.authorDisplayName }}
                                        </p>
                                        <p class="text-gray-400 text-xs">
                                            {{ formatDate(comment.snippet?.topLevelComment?.snippet?.publishedAt) }}
                                        </p>
                                        <span v-if="comment.snippet?.topLevelComment?.snippet?.likeCount > 0"
                                              class="text-red-400 text-xs">
                                            ❤️ {{ comment.snippet.topLevelComment.snippet.likeCount }}
                                        </span>
                                    </div>
                                    <p class="text-gray-300 text-sm">
                                        {{ comment.snippet?.topLevelComment?.snippet?.textDisplay }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-8">
                        <p class="text-gray-400">No hay comentarios disponibles</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
