<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import CurrencySummary from '@/Components/Dashboard/CurrencySummary.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import DateInput from '@/Components/DateInput.vue';
import { MagnifyingGlassIcon, ArrowDownTrayIcon, FunnelIcon, XMarkIcon, EyeIcon, XCircleIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { format, parseISO } from 'date-fns';
import { useTranslations } from '@/composables/useTranslations';

const { __ } = useTranslations();

const props = defineProps({
    payments: Array,
    suppliers: Array,
    branches: Array,
    summary: Object,
    filters: Object,
});

const page = usePage();
const searchQuery = ref(props.filters?.search || '');
const currencyFilter = ref(props.filters?.currency || 'all');
const supplierFilter = ref(props.filters?.supplier_id || 'all');
const branchFilter = ref(props.filters?.branch_id || 'all');
const paidDateFilter = ref(props.filters?.paid_date || '');
const showViewModal = ref(false);
const viewingPayment = ref(null);
const showDeleteConfirm = ref(false);
const paymentToDelete = ref(null);

// Filtrirane poslovnice za filter dropdown
const filterBranches = computed(() => {
    if (supplierFilter.value === 'all') return [];
    return props.branches.filter(b => b.supplier_id == supplierFilter.value);
});

const formatCurrency = (amount, currency) => {
    return new Intl.NumberFormat('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount) + ' ' + currency;
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        return format(typeof dateStr === 'string' ? parseISO(dateStr) : dateStr, 'dd.MM.yyyy');
    } catch {
        return dateStr;
    }
};

const activeFiltersCount = computed(() => {
    let count = 0;
    if (currencyFilter.value !== 'all') count++;
    if (supplierFilter.value !== 'all') count++;
    if (branchFilter.value !== 'all') count++;
    if (paidDateFilter.value) count++;
    return count;
});

const applyFilters = () => {
    const params = {};
    if (searchQuery.value) params.search = searchQuery.value;
    if (currencyFilter.value !== 'all') params.currency = currencyFilter.value;
    if (supplierFilter.value !== 'all') params.supplier_id = supplierFilter.value;
    if (branchFilter.value !== 'all') params.branch_id = branchFilter.value;
    if (paidDateFilter.value) params.paid_date = paidDateFilter.value;
    router.get(route('payments.index'), params, { preserveState: true });
};

const onSupplierFilterChange = () => {
    branchFilter.value = 'all'; // Reset branch filter when supplier changes
    applyFilters();
};

const clearFilters = () => {
    searchQuery.value = '';
    currencyFilter.value = 'all';
    supplierFilter.value = 'all';
    branchFilter.value = 'all';
    paidDateFilter.value = '';
    router.get(route('payments.index'));
};

const openViewModal = (payment) => {
    viewingPayment.value = payment;
    showViewModal.value = true;
};

const handleMarkAsUnpaid = (id) => {
    router.post(route('payments.mark-unpaid', id), {}, { 
        preserveScroll: true,
        onSuccess: () => { showViewModal.value = false; }
    });
};

const handleDeleteClick = (payment) => {
    paymentToDelete.value = payment;
    showDeleteConfirm.value = true;
};

const confirmDelete = () => {
    if (!paymentToDelete.value) return;
    router.delete(route('payments.destroy', paymentToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirm.value = false;
            paymentToDelete.value = null;
            showViewModal.value = false;
        }
    });
};

const exportExcel = () => {
    const params = new URLSearchParams();
    params.append('status', 'PAID');
    if (currencyFilter.value !== 'all') params.append('currency', currencyFilter.value);
    if (supplierFilter.value !== 'all') params.append('supplier_id', supplierFilter.value);
    if (branchFilter.value !== 'all') params.append('branch_id', branchFilter.value);
    if (paidDateFilter.value) params.append('paid_date', paidDateFilter.value);
    window.location.href = route('payments.export-excel') + '?' + params.toString();
};

const exportCsv = () => {
    const params = new URLSearchParams();
    params.append('status', 'PAID');
    if (currencyFilter.value !== 'all') params.append('currency', currencyFilter.value);
    if (supplierFilter.value !== 'all') params.append('supplier_id', supplierFilter.value);
    if (branchFilter.value !== 'all') params.append('branch_id', branchFilter.value);
    if (paidDateFilter.value) params.append('paid_date', paidDateFilter.value);
    window.location.href = route('payments.export') + '?' + params.toString();
};

const getCurrencyColor = (currency) => {
    if (currency === 'KM') return 'text-blue-600';
    if (currency === 'EUR') return 'text-green-600';
    return 'text-purple-600';
};
</script>

