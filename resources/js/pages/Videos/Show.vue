<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    video: Object,
    channel: Object
})

const script = [
    { time: "00:00:00", text: "Introducción y bienvenida a la meditación" },
    { time: "00:02:30", text: "Ejercicios de respiración profunda" },
    { time: "00:05:45", text: "Relajación progresiva del cuerpo" },
    { time: "00:10:15", text: "Visualización del lugar seguro" },
    { time: "00:15:30", text: "Técnicas de mindfulness y atención plena" },
    { time: "00:20:00", text: "Ejercicios de respiración 4-7-8" },
    { time: "00:25:15", text: "Visualización de escenas naturales" },
    { time: "00:30:00", text: "Afirmaciones positivas para el sueño" },
    { time: "00:35:45", text: "Ejercicios de relajación muscular" },
    { time: "00:40:30", text: "Meditación guiada final" },
    { time: "00:45:00", text: "Transición suave hacia el sueño" }
]

// Lista de imágenes disponibles
const availableImages = [
    'piclumen-1750257827909.png',
    'piclumen-1750257894917.png',
    'piclumen-1750258388965.png',
    'piclumen-1750258541037.png',
    'piclumen-1750258671051.png',
    'piclumen-1750258835921.png',
    'piclumen-1750259284357.png',
    'piclumen-1750259405788.png',
    'piclumen-1750259552417.png'
]

// Cada entrada del script tiene su correspondiente elemento visual
const visualElements = script.map((item, index) => ({
    id: index + 1,
    timestamp: item.time,
    title: item.text,
    type: "background",
    // Usa una imagen de la lista, si no hay suficientes, vuelve a empezar
    preview: `/img/${availableImages[index % availableImages.length]}`,
    duration: calculateDuration(item.time, script[index + 1]?.time)
}))

// Función para calcular la duración entre dos timestamps
function calculateDuration(start, end) {
    if (!end) return "hasta el final"
    return `${start} - ${end}`
}

const copyAllTimestamps = () => {
    const formattedText = script
        .map(line => `${line.time} ${line.text}`)
        .join('\n')
    navigator.clipboard.writeText(formattedText)
}

const activeTab = ref('script')
</script>

<template>
    <div class="min-h-screen bg-white">
        <!-- Header -->
        <div class="border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center mb-4">
                            <Link 
                                :href="route('channels.show', channel.id)"
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
                                Volver al Canal
                            </Link>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ video.title }}</h1>
                        <p class="mt-1 text-sm text-gray-500">Canal: {{ channel.name }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button
                        @click="activeTab = 'script'"
                        :class="[
                            activeTab === 'script'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        Guión
                    </button>
                    <button
                        @click="activeTab = 'visual'"
                        :class="[
                            activeTab === 'visual'
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        Elementos Visuales
                    </button>
                </nav>
            </div>

            <!-- Script Tab -->
            <div v-if="activeTab === 'script'" class="py-6">
                <div class="bg-white rounded-lg border p-6">
                    <!-- Copy All Button -->
                    <div class="mb-4">
                        <button
                            @click="copyAllTimestamps"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            Copiar Todos los Timestamps
                        </button>
                    </div>
                    <div class="space-y-3 font-mono">
                        <div v-for="(line, index) in script" 
                             :key="index"
                             class="flex p-2 rounded"
                        >
                            <span class="text-indigo-600 mr-4">{{ line.time }}</span>
                            <span class="text-gray-700">{{ line.text }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visual Elements Tab -->
            <div v-if="activeTab === 'visual'" class="py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="element in visualElements" 
                         :key="element.id" 
                         class="bg-white rounded-lg border overflow-hidden shadow-sm hover:shadow-md transition-shadow"
                    >
                        <div class="aspect-w-16 aspect-h-9 bg-gray-100">
                            <img 
                                :src="element.preview" 
                                :alt="element.title"
                                class="object-cover w-full h-full"
                            />
                        </div>
                        <div class="p-4">
                            <div class="font-mono text-sm text-indigo-600 mb-2">
                                {{ element.timestamp }}
                            </div>
                            <h3 class="text-gray-900 font-medium mb-2">{{ element.title }}</h3>
                            <p class="text-sm text-gray-500">
                                Duración: {{ element.duration }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>