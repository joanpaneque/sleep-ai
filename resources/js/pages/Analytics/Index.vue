<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '../Layout/AppLayout.vue'
import { Line, Bar } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler } from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler)

defineProps({
    success: String,
    error: String
})

const loading = ref(false)
const globalStats = ref(null)
const allVideos = ref([])
const channels = ref([])
const videosLoading = ref(false)
const statsLoading = ref(false)
const dailyStats = ref([])
const dailyStatsLoading = ref(false)
const selectedInterval = ref('5m') // Intervalo por defecto: 5 minutos
let updateInterval = null

// Función para redondear una fecha al intervalo más cercano
const roundToInterval = (date, intervalMinutes) => {
    const coeff = 1000 * 60 * intervalMinutes;
    return new Date(Math.floor(date.getTime() / coeff) * coeff);
}

// Función para agrupar datos por intervalo
const groupDataByInterval = (data) => {
    if (!data || data.length === 0) return [];
    
    const intervalMap = {
        '1m': { minutes: 1, period: 60 },      // 1 minuto, últimos 60 minutos
        '5m': { minutes: 5, period: 60 },      // 5 minutos, últimos 60 minutos
        '1h': { minutes: 60, period: 1440 },   // 1 hora, últimas 24 horas
        '6h': { minutes: 360, period: 40320 }, // 6 horas, últimos 28 días
        '1d': { minutes: 1440, period: 40320 } // 1 día, últimos 28 días
    };

    const { minutes, period } = intervalMap[selectedInterval.value];
    const now = new Date();
    const cutoffTime = new Date(now.getTime() - period * 60000); // Convertir periodo a milisegundos

    // Filtrar datos dentro del periodo
    const filteredData = data.filter(stat => new Date(stat.datetime) >= cutoffTime);
    
    const groupedData = new Map();

    // Ordenar datos por fecha
    const sortedData = [...filteredData].sort((a, b) => new Date(a.datetime) - new Date(b.datetime));

    sortedData.forEach(stat => {
        const date = new Date(stat.datetime);
        const roundedDate = roundToInterval(date, minutes);
        const key = roundedDate.getTime();

        // Guardar solo el último valor para cada intervalo
        groupedData.set(key, {
            datetime: roundedDate,
            total_views: stat.total_views
        });
    });

    // Convertir el Map a array y ordenar
    return Array.from(groupedData.values())
        .sort((a, b) => a.datetime - b.datetime);
}

// Fetch global analytics (all channels combined)
const fetchGlobalAnalytics = async () => {
    statsLoading.value = true
    try {
        const response = await fetch('/analytics/global-stats')
        const data = await response.json()

        if (data.success) {
            globalStats.value = data.data
        } else {
            console.error('Error fetching global analytics:', data.message)
        }
    } catch (error) {
        console.error('Error:', error)
    } finally {
        statsLoading.value = false
    }
}

// Fetch all videos from all channels
const fetchAllVideos = async () => {
    videosLoading.value = true
    try {
        const response = await fetch('/analytics/all-videos?limit=100&order_by=view_count&order_direction=desc')
        const data = await response.json()

        if (data.success) {
            allVideos.value = data.data.videos
            channels.value = data.data.channels
        }
    } catch (error) {
        console.error('Error:', error)
    } finally {
        videosLoading.value = false
    }
}

