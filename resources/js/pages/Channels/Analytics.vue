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
const analyticsReports = ref([])
const videoAnalytics = ref([])
const geographicData = ref([])
const deviceData = ref([])
const trafficSourceData = ref([])
const demographicData = ref([])
const topVideosAnalytics = ref([])
const revenueSummary = ref(null)

const statsLoading = ref(false)
const videosLoading = ref(false)
const analyticsLoading = ref(false)

const selectedDateRange = ref('30') // 7, 30, 90 days
const activeTab = ref('overview') // overview, geographic, devices, traffic, demographics, videos

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

// Fetch YouTube Analytics data
const fetchYouTubeAnalytics = async () => {
    analyticsLoading.value = true
    try {
        // Use fixed date range that matches available data
        const endDate = '2025-07-11'
        const startDate = '2025-06-11'
        
        console.log('Fetching analytics for date range:', startDate, 'to', endDate)
        
        // Fetch all analytics data
        const [dailyResponse, geoResponse, deviceResponse, trafficResponse, demoResponse, videoResponse] = await Promise.all([
            fetch(`/api/channels/${props.channel.id}/analytics/daily?start_date=${startDate}&end_date=${endDate}`),
            fetch(`/api/channels/${props.channel.id}/analytics/geographic?start_date=${startDate}&end_date=${endDate}`),
            fetch(`/api/channels/${props.channel.id}/analytics/devices?start_date=${startDate}&end_date=${endDate}`),
            fetch(`/api/channels/${props.channel.id}/analytics/traffic-sources?start_date=${startDate}&end_date=${endDate}`),
            fetch(`/api/channels/${props.channel.id}/analytics/demographics?start_date=${startDate}&end_date=${endDate}`),
            fetch(`/api/channels/${props.channel.id}/analytics/top-videos?start_date=${startDate}&end_date=${endDate}`)
        ])

        const [daily, geo, device, traffic, demo, videoData] = await Promise.all([
            dailyResponse.json(),
            geoResponse.json(),
            deviceResponse.json(),
            trafficResponse.json(),
            demoResponse.json(),
            videoResponse.json()
        ])

        console.log('Daily analytics response:', daily)
        console.log('Revenue summary:', daily.summary)

        analyticsReports.value = daily.data || []
        revenueSummary.value = daily.summary || null
        geographicData.value = geo.data || []
        deviceData.value = device.data || []
        trafficSourceData.value = traffic.data || []
        demographicData.value = demo.data || []
        topVideosAnalytics.value = videoData.data || []

    } catch (error) {
        console.error('Error fetching YouTube Analytics:', error)
    } finally {
        analyticsLoading.value = false
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

// Computed analytics summaries
const analyticsSummary = computed(() => {
    if (!analyticsReports.value.length) return null
    
    return {
        totalViews: analyticsReports.value.reduce((sum, report) => sum + (report.views || 0), 0),
        totalWatchTime: analyticsReports.value.reduce((sum, report) => sum + (report.estimated_minutes_watched || 0), 0),
        totalLikes: analyticsReports.value.reduce((sum, report) => sum + (report.likes || 0), 0),
        totalComments: analyticsReports.value.reduce((sum, report) => sum + (report.comments || 0), 0),
        totalShares: analyticsReports.value.reduce((sum, report) => sum + (report.shares || 0), 0),
        subscribersGained: analyticsReports.value.reduce((sum, report) => sum + (report.subscribers_gained || 0), 0),
        subscribersLost: analyticsReports.value.reduce((sum, report) => sum + (report.subscribers_lost || 0), 0),
        avgViewDuration: analyticsReports.value.reduce((sum, report) => sum + (report.average_view_duration || 0), 0) / analyticsReports.value.length,
        avgViewPercentage: analyticsReports.value.reduce((sum, report) => sum + (report.average_view_percentage || 0), 0) / analyticsReports.value.length,
        totalRevenue: revenueSummary.value?.total_revenue || 0,
        totalAdRevenue: revenueSummary.value?.total_ad_revenue || 0,
        totalGrossRevenue: revenueSummary.value?.total_gross_revenue || 0,
        avgCpm: revenueSummary.value?.avg_cpm || 0,
        totalMonetizedPlaybacks: revenueSummary.value?.total_monetized_playbacks || 0,
        totalAdImpressions: revenueSummary.value?.total_ad_impressions || 0,
        engagementRate: 0
    }
})

// Calculate engagement rate
const engagementRate = computed(() => {
    if (!analyticsSummary.value) return 0
    const { totalViews, totalLikes, totalComments } = analyticsSummary.value
    return totalViews > 0 ? ((totalLikes + totalComments) / totalViews * 100) : 0
})

// Net subscribers
const netSubscribers = computed(() => {
    if (!analyticsSummary.value) return 0
    return analyticsSummary.value.subscribersGained - analyticsSummary.value.subscribersLost
})

// Open video analytics modal
const openVideoAnalytics = async (video) => {
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
            await Promise.all([
                fetchChannelAnalytics(),
                fetchChannelVideos(),
                fetchYouTubeAnalytics()
            ])
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

// Format time in hours
const formatHours = (minutes) => {
    const hours = Math.floor(minutes / 60)
    const mins = minutes % 60
    return hours > 0 ? `${hours}h ${mins}m` : `${mins}m`
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

// Watch date range changes
const onDateRangeChange = () => {
    fetchYouTubeAnalytics()
}

onMounted(() => {
    fetchChannelAnalytics()
    fetchChannelVideos()
    fetchYouTubeAnalytics()
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
                            <h1 class="text-3xl font-bold text-white mb-2">YouTube Analytics - {{ channel.name }}</h1>
                            <p class="text-gray-400">Dashboard completo con datos avanzados de YouTube Analytics API</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Date Range Selector -->
                        <select 
                            v-model="selectedDateRange" 
                            @change="onDateRangeChange"
                            class="bg-gray-800 border border-gray-700 text-white text-sm rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="7">Últimos 7 días</option>
                            <option value="30">Últimos 30 días</option>
                            <option value="90">Últimos 90 días</option>
                        </select>
                        
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

            <!-- Navigation Tabs -->
            <div class="mb-8">
                <nav class="flex space-x-8">
                    <button 
                        @click="activeTab = 'overview'" 
                        :class="activeTab === 'overview' ? 'border-blue-500 text-blue-400' : 'border-transparent text-gray-400 hover:text-white'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                    >
                        Resumen General
                    </button>
                    <button 
                        @click="activeTab = 'geographic'" 
                        :class="activeTab === 'geographic' ? 'border-blue-500 text-blue-400' : 'border-transparent text-gray-400 hover:text-white'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                    >
                        Geografía
                    </button>
                    <button 
                        @click="activeTab = 'devices'" 
                        :class="activeTab === 'devices' ? 'border-blue-500 text-blue-400' : 'border-transparent text-gray-400 hover:text-white'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                    >
                        Dispositivos
                    </button>
                    <button 
                        @click="activeTab = 'traffic'" 
                        :class="activeTab === 'traffic' ? 'border-blue-500 text-blue-400' : 'border-transparent text-gray-400 hover:text-white'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                    >
                        Fuentes de Tráfico
                    </button>
                    <button 
                        @click="activeTab = 'demographics'" 
                        :class="activeTab === 'demographics' ? 'border-blue-500 text-blue-400' : 'border-transparent text-gray-400 hover:text-white'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                    >
                        Demografía
                    </button>
                    <button 
                        @click="activeTab = 'videos'" 
                        :class="activeTab === 'videos' ? 'border-blue-500 text-blue-400' : 'border-transparent text-gray-400 hover:text-white'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                    >
                        Videos
                    </button>
                </nav>
            </div>

            <!-- Overview Tab -->
            <div v-if="activeTab === 'overview'" class="space-y-8">
                <!-- Main Analytics Summary -->
                <div v-if="analyticsSummary" class="mb-8">
                    <h2 class="text-2xl font-bold text-white mb-6">Resumen de Analytics (Últimos {{ selectedDateRange }} días)</h2>

                    <!-- Primary Metrics -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="bg-gradient-to-br from-blue-600/20 to-blue-700/10 border border-blue-500/30 rounded-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </div>
                            </div>
                            <p class="text-3xl font-bold text-white mb-1">{{ formatNumber(analyticsSummary.totalViews) }}</p>
                            <p class="text-blue-300 text-sm">Visualizaciones Totales</p>
                        </div>

                        <div class="bg-gradient-to-br from-green-600/20 to-green-700/10 border border-green-500/30 rounded-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <p class="text-3xl font-bold text-white mb-1">{{ formatHours(analyticsSummary.totalWatchTime) }}</p>
                            <p class="text-green-300 text-sm">Tiempo de Visualización</p>
                        </div>

                        <div class="bg-gradient-to-br from-purple-600/20 to-purple-700/10 border border-purple-500/30 rounded-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                </div>
                            </div>
                            <p class="text-3xl font-bold text-white mb-1">{{ engagementRate.toFixed(2) }}%</p>
                            <p class="text-purple-300 text-sm">Tasa de Engagement</p>
                        </div>

                        <div class="bg-gradient-to-br from-orange-600/20 to-orange-700/10 border border-orange-500/30 rounded-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                            </div>
                            <p class="text-3xl font-bold text-white mb-1">
                                <span :class="netSubscribers >= 0 ? 'text-green-400' : 'text-red-400'">
                                    {{ netSubscribers >= 0 ? '+' : '' }}{{ formatNumber(netSubscribers) }}
                                </span>
                            </p>
                            <p class="text-orange-300 text-sm">Suscriptores Netos</p>
                        </div>
                    </div>

                    <!-- Secondary Metrics -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                        <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-xl p-4">
                            <p class="text-2xl font-bold text-white mb-1">{{ formatNumber(analyticsSummary.totalLikes) }}</p>
                            <p class="text-gray-400 text-sm">Likes Totales</p>
                        </div>

                        <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-xl p-4">
                            <p class="text-2xl font-bold text-white mb-1">{{ formatNumber(analyticsSummary.totalComments) }}</p>
                            <p class="text-gray-400 text-sm">Comentarios</p>
                        </div>

                        <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-xl p-4">
                            <p class="text-2xl font-bold text-white mb-1">{{ formatNumber(analyticsSummary.totalShares) }}</p>
                            <p class="text-gray-400 text-sm">Compartidos</p>
                        </div>

                        <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-xl p-4">
                            <p class="text-2xl font-bold text-white mb-1">{{ analyticsSummary.avgViewDuration.toFixed(1) }}s</p>
                            <p class="text-gray-400 text-sm">Duración Promedio</p>
                        </div>

                        <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-xl p-4">
                            <p class="text-2xl font-bold text-white mb-1">{{ analyticsSummary.avgViewPercentage.toFixed(1) }}%</p>
                            <p class="text-gray-400 text-sm">% Visualización</p>
                        </div>
                    </div>

                    <!-- Revenue Section (if available) -->
                    <div v-if="analyticsSummary.totalRevenue > 0 || analyticsSummary.totalAdRevenue > 0" class="bg-gradient-to-br from-yellow-600/20 to-yellow-700/10 border border-yellow-500/30 rounded-xl p-6 mb-8">
                        <h3 class="text-xl font-semibold text-white mb-6 flex items-center">
                            <svg class="w-6 h-6 text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                            </svg>
                            Ingresos y Monetización
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-yellow-400">${{ (analyticsSummary.totalRevenue || 0).toFixed(2) }}</p>
                                <p class="text-yellow-300 text-sm">Ingresos Estimados</p>
                            </div>
                            <div class="text-center">
                                <p class="text-3xl font-bold text-green-400">${{ (analyticsSummary.totalAdRevenue || 0).toFixed(2) }}</p>
                                <p class="text-green-300 text-sm">Ingresos por Anuncios</p>
                            </div>
                            <div class="text-center">
                                <p class="text-3xl font-bold text-blue-400">${{ (analyticsSummary.avgCpm || 0).toFixed(2) }}</p>
                                <p class="text-blue-300 text-sm">CPM Promedio</p>
                            </div>
                            <div class="text-center">
                                <p class="text-3xl font-bold text-purple-400">{{ formatNumber(analyticsSummary.totalMonetizedPlaybacks || 0) }}</p>
                                <p class="text-purple-300 text-sm">Reproducciones Monetizadas</p>
                            </div>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <p class="text-yellow-300 text-sm">Datos de junio-julio 2025</p>
                            <p class="text-gray-400 text-xs mt-1">{{ formatNumber(analyticsSummary.totalAdImpressions || 0) }} impresiones de anuncios</p>
                        </div>
                    </div>

                    <!-- No Revenue Message -->
                    <div v-else class="bg-gradient-to-br from-gray-600/20 to-gray-700/10 border border-gray-500/30 rounded-xl p-6 mb-8">
                        <h3 class="text-xl font-semibold text-white mb-4 flex items-center">
                            <svg class="w-6 h-6 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Datos de Monetización
                        </h3>
                        <div class="text-center py-4">
                            <p class="text-gray-300 mb-2">No hay datos de ingresos disponibles</p>
                            <p class="text-gray-400 text-sm">Esto puede deberse a:</p>
                            <ul class="text-gray-400 text-sm mt-2 space-y-1">
                                <li>• El canal no está monetizado</li>
                                <li>• Se requieren permisos adicionales de YouTube Analytics</li>
                                <li>• Los datos de ingresos están restringidos</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Daily Trend Chart -->
                <div v-if="analyticsReports.length > 0" class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                    <h3 class="text-xl font-semibold text-white mb-4">Tendencia Diaria</h3>
                    <div class="space-y-3">
                        <div v-for="report in analyticsReports.slice(0, 10)" :key="report.id" class="flex items-center justify-between py-2 border-b border-gray-700/50 last:border-b-0">
                            <div class="text-gray-300 text-sm">{{ formatDate(report.report_date) }}</div>
                            <div class="flex items-center space-x-6 text-sm">
                                <span class="text-blue-300">{{ formatNumber(report.views) }} vistas</span>
                                <span class="text-green-300">{{ formatHours(report.estimated_minutes_watched) }}</span>
                                <span class="text-purple-300">{{ report.likes || 0 }} likes</span>
                                <span class="text-orange-300">{{ report.comments || 0 }} comentarios</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Geographic Tab -->
            <div v-if="activeTab === 'geographic'" class="space-y-8">
                <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                    <h3 class="text-2xl font-semibold text-white mb-6 flex items-center">
                        <svg class="w-6 h-6 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Distribución Geográfica
                    </h3>
                    
                    <div v-if="geographicData.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="country in geographicData" :key="country.id" 
                             class="bg-gray-700/30 rounded-xl p-4 border border-gray-600/30">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-white font-semibold">{{ country.dimension_value }}</h4>
                                <span class="text-blue-300 text-sm">{{ formatNumber(country.views) }} vistas</span>
                            </div>
                            <div class="space-y-1 text-sm text-gray-400">
                                <div class="flex justify-between">
                                    <span>Tiempo de visualización:</span>
                                    <span class="text-green-300">{{ formatHours(country.estimated_minutes_watched) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Suscriptores ganados:</span>
                                    <span class="text-orange-300">{{ country.subscribers_gained || 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else class="text-center py-8">
                        <p class="text-gray-400">No hay datos geográficos disponibles</p>
                    </div>
                </div>
            </div>

            <!-- Devices Tab -->
            <div v-if="activeTab === 'devices'" class="space-y-8">
                <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                    <h3 class="text-2xl font-semibold text-white mb-6 flex items-center">
                        <svg class="w-6 h-6 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Dispositivos y Plataformas
                    </h3>
                    
                    <div v-if="deviceData.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div v-for="device in deviceData" :key="device.id" 
                             class="bg-gray-700/30 rounded-xl p-6 border border-gray-600/30">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-white font-semibold text-lg">{{ device.dimension_value }}</h4>
                                <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Visualizaciones:</span>
                                    <span class="text-blue-300 font-semibold">{{ formatNumber(device.views) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Tiempo de visualización:</span>
                                    <span class="text-green-300 font-semibold">{{ formatHours(device.estimated_minutes_watched) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Duración promedio:</span>
                                    <span class="text-purple-300 font-semibold">{{ (device.average_view_duration || 0).toFixed(1) }}s</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else class="text-center py-8">
                        <p class="text-gray-400">No hay datos de dispositivos disponibles</p>
                    </div>
                </div>
            </div>

            <!-- Traffic Sources Tab -->
            <div v-if="activeTab === 'traffic'" class="space-y-8">
                <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                    <h3 class="text-2xl font-semibold text-white mb-6 flex items-center">
                        <svg class="w-6 h-6 text-purple-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        Fuentes de Tráfico
                    </h3>
                    
                    <div v-if="trafficSourceData.length > 0" class="space-y-4">
                        <div v-for="source in trafficSourceData" :key="source.id" 
                             class="bg-gray-700/30 rounded-xl p-6 border border-gray-600/30">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-white font-semibold text-lg">{{ source.dimension_value }}</h4>
                                <span class="bg-purple-500/20 text-purple-300 px-3 py-1 rounded-full text-sm">
                                    {{ ((source.views / analyticsSummary.totalViews) * 100).toFixed(1) }}% del tráfico
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-blue-300">{{ formatNumber(source.views) }}</p>
                                    <p class="text-gray-400 text-sm">Visualizaciones</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-green-300">{{ formatHours(source.estimated_minutes_watched) }}</p>
                                    <p class="text-gray-400 text-sm">Tiempo de visualización</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-purple-300">{{ (source.average_view_duration || 0).toFixed(1) }}s</p>
                                    <p class="text-gray-400 text-sm">Duración promedio</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else class="text-center py-8">
                        <p class="text-gray-400">No hay datos de fuentes de tráfico disponibles</p>
                    </div>
                </div>
            </div>

            <!-- Demographics Tab -->
            <div v-if="activeTab === 'demographics'" class="space-y-8">
                <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                    <h3 class="text-2xl font-semibold text-white mb-6 flex items-center">
                        <svg class="w-6 h-6 text-orange-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Demografía de la Audiencia
                    </h3>
                    
                    <div v-if="demographicData.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="demo in demographicData" :key="demo.id" 
                             class="bg-gray-700/30 rounded-xl p-4 border border-gray-600/30">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-white font-semibold">{{ demo.dimension_value }}</h4>
                                <span class="text-orange-300 text-sm font-medium">{{ (demo.viewer_percentage || 0).toFixed(1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-600 rounded-full h-2">
                                <div class="bg-gradient-to-r from-orange-500 to-orange-600 h-2 rounded-full" 
                                     :style="{ width: `${Math.min(demo.viewer_percentage || 0, 100)}%` }"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else class="text-center py-8">
                        <p class="text-gray-400">No hay datos demográficos disponibles</p>
                    </div>
                </div>
            </div>

            <!-- Videos Tab -->
            <div v-if="activeTab === 'videos'" class="space-y-8">
                <!-- Top Videos Analytics -->
                <div v-if="topVideosAnalytics.length > 0" class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6 mb-8">
                    <h3 class="text-2xl font-semibold text-white mb-6 flex items-center">
                        <svg class="w-6 h-6 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Videos Más Populares (Últimos {{ selectedDateRange }} días)
                    </h3>
                    
                    <div class="space-y-4">
                        <div v-for="(video, index) in topVideosAnalytics" :key="video.youtube_video_id" 
                             class="bg-gray-700/30 rounded-xl p-6 border border-gray-600/30 hover:border-gray-500/50 transition-all duration-300">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center space-x-4">
                                    <div class="w-8 h-8 bg-gradient-to-r from-red-500 to-red-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                        {{ index + 1 }}
                                    </div>
                                    <div>
                                        <h4 class="text-white font-semibold text-lg mb-1">Video ID: {{ video.youtube_video_id }}</h4>
                                        <p class="text-gray-400 text-sm">Datos del {{ formatDate(video.report_date) }}</p>
                                    </div>
                                </div>
                                <button 
                                    @click="openVideoAnalytics({ id: video.youtube_video_id })"
                                    class="bg-blue-500/20 hover:bg-blue-500/30 text-blue-300 px-4 py-2 rounded-lg text-sm transition-all duration-200"
                                >
                                    Ver Detalles
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-blue-300">{{ formatNumber(video.total_views) }}</p>
                                    <p class="text-gray-400 text-sm">Visualizaciones</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-green-300">{{ formatHours(video.total_watch_time) }}</p>
                                    <p class="text-gray-400 text-sm">Tiempo visualización</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-purple-300">{{ formatNumber(video.total_likes) }}</p>
                                    <p class="text-gray-400 text-sm">Likes</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-orange-300">{{ formatNumber(video.total_comments) }}</p>
                                    <p class="text-gray-400 text-sm">Comentarios</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- All Videos List -->
                <div>
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold text-white">Todos los Videos</h3>
                        <div class="text-sm text-gray-400">{{ videos.length }} videos</div>
                    </div>

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
                                <h4 class="text-white font-semibold text-sm line-clamp-2 mb-2 group-hover:text-blue-300 transition-colors duration-300">
                                    {{ video.title }}
                                </h4>

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

            <!-- Loading State -->
            <div v-if="analyticsLoading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                <p class="text-gray-400 ml-4">Cargando datos de YouTube Analytics...</p>
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
