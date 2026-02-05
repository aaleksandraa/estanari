<script setup>
import { ref, computed } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import DateInput from '@/Components/DateInput.vue';
import { format } from 'date-fns';
import { useTranslations } from '@/composables/useTranslations';
import {
    ArrowLeftIcon,
    ArrowDownTrayIcon,
    CalendarIcon,
    BanknotesIcon,
    CurrencyEuroIcon,
    ClipboardDocumentListIcon,
    PlusIcon,
    TrashIcon,
    DocumentArrowDownIcon,
    MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon as CheckCircleSolidIcon } from '@heroicons/vue/24/solid';

const { t } = useTranslations();

const props = defineProps({
    plan: Object,
    payments: Array,
    availablePayments: Array,
    exchangeRates: Object,
});

const page = usePage();

const showConfirmModal = ref(false);
const showAddPaymentModal = ref(false);
const showAddCustomModal = ref(false);
const showRemoveConfirm = ref(false);
const showMarkPaidModal = ref(false);
const paymentToRemove = ref(null);
const paymentSearchQuery = ref('');
const paidDate = ref(new Date().toISOString().split('T')[0]);

const customForm = useForm({
    description: '',
    amount: '',
    currency: 'KM',
    planned_date: new Date().toISOString().split('T')[0],
});

const plannedPaymentsCount = computed(() => props.payments?.length || 0);

const canModify = computed(() => {
    return !props.plan.is_paid && page.props.auth?.user;
});

// Check if there are EUR or USD payments
const hasEurOrUsd = computed(() => {
    return props.payments?.some(p => p.currency === 'EUR' || p.currency === 'USD') || false;
});

// Calculate grand total in KM
const grandTotalKM = computed(() => {
    if (!props.payments) return 0;
    return props.payments.reduce((sum, p) => sum + (p.amount_in_km || p.amount), 0);
});

const filteredAvailablePayments = computed(() => {
    if (!paymentSearchQuery.value.trim()) {
        return props.availablePayments;
    }
    const query = paymentSearchQuery.value.toLowerCase().trim();
    return props.availablePayments.filter(payment => {
        const supplierName = payment.supplier?.name?.toLowerCase() || '';
        const branchName = payment.branch?.name?.toLowerCase() || '';
        const invoiceNumber = payment.invoice_number?.toLowerCase() || '';
        const description = payment.description?.toLowerCase() || '';
        return supplierName.includes(query) || 
               branchName.includes(query) || 
               invoiceNumber.includes(query) ||
               description.includes(query);
    });
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const date = typeof dateStr === 'string' ? new Date(dateStr) : dateStr;
        return format(date, 'dd.MM.yyyy');
    } catch {
        return dateStr;
    }
};

const formatCurrency = (amount, currency) => {
    return new Intl.NumberFormat('de-DE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount) + ' ' + currency;
};

const goBack = () => router.visit(route('plans.index'));
const exportCsv = () => window.location.href = route('plans.export-csv', props.plan.id);
const exportPdf = () => window.location.href = route('plans.export-pdf', props.plan.id);
const exportExcel = () => window.location.href = route('plans.export-excel', props.plan.id);

const openMarkAsPaidConfirm = () => {
    paidDate.value = new Date().toISOString().split('T')[0];
    showMarkPaidModal.value = true;
};

const handleConfirm = () => {
    router.post(route('plans.mark-paid', props.plan.id), { paid_date: paidDate.value }, {
        preserveScroll: true,
        onSuccess: () => {
            showMarkPaidModal.value = false;
        },
    });
};

const addPaymentToPlan = (paymentId) => {
    router.post(route('plans.add-payment', props.plan.id), {
        payment_id: paymentId,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showAddPaymentModal.value = false;
        },
    });
};

const openAddCustomModal = () => {
    customForm.reset();
    customForm.planned_date = new Date().toISOString().split('T')[0];
    customForm.currency = 'KM';
    showAddCustomModal.value = true;
};

const submitCustomItem = () => {
    customForm.post(route('plans.add-custom', props.plan.id), {
        preserveScroll: true,
        onSuccess: () => {
            showAddCustomModal.value = false;
            customForm.reset();
        },
    });
};

const openRemoveConfirm = (payment) => {
    paymentToRemove.value = payment;
    showRemoveConfirm.value = true;
};

const confirmRemove = () => {
    if (!paymentToRemove.value) return;
    router.post(route('plans.remove-payment', [props.plan.id, paymentToRemove.value.id]), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showRemoveConfirm.value = false;
            paymentToRemove.value = null;
        },
    });
};

