<script setup>
import { usePage, router } from '@inertiajs/vue3';
import { format, parseISO } from 'date-fns';
import { EllipsisHorizontalIcon, EyeIcon, PencilIcon, CheckCircleIcon, XCircleIcon, TrashIcon, ChevronUpIcon, ChevronDownIcon, BookmarkIcon } from '@heroicons/vue/24/outline';
import { ref, computed, nextTick } from 'vue';
import Modal from '@/Components/Modal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import DateInput from '@/Components/DateInput.vue';
import { useTranslations } from '@/composables/useTranslations';

const { __ } = useTranslations();

const props = defineProps({ 
    payments: Array, 
    selectedIds: Array,
    sortBy: String,
    sortDirection: String,
    plans: Array
});
const emit = defineEmits(['selectAll', 'selectOne', 'markAsPaid', 'edit', 'view', 'sort']);

const page = usePage();
const openMenuId = ref(null);
const menuPosition = ref({ top: 0, left: 0 });
const showViewModal = ref(false);
const showMarkPaidModal = ref(false);
const viewingPayment = ref(null);
const paymentToMarkPaid = ref(null);
const showDeleteConfirm = ref(false);
const paymentToDelete = ref(null);
const paidDate = ref(new Date().toISOString().split('T')[0]);
const showAddToPlanModal = ref(false);
const paymentToAddToPlan = ref(null);
const selectedPlanId = ref('');

const formatCurrency = (amount, currency) => new Intl.NumberFormat('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount) + ' ' + currency;

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try { return format(typeof dateStr === 'string' ? parseISO(dateStr) : dateStr, 'dd.MM.yyyy'); } 
    catch { return dateStr; }
};

const plannedPayments = computed(() => props.payments.filter(p => p.status === 'PLANNED'));
const allSelected = () => plannedPayments.value.length > 0 && props.selectedIds.length === plannedPayments.value.length;
const currentPayment = computed(() => props.payments.find(p => p.id === openMenuId.value));

// Filter plans to exclude those that already contain the payment being added
const availablePlans = computed(() => {
    if (!paymentToAddToPlan.value) return props.plans;
    
    return props.plans.filter(plan => {
        const paymentIds = plan.used_payment_ids || [];
        return !paymentIds.includes(paymentToAddToPlan.value.id);
    });
});

const openMenu = async (event, paymentId) => {
    event.stopPropagation();
    const rect = event.currentTarget.getBoundingClientRect();
    menuPosition.value = { top: rect.bottom + 4, left: rect.right - 192 };
    openMenuId.value = paymentId;
    await nextTick();
    if (menuPosition.value.top + 150 > window.innerHeight) menuPosition.value.top = rect.top - 154;
};

