<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { CalendarIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'dd.mm.yyyy' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const showCalendar = ref(false);
const containerRef = ref(null);

// Current view month/year for calendar navigation
const viewDate = ref(new Date());

const daysOfWeek = ['Po', 'Ut', 'Sr', 'Če', 'Pe', 'Su', 'Ne'];
const months = ['Januar', 'Februar', 'Mart', 'April', 'Maj', 'Juni', 'Juli', 'August', 'Septembar', 'Oktobar', 'Novembar', 'Decembar'];

// Parse modelValue to Date object
const selectedDate = computed(() => {
    if (!props.modelValue) return null;
    const [year, month, day] = props.modelValue.split('-').map(Number);
    return new Date(year, month - 1, day);
});

// Format date for display (dd.MM.yyyy)
const displayValue = computed(() => {
    if (!props.modelValue) return '';
    const [year, month, day] = props.modelValue.split('-');
    if (!year || !month || !day) return '';
    return `${day}.${month}.${year}`;
});

// Calendar grid data
const calendarDays = computed(() => {
    const year = viewDate.value.getFullYear();
    const month = viewDate.value.getMonth();
    
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    
    // Get day of week (0 = Sunday, adjust for Monday start)
    let startDay = firstDay.getDay() - 1;
    if (startDay < 0) startDay = 6;
    
    const days = [];
    
    // Previous month days
    const prevMonthLastDay = new Date(year, month, 0).getDate();
    for (let i = startDay - 1; i >= 0; i--) {
        days.push({
            day: prevMonthLastDay - i,
            month: month - 1,
            year: month === 0 ? year - 1 : year,
            isCurrentMonth: false,
        });
    }
    
    // Current month days
    for (let i = 1; i <= lastDay.getDate(); i++) {
        days.push({
            day: i,
            month: month,
            year: year,
            isCurrentMonth: true,
        });
    }
    
    // Next month days
    const remaining = 42 - days.length;
    for (let i = 1; i <= remaining; i++) {
        days.push({
            day: i,
            month: month + 1,
            year: month === 11 ? year + 1 : year,
            isCurrentMonth: false,
        });
    }
    
    return days;
});

const currentMonthYear = computed(() => {
    return `${months[viewDate.value.getMonth()]} ${viewDate.value.getFullYear()}`;
});

const isSelected = (dayObj) => {
    if (!selectedDate.value) return false;
    return dayObj.day === selectedDate.value.getDate() &&
           dayObj.month === selectedDate.value.getMonth() &&
           dayObj.year === selectedDate.value.getFullYear();
};

const isToday = (dayObj) => {
    const today = new Date();
    return dayObj.day === today.getDate() &&
           dayObj.month === today.getMonth() &&
           dayObj.year === today.getFullYear();
};

const selectDay = (dayObj) => {
    const month = (dayObj.month + 1).toString().padStart(2, '0');
    const day = dayObj.day.toString().padStart(2, '0');
    const year = dayObj.year;
    emit('update:modelValue', `${year}-${month}-${day}`);
    showCalendar.value = false;
};

const prevMonth = () => {
    viewDate.value = new Date(viewDate.value.getFullYear(), viewDate.value.getMonth() - 1, 1);
};

const nextMonth = () => {
    viewDate.value = new Date(viewDate.value.getFullYear(), viewDate.value.getMonth() + 1, 1);
};

const toggleCalendar = () => {
    if (props.disabled) return;
    showCalendar.value = !showCalendar.value;
    if (showCalendar.value && selectedDate.value) {
        viewDate.value = new Date(selectedDate.value);
    } else if (showCalendar.value) {
        viewDate.value = new Date();
    }
};

const handleInput = (event) => {
    const value = event.target.value;
    // Try to parse dd.mm.yyyy format
    const match = value.match(/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/);
    if (match) {
        const [, day, month, year] = match;
        const isoDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
        emit('update:modelValue', isoDate);
    }
};

// Close calendar when clicking outside
const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        showCalendar.value = false;
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
                @focus="toggleCalendar"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
                class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100"
            />
            <button
                type="button"
                @click="toggleCalendar"
                :disabled="disabled"
                class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
            >
                <CalendarIcon class="h-5 w-5" />
            </button>
        </div>
        
        <!-- Custom Calendar Dropdown -->
        <Teleport to="body">
            <div
                v-if="showCalendar"
                :style="{
                    position: 'fixed',
                    top: containerRef?.getBoundingClientRect().bottom + 4 + 'px',
                    left: containerRef?.getBoundingClientRect().left + 'px',
                    zIndex: 9999
                }"
                class="bg-white border border-gray-200 rounded-lg shadow-xl p-3 w-72"
            >
                <!-- Header -->
                <div class="flex items-center justify-between mb-3">
                    <button
                        type="button"
                        @click="prevMonth"
                        class="p-1.5 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <ChevronLeftIcon class="h-5 w-5 text-gray-600" />
                    </button>
                    <span class="font-semibold text-gray-800">{{ currentMonthYear }}</span>
                    <button
                        type="button"
                        @click="nextMonth"
                        class="p-1.5 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <ChevronRightIcon class="h-5 w-5 text-gray-600" />
                    </button>
                </div>
                
                <!-- Days of week header -->
                <div class="grid grid-cols-7 gap-1 mb-1">
                    <div
                        v-for="day in daysOfWeek"
                        :key="day"
                        class="text-center text-xs font-medium text-gray-500 py-1"
                    >
                        {{ day }}
                    </div>
                </div>
                
                <!-- Calendar grid -->
                <div class="grid grid-cols-7 gap-1">
                    <button
                        v-for="(dayObj, index) in calendarDays"
                        :key="index"
                        type="button"
                        @click="selectDay(dayObj)"
                        :class="[
                            'h-8 w-8 text-sm rounded-lg transition-colors flex items-center justify-center',
                            dayObj.isCurrentMonth ? 'text-gray-800' : 'text-gray-400',
                            isSelected(dayObj) ? 'bg-blue-500 text-white font-semibold' : 'hover:bg-gray-100',
                            isToday(dayObj) && !isSelected(dayObj) ? 'border-2 border-blue-500' : ''
                        ]"
                    >
                        {{ dayObj.day }}
                    </button>
                </div>
                
                <!-- Today button -->
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <button
                        type="button"
                        @click="selectDay({ day: new Date().getDate(), month: new Date().getMonth(), year: new Date().getFullYear(), isCurrentMonth: true })"
                        class="w-full py-1.5 text-sm text-blue-600 hover:bg-blue-50 rounded-lg transition-colors font-medium"
                    >
                        Danas
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>
