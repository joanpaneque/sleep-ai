<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['close'])

const form = useForm({
    name: '',
    description: ''
})

const closeModal = () => {
    form.reset()
    emit('close')
}

const submit = () => {
    form.post('/channels', {
        onSuccess: () => {
            closeModal()
        }
    })
}
</script>

<template>
    <transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
    </transition>

    <transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        <div v-if="show" class="fixed inset-0 z-10 overflow-y-auto" @click="closeModal">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div 
                    class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg"
                    @click.stop
                >
                    <!-- Header -->
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-xl font-semibold leading-6 text-gray-900">
                                    Añadir Nuevo Canal
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Completa la información del canal de YouTube que deseas añadir a tu lista.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submit" class="px-4 sm:px-6 pt-2">
                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium leading-6 text-gray-900">
                                    Nombre del Canal
                                </label>
                                <div class="mt-2">
                                    <input
                                        type="text"
                                        id="name"
                                        v-model="form.name"
                                        class="block w-full rounded-md border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6"
                                        :class="{ 'ring-red-500': form.errors.name }"
                                        required
                                    >
                                </div>
                                <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium leading-6 text-gray-900">
                                    Descripción
                                </label>
                                <div class="mt-2">
                                    <textarea
                                        id="description"
                                        v-model="form.description"
                                        rows="4"
                                        class="block w-full rounded-md border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6"
                                        :class="{ 'ring-red-500': form.errors.description }"
                                        required
                                    ></textarea>
                                </div>
                                <p v-if="form.errors.description" class="mt-2 text-sm text-red-600">{{ form.errors.description }}</p>
                            </div>
                        </div>
                    </form>

                    <!-- Footer -->
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 mt-4">
                        <button
                            type="submit"
                            @click="submit"
                            class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="form.processing"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ form.processing ? 'Guardando...' : 'Guardar Canal' }}
                        </button>
                        <button
                            type="button"
                            @click="closeModal"
                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template> 