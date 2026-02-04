<?php

namespace App\Http\Controllers;

use App\Helpers\TranslationHelper;
use App\Models\Payment;
use App\Models\PaymentPlan;
use App\Models\Setting;
use App\Services\ExcelExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentPlanController extends Controller
{
    public function index(): InertiaResponse
    {
        $plans = PaymentPlan::with('creator:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Plans', [
            'plans' => $plans,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'date_filter' => 'required|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'filters' => 'nullable|array',
            'payment_ids' => 'required|array|min:1',
            'total_km' => 'required|numeric',
            'total_eur' => 'required|numeric',
            'total_usd' => 'nullable|numeric',
        ]);

        $validated['payment_count'] = count($validated['payment_ids']);
        $validated['created_by'] = auth()->id();

        PaymentPlan::create($validated);

        return back()->with('success', 'Plan uspješno spremljen.');
    }

    public function update(Request $request, PaymentPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $plan->update($validated);

        return back()->with('success', 'Plan uspješno ažuriran.');
    }

    public function show(PaymentPlan $plan): InertiaResponse
    {
        $exchangeRates = Setting::getExchangeRates();
        
        $payments = Payment::with(['supplier:id,name', 'branch:id,name'])
            ->whereIn('id', $plan->payment_ids ?? [])
            ->get()
            ->map(function ($payment) use ($exchangeRates) {
                $payment->amount_in_km = $this->convertToKM($payment->amount, $payment->currency, $exchangeRates);
                return $payment;
            })
            ->sortByDesc('amount_in_km')
            ->values();

        // Get available payments (not in this plan, status PLANNED)
        $availablePayments = Payment::with(['supplier:id,name', 'branch:id,name'])
            ->whereNotIn('id', $plan->payment_ids ?? [])
            ->where('status', 'PLANNED')
            ->orderBy('planned_date')
            ->get();

        return Inertia::render('PlanDetail', [
            'plan' => $plan->load('creator:id,name'),
            'payments' => $payments,
            'availablePayments' => $availablePayments,
            'exchangeRates' => $exchangeRates,
        ]);
    }

    private function convertToKM(float $amount, string $currency, array $rates): float
    {
        return match ($currency) {
            'EUR' => $amount * $rates['EUR'],
            'USD' => $amount * $rates['USD'],
            default => $amount,
        };
    }

    public function destroy(PaymentPlan $plan): RedirectResponse
    {
        $plan->delete();
        return back()->with('success', 'Plan uspješno obrisan.');
    }

    public function markAsPaid(Request $request, PaymentPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'paid_date' => 'nullable|date',
        ]);

        $payments = Payment::whereIn('id', $plan->payment_ids ?? [])
            ->where('status', 'PLANNED')
            ->get();

        $paidCount = 0;
        foreach ($payments as $payment) {
            $payment->markAsPaid(auth()->id(), $validated['paid_date'] ?? null);
            $paidCount++;
        }

        // Update plan status
        $plan->update(['is_paid' => true, 'paid_at' => now()]);

        return back()->with('success', "Plan označen kao plaćen. Ukupno {$paidCount} plaćanja označeno.");
    }

    public function addPayment(Request $request, PaymentPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:payments,id',
        ]);

        $payment = Payment::findOrFail($validated['payment_id']);
        
        $paymentIds = $plan->payment_ids ?? [];
        if (!in_array($payment->id, $paymentIds)) {
            $paymentIds[] = $payment->id;
            
            // Recalculate totals
            $totalKm = $plan->total_km;
            $totalEur = $plan->total_eur;
            $totalUsd = $plan->total_usd ?? 0;
            
            if ($payment->currency === 'KM') $totalKm += $payment->amount;
            elseif ($payment->currency === 'EUR') $totalEur += $payment->amount;
            else $totalUsd += $payment->amount;
            
            $plan->update([
                'payment_ids' => $paymentIds,
                'payment_count' => count($paymentIds),
                'total_km' => $totalKm,
                'total_eur' => $totalEur,
                'total_usd' => $totalUsd,
            ]);
        }

        return back()->with('success', 'Plaćanje dodano u plan.');
    }

    public function addCustomItem(Request $request, PaymentPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:KM,EUR,USD',
            'planned_date' => 'required|date',
        ]);

        // Create a new payment without supplier/branch (custom item)
        $payment = Payment::create([
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'planned_date' => $validated['planned_date'],
            'status' => 'PLANNED',
            'created_by' => auth()->id(),
            'supplier_id' => null,
            'branch_id' => null,
        ]);

        // Add to plan
        $paymentIds = $plan->payment_ids ?? [];
        $paymentIds[] = $payment->id;
        
        // Recalculate totals
        $totalKm = $plan->total_km;
        $totalEur = $plan->total_eur;
        $totalUsd = $plan->total_usd ?? 0;
        
        if ($payment->currency === 'KM') $totalKm += $payment->amount;
        elseif ($payment->currency === 'EUR') $totalEur += $payment->amount;
        else $totalUsd += $payment->amount;
        
        $plan->update([
            'payment_ids' => $paymentIds,
            'payment_count' => count($paymentIds),
            'total_km' => $totalKm,
            'total_eur' => $totalEur,
            'total_usd' => $totalUsd,
        ]);

        return back()->with('success', 'Custom stavka dodana u plan.');
    }

    public function removePayment(PaymentPlan $plan, Payment $payment): RedirectResponse
    {
        $paymentIds = $plan->payment_ids ?? [];
        
        if (in_array($payment->id, $paymentIds)) {
            $paymentIds = array_values(array_filter($paymentIds, fn($id) => $id !== $payment->id));
            
            // Recalculate totals
            $totalKm = $plan->total_km;
            $totalEur = $plan->total_eur;
            $totalUsd = $plan->total_usd ?? 0;
            
            if ($payment->currency === 'KM') $totalKm -= $payment->amount;
            elseif ($payment->currency === 'EUR') $totalEur -= $payment->amount;
            else $totalUsd -= $payment->amount;
            
            $plan->update([
                'payment_ids' => $paymentIds,
                'payment_count' => count($paymentIds),
                'total_km' => max(0, $totalKm),
                'total_eur' => max(0, $totalEur),
                'total_usd' => max(0, $totalUsd),
            ]);
        }

        return back()->with('success', 'Stavka uklonjena iz plana.');
    }

    public function exportCsv(PaymentPlan $plan): Response
    {
        $payments = Payment::with(['supplier:id,name', 'branch:id,name'])
            ->whereIn('id', $plan->payment_ids ?? [])
            ->orderBy('planned_date')
            ->get();

        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
        $csv .= "PLAN PLAĆANJA: {$plan->name}\n";
        $csv .= "Kreiran: " . $plan->created_at->format('d.m.Y H:i') . "\n";
        if ($plan->description) {
            $csv .= "Opis: {$plan->description}\n";
        }
        $csv .= "\n";
        $csv .= "Br. fakture;Dobavljač;Opis;Poslovnica;Iznos;Valuta;Status;Planirani datum\n";

        foreach ($payments as $payment) {
            $csv .= sprintf(
                "%s;%s;%s;%s;%s;%s;%s;%s\n",
                $payment->invoice_number ?? '',
                $payment->supplier->name ?? ($payment->description ?? ''),
                $payment->supplier ? ($payment->description ?? '') : '',
                $payment->branch->name ?? '',
                number_format($payment->amount, 2, ',', '.'),
                $payment->currency,
                $payment->status === 'PAID' ? 'Plaćeno' : 'Neplaćeno',
                $payment->planned_date->format('d.m.Y')
            );
        }

        $csv .= "\n";
        $csv .= "UKUPNO KM:;;" . number_format($plan->total_km, 2, ',', '.') . ";KM\n";
        $csv .= "UKUPNO EUR:;;" . number_format($plan->total_eur, 2, ',', '.') . ";EUR\n";
        $csv .= "UKUPNO USD:;;" . number_format($plan->total_usd ?? 0, 2, ',', '.') . ";USD\n";
        $csv .= "Broj plaćanja:;;" . $plan->payment_count . "\n";

        $filename = \Str::slug(str_replace('.', '-', $plan->name)) . '.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function exportPdf(PaymentPlan $plan): Response
    {
        $exchangeRates = Setting::getExchangeRates();
        
        $payments = Payment::with(['supplier:id,name', 'branch:id,name'])
            ->whereIn('id', $plan->payment_ids ?? [])
            ->get()
            ->map(function ($payment) use ($exchangeRates) {
                $payment->amount_in_km = $this->convertToKM($payment->amount, $payment->currency, $exchangeRates);
                return $payment;
            })
            ->sortByDesc('amount_in_km')
            ->values();

        $html = $this->generatePdfHtml($plan, $payments, $exchangeRates);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . \Str::slug(str_replace('.', '-', $plan->name)) . '.html"');
    }

    public function exportExcel(PaymentPlan $plan): StreamedResponse
    {
        $locale = TranslationHelper::getUserLocale();
        $exchangeRates = Setting::getExchangeRates();
        
        $payments = Payment::with(['supplier:id,name', 'branch:id,name'])
            ->whereIn('id', $plan->payment_ids ?? [])
            ->get()
            ->map(function ($payment) use ($exchangeRates) {
                $payment->amount_in_km = $this->convertToKM($payment->amount, $payment->currency, $exchangeRates);
                return $payment;
            })
            ->sortByDesc('amount_in_km')
            ->values();

        $hasEurOrUsd = true;
        
        $excel = new ExcelExportService();
        
        $grandTotalKM = $payments->sum('amount_in_km');
        
        $colCount = 9;
        
        $excel->setTitle(
            TranslationHelper::trans('payment_plan', $locale) . ": {$plan->name}",
            TranslationHelper::trans('created_at', $locale) . ": " . $plan->created_at->format('d.m.Y H:i') . ($plan->description ? " | {$plan->description}" : ''),
            $colCount
        );

        $summaryData = [
            TranslationHelper::trans('total_km', $locale) => number_format($plan->total_km, 2, ',', '.') . ' KM',
            TranslationHelper::trans('total_eur', $locale) => number_format($plan->total_eur, 2, ',', '.') . ' EUR',
            TranslationHelper::trans('total_usd', $locale) => number_format($plan->total_usd ?? 0, 2, ',', '.') . ' USD',
            TranslationHelper::trans('payment_count', $locale) => $plan->payment_count,
            strtoupper(TranslationHelper::trans('total_km', $locale)) => number_format($grandTotalKM, 2, ',', '.') . ' KM',
        ];

        $excel->setSummaryRow($summaryData, 3, $colCount);

        $headers = [
            TranslationHelper::trans('invoice_number', $locale),
            TranslationHelper::trans('supplier', $locale),
            TranslationHelper::trans('description', $locale),
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
                'supplier' => $payment->supplier->name ?? ($payment->description ?? '-'),
                'description' => $payment->description ?? '-',
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

        $filename = \Str::slug(str_replace('.', '-', $plan->name)) . '.xlsx';
        return $excel->download($filename);
    }

    private function getTranslatedFilename(string $key, string $locale): string
    {
        $filenames = [
            'bs' => ['plan' => 'plan'],
            'de' => ['plan' => 'plan'],
            'en' => ['plan' => 'plan'],
        ];
        
        return $filenames[$locale][$key] ?? $filenames['en'][$key] ?? $key;
    }

    private function generatePdfHtml(PaymentPlan $plan, $payments, array $exchangeRates): string
    {
        $grandTotalKM = $payments->sum('amount_in_km');
        
        // Determine overall plan status
        $allPaid = $payments->every(fn($p) => $p->status === 'PAID');
        $planStatus = $allPaid ? 'Plaćeno' : 'Neplaćeno';
        $statusClass = $allPaid ? 'status-paid' : 'status-planned';
        
        // Check if we need landscape mode (if any description is longer than 50 chars)
        $needsLandscape = $payments->contains(fn($p) => strlen($p->description ?? '') > 50);
        $orientation = $needsLandscape ? 'landscape' : 'portrait';
        $fontSize = '10px'; // Standard 10px as requested
        
        $rows = '';
        foreach ($payments as $payment) {
            $amountClass = $payment->currency === 'KM' ? 'amount-km' : ($payment->currency === 'EUR' ? 'amount-eur' : 'amount-usd');
            $formattedAmount = number_format($payment->amount, 2, ',', '.');
            $formattedAmountKM = number_format($payment->amount_in_km, 2, ',', '.'); // Without KM suffix
            $formattedDate = $payment->planned_date->format('d.m.Y');
            $supplierName = $payment->supplier->name ?? '-';
            $branchName = $payment->branch->name ?? '-';
            $invoiceNumber = $payment->invoice_number ?? '-';
            $description = $payment->description ?? '-';
            
            $rows .= "<tr>
                <td class=\"invoice\">{$invoiceNumber}</td>
                <td>{$supplierName}</td>
                <td class=\"description\">{$description}</td>
                <td>{$branchName}</td>
                <td class=\"{$amountClass}\">{$formattedAmount} {$payment->currency}</td>
                <td>{$formattedDate}</td>
                <td class=\"amount-km\">{$formattedAmountKM}</td>
            </tr>";
        }

        $totalKmFormatted = number_format($plan->total_km, 2, ',', '.');
        $totalEurFormatted = number_format($plan->total_eur, 2, ',', '.');
        $totalUsdFormatted = number_format($plan->total_usd ?? 0, 2, ',', '.');
        $grandTotalFormatted = number_format($grandTotalKM, 2, ',', '.');
        $createdAtFormatted = $plan->created_at->format('d.m.Y H:i');
        $exportDateFormatted = $plan->created_at->format('d.m.Y');
        $planDescription = $plan->description ?? '';
        $descriptionText = $planDescription ? " | {$planDescription}" : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="bs">
<head>
    <meta charset="UTF-8">
    <title>Plan plaćanja - {$plan->name}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 10px; background: #f8fafc; font-size: {$fontSize}; }
        .container { max-width: 100%; margin: 0 auto; background: white; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; padding: 10px 12px; }
        .header h1 { font-size: 14px; margin-bottom: 2px; }
        .header p { opacity: 0.9; font-size: 9px; line-height: 1.3; }
        .header .status-badge { display: inline-block; padding: 2px 6px; border-radius: 8px; font-size: 9px; font-weight: 500; margin-top: 3px; }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-planned { background: #fef3c7; color: #92400e; }
        .meta { display: flex; gap: 8px; padding: 8px 12px; background: #f1f5f9; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; }
        .meta-item { flex: 1; min-width: 80px; }
        .meta-item label { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; display: block; }
        .meta-item value { display: block; font-size: 11px; font-weight: 600; color: #1e293b; margin-top: 1px; }
        .meta-item.km value { color: #3b82f6; }
        .meta-item.eur value { color: #10b981; }
        .meta-item.usd value { color: #9333ea; }
        .content { padding: 8px 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; table-layout: fixed; }
        th { background: #f8fafc; padding: 5px 4px; text-align: left; font-size: 8px; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0; font-weight: 600; }
        td { padding: 5px 4px; border-bottom: 1px solid #f1f5f9; word-wrap: break-word; overflow-wrap: break-word; vertical-align: top; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        tbody tr:nth-child(odd) { background: #ffffff; }
        tr:hover { background: #f1f5f9 !important; }
        .amount-km { color: #3b82f6; font-weight: 600; text-align: right; }
        .amount-eur { color: #10b981; font-weight: 600; text-align: right; }
        .amount-usd { color: #9333ea; font-weight: 600; text-align: right; }
        .invoice { font-family: 'Courier New', monospace; color: #374151; font-size: 8px; width: 10%; }
        .description { width: 22%; font-size: 8px; color: #4b5563; }
        .supplier { width: 20%; }
        .branch { width: 18%; }
        .amount { width: 12%; text-align: right; }
        .date { width: 8%; text-align: center; font-size: 8px; }
        .total { width: 10%; white-space: nowrap; }
        .footer { padding: 8px 12px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center; color: #64748b; font-size: 8px; }
        @media print { 
            body { padding: 5px; background: white; } 
            .container { box-shadow: none; border: 1px solid #e2e8f0; } 
            @page { size: A4 {$orientation}; margin: 8mm; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$plan->name}</h1>
            <p>Kreiran: {$createdAtFormatted}{$descriptionText}</p>
            <span class="status-badge {$statusClass}">Status: {$planStatus}</span>
        </div>
        <div class="meta">
            <div class="meta-item km">
                <label>Ukupno KM</label>
                <value>{$totalKmFormatted} KM</value>
            </div>
            <div class="meta-item eur">
                <label>Ukupno EUR</label>
                <value>{$totalEurFormatted} EUR</value>
            </div>
            <div class="meta-item usd">
                <label>Ukupno USD</label>
                <value>{$totalUsdFormatted} USD</value>
            </div>
            <div class="meta-item">
                <label>Broj plaćanja</label>
                <value>{$plan->payment_count}</value>
            </div>
            <div class="meta-item km">
                <label>UKUPNO (KM)</label>
                <value>{$grandTotalFormatted} KM</value>
            </div>
        </div>
        <div class="content">
            <table>
                <thead>
                    <tr>
                        <th class="invoice">Br. fakture</th>
                        <th class="supplier">Dobavljač</th>
                        <th class="description">Opis</th>
                        <th class="branch">Poslovnica</th>
                        <th class="amount">Iznos</th>
                        <th class="date">Datum</th>
                        <th class="total">Ukupno KM</th>
                    </tr>
                </thead>
                <tbody>
                    {$rows}
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafc; border-top: 2px solid #e2e8f0; font-weight: 600;">
                        <td colspan="4" style="text-align: right; padding: 6px 4px;">UKUPNO:</td>
                        <td></td>
                        <td></td>
                        <td class="amount-km" style="font-size: 11px; white-space: nowrap;">{$grandTotalFormatted} KM</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="footer">
            WizFlussi - Plan plaćanja exportovan {$exportDateFormatted}
        </div>
    </div>
    <script>window.print();</script>
</body>
</html>
HTML;
    }
}