// Format datetime
const formatDateTime = (dateString) => {
    return new Date(dateString).toLocaleString('es-ES', {
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Format datetime with full info for tooltips
const formatDateTimeTooltip = (dateString) => {
    return new Date(dateString).toLocaleString('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
        day: 'numeric',
        month: 'short'
    })
}

// Fetch daily stats for the last 24 hours with minute-by-minute data
const fetchDailyStats = async () => {
    dailyStatsLoading.value = true;
    try {
        // Ya no necesitamos el parámetro timeframe porque queremos todos los datos
        const response = await fetch('/analytics/daily-stats');
        const data = await response.json();

        if (data.success) {
            console.log('Datos recibidos de la API:', data.data);
            dailyStats.value = data.data;
        } else {
            console.error('Error fetching daily stats:', data.message);
        }
    } catch (error) {
        console.error('Error:', error);
    } finally {
        dailyStatsLoading.value = false;
    }
}

// Trigger global sync for all channels
const triggerGlobalSync = async () => {
    loading.value = true
    try {
        const response = await fetch('/analytics/sync-all', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })

        const data = await response.json()

        if (data.success) {
            // Refresh data after sync
            await fetchGlobalAnalytics()
            await fetchAllVideos()
            await fetchDailyStats() // Refresh daily stats after sync
        }
    } catch (error) {
        console.error('Error:', error)
    } finally {
        loading.value = false
    }
}

// Navigate to specific video analytics
const openVideoAnalytics = (video) => {
    router.get(route('channels.videos.analytics-page', [video.channel_id, video.id]))
}

// Navigate to channel analytics
const openChannelAnalytics = (channelId) => {
    router.get(route('channels.analytics', channelId))
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

// Get channel name by ID
const getChannelName = (channelId) => {
    const channel = channels.value.find(c => c.id === channelId)
    return channel?.name || 'Canal Desconocido'
}

// Computed stats
const totalChannels = computed(() => channels.value.length)
const totalVideos = computed(() => allVideos.value.length)
const totalViews = computed(() => {
    return allVideos.value.reduce((sum, video) => sum + (video.statistics?.view_count || 0), 0)
})
const averageViews = computed(() => {
    return totalVideos.value > 0 ? Math.round(totalViews.value / totalVideos.value) : 0
})

// Chart data computed property con agrupación por intervalo
const chartData = computed(() => {
    const groupedData = groupDataByInterval(dailyStats.value);
    console.log('Datos agrupados:', groupedData);

    const labels = groupedData.map(stat => {
        const date = new Date(stat.datetime);
        // Formato de etiqueta según el intervalo
        if (selectedInterval.value === '1d' || selectedInterval.value === '6h') {
            return date.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        } else {
            return date.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    });

    const views = groupedData.map(stat => stat.total_views);

    return {
        labels,
        datasets: [{
            label: 'Visualizaciones Totales',
            data: views,
            borderColor: '#8B5CF6',
            backgroundColor: 'rgba(139, 92, 246, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointRadius: 2,
            pointHoverRadius: 4,
            pointBackgroundColor: '#8B5CF6',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: '#8B5CF6'
        }]
    }
})

// Chart options
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        x: {
            grid: {
                color: 'rgba(255, 255, 255, 0.1)',
                borderColor: 'rgba(255, 255, 255, 0.1)'
            },
            ticks: {
                color: 'rgba(255, 255, 255, 0.7)',
                maxRotation: 45,
                minRotation: 45,
                autoSkip: true,
                maxTicksLimit: selectedInterval.value === '1m' ? 30 : 20 // Más puntos para intervalo de 1 minuto
            }
        },
        y: {
            grid: {
                color: 'rgba(255, 255, 255, 0.1)',
                borderColor: 'rgba(255, 255, 255, 0.1)'
            },
            ticks: {
                color: 'rgba(255, 255, 255, 0.7)',
                callback: function(value) {
                    return formatNumber(value)
                }
            }
        }
    },
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            backgroundColor: 'rgba(17, 24, 39, 0.9)',
            titleColor: '#fff',
            bodyColor: '#fff',
            borderColor: 'rgba(139, 92, 246, 0.2)',
            borderWidth: 1,
            padding: 12,
            displayColors: false,
            callbacks: {
                title: function(context) {
                    const date = new Date(groupDataByInterval(dailyStats.value)[context[0].dataIndex].datetime);
                    if (selectedInterval.value === '1d' || selectedInterval.value === '6h') {
                        return date.toLocaleString('es-ES', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    } else {
                        return date.toLocaleString('es-ES', {
                            day: 'numeric',
                            month: 'short',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }
                },
                label: function(context) {
                    const exactViews = context.raw.toLocaleString('es-ES');
                    const formattedViews = formatNumber(context.raw);
                    return `${exactViews} visualizaciones (${formattedViews})`;
                }
            }
        }
    }
}

// Daily difference chart data
const dailyDifferenceChartData = computed(() => {
    console.log('Datos originales:', dailyStats.value);

    if (!dailyStats.value || dailyStats.value.length === 0) {
        console.log('No hay datos disponibles');
        return {
            labels: [],
            datasets: [{
                label: 'Incremento de Visualizaciones por Día',
                data: [],
                backgroundColor: 'rgba(139, 92, 246, 0.5)',
                borderColor: 'rgba(139, 92, 246, 1)',
                borderWidth: 1,
                borderRadius: 5,
                hoverBackgroundColor: 'rgba(139, 92, 246, 0.7)'
            }]
        }
    }

    // Agrupar datos por día (usando solo la fecha, sin la hora)
    const dailyData = {};
    
    // Primero encontramos el último valor de cada día
    dailyStats.value.forEach(stat => {
        const date = new Date(stat.datetime).toISOString().split('T')[0];
        
        if (!dailyData[date] || new Date(stat.datetime) > new Date(dailyData[date].datetime)) {
            dailyData[date] = {
                views: stat.total_views,
                datetime: stat.datetime
            };
        }
    });

    console.log('Datos agrupados por día:', dailyData);

    // Convertir a arrays ordenados por fecha
    const sortedDates = Object.keys(dailyData).sort();
    console.log('Fechas ordenadas:', sortedDates);

    const labels = [];
    const viewsDifference = [];

    // Calcular diferencias diarias
    for (let i = 1; i < sortedDates.length; i++) {
        const currentViews = dailyData[sortedDates[i]].views;
        const previousViews = dailyData[sortedDates[i - 1]].views;
        const difference = currentViews - previousViews;
        
        console.log(`Calculando diferencia para ${sortedDates[i]}:`, {
            currentViews,
            previousViews,
            difference
        });

        // Formatear la fecha para la etiqueta
        const formattedDate = new Date(sortedDates[i]).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
        
        labels.push(formattedDate);
        viewsDifference.push(difference);
    }

    console.log('Labels finales:', labels);
    console.log('Diferencias finales:', viewsDifference);

    return {
        labels,
        datasets: [{
            label: 'Incremento de Visualizaciones por Día',
            data: viewsDifference,
            backgroundColor: 'rgba(139, 92, 246, 0.5)',
            borderColor: 'rgba(139, 92, 246, 1)',
            borderWidth: 1,
            borderRadius: 5,
            hoverBackgroundColor: 'rgba(139, 92, 246, 0.7)'
        }]
    }
})

// Daily difference chart options
const dailyDifferenceChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        x: {
            grid: {
                color: 'rgba(255, 255, 255, 0.1)',
                borderColor: 'rgba(255, 255, 255, 0.1)'
            },
            ticks: {
                color: 'rgba(255, 255, 255, 0.7)',
                maxRotation: 45,
                minRotation: 45,
                autoSkip: true,
                maxTicksLimit: 10
            }
        },
        y: {
            grid: {
                color: 'rgba(255, 255, 255, 0.1)',
                borderColor: 'rgba(255, 255, 255, 0.1)'
            },
            ticks: {
                color: 'rgba(255, 255, 255, 0.7)',
                callback: function(value) {
                    return formatNumber(value)
                }
            },
            beginAtZero: true // Asegurar que el eje Y comience en 0
        }
    },
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            backgroundColor: 'rgba(17, 24, 39, 0.9)',
            titleColor: '#fff',
            bodyColor: '#fff',
            borderColor: 'rgba(139, 92, 246, 0.2)',
            borderWidth: 1,
            padding: 12,
            displayColors: false,
            callbacks: {
                title: function(context) {
                    return context[0].label
                },
                label: function(context) {
                    const value = context.raw
                    const sign = value >= 0 ? '+' : ''
                    return `${sign}${formatNumber(value)} visualizaciones`
                }
            }
        }
    }
}

