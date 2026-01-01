<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Header from '@/Components/Header.vue';
import Toast from '@/Components/Toast.vue';

const sidebarCollapsed = ref(false);
const page = usePage();
</script>

<template>
    <div class="flex h-screen bg-gray-50">
        <Sidebar :collapsed="sidebarCollapsed" @toggle="sidebarCollapsed = !sidebarCollapsed" />
        
        <main class="flex-1 overflow-auto">
            <slot />
        </main>

        <Toast />
    </div>
</template>
