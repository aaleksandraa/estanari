<script setup>
import { ExclamationTriangleIcon, CheckCircleIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Potvrda' },
    message: { type: String, default: 'Jeste li sigurni?' },
    confirmText: { type: String, default: 'Potvrdi' },
    cancelText: { type: String, default: 'Odustani' },
    variant: { type: String, default: 'warning' }, // warning, danger, success
    processing: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'cancel']);

const variantConfig = {
    warning: {
        icon: ExclamationTriangleIcon,
        iconBg: 'bg-amber-100',
        iconColor: 'text-amber-600',
        buttonBg: 'bg-amber-500 hover:bg-amber-600',
    },
    danger: {
        icon: TrashIcon,
        iconBg: 'bg-red-100',
        iconColor: 'text-red-600',
        buttonBg: 'bg-red-500 hover:bg-red-600',
    },
    success: {
        icon: CheckCircleIcon,
        iconBg: 'bg-green-100',
        iconColor: 'text-green-600',
        buttonBg: 'bg-green-500 hover:bg-green-600',
    },
};

const config = variantConfig[props.variant] || variantConfig.warning;
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="emit('cancel')"></div>
                
                <!-- Modal -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div v-if="show" class="relative w-full max-w-md transform rounded-xl bg-white shadow-2xl">
                            <!-- Close button -->
                            <button @click="emit('cancel')" class="absolute right-4 top-4 p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100">
                                <XMarkIcon class="h-5 w-5" />
                            </button>

                            <div class="p-6">
                                <!-- Icon -->
                                <div class="flex justify-center mb-4">
                                    <div :class="['flex h-14 w-14 items-center justify-center rounded-full', config.iconBg]">
                                        <component :is="config.icon" :class="['h-7 w-7', config.iconColor]" />
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="text-center">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ title }}</h3>
                                    <p class="text-sm text-gray-500">{{ message }}</p>
                                </div>

                                <!-- Slot for extra content -->
                                <slot></slot>

                                <!-- Actions -->
                                <div class="flex gap-3 mt-6">
                                    <button 
                                        @click="emit('cancel')" 
                                        :disabled="processing"
                                        class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50"
                                    >
                                        {{ cancelText }}
                                    </button>
                                    <button 
                                        @click="emit('confirm')" 
                                        :disabled="processing"
                                        :class="['flex-1 px-4 py-2.5 text-sm font-medium text-white rounded-lg disabled:opacity-50', config.buttonBg]"
                                    >
                                        {{ processing ? 'Obrada...' : confirmText }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
