<script setup>
import { useForm, router, usePoll } from '@inertiajs/vue3'
import AppLayout from '../Layout/AppLayout.vue'

defineProps({
    channels: {
        type: Array,
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
    <AppLayout>
        <!-- Page Header -->
        <div class="sticky top-0 z-30 bg-gray-950/80 backdrop-blur-xl border-b border-gray-800/50">
            <div class="max-w-8xl mx-auto px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2">Canales</h1>
                        <p class="text-gray-400">Gestiona y analiza tus canales de YouTube</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <div class="text-sm text-gray-400">Total de canales</div>
                            <div class="text-2xl font-bold text-white">{{ channels.length }}</div>
                        </div>
                        <button
                            @click="createChannel"
                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white text-sm font-medium rounded-xl transition-all duration-300 shadow-lg shadow-blue-600/25 hover:shadow-blue-600/40 hover:scale-105"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Nuevo Canal
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-8xl mx-auto px-6 lg:px-8 py-8">
            <!-- Channels Section -->
            <div>
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold text-white">Tus Canales</h3>
                    <div class="text-sm text-gray-400">{{ channels.length }} canal{{ channels.length !== 1 ? 'es' : '' }}</div>
                </div>

                <!-- Modern Grid Layout -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6">
                    <!-- Create New Channel Card -->
                    <button
                        @click="createChannel"
                        class="group relative bg-gradient-to-br from-gray-800/50 to-gray-900/50 hover:from-gray-700/60 hover:to-gray-800/60 border border-gray-700/50 hover:border-gray-600/50 rounded-2xl p-8 transition-all duration-300 flex flex-col items-center justify-center min-h-[280px] backdrop-blur-sm hover:shadow-2xl hover:shadow-blue-500/10 hover:scale-[1.02]"
                    >
                        <!-- Animated Background -->
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-600/5 to-purple-600/5 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                        <!-- Plus Icon with Animation -->
                        <div class="relative w-16 h-16 bg-gradient-to-r from-gray-700 to-gray-600 group-hover:from-blue-600 group-hover:to-purple-600 rounded-2xl flex items-center justify-center mb-6 transition-all duration-300 group-hover:scale-110 group-hover:shadow-lg">
                            <svg class="w-8 h-8 text-gray-400 group-hover:text-white transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>

                        <h4 class="text-lg font-semibold text-gray-300 group-hover:text-white transition-colors duration-300 mb-2">
                            Crear Canal
                        </h4>
                        <p class="text-sm text-gray-500 group-hover:text-gray-400 text-center transition-colors duration-300">
                            Añade un nuevo canal de YouTube a tu dashboard
                        </p>

                        <!-- Subtle border glow -->
                        <div class="absolute inset-0 rounded-2xl bg-gradient-to-r from-blue-600/20 to-purple-600/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 -z-10 blur-xl"></div>
                    </button>

                    <!-- Channel Cards -->
                    <div v-for="channel in channels" :key="channel.id"
                         class="group relative bg-gradient-to-br from-gray-800/60 to-gray-900/40 hover:from-gray-700/70 hover:to-gray-800/50 border border-gray-700/50 hover:border-gray-600/50 rounded-2xl overflow-hidden transition-all duration-300 cursor-pointer backdrop-blur-sm hover:shadow-2xl hover:shadow-gray-900/20 hover:scale-[1.02]"
                         @click="showChannel(channel.id)">

                        <!-- Card Header -->
                        <div class="p-6 pb-4">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-lg font-semibold text-white mb-2 truncate group-hover:text-blue-300 transition-colors duration-300">
                                        {{ channel.name }}
                                    </h4>
                                    <p class="text-sm text-gray-400 line-clamp-3 leading-relaxed">
                                        {{ channel.description || 'Sin descripción disponible' }}
                                    </p>
                                </div>
                                <div class="ml-4 w-12 h-12 bg-gradient-to-r from-red-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg flex-shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer with Actions -->
                        <div class="px-6 pb-6">
                            <div class="flex items-center justify-between pt-4 border-t border-gray-700/50">
                                <div class="flex items-center space-x-2">
                                    <button
                                        @click.stop="editChannel(channel.id)"
                                        class="p-2.5 hover:bg-gray-700/50 rounded-xl transition-all duration-200 group/btn"
                                        title="Editar canal"
                                    >
                                        <svg class="w-4 h-4 text-gray-400 group-hover/btn:text-blue-400 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click.stop="deleteChannel(channel.id)"
                                        class="p-2.5 hover:bg-red-500/10 rounded-xl transition-all duration-200 group/btn"
                                        title="Eliminar canal"
                                    >
                                        <svg class="w-4 h-4 text-gray-400 group-hover/btn:text-red-400 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex items-center text-xs text-gray-500 group-hover:text-gray-400 transition-colors duration-300">
                                    <span>Ver detalles</span>
                                    <svg class="w-3 h-3 ml-1 transform group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Hover Glow Effect -->
                        <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-blue-600/5 to-purple-600/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

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
button:focus-visible {
    outline: 2px solid rgb(59, 130, 246);
    outline-offset: 2px;
}
</style>
