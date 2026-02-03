<script setup>
import { useForm, usePage, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import { UserIcon, ShieldCheckIcon, ServerIcon, CurrencyDollarIcon, BuildingOfficeIcon, ArrowDownTrayIcon, ArrowUpTrayIcon, DocumentArrowDownIcon } from '@heroicons/vue/24/outline';
import { useTranslations } from '@/composables/useTranslations';

const { __ } = useTranslations();

const props = defineProps({
    exchangeRates: Object,
    companyName: String,
});

const page = usePage();
const isAdmin = page.props.auth.user?.isAdmin;

const importFile = ref(null);
const importProcessing = ref(false);

const profileForm = useForm({
    name: page.props.auth.user?.name || '',
    email: page.props.auth.user?.email || '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const exchangeForm = useForm({
    exchange_rate_eur: props.exchangeRates?.EUR || 1.95583,
    exchange_rate_usd: props.exchangeRates?.USD || 1.80,
});

const companyForm = useForm({
    company_name: props.companyName || 'WizFlussi',
});

const languageForm = useForm({
    language: page.props.auth.user?.language || 'bs',
});

const languages = [
    { code: 'bs', name: 'Srpski - Hrvatski - Bosanski' },
    { code: 'de', name: 'Njemački' },
    { code: 'en', name: 'Engleski' },
    { code: 'it', name: 'Italijanski' },
    { code: 'sl', name: 'Slovenski' },
    { code: 'es', name: 'Španski' },
    { code: 'bg', name: 'Bugarski' },
    { code: 'hu', name: 'Mađarski' },
    { code: 'fr', name: 'Francuski' },
    { code: 'el', name: 'Grčki' },
];

const updateProfile = () => {
    profileForm.put(route('settings.profile'), {
        preserveScroll: true,
        onSuccess: () => {
            // Profile updated
        },
    });
};

const updatePassword = () => {
    passwordForm.put(route('settings.password'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
        },
    });
};

const updateExchangeRates = () => {
    exchangeForm.put(route('settings.exchange-rates'), {
        preserveScroll: true,
    });
};

const updateCompanyName = () => {
    companyForm.put(route('settings.company-name'), {
        preserveScroll: true,
    });
};

const updateLanguage = () => {
    languageForm.put(route('settings.language'), {
        preserveScroll: true,
        onSuccess: () => {
            // Reload page to apply new language
            window.location.reload();
        },
    });
};

const downloadBackup = () => {
    window.location.href = route('settings.backup');
};

const handleFileSelect = (event) => {
    importFile.value = event.target.files[0];
};

