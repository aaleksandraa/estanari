<script setup>
import { XMarkIcon } from '@heroicons/vue/24/outline';

defineProps({
    show: Boolean,
    title: String,
    maxWidth: {
        type: String,
        default: 'md',
    },
});

defineEmits(['close']);

const maxWidthClass = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
};
</script>

<template>
    <Teleport to="body">
        <Transition leave-active-class="duration-200">
            <div v-show="show" class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50">
                <Transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-show="show" class="fixed inset-0 transform transition-all" @click="$emit('close')">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>
                </Transition>

                <Transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div
                        v-show="show"
                        :class="['mb-6 bg-white rounded-xl overflow-hidden shadow-xl transform transition-all sm:w-full sm:mx-auto', maxWidthClass[maxWidth]]"
                    >
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
                            <button @click="$emit('close')" class="p-1 rounded hover:bg-gray-100 transition-colors">
                                <XMarkIcon class="h-5 w-5 text-gray-400" />
                            </button>
                        </div>
                        <div class="p-6">
                            <slot />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
