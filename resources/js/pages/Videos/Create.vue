<script setup>
import { useForm } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    channel: Object
})

const languages = [
    { code: 'es', name: 'Español' },
    { code: 'en', name: 'English' },
    { code: 'fr', name: 'Français' },
    { code: 'de', name: 'Deutsch' },
    { code: 'it', name: 'Italiano' },
    { code: 'pt', name: 'Português' }
]

const backgrounds = [
    { id: 'nature', name: 'Particles 1', description: 'Paisajes relajantes y sonidos naturales' },
    { id: 'ocean', name: 'Particles 2', description: 'Olas y sonidos marinos' },
    { id: 'rain', name: 'Particles 3', description: 'Lluvia suave y truenos lejanos' },
    { id: 'forest', name: 'Space 1', description: 'Ambiente de bosque con pájaros' },
    { id: 'white_noise', name: 'Space 2', description: 'Sonido uniforme y calmante' },
    { id: 'meditation', name: 'Fire', description: 'Música ambiental para meditación' }
]

const form = useForm({
    title: '',
    description: '',
    background: '',
    language: 'es',
    channel_id: props.channel.id
})

const submit = () => {
    form.post(route('channels.videos.store', props.channel.id))
}
</script>

<template>
    <div class="min-h-screen mx-auto px-4 sm:px-6 lg:px-8 py-8 bg-white">
        <!-- Header -->
        <div class="mb-8 border-b pb-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Crear Nuevo Video</h1>
                <Link
                    :href="route('channels.show', channel.id)"
                    class="inline-flex items-center text-indigo-600 hover:text-indigo-700 transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Cancelar
                </Link>
            </div>
            <p class="mt-2 text-sm text-gray-600">Crea un nuevo video para ayudar a tus usuarios a dormir mejor.</p>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-8 bg-white max-w-2xl mx-auto">
            <!-- Title Input -->
            <div class="relative">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                    Título del Video
                </label>
                <div class="relative rounded-md shadow-sm">
                    <input
                        type="text"
                        id="title"
                        v-model="form.title"
                        class="block w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition-colors duration-200 text-gray-700 bg-white"
                        placeholder="Ej: Meditación para un sueño profundo"
                        required
                    >
                </div>
                <p v-if="form.errors.title" class="mt-2 text-sm text-red-600">{{ form.errors.title }}</p>
            </div>

            <!-- Description Input -->
            <div class="relative">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                    Descripción
                </label>
                <div class="relative rounded-md shadow-sm">
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="block w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition-colors duration-200 text-gray-700 bg-white"
                        placeholder="Describe el contenido del video..."
                        required
                    ></textarea>
                </div>
                <p v-if="form.errors.description" class="mt-2 text-sm text-red-600">{{ form.errors.description }}</p>
            </div>

            <!-- Language Selection -->
            <div class="relative">
                <label for="language" class="block text-sm font-medium text-gray-700 mb-1">
                    Idioma
                </label>
                <div class="relative">
                    <select
                        id="language"
                        v-model="form.language"
                        class="block w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition-colors duration-200 text-gray-700 bg-white appearance-none"
                        required
                    >
                        <option v-for="lang in languages" :key="lang.code" :value="lang.code">
                            {{ lang.name }}
                        </option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
                <p v-if="form.errors.language" class="mt-2 text-sm text-red-600">{{ form.errors.language }}</p>
            </div>

            <!-- Background Selection -->
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-3">Fondo del Video</label>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div
                        v-for="bg in backgrounds"
                        :key="bg.id"
                        @click="form.background = bg.id"
                        class="relative rounded-xl border-2 bg-white px-5 py-4 cursor-pointer transition-all duration-200 hover:shadow-md"
                        :class="[
                            form.background === bg.id 
                                ? 'border-indigo-500 ring-2 ring-indigo-200' 
                                : 'border-gray-200 hover:border-indigo-200'
                        ]"
                    >
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center">
                                    <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-900">{{ bg.name }}</h3>
                                <p class="text-xs text-gray-500 mt-1">{{ bg.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-if="form.errors.background" class="mt-2 text-sm text-red-600">{{ form.errors.background }}</p>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-6">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                >
                    <svg 
                        v-if="form.processing"
                        class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" 
                        fill="none" 
                        viewBox="0 0 24 24"
                    >
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    Crear Video
                </button>
            </div>
        </form>
    </div>
</template>