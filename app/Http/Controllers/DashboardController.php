<?php

namespace App\Http\Controllers;

use App\Helpers\TranslationHelper;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Supplier;
use App\Services\ExcelExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $query = Payment::with(['supplier:id,name', 'branch:id,name'])->orderBy('planned_date');
        $today = today();

        // Date filter
        $dateFilter = $request->get('date_filter', 'today');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        switch ($dateFilter) {
            case 'today':
                $query->where(function ($q) use ($today) {
                    $q->whereDate('planned_date', $today)
                      ->orWhere(function ($sq) use ($today) {
                          $sq->where('status', 'PAID')
                             ->whereDate('paid_date', $today);
                      });
                });
                break;
            case 'tomorrow':
                $query->whereDate('planned_date', $today->copy()->addDay());
                break;
            case '3days':
                $query->whereDate('planned_date', '>=', $today)
                      ->whereDate('planned_date', '<', $today->copy()->addDays(4));
                break;
            case '7days':
                $query->whereDate('planned_date', '>=', $today)
                      ->whereDate('planned_date', '<', $today->copy()->addDays(8));
                break;
            case 'period':
                if ($startDate) $query->whereDate('planned_date', '>=', $startDate);
                if ($endDate) $query->whereDate('planned_date', '<=', $endDate);
                break;
            case 'overdue':
                $query->where('status', 'PLANNED')
                      ->whereDate('planned_date', '<', $today);
                break;
            case 'all':
                // No date filter
                break;
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Currency filter
        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        // Supplier filter
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Branch filter
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Search filter - search across supplier name, invoice number, description, and branch name
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('supplier', function ($sq) use ($searchTerm) {
                    $sq->where('name', 'like', '%' . $searchTerm . '%');
                })
                ->orWhereHas('branch', function ($bq) use ($searchTerm) {
                    $bq->where('name', 'like', '%' . $searchTerm . '%');
                })
                ->orWhere('invoice_number', 'like', '%' . $searchTerm . '%')
                ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        $payments = $query->get();

        // Sorting after fetching (to avoid JOIN conflicts with eager loading)
        if ($request->filled('sort_by')) {
            $sortBy = $request->sort_by;
            $sortDirection = $request->get('sort_direction', 'asc');
            
            $payments = match($sortBy) {
                'invoice_number' => $sortDirection === 'asc' 
                    ? $payments->sortBy('invoice_number', SORT_NATURAL | SORT_FLAG_CASE)->values()
                    : $payments->sortByDesc('invoice_number', SORT_NATURAL | SORT_FLAG_CASE)->values(),
                'supplier' => $sortDirection === 'asc'
                    ? $payments->sortBy(fn($p) => $p->supplier->name ?? '')->values()
                    : $payments->sortByDesc(fn($p) => $p->supplier->name ?? '')->values(),
                'branch' => $sortDirection === 'asc'
                    ? $payments->sortBy(fn($p) => $p->branch->name ?? '')->values()
                    : $payments->sortByDesc(fn($p) => $p->branch->name ?? '')->values(),
                'amount' => $sortDirection === 'asc'
                    ? $payments->sortBy('amount')->values()
                    : $payments->sortByDesc('amount')->values(),
                'status' => $sortDirection === 'asc'
                    ? $payments->sortBy('status')->values()
                    : $payments->sortByDesc('status')->values(),
                'planned_date' => $sortDirection === 'asc'
                    ? $payments->sortBy('planned_date')->values()
                    : $payments->sortByDesc('planned_date')->values(),
                default => $payments
            };
        }

        // Attach plan names to payments
        $allPlans = \App\Models\PaymentPlan::select('id', 'name', 'payment_ids')->get();
        $payments = $payments->map(function ($payment) use ($allPlans) {
            $payment->plan_names = $allPlans->filter(function ($plan) use ($payment) {
                $paymentIds = is_array($plan->payment_ids) ? $plan->payment_ids : [];
                return in_array($payment->id, $paymentIds);
            })->pluck('name')->toArray();
            return $payment;
        });

        // Stats (always for today, regardless of filters)
        $todayPayments = Payment::planned()->whereDate('planned_date', $today)->count();
        $overduePayments = Payment::planned()->whereDate('planned_date', '<', $today)->count();

        $plannedPayments = $payments->where('status', 'PLANNED');
        $paidPayments = $payments->where('status', 'PAID');

        $stats = [
            'todayCount' => $todayPayments,
            'plannedCount' => $plannedPayments->count(),
            'paidCount' => $paidPayments->count(),
            'overdueCount' => $overduePayments,
            'totalPlannedKM' => $plannedPayments->where('currency', 'KM')->sum('amount'),
            'totalPlannedEUR' => $plannedPayments->where('currency', 'EUR')->sum('amount'),
            'totalPlannedUSD' => $plannedPayments->where('currency', 'USD')->sum('amount'),
            'totalKM' => $payments->where('currency', 'KM')->sum('amount'),
            'totalEUR' => $payments->where('currency', 'EUR')->sum('amount'),
            'totalUSD' => $payments->where('currency', 'USD')->sum('amount'),
        ];

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name', 'supplier_id']);
        $plans = \App\Models\PaymentPlan::orderBy('created_at', 'desc')->get(['id', 'name']);

        return Inertia::render('Dashboard', [
            'payments' => $payments,
            'stats' => $stats,
            'suppliers' => $suppliers,
            'branches' => $branches,
            'plans' => $plans,
            'filters' => [
                'date_filter' => $dateFilter,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $request->status,
                'currency' => $request->currency,
                'supplier_id' => $request->supplier_id,
                'branch_id' => $request->branch_id,
                'search' => $request->search,
                'sort_by' => $request->sort_by,
                'sort_direction' => $request->sort_direction,
            ],
        ]);
    }

    public function export(Request $request): Response
    {
        $query = Payment::with(['supplier:id,name', 'branch:id,name'])->orderBy('planned_date');
        $today = today();

        $dateFilter = $request->get('date_filter', 'today');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        switch ($dateFilter) {
            case 'today':
                $query->whereDate('planned_date', $today);
                break;
            case 'tomorrow':
                $query->whereDate('planned_date', $today->copy()->addDay());
                break;
            case '3days':
                $query->whereDate('planned_date', '>=', $today)->whereDate('planned_date', '<', $today->copy()->addDays(4));
                break;
            case '7days':
                $query->whereDate('planned_date', '>=', $today)->whereDate('planned_date', '<', $today->copy()->addDays(8));
                break;
            case 'period':
                if ($startDate) $query->whereDate('planned_date', '>=', $startDate);
                if ($endDate) $query->whereDate('planned_date', '<=', $endDate);
                break;
        }

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('currency')) $query->where('currency', $request->currency);
        if ($request->filled('supplier_id')) $query->where('supplier_id', $request->supplier_id);
        if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);

        // Search filter for CSV export
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('supplier', function ($sq) use ($searchTerm) {
                    $sq->where('name', 'like', '%' . $searchTerm . '%');
                })
                ->orWhereHas('branch', function ($bq) use ($searchTerm) {
                    $bq->where('name', 'like', '%' . $searchTerm . '%');
                })
                ->orWhere('invoice_number', 'like', '%' . $searchTerm . '%')
                ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        $payments = $query->get();

        $csv = "PLAN PLAĆANJA - " . date('d.m.Y H:i') . "\n";
        $csv .= "Period: " . $this->getDateFilterLabel($dateFilter, $startDate, $endDate) . "\n\n";
        $csv .= "Dobavljač,Poslovnica,Iznos,Valuta,Status,Planirani datum,Opis\n";

        $totalKM = 0;
        $totalEUR = 0;
        $totalUSD = 0;

        foreach ($payments as $payment) {
            $csv .= sprintf(
                "\"%s\",\"%s\",%.2f,%s,%s,%s,\"%s\"\n",
                $payment->supplier->name ?? '',
                $payment->branch->name ?? '',
                $payment->amount,
                $payment->currency,
                $payment->status === 'PAID' ? 'Plaćeno' : 'Neplaceno',
                $payment->planned_date->format('d.m.Y'),
                $payment->description ?? ''
            );
            if ($payment->currency === 'KM') $totalKM += $payment->amount;
            elseif ($payment->currency === 'EUR') $totalEUR += $payment->amount;
            else $totalUSD += $payment->amount;
        }

        $csv .= "\n";
        $csv .= "UKUPNO KM:,," . number_format($totalKM, 2, ',', '.') . ",KM\n";
        $csv .= "UKUPNO EUR:,," . number_format($totalEUR, 2, ',', '.') . ",EUR\n";
        $csv .= "UKUPNO USD:,," . number_format($totalUSD, 2, ',', '.') . ",USD\n";
        $csv .= "Broj plaćanja:,," . $payments->count() . "\n";

        $filename = 'plan_placanja_' . date('d-m-Y_His') . '.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function getDateFilterLabel($filter, $startDate, $endDate): string
    {
        return match($filter) {
            'today' => 'Danas (' . today()->format('d.m.Y') . ')',
            'tomorrow' => 'Sutra (' . today()->addDay()->format('d.m.Y') . ')',
            '3days' => 'Sljedeća 3 dana',
            '7days' => 'Sljedećih 7 dana',
            'period' => ($startDate ?? 'početak') . ' - ' . ($endDate ?? 'kraj'),
            'all' => 'Svi datumi',
            default => $filter,
        };
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $locale = TranslationHelper::getUserLocale();
        $query = Payment::with(['supplier:id,name', 'branch:id,name']);
        $today = today();

        $dateFilter = $request->get('date_filter', 'today');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        switch ($dateFilter) {
            case 'today':
                $query->whereDate('planned_date', $today);
                break;
            case 'tomorrow':
                $query->whereDate('planned_date', $today->copy()->addDay());
                break;
            case '3days':
                $query->whereDate('planned_date', '>=', $today)->whereDate('planned_date', '<', $today->copy()->addDays(4));
                break;
            case '7days':
                $query->whereDate('planned_date', '>=', $today)->whereDate('planned_date', '<', $today->copy()->addDays(8));
                break;
            case 'period':
                if ($startDate) $query->whereDate('planned_date', '>=', $startDate);
                if ($endDate) $query->whereDate('planned_date', '<=', $endDate);
                break;
        }

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('currency')) $query->where('currency', $request->currency);
        if ($request->filled('supplier_id')) $query->where('supplier_id', $request->supplier_id);
        if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('supplier', function ($sq) use ($searchTerm) {
                    $sq->where('name', 'like', '%' . $searchTerm . '%');
                })
                ->orWhereHas('branch', function ($bq) use ($searchTerm) {
                    $bq->where('name', 'like', '%' . $searchTerm . '%');
                })
                ->orWhere('invoice_number', 'like', '%' . $searchTerm . '%')
                ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        $exchangeRates = Setting::getExchangeRates();
        
        $payments = $query->get()
            ->map(function ($payment) use ($exchangeRates) {
                $payment->amount_in_km = $this->convertToKM($payment->amount, $payment->currency, $exchangeRates);
                return $payment;
            })
            ->sortByDesc('amount_in_km')
            ->values();

        $totalKM = $payments->where('currency', 'KM')->sum('amount');
        $totalEUR = $payments->where('currency', 'EUR')->sum('amount');
        $totalUSD = $payments->where('currency', 'USD')->sum('amount');
        
        $hasEurOrUsd = true;
        $grandTotalKM = $payments->sum('amount_in_km');

        $colCount = 9;
        
        $excel = new ExcelExportService();
        
        $excel->setTitle(
            TranslationHelper::trans('payment_list', $locale),
            TranslationHelper::trans('period', $locale) . ': ' . $this->getDateFilterLabel($dateFilter, $startDate, $endDate) . ' | ' . TranslationHelper::trans('date', $locale) . ': ' . now()->format('d.m.Y H:i'),
            $colCount
        );

        $summaryData = [
            TranslationHelper::trans('total_km', $locale) => number_format($totalKM, 2, ',', '.') . ' KM',
            TranslationHelper::trans('total_eur', $locale) => number_format($totalEUR, 2, ',', '.') . ' EUR',
            TranslationHelper::trans('total_usd', $locale) => number_format($totalUSD, 2, ',', '.') . ' USD',
            TranslationHelper::trans('payment_count', $locale) => $payments->count(),
            strtoupper(TranslationHelper::trans('total_km', $locale)) => number_format($grandTotalKM, 2, ',', '.') . ' KM',
        ];

        $excel->setSummaryRow($summaryData, 3, $colCount);

        $headers = [
            TranslationHelper::trans('invoice_number', $locale),
            TranslationHelper::trans('description', $locale),
            TranslationHelper::trans('supplier', $locale),
            TranslationHelper::trans('branch', $locale),
            TranslationHelper::trans('amount', $locale),
            TranslationHelper::trans('currency', $locale),
            TranslationHelper::trans('status', $locale),
            TranslationHelper::trans('date', $locale),
            TranslationHelper::trans('total_km', $locale)
        ];
        $excel->setHeaders($headers, 5);

        $data = [];
        foreach ($payments as $payment) {
            $row = [
                'invoice' => $payment->invoice_number ?? '-',
                'description' => $payment->description ?? '-',
                'supplier' => $payment->supplier->name ?? '-',
                'branch' => $payment->branch->name ?? '-',
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status === 'PAID' ? TranslationHelper::trans('paid', $locale) : TranslationHelper::trans('unpaid', $locale),
                'date' => $payment->planned_date->format('d.m.Y'),
                'amount_km' => $payment->amount_in_km,
            ];
            
            $data[] = $row;
        }

        $columnTypes = ['amount' => 'currency', 'amount_km' => 'currency'];

        $excel->setData($data, 6, $columnTypes);
        
        $totalsRow = 6 + count($data);
        $totalsData = ['', '', '', strtoupper(TranslationHelper::trans('total_km', $locale)) . ':', '', '', '', '', $grandTotalKM];
        $excel->setTotalsRow($totalsData, $totalsRow, $colCount);
        
        $excel->getSheet()->getStyle('I' . $totalsRow)->getNumberFormat()->setFormatCode('#,##0.00');
        
        $excel->autoSizeColumns($colCount);

        $filename = $this->getTranslatedFilename('payment_overview', $locale) . '_' . date('d-m-Y') . '.xlsx';
        return $excel->download($filename);
    }

    private function getTranslatedFilename(string $key, string $locale): string
    {
        $filenames = [
            'bs' => [
                'payment_overview' => 'pregled_placanja',
                'unpaid_payments' => 'neplacena_placanja',
            ],
            'de' => [
                'payment_overview' => 'zahlungsuebersicht',
                'unpaid_payments' => 'unbezahlte_zahlungen',
            ],
            'en' => [
                'payment_overview' => 'payment_overview',
                'unpaid_payments' => 'unpaid_payments',
            ],
        ];
        
        return $filenames[$locale][$key] ?? $filenames['en'][$key] ?? $key;
    }

    private function convertToKM(float $amount, string $currency, array $rates): float
    {
        return match ($currency) {
            'EUR' => $amount * $rates['EUR'],
            'USD' => $amount * $rates['USD'],
            default => $amount,
        };
    }

    public function unpaid(Request $request): InertiaResponse
    {
        $query = Payment::with(['supplier:id,name', 'branch:id,name'])
            ->where('status', 'PLANNED')
            ->orderBy('planned_date');
        
        $today = today();

        // Date filter
        $dateFilter = $request->get('date_filter', 'all');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        switch ($dateFilter) {
            case 'today':
                $query->whereDate('planned_date', $today);
                break;
            case 'tomorrow':
                $query->whereDate('planned_date', $today->copy()->addDay());
                break;
            case '3days':
                $query->whereDate('planned_date', '>=', $today)
                      ->whereDate('planned_date', '<', $today->copy()->addDays(4));
                break;
            case '7days':
                $query->whereDate('planned_date', '>=', $today)
                      ->whereDate('planned_date', '<', $today->copy()->addDays(8));
                break;
            case 'period':
                if ($startDate) $query->whereDate('planned_date', '>=', $startDate);
                if ($endDate) $query->whereDate('planned_date', '<=', $endDate);
                break;
            case 'overdue':
                $query->whereDate('planned_date', '<', $today);
                break;
            case 'all':
                // No date filter
                break;
        }

        // Currency filter
        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        // Supplier filter
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Branch filter
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Search filter
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('supplier', function ($sq) use ($searchTerm) {
                    $sq->where('name', 'like', '%' . $searchTerm . '%');
                })
                ->orWhereHas('branch', function ($bq) use ($searchTerm) {
                    $bq->where('name', 'like', '%' . $searchTerm . '%');
                })
                ->orWhere('invoice_number', 'like', '%' . $searchTerm . '%')
                ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        $payments = $query->get();

        // Stats
        $todayPayments = Payment::planned()->whereDate('planned_date', $today)->count();
        $overduePayments = Payment::planned()->whereDate('planned_date', '<', $today)->count();

        $stats = [
            'todayCount' => $todayPayments,
            'plannedCount' => $payments->count(),
            'overdueCount' => $overduePayments,
            'totalKM' => $payments->where('currency', 'KM')->sum('amount'),
            'totalEUR' => $payments->where('currency', 'EUR')->sum('amount'),
            'totalUSD' => $payments->where('currency', 'USD')->sum('amount'),
        ];

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name', 'supplier_id']);

        return Inertia::render('Unpaid', [
            'payments' => $payments,
            'stats' => $stats,
            'suppliers' => $suppliers,
            'branches' => $branches,
            'filters' => [
                'date_filter' => $dateFilter,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'currency' => $request->currency,
                'supplier_id' => $request->supplier_id,
                'branch_id' => $request->branch_id,
                'search' => $request->search,
            ],
        ]);
    }

    public function unpaidExport(Request $request): Response
    {
        $query = Payment::with(['supplier:id,name', 'branch:id,name'])
            ->where('status', 'PLANNED')
            ->orderBy('planned_date');
        
        $today = today();
        $dateFilter = $request->get('date_filter', 'all');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        switch ($dateFilter) {
            case 'today':
                $query->whereDate('planned_date', $today);
                break;
            case 'tomorrow':
                $query->whereDate('planned_date', $today->copy()->addDay());
                break;
            case '3days':
                $query->whereDate('planned_date', '>=', $today)->whereDate('planned_date', '<', $today->copy()->addDays(4));
                break;
            case '7days':
                $query->whereDate('planned_date', '>=', $today)->whereDate('planned_date', '<', $today->copy()->addDays(8));
                break;
            case 'period':
                if ($startDate) $query->whereDate('planned_date', '>=', $startDate);
                if ($endDate) $query->whereDate('planned_date', '<=', $endDate);
                break;
            case 'overdue':
                $query->whereDate('planned_date', '<', $today);
                break;
        }

        if ($request->filled('currency')) $query->where('currency', $request->currency);
        if ($request->filled('supplier_id')) $query->where('supplier_id', $request->supplier_id);
        if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('supplier', function ($sq) use ($searchTerm) {
                    $sq->where('name', 'like', '%' . $searchTerm . '%');
                })
                ->orWhereHas('branch', function ($bq) use ($searchTerm) {
                    $bq->where('name', 'like', '%' . $searchTerm . '%');
                })
                ->orWhere('invoice_number', 'like', '%' . $searchTerm . '%')
                ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        $payments = $query->get();

        $csv = "NEPLAĆENA PLAĆANJA - " . date('d.m.Y H:i') . "\n";
        $csv .= "Period: " . $this->getDateFilterLabel($dateFilter, $startDate, $endDate) . "\n\n";
        $csv .= "Dobavljač,Poslovnica,Broj fakture,Iznos,Valuta,Platiti do,Opis\n";

        $totalKM = 0;
        $totalEUR = 0;
        $totalUSD = 0;

        foreach ($payments as $payment) {
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",%.2f,%s,%s,\"%s\"\n",
                $payment->supplier->name ?? '',
                $payment->branch->name ?? '',
                $payment->invoice_number ?? '',
                $payment->amount,
                $payment->currency,
                $payment->planned_date->format('d.m.Y'),
                $payment->description ?? ''
            );
            if ($payment->currency === 'KM') $totalKM += $payment->amount;
            elseif ($payment->currency === 'EUR') $totalEUR += $payment->amount;
            else $totalUSD += $payment->amount;
        }

        $csv .= "\n";
        $csv .= "UKUPNO KM:,," . number_format($totalKM, 2, ',', '.') . ",KM\n";
        $csv .= "UKUPNO EUR:,," . number_format($totalEUR, 2, ',', '.') . ",EUR\n";
        $csv .= "UKUPNO USD:,," . number_format($totalUSD, 2, ',', '.') . ",USD\n";
        $csv .= "Broj plaćanja:,," . $payments->count() . "\n";

        $filename = 'neplacena_placanja_' . date('d-m-Y_His') . '.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function unpaidExportExcel(Request $request): StreamedResponse
    {
        $locale = TranslationHelper::getUserLocale();
        $query = Payment::with(['supplier:id,name', 'branch:id,name'])
            ->where('status', 'PLANNED');
        
        $today = today();
        $dateFilter = $request->get('date_filter', 'all');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        switch ($dateFilter) {
            case 'today':
                $query->whereDate('planned_date', $today);
                break;
            case 'tomorrow':
                $query->whereDate('planned_date', $today->copy()->addDay());
                break;
            case '3days':
                $query->whereDate('planned_date', '>=', $today)->whereDate('planned_date', '<', $today->copy()->addDays(4));
                break;
            case '7days':
                $query->whereDate('planned_date', '>=', $today)->whereDate('planned_date', '<', $today->copy()->addDays(8));
                break;
            case 'period':
                if ($startDate) $query->whereDate('planned_date', '>=', $startDate);
                if ($endDate) $query->whereDate('planned_date', '<=', $endDate);
                break;
            case 'overdue':
                $query->whereDate('planned_date', '<', $today);
                break;
        }

        if ($request->filled('currency')) $query->where('currency', $request->currency);
        if ($request->filled('supplier_id')) $query->where('supplier_id', $request->supplier_id);
        if ($request->filled('branch_id')) $query->where('branch_id', $request->branch_id);

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('supplier', function ($sq) use ($searchTerm) {
                    $sq->where('name', 'like', '%' . $searchTerm . '%');
                })
                ->orWhereHas('branch', function ($bq) use ($searchTerm) {
                    $bq->where('name', 'like', '%' . $searchTerm . '%');
                })
                ->orWhere('invoice_number', 'like', '%' . $searchTerm . '%')
                ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        $exchangeRates = Setting::getExchangeRates();
        
        $payments = $query->get()
            ->map(function ($payment) use ($exchangeRates) {
                $payment->amount_in_km = $this->convertToKM($payment->amount, $payment->currency, $exchangeRates);
                return $payment;
            })
            ->sortByDesc('amount_in_km')
            ->values();

        $totalKM = $payments->where('currency', 'KM')->sum('amount');
        $totalEUR = $payments->where('currency', 'EUR')->sum('amount');
        $totalUSD = $payments->where('currency', 'USD')->sum('amount');
        
        $hasEurOrUsd = true;
        $grandTotalKM = $payments->sum('amount_in_km');

        $colCount = 9;
        
        $excel = new ExcelExportService();
        
        $excel->setTitle(
            TranslationHelper::trans('unpaid', $locale),
            TranslationHelper::trans('period', $locale) . ': ' . $this->getDateFilterLabel($dateFilter, $startDate, $endDate) . ' | ' . TranslationHelper::trans('date', $locale) . ': ' . now()->format('d.m.Y H:i'),
            $colCount
        );

        $summaryData = [
            TranslationHelper::trans('total_km', $locale) => number_format($totalKM, 2, ',', '.') . ' KM',
            TranslationHelper::trans('total_eur', $locale) => number_format($totalEUR, 2, ',', '.') . ' EUR',
            TranslationHelper::trans('total_usd', $locale) => number_format($totalUSD, 2, ',', '.') . ' USD',
            TranslationHelper::trans('payment_count', $locale) => $payments->count(),
            strtoupper(TranslationHelper::trans('total_km', $locale)) => number_format($grandTotalKM, 2, ',', '.') . ' KM',
        ];

        $excel->setSummaryRow($summaryData, 3, $colCount);

        $headers = [
            TranslationHelper::trans('invoice_number', $locale),
            TranslationHelper::trans('description', $locale),
            TranslationHelper::trans('supplier', $locale),
            TranslationHelper::trans('branch', $locale),
            TranslationHelper::trans('amount', $locale),
            TranslationHelper::trans('currency', $locale),
            TranslationHelper::trans('status', $locale),
            TranslationHelper::trans('pay_by', $locale),
            TranslationHelper::trans('total_km', $locale)
        ];
        $excel->setHeaders($headers, 5);

        $data = [];
        foreach ($payments as $payment) {
            $row = [
                'invoice' => $payment->invoice_number ?? '-',
                'description' => $payment->description ?? '-',
                'supplier' => $payment->supplier->name ?? '-',
                'branch' => $payment->branch->name ?? '-',
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => TranslationHelper::trans('unpaid', $locale),
                'date' => $payment->planned_date->format('d.m.Y'),
                'amount_km' => $payment->amount_in_km,
            ];
            
            $data[] = $row;
        }

        $columnTypes = ['amount' => 'currency', 'amount_km' => 'currency'];

        $excel->setData($data, 6, $columnTypes);
        
        $totalsRow = 6 + count($data);
        $totalsData = ['', '', '', strtoupper(TranslationHelper::trans('total_km', $locale)) . ':', '', '', '', '', $grandTotalKM];
        $excel->setTotalsRow($totalsData, $totalsRow, $colCount);
        
        $excel->getSheet()->getStyle('I' . $totalsRow)->getNumberFormat()->setFormatCode('#,##0.00');
        
        $excel->autoSizeColumns($colCount);

        $filename = $this->getTranslatedFilename('unpaid_payments', $locale) . '_' . date('d-m-Y') . '.xlsx';
        return $excel->download($filename);
    }
}
