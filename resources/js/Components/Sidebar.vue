<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import {
    HomeIcon,
    CreditCardIcon,
    BuildingOffice2Icon,
    DocumentTextIcon,
    Cog6ToothIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    WalletIcon,
    ArrowRightOnRectangleIcon,
    ClipboardDocumentListIcon,
} from '@heroicons/vue/24/outline';

defineProps({
    collapsed: Boolean,
});

defineEmits(['toggle']);

const page = usePage();

const navigation = [
    { name: 'Pregled', href: '/', icon: HomeIcon, routeName: 'dashboard' },
    { name: 'Plaćanja', href: '/payments', icon: CreditCardIcon, routeName: 'payments.index' },
    { name: 'Planovi', href: '/plans', icon: ClipboardDocumentListIcon, routeName: 'plans.index' },
    { name: 'Dobavljači', href: '/suppliers', icon: BuildingOffice2Icon, routeName: 'suppliers.index' },
    { name: 'Izvještaji', href: '/reports', icon: DocumentTextIcon, routeName: 'reports.index' },
    { name: 'Postavke', href: '/settings', icon: Cog6ToothIcon, routeName: 'settings.index' },
];

const roleLabels = {
    admin: 'Administrator',
    accountant: 'Računovodstvo',
    viewer: 'Pregled',
};

const logout = () => {
    router.post(route('logout'));
};

const isActive = (routeName) => {
    return route().current(routeName);
};
</script>

<template>
    <aside
        :class="[
            'flex flex-col bg-slate-800 text-slate-200 transition-all duration-300',
            collapsed ? 'w-16' : 'w-64'
        ]"
    >
        <!-- Logo -->
        <div class="flex h-16 items-center justify-between px-4 border-b border-slate-700">
            <div v-if="!collapsed" class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-500">
                    <WalletIcon class="h-5 w-5 text-white" />
                </div>
                <span class="font-semibold text-sm">e-Stanari</span>
            </div>
            <button
                @click="$emit('toggle')"
                class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-slate-700 transition-colors"
            >
                <ChevronLeftIcon v-if="!collapsed" class="h-4 w-4" />
                <ChevronRightIcon v-else class="h-4 w-4" />
            </button>
        </div>

        <!-- User info -->
        <div v-if="!collapsed && page.props.auth.user" class="px-4 py-3 border-b border-slate-700">
            <p class="text-sm font-medium truncate">{{ page.props.auth.user.email }}</p>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-700 text-slate-300 mt-1">
                {{ roleLabels[page.props.auth.user.role] || page.props.auth.user.role }}
            </span>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-2 py-4 space-y-1">
            <Link
                v-for="item in navigation"
                :key="item.name"
                :href="item.href"
                :class="[
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                    isActive(item.routeName)
                        ? 'bg-blue-500 text-white'
                        : 'text-slate-300 hover:bg-slate-700 hover:text-white'
                ]"
            >
                <component :is="item.icon" class="h-5 w-5 flex-shrink-0" />
                <span v-if="!collapsed">{{ item.name }}</span>
            </Link>
        </nav>

        <!-- Logout -->
        <div class="p-2 border-t border-slate-700">
            <button
                @click="logout"
                :class="[
                    'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-700 hover:text-white transition-colors',
                    collapsed && 'justify-center'
                ]"
            >
                <ArrowRightOnRectangleIcon class="h-5 w-5" />
                <span v-if="!collapsed">Odjavi se</span>
            </button>
        </div>

        <!-- Footer -->
        <div v-if="!collapsed" class="p-4 border-t border-slate-700">
            <div class="text-xs text-slate-500">
                <p>e-Stanari v1.0</p>
                <p class="mt-1">© 2026</p>
            </div>
        </div>
    </aside>
</template>
