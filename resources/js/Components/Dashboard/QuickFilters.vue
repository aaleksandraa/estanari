<script setup>
import {
    CalendarIcon,
    CalendarDaysIcon,
    FunnelIcon,
} from '@heroicons/vue/24/outline';

defineProps({
    activeFilter: String,
});

defineEmits(['change']);

const filters = [
    { value: 'today', label: 'Danas', icon: CalendarIcon },
    { value: '3days', label: '3 dana', icon: CalendarDaysIcon },
    { value: '7days', label: '7 dana', icon: CalendarDaysIcon },
    { value: 'custom', label: 'Period', icon: FunnelIcon },
];
</script>

<template>
    <div class="flex items-center gap-2">
        <button
            v-for="filter in filters"
            :key="filter.value"
            @click="$emit('change', filter.value)"
            :class="[
                'inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                activeFilter === filter.value
                    ? 'bg-blue-500 text-white shadow-sm'
                    : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
            ]"
        >
            <component :is="filter.icon" class="h-4 w-4" />
            {{ filter.label }}
        </button>
    </div>
</template>
