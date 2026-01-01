<script setup>
import { WalletIcon } from '@heroicons/vue/24/outline';
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 p-4">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-xl shadow-lg p-8">
                <div class="text-center mb-8">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-500">
                        <WalletIcon class="h-6 w-6 text-white" />
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">e-Stanari</h1>
                    <p class="text-gray-500 mt-1">Sistem za upravljanje plaćanjima</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
