<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({})

const logout = () => {
    form.post('/logout')
}
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
                    </div>
                    <div class="flex items-center">
                        <button
                            @click="logout"
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            Cerrar Sesión
                        </button>
                    </div>
                </div>
            </div>
        </nav>
        
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <div class="border-4 border-dashed border-gray-200 rounded-lg h-96 flex items-center justify-center">
                    <div class="text-center">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">¡Bienvenido!</h2>
                        <p class="text-gray-600">Has iniciado sesión correctamente.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template> 