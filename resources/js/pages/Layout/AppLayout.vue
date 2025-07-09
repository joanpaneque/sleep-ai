<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const page = usePage()
const sidebarOpen = ref(false)

const navigation = [
    {
        name: 'Dashboard',
        href: route('dashboard'),
        icon: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2v2zm0 0h18M7 21l4-4 4 4',
        current: route().current('dashboard')
    },
    {
        name: 'Canales',
        href: route('channels.index'),
        icon: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
        current: route().current('channels.*')
    },
    /*
    {
        name: 'Estadísticas',
        href: '#',
        icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        current: false
    },
    */
    {
        name: 'Almacenamiento',
        href: route('disk-usage.index'),
        icon: 'M4 7v10c0 2.21 1.79 4 4 4h8c2.21 0 4-1.79 4-4V7M4 7c0-2.21 1.79-4 4-4h8c2.21 0 4 1.79 4 4M4 7h16',
        current: route().current('disk-usage.*')
    },
    /*
    {
        name: 'Configuración',
        href: '#',
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        current: false
    }
    */
]

const user = computed(() => page.props.auth?.user)

const logout = () => {
    router.post(route('logout'))
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-gray-950 via-gray-900 to-gray-950">
        <!-- Mobile sidebar backdrop -->
        <div
            v-show="sidebarOpen"
            class="fixed inset-0 z-40 lg:hidden"
            @click="sidebarOpen = false"
        >
            <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm"></div>
        </div>

        <!-- Sidebar -->
        <div
            class="fixed inset-y-0 left-0 z-50 w-72 transform transition-transform duration-300 ease-in-out lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            <div class="flex h-full flex-col bg-gray-900/95 backdrop-blur-xl border-r border-gray-800/50">
                <!-- Logo -->
                <div class="flex h-16 shrink-0 items-center px-6 border-b border-gray-800/50">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-lg font-semibold text-white">Canal Studio</h1>
                            <p class="text-xs text-gray-400">Dashboard</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-6 space-y-2">
                    <div v-for="item in navigation" :key="item.name">
                        <a
                            :href="item.href"
                            :class="[
                                item.current
                                    ? 'bg-gradient-to-r from-blue-600/20 to-purple-600/20 border-blue-500/30 text-white shadow-lg shadow-blue-500/10'
                                    : 'text-gray-400 hover:text-white hover:bg-gray-800/50 border-transparent',
                                'group flex items-center px-4 py-3 text-sm font-medium rounded-xl border transition-all duration-200'
                            ]"
                        >
                            <svg
                                :class="[
                                    item.current ? 'text-blue-400' : 'text-gray-500 group-hover:text-gray-300',
                                    'mr-3 h-5 w-5 transition-colors duration-200'
                                ]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            {{ item.name }}
                        </a>
                    </div>
                </nav>

                <!-- User section -->
                <div class="border-t border-gray-800/50 p-4">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center">
                            <span class="text-white font-semibold text-sm">
                                {{ user?.name?.charAt(0).toUpperCase() || 'U' }}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">
                                {{ user?.name || 'Usuario' }}
                            </p>
                            <p class="text-xs text-gray-400 truncate">
                                {{ user?.email || 'email@example.com' }}
                            </p>
                        </div>
                    </div>

                    <button
                        @click="logout"
                        class="w-full flex items-center px-4 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-800/50 rounded-lg transition-all duration-200"
                    >
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Cerrar Sesión
                    </button>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="lg:pl-72">
            <!-- Mobile header -->
            <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-800/50 bg-gray-950/80 backdrop-blur-xl px-4 shadow-sm lg:hidden">
                <button
                    type="button"
                    class="-m-2.5 p-2.5 text-gray-400 hover:text-white"
                    @click="sidebarOpen = true"
                >
                    <span class="sr-only">Abrir sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <div class="flex items-center space-x-3">
                    <div class="w-6 h-6 bg-gradient-to-r from-blue-600 to-purple-600 rounded-md flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <span class="text-white font-semibold">Canal Studio</span>
                </div>
            </div>

            <!-- Page content -->
            <main class="min-h-screen">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
/* Custom scrollbar for sidebar */
nav::-webkit-scrollbar {
    width: 4px;
}

nav::-webkit-scrollbar-track {
    background: transparent;
}

nav::-webkit-scrollbar-thumb {
    background: rgba(107, 114, 128, 0.3);
    border-radius: 2px;
}

nav::-webkit-scrollbar-thumb:hover {
    background: rgba(107, 114, 128, 0.5);
}
</style>
