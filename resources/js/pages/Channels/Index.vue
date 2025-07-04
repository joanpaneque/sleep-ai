<script setup>
import { useForm, router, usePoll } from '@inertiajs/vue3'

defineProps({
    channels: {
        type: Array,
        required: true
    },
    storage_stats: {
        type: Object,
        required: true
    }
})

usePoll(2000);

const deleteForm = useForm({})

const deleteChannel = (channelId) => {
    if (confirm('¿Estás seguro de que quieres eliminar este canal?')) {
        deleteForm.delete(route('channels.destroy', channelId))
    }
}

const createChannel = () => {
    router.get(route('channels.create'));
}
const showChannel = (channelId) => {
    router.get(route('channels.show', channelId));
}

const editChannel = (channelId) => {
    router.get(route('channels.edit', channelId));
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">
                    Gestión de Canales
                </h1>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Administra y analiza los canales de YouTube para optimizar el contenido relacionado con el sueño.
                </p>
            </div>

            <!-- Storage Statistics -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 border border-gray-100">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-3">
                        <div class="bg-blue-100 rounded-full p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 1.79 4 4 4h8c2.21 0 4-1.79 4-4V7M4 7c0-2.21 1.79-4 4-4h8c2.21 0 4 1.79 4 4M4 7h16M8 11h8" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">Almacenamiento</h3>
                            <p class="text-sm text-gray-500">Espacio disponible para la aplicación</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-gray-900">
                            {{ storage_stats.used_percentage }}%
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ storage_stats.used_space_formatted }} usado
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Espacio Usado</span>
                        <span>{{ storage_stats.used_space_formatted }} / {{ storage_stats.total_space_formatted }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div
                            class="h-3 rounded-full transition-all duration-300"
                            :class="{
                                'bg-green-500': storage_stats.used_percentage < 50,
                                'bg-yellow-500': storage_stats.used_percentage >= 50 && storage_stats.used_percentage < 80,
                                'bg-red-500': storage_stats.used_percentage >= 80
                            }"
                            :style="{ width: storage_stats.used_percentage + '%' }"
                        ></div>
                    </div>
                </div>

                                                                <!-- Storage Details -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Espacio Usado</p>
                                <p class="text-lg font-semibold text-gray-900">{{ storage_stats.used_space_formatted }}</p>
                            </div>
                            <div class="bg-blue-100 rounded-full p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2v2zm0 0h18M7 21l4-4 4 4" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Espacio Disponible</p>
                                <p class="text-lg font-semibold text-gray-900">{{ storage_stats.free_space_formatted }}</p>
                            </div>
                            <div class="bg-green-100 rounded-full p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Espacio Total</p>
                                <p class="text-lg font-semibold text-gray-900">{{ storage_stats.total_space_formatted }}</p>
                            </div>
                            <div class="bg-purple-100 rounded-full p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Channels Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Add New Channel Card -->
                <button
                    @click="createChannel"
                    class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300 border-2 border-dashed border-gray-300 flex flex-col items-center justify-center p-6 hover:border-blue-500 group"
                >
                    <div class="bg-gray-100 rounded-full p-4 mb-4 group-hover:bg-blue-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400 group-hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-600 group-hover:text-blue-600">
                        Añadir Nuevo Canal
                    </h2>
                    <p class="text-gray-500 text-center mt-2">
                        Agrega un nuevo canal de YouTube
                    </p>
                </button>

                <!-- Existing Channel Cards -->
                <div v-for="channel in channels" :key="channel.id"
                     class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    <div class="p-6">
                        <div
                            @click="showChannel(channel.id)"
                            class="cursor-pointer"
                        >
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-2xl font-bold text-gray-900">
                                    {{ channel.name }}
                                </h2>
                                                                <div class="flex items-center space-x-2">
                                    <div class="bg-blue-100 rounded-full p-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <button
                                        @click.stop="editChannel(channel.id)"
                                        class="bg-green-100 rounded-full p-2 hover:bg-green-200 transition-colors duration-200"
                                        title="Editar canal"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click.stop="deleteChannel(channel.id)"
                                        class="bg-red-100 rounded-full p-2 hover:bg-red-200 transition-colors duration-200"
                                        title="Eliminar canal"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <p class="text-gray-600 mb-4 line-clamp-3">
                                {{ channel.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
