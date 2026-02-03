<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import PaymentTable from '@/Components/Dashboard/PaymentTable.vue';
import CurrencySummary from '@/Components/Dashboard/CurrencySummary.vue';
import Modal from '@/Components/Modal.vue';
import { CreditCardIcon, ClockIcon, CheckCircleIcon, ExclamationCircleIcon, PlusIcon, ArrowDownTrayIcon, CheckIcon, FunnelIcon, XMarkIcon, BookmarkIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import DateInput from '@/Components/DateInput.vue';
import Autocomplete from '@/Components/Autocomplete.vue';
import { useTranslations } from '@/composables/useTranslations';

const { __, t } = useTranslations();

const props = defineProps({ payments: Array, stats: Object, suppliers: Array, branches: Array, filters: Object, plans: Array });
const page = usePage();

const selectedIds = ref([]);
const showPaymentModal = ref(false);
const showSavePlanModal = ref(false);
const showMarkPaidModal = ref(false);
const editingPayment = ref(null);
const filteredBranches = ref([]);
const searchQuery = ref(props.filters?.search || '');
const paidDate = ref(new Date().toISOString().split('T')[0]);
// Filters
const dateFilter = ref(props.filters?.date_filter || 'today');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');
const statusFilter = ref(props.filters?.status || '');
const currencyFilter = ref(props.filters?.currency || '');
const supplierFilter = ref(props.filters?.supplier_id || '');
const branchFilter = ref(props.filters?.branch_id || '');
// Sorting
const sortBy = ref(props.filters?.sort_by || '');
const sortDirection = ref(props.filters?.sort_direction || 'asc');

// Filtrirane poslovnice za filter dropdown
const filterBranches = computed(() => {
    if (!supplierFilter.value) return [];
    return props.branches.filter(b => b.supplier_id == supplierFilter.value);
});

const paymentForm = useForm({
    supplier_id: '', branch_id: '', invoice_number: '', amount: '', currency: 'KM',
    planned_date: new Date().toISOString().split('T')[0], description: '', save_description: false,
});

const planForm = useForm({
    name: '',
    description: '',
    date_filter: '',
    date_from: '',
    date_to: '',
    filters: {},
    payment_ids: [],
    total_km: 0,
    total_eur: 0,
    total_usd: 0,
});

watch(() => paymentForm.supplier_id, (newVal) => {
    filteredBranches.value = newVal ? props.branches.filter(b => b.supplier_id == newVal) : [];
    if (!editingPayment.value) {
        paymentForm.branch_id = '';
        paymentForm.description = '';
    }
});

watch(() => paymentForm.branch_id, async (newBranchId) => {
    if (!editingPayment.value && paymentForm.supplier_id && newBranchId) {
        try {
            const response = await fetch(route('payments.saved-description', {
                supplier: paymentForm.supplier_id,
                branch: newBranchId
            }));
            const data = await response.json();
            if (data.description) {
                paymentForm.description = data.description;
            }
        } catch (error) {
            console.error('Error loading saved description:', error);
        }
    }
});

// Computed za odabrana plaćanja
const selectedPayments = computed(() => {
    return props.payments.filter(p => selectedIds.value.includes(p.id));
});

const selectedTotalKM = computed(() => {
    return selectedPayments.value.filter(p => p.currency === 'KM').reduce((sum, p) => sum + parseFloat(p.amount), 0);
});

const selectedTotalEUR = computed(() => {
    return selectedPayments.value.filter(p => p.currency === 'EUR').reduce((sum, p) => sum + parseFloat(p.amount), 0);
});

const selectedTotalUSD = computed(() => {
    return selectedPayments.value.filter(p => p.currency === 'USD').reduce((sum, p) => sum + parseFloat(p.amount), 0);
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (statusFilter.value) count++;
    if (currencyFilter.value) count++;
    if (supplierFilter.value) count++;
    if (branchFilter.value) count++;
    return count;
});

const applyFilters = () => {
    const params = { date_filter: dateFilter.value };
    if (dateFilter.value === 'period') {
        if (startDate.value) params.start_date = startDate.value;
        if (endDate.value) params.end_date = endDate.value;
    }
    if (statusFilter.value) params.status = statusFilter.value;
    if (currencyFilter.value) params.currency = currencyFilter.value;
    if (supplierFilter.value) params.supplier_id = supplierFilter.value;
    if (branchFilter.value) params.branch_id = branchFilter.value;
    if (searchQuery.value) params.search = searchQuery.value;
    if (sortBy.value) {
        params.sort_by = sortBy.value;
        params.sort_direction = sortDirection.value;
    }
    router.get(route('dashboard'), params, { preserveState: true });
};

const onSupplierFilterChange = () => {
    branchFilter.value = ''; // Reset branch filter when supplier changes
    applyFilters();
};

const clearFilters = () => {
    statusFilter.value = '';
    currencyFilter.value = '';
    supplierFilter.value = '';
    branchFilter.value = '';
    searchQuery.value = '';
    sortBy.value = '';
    sortDirection.value = 'asc';
    applyFilters();
};

const handleSort = (column) => {
    if (sortBy.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortDirection.value = 'asc';
    }
    applyFilters();
};

const handleSelectAll = (checked) => { 
    const planned = props.payments.filter(p => p.status === 'PLANNED');
    selectedIds.value = checked ? planned.map(p => p.id) : []; 
};
const handleSelectOne = (id, checked) => { 
    if (checked) selectedIds.value.push(id); 
    else selectedIds.value = selectedIds.value.filter(i => i !== id); 
};

const handleMarkAsPaid = (id) => {
    router.post(route('payments.mark-paid', id), { paid_date: paidDate.value }, { preserveScroll: true, onSuccess: () => { selectedIds.value = selectedIds.value.filter(i => i !== id); showMarkPaidModal.value = false; } });
};

const handleBatchMarkAsPaid = () => {
    if (selectedIds.value.length === 0) return;
    showMarkPaidModal.value = true;
};

const confirmBatchMarkAsPaid = () => {
    router.post(route('payments.batch-mark-paid'), { ids: selectedIds.value, paid_date: paidDate.value }, { preserveScroll: true, onSuccess: () => { selectedIds.value = []; showMarkPaidModal.value = false; } });
};

const openNewPaymentModal = () => {
    editingPayment.value = null;
    paymentForm.reset();
    paymentForm.planned_date = new Date().toISOString().split('T')[0];
    paymentForm.currency = 'KM';
    filteredBranches.value = [];
    showPaymentModal.value = true;
};

const openEditPaymentModal = (payment) => {
    editingPayment.value = payment;
    paymentForm.supplier_id = payment.supplier_id;
    paymentForm.branch_id = payment.branch_id;
    paymentForm.invoice_number = payment.invoice_number || '';
    paymentForm.amount = payment.amount;
    paymentForm.currency = payment.currency;
    paymentForm.planned_date = payment.planned_date.split('T')[0];
    paymentForm.description = payment.description || '';
    filteredBranches.value = props.branches.filter(b => b.supplier_id == payment.supplier_id);
    showPaymentModal.value = true;
};

const submitPayment = () => {
    const options = { preserveScroll: true, onSuccess: () => { showPaymentModal.value = false; paymentForm.reset(); editingPayment.value = null; } };
    if (editingPayment.value) paymentForm.put(route('payments.update', editingPayment.value.id), options);
    else paymentForm.post(route('payments.store'), options);
};

const openSavePlanModal = () => {
    if (selectedIds.value.length === 0) return;
    const today = new Date();
    planForm.name = `Plan ${getDateFilterLabel()} - ${today.toLocaleDateString('bs-BA')}`;
    planForm.description = '';
    planForm.date_filter = dateFilter.value;
    planForm.date_from = startDate.value || null;
    planForm.date_to = endDate.value || null;
    planForm.filters = {
        status: statusFilter.value,
        currency: currencyFilter.value,
        supplier_id: supplierFilter.value,
    };
    planForm.payment_ids = selectedIds.value;
    planForm.total_km = selectedTotalKM.value;
    planForm.total_eur = selectedTotalEUR.value;
    planForm.total_usd = selectedTotalUSD.value;
    showSavePlanModal.value = true;
};

const submitPlan = () => {
    planForm.post(route('plans.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showSavePlanModal.value = false;
            planForm.reset();
        },
    });
};

