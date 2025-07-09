<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'
import AppLayout from '../Layout/AppLayout.vue'

const props = defineProps({
    channel: Object,
    thumbnail: String,
    app_url: String
})

const form = useForm({
    name: props.channel.name,
    description: props.channel.description,
    intro: null,
    remove_intro: false,
    background_video: null,
    remove_background_video: false,
    frame_image: null,
    remove_frame_image: false,
    image_style_prompt: props.channel.image_style_prompt || '',
    thumbnail: props.thumbnail || '',
    thumbnail_image_prompt: props.channel.thumbnail_image_prompt || '',
    google_oauth_webhook_token: props.channel.google_oauth_webhook_token || '',
    google_client_id: props.channel.google_client_id || '',
    google_client_secret: props.channel.google_client_secret || '',
    _method: 'PATCH'
})

const introFile = ref(null)
const backgroundVideoFile = ref(null)
const frameImageFile = ref(null)
const isDragOverIntro = ref(false)
const isDragOverBackground = ref(false)
const isDragOverFrame = ref(false)
const videoPreviewUrl = ref(null)
const backgroundPreviewUrl = ref(null)
const imagePreviewUrl = ref(null)
const currentVideoUrl = ref(null)
const currentBackgroundUrl = ref(null)
const currentFrameUrl = ref(null)
const showCopySuccess = ref(false)
const isRegeneratingToken = ref(false)

const allowedVideoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'mpeg', 'mpg', 'm4v', 'webm', 'mkv']
const allowedImageExtensions = ['jpeg', 'jpg', 'png', 'gif', 'webp']
const maxVideoFileSize = 512 * 1024 * 1024
const maxImageFileSize = 50 * 1024 * 1024

onMounted(() => {
    if (props.channel.intro) {
        currentVideoUrl.value = `/storage/intros/${props.channel.intro}`
    }
    if (props.channel.background_video) {
        currentBackgroundUrl.value = `/storage/backgrounds/${props.channel.background_video}`
    }
    if (props.channel.frame_image) {
        currentFrameUrl.value = `/storage/frames/${props.channel.frame_image}`
    }
})

const submit = () => {
    form.transform((data) => ({
        ...data,
        intro: introFile.value,
        remove_intro: form.remove_intro,
        background_video: backgroundVideoFile.value,
        remove_background_video: form.remove_background_video,
        frame_image: frameImageFile.value,
        remove_frame_image: form.remove_frame_image,
        google_oauth_webhook_token: form.google_oauth_webhook_token,
        google_client_id: form.google_client_id,
        google_client_secret: form.google_client_secret
    })).post(route('channels.update', props.channel.id), {
        onSuccess: () => {
            form.reset()
            introFile.value = null
            backgroundVideoFile.value = null
            frameImageFile.value = null
            videoPreviewUrl.value = null
            backgroundPreviewUrl.value = null
            imagePreviewUrl.value = null
        }
    })
}

// Intro file handlers
const handleIntroFileChange = (event) => {
    const file = event.target.files[0]
    setIntroFile(file)
}

const handleIntroDrop = (event) => {
    event.preventDefault()
    isDragOverIntro.value = false
    const file = event.dataTransfer.files[0]
    setIntroFile(file)
}

const handleIntroDragOver = (event) => {
    event.preventDefault()
    isDragOverIntro.value = true
}

const handleIntroDragLeave = () => {
    isDragOverIntro.value = false
}

const triggerIntroFileInput = () => {
    document.getElementById('intro-file-input').click()
}

const setIntroFile = (file) => {
    if (!file) return
    const extension = file.name.split('.').pop().toLowerCase()
    if (!allowedVideoExtensions.includes(extension)) {
        alert(`Formato de archivo no válido. Formatos permitidos: ${allowedVideoExtensions.join(', ')}`)
        return
    }
    if (file.size > maxVideoFileSize) {
        alert('El archivo es demasiado grande. Tamaño máximo: 512MB')
        return
    }
    introFile.value = file
    form.intro = file
    form.remove_intro = false
    if (videoPreviewUrl.value) {
        URL.revokeObjectURL(videoPreviewUrl.value)
    }
    videoPreviewUrl.value = URL.createObjectURL(file)
}

const removeIntroFile = () => {
    introFile.value = null
    form.intro = null
    form.remove_intro = true
    if (videoPreviewUrl.value) {
        URL.revokeObjectURL(videoPreviewUrl.value)
        videoPreviewUrl.value = null
    }
    currentVideoUrl.value = null
}

