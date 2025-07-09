<script setup>
import { useForm, usePoll } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/pages/Layout/AppLayout.vue'

const props = defineProps({
    video_stats: {
        type: Object,
        required: true
    },
    storage_stats: {
        type: Object,
        required: true
    },
    channel_stats: {
        type: Object,
        required: true
    },
    rendering_queue: {
        type: Array,
        required: true
    }
})

usePoll(2000);

const form = useForm({})

const logout = () => {
    form.post('/logout')
}

// Datos dummy para el dashboard (excepto videos y storage que vienen del backend)
const stats = {
    moneySpent: '€0.00',
    moneyGenerated: '€0.00',
    profit: '€0.00',
    profitPercentage: 0,
    totalDuration: '124h 32m'
}

const topChannels = [
    { name: 'Meditación Nocturna', videos: 24, size: '384 MB', color: 'blue' },
    { name: 'Historias Relajantes', videos: 18, size: '288 MB', color: 'purple' },
    { name: 'Sonidos Naturales', videos: 15, size: '240 MB', color: 'green' },
]

const getStatusText = (status) => {
    switch (status) {
        case 'generating_script':
            return 'Generando guión'
        case 'generating_content':
            return 'Generando contenido'
        case 'rendering':
            return 'Renderizando'
        default:
            return 'Procesando'
    }
}

const getStatusColor = (status) => {
    switch (status) {
        case 'generating_script':
            return 'from-blue-500 to-blue-400'
        case 'generating_content':
            return 'from-yellow-500 to-yellow-400'
        case 'rendering':
            return 'from-green-500 to-green-400'
        default:
            return 'from-purple-500 to-purple-400'
    }
}

const formatTime = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getActivityColor = (type) => {
    switch (type) {
        case 'video_completed':
            return 'text-green-400'
        case 'channel_created':
            return 'text-blue-400'
        case 'video_failed':
            return 'text-red-400'
        case 'storage_warning':
            return 'text-yellow-400'
        default:
            return 'text-gray-400'
    }
}

const getChannelColor = (color) => {
    switch (color) {
        case 'blue':
            return 'bg-blue-500/20 text-blue-400'
        case 'purple':
            return 'bg-purple-500/20 text-purple-400'
        case 'green':
            return 'bg-green-500/20 text-green-400'
        default:
            return 'bg-gray-500/20 text-gray-400'
    }
}
</script>

