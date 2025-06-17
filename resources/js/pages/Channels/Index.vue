<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AddChannelModal from '@/Components/AddChannelModal.vue'
import DeleteConfirmModal from '@/Components/DeleteConfirmModal.vue'

defineProps({
    channels: {
        type: Array,
        required: true
    }
})

const showModal = ref(false)
const showDeleteModal = ref(false)
const deleteForm = useForm({})
const channelToDelete = ref(null)

const confirmDelete = (channel) => {
    channelToDelete.value = channel
    showDeleteModal.value = true
}

const handleDelete = () => {
    if (channelToDelete.value) {
        deleteForm.delete(`/channels/${channelToDelete.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false
                channelToDelete.value = null
            }
        })
    }
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

            <!-- Channels Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Add New Channel Card -->
                <button
                    @click="showModal = true"
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
                                    @click="confirmDelete(channel)"
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

        <AddChannelModal
            :show="showModal"
            @close="showModal = false"
        />

        <DeleteConfirmModal
            :show="showDeleteModal"
            :channel-name="channelToDelete?.name || ''"
            @close="showDeleteModal = false"
            @confirm="handleDelete"
        />
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