onMounted(() => {
    fetchGlobalAnalytics()
    fetchAllVideos()
    fetchDailyStats()

    // Actualizar datos cada minuto
    updateInterval = setInterval(() => {
        fetchDailyStats()
    }, 60000)
})

onUnmounted(() => {
    if (updateInterval) {
        clearInterval(updateInterval)
    }
})
</script>

<template>
    <AppLayout>
        <!-- Page Header -->
        <div class="sticky top-0 z-30 bg-gray-950/80 backdrop-blur-xl border-b border-gray-800/50">
            <div class="max-w-8xl mx-auto px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2">Analíticas Globales</h1>
                        <p class="text-gray-400">Dashboard completo de todos los canales y videos</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <div class="text-sm text-gray-400">Total de canales</div>
                            <div class="text-2xl font-bold text-white">{{ totalChannels }}</div>
                        </div>
                        <button
                            @click="triggerGlobalSync"
                            :disabled="loading"
                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 disabled:opacity-50 text-white text-sm font-medium rounded-xl transition-all duration-300 shadow-lg shadow-purple-600/25"
                        >
                            <svg v-if="loading" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ loading ? 'Sincronizando...' : 'Sincronizar Todo' }}
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

            <!-- Global Statistics -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-white mb-6">Estadísticas Globales</h2>

                <!-- Views Chart -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Total Views Chart -->
                    <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-white">Visualizaciones Totales</h3>
                                <p class="text-sm text-gray-400">
                                    {{ selectedInterval === '1m' || selectedInterval === '5m' 
                                        ? 'Últimos 60 minutos' 
                                        : selectedInterval === '1h' 
                                            ? 'Últimas 24 horas'
                                            : 'Últimos 28 días' }}
                                </p>
                            </div>
                            
                            <!-- Interval Buttons -->
                            <div class="flex space-x-2">
                                <button
                                    v-for="(label, interval) in {
                                        '1m': 'Cada 1m',
                                        '5m': 'Cada 5m',
                                        '1h': 'Cada 1h',
                                        '6h': 'Cada 6h',
                                        '1d': 'Cada 1d'
                                    }"
                                    :key="interval"
                                    @click="selectedInterval = interval"
                                    :class="[
                                        'px-3 py-1.5 text-sm font-medium rounded-lg transition-all duration-200',
                                        selectedInterval === interval
                                            ? 'bg-purple-600 text-white'
                                            : 'bg-gray-700/50 text-gray-300 hover:bg-gray-700/70'
                                    ]"
                                >
                                    {{ label }}
                                </button>
                            </div>
                        </div>
                        
                        <!-- Loading State -->
                        <div v-if="dailyStatsLoading" class="flex justify-center items-center h-[400px]">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500"></div>
                        </div>

                        <!-- No Data State -->
                        <div v-else-if="dailyStats.length === 0" class="flex flex-col justify-center items-center h-[400px] text-gray-400">
                            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6-4l2 2 4-4" />
                            </svg>
                            <p class="text-lg">No hay datos disponibles</p>
                        </div>

                        <!-- Chart -->
                        <div v-else class="h-[400px]">
                            <Line :data="chartData" :options="chartOptions" />
                        </div>
                    </div>

                    <!-- Daily Difference Chart -->
                    <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Incremento Diario de Visualizaciones</h3>
                        
                        <!-- Loading State -->
                        <div v-if="dailyStatsLoading" class="flex justify-center items-center h-[400px]">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500"></div>
                        </div>

                        <!-- No Data State -->
                        <div v-else-if="dailyStats.length <= 1" class="flex flex-col justify-center items-center h-[400px] text-gray-400">
                            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6-4l2 2 4-4" />
                            </svg>
                            <p class="text-lg">Se necesitan al menos 2 días de datos</p>
                        </div>

                        <!-- Chart -->
                        <div v-else class="h-[400px]">
                            <Bar :data="dailyDifferenceChartData" :options="dailyDifferenceChartOptions" />
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white mb-1">{{ totalChannels }}</p>
                        <p class="text-gray-400 text-sm">Canales Totales</p>
                    </div>

                    <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-red-500 to-red-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white mb-1">{{ totalVideos }}</p>
                        <p class="text-gray-400 text-sm">Videos Totales</p>
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
                        <p class="text-2xl font-bold text-white mb-1">{{ formatNumber(totalViews) }}</p>
                        <p class="text-gray-400 text-sm">Visualizaciones Totales</p>
                    </div>

                    <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6-4l2 2 4-4" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-white mb-1">{{ formatNumber(averageViews) }}</p>
                        <p class="text-gray-400 text-sm">Promedio por Video</p>
                    </div>
                </div>

                <!-- Channels Quick Access -->
                <div v-if="channels.length > 0" class="mb-8">
                    <h3 class="text-xl font-semibold text-white mb-4">Acceso Rápido a Canales</h3>
                    <div class="flex flex-wrap gap-3">
                        <button
                            v-for="channel in channels"
                            :key="channel.id"
                            @click="openChannelAnalytics(channel.id)"
                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-gray-700/50 to-gray-800/50 hover:from-gray-600/50 hover:to-gray-700/50 border border-gray-600/30 hover:border-gray-500/50 text-white text-sm rounded-xl transition-all duration-300"
                        >
                            <svg class="w-4 h-4 mr-2 text-red-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                            {{ channel.name }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- All Videos List -->
            <div>
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-white">Todos los Videos (Ordenados por Visualizaciones)</h2>
                    <div class="text-sm text-gray-400">{{ allVideos.length }} videos en total</div>
                </div>

                <!-- Videos Grid -->
                <div v-if="!videosLoading && allVideos.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div v-for="(video, index) in allVideos" :key="video.id"
                         @click="openVideoAnalytics(video)"
                         class="group bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 hover:border-gray-600/50 rounded-2xl overflow-hidden transition-all duration-300 cursor-pointer hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-900/20">

                        <!-- Thumbnail -->
                        <div class="relative">
                            <img v-if="video.thumbnail"
                                 :src="video.thumbnail"
                                 :alt="video.title"
                                 class="w-full h-48 object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-gray-900/60 to-transparent"></div>

                            <!-- Ranking Badge -->
                            <div class="absolute top-2 left-2 bg-gradient-to-r from-yellow-500 to-orange-500 text-white text-xs font-bold px-2 py-1 rounded">
                                #{{ index + 1 }}
                            </div>

                            <!-- Duration -->
                            <div class="absolute bottom-2 right-2 bg-gray-900/80 text-white text-xs px-2 py-1 rounded">
                                {{ video.duration }}
                            </div>

                            <!-- Performance Score -->
                            <div class="absolute top-2 right-2 bg-gray-900/80 text-white text-xs px-2 py-1 rounded">
                                <span :class="getPerformanceColor(video.metrics?.performance_score || 0)">
                                    {{ video.metrics?.performance_score || 0 }}/100
                                </span>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-4">
                            <!-- Channel Badge - Más prominente -->
                            <div class="mb-3">
                                <button
                                    @click.stop="openChannelAnalytics(video.channel_id)"
                                    class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-red-500/20 to-red-600/20 hover:from-red-500/30 hover:to-red-600/30 border border-red-500/30 hover:border-red-500/50 text-red-400 hover:text-red-300 text-sm rounded-lg transition-all duration-200 group/channel"
                                >
                                    <svg class="w-4 h-4 mr-2 group-hover/channel:scale-110 transition-transform duration-200" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                    </svg>
                                    <span class="font-medium">{{ getChannelName(video.channel_id) }}</span>
                                    <svg class="w-3 h-3 ml-1 opacity-0 group-hover/channel:opacity-100 transition-opacity duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>

                            <h3 class="text-white font-semibold text-sm line-clamp-2 mb-3 group-hover:text-blue-300 transition-colors duration-300">
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
                                    <span class="font-semibold text-green-400">{{ formatNumber(video.statistics?.view_count || 0) }} vistas</span>
                                    <span>{{ formatNumber(video.statistics?.like_count || 0) }} likes</span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span>{{ formatNumber(video.statistics?.comment_count || 0) }} comentarios</span>
                                    <span>{{ (Number(video.metrics?.engagement_rate) || 0).toFixed(2) }}% engagement</span>
                                </div>

                                <!-- Información adicional del canal -->
                                <div class="pt-1 border-t border-gray-700/50">
                                    <div class="flex items-center justify-between">
                                        <span class="text-red-400 font-medium">Canal: {{ getChannelName(video.channel_id) }}</span>
                                        <span class="text-yellow-400">#{{ index + 1 }} más visto</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div v-else-if="videosLoading" class="flex justify-center items-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500"></div>
                </div>

                <!-- No videos -->
                <div v-else class="text-center py-12">
                    <div class="w-24 h-24 bg-gray-800/50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-12 h-12 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">No hay videos sincronizados</h3>
                    <p class="text-gray-400 mb-4">Ejecuta la sincronización para cargar los datos de YouTube</p>
                    <button
                        @click="triggerGlobalSync"
                        class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white text-sm font-medium rounded-xl transition-all duration-300"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Sincronizar Ahora
                    </button>
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
