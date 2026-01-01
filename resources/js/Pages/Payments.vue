<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import PaymentTable from '@/Components/Dashboard/PaymentTable.vue';
import CurrencySummary from '@/Components/Dashboard/CurrencySummary.vue';
import Modal from '@/Components/Modal.vue';
import { MagnifyingGlassIcon, PlusIcon, ArrowDownTrayIcon, CheckIcon, FunnelIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import DateInput from '@/Components/DateInput.vue';
import Autocomplete from '@/Components/Autocomplete.vue';

const props = defineProps({
    payments: Array,
    suppliers: Array,
    branches: Array,
    summary: Object,
    filters: Object,
});

const page = usePage();
const selectedIds = ref([]);
const searchQuery = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');
const currencyFilter = ref(props.filters?.currency || 'all');
const supplierFilter = ref(props.filters?.supplier_id || 'all');
const showPaymentModal = ref(false);
const editingPayment = ref(null);
const filteredBranches = ref([]);

const paymentForm = useForm({
    supplier_id: '',
    branch_id: '',
    amount: '',
    currency: 'KM',
    planned_date: new Date().toISOString().split('T')[0],
    description: '',
});

watch(() => paymentForm.supplier_id, (newVal) => {
    if (newVal) {
        filteredBranches.value = props.branches.filter(b => b.supplier_id == newVal);
        if (!editingPayment.value) paymentForm.branch_id = '';
    } else {
        filteredBranches.value = [];
    }
});

const activeFiltersCount = computed(() => {
    return [statusFilter.value, currencyFilter.value, supplierFilter.value].filter(f => f !== 'all').length;
});

const applyFilters = () => {
    const params = {};
    if (searchQuery.value) params.search = searchQuery.value;
    if (statusFilter.value !== 'all') params.status = statusFilter.value;
    if (currencyFilter.value !== 'all') params.currency = currencyFilter.value;
    if (supplierFilter.value !== 'all') params.supplier_id = supplierFilter.value;
    router.get(route('payments.index'), params, { preserveState: true });
};

const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
    currencyFilter.value = 'all';
    supplierFilter.value = 'all';
    router.get(route('payments.index'));
};

const handleSelectAll = (checked) => {
    const plannedPayments = props.payments.filter(p => p.status === 'PLANNED');
    selectedIds.value = checked ? plannedPayments.map(p => p.id) : [];
};

const handleSelectOne = (id, checked) => {
    if (checked) selectedIds.value.push(id);
    else selectedIds.value = selectedIds.value.filter(i => i !== id);
};

const handleMarkAsPaid = (id) => {
    router.post(route('payments.mark-paid', id), {}, {
        preserveScroll: true,
        onSuccess: () => { selectedIds.value = selectedIds.value.filter(i => i !== id); },
    });
};

const handleBatchMarkAsPaid = () => {
    if (selectedIds.value.length === 0) return;
    router.post(route('payments.batch-mark-paid'), { ids: selectedIds.value }, {
        preserveScroll: true,
        onSuccess: () => { selectedIds.value = []; },
    });
};

const openNewPaymentModal = () => {
    editingPayment.value = null;
    paymentForm.reset();
    paymentForm.planned_date = new Date().toISOString().split('T')[0];
    showPaymentModal.value = true;
};

const openEditPaymentModal = (payment) => {
    editingPayment.value = payment;
    paymentForm.supplier_id = payment.supplier_id;
    paymentForm.branch_id = payment.branch_id;
    paymentForm.amount = payment.amount;
    paymentForm.currency = payment.currency;
    paymentForm.planned_date = payment.planned_date.split('T')[0];
    paymentForm.description = payment.description || '';
    filteredBranches.value = props.branches.filter(b => b.supplier_id == payment.supplier_id);
    showPaymentModal.value = true;
};

const submitPayment = () => {
    if (editingPayment.value) {
        paymentForm.put(route('payments.update', editingPayment.value.id), {
            preserveScroll: true,
            onSuccess: () => { showPaymentModal.value = false; paymentForm.reset(); editingPayment.value = null; },
        });
    } else {
        paymentForm.post(route('payments.store'), {
            preserveScroll: true,
            onSuccess: () => { showPaymentModal.value = false; paymentForm.reset(); },
        });
    }
};

const exportPayments = () => {
    const params = new URLSearchParams();
    if (statusFilter.value !== 'all') params.append('status', statusFilter.value);
    if (currencyFilter.value !== 'all') params.append('currency', currencyFilter.value);
    if (supplierFilter.value !== 'all') params.append('supplier_id', supplierFilter.value);
    window.location.href = route('payments.export') + '?' + params.toString();
};

const exportExcel = () => {
    const params = new URLSearchParams();
    if (statusFilter.value !== 'all') params.append('status', statusFilter.value);
    if (currencyFilter.value !== 'all') params.append('currency', currencyFilter.value);
    if (supplierFilter.value !== 'all') params.append('supplier_id', supplierFilter.value);
    window.location.href = route('payments.export-excel') + '?' + params.toString();
};
</script>

