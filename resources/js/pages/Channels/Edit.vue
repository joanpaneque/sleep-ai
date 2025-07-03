<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
    channel: Object
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
    _method: 'PATCH'
})

// Control de visibilidad de secciones
const showIntroSection = ref(!!props.channel.intro)
const showBackgroundSection = ref(!!props.channel.background_video)
const showFrameSection = ref(!!props.channel.frame_image)
const showStylePromptSection = ref(!!props.channel.image_style_prompt)

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

// Extensiones de archivos permitidas
const allowedVideoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'mpeg', 'mpg', 'm4v', 'webm', 'mkv']
const allowedImageExtensions = ['jpeg', 'jpg', 'png', 'gif', 'webp']
const maxVideoFileSize = 512 * 1024 * 1024 // 512MB en bytes
const maxImageFileSize = 50 * 1024 * 1024 // 50MB en bytes

// Configurar archivos actuales al montar el componente
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
    // Transform form data to include the files
    form.transform((data) => ({
        ...data,
        intro: introFile.value,
        remove_intro: form.remove_intro,
        background_video: backgroundVideoFile.value,
        remove_background_video: form.remove_background_video,
        frame_image: frameImageFile.value,
        remove_frame_image: form.remove_frame_image
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

// Función para limpiar archivos cuando se desmarca el checkbox
const toggleIntroSection = () => {
    if (!showIntroSection.value) {
        removeIntroFile()
    }
}

const toggleBackgroundSection = () => {
    if (!showBackgroundSection.value) {
        removeBackgroundVideoFile()
    }
}

const toggleFrameSection = () => {
    if (!showFrameSection.value) {
        removeFrameImageFile()
    }
}

const toggleStylePromptSection = () => {
    if (!showStylePromptSection.value) {
        form.image_style_prompt = ''
    }
}

// Manejo de archivos para intro
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

    // Validar extensión
    const extension = file.name.split('.').pop().toLowerCase()
    if (!allowedVideoExtensions.includes(extension)) {
        alert(`Formato de archivo no válido. Formatos permitidos: ${allowedVideoExtensions.join(', ')}`)
        return
    }

    // Validar tamaño
    if (file.size > maxVideoFileSize) {
        alert('El archivo es demasiado grande. Tamaño máximo: 512MB')
        return
    }

    introFile.value = file
    form.intro = file
    form.remove_intro = false

    // Crear URL de preview
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
    // También ocultar el video actual si existe
    currentVideoUrl.value = null
}

// Manejo de archivos para background video
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

    // Validar extensión
    const extension = file.name.split('.').pop().toLowerCase()
    if (!allowedVideoExtensions.includes(extension)) {
        alert(`Formato de archivo no válido. Formatos permitidos: ${allowedVideoExtensions.join(', ')}`)
        return
    }

    // Validar tamaño
    if (file.size > maxVideoFileSize) {
        alert('El archivo es demasiado grande. Tamaño máximo: 512MB')
        return
    }

    backgroundVideoFile.value = file
    form.background_video = file
    form.remove_background_video = false

    // Crear URL de preview
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

// Manejo de archivos para frame image
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

    // Validar extensión
    const extension = file.name.split('.').pop().toLowerCase()
    if (!allowedImageExtensions.includes(extension)) {
        alert(`Formato de archivo no válido. Formatos permitidos: ${allowedImageExtensions.join(', ')}`)
        return
    }

    // Validar tamaño
    if (file.size > maxImageFileSize) {
        alert('El archivo es demasiado grande. Tamaño máximo: 50MB')
        return
    }

    frameImageFile.value = file
    form.frame_image = file
    form.remove_frame_image = false

    // Crear URL de preview
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

// Computed para mostrar los archivos actuales o los previews
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
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="bg-blue-100 rounded-full p-3 w-14 h-14 mx-auto mb-4 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        Editar Canal
                    </h1>
                    <p class="mt-2 text-gray-600">
                        Modifica la información del canal "{{ channel.name }}"
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

                    <!-- Checkbox para Video Intro -->
                    <div>
                        <div class="flex items-center">
                            <input
                                id="enable-intro"
                                v-model="showIntroSection"
                                @change="toggleIntroSection"
                                type="checkbox"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            >
                            <label for="enable-intro" class="ml-2 block text-sm font-medium text-gray-700">
                                Usar una intro personalizada
                            </label>
                        </div>
                    </div>

                    <!-- Campo de archivo para la intro -->
                    <div v-if="showIntroSection">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Video Intro del Canal
                        </label>
                        <p class="text-sm text-gray-500 mb-3">
                            {{ hasNewIntroFile ? 'Nuevo archivo seleccionado (reemplazará el actual)' : 'Deja vacío para mantener el video actual' }}
                        </p>

                        <!-- Video actual o preview -->
                        <div v-if="displayVideoUrl" class="mb-4">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <h4 class="text-sm font-medium text-gray-700">
                                        {{ hasNewIntroFile ? 'Nuevo video (vista previa)' : 'Video actual' }}
                                    </h4>
                                    <button
                                        v-if="!hasNewIntroFile && currentVideoUrl"
                                        type="button"
                                        @click="removeIntroFile"
                                        class="text-red-500 hover:text-red-700 text-sm flex items-center space-x-1"
                                        title="Eliminar intro del canal"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Eliminar</span>
                                    </button>
                                </div>
                                <video
                                    :src="displayVideoUrl"
                                    controls
                                    class="max-w-full h-48 rounded-lg shadow-sm mx-auto"
                                >
                                    Tu navegador no soporta el elemento de video.
                                </video>
                                <button
                                    v-if="hasNewIntroFile"
                                    type="button"
                                    @click="removeIntroFile"
                                    class="mt-2 text-red-500 hover:text-red-700 text-sm"
                                >
                                    ✕ Cancelar cambio
                                </button>
                            </div>
                        </div>

                        <!-- Zona de drag & drop -->
                        <div
                            class="relative border-2 border-dashed rounded-lg p-6 transition-colors duration-200"
                            :class="{
                                'border-blue-400 bg-blue-50': isDragOverIntro,
                                'border-gray-300 hover:border-gray-400': !isDragOverIntro,
                                'border-red-300': form.errors.intro
                            }"
                            @dragover.prevent="handleIntroDragOver"
                            @dragleave.prevent="handleIntroDragLeave"
                            @drop.prevent="handleIntroDrop"
                            @click="triggerIntroFileInput"
                        >
                            <!-- Input oculto -->
                            <input
                                id="intro-file-input"
                                type="file"
                                accept="video/*"
                                class="hidden"
                                @change="handleIntroFileChange"
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

                            <!-- Información del nuevo archivo seleccionado -->
                            <div v-else class="space-y-4">
                                <!-- Información del archivo -->
                                <div class="bg-green-50 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <svg class="h-8 w-8 text-green-500" fill="currentColor" viewBox="0 0 20 20">
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

                    <!-- Checkbox para Video de Fondo -->
                    <div>
                        <div class="flex items-center">
                            <input
                                id="enable-background"
                                v-model="showBackgroundSection"
                                @change="toggleBackgroundSection"
                                type="checkbox"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            >
                            <label for="enable-background" class="ml-2 block text-sm font-medium text-gray-700">
                                Usar un video de fondo personalizado
                            </label>
                        </div>
                    </div>

                    <!-- Campo de archivo para el video de fondo -->
                    <div v-if="showBackgroundSection">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Video de Fondo
                        </label>
                        <p class="text-sm text-gray-500 mb-3">
                            {{ hasNewBackgroundFile ? 'Nuevo archivo seleccionado (reemplazará el actual)' : 'Deja vacío para mantener el video actual' }}
                        </p>

                        <!-- Video actual o preview -->
                        <div v-if="displayBackgroundUrl" class="mb-4">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <h4 class="text-sm font-medium text-gray-700">
                                        {{ hasNewBackgroundFile ? 'Nuevo video de fondo (vista previa)' : 'Video de fondo actual' }}
                                    </h4>
                                    <button
                                        v-if="!hasNewBackgroundFile && currentBackgroundUrl"
                                        type="button"
                                        @click="removeBackgroundVideoFile"
                                        class="text-red-500 hover:text-red-700 text-sm flex items-center space-x-1"
                                        title="Eliminar video de fondo"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Eliminar</span>
                                    </button>
                                </div>
                                <video
                                    :src="displayBackgroundUrl"
                                    controls
                                    class="max-w-full h-48 rounded-lg shadow-sm mx-auto"
                                >
                                    Tu navegador no soporta el elemento de video.
                                </video>
                                <button
                                    v-if="hasNewBackgroundFile"
                                    type="button"
                                    @click="removeBackgroundVideoFile"
                                    class="mt-2 text-red-500 hover:text-red-700 text-sm"
                                >
                                    ✕ Cancelar cambio
                                </button>
                            </div>
                        </div>

                        <!-- Zona de drag & drop para background video -->
                        <div
                            class="relative border-2 border-dashed rounded-lg p-6 transition-colors duration-200"
                            :class="{
                                'border-blue-400 bg-blue-50': isDragOverBackground,
                                'border-gray-300 hover:border-gray-400': !isDragOverBackground,
                                'border-red-300': form.errors.background_video
                            }"
                            @dragover.prevent="handleBackgroundDragOver"
                            @dragleave.prevent="handleBackgroundDragLeave"
                            @drop.prevent="handleBackgroundDrop"
                            @click="triggerBackgroundFileInput"
                        >
                            <!-- Input oculto -->
                            <input
                                id="background-file-input"
                                type="file"
                                accept="video/*"
                                class="hidden"
                                @change="handleBackgroundFileChange"
                            >

                            <!-- Contenido de la zona de drop -->
                            <div v-if="!backgroundVideoFile" class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="mt-4">
                                    <p class="text-lg text-gray-600">Arrastra y suelta el video de fondo aquí</p>
                                    <p class="text-sm text-gray-500 mt-1">o haz clic para seleccionar</p>
                                    <p class="text-xs text-gray-400 mt-2">
                                        Formatos: {{ allowedVideoExtensions.join(', ').toUpperCase() }}
                                    </p>
                                    <p class="text-xs text-gray-400">Tamaño máximo: 512MB</p>
                                </div>
                            </div>

                            <!-- Información del nuevo archivo seleccionado -->
                            <div v-else class="space-y-4">
                                <!-- Información del archivo -->
                                <div class="bg-green-50 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <svg class="h-8 w-8 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                                            </svg>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ backgroundVideoFile.name }}</p>
                                                <p class="text-xs text-gray-500">{{ formatFileSize(backgroundVideoFile.size) }}</p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            @click.stop="removeBackgroundVideoFile"
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

                        <p v-if="form.errors.background_video" class="mt-1 text-sm text-red-600">
                            {{ form.errors.background_video }}
                        </p>
                    </div>

                    <!-- Checkbox para Imagen del Marco -->
                    <div>
                        <div class="flex items-center">
                            <input
                                id="enable-frame"
                                v-model="showFrameSection"
                                @change="toggleFrameSection"
                                type="checkbox"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            >
                            <label for="enable-frame" class="ml-2 block text-sm font-medium text-gray-700">
                                Usar una imagen de marco personalizada
                            </label>
                        </div>
                    </div>

                    <!-- Campo de archivo para la imagen del marco -->
                    <div v-if="showFrameSection">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Imagen del Marco
                        </label>
                        <p class="text-sm text-gray-500 mb-3">
                            {{ hasNewFrameFile ? 'Nueva imagen seleccionada (reemplazará la actual)' : 'Deja vacío para mantener la imagen actual' }}
                        </p>

                        <!-- Imagen actual o preview -->
                        <div v-if="displayFrameUrl" class="mb-4">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <h4 class="text-sm font-medium text-gray-700">
                                        {{ hasNewFrameFile ? 'Nueva imagen del marco (vista previa)' : 'Imagen del marco actual' }}
                                    </h4>
                                    <button
                                        v-if="!hasNewFrameFile && currentFrameUrl"
                                        type="button"
                                        @click="removeFrameImageFile"
                                        class="text-red-500 hover:text-red-700 text-sm flex items-center space-x-1"
                                        title="Eliminar imagen del marco"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Eliminar</span>
                                    </button>
                                </div>
                                <img
                                    :src="displayFrameUrl"
                                    class="max-w-full h-48 rounded-lg shadow-sm mx-auto object-contain"
                                    alt="Imagen del marco"
                                >
                                <button
                                    v-if="hasNewFrameFile"
                                    type="button"
                                    @click="removeFrameImageFile"
                                    class="mt-2 text-red-500 hover:text-red-700 text-sm"
                                >
                                    ✕ Cancelar cambio
                                </button>
                            </div>
                        </div>

                        <!-- Zona de drag & drop para frame image -->
                        <div
                            class="relative border-2 border-dashed rounded-lg p-6 transition-colors duration-200"
                            :class="{
                                'border-blue-400 bg-blue-50': isDragOverFrame,
                                'border-gray-300 hover:border-gray-400': !isDragOverFrame,
                                'border-red-300': form.errors.frame_image
                            }"
                            @dragover.prevent="handleFrameDragOver"
                            @dragleave.prevent="handleFrameDragLeave"
                            @drop.prevent="handleFrameDrop"
                            @click="triggerFrameFileInput"
                        >
                            <!-- Input oculto -->
                            <input
                                id="frame-file-input"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handleFrameFileChange"
                            >

                            <!-- Contenido de la zona de drop -->
                            <div v-if="!frameImageFile" class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="mt-4">
                                    <p class="text-lg text-gray-600">Arrastra y suelta la imagen del marco aquí</p>
                                    <p class="text-sm text-gray-500 mt-1">o haz clic para seleccionar</p>
                                    <p class="text-xs text-gray-400 mt-2">
                                        Formatos: {{ allowedImageExtensions.join(', ').toUpperCase() }}
                                    </p>
                                    <p class="text-xs text-gray-400">Tamaño máximo: 50MB</p>
                                </div>
                            </div>

                            <!-- Información del nuevo archivo seleccionado -->
                            <div v-else class="space-y-4">
                                <!-- Información del archivo -->
                                <div class="bg-green-50 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <svg class="h-8 w-8 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                                            </svg>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ frameImageFile.name }}</p>
                                                <p class="text-xs text-gray-500">{{ formatFileSize(frameImageFile.size) }}</p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            @click.stop="removeFrameImageFile"
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

                        <p v-if="form.errors.frame_image" class="mt-1 text-sm text-red-600">
                            {{ form.errors.frame_image }}
                        </p>
                    </div>

                    <!-- Checkbox para Prompt de Estilo -->
                    <div>
                        <div class="flex items-center">
                            <input
                                id="enable-style-prompt"
                                v-model="showStylePromptSection"
                                @change="toggleStylePromptSection"
                                type="checkbox"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            >
                            <label for="enable-style-prompt" class="ml-2 block text-sm font-medium text-gray-700">
                                Personalizar el estilo de las imágenes generadas
                            </label>
                        </div>
                    </div>

                    <!-- Campo de texto para el prompt de estilo de imagen -->
                    <div v-if="showStylePromptSection">
                        <label for="image_style_prompt" class="block text-sm font-medium text-gray-700 mb-1">
                            Prompt de Estilo de Imagen
                        </label>
                        <textarea
                            id="image_style_prompt"
                            v-model="form.image_style_prompt"
                            rows="3"
                            class="block text-black w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Describe el estilo visual que quieres para las imágenes generadas (ej: estilo anime, realista, cartoon, etc.)"
                        ></textarea>
                        <p class="text-xs text-gray-500 mt-1">Máximo 1000 caracteres</p>
                        <p v-if="form.errors.image_style_prompt" class="mt-1 text-sm text-red-600">
                            {{ form.errors.image_style_prompt }}
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
                            {{ form.processing ? 'Actualizando...' : 'Actualizar Canal' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
