<script setup>
import { usePage, router } from '@inertiajs/vue3';
import { format, parseISO } from 'date-fns';
import { EllipsisHorizontalIcon, EyeIcon, PencilIcon, CheckCircleIcon, XCircleIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { ref, computed, nextTick } from 'vue';
import Modal from '@/Components/Modal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({ payments: Array, selectedIds: Array });
const emit = defineEmits(['selectAll', 'selectOne', 'markAsPaid', 'edit', 'view']);

const page = usePage();
const openMenuId = ref(null);
const menuPosition = ref({ top: 0, left: 0 });
const showViewModal = ref(false);
const viewingPayment = ref(null);
const showDeleteConfirm = ref(false);
const paymentToDelete = ref(null);

const formatCurrency = (amount, currency) => new Intl.NumberFormat('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount) + ' ' + currency;

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try { return format(typeof dateStr === 'string' ? parseISO(dateStr) : dateStr, 'dd.MM.yyyy'); } 
    catch { return dateStr; }
};

const plannedPayments = computed(() => props.payments.filter(p => p.status === 'PLANNED'));
const allSelected = () => plannedPayments.value.length > 0 && props.selectedIds.length === plannedPayments.value.length;
const currentPayment = computed(() => props.payments.find(p => p.id === openMenuId.value));

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
const handleMarkAsPaid = (id) => { emit('markAsPaid', id); closeMenu(); };
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
const handleRowClick = (event, payment) => {
    if (event.target.type === 'checkbox') return;
    handleView(payment);
};
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="w-12 px-4 py-3"><input type="checkbox" :checked="allSelected()" @change="emit('selectAll', $event.target.checked)" class="h-4 w-4 rounded border-gray-300 text-blue-600" /></th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Br. fakture</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Dobavljač</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Poslovnica</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Iznos</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Datum</th>
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
                        <td class="px-4 py-3 text-center"><span :class="['px-2.5 py-0.5 rounded-full text-xs font-medium', payment.status === 'PAID' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800']">{{ payment.status === 'PAID' ? 'Plaćeno' : 'Neplaćeno' }}</span></td>
                        <td class="px-4 py-3"><p class="text-sm text-gray-500">{{ formatDate(payment.status === 'PAID' ? payment.paid_date : payment.planned_date) }}</p></td>
                        <td class="px-4 py-3" @click.stop><button @click="openMenu($event, payment.id)" class="p-1 rounded hover:bg-gray-100"><EllipsisHorizontalIcon class="h-5 w-5 text-gray-400" /></button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-if="payments.length === 0" class="py-12 text-center text-gray-500 text-sm">Nema plaćanja za prikaz</div>
    </div>

    <Teleport to="body">
        <div v-if="openMenuId !== null && currentPayment">
            <div @click="closeMenu" class="fixed inset-0 z-40"></div>
            <div class="fixed w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50" :style="{ top: menuPosition.top + 'px', left: menuPosition.left + 'px' }">
                <button @click="handleView(currentPayment)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><EyeIcon class="h-4 w-4" /> Pregledaj</button>
                <template v-if="page.props.auth.user?.canModify">
                    <button @click="handleEdit(currentPayment)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"><PencilIcon class="h-4 w-4" /> Uredi</button>
                    <hr class="my-1" />
                    <button v-if="currentPayment.status === 'PLANNED'" @click="handleMarkAsPaid(currentPayment.id)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-green-600 hover:bg-gray-50"><CheckCircleIcon class="h-4 w-4" /> Označi kao plaćeno</button>
                    <button v-if="currentPayment.status === 'PAID'" @click="handleMarkAsUnpaid(currentPayment.id)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-amber-600 hover:bg-gray-50"><XCircleIcon class="h-4 w-4" /> Vrati u neplaćeno</button>
                    <hr class="my-1" />
                    <button @click="handleDeleteClick(currentPayment)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50"><TrashIcon class="h-4 w-4" /> Obriši</button>
                </template>
            </div>
        </div>
    </Teleport>

    <Modal :show="showViewModal" title="Detalji plaćanja" @close="showViewModal = false">
        <div v-if="viewingPayment" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div><p class="text-sm font-medium text-gray-500">Dobavljač</p><p class="text-sm text-gray-900">{{ viewingPayment.supplier?.name || 'N/A' }}</p></div>
                <div><p class="text-sm font-medium text-gray-500">Poslovnica</p><p class="text-sm text-gray-900">{{ viewingPayment.branch?.name || 'N/A' }}</p></div>
                <div><p class="text-sm font-medium text-gray-500">Broj fakture</p><p class="text-sm text-gray-900 font-mono">{{ viewingPayment.invoice_number || '-' }}</p></div>
                <div><p class="text-sm font-medium text-gray-500">Iznos</p><p :class="['text-sm font-semibold', viewingPayment.currency === 'KM' ? 'text-blue-600' : viewingPayment.currency === 'EUR' ? 'text-green-600' : 'text-purple-600']">{{ formatCurrency(viewingPayment.amount, viewingPayment.currency) }}</p></div>
                <div><p class="text-sm font-medium text-gray-500">Status</p><span :class="['px-2.5 py-0.5 rounded-full text-xs font-medium', viewingPayment.status === 'PAID' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800']">{{ viewingPayment.status === 'PAID' ? 'Plaćeno' : 'Neplaćeno' }}</span></div>
                <div><p class="text-sm font-medium text-gray-500">Planirani datum</p><p class="text-sm text-gray-900">{{ formatDate(viewingPayment.planned_date) }}</p></div>
                <div v-if="viewingPayment.paid_date"><p class="text-sm font-medium text-gray-500">Datum plaćanja</p><p class="text-sm text-gray-900">{{ formatDate(viewingPayment.paid_date) }}</p></div>
            </div>
            <div v-if="viewingPayment.description"><p class="text-sm font-medium text-gray-500">Opis</p><p class="text-sm text-gray-900 mt-1">{{ viewingPayment.description }}</p></div>
            <div class="flex justify-between gap-3 pt-4">
                <div class="flex gap-2">
                    <button v-if="page.props.auth.user?.canModify" @click="handleDeleteClick(viewingPayment)" class="px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-300 rounded-lg hover:bg-red-50">Obriši</button>
                </div>
                <div class="flex gap-2">
                    <button v-if="viewingPayment.status === 'PLANNED' && page.props.auth.user?.canModify" @click="handleMarkAsPaid(viewingPayment.id); showViewModal = false" class="px-4 py-2 text-sm font-medium text-white bg-green-500 rounded-lg hover:bg-green-600">Označi kao plaćeno</button>
                    <button v-if="viewingPayment.status === 'PAID' && page.props.auth.user?.canModify" @click="handleMarkAsUnpaid(viewingPayment.id); showViewModal = false" class="px-4 py-2 text-sm font-medium text-amber-700 bg-amber-100 rounded-lg hover:bg-amber-200">Označi kao neplaćeno</button>
                    <button @click="showViewModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Zatvori</button>
                    <button v-if="page.props.auth.user?.canModify" @click="handleEdit(viewingPayment)" class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600">Uredi</button>
                </div>
            </div>
        </div>
    </Modal>

    <!-- Delete Confirm Modal -->
    <ConfirmModal
        :show="showDeleteConfirm"
        title="Obriši plaćanje"
        :message="`Jeste li sigurni da želite obrisati plaćanje za '${paymentToDelete?.supplier?.name || 'N/A'}'? Ova akcija se ne može poništiti.`"
        confirmText="Obriši"
        variant="danger"
        @confirm="confirmDelete"
        @cancel="showDeleteConfirm = false; paymentToDelete = null"
    />
</template>