// Background video handlers
const handleBackgroundFileChange = (event) => {
    const file = event.target.files[0]
    setBackgroundVideoFile(file)
}

const handleBackgroundDrop = (event) => {
    event.preventDefault()
    isDragOverBackground.value = false
    const file = event.dataTransfer.files[0]
    setBackgroundVideoFile(file)
}

const handleBackgroundDragOver = (event) => {
    event.preventDefault()
    isDragOverBackground.value = true
}

const handleBackgroundDragLeave = () => {
    isDragOverBackground.value = false
}

const triggerBackgroundFileInput = () => {
    document.getElementById('background-file-input').click()
}

const setBackgroundVideoFile = (file) => {
    if (!file) return
    const extension = file.name.split('.').pop().toLowerCase()
    if (!allowedVideoExtensions.includes(extension)) {
        alert(`Formato de archivo no válido. Formatos permitidos: ${allowedVideoExtensions.join(', ')}`)
        return
    }
    if (file.size > maxVideoFileSize) {
        alert('El archivo es demasiado grande. Tamaño máximo: 512MB')
        return
    }
    backgroundVideoFile.value = file
    form.background_video = file
    form.remove_background_video = false
    if (backgroundPreviewUrl.value) {
        URL.revokeObjectURL(backgroundPreviewUrl.value)
    }
    backgroundPreviewUrl.value = URL.createObjectURL(file)
}

const removeBackgroundVideoFile = () => {
    backgroundVideoFile.value = null
    form.background_video = null
    form.remove_background_video = true
    if (backgroundPreviewUrl.value) {
        URL.revokeObjectURL(backgroundPreviewUrl.value)
        backgroundPreviewUrl.value = null
    }
    currentBackgroundUrl.value = null
}

// Frame image handlers
const handleFrameFileChange = (event) => {
    const file = event.target.files[0]
    setFrameImageFile(file)
}

const handleFrameDrop = (event) => {
    event.preventDefault()
    isDragOverFrame.value = false
    const file = event.dataTransfer.files[0]
    setFrameImageFile(file)
}

const handleFrameDragOver = (event) => {
    event.preventDefault()
    isDragOverFrame.value = true
}

const handleFrameDragLeave = () => {
    isDragOverFrame.value = false
}

const triggerFrameFileInput = () => {
    document.getElementById('frame-file-input').click()
}

const setFrameImageFile = (file) => {
    if (!file) return
    const extension = file.name.split('.').pop().toLowerCase()
    if (!allowedImageExtensions.includes(extension)) {
        alert(`Formato de archivo no válido. Formatos permitidos: ${allowedImageExtensions.join(', ')}`)
        return
    }
    if (file.size > maxImageFileSize) {
        alert('El archivo es demasiado grande. Tamaño máximo: 50MB')
        return
    }
    frameImageFile.value = file
    form.frame_image = file
    form.remove_frame_image = false
    if (imagePreviewUrl.value) {
        URL.revokeObjectURL(imagePreviewUrl.value)
    }
    imagePreviewUrl.value = URL.createObjectURL(file)
}

const removeFrameImageFile = () => {
    frameImageFile.value = null
    form.frame_image = null
    form.remove_frame_image = true
    if (imagePreviewUrl.value) {
        URL.revokeObjectURL(imagePreviewUrl.value)
        imagePreviewUrl.value = null
    }
    currentFrameUrl.value = null
}

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const copyWebhookUrl = async () => {
    const webhookUrl = `${props.app_url}/webhook/oauth/${form.google_oauth_webhook_token}`
    try {
        await navigator.clipboard.writeText(webhookUrl)
        // Mostrar feedback visual de éxito
        showCopySuccess.value = true
        setTimeout(() => {
            showCopySuccess.value = false
        }, 2000) // Ocultar después de 2 segundos
    } catch (err) {
        console.error('Error al copiar URL:', err)
        // Fallback: seleccionar el texto
        const input = document.querySelector('input[readonly]')
        if (input) {
            input.select()
        }
    }
}

const regenerateToken = async () => {
    isRegeneratingToken.value = true

    try {
        const response = await fetch(route('generate-webhook-token'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })

        const data = await response.json()

        if (data.success) {
            // Actualizar el token en el formulario
            form.google_oauth_webhook_token = data.token
        } else {
            console.error('Error al generar el token:', data.message)
        }
    } catch (error) {
        console.error('Error generating token:', error)
    } finally {
        isRegeneratingToken.value = false
    }
}

