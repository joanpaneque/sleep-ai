<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post('/login')
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-gradient-to-br from-gray-800/60 to-gray-900/40 backdrop-blur-sm p-8 rounded-2xl border border-gray-700/50 shadow-2xl">
            <!-- Logo/Header -->
            <div class="text-center">
                <div class="w-16 h-16 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg mx-auto mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </div>
                <h2 class="text-4xl font-extrabold text-white mb-2">
                    Sleep AI
                </h2>
                <p class="text-lg text-gray-400">
                    Optimiza tu contenido mientras duermes
                </p>
            </div>

            <form class="mt-8 space-y-6" @submit.prevent="submit">
                <!-- Email -->
                <div class="space-y-2">
                    <label for="email" class="block text-sm font-medium text-gray-300">
                        Correo electrónico
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                        </div>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            class="block w-full pl-10 pr-3 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-white placeholder-gray-400 focus:border-indigo-500/50 focus:bg-gray-700/70 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200"
                            placeholder="tu@email.com"
                        >
                    </div>
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-400">
                        {{ form.errors.email }}
                    </p>
                </div>

                <!-- Password -->
                <div class="space-y-2">
                    <label for="password" class="block text-sm font-medium text-gray-300">
                        Contraseña
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            class="block w-full pl-10 pr-3 py-3 bg-gray-700/50 border border-gray-600/50 rounded-lg text-white placeholder-gray-400 focus:border-indigo-500/50 focus:bg-gray-700/70 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200"
                            placeholder="••••••••"
                        >
                    </div>
                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-400">
                        {{ form.errors.password }}
                    </p>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center">
                    <input
                        id="remember"
                        v-model="form.remember"
                        type="checkbox"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 bg-gray-700/50 border-gray-600/50 rounded cursor-pointer"
                    >
                    <label for="remember" class="ml-2 block text-sm text-gray-300 cursor-pointer">
                        Recordarme
                    </label>
                </div>

                <!-- Submit Button -->
                <div>
                    <button
                        type="submit"
                        class="group relative w-full flex justify-center py-3 px-4 bg-gray-700/50 hover:bg-indigo-500/20 border border-gray-600/50 hover:border-indigo-500/50 rounded-lg transition-all duration-200 shadow-sm hover:shadow-lg hover:shadow-indigo-500/20 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                        :disabled="form.processing"
                    >
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg
                                v-if="!form.processing"
                                class="h-5 w-5 text-gray-300 group-hover:text-indigo-400 transition-colors duration-200"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                            <svg
                                v-else
                                class="animate-spin h-5 w-5 text-gray-300"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                        <span class="text-sm font-medium text-gray-300 group-hover:text-indigo-400 transition-colors duration-200">
                            {{ form.processing ? 'Iniciando sesión...' : 'Iniciar sesión' }}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
/* Enhanced focus states for accessibility */
input:focus-visible,
button:focus-visible {
    outline: 2px solid rgb(59, 130, 246);
    outline-offset: 2px;
}
</style>