const getDateFilterLabel = (filter) => {
    const labels = {
        today: t('today'),
        tomorrow: t('tomorrow'),
        '3days': t('3days'),
        '7days': t('7days'),
        period: t('period'),
        all: t('all'),
        custom: t('custom'),
    };
    return labels[filter] || filter;
};

const getCurrencyColor = (currency) => {
    if (currency === 'KM') return 'text-blue-600';
    if (currency === 'EUR') return 'text-green-600';
    return 'text-purple-600';
};
</script>

<template>
    <MainLayout>
        <Header :title="plan.name" />
        <div class="p-6 space-y-6">
            <!-- Back & Actions -->
            <div class="flex items-center justify-between flex-wrap gap-3">
                <button @click="goBack" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900">
                    <ArrowLeftIcon class="h-4 w-4" /> {{ t('back_to_plans') }}
                </button>
                <div class="flex items-center gap-2 flex-wrap">
                    <button 
                        v-if="canModify"
                        @click="showAddPaymentModal = true" 
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100">
                        <PlusIcon class="h-4 w-4" /> {{ t('add_payment_to_plan') }}
                    </button>
                    <button 
                        v-if="canModify" 
                        @click="openAddCustomModal" 
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-purple-600 bg-purple-50 rounded-lg hover:bg-purple-100">
                        <PlusIcon class="h-4 w-4" /> {{ t('add_custom_item') }}
                    </button>
                    <button 
                        v-if="!plan.is_paid && plannedPaymentsCount > 0 && canModify" 
                        @click="openMarkAsPaidConfirm" 
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-500 rounded-lg hover:bg-green-600">
                        <CheckCircleSolidIcon class="h-4 w-4" /> {{ t('mark_as_paid') }}
                    </button>
                    <button @click="exportExcel" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                        <ArrowDownTrayIcon class="h-4 w-4" /> {{ t('excel') }}
                    </button>
                    <button @click="exportCsv" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        <ArrowDownTrayIcon class="h-4 w-4" /> {{ t('csv') }}
                    </button>
                    <button @click="exportPdf" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        <DocumentArrowDownIcon class="h-4 w-4" /> {{ t('pdf') }}
                    </button>
                </div>
            </div>

            <!-- Plan Info Card -->
            <div :class="['rounded-xl overflow-hidden shadow-sm', plan.is_paid ? 'bg-green-500' : 'bg-blue-500']">
                <div :class="['px-6 py-5 text-white', plan.is_paid ? 'bg-green-600' : 'bg-blue-600']">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold">{{ plan.name }}</h2>
                            <p v-if="plan.description" class="opacity-90 mt-1">{{ plan.description }}</p>
                        </div>
                        <div v-if="plan.is_paid" class="flex items-center gap-2 bg-white/20 px-3 py-1.5 rounded-full">
                            <CheckCircleSolidIcon class="h-5 w-5" />
                            <span class="text-sm font-medium">{{ t('paid_status') }}</span>
                        </div>
                    </div>
                </div>
                <div :class="['grid grid-cols-2 md:grid-cols-5 divide-x divide-y md:divide-y-0', plan.is_paid ? 'bg-green-50 divide-green-200' : 'bg-blue-50 divide-blue-200']">
                    <div class="p-5 text-center">
                        <div class="flex items-center justify-center gap-2 text-gray-500 mb-2">
                            <BanknotesIcon class="h-5 w-5" />
                            <span class="text-sm">{{ t('total_km') }}</span>
                        </div>
                        <p :class="['text-2xl font-bold', plan.is_paid ? 'text-green-600' : 'text-blue-600']">{{ formatCurrency(plan.total_km, 'KM') }}</p>
                    </div>
                    <div class="p-5 text-center">
                        <div class="flex items-center justify-center gap-2 text-gray-500 mb-2">
                            <CurrencyEuroIcon class="h-5 w-5" />
                            <span class="text-sm">{{ t('total_eur') }}</span>
                        </div>
                        <p class="text-2xl font-bold text-green-600">{{ formatCurrency(plan.total_eur, 'EUR') }}</p>
                    </div>
                    <div class="p-5 text-center">
                        <div class="flex items-center justify-center gap-2 text-gray-500 mb-2">
                            <BanknotesIcon class="h-5 w-5" />
                            <span class="text-sm">{{ t('total_usd') }}</span>
                        </div>
                        <p class="text-2xl font-bold text-purple-600">{{ formatCurrency(plan.total_usd || 0, 'USD') }}</p>
                    </div>
                    <div class="p-5 text-center">
                        <div class="flex items-center justify-center gap-2 text-gray-500 mb-2">
                            <ClipboardDocumentListIcon class="h-5 w-5" />
                            <span class="text-sm">{{ t('payment_count') }}</span>
                        </div>
                        <p class="text-2xl font-bold text-gray-800">{{ plan.payment_count }}</p>
                    </div>
                    <div class="p-5 text-center">
                        <div class="flex items-center justify-center gap-2 text-gray-500 mb-2">
                            <CalendarIcon class="h-5 w-5" />
                            <span class="text-sm">{{ t('created_at') }}</span>
                        </div>
                        <p class="text-lg font-semibold text-gray-800">{{ formatDate(plan.created_at) }}</p>
                    </div>
                </div>
                <!-- Grand Total KM row (only if EUR/USD exists) -->
                <div v-if="hasEurOrUsd" :class="['px-6 py-4 border-t', plan.is_paid ? 'bg-green-100 border-green-200' : 'bg-blue-100 border-blue-200']">
                    <div class="flex items-center justify-center gap-3">
                        <span class="text-sm font-medium text-gray-600">{{ t('total_converted_km').toUpperCase() }}:</span>
                        <span :class="['text-xl font-bold', plan.is_paid ? 'text-green-700' : 'text-blue-700']">
                            {{ formatCurrency(grandTotalKM, 'KM') }}
                        </span>
                        <span class="text-xs text-gray-500">(EUR: {{ exchangeRates?.EUR || 1.95583 }} KM, USD: {{ exchangeRates?.USD || 1.80 }} KM)</span>
                    </div>
                </div>
                <div :class="['px-6 py-3 border-t', plan.is_paid ? 'bg-green-50 border-green-200' : 'bg-blue-50 border-blue-200']">
                    <div class="flex items-center justify-between flex-wrap gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-gray-500">{{ t('description') }}:</span>
                            <span class="font-medium text-gray-700">{{ plan.description || '-' }}</span>
                        </div>
                        <div v-if="plan.scheduled_date" class="flex items-center gap-2">
                            <span class="text-gray-500">{{ t('plan_for') }}:</span>
                            <span class="font-semibold text-gray-800">{{ formatDate(plan.scheduled_date) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="font-semibold text-gray-800">{{ t('plan_items') }} ({{ payments.length }})</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ t('description') }} / {{ t('supplier') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ t('branch') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ t('invoice_number') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ t('amount') }}</th>
                                <th v-if="hasEurOrUsd" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ t('total_km') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ t('status') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{{ t('date') }}</th>
                                <th v-if="canModify" class="px-4 py-3 w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="payment in payments" :key="payment.id" :class="['hover:bg-gray-50', payment.status === 'PAID' && 'bg-green-50/50']">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-sm text-gray-900">
                                        {{ payment.supplier?.name || payment.description || t('custom_item') }}
                                    </p>
                                    <p v-if="payment.supplier && payment.description" class="text-xs text-gray-500 mt-0.5">{{ payment.description }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-500">{{ payment.branch?.name || '-' }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-mono text-sm text-gray-600">{{ payment.invoice_number || '-' }}</p>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span :class="['font-semibold text-sm', getCurrencyColor(payment.currency)]">
                                        {{ formatCurrency(payment.amount, payment.currency) }}
                                    </span>
                                </td>
                                <td v-if="hasEurOrUsd" class="px-4 py-3 text-right">
                                    <span class="font-semibold text-sm text-gray-700">
                                        {{ formatCurrency(payment.amount_in_km || payment.amount, 'KM') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="['px-2.5 py-0.5 rounded-full text-xs font-medium', payment.status === 'PAID' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800']">
                                        {{ payment.status === 'PAID' ? t('paid_status') : t('unpaid_status') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-500">{{ formatDate(payment.planned_date) }}</p>
                                </td>
                                <td v-if="canModify" class="px-4 py-3">
                                    <button 
                                        @click="openRemoveConfirm(payment)" 
                                        class="p-1 text-gray-400 hover:text-red-500 rounded hover:bg-red-50">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="payments.length === 0" class="py-12 text-center text-gray-500 text-sm">
                    {{ t('no_payments_in_plan') }}
                </div>
            </div>
        </div>

        <!-- Add Payment Modal -->
        <Modal :show="showAddPaymentModal" :title="t('add_payment_to_plan')" @close="showAddPaymentModal = false; paymentSearchQuery = ''">
            <div class="space-y-4">
                <p class="text-sm text-gray-500">{{ t('select_payment_to_add') }}</p>
                
                <!-- Search input -->
                <div class="relative">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input 
                        v-model="paymentSearchQuery" 
                        type="search" 
                        :placeholder="t('search_by_supplier')" 
                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                    />
                </div>

                <div v-if="availablePayments.length === 0" class="py-8 text-center text-gray-500">
                    {{ t('no_available_payments') }}
                </div>
                <div v-else-if="filteredAvailablePayments.length === 0" class="py-8 text-center text-gray-500">
                    {{ t('no_search_results') }} "{{ paymentSearchQuery }}"
                </div>
                <div v-else class="max-h-80 overflow-y-auto space-y-2">
                    <button 
                        v-for="payment in filteredAvailablePayments" 
                        :key="payment.id"
                        @click="addPaymentToPlan(payment.id)"
                        class="w-full p-3 text-left border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-sm text-gray-900">{{ payment.supplier?.name || 'N/A' }}</p>
                                <p class="text-xs text-gray-500">{{ payment.branch?.name || '' }} {{ payment.invoice_number ? '• ' + payment.invoice_number : '' }}</p>
                            </div>
                            <div class="text-right">
                                <p :class="['font-semibold text-sm', getCurrencyColor(payment.currency)]">
                                    {{ formatCurrency(payment.amount, payment.currency) }}
                                </p>
                                <p class="text-xs text-gray-500">{{ formatDate(payment.planned_date) }}</p>
                            </div>
                        </div>
                    </button>
                </div>
                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <span class="text-xs text-gray-400">{{ t('of_payments', { count: filteredAvailablePayments.length, total: availablePayments.length }) }}</span>
                    <button 
                        type="button"
                        @click="showAddPaymentModal = false; paymentSearchQuery = ''" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        {{ t('close') }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Add Custom Item Modal -->
        <Modal :show="showAddCustomModal" :title="t('add_custom_item_title')" @close="showAddCustomModal = false">
            <form @submit.prevent="submitCustomItem" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('description_required') }}</label>
                    <input 
                        v-model="customForm.description" 
                        type="text" 
                        required
                        :placeholder="t('description_placeholder')"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                    />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('amount_required') }}</label>
                        <input 
                            v-model="customForm.amount" 
                            type="number" 
                            step="0.01" 
                            min="0.01" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('currency_required') }}</label>
                        <select 
                            v-model="customForm.currency" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="KM">KM</option>
                            <option value="EUR">EUR</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('date_required') }}</label>
                    <DateInput v-model="customForm.planned_date" :required="true" />
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button 
                        type="button"
                        @click="showAddCustomModal = false" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        {{ t('cancel') }}
                    </button>
                    <button 
                        type="submit" 
                        :disabled="customForm.processing"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50">
                        {{ customForm.processing ? t('adding') : t('add') }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Mark as Paid Modal -->
        <Modal :show="showMarkPaidModal" :title="t('mark_plan_as_paid_title')" @close="showMarkPaidModal = false">
            <form @submit.prevent="handleConfirm" class="space-y-4">
                <div class="rounded-lg bg-green-50 border border-green-200 p-4">
                    <p class="text-sm text-green-800">
                        {{ t('mark_plan_as_paid_desc', { name: plan.name }) }}
                    </p>
                    <p class="text-xs text-green-700 mt-1">
                        {{ t('all_planned_payments_marked', { count: plannedPaymentsCount }) }}
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('payment_date') }} {{ t('required') }}</label>
                    <DateInput v-model="paidDate" :required="true" />
                    <p class="mt-1 text-xs text-gray-500">{{ t('select_payment_date') }}</p>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showMarkPaidModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">{{ t('cancel') }}</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-500 rounded-lg hover:bg-green-600">
                        {{ t('mark_as_paid') }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Confirm Remove Modal -->
        <ConfirmModal
            :show="showRemoveConfirm"
            :title="t('remove_item_from_plan')"
            :message="t('remove_item_confirm', { name: paymentToRemove?.supplier?.name || paymentToRemove?.description || t('custom_item') })"
            :confirmText="t('remove')"
            variant="danger"
            @confirm="confirmRemove"
            @cancel="showRemoveConfirm = false; paymentToRemove = null"
        />
    </MainLayout>
</template>