const displayVideoUrl = computed(() => {
    return videoPreviewUrl.value || currentVideoUrl.value
})

const displayBackgroundUrl = computed(() => {
    return backgroundPreviewUrl.value || currentBackgroundUrl.value
})

const displayFrameUrl = computed(() => {
    return imagePreviewUrl.value || currentFrameUrl.value
})

const hasNewIntroFile = computed(() => {
    return !!introFile.value
})

const hasNewBackgroundFile = computed(() => {
    return !!backgroundVideoFile.value
})

const hasNewFrameFile = computed(() => {
    return !!frameImageFile.value
})
</script>

<template>
    <AppLayout>
        <!-- Header -->
        <div class="sticky top-0 z-30 bg-gray-950/80 backdrop-blur-xl border-b border-gray-800/50">
            <div class="max-w-8xl mx-auto px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white mb-1">Editar Canal</h1>
                            <p class="text-gray-400">Modifica la información del canal "{{ channel.name }}"</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-400">Modificación</div>
                        <div class="text-white font-medium">Canal YouTube</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-8xl mx-auto px-6 lg:px-8 py-8">
            <form @submit.prevent="submit">
                <!-- Bento Grid Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Información Básica - Ocupa 2 columnas -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Datos del Canal -->
                        <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 shadow-xl">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-white">Información del Canal</h3>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-300 mb-2">
                                        Nombre del Canal
                                    </label>
                                    <input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        required
                                        class="block text-white w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 transition-all duration-200"
                                        placeholder="Nombre del canal de YouTube"
                                    >
                                    <p v-if="form.errors.name" class="mt-2 text-sm text-red-400">
                                        {{ form.errors.name }}
                                    </p>
                                </div>

                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-300 mb-2">
                                        Descripción
                                    </label>
                                    <textarea
                                        id="description"
                                        v-model="form.description"
                                        rows="4"
                                        required
                                        class="block text-white w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 transition-all duration-200"
                                        placeholder="Describe el canal y su contenido..."
                                    ></textarea>
                                    <p v-if="form.errors.description" class="mt-2 text-sm text-red-400">
                                        {{ form.errors.description }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Personalización de Estilo -->
                        <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 shadow-xl">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-white">Personalización de Estilo</h3>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label for="image_style_prompt" class="block text-sm font-medium text-gray-300 mb-2">
                                        Estilo de Imágenes
                                    </label>
                                    <textarea
                                        id="image_style_prompt"
                                        v-model="form.image_style_prompt"
                                        rows="3"
                                        class="block text-white w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 transition-all duration-200"
                                        placeholder="Describe el estilo visual (ej: estilo anime, realista, cartoon, etc.)"
                                    ></textarea>
                                    <p class="text-xs text-gray-500 mt-1">Opcional - Personaliza el estilo de las imágenes generadas</p>
                                    <p v-if="form.errors.image_style_prompt" class="mt-2 text-sm text-red-400">
                                        {{ form.errors.image_style_prompt }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Configuración del Webhook -->
                        <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 shadow-xl">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-white">Configuración del Webhook</h3>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label for="google_oauth_webhook_token" class="block text-sm font-medium text-gray-300 mb-2">
                                        Token del Webhook
                                    </label>
                                    <div class="flex space-x-2">
                                        <input
                                            id="google_oauth_webhook_token"
                                            v-model="form.google_oauth_webhook_token"
                                            type="text"
                                            class="block text-white flex-1 px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400 transition-all duration-200 font-mono"
                                            placeholder="Introduce un token de 16 caracteres"
                                            maxlength="16"
                                        >
                                        <button
                                            type="button"
                                            @click="regenerateToken"
                                            :disabled="isRegeneratingToken"
                                            class="px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 text-sm font-medium whitespace-nowrap"
                                        >
                                            <div class="flex items-center">
                                                <svg v-if="isRegeneratingToken" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                <span class="ml-2">{{ isRegeneratingToken ? 'Generando...' : 'Generar' }}</span>
                                            </div>
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Token de 16 caracteres con al menos una mayúscula, una minúscula y un número
                                    </p>
                                    <p v-if="form.errors.google_oauth_webhook_token" class="mt-2 text-sm text-red-400">
                                        {{ form.errors.google_oauth_webhook_token }}
                                    </p>
                                </div>

                                <div v-if="form.google_oauth_webhook_token">
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        URL del Webhook OAuth
                                    </label>
                                    <div class="relative">
                                        <input
                                            type="text"
                                            readonly
                                            :value="`${app_url}/webhook/oauth/${form.google_oauth_webhook_token}`"
                                            class="block text-white w-full px-4 py-3 bg-gray-700/30 border border-gray-600/50 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 font-mono text-sm cursor-pointer"
                                            @click="$event.target.select()"
                                        >
                                        <button
                                            type="button"
                                            @click="copyWebhookUrl"
                                            class="absolute right-2 top-1/2 transform -translate-y-1/2 p-2 text-gray-400 hover:text-white transition-colors duration-200"
                                            :title="showCopySuccess ? '¡Copiado!' : 'Copiar URL'"
                                        >
                                            <svg v-if="!showCopySuccess" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <svg v-else class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2">
                                        Esta URL será usada por la API de Google para permitir que la web acceda a las estadísticas del canal
                                    </p>
                                    <!-- Notificación de copiado -->
                                    <div v-if="showCopySuccess" class="mt-2 p-2 bg-green-500/10 border border-green-500/20 rounded-lg flex items-center space-x-2 animate-pulse">
                                        <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span class="text-xs text-green-300 font-medium">¡URL copiada al portapapeles!</span>
                                    </div>
                                </div>

                                <div>
                                    <label for="google_client_id" class="block text-sm font-medium text-gray-300 mb-2">
                                        Google Client ID
                                    </label>
                                    <input
                                        id="google_client_id"
                                        v-model="form.google_client_id"
                                        type="text"
                                        class="block text-white w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400 transition-all duration-200 font-mono text-sm"
                                        placeholder="Client ID de la aplicación de Google"
                                    >
                                    <p v-if="form.errors.google_client_id" class="mt-2 text-sm text-red-400">
                                        {{ form.errors.google_client_id }}
                                    </p>
                                </div>

                                <div>
                                    <label for="google_client_secret" class="block text-sm font-medium text-gray-300 mb-2">
                                        Google Client Secret
                                    </label>
                                    <input
                                        id="google_client_secret"
                                        v-model="form.google_client_secret"
                                        type="password"
                                        class="block text-white w-full px-4 py-3 bg-gray-700/50 border border-gray-600/50 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400 transition-all duration-200 font-mono text-sm"
                                        placeholder="Client Secret de la aplicación de Google"
                                    >
                                    <p v-if="form.errors.google_client_secret" class="mt-2 text-sm text-red-400">
                                        {{ form.errors.google_client_secret }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Templates -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Template de Thumbnail -->
                            <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 shadow-xl">
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="w-8 h-8 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <h4 class="text-lg font-semibold text-white">Template HTML</h4>
                                </div>

                                <div class="space-y-3">
                                    <div class="p-3 bg-green-500/10 border border-green-500/20 rounded-xl">
                                        <div class="text-xs text-green-400 space-y-1">
                                            <p><code class="bg-green-400/20 px-1 rounded">%title%</code> - Título</p>
                                            <p><code class="bg-green-400/20 px-1 rounded">%subtitle%</code> - Subtítulo</p>
                                            <p><code class="bg-green-400/20 px-1 rounded">%img_url%</code> - URL imagen</p>
                                        </div>
                                    </div>
                                    <textarea
                                        id="thumbnail"
                                        v-model="form.thumbnail"
                                        rows="6"
                                        class="block text-white w-full px-3 py-3 bg-gray-700/50 border border-gray-600/50 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm placeholder-gray-400 transition-all duration-200"
                                        placeholder="<div>Template HTML...</div>"
                                    ></textarea>
                                    <p v-if="form.errors.thumbnail" class="text-sm text-red-400">
                                        {{ form.errors.thumbnail }}
                                    </p>
                                </div>
                            </div>

                            <!-- Prompt Inteligente -->
                            <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 shadow-xl">
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="w-8 h-8 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                        </svg>
                                    </div>
                                    <h4 class="text-lg font-semibold text-white">Prompt IA</h4>
                                </div>

                                <div class="space-y-3">
                                    <div class="p-3 bg-yellow-500/10 border border-yellow-500/20 rounded-xl">
                                        <div class="text-xs text-yellow-400">
                                            <p><code class="bg-yellow-400/20 px-1 rounded">%video_title%</code> - Título específico del video</p>
                                            <p class="text-xs text-yellow-400 mt-1">
                                                La IA generará prompts personalizados para cada video.
                                            </p>
                                        </div>
                                    </div>
                                    <textarea
                                        id="thumbnail_image_prompt"
                                        v-model="form.thumbnail_image_prompt"
                                        rows="6"
                                        class="block text-white w-full px-3 py-3 bg-gray-700/50 border border-gray-600/50 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 transition-all duration-200"
                                        placeholder="Genera una imagen cinematográfica basada en '%video_title%'..."
                                    ></textarea>
                                    <p v-if="form.errors.thumbnail_image_prompt" class="text-sm text-red-400">
                                        {{ form.errors.thumbnail_image_prompt }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Archivos Multimedia - Columna derecha -->
                    <div class="space-y-6">
                        <!-- Video Intro -->
                        <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 shadow-xl">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293H15M9 10v4a2 2 0 002 2h2a2 2 0 002-2v-4M9 10V9a2 2 0 012-2h2a2 2 0 012 2v1" />
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-white">Video Intro</h4>
                            </div>

                            <!-- Preview del video si existe -->
                            <div v-if="displayVideoUrl" class="mb-4">
                                <video
                                    :src="displayVideoUrl"
                                    controls
                                    class="w-full h-32 rounded-xl shadow-sm object-cover"
                                >
                                    Tu navegador no soporta el elemento de video.
                                </video>
                                <div class="mt-2 flex items-center justify-between">
                                    <div class="text-sm text-gray-400">
                                        <span v-if="hasNewIntroFile">{{ introFile.name }} ({{ formatFileSize(introFile.size) }})</span>
                                        <span v-else-if="currentVideoUrl">Archivo actual</span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeIntroFile"
                                        class="text-red-400 hover:text-red-300 transition-colors duration-200"
                                        :title="hasNewIntroFile ? 'Cancelar cambio' : 'Eliminar intro del canal'"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Zona de drag & drop -->
                            <div
                                class="relative border-2 border-dashed rounded-xl p-4 transition-colors duration-200 cursor-pointer"
                                :class="{
                                    'border-blue-400 bg-blue-500/10': isDragOverIntro,
                                    'border-gray-600/50 hover:border-gray-500/50': !isDragOverIntro,
                                    'border-red-400': form.errors.intro
                                }"
                                @dragover.prevent="handleIntroDragOver"
                                @dragleave.prevent="handleIntroDragLeave"
                                @drop.prevent="handleIntroDrop"
                                @click="triggerIntroFileInput"
                            >
                                <input
                                    id="intro-file-input"
                                    type="file"
                                    accept="video/*"
                                    class="hidden"
                                    @change="handleIntroFileChange"
                                >
                                <div class="text-center">
                                    <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <p class="text-sm text-gray-300">{{ hasNewIntroFile ? 'Nuevo archivo seleccionado' : 'Arrastra tu video intro' }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ hasNewIntroFile ? 'Reemplazará el actual' : 'o haz clic para seleccionar' }}</p>
                                    <p class="text-xs text-gray-500 mt-1">Máx. 512MB</p>
                                </div>
                            </div>
                            <p v-if="form.errors.intro" class="mt-2 text-sm text-red-400">
                                {{ form.errors.intro }}
                            </p>
                        </div>

                        <!-- Video de Fondo -->
                        <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 shadow-xl">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="w-8 h-8 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m0 0V1a1 1 0 011-1h2a1 1 0 011 1v18a1 1 0 01-1 1H4a1 1 0 01-1-1V4a1 1 0 011-1h2a1 1 0 011 1v3" />
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-white">Video Fondo</h4>
                            </div>

                            <!-- Preview del video si existe -->
                            <div v-if="displayBackgroundUrl" class="mb-4">
                                <video
                                    :src="displayBackgroundUrl"
                                    controls
                                    class="w-full h-32 rounded-xl shadow-sm object-cover"
                                >
                                    Tu navegador no soporta el elemento de video.
                                </video>
                                <div class="mt-2 flex items-center justify-between">
                                    <div class="text-sm text-gray-400">
                                        <span v-if="hasNewBackgroundFile">{{ backgroundVideoFile.name }} ({{ formatFileSize(backgroundVideoFile.size) }})</span>
                                        <span v-else-if="currentBackgroundUrl">Archivo actual</span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeBackgroundVideoFile"
                                        class="text-red-400 hover:text-red-300 transition-colors duration-200"
                                        :title="hasNewBackgroundFile ? 'Cancelar cambio' : 'Eliminar video de fondo'"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Zona de drag & drop -->
                            <div
                                class="relative border-2 border-dashed rounded-xl p-4 transition-colors duration-200 cursor-pointer"
                                :class="{
                                    'border-blue-400 bg-blue-500/10': isDragOverBackground,
                                    'border-gray-600/50 hover:border-gray-500/50': !isDragOverBackground,
                                    'border-red-400': form.errors.background_video
                                }"
                                @dragover.prevent="handleBackgroundDragOver"
                                @dragleave.prevent="handleBackgroundDragLeave"
                                @drop.prevent="handleBackgroundDrop"
                                @click="triggerBackgroundFileInput"
                            >
                                <input
                                    id="background-file-input"
                                    type="file"
                                    accept="video/*"
                                    class="hidden"
                                    @change="handleBackgroundFileChange"
                                >
                                <div class="text-center">
                                    <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <p class="text-sm text-gray-300">{{ hasNewBackgroundFile ? 'Nuevo archivo seleccionado' : 'Arrastra video de fondo' }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ hasNewBackgroundFile ? 'Reemplazará el actual' : 'o haz clic para seleccionar' }}</p>
                                    <p class="text-xs text-gray-500 mt-1">Máx. 512MB</p>
                                </div>
                            </div>
                            <p v-if="form.errors.background_video" class="mt-2 text-sm text-red-400">
                                {{ form.errors.background_video }}
                            </p>
                        </div>

                        <!-- Imagen del Marco -->
                        <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 shadow-xl">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-white">Imagen Marco</h4>
                            </div>

                            <!-- Preview de la imagen si existe -->
                            <div v-if="displayFrameUrl" class="mb-4">
                                <img
                                    :src="displayFrameUrl"
                                    class="w-full h-32 rounded-xl shadow-sm object-cover"
                                    alt="Imagen del marco"
                                >
                                <div class="mt-2 flex items-center justify-between">
                                    <div class="text-sm text-gray-400">
                                        <span v-if="hasNewFrameFile">{{ frameImageFile.name }} ({{ formatFileSize(frameImageFile.size) }})</span>
                                        <span v-else-if="currentFrameUrl">Archivo actual</span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeFrameImageFile"
                                        class="text-red-400 hover:text-red-300 transition-colors duration-200"
                                        :title="hasNewFrameFile ? 'Cancelar cambio' : 'Eliminar imagen del marco'"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Zona de drag & drop -->
                            <div
                                class="relative border-2 border-dashed rounded-xl p-4 transition-colors duration-200 cursor-pointer"
                                :class="{
                                    'border-blue-400 bg-blue-500/10': isDragOverFrame,
                                    'border-gray-600/50 hover:border-gray-500/50': !isDragOverFrame,
                                    'border-red-400': form.errors.frame_image
                                }"
                                @dragover.prevent="handleFrameDragOver"
                                @dragleave.prevent="handleFrameDragLeave"
                                @drop.prevent="handleFrameDrop"
                                @click="triggerFrameFileInput"
                            >
                                <input
                                    id="frame-file-input"
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="handleFrameFileChange"
                                >
                                <div class="text-center">
                                    <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <p class="text-sm text-gray-300">{{ hasNewFrameFile ? 'Nueva imagen seleccionada' : 'Arrastra imagen del marco' }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ hasNewFrameFile ? 'Reemplazará la actual' : 'o haz clic para seleccionar' }}</p>
                                    <p class="text-xs text-gray-500 mt-1">Máx. 50MB</p>
                                </div>
                            </div>
                            <p v-if="form.errors.frame_image" class="mt-2 text-sm text-red-400">
                                {{ form.errors.frame_image }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-700/50">
                    <a
                        :href="route('channels.index')"
                        class="inline-flex items-center px-6 py-3 border border-gray-600/50 rounded-xl text-sm font-medium text-gray-300 bg-gray-700/30 hover:bg-gray-700/50 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200"
                    >
                        Cancelar
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center px-6 py-3 border border-transparent rounded-xl shadow-lg text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 transition-all duration-200 hover:scale-105"
                        :disabled="form.processing"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.processing ? 'Actualizando...' : 'Actualizar Canal' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
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
button:focus-visible,
input:focus-visible,
textarea:focus-visible {
    outline: 2px solid rgb(59, 130, 246);
    outline-offset: 2px;
}
</style>
