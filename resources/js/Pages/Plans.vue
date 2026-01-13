<script setup>
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { format, parseISO } from 'date-fns';
import {
    CalendarIcon, EllipsisHorizontalIcon, EyeIcon, TrashIcon, ArrowDownTrayIcon,
    DocumentArrowDownIcon, ClipboardDocumentListIcon, CheckCircleIcon
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon as CheckCircleSolidIcon } from '@heroicons/vue/24/solid';

const props = defineProps({ plans: Array });
const page = usePage();
const openMenuId = ref(null);
const processing = ref(false);
const menuButtonRefs = ref({});

// Confirm modal state
const showConfirmModal = ref(false);
const confirmAction = ref(null);
const confirmPlan = ref(null);
const confirmConfig = ref({ title: '', message: '', variant: 'warning', confirmText: 'Potvrdi' });

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    try { return format(typeof dateStr === 'string' ? parseISO(dateStr) : dateStr, 'dd.MM.yyyy HH:mm'); }
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

const openDeleteConfirm = (plan) => {
    confirmPlan.value = plan;
    confirmAction.value = 'delete';
    confirmConfig.value = {
        title: 'Obriši plan',
        message: `Jeste li sigurni da želite obrisati plan "${plan.name}"? Ova akcija se ne može poništiti.`,
        variant: 'danger',
        confirmText: 'Obriši',
    };
    showConfirmModal.value = true;
    openMenuId.value = null;
};

const openMarkAsPaidConfirm = (plan) => {
    confirmPlan.value = plan;
    confirmAction.value = 'markAsPaid';
    confirmConfig.value = {
        title: 'Označi kao plaćeno',
        message: `Označiti plan "${plan.name}" kao plaćen? Sva planirana plaćanja (${plan.payment_count}) u ovom planu će biti označena kao plaćena.`,
        variant: 'success',
        confirmText: 'Označi kao plaćeno',
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
    const labels = { today: 'Danas', tomorrow: 'Sutra', '3days': '3 dana', '7days': '7 dana', period: 'Period', all: 'Svi', custom: 'Prilagođeno' };
    return labels[filter] || filter;
};
</script>

<template>
    <MainLayout>
        <Header title="Spremljeni planovi" />
        <div class="p-6 space-y-6">
            <!-- Empty State -->
            <div v-if="plans.length === 0" class="flex flex-col items-center justify-center py-16 text-gray-500">
                <ClipboardDocumentListIcon class="h-16 w-16 mb-4 opacity-40" />
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nema spremljenih planova</h3>
                <p class="text-sm text-center max-w-md">
                    Kreirajte plan na stranici Pregled odabirom filtera i klikom na "Spremi plan".
                    Planovi vam omogućavaju brz pristup često korištenim pregledima plaćanja.
                </p>
            </div>

            <!-- Plans Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="plan in plans" :key="plan.id" 
                    :class="['group relative rounded-xl border overflow-hidden hover:shadow-lg transition-all cursor-pointer',
                        plan.is_paid ? 'border-green-200 bg-green-50/30' : 'border-gray-200 bg-white hover:border-blue-200']"
                    @click="viewPlan(plan)">
                    
                    <!-- Paid Badge -->
                    <div v-if="plan.is_paid" class="absolute top-3 right-12 z-10">
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            <CheckCircleSolidIcon class="h-3.5 w-3.5" /> Plaćeno
                        </span>
                    </div>

                    <!-- Card Header -->
                    <div class="p-5 pb-3">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0 pr-16">
                                <h3 :class="['font-semibold truncate transition-colors', plan.is_paid ? 'text-green-800' : 'text-gray-900 group-hover:text-blue-600']">
                                    {{ plan.name }}
                                </h3>
                                <p v-if="plan.description" class="text-sm text-gray-500 mt-1 line-clamp-2">
                                    {{ plan.description }}
                                </p>
                            </div>
                            <!-- Menu -->
                            <div class="relative ml-2" @click.stop>
                                <button 
                                    :data-menu-button="plan.id"
                                    @click="openMenuId = openMenuId === plan.id ? null : plan.id" 
                                    class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                                    <EllipsisHorizontalIcon class="h-5 w-5 text-gray-400" />
                                </button>
                            </div>
                        </div>
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
                                <p class="text-xs text-gray-500">plaćanja</p>
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
                                Plaćeno: {{ formatDateTime(plan.paid_at) }}
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
                <div v-for="plan in plans" :key="'menu-' + plan.id">
                    <div v-if="openMenuId === plan.id" 
                        class="fixed w-52 bg-white rounded-lg shadow-xl border border-gray-200 py-1 z-40"
                        :style="getMenuPosition(plan.id)">
                        <button @click="viewPlan(plan)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <EyeIcon class="h-4 w-4" /> Pregledaj
                        </button>
                        <button v-if="!plan.is_paid && page.props.auth.user?.canModify" @click="openMarkAsPaidConfirm(plan)" 
                            class="w-full flex items-center gap-2 px-4 py-2 text-sm text-green-600 hover:bg-green-50">
                            <CheckCircleIcon class="h-4 w-4" /> Označi kao plaćeno
                        </button>
                        <hr class="my-1" />
                        <button @click="exportCsv(plan)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <ArrowDownTrayIcon class="h-4 w-4" /> Export CSV
                        </button>
                        <button @click="exportPdf(plan)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <DocumentArrowDownIcon class="h-4 w-4" /> Export PDF
                        </button>
                        <button @click="exportExcel(plan)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <DocumentArrowDownIcon class="h-4 w-4" /> Export Excel
                        </button>
                        <hr class="my-1" />
                        <button v-if="page.props.auth.user?.canModify" @click="openDeleteConfirm(plan)" 
                            class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                            <TrashIcon class="h-4 w-4" /> Obriši
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
