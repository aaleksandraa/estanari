<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { CalendarIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'mm.yyyy' },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const showPicker = ref(false);
const containerRef = ref(null);

const viewYear = ref(new Date().getFullYear());

const months = [
    { short: 'Jan', full: 'Januar', num: '01' },
    { short: 'Feb', full: 'Februar', num: '02' },
    { short: 'Mar', full: 'Mart', num: '03' },
    { short: 'Apr', full: 'April', num: '04' },
    { short: 'Maj', full: 'Maj', num: '05' },
    { short: 'Jun', full: 'Juni', num: '06' },
    { short: 'Jul', full: 'Juli', num: '07' },
    { short: 'Aug', full: 'August', num: '08' },
    { short: 'Sep', full: 'Septembar', num: '09' },
    { short: 'Okt', full: 'Oktobar', num: '10' },
    { short: 'Nov', full: 'Novembar', num: '11' },
    { short: 'Dec', full: 'Decembar', num: '12' },
];

// Parse modelValue
const selectedMonth = computed(() => {
    if (!props.modelValue) return null;
    const [year, month] = props.modelValue.split('-');
    return { year: parseInt(year), month: parseInt(month) };
});

// Format for display (MM.yyyy)
const displayValue = computed(() => {
    if (!props.modelValue) return '';
    const [year, month] = props.modelValue.split('-');
    if (!year || !month) return '';
    return `${month}.${year}`;
});

const isSelected = (monthNum) => {
    if (!selectedMonth.value) return false;
    return selectedMonth.value.month === parseInt(monthNum) && selectedMonth.value.year === viewYear.value;
};

const isCurrentMonth = (monthNum) => {
    const now = new Date();
    return now.getMonth() + 1 === parseInt(monthNum) && now.getFullYear() === viewYear.value;
};

const selectMonth = (monthNum) => {
    emit('update:modelValue', `${viewYear.value}-${monthNum}`);
    showPicker.value = false;
};

const prevYear = () => {
    viewYear.value--;
};

const nextYear = () => {
    viewYear.value++;
};

const togglePicker = () => {
    if (props.disabled) return;
    showPicker.value = !showPicker.value;
    if (showPicker.value && selectedMonth.value) {
        viewYear.value = selectedMonth.value.year;
    } else if (showPicker.value) {
        viewYear.value = new Date().getFullYear();
    }
};

const handleInput = (event) => {
    const value = event.target.value;
    const match = value.match(/^(\d{1,2})\.(\d{4})$/);
    if (match) {
        const [, month, year] = match;
        emit('update:modelValue', `${year}-${month.padStart(2, '0')}`);
    }
};

const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        showPicker.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="containerRef" class="relative">
        <div class="relative">
            <input
                type="text"
                :value="displayValue"
                @input="handleInput"
                @focus="togglePicker"
                :placeholder="placeholder"
                :disabled="disabled"
                class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100"
            />
            <button
                type="button"
                @click="togglePicker"
                :disabled="disabled"
                class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
            >
                <CalendarIcon class="h-5 w-5" />
            </button>
        </div>
        
        <!-- Month Picker Dropdown -->
        <Teleport to="body">
            <div
                v-if="showPicker"
                :style="{
                    position: 'fixed',
                    top: containerRef?.getBoundingClientRect().bottom + 4 + 'px',
                    left: containerRef?.getBoundingClientRect().left + 'px',
                    zIndex: 9999
                }"
                class="bg-white border border-gray-200 rounded-lg shadow-xl p-3 w-64"
            >
                <!-- Year navigation -->
                <div class="flex items-center justify-between mb-3">
                    <button
                        type="button"
                        @click="prevYear"
                        class="p-1.5 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <ChevronLeftIcon class="h-5 w-5 text-gray-600" />
                    </button>
                    <span class="font-semibold text-gray-800">{{ viewYear }}</span>
                    <button
                        type="button"
                        @click="nextYear"
                        class="p-1.5 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <ChevronRightIcon class="h-5 w-5 text-gray-600" />
                    </button>
                </div>
                
                <!-- Months grid -->
                <div class="grid grid-cols-3 gap-2">
                    <button
                        v-for="month in months"
                        :key="month.num"
                        type="button"
                        @click="selectMonth(month.num)"
                        :class="[
                            'py-2 px-3 text-sm rounded-lg transition-colors',
                            isSelected(month.num) ? 'bg-blue-500 text-white font-semibold' : 'text-gray-700 hover:bg-gray-100',
                            isCurrentMonth(month.num) && !isSelected(month.num) ? 'border-2 border-blue-500' : ''
                        ]"
                    >
                        {{ month.short }}
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>
