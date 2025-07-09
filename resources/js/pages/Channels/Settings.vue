<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '../Layout/AppLayout.vue'

const props = defineProps({
    channel: {
        type: Object,
        required: true
    },
    success: {
        type: String,
        default: null
    },
    error: {
        type: String,
        default: null
    }
})

// Debug: Log channel data
console.log('Channel data:', props.channel)

const isTestingConnection = ref(false)
const testResult = ref(null)
const showTestResult = ref(false)
const isStartingOAuth = ref(false)

const hasOAuthTokens = computed(() => {
    const result = props.channel.google_access_token && props.channel.google_refresh_token
    console.log('hasOAuthTokens:', result, {
        access_token: !!props.channel.google_access_token,
        refresh_token: !!props.channel.google_refresh_token
    })
    return result
})

const tokenExpiresAt = computed(() => {
    if (!props.channel.google_access_token_expires_at) return null
    return new Date(props.channel.google_access_token_expires_at)
})

const isTokenExpired = computed(() => {
    if (!tokenExpiresAt.value) return false
    const expired = tokenExpiresAt.value < new Date()
    console.log('isTokenExpired:', expired, {
        expires_at: tokenExpiresAt.value,
        now: new Date()
    })
    return expired
})

const canTestConnection = computed(() => {
    return hasOAuthTokens.value && !isTokenExpired.value
})

const hasOAuthCredentials = computed(() => {
    const result = props.channel.google_client_id &&
           props.channel.google_client_secret &&
           props.channel.google_oauth_webhook_token
    console.log('hasOAuthCredentials:', result, {
        client_id: !!props.channel.google_client_id,
        client_secret: !!props.channel.google_client_secret,
        webhook_token: !!props.channel.google_oauth_webhook_token
    })
    return result
})

const needsAuthentication = computed(() => {
    const result = !hasOAuthTokens.value || isTokenExpired.value
    console.log('needsAuthentication:', result)
    return result
})

