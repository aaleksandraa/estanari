<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircleIcon, XCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const show = ref(false);
const message = ref('');
const type = ref('success');

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            message.value = flash.success;
            type.value = 'success';
            show.value = true;
            setTimeout(() => (show.value = false), 4000);
        }
        if (flash?.error) {
            message.value = flash.error;
            type.value = 'error';
            show.value = true;
            setTimeout(() => (show.value = false), 4000);
        }
    },
    { immediate: true, deep: true }
);
</script>

<template>
    <Transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="show"
            class="fixed bottom-4 right-4 z-50 flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg"
            :class="type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'"
        >
            <CheckCircleIcon v-if="type === 'success'" class="h-5 w-5 text-green-500" />
            <XCircleIcon v-else class="h-5 w-5 text-red-500" />
            <span class="text-sm font-medium">{{ message }}</span>
            <button @click="show = false" class="ml-2 hover:opacity-70">
                <XMarkIcon class="h-4 w-4" />
            </button>
        </div>
    </Transition>
</template>
