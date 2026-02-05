<script setup>
import { ref, watch, onMounted } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { format, parseISO } from 'date-fns';
import {
    MagnifyingGlassIcon, PlusIcon, BuildingOffice2Icon, EnvelopeIcon, PhoneIcon, MapPinIcon,
    EllipsisHorizontalIcon, EyeIcon, PencilIcon, TrashIcon, ChevronRightIcon, ArrowDownTrayIcon,
    CheckIcon, XMarkIcon, ArrowUpTrayIcon, DocumentArrowDownIcon, Squares2X2Icon, ListBulletIcon
} from '@heroicons/vue/24/outline';
import { useTranslations } from '@/composables/useTranslations';

const { __ } = useTranslations();

const props = defineProps({ suppliers: Array, search: String });
const page = usePage();

const searchQuery = ref(props.search || '');
const expandedSupplier = ref(null);
const openMenuId = ref(null);
const openBranchMenuId = ref(null);
const viewMode = ref('grid'); // 'grid' or 'list'

// Load view mode from localStorage on mount
onMounted(() => {
    const savedViewMode = localStorage.getItem('suppliers_view_mode');
    if (savedViewMode) {
        viewMode.value = savedViewMode;
    }
});

// Watch for search query changes and trigger search automatically
let searchTimeout = null;
watch(searchQuery, (newValue) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('suppliers.index'), { search: newValue }, { preserveState: true, preserveScroll: true });
    }, 300); // 300ms debounce
});

// Toggle view mode and save to localStorage
const toggleViewMode = (mode) => {
    viewMode.value = mode;
    localStorage.setItem('suppliers_view_mode', mode);
};

// Modals
const showSupplierModal = ref(false);
const showBranchModal = ref(false);
const showImportModal = ref(false);
const showBranchDeleteConfirm = ref(false);
const editingSupplier = ref(null);
const editingBranch = ref(null);
const selectedSupplierId = ref(null);
const branchToDelete = ref(null);
const branchDeleteIndex = ref(null);

// Import
const importFile = ref(null);
const importProcessing = ref(false);

// Forms
const supplierForm = useForm({ name: '', email: '', phone: '', address: '', is_active: true, branches: [] });
const branchForm = useForm({ supplier_id: '', name: '', address: '', is_active: true });

// Branch management for supplier form
const supplierBranches = ref([]);
const branchesToDelete = ref([]);
const nextBranchId = ref(1);

// Modal state management
const hasUnsavedChanges = ref(false);
const branchOperationLoading = ref(false);
const originalSupplierFormData = ref(null);
const originalBranchesData = ref(null);

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
    supplierBranches.value = [];
    branchesToDelete.value = [];
    nextBranchId.value = 1;
    
    // Initialize modal state management
    hasUnsavedChanges.value = false;
    originalSupplierFormData.value = null;
    originalBranchesData.value = null;
    
    showSupplierModal.value = true;
    
    // Set up change detection after modal opens
    setTimeout(() => {
        setupChangeDetection();
    }, 100);
};

const openEditSupplierModal = (supplier) => {
    editingSupplier.value = supplier;
    supplierForm.name = supplier.name;
    supplierForm.email = supplier.email || '';
    supplierForm.phone = supplier.phone || '';
    supplierForm.address = supplier.address || '';
    supplierForm.is_active = supplier.is_active;
    
    // Load existing branches
    supplierBranches.value = supplier.branches ? supplier.branches.map(branch => ({
        id: branch.id,
        name: branch.name,
        address: branch.address || '',
        is_active: branch.is_active,
        _delete: false,
        _isNew: false
    })) : [];
    
    branchesToDelete.value = [];
    nextBranchId.value = Math.max(...supplierBranches.value.map(b => b.id || 0), 0) + 1;
    
    // Initialize modal state management
    hasUnsavedChanges.value = false;
    
    showSupplierModal.value = true;
    openMenuId.value = null;
    
    // Set up change detection after modal opens
    setTimeout(() => {
        setupChangeDetection();
    }, 100);
};

// Setup change detection for unsaved changes warning
const setupChangeDetection = () => {
    // Store original form data for comparison
    originalSupplierFormData.value = {
        name: supplierForm.name,
        email: supplierForm.email,
        phone: supplierForm.phone,
        address: supplierForm.address,
        is_active: supplierForm.is_active
    };
    
    // Store original branches data for comparison
    originalBranchesData.value = JSON.parse(JSON.stringify(supplierBranches.value));
    
    // Watch for changes in supplier form
    watch([
        () => supplierForm.name,
        () => supplierForm.email,
        () => supplierForm.phone,
        () => supplierForm.address,
        () => supplierForm.is_active
    ], () => {
        checkForUnsavedChanges();
    }, { deep: true });
    
    // Watch for changes in branches
    watch(supplierBranches, () => {
        checkForUnsavedChanges();
    }, { deep: true });
    
    // Watch for changes in branches to delete
    watch(branchesToDelete, () => {
        checkForUnsavedChanges();
    }, { deep: true });
};