const formatDate = (date) => {
    if (!date) return 'No disponible'
    return new Intl.DateTimeFormat('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date)
}

const formatNumber = (num) => {
    if (!num) return '0'
    return new Intl.NumberFormat('es-ES').format(num)
}

const testConnection = async () => {
    isTestingConnection.value = true
    testResult.value = null
    showTestResult.value = false

    try {
        const response = await fetch(route('channels.test-connection', props.channel.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })

        const data = await response.json()
        testResult.value = data
        showTestResult.value = true

        // Auto-hide success messages after 5 seconds
        if (data.success) {
            setTimeout(() => {
                showTestResult.value = false
            }, 5000)
        }

    } catch (error) {
        console.error('Error testing connection:', error)
        testResult.value = {
            success: false,
            message: 'Error de conexión. Inténtalo de nuevo.',
            error: 'NETWORK_ERROR'
        }
        showTestResult.value = true
    } finally {
        isTestingConnection.value = false
    }
}

const startOAuth = async () => {
    isStartingOAuth.value = true

    try {
        const response = await fetch(route('channels.start-oauth', props.channel.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })

        const data = await response.json()

        if (data.success && data.auth_url) {
            // Redirect to Google OAuth
            window.location.href = data.auth_url
        } else {
            console.error('Error starting OAuth:', data.message)
            // You could show an error message here
            alert('Error al iniciar la autenticación: ' + data.message)
        }

    } catch (error) {
        console.error('Error starting OAuth:', error)
        alert('Error de conexión. Inténtalo de nuevo.')
    } finally {
        isStartingOAuth.value = false
    }
}
</script>

<template>
    <AppLayout>
        <!-- Header -->
        <div class="sticky top-0 z-30 bg-gray-950/80 backdrop-blur-xl border-b border-gray-800/50">
            <div class="max-w-8xl mx-auto px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 bg-gradient-to-r from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white mb-1">Configuración</h1>
                            <p class="text-gray-400">{{ channel.name }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-400">Canal ID</div>
                        <div class="text-white font-medium">#{{ channel.id }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-4xl mx-auto px-6 lg:px-8 py-8">
            <!-- Flash Messages -->
            <div v-if="success || error" class="mb-6">
                <!-- Success Message -->
                <div v-if="success" class="bg-green-500/10 border border-green-500/30 rounded-xl p-4 mb-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-green-300 font-medium">{{ success }}</p>
                    </div>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 mb-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-red-300 font-medium">{{ error }}</p>
                    </div>
                </div>
            </div>

            <!-- OAuth Connection Status -->
            <div class="bg-gradient-to-br from-gray-800/60 to-gray-900/40 border border-gray-700/50 rounded-2xl p-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold text-white">Estado de Conexión OAuth</h3>
                    <div v-if="hasOAuthTokens && !isTokenExpired" class="flex items-center px-3 py-1.5 bg-green-500/20 border border-green-500/30 rounded-full">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                        <span class="text-sm font-medium text-green-400">Conectado</span>
                    </div>
                    <div v-else-if="hasOAuthTokens && isTokenExpired" class="flex items-center px-3 py-1.5 bg-yellow-500/20 border border-yellow-500/30 rounded-full">
                        <div class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></div>
                        <span class="text-sm font-medium text-yellow-400">Expirado</span>
                    </div>
                    <div v-else class="flex items-center px-3 py-1.5 bg-red-500/20 border border-red-500/30 rounded-full">
                        <div class="w-2 h-2 bg-red-500 rounded-full mr-2"></div>
                        <span class="text-sm font-medium text-red-400">Desconectado</span>
                    </div>
                </div>

                <!-- Connection Status Details -->
                <div class="space-y-4">
                    <div v-if="hasOAuthTokens && !isTokenExpired" class="text-gray-300">
                        <p class="mb-2">✅ Tu canal está conectado correctamente con YouTube.</p>
                        <p class="text-sm text-gray-400">
                            Token válido hasta: {{ formatDate(tokenExpiresAt) }}
                        </p>
                    </div>
                    <div v-else-if="hasOAuthTokens && isTokenExpired" class="text-gray-300">
                        <p class="mb-2">⚠️ Tu token de acceso ha expirado.</p>
                        <p class="text-sm text-gray-400">
                            Expiró el: {{ formatDate(tokenExpiresAt) }}
                        </p>
                        <p class="text-sm text-gray-400 mt-2">
                            Necesitas volver a autorizar la aplicación para renovar el acceso.
                        </p>
                    </div>
                    <div v-else class="text-gray-300">
                        <p class="mb-2">❌ Este canal no está conectado con YouTube.</p>
                        <p class="text-sm text-gray-400">
                            Para conectar este canal, necesitas configurar las credenciales OAuth y completar el proceso de autorización.
                        </p>
                    </div>
                </div>

                <!-- Test Connection Button -->
                <div class="mt-6 pt-6 border-t border-gray-700/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-lg font-medium text-white mb-1">Comprobar Conexión</h4>
                            <p class="text-sm text-gray-400">
                                Verifica que la conexión con YouTube funciona correctamente
                            </p>
                        </div>
                        <button
                            @click="testConnection"
                            :disabled="!canTestConnection || isTestingConnection"
                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 disabled:from-gray-600 disabled:to-gray-700 disabled:cursor-not-allowed text-white text-sm font-medium rounded-xl transition-all duration-300 shadow-lg shadow-blue-600/25 hover:shadow-blue-600/40 disabled:shadow-none"
                        >
                            <svg v-if="isTestingConnection" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ isTestingConnection ? 'Comprobando...' : 'Comprobar Conexión' }}
                        </button>
                    </div>
                </div>

                <!-- OAuth Authentication Button -->
                <div class="mt-6 pt-6 border-t border-gray-700/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-lg font-medium text-white mb-1">Autenticación con Google</h4>
                            <p class="text-sm text-gray-400">
                                <span v-if="hasOAuthTokens && !isTokenExpired">Tu canal ya está conectado con Google</span>
                                <span v-else-if="hasOAuthTokens && isTokenExpired">Renueva tu autenticación con Google</span>
                                <span v-else>Conecta tu canal con Google para acceder a YouTube</span>
                            </p>
                        </div>
                        <button
                            @click="startOAuth"
                            :disabled="!hasOAuthCredentials || isStartingOAuth"
                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 disabled:from-gray-600 disabled:to-gray-700 disabled:cursor-not-allowed text-white text-sm font-medium rounded-xl transition-all duration-300 shadow-lg shadow-green-600/25 hover:shadow-green-600/40 disabled:shadow-none"
                        >
                            <svg v-if="isStartingOAuth" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span v-if="isStartingOAuth">Redirigiendo...</span>
                            <span v-else-if="hasOAuthTokens && !isTokenExpired">Reautenticar</span>
                            <span v-else-if="hasOAuthTokens && isTokenExpired">Renovar Autenticación</span>
                            <span v-else>Autenticar con Google</span>
                        </button>
                    </div>

                    <!-- Warning if credentials are missing -->
                    <div v-if="!hasOAuthCredentials" class="mt-4 p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-xl">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-yellow-400 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                            <div>
                                <h5 class="text-yellow-300 font-medium mb-1">Credenciales OAuth Requeridas</h5>
                                <p class="text-yellow-400 text-sm">
                                    Para autenticarte con Google, primero necesitas configurar:
                                </p>
                                <ul class="text-yellow-400 text-sm mt-2 space-y-1">
                                    <li v-if="!channel.google_client_id">• Client ID de Google</li>
                                    <li v-if="!channel.google_client_secret">• Client Secret de Google</li>
                                    <li v-if="!channel.google_oauth_webhook_token">• Token del Webhook OAuth</li>
                                </ul>
                                <p class="text-yellow-400 text-sm mt-2">
                                    Ve a <strong>Editar Canal</strong> para configurar estas credenciales.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Test Results -->
                <div v-if="showTestResult && testResult" class="mt-6">
                    <!-- Success Result -->
                    <div v-if="testResult.success" class="bg-green-500/10 border border-green-500/30 rounded-xl p-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <h4 class="text-lg font-medium text-green-400 mb-2">{{ testResult.message }}</h4>
                                <div v-if="testResult.data" class="space-y-3">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="bg-gray-900/50 rounded-lg p-4">
                                            <div class="text-sm text-gray-400 mb-1">Canal de YouTube</div>
                                            <div class="text-white font-medium">{{ testResult.data.youtube_channel_title }}</div>
                                        </div>
                                        <div class="bg-gray-900/50 rounded-lg p-4">
                                            <div class="text-sm text-gray-400 mb-1">ID del Canal</div>
                                            <div class="text-white font-mono text-sm">{{ testResult.data.youtube_channel_id }}</div>
                                        </div>
                                        <div class="bg-gray-900/50 rounded-lg p-4">
                                            <div class="text-sm text-gray-400 mb-1">Suscriptores</div>
                                            <div class="text-white font-medium">{{ formatNumber(testResult.data.subscriber_count) }}</div>
                                        </div>
                                        <div class="bg-gray-900/50 rounded-lg p-4">
                                            <div class="text-sm text-gray-400 mb-1">Videos</div>
                                            <div class="text-white font-medium">{{ formatNumber(testResult.data.video_count) }}</div>
                                        </div>
                                        <div class="bg-gray-900/50 rounded-lg p-4 md:col-span-2">
                                            <div class="text-sm text-gray-400 mb-1">Visualizaciones Totales</div>
                                            <div class="text-white font-medium">{{ formatNumber(testResult.data.view_count) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error Result -->
                    <div v-else class="bg-red-500/10 border border-red-500/30 rounded-xl p-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-lg font-medium text-red-400 mb-2">Error de Conexión</h4>
                                <p class="text-red-300">{{ testResult.message }}</p>
                                <div v-if="testResult.error" class="mt-2 text-sm text-red-400">
                                    Código de error: {{ testResult.error }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-700/50">
                <a
                    :href="route('channels.index')"
                    class="inline-flex items-center px-6 py-3 border border-gray-600/50 rounded-xl text-sm font-medium text-gray-300 bg-gray-700/30 hover:bg-gray-700/50 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver a Canales
                </a>
                <a
                    :href="route('channels.edit', channel.id)"
                    class="inline-flex items-center px-6 py-3 border border-transparent rounded-xl shadow-lg text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 hover:scale-105"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Editar Canal
                </a>
            </div>
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
a:focus-visible {
    outline: 2px solid rgb(59, 130, 246);
    outline-offset: 2px;
}
</style>