<template>
    <AppLayout title="Dashboard">
        <!-- Header -->
        <div class="bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 border-b border-gray-700">
            <div class="max-w-8xl mx-auto px-6 lg:px-8 py-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">Dashboard</h1>
                            <p class="text-gray-400">Resumen general del sistema</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <Link
                            href="/channels"
                            class="group/channels px-4 py-2 bg-gray-700/50 hover:bg-blue-500/20 border border-gray-600/50 hover:border-blue-500/50 rounded-lg transition-all duration-200 shadow-sm hover:shadow-lg hover:shadow-blue-500/20 hover:scale-105"
                        >
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-gray-300 group-hover/channels:text-blue-400 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span class="text-sm font-medium text-gray-300 group-hover/channels:text-blue-400 transition-colors duration-200">
                                    Ver Canales
                                </span>
                            </div>
                        </Link>
                        <button
                            @click="logout"
                            class="group/logout px-4 py-2 bg-gray-700/50 hover:bg-red-500/20 border border-gray-600/50 hover:border-red-500/50 rounded-lg transition-all duration-200 shadow-sm hover:shadow-lg hover:shadow-red-500/20 hover:scale-105"
                        >
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-gray-300 group-hover/logout:text-red-400 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span class="text-sm font-medium text-gray-300 group-hover/logout:text-red-400 transition-colors duration-200">
                                    Cerrar Sesión
                                </span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-8xl mx-auto px-6 lg:px-8 py-8">
            <!-- Bento Grid Layout -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-6">

                <!-- Videos Totales - Grande -->
                <div class="lg:col-span-2 bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-8 shadow-xl hover:shadow-2xl transition-all duration-300">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 bg-gradient-to-r from-blue-500/20 to-blue-600/20 rounded-2xl flex items-center justify-center">
                                <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Videos Generados</h3>
                                <p class="text-sm text-gray-400">Contenido total producido</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-5xl font-bold text-white mb-1">{{ video_stats.total_videos }}</div>
                            <div class="text-sm text-blue-400 font-medium">videos</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div class="bg-gray-700/30 rounded-xl p-4 border border-gray-600/30">
                            <div class="flex items-center space-x-3 mb-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-emerald-500/20 to-emerald-600/20 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 1.79 4 4 4h8c2.21 0 4-1.79 4-4V7M4 7c0-2.21 1.79-4 4-4h8c2.21 0 4 1.79 4 4M4 7h16" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-400">Total Generado</div>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-white">{{ video_stats.total_storage_formatted }}</div>
                        </div>

                        <div class="bg-gray-700/30 rounded-xl p-4 border border-gray-600/30">
                            <div class="flex items-center space-x-3 mb-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-purple-500/20 to-purple-600/20 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-400">Promedio por Video</div>
                                </div>
                            </div>
                            <div class="text-2xl font-bold text-white">{{ video_stats.average_video_size_formatted }}</div>
                        </div>
                    </div>
                </div>

                <!-- Finanzas - Vertical -->
                <div class="lg:row-span-2 bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 shadow-xl hover:shadow-2xl transition-all duration-300">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-emerald-500/20 to-emerald-600/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                            </svg>
                        </div>
                        <div class="text-xs text-emerald-400 font-medium">+{{ stats.profitPercentage }}%</div>
                    </div>
                    <div class="space-y-6">
                        <div>
                            <div class="text-sm text-gray-400 mb-1">Ingresos</div>
                            <div class="text-2xl font-bold text-white">{{ stats.moneyGenerated }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-400 mb-1">Gastos</div>
                            <div class="text-xl font-semibold text-red-400">{{ stats.moneySpent }}</div>
                        </div>
                        <div class="pt-4 border-t border-gray-700/50">
                            <div class="text-sm text-gray-400 mb-1">Beneficio Neto</div>
                            <div class="text-2xl font-bold text-emerald-400">{{ stats.profit }}</div>
                        </div>
                    </div>
                </div>

                <!-- Almacenamiento -->
                <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 shadow-xl hover:shadow-2xl transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-purple-500/20 to-purple-600/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 1.79 4 4 4h8c2.21 0 4-1.79 4-4V7M4 7c0-2.21 1.79-4 4-4h8c2.21 0 4 1.79 4 4M4 7h16" />
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="text-3xl font-bold text-white">{{ storage_stats.used_percentage }}%</div>
                        <div class="text-sm text-gray-400">Almacenamiento Usado</div>
                        <div class="text-xs text-gray-500 mb-2">{{ storage_stats.used_space_formatted }} / {{ storage_stats.total_space_formatted }}</div>
                        <div class="w-full bg-gray-700/60 rounded-full h-3">
                            <div
                                class="h-3 rounded-full transition-all duration-1000 ease-out"
                                :class="{
                                    'bg-gradient-to-r from-emerald-500 to-emerald-400': storage_stats.used_percentage < 50,
                                    'bg-gradient-to-r from-yellow-500 to-yellow-400': storage_stats.used_percentage >= 50 && storage_stats.used_percentage < 80,
                                    'bg-gradient-to-r from-red-500 to-red-400': storage_stats.used_percentage >= 80
                                }"
                                :style="{ width: storage_stats.used_percentage + '%' }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Canales -->
                <Link href="/channels" class="block bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 shadow-xl hover:shadow-2xl transition-all duration-300 hover:border-indigo-500/50">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-indigo-500/20 to-indigo-600/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="text-3xl font-bold text-white">{{ channel_stats.total_channels }}</div>
                        <div class="text-sm text-gray-400">Canales Activos</div>
                        <div class="text-xs text-indigo-400 flex items-center space-x-1 group-hover:underline">
                            <span>Ver todos</span>
                            <svg class="w-3 h-3 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                        </div>
                    </div>
                </Link>

                <!-- Top Canales -->
                <div class="lg:col-span-2 bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 shadow-xl hover:shadow-2xl transition-all duration-300">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-white">Top Canales</h3>
                        <Link href="/channels" class="text-xs text-gray-400 hover:text-blue-400 transition-colors">Ver todos</Link>
                    </div>
                    <div class="space-y-4">
                        <div v-for="channel in topChannels" :key="channel.name" class="flex items-center justify-between p-3 bg-gray-700/20 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div :class="['w-8 h-8 rounded-lg flex items-center justify-center', getChannelColor(channel.color)]">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-white">{{ channel.name }}</div>
                                    <div class="text-xs text-gray-400">{{ channel.videos }} videos</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-medium text-white">{{ channel.size }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cola de Renderizado - Ancho completo -->
                <div class="lg:col-span-4 xl:col-span-6 bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-8 shadow-xl hover:shadow-2xl transition-all duration-300">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-yellow-500/20 to-orange-600/20 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Cola de Renderizado</h3>
                                <p class="text-gray-400">Videos en proceso de generación</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-400">En cola</div>
                            <div class="text-2xl font-bold text-yellow-400">{{ rendering_queue.length }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div
                            v-for="video in rendering_queue"
                            :key="video.id"
                            class="p-4 bg-gray-700/30 hover:bg-gray-700/50 rounded-xl transition-all duration-200 border border-gray-600/30"
                        >
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-400">ID: {{ video.id }}</span>
                                    <div class="flex items-center space-x-1">
                                        <div class="w-2 h-2 rounded-full bg-yellow-400 animate-pulse"></div>
                                        <span class="text-xs text-gray-400">{{ formatTime(video.created_at) }}</span>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="font-medium text-white text-sm mb-1 line-clamp-2">{{ video.title }}</h4>
                                    <p class="text-xs text-gray-400">{{ video.channel.name }}</p>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-gray-300">{{ getStatusText(video.status) }}</span>
                                        <span class="text-white font-medium">{{ video.status_progress || 0 }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-600/50 rounded-full h-1.5">
                                        <div
                                            :class="['h-1.5 rounded-full transition-all duration-500 ease-out bg-gradient-to-r', getStatusColor(video.status)]"
                                            :style="{ width: (video.status_progress || 0) + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="rendering_queue.length === 0" class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-white mb-1">No hay videos en cola</h3>
                        <p class="text-gray-400 text-sm">Todos los videos han sido procesados</p>
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

/* Enhanced focus states for accessibility */
button:focus-visible,
a:focus-visible {
    outline: 2px solid rgb(59, 130, 246);
    outline-offset: 2px;
}
</style>