// Check if there are unsaved changes
const checkForUnsavedChanges = () => {
    if (!originalSupplierFormData.value || !originalBranchesData.value) {
        hasUnsavedChanges.value = false;
        return;
    }
    
    // Check supplier form changes
    const currentSupplierData = {
        name: supplierForm.name,
        email: supplierForm.email,
        phone: supplierForm.phone,
        address: supplierForm.address,
        is_active: supplierForm.is_active
    };
    
    const supplierChanged = JSON.stringify(currentSupplierData) !== JSON.stringify(originalSupplierFormData.value);
    
    // Check branches changes
    const branchesChanged = JSON.stringify(supplierBranches.value) !== JSON.stringify(originalBranchesData.value);
    
    // Check if there are branches marked for deletion
    const hasDeletions = branchesToDelete.value.length > 0;
    
    hasUnsavedChanges.value = supplierChanged || branchesChanged || hasDeletions;
};

// Handle modal close with unsaved changes warning
const handleSupplierModalClose = () => {
    if (hasUnsavedChanges.value) {
        if (confirm('Imate nespremljene promjene. Jeste li sigurni da se želite zatvoriti bez spremanja?')) {
            showSupplierModal.value = false;
            hasUnsavedChanges.value = false;
        }
    } else {
        showSupplierModal.value = false;
    }
};

// Branch management functions for supplier form
const addBranchRow = () => {
    supplierBranches.value.push({
        id: null,
        name: '',
        address: '',
        is_active: true,
        _delete: false,
        _isNew: true,
        _tempId: nextBranchId.value++
    });
};

const removeBranchRow = (index) => {
    const branch = supplierBranches.value[index];
    
    if (branch._isNew) {
        // For new branches, just remove from array immediately
        supplierBranches.value.splice(index, 1);
    } else {
        // For existing branches, show confirmation dialog before marking for deletion
        branchToDelete.value = branch;
        branchDeleteIndex.value = index;
        showBranchDeleteConfirm.value = true;
    }
};

const confirmBranchDeletion = () => {
    if (branchToDelete.value && branchDeleteIndex.value !== null) {
        const branch = branchToDelete.value;
        
        // Mark existing branch for deletion
        branch._delete = true;
        branchesToDelete.value.push(branch.id);
        
        // Close confirmation dialog and reset state
        showBranchDeleteConfirm.value = false;
        branchToDelete.value = null;
        branchDeleteIndex.value = null;
    }
};

const cancelBranchDeletion = () => {
    // Close confirmation dialog and reset state
    showBranchDeleteConfirm.value = false;
    branchToDelete.value = null;
    branchDeleteIndex.value = null;
};

const undoBranchDeletion = (index) => {
    const branch = supplierBranches.value[index];
    if (branch._delete) {
        // Remove from deletion list and restore branch
        branch._delete = false;
        const deleteIndex = branchesToDelete.value.indexOf(branch.id);
        if (deleteIndex > -1) {
            branchesToDelete.value.splice(deleteIndex, 1);
        }
    }
};

const validateBranches = () => {
    const errors = {};
    supplierBranches.value.forEach((branch, index) => {
        if (!branch._delete) {
            // Validate branch name is required and not just whitespace
            if (!branch.name || branch.name.trim() === '') {
                errors[`branches.${index}.name`] = 'Naziv poslovnice je obavezan.';
            }
            // Additional validation: check for minimum length
            else if (branch.name.trim().length < 2) {
                errors[`branches.${index}.name`] = 'Naziv poslovnice mora imati najmanje 2 karaktera.';
            }
            // Additional validation: check for duplicate branch names within the same supplier
            const duplicateIndex = supplierBranches.value.findIndex((otherBranch, otherIndex) => 
                otherIndex !== index && 
                !otherBranch._delete && 
                otherBranch.name.trim().toLowerCase() === branch.name.trim().toLowerCase()
            );
            if (duplicateIndex !== -1) {
                errors[`branches.${index}.name`] = 'Naziv poslovnice već postoji.';
            }
        }
    });
    return errors;
};

const prepareBranchData = () => {
    return supplierBranches.value
        .filter(branch => !branch._delete)
        .map(branch => ({
            id: branch.id,
            name: branch.name.trim(),
            address: branch.address?.trim() || '',
            is_active: branch.is_active,
            _isNew: branch._isNew
        }));
};

// Function to preserve form state during validation errors
const preserveFormState = () => {
    // This function ensures that branch data is maintained in the form
    // even when validation errors occur, so users don't lose their input
    return {
        supplierData: {
            name: supplierForm.name,
            email: supplierForm.email,
            phone: supplierForm.phone,
            address: supplierForm.address,
            is_active: supplierForm.is_active
        },
        branchData: supplierBranches.value.map(branch => ({ ...branch })),
        branchesToDeleteData: [...branchesToDelete.value]
    };
};

