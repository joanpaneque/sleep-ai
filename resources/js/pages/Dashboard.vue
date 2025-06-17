<script setup>
import { useForm } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'

const form = useForm({})

const logout = () => {
    form.post('/logout')
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="max-w-md w-full space-y-8 p-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">
                    ¡Bienvenido!
                </h2>
                <p class="text-gray-600 mb-8">Has iniciado sesión correctamente.</p>
            </div>
            
            <div class="space-y-6">
                <Link 
                    href="/channels"
                    class="block w-full py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors duration-200 text-center"
                >
                    Empezar a trabajar
                </Link>

                <button
                    @click="logout"
                    class="w-full py-2 px-4 bg-red-600 hover:bg-red-700 text-white font-medium rounded-md transition-colors duration-200"
                >
                    Cerrar Sesión
                </button>
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