const submitImport = () => {
    if (!importFile.value) return;
    
    importProcessing.value = true;
    const formData = new FormData();
    formData.append('file', importFile.value);
    
    router.post(route('settings.import'), formData, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
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
        <Header :title="__('settings')" />
        <div class="p-6 space-y-6 max-w-3xl">
            <!-- Company Name (Admin only) -->
            <div v-if="isAdmin" class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <BuildingOfficeIcon class="h-5 w-5 text-blue-600" />
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('company_name') }}</h2>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">{{ __('company_name_desc') }}</p>
                </div>
                <form @submit.prevent="updateCompanyName" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('company_name') }}</label>
                        <input v-model="companyForm.company_name" type="text" maxlength="100" class="w-full px-4 py-2 border border-gray-300 rounded-lg" :placeholder="__('company_name_placeholder')" />
                        <p v-if="companyForm.errors.company_name" class="mt-1 text-sm text-red-600">{{ companyForm.errors.company_name }}</p>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="companyForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50">
                            {{ companyForm.processing ? __('saving') : __('save_company_name') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Profile Settings -->
            <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <UserIcon class="h-5 w-5 text-blue-600" />
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('profile') }}</h2>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">{{ __('manage_profile') }}</p>
                </div>
                <form @submit.prevent="updateProfile" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('name') }}</label>
                            <input v-model="profileForm.name" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                            <p v-if="profileForm.errors.name" class="mt-1 text-sm text-red-600">{{ profileForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('email') }}</label>
                            <input v-model="profileForm.email" type="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                            <p v-if="profileForm.errors.email" class="mt-1 text-sm text-red-600">{{ profileForm.errors.email }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="profileForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50">
                            {{ profileForm.processing ? __('saving') : __('save_profile') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Language Settings -->
            <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                        </svg>
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('language') }}</h2>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">{{ __('language_desc') }}</p>
                </div>
                <form @submit.prevent="updateLanguage" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('language') }}</label>
                        <select v-model="languageForm.language" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option v-for="lang in languages" :key="lang.code" :value="lang.code">{{ lang.name }}</option>
                        </select>
                        <p v-if="languageForm.errors.language" class="mt-1 text-sm text-red-600">{{ languageForm.errors.language }}</p>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="languageForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-purple-500 rounded-lg hover:bg-purple-600 disabled:opacity-50">
                            {{ languageForm.processing ? __('saving') : __('save_language') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security -->
            <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <ShieldCheckIcon class="h-5 w-5 text-blue-600" />
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('security') }}</h2>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">{{ __('change_password') }}</p>
                </div>
                <form @submit.prevent="updatePassword" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('current_password') }}</label>
                        <input v-model="passwordForm.current_password" type="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                        <p v-if="passwordForm.errors.current_password" class="mt-1 text-sm text-red-600">{{ passwordForm.errors.current_password }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('new_password') }}</label>
                            <input v-model="passwordForm.password" type="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                            <p v-if="passwordForm.errors.password" class="mt-1 text-sm text-red-600">{{ passwordForm.errors.password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('confirm_password') }}</label>
                            <input v-model="passwordForm.password_confirmation" type="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="passwordForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50">
                            {{ passwordForm.processing ? __('saving') : __('change_password_btn') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Exchange Rates (Admin only) -->
            <div v-if="isAdmin" class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <CurrencyDollarIcon class="h-5 w-5 text-green-600" />
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('exchange_rates') }}</h2>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">{{ __('exchange_rates_desc') }}</p>
                </div>
                <form @submit.prevent="updateExchangeRates" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">1 EUR = KM</label>
                            <input v-model="exchangeForm.exchange_rate_eur" type="number" step="0.00001" min="0.01" max="10" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                            <p class="mt-1 text-xs text-gray-500">{{ __('fixed_rate') }}</p>
                            <p v-if="exchangeForm.errors.exchange_rate_eur" class="mt-1 text-sm text-red-600">{{ exchangeForm.errors.exchange_rate_eur }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">1 USD = KM</label>
                            <input v-model="exchangeForm.exchange_rate_usd" type="number" step="0.00001" min="0.01" max="10" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                            <p class="mt-1 text-xs text-gray-500">{{ __('variable_rate') }}</p>
                            <p v-if="exchangeForm.errors.exchange_rate_usd" class="mt-1 text-sm text-red-600">{{ exchangeForm.errors.exchange_rate_usd }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="exchangeForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-green-500 rounded-lg hover:bg-green-600 disabled:opacity-50">
                            {{ exchangeForm.processing ? __('saving') : __('save_rates') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Backup & Import (Admin only) -->
            <div v-if="isAdmin" class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <ServerIcon class="h-5 w-5 text-orange-600" />
                        <h2 class="text-lg font-semibold text-gray-900">Backup & Import</h2>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Preuzmite backup svih podataka ili importujte podatke sa drugog profila</p>
                </div>
                <div class="p-6 space-y-6">
                    <!-- Backup Section -->
                    <div>
                        <h3 class="font-medium text-sm text-gray-900 mb-2">Preuzmi Backup</h3>
                        <p class="text-sm text-gray-600 mb-4">Preuzmite JSON fajl sa svim dobavljačima, poslovnicama, plaćanjima i planovima.</p>
                        <button @click="downloadBackup" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600">
                            <ArrowDownTrayIcon class="h-4 w-4" /> Preuzmi Backup
                        </button>
                    </div>

                    <hr />

                    <!-- Import Section -->
                    <div>
                        <h3 class="font-medium text-sm text-gray-900 mb-2">Importuj Podatke</h3>
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
                            <h4 class="font-medium text-amber-800 mb-2 flex items-center gap-2">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Upozorenje
                            </h4>
                            <ul class="text-sm text-amber-700 space-y-1 list-disc list-inside">
                                <li>Import će dodati nove dobavljače, poslovnice, plaćanja i planove</li>
                                <li>Postojeći podaci neće biti obrisani</li>
                                <li>Preporučujemo da prvo napravite backup trenutnih podataka</li>
                                <li>Podržan format: JSON fajl iz backup funkcije</li>
                            </ul>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Odaberi JSON fajl</label>
                                <input 
                                    type="file" 
                                    @change="handleFileSelect"
                                    accept=".json"
                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                />
                            </div>

                            <div v-if="importFile" class="bg-gray-50 rounded-lg p-3 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <DocumentArrowDownIcon class="h-5 w-5 text-gray-400" />
                                    <span class="text-sm text-gray-700">{{ importFile.name }}</span>
                                </div>
                                <button @click="importFile = null" class="text-gray-400 hover:text-red-500">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <button 
                                @click="submitImport" 
                                :disabled="!importFile || importProcessing"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <ArrowUpTrayIcon class="h-4 w-4" />
                                {{ importProcessing ? 'Importujem...' : 'Importuj Podatke' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Info -->
            <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <ServerIcon class="h-5 w-5 text-blue-600" />
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('system') }}</h2>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">{{ __('system_info') }}</p>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-sm text-gray-900">{{ __('app_version') }}</p>
                            <p class="text-sm text-gray-500">{{ page.props.appSettings?.companyName || 'WizFlussi' }} v1.0.0</p>
                        </div>
                    </div>
                    <hr />
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-sm text-gray-900">{{ __('your_role') }}</p>
                            <p class="text-sm text-gray-500">{{ page.props.auth.user?.role || 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
