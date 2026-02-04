<script setup>
import { ref, computed } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import Modal from '@/Components/Modal.vue';
import DateInput from '@/Components/DateInput.vue';
import { format, parseISO, isWithinInterval, startOfDay, endOfDay } from 'date-fns';
import {
    CalendarIcon, EllipsisHorizontalIcon, EyeIcon, TrashIcon, ArrowDownTrayIcon,
    DocumentArrowDownIcon, ClipboardDocumentListIcon, CheckCircleIcon,
    MagnifyingGlassIcon, FunnelIcon, XMarkIcon, PlusIcon
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon as CheckCircleSolidIcon } from '@heroicons/vue/24/solid';
import { useTranslations } from '@/composables/useTranslations';

const { __ } = useTranslations();

const props = defineProps({ plans: Array });
const page = usePage();
const openMenuId = ref(null);
const processing = ref(false);

// Create Plan Modal state
const showCreatePlanModal = ref(false);
const createPlanForm = useForm({
    name: '',
    description: '',
    scheduled_date: new Date().toISOString().split('T')[0],
});

// Filter states
const searchQuery = ref('');
const statusFilter = ref('all'); // all, paid, unpaid
const selectedDate = ref('');
const showPeriodPicker = ref(false);
const dateFrom = ref('');
const dateTo = ref('');

// Filtered plans computed property
const filteredPlans = computed(() => {
    let filtered = [...props.plans];

    // Search by name or description
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(plan => 
            plan.name.toLowerCase().includes(query) ||
            (plan.description && plan.description.toLowerCase().includes(query))
        );
    }

    // Filter by status
    if (statusFilter.value === 'paid') {
        filtered = filtered.filter(plan => plan.is_paid);
    } else if (statusFilter.value === 'unpaid') {
        filtered = filtered.filter(plan => !plan.is_paid);
    }

    // Filter by single date
    if (selectedDate.value && !showPeriodPicker.value) {
        filtered = filtered.filter(plan => {
            if (!plan.scheduled_date) return false;
            const scheduledDate = startOfDay(typeof plan.scheduled_date === 'string' ? parseISO(plan.scheduled_date) : plan.scheduled_date);
            const filterDate = startOfDay(parseISO(selectedDate.value));
            return scheduledDate.getTime() === filterDate.getTime();
        });
    }

    // Filter by date range (period)
    if (showPeriodPicker.value && (dateFrom.value || dateTo.value)) {
        filtered = filtered.filter(plan => {
            if (!plan.scheduled_date) return false;
            
            const scheduledDate = startOfDay(typeof plan.scheduled_date === 'string' ? parseISO(plan.scheduled_date) : plan.scheduled_date);
            
            if (dateFrom.value && dateTo.value) {
                const from = startOfDay(parseISO(dateFrom.value));
                const to = endOfDay(parseISO(dateTo.value));
                return isWithinInterval(scheduledDate, { start: from, end: to });
            } else if (dateFrom.value) {
                const from = startOfDay(parseISO(dateFrom.value));
                return scheduledDate >= from;
            } else if (dateTo.value) {
                const to = endOfDay(parseISO(dateTo.value));
                return scheduledDate <= to;
            }
            
            return true;
        });
    }

    return filtered;
});

const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
    selectedDate.value = '';
    showPeriodPicker.value = false;
    dateFrom.value = '';
    dateTo.value = '';
};

const hasActiveFilters = computed(() => {
    return searchQuery.value.trim() !== '' || 
           statusFilter.value !== 'all' || 
           selectedDate.value !== '' ||
           dateFrom.value !== '' || 
           dateTo.value !== '';
});

// Confirm modal state
const showConfirmModal = ref(false);
const confirmAction = ref(null);
const confirmPlan = ref(null);
const confirmConfig = ref({ title: '', message: '', variant: 'warning', confirmText: 'Potvrdi' });

// Edit Plan Modal state (with scheduled_date)
const showEditPlanModal = ref(false);
const editingPlan = ref(null);
const editPlanForm = useForm({
    name: '',
    description: '',
    scheduled_date: '',
});

// Change paid date modal state
const showChangePaidDateModal = ref(false);
const changePaidDateForm = useForm({
    paid_date: '',
});

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    try { return format(typeof dateStr === 'string' ? parseISO(dateStr) : dateStr, 'dd.MM.yyyy HH:mm'); }
    catch { return dateStr; }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try { return format(typeof dateStr === 'string' ? parseISO(dateStr) : dateStr, 'dd.MM.yyyy'); }
    catch { return dateStr; }
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);
};

