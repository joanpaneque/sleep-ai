<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const form = useForm({
    name: '',
    description: '',
    intro: null
})

const introFile = ref(null)
const isDragOver = ref(false)
const videoPreviewUrl = ref(null)

// Extensiones de video permitidas
const allowedVideoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'mpeg', 'mpg', 'm4v', 'webm', 'mkv']
const maxFileSize = 512 * 1024 * 1024 // 512MB en bytes

const submit = () => {
    // Transform form data to include the file
    form.transform((data) => ({
        ...data,
        intro: introFile.value
    })).post(route('channels.store'), {
        onSuccess: () => {
            form.reset()
            introFile.value = null
            videoPreviewUrl.value = null
        }
    })
}

// Manejo de archivos
const handleFileChange = (event) => {
    const file = event.target.files[0]
    setIntroFile(file)
}

const handleDrop = (event) => {
    event.preventDefault()
    isDragOver.value = false
    const file = event.dataTransfer.files[0]
    setIntroFile(file)
}

const handleDragOver = (event) => {
    event.preventDefault()
    isDragOver.value = true
}

const handleDragLeave = () => {
    isDragOver.value = false
}

const triggerFileInput = () => {
    document.getElementById('intro-file-input').click()
}

const setIntroFile = (file) => {
    if (!file) return

    // Validar extensión
    const extension = file.name.split('.').pop().toLowerCase()
    if (!allowedVideoExtensions.includes(extension)) {
        alert(`Formato de archivo no válido. Formatos permitidos: ${allowedVideoExtensions.join(', ')}`)
        return
    }

    // Validar tamaño
    if (file.size > maxFileSize) {
        alert('El archivo es demasiado grande. Tamaño máximo: 512MB')
        return
    }

    introFile.value = file
    form.intro = file

    // Crear URL de preview
    if (videoPreviewUrl.value) {
        URL.revokeObjectURL(videoPreviewUrl.value)
    }
    videoPreviewUrl.value = URL.createObjectURL(file)
}

const removeIntroFile = () => {
    introFile.value = null
    form.intro = null
    if (videoPreviewUrl.value) {
        URL.revokeObjectURL(videoPreviewUrl.value)
        videoPreviewUrl.value = null
    }
}

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="bg-blue-100 rounded-full p-3 w-14 h-14 mx-auto mb-4 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        Nuevo Canal
                    </h1>
                    <p class="mt-2 text-gray-600">
                        Añade un nuevo canal de YouTube para analizar
                    </p>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Nombre del Canal
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="block text-black w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Nombre del canal"
                            >
                        </div>
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                            Descripción
                        </label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            required
                            class="block text-black w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Describe el canal y su contenido..."
                        ></textarea>
                        <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <!-- Campo de archivo para la intro -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Video Intro del Canal
                        </label>

                        <!-- Zona de drag & drop -->
                        <div
                            class="relative border-2 border-dashed rounded-lg p-6 transition-colors duration-200"
                            :class="{
                                'border-blue-400 bg-blue-50': isDragOver,
                                'border-gray-300 hover:border-gray-400': !isDragOver,
                                'border-red-300': form.errors.intro
                            }"
                            @dragover.prevent="handleDragOver"
                            @dragleave.prevent="handleDragLeave"
                            @drop.prevent="handleDrop"
                            @click="triggerFileInput"
                        >
                            <!-- Input oculto -->
                            <input
                                id="intro-file-input"
                                type="file"
                                accept="video/*"
                                class="hidden"
                                @change="handleFileChange"
                            >

                            <!-- Contenido de la zona de drop -->
                            <div v-if="!introFile" class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="mt-4">
                                    <p class="text-lg text-gray-600">Arrastra y suelta tu video aquí</p>
                                    <p class="text-sm text-gray-500 mt-1">o haz clic para seleccionar</p>
                                    <p class="text-xs text-gray-400 mt-2">
                                        Formatos: {{ allowedVideoExtensions.join(', ').toUpperCase() }}
                                    </p>
                                    <p class="text-xs text-gray-400">Tamaño máximo: 512MB</p>
                                </div>
                            </div>

                            <!-- Preview del archivo seleccionado -->
                            <div v-else class="space-y-4">
                                <!-- Video preview -->
                                <div class="flex justify-center">
                                    <video
                                        v-if="videoPreviewUrl"
                                        :src="videoPreviewUrl"
                                        controls
                                        class="max-w-full h-48 rounded-lg shadow-sm"
                                    >
                                        Tu navegador no soporta el elemento de video.
                                    </video>
                                </div>

                                <!-- Información del archivo -->
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <svg class="h-8 w-8 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                                            </svg>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ introFile.name }}</p>
                                                <p class="text-xs text-gray-500">{{ formatFileSize(introFile.size) }}</p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            @click.stop="removeIntroFile"
                                            class="text-red-500 hover:text-red-700 p-1"
                                        >
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p v-if="form.errors.intro" class="mt-1 text-sm text-red-600">
                            {{ form.errors.intro }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end space-x-3 pt-4">
                        <a
                            :href="route('channels.index')"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Cancelar
                        </a>
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ form.processing ? 'Guardando...' : 'Guardar Canal' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
