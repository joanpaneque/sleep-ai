<script setup>
import { computed } from 'vue'
import { router, usePoll } from '@inertiajs/vue3'
import AppLayout from '@/pages/Layout/AppLayout.vue'
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js'
import { Line } from 'vue-chartjs'

// Register Chart.js components
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
)

// Props
const props = defineProps({
    storage_stats: {
        type: Object,
        required: true
    },
    storage_breakdown: {
        type: Array,
        required: true
    },
    historical_video_stats: {
        type: Object,
        required: true
    }
});

usePoll(2000);

// Chart data
const chartData = computed(() => {
    if (!props.historical_video_stats.chart_data || props.historical_video_stats.chart_data.length === 0) {
        return null
    }

    return {
        labels: props.historical_video_stats.chart_data.map(point => point.date_formatted || point.date),
        datasets: [
            {
                label: 'Almacenamiento (MB)',
                data: props.historical_video_stats.chart_data.map(point => point.total_size_mb || point.storage_mb),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0, // Straight lines
                pointRadius: 0,
                pointHoverRadius: 0,
            }
        ]
    }
})

// Chart options
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            backgroundColor: 'rgba(17, 24, 39, 0.95)',
            titleColor: 'rgb(243, 244, 246)',
            bodyColor: 'rgb(209, 213, 219)',
            borderColor: 'rgba(75, 85, 99, 0.5)',
            borderWidth: 1,
            cornerRadius: 8,
            displayColors: false,
            callbacks: {
                title: function(context) {
                    return `Fecha: ${context[0].label}`
                },
                label: function(context) {
                    return `Almacenamiento: ${context.parsed.y.toLocaleString()} MB`
                }
            }
        }
    },

    scales: {
        x: {
            grid: {
                color: 'rgba(75, 85, 99, 0.3)',
                drawBorder: false
            },
            ticks: {
                color: 'rgb(156, 163, 175)',
                maxTicksLimit: 8
            }
        },
        y: {
            grid: {
                color: 'rgba(75, 85, 99, 0.3)',
                drawBorder: false
            },
            ticks: {
                color: 'rgb(156, 163, 175)',
                callback: function(value) {
                    return value.toLocaleString() + ' MB'
                }
            },
            beginAtZero: true
        }
    },
    interaction: {
        intersect: false,
        mode: 'index'
    }
}

// Helper functions
const getColorClasses = (color) => {
    const colorMap = {
        'blue': 'from-blue-500 to-blue-600',
        'green': 'from-green-500 to-green-600',
        'yellow': 'from-yellow-500 to-yellow-600',
        'red': 'from-red-500 to-red-600',
        'purple': 'from-purple-500 to-purple-600',
        'pink': 'from-pink-500 to-pink-600',
        'indigo': 'from-indigo-500 to-indigo-600',
        'gray': 'from-gray-500 to-gray-600',
        'orange': 'from-orange-500 to-orange-600',
        'teal': 'from-teal-500 to-teal-600',
    }
    return colorMap[color] || 'from-gray-500 to-gray-600'
}

const getIconColorClasses = (color) => {
    const colorMap = {
        'blue': 'bg-blue-500/20 text-blue-400',
        'green': 'bg-green-500/20 text-green-400',
        'yellow': 'bg-yellow-500/20 text-yellow-400',
        'red': 'bg-red-500/20 text-red-400',
        'purple': 'bg-purple-500/20 text-purple-400',
        'pink': 'bg-pink-500/20 text-pink-400',
        'indigo': 'bg-indigo-500/20 text-indigo-400',
        'gray': 'bg-gray-500/20 text-gray-400',
        'orange': 'bg-orange-500/20 text-orange-400',
        'teal': 'bg-teal-500/20 text-teal-400',
    }
    return colorMap[color] || 'bg-gray-500/20 text-gray-400'
}

// Actions
const editChannel = (channelId) => {
    router.visit(`/channels/${channelId}/edit`)
}

const deleteChannelVideos = (channelId, channelName) => {
    if (confirm(`¿Estás seguro de que quieres eliminar todos los videos del canal "${channelName}"? Esta acción no se puede deshacer.`)) {
        router.delete(`/channels/${channelId}/videos`, {
            preserveScroll: true,
            onSuccess: () => {
                alert('Videos eliminados correctamente')
            },
            onError: () => {
                alert('Error al eliminar los videos')
            }
        })
    }
}
</script>