const getMenuPosition = (planId) => {
    const button = document.querySelector(`[data-menu-button="${planId}"]`);
    if (!button) return { top: '0px', left: '0px' };
    
    const rect = button.getBoundingClientRect();
    let top = rect.bottom + 4;
    let left = rect.right - 208; // 208px = 52 * 4 (w-52)
    
    // Check if menu would go off bottom of screen
    if (top + 250 > window.innerHeight) {
        top = rect.top - 254; // Position above button
    }
    
    // Check if menu would go off left of screen
    if (left < 8) {
        left = 8;
    }
    
    return { top: top + 'px', left: left + 'px' };
};

const viewPlan = (plan) => {
    router.get(route('plans.show', plan.id));
    openMenuId.value = null;
};

const openEditPlanModal = (plan) => {
    editingPlan.value = plan;
    editPlanForm.name = plan.name;
    editPlanForm.description = plan.description || '';
    // Convert scheduled_date to YYYY-MM-DD format for date input
    if (plan.scheduled_date) {
        try {
            const date = typeof plan.scheduled_date === 'string' ? parseISO(plan.scheduled_date) : plan.scheduled_date;
            editPlanForm.scheduled_date = format(date, 'yyyy-MM-dd');
        } catch {
            editPlanForm.scheduled_date = new Date().toISOString().split('T')[0];
        }
    } else {
        editPlanForm.scheduled_date = new Date().toISOString().split('T')[0];
    }
    showEditPlanModal.value = true;
    openMenuId.value = null;
};

const submitEditPlan = () => {
    if (!editingPlan.value) return;
    
    editPlanForm.put(route('plans.update', editingPlan.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditPlanModal.value = false;
            editingPlan.value = null;
            editPlanForm.reset();
        },
    });
};

const closeEditModal = () => {
    showEditPlanModal.value = false;
    editingPlan.value = null;
    editPlanForm.reset();
};

const openChangePaidDateModal = (plan) => {
    confirmPlan.value = plan;
    // Convert paid_at to YYYY-MM-DD format for date input
    if (plan.paid_at) {
        const date = typeof plan.paid_at === 'string' ? parseISO(plan.paid_at) : plan.paid_at;
        changePaidDateForm.paid_date = format(date, 'yyyy-MM-dd');
    }
    showChangePaidDateModal.value = true;
    openMenuId.value = null;
};

const handleChangePaidDateSubmit = () => {
    if (!confirmPlan.value) return;
    
    changePaidDateForm.post(route('plans.change-paid-date', confirmPlan.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showChangePaidDateModal.value = false;
            confirmPlan.value = null;
            changePaidDateForm.reset();
        },
    });
};

const handleChangePaidDateCancel = () => {
    showChangePaidDateModal.value = false;
    confirmPlan.value = null;
    changePaidDateForm.reset();
};

const openDeleteConfirm = (plan) => {
    confirmPlan.value = plan;
    confirmAction.value = 'delete';
    confirmConfig.value = {
        title: __('delete') + ' ' + __('save_plan').toLowerCase(),
        message: __('delete_plan_confirm').replace('{name}', plan.name),
        variant: 'danger',
        confirmText: __('delete'),
    };
    showConfirmModal.value = true;
    openMenuId.value = null;
};

const openMarkAsPaidConfirm = (plan) => {
    confirmPlan.value = plan;
    confirmAction.value = 'markAsPaid';
    confirmConfig.value = {
        title: __('mark_as_paid'),
        message: __('mark_plan_as_paid_confirm').replace('{name}', plan.name).replace('{count}', plan.payment_count),
        variant: 'success',
        confirmText: __('mark_as_paid'),
    };
    showConfirmModal.value = true;
    openMenuId.value = null;
};

const handleConfirm = () => {
    if (!confirmPlan.value) return;
    processing.value = true;

    if (confirmAction.value === 'delete') {
        router.delete(route('plans.destroy', confirmPlan.value.id), {
            onFinish: () => {
                processing.value = false;
                showConfirmModal.value = false;
                confirmPlan.value = null;
            },
        });
    } else if (confirmAction.value === 'markAsPaid') {
        router.post(route('plans.mark-paid', confirmPlan.value.id), {}, {
            onFinish: () => {
                processing.value = false;
                showConfirmModal.value = false;
                confirmPlan.value = null;
            },
        });
    }
};

const handleCancel = () => {
    showConfirmModal.value = false;
    confirmPlan.value = null;
    confirmAction.value = null;
};

const exportCsv = (plan) => {
    window.location.href = route('plans.export-csv', plan.id);
    openMenuId.value = null;
};

const exportPdf = (plan) => {
    window.open(route('plans.export-pdf', plan.id), '_blank');
    openMenuId.value = null;
};