// Function to restore form state after validation errors
const restoreFormState = (savedState) => {
    if (savedState) {
        supplierForm.name = savedState.supplierData.name;
        supplierForm.email = savedState.supplierData.email;
        supplierForm.phone = savedState.supplierData.phone;
        supplierForm.address = savedState.supplierData.address;
        supplierForm.is_active = savedState.supplierData.is_active;
        supplierBranches.value = savedState.branchData;
        branchesToDelete.value = savedState.branchesToDeleteData;
    }
};

const submitSupplier = () => {
    // Preserve current form state before validation
    const currentFormState = preserveFormState();
    
    // Clear any previous branch validation errors
    Object.keys(supplierForm.errors).forEach(key => {
        if (key.startsWith('branches.')) {
            supplierForm.clearErrors(key);
        }
    });
    
    // Validate branches
    const branchErrors = validateBranches();
    if (Object.keys(branchErrors).length > 0) {
        // Set branch validation errors
        Object.keys(branchErrors).forEach(key => {
            supplierForm.setError(key, branchErrors[key]);
        });
        // Restore form state to preserve user input
        restoreFormState(currentFormState);
        return;
    }
    
    // Prepare form data with branches
    const formData = {
        name: supplierForm.name,
        email: supplierForm.email,
        phone: supplierForm.phone,
        address: supplierForm.address,
        is_active: supplierForm.is_active,
        branches: prepareBranchData(),
        branchesToDelete: branchesToDelete.value
    };
    
    if (editingSupplier.value) {
        supplierForm.put(route('suppliers.update', editingSupplier.value.id), {
            data: formData,
            preserveScroll: true,
            onSuccess: () => { 
                showSupplierModal.value = false; 
                supplierForm.reset(); 
                editingSupplier.value = null;
                supplierBranches.value = [];
                branchesToDelete.value = [];
                hasUnsavedChanges.value = false;
            },
            onError: (errors) => {
                // Handle server-side validation errors
                // Preserve branch data in form state during validation errors
                restoreFormState(currentFormState);
                console.log('Server validation errors:', errors);
            }
        });
    } else {
        supplierForm.post(route('suppliers.store'), {
            data: formData,
            preserveScroll: true,
            onSuccess: () => { 
                showSupplierModal.value = false; 
                supplierForm.reset();
                supplierBranches.value = [];
                branchesToDelete.value = [];
                hasUnsavedChanges.value = false;
            },
            onError: (errors) => {
                // Handle server-side validation errors
                // Preserve branch data in form state during validation errors
                restoreFormState(currentFormState);
                console.log('Server validation errors:', errors);
            }
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
    branchOperationLoading.value = false;
    showBranchModal.value = true;
};

const openEditBranchModal = (branch) => {
    editingBranch.value = branch;
    branchForm.supplier_id = branch.supplier_id;
    branchForm.name = branch.name;
    branchForm.address = branch.address || '';
    branchForm.is_active = branch.is_active;
    branchOperationLoading.value = false;
    showBranchModal.value = true;
    openBranchMenuId.value = null;
};

const submitBranch = () => {
    branchOperationLoading.value = true;
    
    if (editingBranch.value) {
        branchForm.put(route('branches.update', editingBranch.value.id), {
            preserveScroll: true,
            onSuccess: () => { 
                showBranchModal.value = false; 
                branchForm.reset(); 
                editingBranch.value = null;
                branchOperationLoading.value = false;
            },
            onError: () => {
                branchOperationLoading.value = false;
            },
            onFinish: () => {
                branchOperationLoading.value = false;
            }
        });
    } else {
        branchForm.post(route('branches.store'), {
            preserveScroll: true,
            onSuccess: () => { 
                showBranchModal.value = false; 
                branchForm.reset();
                branchOperationLoading.value = false;
            },
            onError: () => {
                branchOperationLoading.value = false;
            },
            onFinish: () => {
                branchOperationLoading.value = false;
            }
        });
    }
};

const deleteBranch = (branch) => {
    if (confirm(`Jeste li sigurni da želite obrisati poslovnicu "${branch.name}"?`)) {
        branchOperationLoading.value = true;
        router.delete(route('branches.destroy', branch.id), {
            onFinish: () => {
                branchOperationLoading.value = false;
            }
        });
    }
    openBranchMenuId.value = null;
};

const toggleBranchStatus = (branch) => {
    branchOperationLoading.value = true;
    
    if (branch.is_active) {
        router.post(route('branches.deactivate', branch.id), {}, {
            onFinish: () => {
                branchOperationLoading.value = false;
            }
        });
    } else {
        router.post(route('branches.activate', branch.id), {}, {
            onFinish: () => {
                branchOperationLoading.value = false;
            }
        });
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
        <Header :title="__('suppliers')" />
        <div class="p-6 space-y-6">
            <!-- Search & Actions -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <div class="relative flex-1 sm:w-96">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input v-model="searchQuery" type="search" :placeholder="__('search')" class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg" />
                    </div>
                    <!-- View Mode Toggle -->
                    <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                        <button 
                            @click="toggleViewMode('grid')" 
                            :class="['p-2 rounded transition-colors', viewMode === 'grid' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500 hover:text-gray-700']"
                            title="Grid prikaz"
                        >
                            <Squares2X2Icon class="h-4 w-4" />
                        </button>
                        <button 
                            @click="toggleViewMode('list')" 
                            :class="['p-2 rounded transition-colors', viewMode === 'list' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500 hover:text-gray-700']"
                            title="Lista prikaz"
                        >
                            <ListBulletIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button v-if="page.props.auth.user?.canModify" @click="openImportModal" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-purple-700 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100">
                        <ArrowUpTrayIcon class="h-4 w-4" /> {{ __('import') }}
                    </button>
                    <button @click="exportExcel" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                        <ArrowDownTrayIcon class="h-4 w-4" /> {{ __('excel') }}
                    </button>
                    <button @click="exportSuppliers" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        <ArrowDownTrayIcon class="h-4 w-4" /> {{ __('csv') }}
                    </button>
                    <button v-if="page.props.auth.user?.canModify" @click="openNewSupplierModal" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                        <PlusIcon class="h-4 w-4" /> {{ __('new_supplier') }}
                    </button>
                </div>
            </div>

            <!-- Suppliers Grid View -->
            <div v-if="viewMode === 'grid'" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
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
                                        {{ supplier.is_active ? __('active') : __('inactive') }}
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
                                        <PencilIcon class="h-4 w-4" /> {{ __('edit') }}
                                    </button>
                                    <button v-if="page.props.auth.user?.canModify" @click="toggleSupplierStatus(supplier)" :class="['w-full flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-50', supplier.is_active ? 'text-orange-600' : 'text-green-600']">
                                        <template v-if="supplier.is_active"><XMarkIcon class="h-4 w-4" /> {{ __('deactivate') }}</template>
                                        <template v-else><CheckIcon class="h-4 w-4" /> {{ __('activate') }}</template>
                                    </button>
                                    <hr v-if="page.props.auth.user?.canModify" class="my-1" />
                                    <button v-if="page.props.auth.user?.canModify" @click="deleteSupplier(supplier)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                        <TrashIcon class="h-4 w-4" /> {{ __('delete') }}
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
                                {{ supplier.branches?.length || 0 }} {{ __('branches').toLowerCase() }}
                            </button>
                            <button 
                                v-if="page.props.auth.user?.canModify" 
                                @click="openNewBranchModal(supplier.id)" 
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shadow-sm transition-all duration-200 transform hover:scale-105"
                            >
                                <PlusIcon class="h-4 w-4" /> {{ __('add_branch') }}
                            </button>
                        </div>
                    </div>

                    <!-- Branches List -->
                    <div v-if="expandedSupplier === supplier.id" class="border-t border-gray-200 bg-gray-50 px-5 py-4">
                        <!-- Empty State with Add First Branch Button -->
                        <div v-if="!supplier.branches || supplier.branches.length === 0" class="text-center py-8">
                            <BuildingOffice2Icon class="h-12 w-12 mx-auto mb-4 text-gray-300" />
                            <p class="text-sm text-gray-500 mb-4">{{ __('no_branches_for_supplier') }}</p>
                            <button 
                                v-if="page.props.auth.user?.canModify" 
                                @click="openNewBranchModal(supplier.id)" 
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 shadow-sm transition-all duration-200 transform hover:scale-105"
                            >
                                <PlusIcon class="h-4 w-4" /> 
                                {{ __('add_first_branch') }}
                            </button>
                        </div>
                        
                        <!-- Branches with Prominent Add Button -->
                        <div v-else class="space-y-4">
                            <!-- Header with Add Branch Button -->
                            <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                                <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                                    {{ __('branches') }} ({{ supplier.branches?.length || 0 }})
                                </h4>
                                <button 
                                    v-if="page.props.auth.user?.canModify"
                                    @click="openNewBranchModal(supplier.id)" 
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 shadow-md transition-all duration-200 transform hover:scale-105 hover:shadow-lg"
                                >
                                    <PlusIcon class="h-4 w-4" /> 
                                    {{ __('add_branch') }}
                                </button>
                            </div>
                            
                            <!-- Branch Items with Enhanced Visual Hierarchy -->
                            <div class="grid gap-3">
                                <div v-for="branch in supplier.branches" :key="branch.id" class="flex items-center justify-between rounded-lg bg-white p-4 border border-gray-200 shadow-sm hover:shadow-md transition-all duration-200 hover:border-gray-300">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 flex-shrink-0">
                                                <BuildingOffice2Icon class="h-4 w-4 text-blue-600" />
                                            </div>
                                            <div>
                                                <p class="font-semibold text-sm text-gray-900">{{ branch.name }}</p>
                                                <p v-if="branch.address" class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                                                    <MapPinIcon class="h-3 w-3" />
                                                    {{ branch.address }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', branch.is_active ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200']">
                                            {{ branch.is_active ? __('active') : __('inactive') }}
                                        </span>
                                        <div v-if="page.props.auth.user?.canModify" class="relative">
                                            <button @click.stop="openBranchMenuId = openBranchMenuId === branch.id ? null : branch.id" class="p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                                                <EllipsisHorizontalIcon class="h-4 w-4 text-gray-400" />
                                            </button>
                                            <div v-if="openBranchMenuId === branch.id" @click="openBranchMenuId = null" class="fixed inset-0 z-30"></div>
                                            <div v-if="openBranchMenuId === branch.id" class="absolute right-0 mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-40">
                                            <button @click="openEditBranchModal(branch)" :disabled="branchOperationLoading" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                                                <svg v-if="branchOperationLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <PencilIcon v-else class="h-4 w-4" /> {{ __('edit') }}
                                            </button>
                                            <button @click="toggleBranchStatus(branch)" :disabled="branchOperationLoading" :class="['w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200', branch.is_active ? 'text-orange-600' : 'text-green-600']">
                                                <svg v-if="branchOperationLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <template v-else-if="branch.is_active"><XMarkIcon class="h-4 w-4" /> {{ __('deactivate') }}</template>
                                                <template v-else><CheckIcon class="h-4 w-4" /> {{ __('activate') }}</template>
                                            </button>
                                            <hr class="my-1" />
                                            <button @click="deleteBranch(branch)" :disabled="branchOperationLoading" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                                                <svg v-if="branchOperationLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <TrashIcon v-else class="h-4 w-4" /> {{ __('delete') }}
                                            </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="border-t border-gray-200 bg-gray-50 px-5 py-3">
                        <p class="text-xs text-gray-500">{{ __('added') }}: {{ format(parseISO(supplier.created_at), 'dd.MM.yyyy') }}</p>
                    </div>
                </div>
            </div>

            <!-- Suppliers List View -->
            <div v-if="viewMode === 'list'" class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('supplier') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('contact') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('branches') }}</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold uppercase text-gray-500">{{ __('status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">{{ __('added') }}</th>
                            <th class="w-12 px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template v-for="supplier in suppliers" :key="supplier.id">
                            <!-- Supplier Row -->
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 flex-shrink-0">
                                            <BuildingOffice2Icon class="h-5 w-5 text-blue-600" />
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ supplier.name }}</p>
                                            <p v-if="supplier.address" class="text-xs text-gray-500 mt-0.5">{{ supplier.address }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <p v-if="supplier.email" class="text-sm text-gray-600 flex items-center gap-1.5">
                                            <EnvelopeIcon class="h-3.5 w-3.5 text-gray-400" />
                                            {{ supplier.email }}
                                        </p>
                                        <p v-if="supplier.phone" class="text-sm text-gray-600 flex items-center gap-1.5">
                                            <PhoneIcon class="h-3.5 w-3.5 text-gray-400" />
                                            {{ supplier.phone }}
                                        </p>
                                        <p v-if="!supplier.email && !supplier.phone" class="text-sm text-gray-400">-</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <button @click="toggleExpand(supplier.id)" class="flex items-center gap-1.5 text-sm text-blue-600 hover:underline">
                                        <ChevronRightIcon :class="['h-4 w-4 transition-transform', expandedSupplier === supplier.id && 'rotate-90']" />
                                        {{ supplier.branches?.length || 0 }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', supplier.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">
                                        {{ supplier.is_active ? __('active') : __('inactive') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-500">{{ format(parseISO(supplier.created_at), 'dd.MM.yyyy') }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="relative">
                                        <button @click="openMenuId = openMenuId === supplier.id ? null : supplier.id" class="p-1 rounded hover:bg-gray-100">
                                            <EllipsisHorizontalIcon class="h-5 w-5 text-gray-400" />
                                        </button>
                                        <div v-if="openMenuId === supplier.id" @click="openMenuId = null" class="fixed inset-0 z-30"></div>
                                        <div v-if="openMenuId === supplier.id" class="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-40">
                                            <button v-if="page.props.auth.user?.canModify" @click="openEditSupplierModal(supplier)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                <PencilIcon class="h-4 w-4" /> {{ __('edit') }}
                                            </button>
                                            <button v-if="page.props.auth.user?.canModify" @click="openNewBranchModal(supplier.id)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-blue-600 hover:bg-gray-50">
                                                <PlusIcon class="h-4 w-4" /> {{ __('add_branch') }}
                                            </button>
                                            <button v-if="page.props.auth.user?.canModify" @click="toggleSupplierStatus(supplier)" :class="['w-full flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-50', supplier.is_active ? 'text-orange-600' : 'text-green-600']">
                                                <template v-if="supplier.is_active"><XMarkIcon class="h-4 w-4" /> {{ __('deactivate') }}</template>
                                                <template v-else><CheckIcon class="h-4 w-4" /> {{ __('activate') }}</template>
                                            </button>
                                            <hr v-if="page.props.auth.user?.canModify" class="my-1" />
                                            <button v-if="page.props.auth.user?.canModify" @click="deleteSupplier(supplier)" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                <TrashIcon class="h-4 w-4" /> {{ __('delete') }}
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <!-- Branches Expanded Row -->
                            <tr v-if="expandedSupplier === supplier.id" class="bg-gray-50">
                                <td colspan="6" class="px-6 py-4">
                                    <div class="ml-14">
                                        <!-- Empty State with Add First Branch Button -->
                                        <div v-if="!supplier.branches || supplier.branches.length === 0" class="text-center py-8">
                                            <BuildingOffice2Icon class="h-10 w-10 mx-auto mb-3 text-gray-300" />
                                            <p class="text-sm text-gray-500 mb-4">{{ __('no_branches_for_supplier') }}</p>
                                            <button 
                                                v-if="page.props.auth.user?.canModify" 
                                                @click="openNewBranchModal(supplier.id)" 
                                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 shadow-sm transition-all duration-200 transform hover:scale-105"
                                            >
                                                <PlusIcon class="h-4 w-4" /> 
                                                {{ __('add_first_branch') }}
                                            </button>
                                        </div>
                                        
                                        <!-- Branches with Enhanced Visual Hierarchy -->
                                        <div v-else class="space-y-4">
                                            <!-- Header with Add Branch Button -->
                                            <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                                                <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                                                    {{ __('branches') }} ({{ supplier.branches?.length || 0 }})
                                                </h4>
                                                <button 
                                                    v-if="page.props.auth.user?.canModify" 
                                                    @click="openNewBranchModal(supplier.id)" 
                                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 shadow-md transition-all duration-200 transform hover:scale-105 hover:shadow-lg"
                                                >
                                                    <PlusIcon class="h-4 w-4" /> 
                                                    {{ __('add_branch') }}
                                                </button>
                                            </div>
                                            
                                            <!-- Branch Items with Enhanced Visual Hierarchy -->
                                            <div class="grid gap-3">
                                                <div v-for="branch in supplier.branches" :key="branch.id" class="flex items-center justify-between bg-white rounded-lg p-4 border border-gray-200 shadow-sm hover:shadow-md transition-all duration-200 hover:border-gray-300">
                                                    <div class="flex-1">
                                                        <div class="flex items-center gap-3">
                                                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 flex-shrink-0">
                                                                <BuildingOffice2Icon class="h-4 w-4 text-blue-600" />
                                                            </div>
                                                            <div>
                                                                <p class="font-semibold text-sm text-gray-900">{{ branch.name }}</p>
                                                                <p v-if="branch.address" class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                                                                    <MapPinIcon class="h-3 w-3" />
                                                                    {{ branch.address }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', branch.is_active ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200']">
                                                            {{ branch.is_active ? __('active') : __('inactive') }}
                                                        </span>
                                                        <div v-if="page.props.auth.user?.canModify" class="relative">
                                                            <button @click.stop="openBranchMenuId = openBranchMenuId === branch.id ? null : branch.id" class="p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                                                                <EllipsisHorizontalIcon class="h-4 w-4 text-gray-400" />
                                                            </button>
                                                            <div v-if="openBranchMenuId === branch.id" @click="openBranchMenuId = null" class="fixed inset-0 z-30"></div>
                                                            <div v-if="openBranchMenuId === branch.id" class="absolute right-0 mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-40">
                                                            <button @click="openEditBranchModal(branch)" :disabled="branchOperationLoading" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                                                                <svg v-if="branchOperationLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                                </svg>
                                                                <PencilIcon v-else class="h-4 w-4" /> {{ __('edit') }}
                                                            </button>
                                                            <button @click="toggleBranchStatus(branch)" :disabled="branchOperationLoading" :class="['w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200', branch.is_active ? 'text-orange-600' : 'text-green-600']">
                                                                <svg v-if="branchOperationLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                                </svg>
                                                                <template v-else-if="branch.is_active"><XMarkIcon class="h-4 w-4" /> {{ __('deactivate') }}</template>
                                                                <template v-else><CheckIcon class="h-4 w-4" /> {{ __('activate') }}</template>
                                                            </button>
                                                            <hr class="my-1" />
                                                            <button @click="deleteBranch(branch)" :disabled="branchOperationLoading" class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                                                                <svg v-if="branchOperationLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                                </svg>
                                                                <TrashIcon v-else class="h-4 w-4" /> {{ __('delete') }}
                                                            </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div v-if="suppliers.length === 0" class="flex flex-col items-center justify-center py-12 text-gray-500">
                <BuildingOffice2Icon class="h-12 w-12 mb-4 opacity-50" />
                <p class="text-sm">{{ __('no_suppliers') }}</p>
            </div>
        </div>

        <!-- Supplier Modal -->
        <Modal :show="showSupplierModal" :title="editingSupplier ? __('edit_supplier') : __('new_supplier')" @close="handleSupplierModalClose">
            <form @submit.prevent="submitSupplier" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supplier_name') }} {{ __('required') }}</label>
                    <input v-model="supplierForm.name" type="text" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                    <p v-if="supplierForm.errors.name" class="mt-1 text-sm text-red-600">{{ supplierForm.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('email') }}</label>
                    <input v-model="supplierForm.email" type="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('phone') }}</label>
                    <input v-model="supplierForm.phone" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('address') }}</label>
                    <textarea v-model="supplierForm.address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                
                <!-- Branch Management Section -->
                <div class="border-t border-gray-200 pt-4">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-medium text-gray-700">{{ __('branches') }}</label>
                        <button type="button" @click="addBranchRow" class="inline-flex items-center gap-1 px-3 py-1 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-all duration-200 transform hover:scale-105">
                            <PlusIcon class="h-4 w-4" /> {{ __('add_branch') }}
                        </button>
                    </div>
                    
                    <!-- Branch validation summary -->
                    <div v-if="Object.keys(supplierForm.errors).some(key => key.startsWith('branches.'))" class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center gap-2 text-red-800">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm font-medium">Molimo ispravite greške u poslovnicama prije spremanja.</span>
                        </div>
                    </div>
                    
                    <div v-if="supplierBranches.length === 0" class="text-center py-6 text-gray-500 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                        <BuildingOffice2Icon class="h-8 w-8 mx-auto mb-2 opacity-50" />
                        <p class="text-sm">{{ __('no_branches_yet') }}</p>
                        <button type="button" @click="addBranchRow" class="mt-2 text-sm text-blue-600 hover:underline transition-all duration-200 transform hover:scale-105">
                            {{ __('add_first_branch') }}
                        </button>
                    </div>
                    
                    <div v-else class="space-y-3">
                        <div v-for="(branch, index) in supplierBranches" :key="branch._tempId || branch.id" 
                             :class="[
                                 'p-3 border rounded-lg transition-all duration-200 relative',
                                 branch._delete ? 'bg-red-50 border-red-200 opacity-75 shadow-sm' : 
                                 (branch._isNew ? 'bg-green-50 border-green-200 shadow-sm ring-1 ring-green-200' : 'bg-white border-gray-200 hover:border-gray-300')
                             ]">
                            <!-- Visual state indicator ribbon -->
                            <div v-if="branch._isNew && !branch._delete" class="absolute -top-2 -right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-sm">
                                NOVO
                            </div>
                            <div v-if="branch._delete" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-sm">
                                BRIŠE SE
                            </div>
                            
                            <div v-if="branch._delete" class="mb-2 flex items-center gap-2 text-red-700 text-sm font-medium">
                                <svg class="h-4 w-4 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Označeno za brisanje - promjene će biti spremljene
                            </div>
                            <div v-else-if="branch._isNew" class="mb-2 flex items-center gap-2 text-green-700 text-sm font-medium">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Nova poslovnica - bit će dodana pri spremanju
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="flex-1 space-y-2">
                                    <div>
                                        <input 
                                            v-model="branch.name" 
                                            type="text" 
                                            :placeholder="__('branch_name') + ' *'"
                                            :disabled="branch._delete"
                                            :class="[
                                                'w-full px-3 py-2 text-sm border rounded-lg transition-all duration-200',
                                                branch._delete ? 'bg-gray-100 text-gray-500 line-through cursor-not-allowed' : 
                                                branch._isNew ? 'bg-green-50 border-green-300 focus:border-green-500 focus:ring-green-500 focus:ring-2' :
                                                supplierForm.errors[`branches.${index}.name`] ? 'border-red-300 focus:border-red-500 focus:ring-red-500 focus:ring-2' : 
                                                'border-gray-300 focus:border-blue-500 focus:ring-blue-500 focus:ring-2'
                                            ]"
                                            @input="() => {
                                                // Clear validation error when user starts typing
                                                if (supplierForm.errors[`branches.${index}.name`]) {
                                                    supplierForm.clearErrors(`branches.${index}.name`);
                                                }
                                            }"
                                        />
                                        <p v-if="supplierForm.errors[`branches.${index}.name`]" class="mt-1 text-sm text-red-600 flex items-center gap-1">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            {{ supplierForm.errors[`branches.${index}.name`] }}
                                        </p>
                                    </div>
                                    <div>
                                        <input 
                                            v-model="branch.address" 
                                            type="text" 
                                            :placeholder="__('address')"
                                            :disabled="branch._delete"
                                            :class="[
                                                'w-full px-3 py-2 text-sm border rounded-lg transition-all duration-200', 
                                                branch._delete ? 'bg-gray-100 text-gray-500 line-through cursor-not-allowed' : 
                                                branch._isNew ? 'bg-green-50 border-green-300 focus:border-green-500 focus:ring-green-500 focus:ring-2' :
                                                'border-gray-300 focus:border-blue-500 focus:ring-blue-500 focus:ring-2'
                                            ]"
                                        />
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span v-if="branch._isNew && !branch._delete" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                        <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        {{ __('new') }}
                                    </span>
                                    <span v-else-if="branch._delete" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                                        <svg class="h-3 w-3 mr-1 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        {{ __('to_delete') }}
                                    </span>
                                    <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                        <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-8a1 1 0 012 0v3a1 1 0 11-2 0v-3zM10 4a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
                                        </svg>
                                        {{ __('existing') }}
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <button 
                                            v-if="branch._delete"
                                            type="button" 
                                            @click="undoBranchDeletion(index)"
                                            class="p-1 rounded text-blue-500 hover:text-blue-700 hover:bg-blue-50 transition-all duration-200 transform hover:scale-110"
                                            title="Poništi brisanje"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                            </svg>
                                        </button>
                                        <button 
                                            type="button" 
                                            @click="removeBranchRow(index)"
                                            :class="[
                                                'p-1 rounded transition-all duration-200',
                                                branch._delete ? 'text-gray-400 cursor-not-allowed opacity-50' : 
                                                'text-red-500 hover:text-red-700 hover:bg-red-50 transform hover:scale-110'
                                            ]"
                                            :disabled="branch._delete"
                                            :title="branch._delete ? 'Već označeno za brisanje' : (branch._isNew ? 'Ukloni poslovnicu' : 'Označi za brisanje')"
                                        >
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div v-if="editingSupplier" class="flex items-center gap-3">
                    <input v-model="supplierForm.is_active" type="checkbox" id="supplier_active" class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                    <label for="supplier_active" class="text-sm font-medium text-gray-700">{{ __('active') }}</label>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="handleSupplierModalClose" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all duration-200">{{ __('cancel') }}</button>
                    <button type="submit" :disabled="supplierForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 flex items-center gap-2">
                        <svg v-if="supplierForm.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ supplierForm.processing ? __('saving') : __('save') }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Branch Modal -->
        <Modal :show="showBranchModal" :title="editingBranch ? __('edit_branch') : __('new_branch')" @close="showBranchModal = false">
            <form @submit.prevent="submitBranch" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('branch_name') }} {{ __('required') }}</label>
                    <input v-model="branchForm.name" type="text" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                    <p v-if="branchForm.errors.name" class="mt-1 text-sm text-red-600">{{ branchForm.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('address') }}</label>
                    <textarea v-model="branchForm.address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div v-if="editingBranch" class="flex items-center gap-3">
                    <input v-model="branchForm.is_active" type="checkbox" id="branch_active" class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                    <label for="branch_active" class="text-sm font-medium text-gray-700">{{ __('active') }}</label>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showBranchModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">{{ __('cancel') }}</button>
                    <button type="submit" :disabled="branchForm.processing || branchOperationLoading" class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50 flex items-center gap-2">
                        <svg v-if="branchForm.processing || branchOperationLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ (branchForm.processing || branchOperationLoading) ? __('saving') : __('save') }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Import Modal -->
        <Modal :show="showImportModal" :title="__('import_suppliers')" @close="showImportModal = false">
            <div class="space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-medium text-blue-800 mb-2">{{ __('import_instructions') }}</h4>
                    <ol class="text-sm text-blue-700 space-y-1 list-decimal list-inside">
                        <li>{{ __('download_template') }}</li>
                        <li>Popunite podatke o dobavljačima i poslovnicama</li>
                        <li>Obrišite primjere (žuti redovi)</li>
                        <li>Učitajte popunjeni fajl</li>
                    </ol>
                </div>

                <div class="flex justify-center">
                    <button @click="downloadTemplate" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100">
                        <DocumentArrowDownIcon class="h-5 w-5" /> {{ __('download_template') }}
                    </button>
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('select_excel_file') }}</label>
                    <input 
                        type="file" 
                        @change="handleFileSelect"
                        accept=".xlsx,.xls"
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    />
                    <p class="mt-1 text-xs text-gray-500">{{ __('supported_formats') }}</p>
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
                        {{ __('cancel') }}
                    </button>
                    <button 
                        @click="submitImport" 
                        :disabled="!importFile || importProcessing"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ importProcessing ? __('importing') : __('import') }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Branch Deletion Confirmation Modal -->
        <ConfirmModal 
            :show="showBranchDeleteConfirm"
            :title="__('confirm_delete_branch')"
            :message="`Jeste li sigurni da želite obrisati poslovnicu '${branchToDelete?.name}'? Ova akcija se ne može poništiti.`"
            :confirm-text="__('delete')"
            :cancel-text="__('cancel')"
            variant="danger"
            @confirm="confirmBranchDeletion"
            @cancel="cancelBranchDeletion"
        >
            <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                <div class="flex items-start gap-2">
                    <svg class="h-5 w-5 text-amber-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-amber-800">Upozorenje</p>
                        <p class="text-sm text-amber-700 mt-1">Brisanje poslovnice će ukloniti sve povezane podatke. Ova akcija se ne može poništiti.</p>
                    </div>
                </div>
            </div>
        </ConfirmModal>
    </MainLayout>
</template>
