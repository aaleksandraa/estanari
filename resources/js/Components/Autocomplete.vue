<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { ChevronDownIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Odaberi...' },
    disabled: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
    labelKey: { type: String, default: 'name' },
    valueKey: { type: String, default: 'id' },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');
const inputRef = ref(null);
const dropdownRef = ref(null);
const highlightedIndex = ref(-1);

// Get selected option label
const selectedLabel = computed(() => {
    if (!props.modelValue) return '';
    const selected = props.options.find(opt => opt[props.valueKey] == props.modelValue);
    return selected ? selected[props.labelKey] : '';
});

// Filter options based on search
const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(opt => 
        opt[props.labelKey].toLowerCase().includes(query)
    );
});

// Display value in input
const displayValue = computed(() => {
    if (isOpen.value) return searchQuery.value;
    return selectedLabel.value;
});

const openDropdown = () => {
    if (props.disabled) return;
    isOpen.value = true;
    searchQuery.value = '';
    highlightedIndex.value = -1;
};

const closeDropdown = () => {
    isOpen.value = false;
    searchQuery.value = '';
    highlightedIndex.value = -1;
};

const selectOption = (option) => {
    emit('update:modelValue', option[props.valueKey]);
    closeDropdown();
};

const clearSelection = () => {
    emit('update:modelValue', '');
    searchQuery.value = '';
};

const handleInput = (event) => {
    searchQuery.value = event.target.value;
    highlightedIndex.value = 0;
    if (!isOpen.value) isOpen.value = true;
};

const handleKeydown = (event) => {
    if (!isOpen.value && (event.key === 'ArrowDown' || event.key === 'Enter')) {
        openDropdown();
        event.preventDefault();
        return;
    }

    if (!isOpen.value) return;

    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            highlightedIndex.value = Math.min(highlightedIndex.value + 1, filteredOptions.value.length - 1);
            break;
        case 'ArrowUp':
            event.preventDefault();
            highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
            break;
        case 'Enter':
            event.preventDefault();
            if (highlightedIndex.value >= 0 && filteredOptions.value[highlightedIndex.value]) {
                selectOption(filteredOptions.value[highlightedIndex.value]);
            }
            break;
        case 'Escape':
            closeDropdown();
            break;
    }
};

// Close on click outside
const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// Reset highlighted index when filtered options change
watch(filteredOptions, () => {
    if (highlightedIndex.value >= filteredOptions.value.length) {
        highlightedIndex.value = Math.max(0, filteredOptions.value.length - 1);
    }
});
</script>

<template>
    <div ref="dropdownRef" class="relative">
        <div class="relative">
            <input
                ref="inputRef"
                type="text"
                :value="displayValue"
                @input="handleInput"
                @focus="openDropdown"
                @keydown="handleKeydown"
                :placeholder="placeholder"
                :disabled="disabled"
                :required="required && !modelValue"
                class="w-full px-4 py-2 pr-16 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100"
            />
            <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                <button
                    v-if="modelValue && !disabled"
                    type="button"
                    @click.stop="clearSelection"
                    class="p-1 text-gray-400 hover:text-gray-600"
                >
                    <XMarkIcon class="h-4 w-4" />
                </button>
                <button
                    type="button"
                    @click.stop="isOpen ? closeDropdown() : openDropdown()"
                    :disabled="disabled"
                    class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50"
                >
                    <ChevronDownIcon class="h-4 w-4 transition-transform" :class="{ 'rotate-180': isOpen }" />
                </button>
            </div>
        </div>

        <!-- Dropdown -->
        <Teleport to="body">
            <div
                v-if="isOpen"
                :style="{
                    position: 'fixed',
                    top: dropdownRef?.getBoundingClientRect().bottom + 4 + 'px',
                    left: dropdownRef?.getBoundingClientRect().left + 'px',
                    width: dropdownRef?.getBoundingClientRect().width + 'px',
                    zIndex: 9999
                }"
                class="bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto"
            >
                <div v-if="filteredOptions.length === 0" class="px-4 py-3 text-sm text-gray-500">
                    Nema rezultata
                </div>
                <button
                    v-for="(option, index) in filteredOptions"
                    :key="option[valueKey]"
                    type="button"
                    @click="selectOption(option)"
                    @mouseenter="highlightedIndex = index"
                    :class="[
                        'w-full px-4 py-2 text-left text-sm transition-colors',
                        highlightedIndex === index ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50',
                        option[valueKey] == modelValue ? 'font-medium bg-blue-50' : ''
                    ]"
                >
                    {{ option[labelKey] }}
                </button>
            </div>
        </Teleport>
    </div>
</template>