const exportExcel = (plan) => {
    window.location.href = route('plans.export-excel', plan.id);
    openMenuId.value = null;
};

const getDateFilterLabel = (filter) => {
    const labels = {
        today: __('today'),
        tomorrow: __('tomorrow'),
        '3days': __('3days'),
        '7days': __('7days'),
        period: __('period'),
        all: __('all'),
        custom: __('custom')
    };
    return labels[filter] || filter;
};

const submitCreatePlan = () => {
    createPlanForm.post(route('plans.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreatePlanModal.value = false;
            createPlanForm.reset();
            createPlanForm.scheduled_date = new Date().toISOString().split('T')[0];
        },
    });
};
</script>

<template>
    <MainLayout>
        <Header :title="__('saved_plans')" />
        <div class="p-6 space-y-6">
            <!-- Create New Plan Button -->
            <div v-if="page.props.auth.user?.canModify" class="flex justify-end">
                <button
                    @click="showCreatePlanModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 transition-colors"
                >
                    <PlusIcon class="h-5 w-5" />
                    {{ __('create_new_plan') }}
                </button>
            </div>

            <!-- Filters Section - Single Row -->
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search -->
                    <div class="flex-1 min-w-[250px] relative">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="__('search') + '...'"
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                        />
                    </div>

                    <!-- Status Filter -->
                    <select
                        v-model="statusFilter"
                        class="min-w-[160px] px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white"
                    >
                        <option value="all">{{ __('all_statuses') }}</option>
                        <option value="paid">{{ __('paid_status') }}</option>
                        <option value="unpaid">{{ __('unpaid') }}</option>
                    </select>

                    <!-- Single Date Picker (when period is not active) -->
                    <DateInput
                        v-if="!showPeriodPicker"
                        v-model="selectedDate"
                        :placeholder="__('date')"
                        class="min-w-[160px]"
                    />

                    <!-- Period Button -->
                    <button
                        @click="showPeriodPicker = !showPeriodPicker"
                        :class="[
                            'px-4 py-2.5 text-sm font-medium rounded-lg transition-colors whitespace-nowrap',
                            showPeriodPicker 
                                ? 'bg-blue-500 text-white hover:bg-blue-600' 
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        {{ __('period') }}
                    </button>

                    <!-- Period Date Pickers (when period is active) -->
                    <template v-if="showPeriodPicker">
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600 whitespace-nowrap">{{ __('from') }}:</span>
                            <DateInput
                                v-model="dateFrom"
                                :placeholder="__('date')"
                            />
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600 whitespace-nowrap">{{ __('to') }}:</span>
                            <DateInput
                                v-model="dateTo"
                                :placeholder="__('date')"
                            />
                        </div>
                    </template>

                    <!-- Clear Filters Button -->
                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors"
                    >
                        <XMarkIcon class="h-4 w-4" />
                        {{ __('clear') }}
                    </button>

                    <!-- Results Count -->
                    <div class="ml-auto text-sm font-medium text-gray-600 whitespace-nowrap">
                        {{ filteredPlans.length }} {{ filteredPlans.length === 1 ? __('plan') : __('plans') }}
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredPlans.length === 0 && !hasActiveFilters" class="flex flex-col items-center justify-center py-16 text-gray-500">
                <ClipboardDocumentListIcon class="h-16 w-16 mb-4 opacity-40" />
                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('no_saved_plans') }}</h3>
                <p class="text-sm text-center max-w-md">
                    {{ __('no_saved_plans_desc') }}
                </p>
            </div>

            <!-- No Results State -->
            <div v-else-if="filteredPlans.length === 0 && hasActiveFilters" class="flex flex-col items-center justify-center py-16 text-gray-500">
                <FunnelIcon class="h-16 w-16 mb-4 opacity-40" />
                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('no_results') }}</h3>
                <p class="text-sm text-center max-w-md mb-4">
                    {{ __('no_results_desc') }}
                </p>
                <button
                    @click="clearFilters"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600"
                >
                    {{ __('clear_filters') }}
                </button>
            </div>

            <!-- Plans Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="plan in filteredPlans" :key="plan.id" 
                    :class="['group relative rounded-xl border overflow-hidden hover:shadow-lg transition-all cursor-pointer',
                        plan.is_paid ? 'border-green-200 bg-green-50/30' : 'border-gray-200 bg-white hover:border-blue-200']"
                    @click="viewPlan(plan)">

                    <!-- Card Header -->
                    <div class="p-5 pb-3">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                <h3 :class="['font-semibold truncate transition-colors', plan.is_paid ? 'text-green-800' : 'text-gray-900 group-hover:text-blue-600']">
                                    {{ plan.name }}
                                </h3>
                                <!-- Paid Badge - inline with title -->
                                <span v-if="plan.is_paid" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 whitespace-nowrap flex-shrink-0">
                                    <CheckCircleSolidIcon class="h-3.5 w-3.5" /> {{ __('paid_status') }}
                                </span>
                            </div>
                            <!-- Menu -->
                            <div class="relative flex-shrink-0" @click.stop>
                                <button 
                                    :data-menu-button="plan.id"
                                    @click="openMenuId = openMenuId === plan.id ? null : plan.id" 
                                    class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                                    <EllipsisHorizontalIcon class="h-5 w-5 text-gray-400" />
                                </button>
                            </div>
                        </div>
                        <p v-if="plan.description" class="text-sm text-gray-500 mt-1 line-clamp-2">
                            {{ plan.description }}
                        </p>
                    </div>

                    <!-- Stats -->
                    <div :class="['px-5 py-3 border-t', plan.is_paid ? 'bg-green-50/50 border-green-100' : 'bg-gradient-to-r from-gray-50 to-white border-gray-100']">
                        <div class="grid grid-cols-3 gap-3">
                            <div class="text-center">
                                <p :class="['text-lg font-bold', plan.is_paid ? 'text-green-600' : 'text-blue-600']">{{ formatCurrency(plan.total_km) }}</p>
                                <p class="text-xs text-gray-500">KM</p>
                            </div>
                            <div class="text-center border-x border-gray-200">
                                <p class="text-lg font-bold text-green-600">{{ formatCurrency(plan.total_eur) }}</p>
                                <p class="text-xs text-gray-500">EUR</p>
                            </div>
                            <div class="text-center">
                                <p class="text-lg font-bold text-gray-700">{{ plan.payment_count }}</p>
                                <p class="text-xs text-gray-500">{{ __('payments') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div :class="['px-5 py-3 border-t', plan.is_paid ? 'bg-green-50 border-green-100' : 'bg-gray-50 border-gray-100']">
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <div class="flex items-center gap-1">
                                <CalendarIcon class="h-3.5 w-3.5" />
                                <span>{{ getDateFilterLabel(plan.date_filter) }}</span>
                            </div>
                            <span v-if="plan.is_paid && plan.paid_at" class="text-green-600">
                                {{ __('paid_at') }} {{ formatDate(plan.paid_at) }}
                            </span>
                            <span v-else>{{ formatDateTime(plan.created_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dropdown Menu with Teleport -->
        <Teleport to="body">
            <div v-if="openMenuId !== null">
                <div @click="openMenuId = null" class="fixed inset-0 z-30"></div>
                <div v-for="plan in filteredPlans" :key="'menu-' + plan.id">
                    <div v-if="openMenuId === plan.id" 
                        class="fixed w-52 bg-white rounded-lg shadow-xl border border-gray-200 py-1 z-40"
                        :style="getMenuPosition(plan.id)">
                        <button @click="viewPlan(plan)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <EyeIcon class="h-4 w-4" /> {{ __('view_payment') }}
                        </button>
                        <button v-if="page.props.auth.user?.canModify" @click="openEditPlanModal(plan)" 
                            class="w-full flex items-center gap-2 px-4 py-2 text-sm text-blue-600 hover:bg-blue-50">
                            <CalendarIcon class="h-4 w-4" /> {{ __('edit_plan') }}
                        </button>
                        <button v-if="plan.is_paid && page.props.auth.user?.canModify" @click="openChangePaidDateModal(plan)" 
                            class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <CalendarIcon class="h-4 w-4" /> {{ __('change_paid_date') }}
                        </button>
                        <button v-if="!plan.is_paid && page.props.auth.user?.canModify" @click="openMarkAsPaidConfirm(plan)" 
                            class="w-full flex items-center gap-2 px-4 py-2 text-sm text-green-600 hover:bg-green-50">
                            <CheckCircleIcon class="h-4 w-4" /> {{ __('mark_as_paid') }}
                        </button>
                        <hr class="my-1" />
                        <button @click="exportCsv(plan)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <ArrowDownTrayIcon class="h-4 w-4" /> {{ __('export_csv') }}
                        </button>
                        <button @click="exportPdf(plan)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <DocumentArrowDownIcon class="h-4 w-4" /> {{ __('export_pdf') }}
                        </button>
                        <button @click="exportExcel(plan)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <DocumentArrowDownIcon class="h-4 w-4" /> {{ __('export_excel') }}
                        </button>
                        <hr class="my-1" />
                        <button v-if="page.props.auth.user?.canModify" @click="openDeleteConfirm(plan)" 
                            class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                            <TrashIcon class="h-4 w-4" /> {{ __('delete') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Confirm Modal -->
        <ConfirmModal
            :show="showConfirmModal"
            :title="confirmConfig.title"
            :message="confirmConfig.message"
            :variant="confirmConfig.variant"
            :confirmText="confirmConfig.confirmText"
            :processing="processing"
            @confirm="handleConfirm"
            @cancel="handleCancel"
        />

        <!-- Edit Plan Modal (with scheduled_date) -->
        <Modal :show="showEditPlanModal" @close="closeEditModal" max-width="lg">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('edit_plan') }}</h2>
                
                <form @submit.prevent="submitEditPlan" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('plan_name') }} <span class="text-red-500">*</span>
                        </label>
                        <input 
                            v-model="editPlanForm.name" 
                            type="text" 
                            required
                            maxlength="255"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :placeholder="__('plan_name_placeholder')"
                        />
                        <p v-if="editPlanForm.errors.name" class="mt-1 text-sm text-red-600">{{ editPlanForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('description') }} ({{ __('optional') }})
                        </label>
                        <textarea 
                            v-model="editPlanForm.description" 
                            rows="3"
                            maxlength="1000"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :placeholder="__('plan_description_placeholder')"
                        ></textarea>
                        <p v-if="editPlanForm.errors.description" class="mt-1 text-sm text-red-600">{{ editPlanForm.errors.description }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('payment_date') }} <span class="text-red-500">*</span>
                        </label>
                        <DateInput
                            v-model="editPlanForm.scheduled_date"
                            :placeholder="__('select_payment_date')"
                            required
                        />
                        <p v-if="editPlanForm.errors.scheduled_date" class="mt-1 text-sm text-red-600">{{ editPlanForm.errors.scheduled_date }}</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button 
                            type="button" 
                            @click="closeEditModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            {{ __('cancel') }}
                        </button>
                        <button 
                            type="submit" 
                            :disabled="editPlanForm.processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50"
                        >
                            {{ editPlanForm.processing ? __('saving') : __('save_changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Change Paid Date Modal -->
        <Modal :show="showChangePaidDateModal" @close="handleChangePaidDateCancel" max-width="md">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Promijeni datum plaćanja</h2>
                
                <form @submit.prevent="handleChangePaidDateSubmit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('payment_date') }}
                        </label>
                        <DateInput
                            v-model="changePaidDateForm.paid_date"
                            :placeholder="__('select_payment_date')"
                            required
                        />
                        <p v-if="changePaidDateForm.errors.paid_date" class="mt-1 text-sm text-red-600">{{ changePaidDateForm.errors.paid_date }}</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button 
                            type="button" 
                            @click="handleChangePaidDateCancel"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            {{ __('cancel') }}
                        </button>
                        <button 
                            type="submit" 
                            :disabled="changePaidDateForm.processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600 disabled:opacity-50"
                        >
                            {{ changePaidDateForm.processing ? __('saving') : __('save') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Create Plan Modal -->
        <Modal :show="showCreatePlanModal" @close="showCreatePlanModal = false" max-width="lg">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('create_new_plan') }}</h2>
                
                <form @submit.prevent="submitCreatePlan" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('plan_name') }} <span class="text-red-500">*</span>
                        </label>
                        <input 
                            v-model="createPlanForm.name" 
                            type="text" 
                            required
                            maxlength="255"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :placeholder="__('plan_name_placeholder')"
                        />
                        <p v-if="createPlanForm.errors.name" class="mt-1 text-sm text-red-600">{{ createPlanForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('description') }} ({{ __('optional') }})
                        </label>
                        <textarea 
                            v-model="createPlanForm.description" 
                            rows="3"
                            maxlength="1000"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :placeholder="__('plan_description_placeholder')"
                        ></textarea>
                        <p v-if="createPlanForm.errors.description" class="mt-1 text-sm text-red-600">{{ createPlanForm.errors.description }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ __('payment_date') }} <span class="text-red-500">*</span>
                        </label>
                        <DateInput
                            v-model="createPlanForm.scheduled_date"
                            :placeholder="__('select_payment_date')"
                            required
                        />
                        <p v-if="createPlanForm.errors.scheduled_date" class="mt-1 text-sm text-red-600">{{ createPlanForm.errors.scheduled_date }}</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button 
                            type="button" 
                            @click="showCreatePlanModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            {{ __('cancel') }}
                        </button>
                        <button 
                            type="submit" 
                            :disabled="createPlanForm.processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50"
                        >
                            {{ createPlanForm.processing ? __('saving') : __('create_plan') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </MainLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
