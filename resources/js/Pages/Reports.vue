<script setup>
import { ref } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import Header from '@/Components/Header.vue';
import { CalendarIcon, ChartBarIcon, ChartPieIcon, DocumentTextIcon, ArrowDownTrayIcon, TableCellsIcon } from '@heroicons/vue/24/outline';
import DateInput from '@/Components/DateInput.vue';
import MonthInput from '@/Components/MonthInput.vue';

const selectedDate = ref(new Date().toISOString().split('T')[0]);
const selectedMonth = ref(new Date().toISOString().slice(0, 7));

const downloadDaily = () => {
    window.location.href = `/reports/daily?date=${selectedDate.value}`;
};

const downloadMonthly = () => {
    window.location.href = `/reports/monthly?month=${selectedMonth.value}`;
};

const downloadBySupplier = () => {
    window.location.href = '/reports/by-supplier';
};

const downloadByCurrency = () => {
    window.location.href = '/reports/by-currency';
};
</script>

<template>
    <MainLayout>
        <Header title="Izvještaji" />
        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Daily Report -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Dnevni izvještaj</h3>
                            <p class="text-sm text-gray-500 mt-1">Pregled svih plaćanja za odabrani dan</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100">
                            <CalendarIcon class="h-5 w-5 text-blue-600" />
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Odaberi datum</label>
                        <DateInput v-model="selectedDate" />
                    </div>
                    <button @click="downloadDaily" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                        <TableCellsIcon class="h-4 w-4" /> Preuzmi Excel
                    </button>
                </div>

                <!-- Monthly Report -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Mjesečni izvještaj</h3>
                            <p class="text-sm text-gray-500 mt-1">Sumarni pregled plaćanja po danima u mjesecu</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100">
                            <ChartBarIcon class="h-5 w-5 text-emerald-600" />
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Odaberi mjesec</label>
                        <MonthInput v-model="selectedMonth" />
                    </div>
                    <button @click="downloadMonthly" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                        <TableCellsIcon class="h-4 w-4" /> Preuzmi Excel
                    </button>
                </div>

                <!-- By Supplier Report -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Izvještaj po dobavljaču</h3>
                            <p class="text-sm text-gray-500 mt-1">Detaljan pregled plaćanja za svakog dobavljača</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100">
                            <ChartPieIcon class="h-5 w-5 text-purple-600" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mb-4">Uključuje sve dobavljače sa ukupnim iznosima po valutama</p>
                    <button @click="downloadBySupplier" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                        <TableCellsIcon class="h-4 w-4" /> Preuzmi Excel
                    </button>
                </div>

                <!-- By Currency Report -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Izvještaj po valuti</h3>
                            <p class="text-sm text-gray-500 mt-1">Pregled plaćanja grupisanih po valutama (KM, EUR, USD)</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100">
                            <DocumentTextIcon class="h-5 w-5 text-amber-600" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mb-4">Excel sa odvojenim listovima za svaku valutu + pregled</p>
                    <button @click="downloadByCurrency" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                        <TableCellsIcon class="h-4 w-4" /> Preuzmi Excel
                    </button>
                </div>
            </div>

            <!-- Info Section -->
            <div class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-8 text-center">
                <TableCellsIcon class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                <h3 class="font-medium text-gray-900 mb-2">Profesionalni Excel izvještaji</h3>
                <p class="text-sm text-gray-500 max-w-md mx-auto">
                    Svi izvještaji se generišu u Excel formatu (.xlsx) sa formatiranim tabelama, 
                    bojama i automatski prilagođenim širinama kolona. Spremni za štampu ili dalju obradu.
                </p>
            </div>
        </div>
    </MainLayout>
</template>