<template>
    <MainLayout>
        <Header title="Sva plaćanja" />
        <div class="p-6 space-y-6">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="relative w-full sm:w-96">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input v-model="searchQuery" @keyup.enter="applyFilters" type="search" placeholder="Pretraži plaćanja..." class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div class="flex items-center gap-2">
                        <button v-if="selectedIds.length > 0 && page.props.auth.user?.canModify" @click="handleBatchMarkAsPaid" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-green-500 rounded-lg hover:bg-green-600">
                            <CheckIcon class="h-4 w-4" /> Označi plaćeno ({{ selectedIds.length }})
                        </button>
                        <button @click="exportExcel" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                            <ArrowDownTrayIcon class="h-4 w-4" /> Excel
                        </button>
                        <button @click="exportPayments" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            <ArrowDownTrayIcon class="h-4 w-4" /> CSV
                        </button>
                        <button v-if="page.props.auth.user?.canModify" @click="openNewPaymentModal" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                            <PlusIcon class="h-4 w-4" /> Novo plaćanje
                        </button>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 text-sm text-gray-500"><FunnelIcon class="h-4 w-4" /><span>Filteri:</span></div>
                    <select v-model="statusFilter" @change="applyFilters" class="h-9 pl-3 pr-8 text-sm border border-gray-300 rounded-lg bg-white appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3csvg%20xmlns%3d%22http%3a%2f%2fwww.w3.org%2f2000%2fsvg%22%20viewBox%3d%220%200%2020%2020%22%20fill%3d%22%236b7280%22%3e%3cpath%20fill-rule%3d%22evenodd%22%20d%3d%22M5.23%207.21a.75.75%200%20011.06.02L10%2011.168l3.71-3.938a.75.75%200%20111.08%201.04l-4.25%204.5a.75.75%200%2001-1.08%200l-4.25-4.5a.75.75%200%2001.02-1.06z%22%20clip-rule%3d%22evenodd%22%2f%3e%3c%2fsvg%3e')] bg-[length:1.25rem_1.25rem] bg-[right_0.5rem_center] bg-no-repeat">
                        <option value="all">Svi statusi</option>
                        <option value="PLANNED">Planirano</option>
                        <option value="PAID">Plaćeno</option>
                    </select>
                    <select v-model="currencyFilter" @change="applyFilters" class="h-9 pl-3 pr-8 text-sm border border-gray-300 rounded-lg bg-white appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3csvg%20xmlns%3d%22http%3a%2f%2fwww.w3.org%2f2000%2fsvg%22%20viewBox%3d%220%200%2020%2020%22%20fill%3d%22%236b7280%22%3e%3cpath%20fill-rule%3d%22evenodd%22%20d%3d%22M5.23%207.21a.75.75%200%20011.06.02L10%2011.168l3.71-3.938a.75.75%200%20111.08%201.04l-4.25%204.5a.75.75%200%2001-1.08%200l-4.25-4.5a.75.75%200%2001.02-1.06z%22%20clip-rule%3d%22evenodd%22%2f%3e%3c%2fsvg%3e')] bg-[length:1.25rem_1.25rem] bg-[right_0.5rem_center] bg-no-repeat">
                        <option value="all">Sve valute</option>
                        <option value="KM">KM</option>
                        <option value="EUR">EUR</option>
                        <option value="USD">USD</option>
                    </select>
                    <select v-model="supplierFilter" @change="applyFilters" class="h-9 pl-3 pr-8 text-sm border border-gray-300 rounded-lg bg-white appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3csvg%20xmlns%3d%22http%3a%2f%2fwww.w3.org%2f2000%2fsvg%22%20viewBox%3d%220%200%2020%2020%22%20fill%3d%22%236b7280%22%3e%3cpath%20fill-rule%3d%22evenodd%22%20d%3d%22M5.23%207.21a.75.75%200%20011.06.02L10%2011.168l3.71-3.938a.75.75%200%20111.08%201.04l-4.25%204.5a.75.75%200%2001-1.08%200l-4.25-4.5a.75.75%200%2001.02-1.06z%22%20clip-rule%3d%22evenodd%22%2f%3e%3c%2fsvg%3e')] bg-[length:1.25rem_1.25rem] bg-[right_0.5rem_center] bg-no-repeat">
                        <option value="all">Svi dobavljači</option>
                        <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                    </select>
                    <button v-if="activeFiltersCount > 0" @click="clearFilters" class="inline-flex items-center gap-1 px-2 py-1 text-sm text-gray-500 hover:text-gray-700">
                        <XMarkIcon class="h-4 w-4" /> Očisti ({{ activeFiltersCount }})
                    </button>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <CurrencySummary :totalKM="summary.totalKM" :totalEUR="summary.totalEUR" :totalUSD="summary.totalUSD" label="Ukupno:" />
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-gray-100 text-gray-800">{{ summary.count }} plaćanja</span>
            </div>
            <PaymentTable :payments="payments" :selectedIds="selectedIds" @selectAll="handleSelectAll" @selectOne="handleSelectOne" @markAsPaid="handleMarkAsPaid" @edit="openEditPaymentModal" />
        </div>

        <Modal :show="showPaymentModal" :title="editingPayment ? 'Uredi plaćanje' : 'Novo plaćanje'" @close="showPaymentModal = false">
            <form @submit.prevent="submitPayment" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dobavljač *</label>
                    <Autocomplete
                        v-model="paymentForm.supplier_id"
                        :options="suppliers"
                        placeholder="Pretraži dobavljača..."
                        :required="true"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Poslovnica *</label>
                    <Autocomplete
                        v-model="paymentForm.branch_id"
                        :options="filteredBranches"
                        placeholder="Pretraži poslovnicu..."
                        :disabled="!paymentForm.supplier_id"
                        :required="true"
                    />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Iznos *</label>
                        <input v-model="paymentForm.amount" type="number" step="0.01" min="0.01" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valuta *</label>
                        <select v-model="paymentForm.currency" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="KM">KM</option>
                            <option value="EUR">EUR</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Planirani datum *</label>
                    <DateInput v-model="paymentForm.planned_date" :required="true" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Opis</label>
                    <textarea v-model="paymentForm.description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showPaymentModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Odustani</button>
                    <button type="submit" :disabled="paymentForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50">
                        {{ paymentForm.processing ? 'Spremanje...' : 'Spremi' }}
                    </button>
                </div>
            </form>
        </Modal>
    </MainLayout>
</template>
