<script setup>
import { useForm } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/pages/Layout/AppLayout.vue'

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
    <AppLayout title="Crear Nuevo Video">
        <!-- Header -->
        <div class="bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 border-b border-gray-700">
            <div class="max-w-8xl mx-auto px-6 lg:px-8 py-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">Crear Nuevo Video</h1>
                            <p class="text-gray-400">Canal: {{ channel.name }}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <Link
                            :href="route('channels.show', channel.id)"
                            class="group/cancel px-4 py-2 bg-gray-700/50 hover:bg-gray-600/50 border border-gray-600/50 hover:border-gray-500/50 rounded-lg transition-all duration-200 shadow-sm hover:shadow-lg"
                        >
                            <div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-gray-300 group-hover/cancel:text-white transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span class="text-sm font-medium text-gray-300 group-hover/cancel:text-white transition-colors duration-200">
                                    Cancelar
                                </span>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-4xl mx-auto px-6 lg:px-8 py-8">
            <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-8 shadow-xl">
                <div class="mb-8">
                    <h2 class="text-2xl font-semibold text-white mb-2">Configuración del Video</h2>
                    <p class="text-gray-400">Define los parámetros para generar tu video de relajación</p>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-8">
                    <!-- Title Input -->
                    <div class="space-y-3">
                        <label for="title" class="block text-sm font-medium text-gray-300">
                            Título del Video
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                id="title"
                                v-model="form.title"
                                class="block w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-white placeholder-gray-400 focus:border-indigo-500/50 focus:bg-gray-700/70 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200"
                                placeholder="Ej: Meditación para un sueño profundo"
                                required
                            >
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                        </div>
                        <p v-if="form.errors.title" class="text-sm text-red-400">{{ form.errors.title }}</p>
                    </div>

                    <!-- Description Input -->
                    <div class="space-y-3">
                        <label for="description" class="block text-sm font-medium text-gray-300">
                            Descripción
                        </label>
                                                 <div class="relative">
                             <textarea
                                 id="description"
                                 v-model="form.description"
                                 rows="4"
                                 class="block w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-white placeholder-gray-400 focus:border-indigo-500/50 focus:bg-gray-700/70 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200 resize-none"
                                 placeholder="Describe el contenido del video... (esto no se usa para generar el video, aquí puedes poner notas para ti)"
                                 required
                             ></textarea>
                         </div>
                        <p v-if="form.errors.description" class="text-sm text-red-400">{{ form.errors.description }}</p>
                    </div>

                    <!-- Grid for smaller inputs -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Language Selection -->
                        <div class="space-y-3">
                            <label for="language" class="block text-sm font-medium text-gray-300">
                                Idioma
                            </label>
                            <div class="relative">
                                <select
                                    id="language"
                                    v-model="form.language"
                                    class="block w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-white focus:border-indigo-500/50 focus:bg-gray-700/70 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200 appearance-none cursor-pointer"
                                    required
                                >
                                    <option v-for="(lang, code) in languages" :key="code" :value="code" class="bg-gray-800 text-white">
                                        {{ lang.language_name }}
                                    </option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                            <p v-if="form.errors.language" class="text-sm text-red-400">{{ form.errors.language }}</p>
                        </div>

                        <!-- Stories Amount -->
                        <div class="space-y-3">
                            <label for="stories_amount" class="block text-sm font-medium text-gray-300">
                                Cantidad de Historias
                            </label>
                            <div class="relative">
                                                             <input
                                 type="text"
                                 id="stories_amount"
                                 v-model="form.stories_amount"
                                 class="block w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-white placeholder-gray-400 focus:border-indigo-500/50 focus:bg-gray-700/70 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200"
                                 placeholder="5"
                                 required
                             >
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h4a1 1 0 110 2h-1v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6H3a1 1 0 010-2h4zM6 6v12h12V6H6zm3 3a1 1 0 112 0v6a1 1 0 11-2 0V9zm4 0a1 1 0 112 0v6a1 1 0 11-2 0V9z" />
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400">Número de historias que incluirá el video (1-100)</p>
                            <p v-if="form.errors.stories_amount" class="text-sm text-red-400">{{ form.errors.stories_amount }}</p>
                        </div>
                    </div>

                    <!-- Characters Amount -->
                    <div class="space-y-3">
                        <label for="characters_amount" class="block text-sm font-medium text-gray-300">
                            Cantidad de Caracteres por Historia
                        </label>
                        <div class="relative">
                                                         <input
                                 type="text"
                                 id="characters_amount"
                                 v-model="form.characters_amount"
                                 class="block w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-white placeholder-gray-400 focus:border-indigo-500/50 focus:bg-gray-700/70 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200"
                                 placeholder="200"
                                 required
                             >
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400">Número de caracteres por historia (1-1,000,000)</p>
                        <p v-if="form.errors.characters_amount" class="text-sm text-red-400">{{ form.errors.characters_amount }}</p>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end pt-6 border-t border-gray-700/50">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="group/submit px-6 py-3 bg-gray-700/50 hover:bg-indigo-500/20 border border-gray-600/50 hover:border-indigo-500/50 rounded-lg transition-all duration-200 shadow-sm hover:shadow-lg hover:shadow-indigo-500/20 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                        >
                            <div class="flex items-center space-x-2">
                                <svg
                                    v-if="form.processing"
                                    class="animate-spin w-5 h-5 text-gray-300"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                <svg
                                    v-else
                                    class="w-5 h-5 text-gray-300 group-hover/submit:text-indigo-400 transition-colors duration-200"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="text-sm font-medium text-gray-300 group-hover/submit:text-indigo-400 transition-colors duration-200">
                                    {{ form.processing ? 'Creando...' : 'Crear Video' }}
                                </span>
                            </div>
                        </button>
                    </div>
                </form>
            </div>


        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom scrollbar for textarea */
textarea::-webkit-scrollbar {
    width: 6px;
}

textarea::-webkit-scrollbar-track {
    background: rgba(31, 41, 55, 0.5);
}

textarea::-webkit-scrollbar-thumb {
    background: rgba(107, 114, 128, 0.5);
    border-radius: 3px;
}

textarea::-webkit-scrollbar-thumb:hover {
    background: rgba(107, 114, 128, 0.8);
}

/* Enhanced focus states for accessibility */
input:focus-visible,
textarea:focus-visible,
select:focus-visible,
button:focus-visible {
    outline: 2px solid rgb(59, 130, 246);
    outline-offset: 2px;
}

/* Custom select arrow */
select option {
    background-color: rgb(31, 41, 55);
    color: white;
}
</style>
