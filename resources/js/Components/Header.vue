<script setup>
import { ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { BellIcon, MagnifyingGlassIcon, UserIcon } from '@heroicons/vue/24/outline';

defineProps({
    title: String,
});

const page = usePage();
const showUserMenu = ref(false);
</script>

<template>
    <header class="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-6">
        <h1 class="text-xl font-semibold text-gray-900">{{ title }}</h1>

        <div class="flex items-center gap-4">
            <!-- Search -->
            <div class="relative hidden md:block">
                <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <input
                    type="search"
                    placeholder="Pretraži..."
                    class="w-64 pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
            </div>

            <!-- Notifications -->
            <button class="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                <BellIcon class="h-5 w-5" />
                <span class="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white">
                    3
                </span>
            </button>

            <!-- User menu -->
            <div class="relative">
                <button
                    @click="showUserMenu = !showUserMenu"
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 text-white hover:bg-blue-600 transition-colors"
                >
                    <UserIcon class="h-4 w-4" />
                </button>

                <div
                    v-if="showUserMenu"
                    @click="showUserMenu = false"
                    class="fixed inset-0 z-10"
                ></div>

                <div
                    v-if="showUserMenu"
                    class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-20"
                >
                    <div class="px-4 py-2 border-b border-gray-100">
                        <p class="text-sm font-medium text-gray-900">{{ page.props.auth.user?.name }}</p>
                        <p class="text-xs text-gray-500">{{ page.props.auth.user?.email }}</p>
                    </div>
                    <a href="/settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profil</a>
                    <a href="/settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Postavke</a>
                    <hr class="my-1" />
                    <button
                        @click="router.post(route('logout'))"
                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50"
                    >
                        Odjavi se
                    </button>
                </div>
            </div>
        </div>
    </header>
</template>
