<script setup>
defineProps({
    title: String,
    value: String,
    subtitle: String,
    icon: Object,
    variant: {
        type: String,
        default: 'default',
    },
    clickable: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['click']);

const variantClasses = {
    default: {
        value: 'text-gray-900',
        icon: 'bg-gray-100 text-gray-500',
    },
    primary: {
        value: 'text-blue-600',
        icon: 'bg-blue-100 text-blue-600',
    },
    success: {
        value: 'text-green-600',
        icon: 'bg-green-100 text-green-600',
    },
    warning: {
        value: 'text-amber-600',
        icon: 'bg-amber-100 text-amber-600',
    },
    danger: {
        value: 'text-red-600',
        icon: 'bg-red-100 text-red-600',
    },
};
</script>

<template>
    <div 
        @click="clickable && $emit('click')"
        :class="[
            'rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-shadow',
            clickable ? 'cursor-pointer hover:shadow-md hover:border-gray-300' : 'hover:shadow-md'
        ]"
    >
        <div class="flex items-start justify-between">
            <div class="space-y-1">
                <p class="text-sm font-medium text-gray-500">{{ title }}</p>
                <p :class="['text-2xl font-bold tracking-tight', variantClasses[variant]?.value || variantClasses.default.value]">
                    {{ value }}
                </p>
                <p v-if="subtitle" class="text-xs text-gray-500">{{ subtitle }}</p>
            </div>
            <div :class="['flex h-10 w-10 items-center justify-center rounded-lg', variantClasses[variant]?.icon || variantClasses.default.icon]">
                <component :is="icon" class="h-5 w-5" />
            </div>
        </div>
    </div>
</template>
