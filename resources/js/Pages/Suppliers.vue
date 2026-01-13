<script setup>
import { ref } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import Modal from '@/Components/Modal.vue';
import { format, parseISO } from 'date-fns';
import {
    MagnifyingGlassIcon, PlusIcon, BuildingOffice2Icon, EnvelopeIcon, PhoneIcon, MapPinIcon,
    EllipsisHorizontalIcon, EyeIcon, PencilIcon, TrashIcon, ChevronRightIcon, ArrowDownTrayIcon,
    CheckIcon, XMarkIcon, ArrowUpTrayIcon, DocumentArrowDownIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({ suppliers: Array, search: String });
const page = usePage();

const searchQuery = ref(props.search || '');
const expandedSupplier = ref(null);
const openMenuId = ref(null);
const openBranchMenuId = ref(null);

// Modals
const showSupplierModal = ref(false);
const showBranchModal = ref(false);
const showImportModal = ref(false);
const editingSupplier = ref(null);
const editingBranch = ref(null);
const selectedSupplierId = ref(null);

// Import
const importFile = ref(null);
const importProcessing = ref(false);

// Forms
const supplierForm = useForm({ name: '', email: '', phone: '', address: '', is_active: true });
const branchForm = useForm({ supplier_id: '', name: '', address: '', is_active: true });

const handleSearch = () => {
    router.get(route('suppliers.index'), { search: searchQuery.value }, { preserveState: true });
};

const toggleExpand = (id) => {
    expandedSupplier.value = expandedSupplier.value === id ? null : id;
};

// ========== SUPPLIER FUNCTIONS ==========
const openNewSupplierModal = () => {
    editingSupplier.value = null;
    supplierForm.reset();
    supplierForm.is_active = true;
    showSupplierModal.value = true;
};

const openEditSupplierModal = (supplier) => {
    editingSupplier.value = supplier;
    supplierForm.name = supplier.name;
    supplierForm.email = supplier.email || '';
    supplierForm.phone = supplier.phone || '';
    supplierForm.address = supplier.address || '';
    supplierForm.is_active = supplier.is_active;
    showSupplierModal.value = true;
    openMenuId.value = null;
};

const submitSupplier = () => {
    if (editingSupplier.value) {
        supplierForm.put(route('suppliers.update', editingSupplier.value.id), {
            preserveScroll: true,
            onSuccess: () => { showSupplierModal.value = false; supplierForm.reset(); editingSupplier.value = null; },
        });
    } else {
        supplierForm.post(route('suppliers.store'), {
            preserveScroll: true,
            onSuccess: () => { showSupplierModal.value = false; supplierForm.reset(); },
        });
    }
};

const deleteSupplier = (supplier) => {
    if (confirm(`Jeste li sigurni da želite obrisati dobavljača "${supplier.name}"? Ova akcija će obrisati i sve njegove poslovnice.`)) {
        router.delete(route('suppliers.destroy', supplier.id));
    }
    openMenuId.value = null;
};

const toggleSupplierStatus = (supplier) => {
    if (supplier.is_active) {
        router.post(route('suppliers.deactivate', supplier.id));
    } else {
        router.post(route('suppliers.activate', supplier.id));
    }
    openMenuId.value = null;
};

// ========== BRANCH FUNCTIONS ==========
const openNewBranchModal = (supplierId) => {
    editingBranch.value = null;
    selectedSupplierId.value = supplierId;
    branchForm.reset();
    branchForm.supplier_id = supplierId;
    branchForm.is_active = true;
    showBranchModal.value = true;
};

const openEditBranchModal = (branch) => {
    editingBranch.value = branch;
    branchForm.supplier_id = branch.supplier_id;
    branchForm.name = branch.name;
    branchForm.address = branch.address || '';
    branchForm.is_active = branch.is_active;
    showBranchModal.value = true;
    openBranchMenuId.value = null;
};

const submitBranch = () => {
    if (editingBranch.value) {
        branchForm.put(route('branches.update', editingBranch.value.id), {
            preserveScroll: true,
            onSuccess: () => { showBranchModal.value = false; branchForm.reset(); editingBranch.value = null; },
        });
    } else {
        branchForm.post(route('branches.store'), {
            preserveScroll: true,
            onSuccess: () => { showBranchModal.value = false; branchForm.reset(); },
        });
    }
};

const deleteBranch = (branch) => {
    if (confirm(`Jeste li sigurni da želite obrisati poslovnicu "${branch.name}"?`)) {
        router.delete(route('branches.destroy', branch.id));
    }
    openBranchMenuId.value = null;
};

const toggleBranchStatus = (branch) => {
    if (branch.is_active) {
        router.post(route('branches.deactivate', branch.id));
    } else {
        router.post(route('branches.activate', branch.id));
    }
    openBranchMenuId.value = null;
};

const exportSuppliers = () => { window.location.href = route('suppliers.export'); };
const exportExcel = () => { window.location.href = route('suppliers.export-excel'); };
const downloadTemplate = () => { window.location.href = route('suppliers.template'); };

const openImportModal = () => {
    importFile.value = null;
    showImportModal.value = true;
};

const handleFileSelect = (event) => {
    importFile.value = event.target.files[0];
};

const submitImport = () => {
    if (!importFile.value) return;
    
    importProcessing.value = true;
    const formData = new FormData();
    formData.append('file', importFile.value);
    
    router.post(route('suppliers.import'), formData, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showImportModal.value = false;
            importFile.value = null;
        },
        onFinish: () => {
            importProcessing.value = false;
        },
    });
};
</script>

