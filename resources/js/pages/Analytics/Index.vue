<script setup>
import { ref, onMounted, computed, onUnmounted, watch } from 'vue'
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
const selectedDifferenceInterval = ref('1h') // Intervalo por defecto: 1 hora (cambiado de 5m a 1h)
const comparisonMode = ref('none') // 'none', 'previous_period', 'same_period_last_week'
const comparisonData = ref(null)
const selectedChannels = ref([]) // Para filtros de canal
const viewMode = ref('grid') // 'grid' o 'compact' para el modo de vista
const sortBy = ref('views') // Para ordenamiento
const sortOrder = ref('desc') // Para dirección del ordenamiento
let updateInterval = null

// Función para redondear una fecha al intervalo más cercano
const roundToInterval = (date, intervalMinutes) => {
    const coeff = 1000 * 60 * intervalMinutes;
    return new Date(Math.floor(date.getTime() / coeff) * coeff);
}

// Función para obtener el período de comparación
const getComparisonPeriod = (date, mode) => {
    if (mode !== 'previous_period') return date;

    const newDate = new Date(date)
    // Restar el período actual
    const intervalMap = {
        '1m': 60 * 60 * 1000,        // 1 hora en ms
        '5m': 24 * 60 * 60 * 1000,   // 24 horas en ms
        '1h': 48 * 60 * 60 * 1000,   // 48 horas en ms
        '6h': 48 * 60 * 60 * 1000,   // 48 horas en ms
        '1d': 28 * 24 * 60 * 60 * 1000 // 28 días en ms
    }
    return new Date(newDate.getTime() - intervalMap[selectedInterval.value])
}

// Función para agrupar datos por intervalo
const groupDataByInterval = (data, isComparison = false) => {
    if (!data || data.length === 0) return [];
    
    const intervalMap = {
        '1m': { minutes: 1, period: 60 },      // 1 minuto, últimos 60 minutos
        '5m': { minutes: 5, period: 1440 },    // 5 minutos, últimas 24 horas
        '1h': { minutes: 60, period: 2880 },   // 1 hora, últimas 48 horas
        '6h': { minutes: 360, period: 2880 },  // 6 horas, últimas 48 horas
        '1d': { minutes: 1440, period: 40320 } // 1 día, últimos 28 días
    };

    const { minutes, period } = intervalMap[selectedInterval.value];
    const now = new Date();
    const periodEnd = isComparison ? getComparisonPeriod(now, comparisonMode.value) : now;
    const periodStart = new Date(periodEnd.getTime() - period * 60000);

    // Filtrar datos dentro del periodo
    const filteredData = data.filter(stat => {
        const statDate = new Date(stat.datetime);
        return statDate >= periodStart && statDate <= periodEnd;
    });
    
    // Crear array de puntos de tiempo para el período completo
    const timePoints = [];
    let currentTime = new Date(periodStart);
    while (currentTime <= periodEnd) {
        timePoints.push(roundToInterval(new Date(currentTime), minutes));
        currentTime = new Date(currentTime.getTime() + minutes * 60000);
    }

    // Crear Map con todos los puntos de tiempo inicializados
    const groupedData = new Map();
    timePoints.forEach(time => {
        groupedData.set(time.getTime(), {
            datetime: time,
            total_views: null
        });
    });

    // Llenar con datos reales donde existan
    filteredData.forEach(stat => {
        const date = new Date(stat.datetime);
        const roundedDate = roundToInterval(date, minutes);
        const key = roundedDate.getTime();

        if (groupedData.has(key)) {
            groupedData.set(key, {
                datetime: roundedDate,
                total_views: stat.total_views
            });
        }
    });

    // Convertir a array y rellenar valores nulos con interpolación
    const sortedData = Array.from(groupedData.values())
        .sort((a, b) => a.datetime - b.datetime);

    // Interpolación lineal para valores faltantes
    let lastValidValue = null;
    for (let i = 0; i < sortedData.length; i++) {
        if (sortedData[i].total_views === null) {
            // Buscar el próximo valor válido
            let nextValidIndex = i + 1;
            while (nextValidIndex < sortedData.length && sortedData[nextValidIndex].total_views === null) {
                nextValidIndex++;
            }

            if (lastValidValue !== null && nextValidIndex < sortedData.length) {
                // Interpolación lineal
                const nextValue = sortedData[nextValidIndex].total_views;
                const totalSteps = nextValidIndex - i + 1;
                const step = (nextValue - lastValidValue) / totalSteps;
                sortedData[i].total_views = Math.round(lastValidValue + step * (i - (nextValidIndex - totalSteps)));
            } else if (lastValidValue !== null) {
                // Si no hay próximo valor, usar el último válido
                sortedData[i].total_views = lastValidValue;
            } else if (nextValidIndex < sortedData.length) {
                // Si no hay valor anterior, usar el próximo válido
                sortedData[i].total_views = sortedData[nextValidIndex].total_views;
            } else {
                // Si no hay valores válidos, usar 0 o el último valor conocido
                sortedData[i].total_views = lastValidValue || 0;
            }
        }
        if (sortedData[i].total_views !== null) {
            lastValidValue = sortedData[i].total_views;
        }
    }

    return sortedData;
}