<template>
    <MainLayout>
        <Header :title="__('paid')" />
        <div class="p-6 space-y-6">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="relative w-full sm:w-96">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input v-model="searchQuery" @keyup.enter="applyFilters" type="search" :placeholder="__('search')" class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="exportExcel" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                            <ArrowDownTrayIcon class="h-4 w-4" /> {{ __('excel') }}
                        </button>
                        <button @click="exportCsv" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            <ArrowDownTrayIcon class="h-4 w-4" /> {{ __('csv') }}
                        </button>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 text-sm text-gray-500"><FunnelIcon class="h-4 w-4" /><span>{{ __('filters') }}:</span></div>
                    
                    <!-- Date Filter -->
                    <div class="w-40">
                        <DateInput v-model="paidDateFilter" @update:modelValue="applyFilters" :placeholder="__('payment_date')" />
                    </div>

                    <select v-model="currencyFilter" @change="applyFilters" class="h-9 pl-3 pr-8 text-sm border border-gray-300 rounded-lg bg-white appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3csvg%20xmlns%3d%22http%3a%2f%2fwww.w3.org%2f2000%2fsvg%22%20viewBox%3d%220%200%2020%2020%22%20fill%3d%22%236b7280%22%3e%3cpath%20fill-rule%3d%22evenodd%22%20d%3d%22M5.23%207.21a.75.75%200%20011.06.02L10%2011.168l3.71-3.938a.75.75%200%20111.08%201.04l-4.25%204.5a.75.75%200%2001-1.08%200l-4.25-4.5a.75.75%200%2001.02-1.06z%22%20clip-rule%3d%22evenodd%22%2f%3e%3c%2fsvg%3e')] bg-[length:1.25rem_1.25rem] bg-[right_0.5rem_center] bg-no-repeat">
                        <option value="all">{{ __('all_currencies') }}</option>
                        <option value="KM">KM</option>
                        <option value="EUR">EUR</option>
                        <option value="USD">USD</option>
                    </select>
                    <select v-model="supplierFilter" @change="onSupplierFilterChange" class="h-9 pl-3 pr-8 text-sm border border-gray-300 rounded-lg bg-white appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3csvg%20xmlns%3d%22http%3a%2f%2fwww.w3.org%2f2000%2fsvg%22%20viewBox%3d%220%200%2020%2020%22%20fill%3d%22%236b7280%22%3e%3cpath%20fill-rule%3d%22evenodd%22%20d%3d%22M5.23%207.21a.75.75%200%20011.06.02L10%2011.168l3.71-3.938a.75.75%200%20111.08%201.04l-4.25%204.5a.75.75%200%2001-1.08%200l-4.25-4.5a.75.75%200%2001.02-1.06z%22%20clip-rule%3d%22evenodd%22%2f%3e%3c%2fsvg%3e')] bg-[length:1.25rem_1.25rem] bg-[right_0.5rem_center] bg-no-repeat">
                        <option value="all">{{ __('all_suppliers') }}</option>
                        <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                    </select>
                    <select v-if="supplierFilter !== 'all'" v-model="branchFilter" @change="applyFilters" class="h-9 pl-3 pr-8 text-sm border border-gray-300 rounded-lg bg-white appearance-none bg-[url('data:image/svg+xml;charset=UTF-8,%3csvg%20xmlns%3d%22http%3a%2f%2fwww.w3.org%2f2000%2fsvg%22%20viewBox%3d%220%200%2020%2020%22%20fill%3d%22%236b7280%22%3e%3cpath%20fill-rule%3d%22evenodd%22%20d%3d%22M5.23%207.21a.75.75%200%20011.06.02L10%2011.168l3.71-3.938a.75.75%200%20111.08%201.04l-4.25%204.5a.75.75%200%2001-1.08%200l-4.25-4.5a.75.75%200%2001.02-1.06z%22%20clip-rule%3d%22evenodd%22%2f%3e%3c%2fsvg%3e')] bg-[length:1.25rem_1.25rem] bg-[right_0.5rem_center] bg-no-repeat">
                        <option value="all">{{ __('all_branches') }}</option>
                        <option v-for="b in filterBranches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <button v-if="activeFiltersCount > 0" @click="clearFilters" class="inline-flex items-center gap-1 px-2 py-1 text-sm text-gray-500 hover:text-gray-700">
                        <XMarkIcon class="h-4 w-4" /> {{ __('clear') }} ({{ activeFiltersCount }})
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <CurrencySummary :totalKM="summary.totalKM" :totalEUR="summary.totalEUR" :totalUSD="summary.totalUSD" :label="__('total') + ' ' + __('paid_status').toLowerCase() + ':'" />
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-green-100 text-green-800">{{ summary.count }} {{ __('payments') }}</span>
            </div>

            <!-- Payments Table -->
            <div class="rounded-lg border border-gray-200 bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('invoice_number') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('supplier') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('branch') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">{{ __('amount') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('payment_date') }}</th>
                                <th class="w-12 px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="payment in payments" :key="payment.id" @click="openViewModal(payment)" class="hover:bg-gray-50 cursor-pointer">
                                <td class="px-4 py-3"><p class="text-sm text-gray-600 font-mono">{{ payment.invoice_number || '-' }}</p></td>
                                <td class="px-4 py-3"><p class="font-medium text-sm text-gray-900">{{ payment.supplier?.name || 'N/A' }}</p></td>
                                <td class="px-4 py-3"><p class="text-sm text-gray-500">{{ payment.branch?.name || 'N/A' }}</p></td>
                                <td class="px-4 py-3 text-right">
                                    <span :class="['font-semibold text-sm', getCurrencyColor(payment.currency)]">
                                        {{ formatCurrency(payment.amount, payment.currency) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-900 font-medium">{{ formatDate(payment.paid_date) }}</p>
                                </td>
                                <td class="px-4 py-3" @click.stop>
                                    <button @click="openViewModal(payment)" class="p-1 rounded hover:bg-gray-100">
                                        <EyeIcon class="h-5 w-5 text-gray-400" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="payments.length === 0" class="py-12 text-center text-gray-500 text-sm">{{ __('no_paid_invoices') }}</div>
            </div>
        </div>

        <!-- View Modal -->
        <Modal :show="showViewModal" :title="__('payment_details')" @close="showViewModal = false">
            <div v-if="viewingPayment" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div><p class="text-sm font-medium text-gray-500">{{ __('supplier') }}</p><p class="text-sm text-gray-900">{{ viewingPayment.supplier?.name || 'N/A' }}</p></div>
                    <div><p class="text-sm font-medium text-gray-500">{{ __('branch') }}</p><p class="text-sm text-gray-900">{{ viewingPayment.branch?.name || 'N/A' }}</p></div>
                    <div><p class="text-sm font-medium text-gray-500">{{ __('invoice_number') }}</p><p class="text-sm text-gray-900 font-mono">{{ viewingPayment.invoice_number || '-' }}</p></div>
                    <div><p class="text-sm font-medium text-gray-500">{{ __('amount') }}</p><p :class="['text-sm font-semibold', getCurrencyColor(viewingPayment.currency)]">{{ formatCurrency(viewingPayment.amount, viewingPayment.currency) }}</p></div>
                    <div><p class="text-sm font-medium text-gray-500">{{ __('status') }}</p><span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ __('paid_status') }}</span></div>
                    <div><p class="text-sm font-medium text-gray-500">{{ __('payment_date') }}</p><p class="text-sm text-gray-900 font-medium">{{ formatDate(viewingPayment.paid_date) }}</p></div>
                    <div><p class="text-sm font-medium text-gray-500">{{ __('planned_date') }}</p><p class="text-sm text-gray-500">{{ formatDate(viewingPayment.planned_date) }}</p></div>
                </div>
                <div v-if="viewingPayment.description"><p class="text-sm font-medium text-gray-500">{{ __('description') }}</p><p class="text-sm text-gray-900 mt-1">{{ viewingPayment.description }}</p></div>
                <div class="flex justify-between gap-3 pt-4">
                    <div class="flex gap-2">
                        <button v-if="page.props.auth.user?.canModify" @click="handleDeleteClick(viewingPayment)" class="px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-300 rounded-lg hover:bg-red-50">{{ __('delete') }}</button>
                    </div>
                    <div class="flex gap-2">
                        <button v-if="page.props.auth.user?.canModify" @click="handleMarkAsUnpaid(viewingPayment.id)" class="px-4 py-2 text-sm font-medium text-amber-700 bg-amber-100 rounded-lg hover:bg-amber-200">{{ __('mark_as_unpaid') }}</button>
                        <button @click="showViewModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">{{ __('close') }}</button>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Delete Confirm Modal -->
        <ConfirmModal
            :show="showDeleteConfirm"
            :title="__('delete') + ' ' + __('payments').slice(0, -1)"
            :message="`${__('delete_supplier_confirm').replace('{name}', paymentToDelete?.supplier?.name || 'N/A')}`"
            :confirmText="__('delete')"
            variant="danger"
            @confirm="confirmDelete"
            @cancel="showDeleteConfirm = false; paymentToDelete = null"
        />
    </MainLayout>
</template>