<template>
    <AppLayout title="Uso del Almacenamiento">
        <!-- Header -->
        <div class="bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 border-b border-gray-700">
            <div class="max-w-8xl mx-auto px-6 lg:px-8 py-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-white">Uso del Almacenamiento</h1>
                        <p class="mt-2 text-gray-400">Monitorea y gestiona el espacio de almacenamiento</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-400">Última actualización</div>
                        <div class="text-white font-medium">{{ new Date().toLocaleString() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-8xl mx-auto px-6 lg:px-8 py-8">
            <!-- Storage Overview -->
            <div class="mb-8">
                <!-- Main Storage Card -->
                <div>
                    <div class="bg-gradient-to-r from-gray-900/60 to-gray-800/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-8 shadow-2xl">
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center space-x-4">
                                <div class="w-16 h-16 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 1.79 4 4 4h8c2.21 0 4-1.79 4-4V7M4 7c0-2.21 1.79-4 4-4h8c2.21 0 4 1.79 4 4M4 7h16" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold text-white">Uso del Almacenamiento</h3>
                                    <p class="text-gray-400">Estado actual del sistema</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-4xl font-bold text-white mb-1">{{ storage_stats.used_percentage }}%</div>
                                <div class="text-sm text-gray-400">utilizado</div>
                            </div>
                        </div>

                        <!-- Progress Visualization -->
                        <div class="space-y-6">
                            <div class="relative">
                                <div class="flex justify-between text-sm text-gray-400 mb-3">
                                    <span>Espacio utilizado</span>
                                    <span>{{ storage_stats.used_space_formatted }} / {{ storage_stats.total_space_formatted }}</span>
                                </div>
                                <div class="relative w-full bg-gray-800/60 rounded-full h-4 overflow-hidden">
                                    <div class="absolute inset-0 bg-gradient-to-r from-gray-800/40 to-gray-700/40"></div>
                                    <div
                                        class="relative h-full rounded-full transition-all duration-1000 ease-out"
                                        :class="{
                                            'bg-gradient-to-r from-emerald-500 to-green-400': storage_stats.used_percentage < 50,
                                            'bg-gradient-to-r from-yellow-500 to-orange-400': storage_stats.used_percentage >= 50 && storage_stats.used_percentage < 80,
                                            'bg-gradient-to-r from-red-500 to-pink-500': storage_stats.used_percentage >= 80
                                        }"
                                        :style="{ width: storage_stats.used_percentage + '%' }"
                                    >
                                        <div class="absolute inset-0 bg-gradient-to-t from-white/20 to-transparent"></div>
                                    </div>
                                    <!-- Glow effect -->
                                    <div
                                        class="absolute top-0 h-full rounded-full blur-sm opacity-60 transition-all duration-1000"
                                        :class="{
                                            'bg-gradient-to-r from-emerald-400 to-green-300': storage_stats.used_percentage < 50,
                                            'bg-gradient-to-r from-yellow-400 to-orange-300': storage_stats.used_percentage >= 50 && storage_stats.used_percentage < 80,
                                            'bg-gradient-to-r from-red-400 to-pink-400': storage_stats.used_percentage >= 80
                                        }"
                                        :style="{ width: storage_stats.used_percentage + '%' }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Storage Stats -->
                            <div class="grid grid-cols-3 gap-4">
                                <div class="bg-gray-800/40 backdrop-blur-sm rounded-xl p-4 border border-gray-700/30 text-center">
                                    <div class="text-xl font-bold text-white mb-1">{{ storage_stats.used_space_formatted }}</div>
                                    <div class="text-xs text-gray-400">Usado</div>
                                </div>
                                <div class="bg-gray-800/40 backdrop-blur-sm rounded-xl p-4 border border-gray-700/30 text-center">
                                    <div class="text-xl font-bold text-white mb-1">{{ storage_stats.free_space_formatted }}</div>
                                    <div class="text-xs text-gray-400">Disponible</div>
                                </div>
                                <div class="bg-gray-800/40 backdrop-blur-sm rounded-xl p-4 border border-gray-700/30 text-center">
                                    <div class="text-xl font-bold text-white mb-1">{{ storage_stats.total_space_formatted }}</div>
                                    <div class="text-xs text-gray-400">Total</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Storage Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Breakdown by Channels -->
                <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 shadow-xl">
                    <h4 class="text-xl font-semibold text-white mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Canales
                    </h4>
                    <div class="space-y-4 max-h-96 overflow-y-auto overflow-x-visible">
                        <div v-for="item in storage_breakdown" :key="item.name" class="group p-4 bg-gray-700/30 hover:bg-gray-700/50 rounded-xl transition-all duration-200">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-3 flex-1 min-w-0">
                                    <div :class="['w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0', getIconColorClasses(item.color)]">
                                        <svg v-if="item.channel_id" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                        </svg>
                                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-white font-medium truncate">{{ item.name }}</div>
                                        <div v-if="item.description && item.channel_id" class="text-xs text-gray-400 mt-1 line-clamp-2">
                                            {{ item.description }}
                                        </div>
                                        <div class="text-sm text-gray-400 mt-1">
                                            {{ item.size_formatted }}
                                            <span v-if="item.channel_id" class="text-xs text-gray-500 ml-2">
                                                ID: {{ item.channel_id }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <!-- Progress Bar and Percentage Row -->
                                    <div class="flex items-center space-x-3 mb-2">
                                        <!-- Segmented Progress Bar with Tooltip -->
                                        <div class="relative group/progress">
                                            <div class="w-24 bg-gray-700 rounded-full h-2 relative overflow-hidden cursor-help">
                                                <!-- Channel Directory Segment -->
                                                <div
                                                    :class="['h-2 rounded-full bg-gradient-to-r absolute left-0', getColorClasses(item.color)]"
                                                    :style="{ width: storage_stats.used_space > 0 ? Math.min((item.channel_directory_size / storage_stats.used_space) * 100, 100) + '%' : '0%' }"
                                                ></div>
                                                <!-- Residuos Segment -->
                                                <div
                                                    class="h-2 bg-gradient-to-r from-gray-500 to-gray-600 absolute"
                                                    :style="{
                                                        left: storage_stats.used_space > 0 ? Math.min((item.channel_directory_size / storage_stats.used_space) * 100, 100) + '%' : '0%',
                                                        width: storage_stats.used_space > 0 ? Math.min((item.residuos_size / storage_stats.used_space) * 100, 100) + '%' : '0%'
                                                    }"
                                                ></div>
                                            </div>

                                            <!-- Tooltip -->
                                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 mt-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-xl opacity-0 group-hover/progress:opacity-100 transition-opacity duration-200 pointer-events-none z-[9999] whitespace-nowrap">
                                                <div class="space-y-1">
                                                    <div class="flex items-center space-x-2">
                                                        <div :class="['w-2 h-2 rounded-full', getColorClasses(item.color)]"></div>
                                                        <span>Carpeta: {{ item.channel_directory_formatted }}</span>
                                                    </div>
                                                    <div class="flex items-center space-x-2">
                                                        <div class="w-2 h-2 rounded-full bg-gray-500"></div>
                                                        <span>Residuos: {{ item.residuos_formatted }}</span>
                                                    </div>
                                                    <div class="border-t border-gray-700 pt-1 mt-1">
                                                        <span class="font-medium">Total: {{ item.size_formatted }}</span>
                                                    </div>
                                                </div>
                                                <!-- Tooltip Arrow -->
                                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-b-4 border-transparent border-b-gray-900"></div>
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-400 font-medium min-w-[2.5rem] text-right">
                                            {{ storage_stats.used_space > 0 ? Math.round((item.size / storage_stats.used_space) * 100) : 0 }}%
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div v-if="item.channel_id" class="flex justify-end space-x-2">
                                        <!-- Edit Channel Button -->
                                        <button
                                            @click="editChannel(item.channel_id)"
                                            class="group/edit p-2 bg-gray-700/50 hover:bg-blue-500/20 border border-gray-600/50 hover:border-blue-500/50 rounded-lg transition-all duration-200 shadow-sm hover:shadow-lg hover:shadow-blue-500/20 hover:scale-105"
                                            title="Editar canal"
                                        >
                                            <svg class="w-4 h-4 text-gray-300 group-hover/edit:text-blue-400 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <!-- Delete Channel Videos Button -->
                                        <button
                                            @click="deleteChannelVideos(item.channel_id, item.name)"
                                            class="group/delete px-3 py-2 bg-gray-700/50 hover:bg-red-500/20 border border-gray-600/50 hover:border-red-500/50 rounded-lg transition-all duration-200 shadow-sm hover:shadow-lg hover:shadow-red-500/20 hover:scale-105"
                                            title="Eliminar todos los videos del canal"
                                        >
                                            <div class="flex items-center space-x-1.5">
                                                <svg class="w-4 h-4 text-gray-300 group-hover/delete:text-red-400 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span class="text-xs font-medium text-gray-300 group-hover/delete:text-red-400 transition-colors duration-200">
                                                    Eliminar videos
                                                </span>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historical Video Statistics -->
                <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 shadow-xl">
                    <h4 class="text-xl font-semibold text-white mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Estadísticas Históricas de Videos
                    </h4>
                    <div class="space-y-4">
                        <!-- Total Historical Storage -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-gradient-to-r from-blue-500/10 to-blue-600/10 border border-blue-500/20 rounded-xl">
                                <div class="text-2xl font-bold text-blue-400">{{ historical_video_stats.total_historical_size_formatted }}</div>
                                <div class="text-xs text-gray-400 mt-1">Espacio total generado</div>
                            </div>
                            <div class="p-4 bg-gradient-to-r from-green-500/10 to-green-600/10 border border-green-500/20 rounded-xl">
                                <div class="text-2xl font-bold text-green-400">{{ historical_video_stats.total_completed_videos }}</div>
                                <div class="text-xs text-gray-400 mt-1">Videos completados</div>
                            </div>
                        </div>

                        <!-- Video Statistics -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-gray-700/20 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-green-500/20 text-green-400 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-white text-sm font-medium">Videos Activos</div>
                                        <div class="text-xs text-gray-400">{{ historical_video_stats.total_active_size_formatted }}</div>
                                    </div>
                                </div>
                                <div class="text-white font-bold">{{ historical_video_stats.total_active_videos }}</div>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-gray-700/20 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-red-500/20 text-red-400 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-white text-sm font-medium">Videos Eliminados</div>
                                        <div class="text-xs text-gray-400">{{ historical_video_stats.total_deleted_size_formatted }} liberados</div>
                                    </div>
                                </div>
                                <div class="text-white font-bold">{{ historical_video_stats.total_deleted_videos }}</div>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-gray-700/20 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-purple-500/20 text-purple-400 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-white text-sm font-medium">Promedio por Video</div>
                                        <div class="text-xs text-gray-400">{{ historical_video_stats.average_video_size_formatted }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Largest Video -->
                            <div v-if="historical_video_stats.largest_video" class="p-3 bg-gradient-to-r from-yellow-500/10 to-orange-500/10 border border-yellow-500/20 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-yellow-500/20 text-yellow-400 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-white text-sm font-medium">Video Más Grande</div>
                                        <div class="text-xs text-gray-400 truncate">{{ historical_video_stats.largest_video.title }}</div>
                                        <div class="text-xs text-yellow-400">{{ historical_video_stats.largest_video.size_formatted }} • {{ historical_video_stats.largest_video.channel_name }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfica de Evolución de Almacenamiento - Full Width -->
        <div class="w-full px-6 lg:px-8 pb-8">
            <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-8 backdrop-blur-sm">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold text-white">Evolución del Almacenamiento</h3>
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>

                <!-- Gráfica de líneas -->
                <div class="h-96 mb-6" v-if="chartData">
                    <Line
                        :data="chartData"
                        :options="chartOptions"
                        class="h-full w-full"
                    />
                </div>

                <!-- Mensaje si no hay datos -->
                <div v-else class="h-96 flex items-center justify-center">
                    <div class="text-center">
                        <svg class="w-16 h-16 text-gray-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <p class="text-gray-400">No hay datos históricos disponibles</p>
                    </div>
                </div>

                <!-- Información de la gráfica -->
                <div class="flex items-center justify-between text-sm text-gray-400 border-t border-gray-700/50 pt-4">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                            <span>Almacenamiento acumulado (MB)</span>
                        </div>
                    </div>
                    <div>
                        {{ props.historical_video_stats.chart_data?.length || 0 }} puntos de datos
                    </div>
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
