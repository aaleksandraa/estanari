<?php

namespace App\Http\Controllers;

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

        $payments = $query->get();

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

        return Inertia::render('Dashboard', [
            'payments' => $payments,
            'stats' => $stats,
            'suppliers' => $suppliers,
            'branches' => $branches,
            'filters' => [
                'date_filter' => $dateFilter,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $request->status,
                'currency' => $request->currency,
                'supplier_id' => $request->supplier_id,
                'branch_id' => $request->branch_id,
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
        
        // Check if there are any EUR or USD payments
        $hasEurOrUsd = $payments->contains(fn($p) => in_array($p->currency, ['EUR', 'USD']));
        $grandTotalKM = $payments->sum('amount_in_km');

        $colCount = $hasEurOrUsd ? 8 : 7;
        
        $excel = new ExcelExportService();
        
        $excel->setTitle(
            'Pregled plaćanja',
            'Period: ' . $this->getDateFilterLabel($dateFilter, $startDate, $endDate) . ' | Exportovano: ' . now()->format('d.m.Y H:i'),
            $colCount
        );

        $summaryData = [
            'Ukupno KM' => number_format($totalKM, 2, ',', '.') . ' KM',
            'Ukupno EUR' => number_format($totalEUR, 2, ',', '.') . ' EUR',
            'Ukupno USD' => number_format($totalUSD, 2, ',', '.') . ' USD',
            'Broj stavki' => $payments->count(),
        ];
        
        if ($hasEurOrUsd) {
            $summaryData['UKUPNO (KM)'] = number_format($grandTotalKM, 2, ',', '.') . ' KM';
        }

        $excel->setSummaryRow($summaryData, 3, $colCount);

        $headers = ['Br. fakture', 'Dobavljač', 'Poslovnica', 'Iznos', 'Valuta', 'Status', 'Datum'];
        if ($hasEurOrUsd) {
            $headers[] = 'Ukupno KM';
        }
        $excel->setHeaders($headers, 5);

        $data = [];
        foreach ($payments as $payment) {
            $row = [
                'invoice' => $payment->invoice_number ?? '-',
                'supplier' => $payment->supplier->name ?? '-',
                'branch' => $payment->branch->name ?? '-',
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status === 'PAID' ? 'Plaćeno' : 'Neplaceno',
                'date' => $payment->planned_date->format('d.m.Y'),
            ];
            
            if ($hasEurOrUsd) {
                $row['amount_km'] = $payment->amount_in_km;
            }
            
            $data[] = $row;
        }

        $columnTypes = ['amount' => 'currency'];
        if ($hasEurOrUsd) {
            $columnTypes['amount_km'] = 'currency';
        }

        $excel->setData($data, 6, $columnTypes);
        
        // Add totals row at the bottom
        $totalsRow = 6 + count($data);
        $totalsData = ['', '', 'UKUPNO:', '', '', '', ''];
        if ($hasEurOrUsd) {
            $totalsData[] = $grandTotalKM;
        }
        $excel->setTotalsRow($totalsData, $totalsRow, $colCount);
        
        // Format the total amount cell
        if ($hasEurOrUsd) {
            $excel->getSheet()->getStyle('H' . $totalsRow)->getNumberFormat()->setFormatCode('#,##0.00');
        }
        
        $excel->autoSizeColumns($colCount);

        $filename = 'pregled_placanja_' . date('d-m-Y') . '.xlsx';
        return $excel->download($filename);
    }

    private function convertToKM(float $amount, string $currency, array $rates): float
    {
        return match ($currency) {
            'EUR' => $amount * $rates['EUR'],
            'USD' => $amount * $rates['USD'],
            default => $amount,
        };
    }
}