const exportPayments = () => {
    const params = new URLSearchParams();
    params.append('date_filter', dateFilter.value);
    if (dateFilter.value === 'period') {
        if (startDate.value) params.append('start_date', startDate.value);
        if (endDate.value) params.append('end_date', endDate.value);
    }
    if (statusFilter.value) params.append('status', statusFilter.value);
    if (currencyFilter.value) params.append('currency', currencyFilter.value);
    if (supplierFilter.value) params.append('supplier_id', supplierFilter.value);
    if (searchQuery.value) params.append('search', searchQuery.value);
    window.location.href = route('dashboard.export') + '?' + params.toString();
};

const exportExcel = () => {
    const params = new URLSearchParams();
    params.append('date_filter', dateFilter.value);
    if (dateFilter.value === 'period') {
        if (startDate.value) params.append('start_date', startDate.value);
        if (endDate.value) params.append('end_date', endDate.value);
    }
    if (statusFilter.value) params.append('status', statusFilter.value);
    if (currencyFilter.value) params.append('currency', currencyFilter.value);
    if (supplierFilter.value) params.append('supplier_id', supplierFilter.value);
    if (searchQuery.value) params.append('search', searchQuery.value);
    window.location.href = route('dashboard.export-excel') + '?' + params.toString();
};

const getDateFilterLabel = () => {
    const labels = {
        today: __('today'),
        tomorrow: __('tomorrow'),
        '3days': __('3days'),
        '7days': __('7days'),
        period: __('period'),
        all: __('all'),
        overdue: __('not_paid_on_time')
    };
    return labels[dateFilter.value] || dateFilter.value;
};

