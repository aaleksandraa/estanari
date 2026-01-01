<script setup>
defineProps({
    totalKM: Number,
    totalEUR: Number,
    totalUSD: Number,
    label: String,
});

const formatCurrency = (amount, currency) => {
    return new Intl.NumberFormat('de-DE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount || 0) + ' ' + currency;
};
</script>

<template>
    <div class="flex items-center gap-6 rounded-lg border border-gray-200 bg-white px-6 py-4">
        <span v-if="label" class="text-sm font-medium text-gray-500">{{ label }}</span>
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-2">
                <div class="h-3 w-3 rounded-full bg-blue-500"></div>
                <span class="text-sm text-gray-500">KM:</span>
                <span class="font-semibold text-blue-600">{{ formatCurrency(totalKM, 'KM') }}</span>
            </div>
            <div class="h-4 w-px bg-gray-200"></div>
            <div class="flex items-center gap-2">
                <div class="h-3 w-3 rounded-full bg-green-500"></div>
                <span class="text-sm text-gray-500">EUR:</span>
                <span class="font-semibold text-green-600">{{ formatCurrency(totalEUR, 'EUR') }}</span>
            </div>
            <div class="h-4 w-px bg-gray-200"></div>
            <div class="flex items-center gap-2">
                <div class="h-3 w-3 rounded-full bg-purple-500"></div>
                <span class="text-sm text-gray-500">USD:</span>
                <span class="font-semibold text-purple-600">{{ formatCurrency(totalUSD, 'USD') }}</span>
            </div>
        </div>
    </div>
</template>