<template>
    <MainLayout>
        <Header title="Dobavljači" />
        <div class="p-6 space-y-6">
            <!-- Search & Actions -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="relative w-full sm:w-96">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input v-model="searchQuery" @keyup.enter="handleSearch" type="search" placeholder="Pretraži dobavljače..." class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg" />
                </div>
                <div class="flex items-center gap-2">
                    <button v-if="page.props.auth.user?.canModify" @click="openImportModal" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-purple-700 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100">
                        <ArrowUpTrayIcon class="h-4 w-4" /> Import
                    </button>
                    <button @click="exportExcel" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                        <ArrowDownTrayIcon class="h-4 w-4" /> Excel
                    </button>
                    <button @click="exportSuppliers" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        <ArrowDownTrayIcon class="h-4 w-4" /> CSV
                    </button>
                    <button v-if="page.props.auth.user?.canModify" @click="openNewSupplierModal" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                        <PlusIcon class="h-4 w-4" /> Novi dobavljač
                    </button>
                </div>
            </div>

            <!-- Suppliers Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div v-for="supplier in suppliers" :key="supplier.id" class="rounded-xl border border-gray-200 bg-white overflow-hidden hover:shadow-md transition-shadow">
                    <div class="p-5">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100">
                                    <BuildingOffice2Icon class="h-6 w-6 text-blue-600" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ supplier.name }}</h3>
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mt-1', supplier.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">
                                        {{ supplier.is_active ? 'Aktivan' : 'Neaktivan' }}
                                    </span>
                                </div>
                            </div>
                            <!-- Supplier Menu -->
                            <div class="relative">
                                <button @click="openMenuId = openMenuId === supplier.id ? null : supplier.id" class="p-1 rounded hover:bg-gray-100">
                                    <EllipsisHorizontalIcon class="h-5 w-5 text-gray-400" />
                                </button>
                                <div v-if="openMenuId === supplier.id" @click="openMenuId = null" class="fixed inset-0 z-30"></div>
                                <div v-if="openMenuId === supplier.id" class="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-40">
                                    <button v-if="page.props.auth.user?.canModify" @click="openEditSupplierModal(supplier)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        <PencilIcon class="h-4 w-4" /> Uredi
                                    </button>
                                    <button v-if="page.props.auth.user?.canModify" @click="toggleSupplierStatus(supplier)" :class="['w-full flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-50', supplier.is_active ? 'text-orange-600' : 'text-green-600']">
                                        <template v-if="supplier.is_active"><XMarkIcon class="h-4 w-4" /> Deaktiviraj</template>
                                        <template v-else><CheckIcon class="h-4 w-4" /> Aktiviraj</template>
                                    </button>
                                    <hr v-if="page.props.auth.user?.canModify" class="my-1" />
                                    <button v-if="page.props.auth.user?.canModify" @click="deleteSupplier(supplier)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                        <TrashIcon class="h-4 w-4" /> Obriši
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Contact Info -->
                        <div class="mt-4 space-y-2">
                            <div v-if="supplier.email" class="flex items-center gap-2 text-sm text-gray-500"><EnvelopeIcon class="h-4 w-4" /><span>{{ supplier.email }}</span></div>
                            <div v-if="supplier.phone" class="flex items-center gap-2 text-sm text-gray-500"><PhoneIcon class="h-4 w-4" /><span>{{ supplier.phone }}</span></div>
                            <div v-if="supplier.address" class="flex items-center gap-2 text-sm text-gray-500"><MapPinIcon class="h-4 w-4" /><span>{{ supplier.address }}</span></div>
                        </div>
                        <!-- Branches Toggle -->
                        <div class="mt-4 flex items-center justify-between">
                            <button @click="toggleExpand(supplier.id)" class="flex items-center gap-2 text-sm font-medium text-blue-600 hover:underline">
                                <ChevronRightIcon :class="['h-4 w-4 transition-transform', expandedSupplier === supplier.id && 'rotate-90']" />
                                {{ supplier.branches?.length || 0 }} poslovnica
                            </button>
                            <button v-if="page.props.auth.user?.canModify" @click="openNewBranchModal(supplier.id)" class="text-sm text-blue-600 hover:underline">+ Dodaj poslovnicu</button>
                        </div>
                    </div>

                    <!-- Branches List -->
                    <div v-if="expandedSupplier === supplier.id && supplier.branches?.length > 0" class="border-t border-gray-200 bg-gray-50 px-5 py-4">
                        <div class="space-y-3">
                            <div v-for="branch in supplier.branches" :key="branch.id" class="flex items-center justify-between rounded-lg bg-white p-3 border border-gray-200">
                                <div>
                                    <p class="font-medium text-sm text-gray-900">{{ branch.name }}</p>
                                    <p v-if="branch.address" class="text-xs text-gray-500 mt-0.5">{{ branch.address }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-medium', branch.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">
                                        {{ branch.is_active ? 'Aktivna' : 'Neaktivna' }}
                                    </span>
                                    <!-- Branch Menu -->
                                    <div v-if="page.props.auth.user?.canModify" class="relative">
                                        <button @click.stop="openBranchMenuId = openBranchMenuId === branch.id ? null : branch.id" class="p-1 rounded hover:bg-gray-100">
                                            <EllipsisHorizontalIcon class="h-4 w-4 text-gray-400" />
                                        </button>
                                        <div v-if="openBranchMenuId === branch.id" @click="openBranchMenuId = null" class="fixed inset-0 z-30"></div>
                                        <div v-if="openBranchMenuId === branch.id" class="absolute right-0 mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-40">
                                            <button @click="openEditBranchModal(branch)" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                <PencilIcon class="h-4 w-4" /> Uredi
                                            </button>
                                            <button @click="toggleBranchStatus(branch)" :class="['w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50', branch.is_active ? 'text-orange-600' : 'text-green-600']">
                                                <template v-if="branch.is_active"><XMarkIcon class="h-4 w-4" /> Deaktiviraj</template>
                                                <template v-else><CheckIcon class="h-4 w-4" /> Aktiviraj</template>
                                            </button>
                                            <hr class="my-1" />
                                            <button @click="deleteBranch(branch)" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                                                <TrashIcon class="h-4 w-4" /> Obriši
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="border-t border-gray-200 bg-gray-50 px-5 py-3">
                        <p class="text-xs text-gray-500">Dodano: {{ format(parseISO(supplier.created_at), 'dd.MM.yyyy') }}</p>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="suppliers.length === 0" class="flex flex-col items-center justify-center py-12 text-gray-500">
                <BuildingOffice2Icon class="h-12 w-12 mb-4 opacity-50" />
                <p class="text-sm">Nema dobavljača za prikaz</p>
            </div>
        </div>

        <!-- Supplier Modal -->
        <Modal :show="showSupplierModal" :title="editingSupplier ? 'Uredi dobavljača' : 'Novi dobavljač'" @close="showSupplierModal = false">
            <form @submit.prevent="submitSupplier" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Naziv *</label>
                    <input v-model="supplierForm.name" type="text" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                    <p v-if="supplierForm.errors.name" class="mt-1 text-sm text-red-600">{{ supplierForm.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input v-model="supplierForm.email" type="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                    <input v-model="supplierForm.phone" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adresa</label>
                    <textarea v-model="supplierForm.address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div v-if="editingSupplier" class="flex items-center gap-3">
                    <input v-model="supplierForm.is_active" type="checkbox" id="supplier_active" class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                    <label for="supplier_active" class="text-sm font-medium text-gray-700">Aktivan</label>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showSupplierModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Odustani</button>
                    <button type="submit" :disabled="supplierForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50">
                        {{ supplierForm.processing ? 'Spremanje...' : 'Spremi' }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Branch Modal -->
        <Modal :show="showBranchModal" :title="editingBranch ? 'Uredi poslovnicu' : 'Nova poslovnica'" @close="showBranchModal = false">
            <form @submit.prevent="submitBranch" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Naziv *</label>
                    <input v-model="branchForm.name" type="text" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                    <p v-if="branchForm.errors.name" class="mt-1 text-sm text-red-600">{{ branchForm.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adresa</label>
                    <textarea v-model="branchForm.address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div v-if="editingBranch" class="flex items-center gap-3">
                    <input v-model="branchForm.is_active" type="checkbox" id="branch_active" class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                    <label for="branch_active" class="text-sm font-medium text-gray-700">Aktivna</label>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showBranchModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Odustani</button>
                    <button type="submit" :disabled="branchForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50">
                        {{ branchForm.processing ? 'Spremanje...' : 'Spremi' }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Import Modal -->
        <Modal :show="showImportModal" title="Import dobavljača iz Excel-a" @close="showImportModal = false">
            <div class="space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-medium text-blue-800 mb-2">Upute za import:</h4>
                    <ol class="text-sm text-blue-700 space-y-1 list-decimal list-inside">
                        <li>Preuzmite šablon klikom na dugme ispod</li>
                        <li>Popunite podatke o dobavljačima i poslovnicama</li>
                        <li>Obrišite primjere (žuti redovi)</li>
                        <li>Učitajte popunjeni fajl</li>
                    </ol>
                </div>

                <div class="flex justify-center">
                    <button @click="downloadTemplate" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100">
                        <DocumentArrowDownIcon class="h-5 w-5" /> Preuzmi šablon (Excel)
                    </button>
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Odaberi Excel fajl:</label>
                    <input 
                        type="file" 
                        @change="handleFileSelect"
                        accept=".xlsx,.xls"
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    />
                    <p class="mt-1 text-xs text-gray-500">Podržani formati: .xlsx, .xls (max 5MB)</p>
                </div>

                <div v-if="importFile" class="bg-gray-50 rounded-lg p-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <DocumentArrowDownIcon class="h-5 w-5 text-gray-400" />
                        <span class="text-sm text-gray-700">{{ importFile.name }}</span>
                    </div>
                    <button @click="importFile = null" class="text-gray-400 hover:text-red-500">
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showImportModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Odustani
                    </button>
                    <button 
                        @click="submitImport" 
                        :disabled="!importFile || importProcessing"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ importProcessing ? 'Importovanje...' : 'Importuj' }}
                    </button>
                </div>
            </div>
        </Modal>
    </MainLayout>
</template>