const showOverdueOnly = () => {
    dateFilter.value = 'overdue';
    statusFilter.value = '';
    currencyFilter.value = '';
    supplierFilter.value = '';
    branchFilter.value = '';
    router.get(route('dashboard'), { date_filter: 'overdue' }, { preserveState: true });
};
</script>

<template>
    <MainLayout>
        <Header :title="__('payments_overview')" />
        <div class="p-6 space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard :title="__('today_to_pay')" :value="stats.todayCount.toString()" :subtitle="__('planned_payments')" :icon="ClockIcon" variant="warning" />
                <StatCard :title="__('unpaid_status')" :value="stats.plannedCount.toString()" :subtitle="__('in_selected_period')" :icon="CreditCardIcon" variant="primary" />
                <StatCard :title="__('paid_status')" :value="stats.paidCount.toString()" :subtitle="__('in_selected_period')" :icon="CheckCircleIcon" variant="success" />
                <StatCard 
                    :title="__('not_paid_on_time')" 
                    :value="stats.overdueCount.toString()" 
                    :subtitle="__('click_for_overview')" 
                    :icon="ExclamationCircleIcon" 
                    :variant="stats.overdueCount > 0 ? 'danger' : 'default'" 
                    :clickable="stats.overdueCount > 0"
                    @click="showOverdueOnly"
                />
            </div>

            <!-- Date Filters and Additional Filters in one row -->
            <div class="flex flex-wrap items-center justify-between gap-2">
                <!-- Date Filters - Left Side -->
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium text-gray-700">{{ __('date_pay_by') }}:</span>
                    <button v-for="opt in [{v:'today',l:__('today')},{v:'tomorrow',l:__('tomorrow')},{v:'3days',l:__('3days')},{v:'7days',l:__('7days')},{v:'all',l:__('all')}]" :key="opt.v"
                        @click="dateFilter = opt.v; applyFilters()"
                        :class="['px-3 py-1.5 text-sm font-medium rounded-lg transition-colors', dateFilter === opt.v ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50']">
                        {{ opt.l }}
                    </button>
                    <button @click="dateFilter = 'period'" :class="['px-3 py-1.5 text-sm font-medium rounded-lg transition-colors', dateFilter === 'period' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50']">
                        {{ __('period') }}
                    </button>
                    <button v-if="dateFilter === 'overdue'" class="px-3 py-1.5 text-sm font-medium rounded-lg bg-red-500 text-white">
                        {{ __('not_paid_on_time') }}
                    </button>
                    <template v-if="dateFilter === 'period'">
                        <div class="w-36">
                            <DateInput v-model="startDate" @update:modelValue="applyFilters" :placeholder="__('from_date')" />
                        </div>
                        <span class="text-gray-500">{{ __('to_date').toLowerCase() }}</span>
                        <div class="w-36">
                            <DateInput v-model="endDate" @update:modelValue="applyFilters" :placeholder="__('to_date')" />
                        </div>
                    </template>
                </div>
                
                <!-- Filters - Right Side -->
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-2 text-sm text-gray-500"><FunnelIcon class="h-4 w-4" /><span>{{ __('filters') }}:</span></div>
                    
                    <!-- Search Input -->
                    <div class="relative">
                        <input 
                            v-model="searchQuery" 
                            @input="applyFilters"
                            type="text" 
                            :placeholder="__('search')" 
                            class="h-9 pl-9 pr-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-48"
                        />
                        <MagnifyingGlassIcon class="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    </div>

                    <select v-model="statusFilter" @change="applyFilters" class="h-9 pl-3 pr-8 text-sm border border-gray-300 rounded-lg bg-white appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3csvg%20xmlns%3d%22http%3a%2f%2fwww.w3.org%2f2000%2fsvg%22%20viewBox%3d%220%200%2020%2020%22%20fill%3d%22%236b7280%22%3e%3cpath%20fill-rule%3d%22evenodd%22%20d%3d%22M5.23%207.21a.75.75%200%20011.06.02L10%2011.168l3.71-3.938a.75.75%200%20111.08%201.04l-4.25%204.5a.75.75%200%2001-1.08%200l-4.25-4.5a.75.75%200%2001.02-1.06z%22%20clip-rule%3d%22evenodd%22%2f%3e%3c%2fsvg%3e')] bg-[length:1.25rem_1.25rem] bg-[right_0.5rem_center] bg-no-repeat">
                        <option value="">{{ __('all_statuses') }}</option>
                        <option value="PLANNED">{{ __('unpaid_status') }}</option>
                        <option value="PAID">{{ __('paid_status') }}</option>
                    </select>
                    <select v-model="currencyFilter" @change="applyFilters" class="h-9 pl-3 pr-8 text-sm border border-gray-300 rounded-lg bg-white appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3csvg%20xmlns%3d%22http%3a%2f%2fwww.w3.org%2f2000%2fsvg%22%20viewBox%3d%220%200%2020%2020%22%20fill%3d%22%236b7280%22%3e%3cpath%20fill-rule%3d%22evenodd%22%20d%3d%22M5.23%207.21a.75.75%200%20011.06.02L10%2011.168l3.71-3.938a.75.75%200%20111.08%201.04l-4.25%204.5a.75.75%200%2001-1.08%200l-4.25-4.5a.75.75%200%2001.02-1.06z%22%20clip-rule%3d%22evenodd%22%2f%3e%3c%2fsvg%3e')] bg-[length:1.25rem_1.25rem] bg-[right_0.5rem_center] bg-no-repeat">
                        <option value="">{{ __('all_currencies') }}</option>
                        <option value="KM">KM</option>
                        <option value="EUR">EUR</option>
                        <option value="USD">USD</option>
                    </select>
                    
                    <!-- Autocomplete for Supplier -->
                    <div class="w-52">
                        <Autocomplete
                            v-model="supplierFilter"
                            :options="suppliers"
                            :placeholder="__('all_suppliers')"
                            @update:modelValue="onSupplierFilterChange"
                        />
                    </div>

                    <select v-if="supplierFilter" v-model="branchFilter" @change="applyFilters" class="h-9 pl-3 pr-8 text-sm border border-gray-300 rounded-lg bg-white appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3csvg%20xmlns%3d%22http%3a%2f%2fwww.w3.org%2f2000%2fsvg%22%20viewBox%3d%220%200%2020%2020%22%20fill%3d%22%236b7280%22%3e%3cpath%20fill-rule%3d%22evenodd%22%20d%3d%22M5.23%207.21a.75.75%200%20011.06.02L10%2011.168l3.71-3.938a.75.75%200%20111.08%201.04l-4.25%204.5a.75.75%200%2001-1.08%200l-4.25-4.5a.75.75%200%2001.02-1.06z%22%20clip-rule%3d%22evenodd%22%2f%3e%3c%2fsvg%3e')] bg-[length:1.25rem_1.25rem] bg-[right_0.5rem_center] bg-no-repeat">
                        <option value="">{{ __('all_branches') }}</option>
                        <option v-for="b in filterBranches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <button v-if="activeFiltersCount > 0 || searchQuery" @click="clearFilters" class="inline-flex items-center gap-1 px-2 py-1 text-sm text-gray-500 hover:text-gray-700">
                        <XMarkIcon class="h-4 w-4" /> {{ __('clear') }} ({{ activeFiltersCount + (searchQuery ? 1 : 0) }})
                    </button>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <CurrencySummary :totalKM="stats.totalKM" :totalEUR="stats.totalEUR" :totalUSD="stats.totalUSD" :label="__('total')" />
                <div class="flex items-center gap-2">
                    <button v-if="selectedIds.length > 0 && page.props.auth.user?.canModify" @click="handleBatchMarkAsPaid" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-green-500 rounded-lg hover:bg-green-600">
                        <CheckIcon class="h-4 w-4" /> {{ __('mark_paid') }} ({{ selectedIds.length }})
                    </button>
                    <button @click="openSavePlanModal" :disabled="selectedIds.length === 0" :class="['inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition-colors', selectedIds.length > 0 ? 'text-white bg-emerald-600 hover:bg-emerald-700' : 'text-gray-400 bg-gray-100 cursor-not-allowed']">
                        <BookmarkIcon class="h-4 w-4" /> {{ __('save_plan') }}{{ selectedIds.length > 0 ? ` (${selectedIds.length})` : '' }}
                    </button>
                    <button @click="exportExcel" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                        <ArrowDownTrayIcon class="h-4 w-4" /> {{ __('excel') }}
                    </button>
                    <button @click="exportPayments" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        <ArrowDownTrayIcon class="h-4 w-4" /> {{ __('csv') }}
                    </button>
                    <button v-if="page.props.auth.user?.canModify" @click="openNewPaymentModal" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                        <PlusIcon class="h-4 w-4" /> {{ __('new_payment') }}
                    </button>
                </div>
            </div>

            <!-- Results info -->
            <div class="text-sm text-gray-500">
                {{ __('showing') }} {{ payments.length }} {{ __('payments') }} {{ __('for_period') }} <span class="font-medium">{{ getDateFilterLabel() }}</span>
            </div>

            <!-- Table -->
            <PaymentTable 
                :payments="payments" 
                :selectedIds="selectedIds" 
                :sortBy="sortBy"
                :sortDirection="sortDirection"
                :plans="plans"
                @selectAll="handleSelectAll" 
                @selectOne="handleSelectOne" 
                @markAsPaid="handleMarkAsPaid" 
                @edit="openEditPaymentModal"
                @sort="handleSort"
            />
        </div>

        <!-- Payment Modal -->
        <Modal :show="showPaymentModal" :title="editingPayment ? __('edit_payment') : __('new_payment')" @close="showPaymentModal = false">
            <form @submit.prevent="submitPayment" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supplier') }} {{ __('required') }}</label>
                    <Autocomplete
                        v-model="paymentForm.supplier_id"
                        :options="suppliers"
                        :placeholder="__('search_supplier')"
                        :required="true"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('branch') }} {{ __('required') }}</label>
                    <Autocomplete
                        v-model="paymentForm.branch_id"
                        :options="filteredBranches"
                        :placeholder="__('search_branch')"
                        :disabled="!paymentForm.supplier_id"
                        :required="true"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('invoice_number') }}</label>
                    <input v-model="paymentForm.invoice_number" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" :placeholder="__('invoice_placeholder')" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('amount') }} {{ __('required') }}</label>
                        <input v-model="paymentForm.amount" type="number" step="0.01" min="0.01" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('currency') }} {{ __('required') }}</label>
                        <select v-model="paymentForm.currency" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="KM">KM</option>
                            <option value="EUR">EUR</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('planned_date') }} {{ __('required') }}</label>
                    <DateInput v-model="paymentForm.planned_date" :required="true" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('description') }}</label>
                    <textarea v-model="paymentForm.description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                    <div v-if="!editingPayment" class="mt-2 flex items-center gap-2">
                        <input v-model="paymentForm.save_description" type="checkbox" id="save_description" class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                        <label for="save_description" class="text-sm text-gray-600">Sačuvaj opis za ovog dobavljača i poslovnicu</label>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showPaymentModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">{{ __('cancel') }}</button>
                    <button type="submit" :disabled="paymentForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50">
                        {{ paymentForm.processing ? __('saving') : __('save') }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Save Plan Modal -->
        <Modal :show="showSavePlanModal" :title="__('save_payment_plan')" @close="showSavePlanModal = false">
            <form @submit.prevent="submitPlan" class="space-y-4">
                <div class="rounded-lg bg-blue-50 border border-blue-200 p-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-blue-700">{{ __('selected_payments') }}</span>
                        <span class="font-semibold text-blue-900">{{ selectedIds.length }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm mt-2">
                        <span class="text-blue-700">{{ __('total_km') }}:</span>
                        <span class="font-semibold text-blue-900">{{ new Intl.NumberFormat('de-DE', { minimumFractionDigits: 2 }).format(selectedTotalKM) }} KM</span>
                    </div>
                    <div class="flex items-center justify-between text-sm mt-1">
                        <span class="text-blue-700">{{ __('total_eur') }}:</span>
                        <span class="font-semibold text-green-700">{{ new Intl.NumberFormat('de-DE', { minimumFractionDigits: 2 }).format(selectedTotalEUR) }} EUR</span>
                    </div>
                    <div class="flex items-center justify-between text-sm mt-1">
                        <span class="text-blue-700">{{ __('total_usd') }}:</span>
                        <span class="font-semibold text-purple-700">{{ new Intl.NumberFormat('de-DE', { minimumFractionDigits: 2 }).format(selectedTotalUSD) }} USD</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('plan_name') }} {{ __('required') }}</label>
                    <input v-model="planForm.name" type="text" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" :placeholder="__('plan_name_placeholder')" />
                    <p v-if="planForm.errors.name" class="mt-1 text-sm text-red-600">{{ planForm.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('optional_description') }}</label>
                    <textarea v-model="planForm.description" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg" :placeholder="__('additional_notes')"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showSavePlanModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">{{ __('cancel') }}</button>
                    <button type="submit" :disabled="planForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50">
                        {{ planForm.processing ? __('saving') : __('save_plan') }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Mark as Paid Modal -->
        <Modal :show="showMarkPaidModal" :title="__('mark_as_paid')" @close="showMarkPaidModal = false; paidDate = new Date().toISOString().split('T')[0]">
            <form @submit.prevent="confirmBatchMarkAsPaid" class="space-y-4">
                <div class="rounded-lg bg-green-50 border border-green-200 p-4">
                    <p class="text-sm text-green-800">
                        {{ t('mark_payments_as_paid', { count: selectedIds.length }) }}
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('payment_date') }} {{ __('required') }}</label>
                    <DateInput v-model="paidDate" :required="true" />
                    <p class="mt-1 text-xs text-gray-500">{{ __('select_payment_date') }}</p>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showMarkPaidModal = false; paidDate = new Date().toISOString().split('T')[0]" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">{{ __('cancel') }}</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-500 rounded-lg hover:bg-green-600">
                        {{ __('mark_as_paid') }}
                    </button>
                </div>
            </form>
        </Modal>
    </MainLayout>
</template>
