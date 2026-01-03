<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import { UserIcon, BellIcon, ShieldCheckIcon, ServerIcon, CurrencyDollarIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    exchangeRates: Object,
});

const page = usePage();
const isAdmin = page.props.auth.user?.role === 'admin';

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
</script>

<template>
    <MainLayout>
        <Header title="Postavke" />
        <div class="p-6 space-y-6 max-w-3xl">
            <!-- Profile Settings -->
            <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <UserIcon class="h-5 w-5 text-blue-600" />
                        <h2 class="text-lg font-semibold text-gray-900">Profil</h2>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Upravljajte svojim korisničkim profilom</p>
                </div>
                <form @submit.prevent="updateProfile" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ime</label>
                            <input v-model="profileForm.name" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                            <p v-if="profileForm.errors.name" class="mt-1 text-sm text-red-600">{{ profileForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input v-model="profileForm.email" type="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                            <p v-if="profileForm.errors.email" class="mt-1 text-sm text-red-600">{{ profileForm.errors.email }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="profileForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50">
                            {{ profileForm.processing ? 'Spremanje...' : 'Spremi profil' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security -->
            <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <ShieldCheckIcon class="h-5 w-5 text-blue-600" />
                        <h2 class="text-lg font-semibold text-gray-900">Sigurnost</h2>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Promijenite lozinku</p>
                </div>
                <form @submit.prevent="updatePassword" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Trenutna lozinka</label>
                        <input v-model="passwordForm.current_password" type="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                        <p v-if="passwordForm.errors.current_password" class="mt-1 text-sm text-red-600">{{ passwordForm.errors.current_password }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nova lozinka</label>
                            <input v-model="passwordForm.password" type="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                            <p v-if="passwordForm.errors.password" class="mt-1 text-sm text-red-600">{{ passwordForm.errors.password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Potvrdi lozinku</label>
                            <input v-model="passwordForm.password_confirmation" type="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="passwordForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 disabled:opacity-50">
                            {{ passwordForm.processing ? 'Spremanje...' : 'Promijeni lozinku' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Exchange Rates (Admin only) -->
            <div v-if="isAdmin" class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <CurrencyDollarIcon class="h-5 w-5 text-green-600" />
                        <h2 class="text-lg font-semibold text-gray-900">Kursevi valuta</h2>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Postavite kurseve za konverziju u KM (koristi se za sortiranje i prikaz ukupno)</p>
                </div>
                <form @submit.prevent="updateExchangeRates" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">1 EUR = KM</label>
                            <input v-model="exchangeForm.exchange_rate_eur" type="number" step="0.00001" min="0.01" max="10" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                            <p class="mt-1 text-xs text-gray-500">Fiksni kurs: 1.95583</p>
                            <p v-if="exchangeForm.errors.exchange_rate_eur" class="mt-1 text-sm text-red-600">{{ exchangeForm.errors.exchange_rate_eur }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">1 USD = KM</label>
                            <input v-model="exchangeForm.exchange_rate_usd" type="number" step="0.00001" min="0.01" max="10" class="w-full px-4 py-2 border border-gray-300 rounded-lg" />
                            <p class="mt-1 text-xs text-gray-500">Promjenjiv kurs</p>
                            <p v-if="exchangeForm.errors.exchange_rate_usd" class="mt-1 text-sm text-red-600">{{ exchangeForm.errors.exchange_rate_usd }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="exchangeForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-green-500 rounded-lg hover:bg-green-600 disabled:opacity-50">
                            {{ exchangeForm.processing ? 'Spremanje...' : 'Spremi kurseve' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- System Info -->
            <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <ServerIcon class="h-5 w-5 text-blue-600" />
                        <h2 class="text-lg font-semibold text-gray-900">Sistem</h2>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Informacije o sistemu</p>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-sm text-gray-900">Verzija aplikacije</p>
                            <p class="text-sm text-gray-500">e-Stanari v1.0.0</p>
                        </div>
                    </div>
                    <hr />
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-sm text-gray-900">Vaša uloga</p>
                            <p class="text-sm text-gray-500">{{ page.props.auth.user?.role || 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