const closeMenu = () => { openMenuId.value = null; };
const handleView = (payment) => { viewingPayment.value = payment; showViewModal.value = true; closeMenu(); };
const handleEdit = (payment) => { emit('edit', payment); closeMenu(); showViewModal.value = false; };
const handleMarkAsPaidClick = (payment) => { 
    paymentToMarkPaid.value = payment;
    paidDate.value = new Date().toISOString().split('T')[0];
    showMarkPaidModal.value = true;
    closeMenu(); 
};
const confirmMarkAsPaid = () => {
    if (!paymentToMarkPaid.value) return;
    router.post(route('payments.mark-paid', paymentToMarkPaid.value.id), { paid_date: paidDate.value }, { 
        preserveScroll: true,
        onSuccess: () => {
            showMarkPaidModal.value = false;
            showViewModal.value = false;
            paymentToMarkPaid.value = null;
        }
    });
};
const handleMarkAsUnpaid = (id) => { 
    router.post(route('payments.mark-unpaid', id), {}, { preserveScroll: true });
    closeMenu(); 
};
const handleDeleteClick = (payment) => {
    paymentToDelete.value = payment;
    showDeleteConfirm.value = true;
    closeMenu();
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
const handleAddToPlanClick = (payment) => {
    paymentToAddToPlan.value = payment;
    selectedPlanId.value = '';
    showAddToPlanModal.value = true;
    closeMenu();
};
const confirmAddToPlan = () => {
    if (!paymentToAddToPlan.value || !selectedPlanId.value) return;
    router.post(route('plans.add-payment', selectedPlanId.value), { 
        payment_id: paymentToAddToPlan.value.id 
    }, { 
        preserveScroll: true,
        onSuccess: () => {
            showAddToPlanModal.value = false;
            showViewModal.value = false;
            paymentToAddToPlan.value = null;
            selectedPlanId.value = '';
        }
    });
};
const handleRowClick = (event, payment) => {
    if (event.target.type === 'checkbox') return;
    handleView(payment);
};

const handleSort = (column) => {
    emit('sort', column);
};

const isSorted = (column) => {
    return props.sortBy === column;
};

const getSortDirection = (column) => {
    if (props.sortBy !== column) return 'none';
    return props.sortDirection;
};
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="w-12 px-4 py-3"><input type="checkbox" :checked="allSelected()" @change="emit('selectAll', $event.target.checked)" class="h-4 w-4 rounded border-gray-300 text-blue-600" /></th>
                        <th class="px-4 py-3 text-left">
                            <button @click="handleSort('invoice_number')" class="flex items-center gap-1.5 text-xs font-semibold uppercase text-gray-500 hover:text-gray-700 transition-colors">
                                {{ __('invoice_number') }}
                                <span class="flex flex-col -space-y-1">
                                    <ChevronUpIcon :class="['h-3 w-3 transition-colors', isSorted('invoice_number') && getSortDirection('invoice_number') === 'asc' ? 'text-blue-600' : 'text-gray-300']" />
                                    <ChevronDownIcon :class="['h-3 w-3 transition-colors', isSorted('invoice_number') && getSortDirection('invoice_number') === 'desc' ? 'text-blue-600' : 'text-gray-300']" />
                                </span>
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left">
                            <button @click="handleSort('supplier')" class="flex items-center gap-1.5 text-xs font-semibold uppercase text-gray-500 hover:text-gray-700 transition-colors">
                                {{ __('supplier') }}
                                <span class="flex flex-col -space-y-1">
                                    <ChevronUpIcon :class="['h-3 w-3 transition-colors', isSorted('supplier') && getSortDirection('supplier') === 'asc' ? 'text-blue-600' : 'text-gray-300']" />
                                    <ChevronDownIcon :class="['h-3 w-3 transition-colors', isSorted('supplier') && getSortDirection('supplier') === 'desc' ? 'text-blue-600' : 'text-gray-300']" />
                                </span>
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left">
                            <button @click="handleSort('branch')" class="flex items-center gap-1.5 text-xs font-semibold uppercase text-gray-500 hover:text-gray-700 transition-colors">
                                {{ __('branch') }}
                                <span class="flex flex-col -space-y-1">
                                    <ChevronUpIcon :class="['h-3 w-3 transition-colors', isSorted('branch') && getSortDirection('branch') === 'asc' ? 'text-blue-600' : 'text-gray-300']" />
                                    <ChevronDownIcon :class="['h-3 w-3 transition-colors', isSorted('branch') && getSortDirection('branch') === 'desc' ? 'text-blue-600' : 'text-gray-300']" />
                                </span>
                            </button>
                        </th>
                        <th class="px-4 py-3 text-right">
                            <button @click="handleSort('amount')" class="flex items-center gap-1.5 ml-auto text-xs font-semibold uppercase text-gray-500 hover:text-gray-700 transition-colors">
                                {{ __('amount') }}
                                <span class="flex flex-col -space-y-1">
                                    <ChevronUpIcon :class="['h-3 w-3 transition-colors', isSorted('amount') && getSortDirection('amount') === 'asc' ? 'text-blue-600' : 'text-gray-300']" />
                                    <ChevronDownIcon :class="['h-3 w-3 transition-colors', isSorted('amount') && getSortDirection('amount') === 'desc' ? 'text-blue-600' : 'text-gray-300']" />
                                </span>
                            </button>
                        </th>
                        <th class="px-4 py-3 text-center">
                            <button @click="handleSort('status')" class="flex items-center gap-1.5 mx-auto text-xs font-semibold uppercase text-gray-500 hover:text-gray-700 transition-colors">
                                {{ __('status') }}
                                <span class="flex flex-col -space-y-1">
                                    <ChevronUpIcon :class="['h-3 w-3 transition-colors', isSorted('status') && getSortDirection('status') === 'asc' ? 'text-blue-600' : 'text-gray-300']" />
                                    <ChevronDownIcon :class="['h-3 w-3 transition-colors', isSorted('status') && getSortDirection('status') === 'desc' ? 'text-blue-600' : 'text-gray-300']" />
                                </span>
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left">
                            <button @click="handleSort('planned_date')" class="flex items-center gap-1.5 text-xs font-semibold uppercase text-gray-500 hover:text-gray-700 transition-colors">
                                {{ __('pay_by') }}
                                <span class="flex flex-col -space-y-1">
                                    <ChevronUpIcon :class="['h-3 w-3 transition-colors', isSorted('planned_date') && getSortDirection('planned_date') === 'asc' ? 'text-blue-600' : 'text-gray-300']" />
                                    <ChevronDownIcon :class="['h-3 w-3 transition-colors', isSorted('planned_date') && getSortDirection('planned_date') === 'desc' ? 'text-blue-600' : 'text-gray-300']" />
                                </span>
                            </button>
                        </th>
                        <th class="w-12 px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="payment in payments" :key="payment.id" @click="handleRowClick($event, payment)" :class="['hover:bg-gray-50 cursor-pointer', selectedIds.includes(payment.id) && 'bg-blue-50']">
                        <td class="px-4 py-3" @click.stop><input v-if="payment.status === 'PLANNED'" type="checkbox" :checked="selectedIds.includes(payment.id)" @change="emit('selectOne', payment.id, $event.target.checked)" class="h-4 w-4 rounded border-gray-300 text-blue-600" /></td>
                        <td class="px-4 py-3"><p class="text-sm text-gray-600 font-mono">{{ payment.invoice_number || '-' }}</p></td>
                        <td class="px-4 py-3"><p class="font-medium text-sm text-gray-900">{{ payment.supplier?.name || 'N/A' }}</p></td>
                        <td class="px-4 py-3"><p class="text-sm text-gray-500">{{ payment.branch?.name || 'N/A' }}</p></td>
                        <td class="px-4 py-3 text-right"><span :class="['font-semibold text-sm', payment.currency === 'KM' ? 'text-blue-600' : payment.currency === 'EUR' ? 'text-green-600' : 'text-purple-600']">{{ formatCurrency(payment.amount, payment.currency) }}</span></td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex flex-col items-center gap-1">
                                <span :class="['px-2.5 py-0.5 rounded-full text-xs font-medium', payment.status === 'PAID' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800']">
                                    {{ payment.status === 'PAID' ? __('paid_status') : __('unpaid_status') }}
                                </span>
                                <span v-if="payment.status === 'PAID' && payment.paid_date" class="text-xs text-green-600 font-medium">
                                    {{ formatDate(payment.paid_date) }}
                                </span>
                                <div v-if="payment.plan_names && payment.plan_names.length > 0" class="flex flex-col gap-0.5 mt-1">
                                    <span v-for="planName in payment.plan_names" :key="planName" class="text-xs text-blue-600 font-medium flex items-center gap-1">
                                        <BookmarkIcon class="h-3 w-3" />
                                        {{ planName }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3"><p class="text-sm text-gray-500">{{ formatDate(payment.planned_date) }}</p></td>
                        <td class="px-4 py-3" @click.stop><button @click="openMenu($event, payment.id)" class="p-1 rounded hover:bg-gray-100"><EllipsisHorizontalIcon class="h-5 w-5 text-gray-400" /></button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-if="payments.length === 0" class="py-12 text-center text-gray-500 text-sm">{{ __('no_payments_to_display') }}</div>
    </div>

    <Teleport to="body">
        <div v-if="openMenuId !== null && currentPayment">
            <div @click="closeMenu" class="fixed inset-0 z-40"></div>
            <div class="fixed w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50" :style="{ top: menuPosition.top + 'px', left: menuPosition.left + 'px' }">
                <button @click="handleView(currentPayment)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><EyeIcon class="h-4 w-4" /> {{ __('view_payment') }}</button>
                <template v-if="page.props.auth.user?.canModify">
                    <button @click="handleEdit(currentPayment)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><PencilIcon class="h-4 w-4" /> {{ __('edit') }}</button>
                    <hr class="my-1" />
                    <button @click="handleAddToPlanClick(currentPayment)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-blue-600 hover:bg-gray-50"><BookmarkIcon class="h-4 w-4" /> Dodaj u plan</button>
                    <hr class="my-1" />
                    <button v-if="currentPayment.status === 'PLANNED'" @click="handleMarkAsPaidClick(currentPayment)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-green-600 hover:bg-gray-50"><CheckCircleIcon class="h-4 w-4" /> {{ __('mark_as_paid') }}</button>
                    <button v-if="currentPayment.status === 'PAID'" @click="handleMarkAsUnpaid(currentPayment.id)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-amber-600 hover:bg-gray-50"><XCircleIcon class="h-4 w-4" /> {{ __('return_to_unpaid') }}</button>
                    <hr class="my-1" />
                    <button @click="handleDeleteClick(currentPayment)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50"><TrashIcon class="h-4 w-4" /> {{ __('delete') }}</button>
                </template>
            </div>
        </div>
    </Teleport>

    <Modal :show="showViewModal" :title="__('payment_details')" @close="showViewModal = false">
        <div v-if="viewingPayment" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div><p class="text-sm font-medium text-gray-500">{{ __('supplier') }}</p><p class="text-sm text-gray-900">{{ viewingPayment.supplier?.name || 'N/A' }}</p></div>
                <div><p class="text-sm font-medium text-gray-500">{{ __('branch') }}</p><p class="text-sm text-gray-900">{{ viewingPayment.branch?.name || 'N/A' }}</p></div>
                <div><p class="text-sm font-medium text-gray-500">{{ __('invoice_number') }}</p><p class="text-sm text-gray-900 font-mono">{{ viewingPayment.invoice_number || '-' }}</p></div>
                <div><p class="text-sm font-medium text-gray-500">{{ __('amount') }}</p><p :class="['text-sm font-semibold', viewingPayment.currency === 'KM' ? 'text-blue-600' : viewingPayment.currency === 'EUR' ? 'text-green-600' : 'text-purple-600']">{{ formatCurrency(viewingPayment.amount, viewingPayment.currency) }}</p></div>
                <div><p class="text-sm font-medium text-gray-500">{{ __('status') }}</p><span :class="['px-2.5 py-0.5 rounded-full text-xs font-medium', viewingPayment.status === 'PAID' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800']">{{ viewingPayment.status === 'PAID' ? __('paid_status') : __('unpaid_status') }}</span></div>
                <div><p class="text-sm font-medium text-gray-500">{{ __('planned_date') }}</p><p class="text-sm text-gray-900">{{ formatDate(viewingPayment.planned_date) }}</p></div>
                <div v-if="viewingPayment.paid_date"><p class="text-sm font-medium text-gray-500">{{ __('payment_date') }}</p><p class="text-sm text-gray-900">{{ formatDate(viewingPayment.paid_date) }}</p></div>
            </div>
            <div v-if="viewingPayment.description"><p class="text-sm font-medium text-gray-500">{{ __('description') }}</p><p class="text-sm text-gray-900 mt-1">{{ viewingPayment.description }}</p></div>
            <div class="flex justify-between gap-3 pt-4">
                <div class="flex gap-2">
                    <button v-if="page.props.auth.user?.canModify" @click="handleDeleteClick(viewingPayment)" class="px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-300 rounded-lg hover:bg-red-50">{{ __('delete') }}</button>
                </div>
                <div class="flex gap-2">
                    <button v-if="viewingPayment.status === 'PLANNED' && page.props.auth.user?.canModify" @click="handleMarkAsPaidClick(viewingPayment)" class="px-4 py-2 text-sm font-medium text-white bg-green-500 rounded-lg hover:bg-green-600">{{ __('mark_as_paid') }}</button>
                    <button v-if="viewingPayment.status === 'PAID' && page.props.auth.user?.canModify" @click="handleMarkAsUnpaid(viewingPayment.id); showViewModal = false" class="px-4 py-2 text-sm font-medium text-amber-700 bg-amber-100 rounded-lg hover:bg-amber-200">{{ __('mark_as_unpaid') }}</button>
                    <button @click="showViewModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">{{ __('close') }}</button>
                    <button v-if="page.props.auth.user?.canModify" @click="handleEdit(viewingPayment)" class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600">{{ __('edit') }}</button>
                </div>
            </div>
        </div>
    </Modal>

    <!-- Mark as Paid Modal -->
    <Modal :show="showMarkPaidModal" :title="__('mark_as_paid')" @close="showMarkPaidModal = false; paymentToMarkPaid = null">
        <form @submit.prevent="confirmMarkAsPaid" class="space-y-4">
            <div v-if="paymentToMarkPaid" class="rounded-lg bg-green-50 border border-green-200 p-4">
                <p class="text-sm text-green-800">
                    {{ __('mark_payment_as_paid').replace('{name}', paymentToMarkPaid.supplier?.name || 'N/A') }}
                </p>
                <p class="text-xs text-green-700 mt-1">{{ __('amount') }}: {{ formatCurrency(paymentToMarkPaid.amount, paymentToMarkPaid.currency) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('payment_date') }} {{ __('required') }}</label>
                <DateInput v-model="paidDate" :required="true" />
                <p class="mt-1 text-xs text-gray-500">{{ __('select_payment_date') }}</p>
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <button type="button" @click="showMarkPaidModal = false; paymentToMarkPaid = null" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">{{ __('cancel') }}</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-500 rounded-lg hover:bg-green-600">
                    {{ __('mark_as_paid') }}
                </button>
            </div>
        </form>
    </Modal>

    <!-- Delete Confirm Modal -->
    <ConfirmModal
        :show="showDeleteConfirm"
        :title="__('delete_payment')"
        :message="__('delete_payment_confirm').replace('{name}', paymentToDelete?.supplier?.name || 'N/A')"
        :confirmText="__('delete')"
        variant="danger"
        @confirm="confirmDelete"
        @cancel="showDeleteConfirm = false; paymentToDelete = null"
    />

    <!-- Add to Plan Modal -->
    <Modal :show="showAddToPlanModal" title="Dodaj u plan" @close="showAddToPlanModal = false; paymentToAddToPlan = null; selectedPlanId = ''">
        <form @submit.prevent="confirmAddToPlan" class="space-y-4">
            <div v-if="paymentToAddToPlan" class="rounded-lg bg-blue-50 border border-blue-200 p-4">
                <p class="text-sm text-blue-800 font-medium">{{ paymentToAddToPlan.supplier?.name || 'N/A' }}</p>
                <p class="text-xs text-blue-700 mt-1">{{ __('amount') }}: {{ formatCurrency(paymentToAddToPlan.amount, paymentToAddToPlan.currency) }}</p>
                <p v-if="paymentToAddToPlan.invoice_number" class="text-xs text-blue-700">Br. fakture: {{ paymentToAddToPlan.invoice_number }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Odaberi plan</label>
                <select v-model="selectedPlanId" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Odaberi plan --</option>
                    <option v-for="plan in availablePlans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                </select>
                <p v-if="availablePlans.length === 0" class="mt-2 text-sm text-amber-600">
                    Ovo plaćanje je već dodano u sve dostupne planove.
                </p>
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <button type="button" @click="showAddToPlanModal = false; paymentToAddToPlan = null; selectedPlanId = ''" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">{{ __('cancel') }}</button>
                <button type="submit" :disabled="!selectedPlanId" class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed">
                    Dodaj u plan
                </button>
            </div>
        </form>
    </Modal>
</template>