// Función para agrupar datos por intervalo para diferencias
const groupDifferenceDataByInterval = (data) => {
    if (!data || data.length === 0) return [];
    
    const intervalMap = {
        '1h': { minutes: 60, period: 2880 },   // 1 hora, últimas 48 horas
        '6h': { minutes: 360, period: 2880 },  // 6 horas, últimas 48 horas
        '1d': { minutes: 1440, period: 40320 } // 1 día, últimos 28 días
    };

    const { minutes, period } = intervalMap[selectedDifferenceInterval.value];
    const now = new Date();
    const cutoffTime = new Date(now.getTime() - period * 60000);

    const filteredData = data.filter(stat => new Date(stat.datetime) >= cutoffTime);
    const groupedData = new Map();

    const sortedData = [...filteredData].sort((a, b) => new Date(a.datetime) - new Date(b.datetime));

    sortedData.forEach(stat => {
        const date = new Date(stat.datetime);
        const roundedDate = roundToInterval(date, minutes);
        const key = roundedDate.getTime();

        groupedData.set(key, {
            datetime: roundedDate,
            total_views: stat.total_views
        });
    });

    return Array.from(groupedData.values())
        .sort((a, b) => a.datetime - b.datetime);
}

// Fetch global analytics (all channels combined)
const fetchGlobalAnalytics = async () => {
    statsLoading.value = true
    try {
        const response = await fetch('/analytics/global-stats', {
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }
        
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
        const response = await fetch('/analytics/all-videos?limit=100&order_by=view_count&order_direction=desc', {
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }
        
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
        // Construir la URL con los canales seleccionados si hay alguno
        let url = '/analytics/daily-stats';
        if (selectedChannels.value.length > 0) {
            const channelParams = selectedChannels.value.map(id => `channels[]=${id}`).join('&');
            url += `?${channelParams}`;
        }

        const response = await fetch(url, {
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }
        
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
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

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
    const currentData = groupDataByInterval(dailyStats.value);
    const comparisonGroupedData = comparisonMode.value !== 'none' 
        ? groupDataByInterval(dailyStats.value, true)
        : null;

    const labels = currentData.map(stat => {
        const date = new Date(stat.datetime);
        if (selectedInterval.value === '1d' || selectedInterval.value === '6h') {
            return date.toLocaleString('es-ES', {
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

    const datasets = [{
        label: 'Visualizaciones Totales',
        data: currentData.map(stat => stat.total_views),
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
    }];

    if (comparisonGroupedData) {
        datasets.push({
            label: comparisonMode.value === 'previous_period' ? 'Período Anterior' : 'Misma Semana Anterior',
            data: comparisonGroupedData.map(stat => stat.total_views),
            borderColor: '#60A5FA',
            backgroundColor: 'rgba(96, 165, 250, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointRadius: 2,
            pointHoverRadius: 4,
            pointBackgroundColor: '#60A5FA',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: '#60A5FA'
        });
    }

    return {
        labels,
        datasets
    }
})

// Computed para filtrar videos por canal seleccionado
const filteredVideos = computed(() => {
    if (selectedChannels.value.length === 0) return allVideos.value;
    return allVideos.value.filter(video => selectedChannels.value.includes(video.channel_id));
})

// Computed para ordenar videos
const sortedVideos = computed(() => {
    return [...filteredVideos.value].sort((a, b) => {
        let aValue, bValue;
        
        switch (sortBy.value) {
            case 'views':
                aValue = a.statistics?.view_count || 0;
                bValue = b.statistics?.view_count || 0;
                break;
            case 'likes':
                aValue = a.statistics?.like_count || 0;
                bValue = b.statistics?.like_count || 0;
                break;
            case 'comments':
                aValue = a.statistics?.comment_count || 0;
                bValue = b.statistics?.comment_count || 0;
                break;
            case 'date':
                aValue = new Date(a.published_at).getTime();
                bValue = new Date(b.published_at).getTime();
                break;
            case 'performance':
                aValue = a.metrics?.performance_score || 0;
                bValue = b.metrics?.performance_score || 0;
                break;
            default:
                aValue = a.statistics?.view_count || 0;
                bValue = b.statistics?.view_count || 0;
        }

        return sortOrder.value === 'desc' ? bValue - aValue : aValue - bValue;
    });
})

// Computed para datos de gráfica (ahora los datos ya vienen filtrados del backend)
const filteredChartData = computed(() => {
    return chartData.value;
})

// Chart options
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        intersect: false,
        mode: 'index'
    },
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
                maxTicksLimit: selectedInterval.value === '1m' ? 30 : 20
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
            display: true,
            position: 'top',
            labels: {
                color: 'rgba(255, 255, 255, 0.7)',
                padding: 20,
                usePointStyle: true,
                pointStyle: 'circle'
            }
        },
        tooltip: {
            backgroundColor: 'rgba(17, 24, 39, 0.9)',
            titleColor: '#fff',
            bodyColor: '#fff',
            borderColor: 'rgba(139, 92, 246, 0.2)',
            borderWidth: 1,
            padding: 12,
            displayColors: true,
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
                    const value = context.raw;
                    const exactViews = value.toLocaleString('es-ES');
                    const formattedViews = formatNumber(value);

                    // Calcular el porcentaje de diferencia si hay comparación
                    if (comparisonMode.value !== 'none' && context.datasetIndex === 0 && context.dataset.data.length > 1) {
                        const currentValue = value;
                        const comparisonValue = context.chart.data.datasets[1].data[context.dataIndex];
                        if (comparisonValue) {
                            const difference = currentValue - comparisonValue;
                            const percentChange = ((difference / comparisonValue) * 100).toFixed(1);
                            const sign = difference >= 0 ? '+' : '';
                            return `${context.dataset.label}: ${exactViews} (${formattedViews}) ${sign}${percentChange}%`;
                        }
                    }
                    
                    return `${context.dataset.label}: ${exactViews} (${formattedViews})`;
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
                label: 'Incremento de Visualizaciones',
                data: [],
                backgroundColor: 'rgba(139, 92, 246, 0.5)',
                borderColor: 'rgba(139, 92, 246, 1)',
                borderWidth: 1,
                borderRadius: 5,
                hoverBackgroundColor: 'rgba(139, 92, 246, 0.7)'
            }]
        }
    }

    // Usar la función de agrupación específica para diferencias
    const groupedData = groupDifferenceDataByInterval(dailyStats.value);
    console.log('Datos agrupados:', groupedData);

    const labels = [];
    const viewsDifference = [];

    // Calcular diferencias entre intervalos
    for (let i = 1; i < groupedData.length; i++) {
        const currentViews = groupedData[i].total_views;
        const previousViews = groupedData[i - 1].total_views;
        const difference = currentViews - previousViews;
        
        const date = new Date(groupedData[i].datetime);
        let formattedDate;
        
        if (selectedDifferenceInterval.value === '1d' || selectedDifferenceInterval.value === '6h') {
            formattedDate = date.toLocaleString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        } else {
            formattedDate = date.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        labels.push(formattedDate);
        viewsDifference.push(difference);
    }

    return {
        labels,
        datasets: [{
            label: 'Incremento de Visualizaciones',
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

// Watch for changes in selected channels
watch(selectedChannels, () => {
    fetchDailyStats();
});

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
                        <div class="flex flex-col space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-white">Visualizaciones Totales</h3>
                                    <p class="text-sm text-gray-400">
                                        {{ selectedInterval === '1m' 
                                            ? 'Últimos 60 minutos'
                                            : selectedInterval === '5m'
                                                ? 'Últimas 24 horas'
                                                : selectedInterval === '1h' || selectedInterval === '6h'
                                                    ? 'Últimas 48 horas'
                                                    : 'Últimos 28 días' }}
                                    </p>
                                </div>
                                
                                <div class="flex items-center gap-3">
                                    <!-- Channel Filter -->
                                    <div class="relative">
                                        <button
                                            @click="$refs.channelDropdown.classList.toggle('hidden')"
                                            class="px-3 py-1.5 text-xs font-medium bg-gray-700/50 text-gray-300 hover:bg-gray-700/70 rounded-lg transition-all duration-200 flex items-center gap-2"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                            </svg>
                                            Filtrar Canales
                                            <span v-if="selectedChannels.length > 0" class="bg-purple-600 text-white px-1.5 rounded-full text-xs">
                                                {{ selectedChannels.length }}
                                            </span>
                                        </button>
                                        <div
                                            ref="channelDropdown"
                                            class="hidden absolute right-0 mt-2 w-64 bg-gray-800 border border-gray-700 rounded-xl shadow-lg z-50"
                                        >
                                            <div class="p-2">
                                                <div class="space-y-1">
                                                    <label
                                                        v-for="channel in channels"
                                                        :key="channel.id"
                                                        class="flex items-center p-2 rounded hover:bg-gray-700/50 cursor-pointer"
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            :value="channel.id"
                                                            v-model="selectedChannels"
                                                            class="rounded border-gray-600 text-purple-600 focus:ring-purple-500 bg-gray-700"
                                                        >
                                                        <span class="ml-2 text-sm text-gray-300">{{ channel.name }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Comparison Controls -->
                                    <div class="flex">
                                        <button
                                            v-for="(label, mode) in {
                                                'none': 'Sin Comparar',
                                                'previous_period': 'vs Anterior'
                                            }"
                                            :key="mode"
                                            @click="comparisonMode = mode"
                                            :class="[
                                                'px-2 py-1 text-xs font-medium transition-all duration-200',
                                                mode === 'none' ? 'rounded-l-lg' : 'rounded-r-lg border-l border-gray-600',
                                                comparisonMode === mode
                                                    ? 'bg-blue-600 text-white'
                                                    : 'bg-gray-700/50 text-gray-300 hover:bg-gray-700/70'
                                            ]"
                                        >
                                            {{ label }}
                                        </button>
                                    </div>

                                    <div class="h-4 w-px bg-gray-700"></div>

                                    <!-- Interval Buttons -->
                                    <div class="flex">
                                        <button
                                            v-for="(label, interval) in {
                                                '1m': '1m',
                                                '5m': '5m',
                                                '1h': '1h',
                                                '6h': '6h',
                                                '1d': '1d'
                                            }"
                                            :key="interval"
                                            @click="selectedInterval = interval"
                                            :class="[
                                                'px-2 py-1 text-xs font-medium transition-all duration-200',
                                                interval === '1m' ? 'rounded-l-lg' : interval === '1d' ? 'rounded-r-lg' : '',
                                                interval !== '1m' ? 'border-l border-gray-600' : '',
                                                selectedInterval === interval
                                                    ? 'bg-purple-600 text-white'
                                                    : 'bg-gray-700/50 text-gray-300 hover:bg-gray-700/70'
                                            ]"
                                        >
                                            {{ label }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Chart Content -->
                        <div v-if="dailyStatsLoading" class="flex justify-center items-center h-[400px]">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500"></div>
                        </div>
                        <div v-else-if="dailyStats.length === 0" class="flex flex-col justify-center items-center h-[400px] text-gray-400">
                            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6-4l2 2 4-4" />
                            </svg>
                            <p class="text-lg">No hay datos disponibles</p>
                        </div>
                        <div v-else class="h-[400px]">
                            <Line :data="filteredChartData" :options="chartOptions" />
                        </div>
                    </div>

                    <!-- Daily Difference Chart -->
                    <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-white">Incremento de Visualizaciones</h3>
                                <p class="text-sm text-gray-400">
                                    {{ selectedDifferenceInterval === '1h' || selectedDifferenceInterval === '6h'
                                        ? 'Últimas 48 horas'
                                        : 'Últimos 28 días' }}
                                </p>
                            </div>
                            
                            <!-- Interval Buttons -->
                            <div class="flex space-x-2">
                                <button
                                    v-for="(label, interval) in {
                                        '1h': 'Cada 1h',
                                        '6h': 'Cada 6h',
                                        '1d': 'Cada 1d'
                                    }"
                                    :key="interval"
                                    @click="selectedDifferenceInterval = interval"
                                    :class="[
                                        'px-3 py-1.5 text-sm font-medium rounded-lg transition-all duration-200',
                                        selectedDifferenceInterval === interval
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
                        <div v-else-if="dailyStats.length <= 1" class="flex flex-col justify-center items-center h-[400px] text-gray-400">
                            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6-4l2 2 4-4" />
                            </svg>
                            <p class="text-lg">Se necesitan al menos dos puntos de datos para el intervalo seleccionado</p>
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
                    <div class="flex items-center gap-4">
                        <!-- View Mode Toggle -->
                        <div class="flex rounded-lg overflow-hidden">
                            <button
                                @click="viewMode = 'grid'"
                                :class="[
                                    'p-2 transition-all duration-200',
                                    viewMode === 'grid'
                                        ? 'bg-purple-600 text-white'
                                        : 'bg-gray-700/50 text-gray-300 hover:bg-gray-700/70'
                                ]"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                            </button>
                            <button
                                @click="viewMode = 'compact'"
                                :class="[
                                    'p-2 transition-all duration-200',
                                    viewMode === 'compact'
                                        ? 'bg-purple-600 text-white'
                                        : 'bg-gray-700/50 text-gray-300 hover:bg-gray-700/70'
                                ]"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                            </button>
                        </div>

                        <!-- Sort Controls -->
                        <div class="flex items-center gap-2">
                            <select
                                v-model="sortBy"
                                class="bg-gray-700/50 border-gray-600 text-gray-300 text-sm rounded-lg focus:ring-purple-500 focus:border-purple-500 p-2.5"
                            >
                                <option value="views">Visualizaciones</option>
                                <option value="likes">Likes</option>
                                <option value="comments">Comentarios</option>
                                <option value="date">Fecha</option>
                                <option value="performance">Rendimiento</option>
                            </select>
                            <button
                                @click="sortOrder = sortOrder === 'desc' ? 'asc' : 'desc'"
                                class="p-2.5 bg-gray-700/50 text-gray-300 rounded-lg hover:bg-gray-700/70 transition-all duration-200"
                            >
                                <svg
                                    class="w-5 h-5 transition-transform duration-200"
                                    :class="{ 'transform rotate-180': sortOrder === 'asc' }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                                </svg>
                            </button>
                        </div>

                        <div class="text-sm text-gray-400">{{ sortedVideos.length }} videos en total</div>
                    </div>
                </div>

                <!-- Videos Grid/List -->
                <div v-if="!videosLoading && sortedVideos.length > 0" :class="[
                    viewMode === 'grid'
                        ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6'
                        : 'space-y-4'
                ]">
                    <template v-for="(video, index) in sortedVideos" :key="video.id">
                        <!-- Grid View -->
                        <div
                            v-if="viewMode === 'grid'"
                            @click="openVideoAnalytics(video)"
                            class="group bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 hover:border-gray-600/50 rounded-2xl overflow-hidden transition-all duration-300 cursor-pointer hover:scale-[1.02] hover:shadow-2xl hover:shadow-gray-900/20"
                        >
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

                        <!-- Compact View -->
                        <div
                            v-else
                            @click="openVideoAnalytics(video)"
                            class="group bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 hover:border-gray-600/50 rounded-xl p-4 transition-all duration-300 cursor-pointer hover:shadow-lg"
                        >
                            <div class="flex items-center gap-4">
                                <!-- Thumbnail -->
                                <div class="relative w-40 h-24 flex-shrink-0">
                                    <img
                                        v-if="video.thumbnail"
                                        :src="video.thumbnail"
                                        :alt="video.title"
                                        class="w-full h-full object-cover rounded-lg"
                                    >
                                    <div class="absolute bottom-1 right-1 bg-gray-900/80 text-white text-xs px-1.5 py-0.5 rounded">
                                        {{ video.duration }}
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="flex-grow min-w-0">
                                    <div class="flex items-start justify-between gap-4">
                                        <h3 class="text-white font-semibold text-sm line-clamp-2 group-hover:text-blue-300 transition-colors duration-300">
                                            {{ video.title }}
                                        </h3>
                                        <div :class="['text-sm font-medium', getPerformanceColor(video.metrics?.performance_score || 0)]">
                                            {{ video.metrics?.performance_score || 0 }}/100
                                        </div>
                                    </div>

                                    <div class="mt-2 flex items-center gap-4 text-sm text-gray-400">
                                        <span class="font-semibold text-green-400">{{ formatNumber(video.statistics?.view_count || 0) }} vistas</span>
                                        <span>{{ formatNumber(video.statistics?.like_count || 0) }} likes</span>
                                        <span>{{ formatNumber(video.statistics?.comment_count || 0) }} comentarios</span>
                                        <span>{{ (Number(video.metrics?.engagement_rate) || 0).toFixed(2) }}% engagement</span>
                                    </div>

                                    <div class="mt-2 flex items-center gap-2 text-sm">
                                        <button
                                            @click.stop="openChannelAnalytics(video.channel_id)"
                                            class="text-red-400 hover:text-red-300 font-medium transition-colors duration-200"
                                        >
                                            {{ getChannelName(video.channel_id) }}
                                        </button>
                                        <span class="text-gray-600">•</span>
                                        <span class="text-gray-400">{{ formatDate(video.published_at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
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
