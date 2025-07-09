<script setup>
import { useForm } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    channel: Object,
    languages: Object
})

const form = useForm({
    title: '',
    description: '',
    language: 'es',
    stories_amount: 5,
    characters_amount: 200,
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
                        placeholder="Describe el contenido del video... (esto no se usa para generar el video, aqui puedes poner notas para ti)"
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
                        <option v-for="(lang, code) in languages" :key="code" :value="code">
                            {{ lang.language_name }}
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

            <!-- Stories Amount -->
            <div class="relative">
                <label for="stories_amount" class="block text-sm font-medium text-gray-700 mb-1">
                    Cantidad de Historias
                </label>
                <div class="relative rounded-md shadow-sm">
                    <input
                        type="number"
                        id="stories_amount"
                        v-model="form.stories_amount"
                        min="1"
                        max="100"
                        class="block w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition-colors duration-200 text-gray-700 bg-white"
                        required
                    >
                </div>
                <p class="mt-1 text-sm text-gray-500">Número de historias que incluirá el video (1-100)</p>
                <p v-if="form.errors.stories_amount" class="mt-2 text-sm text-red-600">{{ form.errors.stories_amount }}</p>
            </div>

            <!-- Characters Amount -->
            <div class="relative">
                <label for="characters_amount" class="block text-sm font-medium text-gray-700 mb-1">
                    Cantidad de Caracteres
                </label>
                <div class="relative rounded-md shadow-sm">
                    <input
                        type="number"
                        id="characters_amount"
                        v-model="form.characters_amount"
                        min="1"
                        max="1000000"
                        class="block w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 transition-colors duration-200 text-gray-700 bg-white"
                        required
                    >
                </div>
                <p class="mt-1 text-sm text-gray-500">Número de caracteres por historia (1-1000000)</p>
                <p v-if="form.errors.characters_amount" class="mt-2 text-sm text-red-600">{{ form.errors.characters_amount }}</p>
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
