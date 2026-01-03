<?php

namespace App\Http\Controllers;

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

    public function markAsPaid(PaymentPlan $plan): RedirectResponse
    {
        $payments = Payment::whereIn('id', $plan->payment_ids ?? [])
            ->where('status', 'PLANNED')
            ->get();

        $paidCount = 0;
        foreach ($payments as $payment) {
            $payment->markAsPaid(auth()->id());
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
        $csv .= "Br. fakture;Dobavljač;Poslovnica;Iznos;Valuta;Status;Planirani datum;Opis\n";

        foreach ($payments as $payment) {
            $csv .= sprintf(
                "%s;%s;%s;%s;%s;%s;%s;%s\n",
                $payment->invoice_number ?? '',
                $payment->supplier->name ?? '',
                $payment->branch->name ?? '',
                number_format($payment->amount, 2, ',', '.'),
                $payment->currency,
                $payment->status === 'PAID' ? 'Plaćeno' : 'Neplaceno',
                $payment->planned_date->format('d.m.Y'),
                $payment->description ?? ''
            );
        }

        $csv .= "\n";
        $csv .= "UKUPNO KM:;;" . number_format($plan->total_km, 2, ',', '.') . ";KM\n";
        $csv .= "UKUPNO EUR:;;" . number_format($plan->total_eur, 2, ',', '.') . ";EUR\n";
        $csv .= "UKUPNO USD:;;" . number_format($plan->total_usd ?? 0, 2, ',', '.') . ";USD\n";
        $csv .= "Broj plaćanja:;;" . $plan->payment_count . "\n";

        $filename = 'plan_' . \Str::slug($plan->name) . '_' . date('d-m-Y') . '.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function exportPdf(PaymentPlan $plan): Response
    {
        $payments = Payment::with(['supplier:id,name', 'branch:id,name'])
            ->whereIn('id', $plan->payment_ids ?? [])
            ->orderBy('planned_date')
            ->get();

        $html = $this->generatePdfHtml($plan, $payments);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="plan_' . \Str::slug($plan->name) . '_' . date('d-m-Y') . '.html"');
    }

    public function exportExcel(PaymentPlan $plan): StreamedResponse
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

        // Check if there are any EUR or USD payments
        $hasEurOrUsd = $payments->contains(fn($p) => in_array($p->currency, ['EUR', 'USD']));
        
        $excel = new ExcelExportService();
        
        // Calculate grand total in KM
        $grandTotalKM = $payments->sum('amount_in_km');
        
        $colCount = $hasEurOrUsd ? 8 : 7;
        
        $excel->setTitle(
            "Plan plaćanja: {$plan->name}",
            "Kreiran: " . $plan->created_at->format('d.m.Y H:i') . ($plan->description ? " | {$plan->description}" : ''),
            $colCount
        );

        $summaryData = [
            'Ukupno KM' => number_format($plan->total_km, 2, ',', '.') . ' KM',
            'Ukupno EUR' => number_format($plan->total_eur, 2, ',', '.') . ' EUR',
            'Ukupno USD' => number_format($plan->total_usd ?? 0, 2, ',', '.') . ' USD',
            'Broj stavki' => $plan->payment_count,
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
                'supplier' => $payment->supplier->name ?? 'Custom stavka',
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
            $totalColLetter = $excel->getSheet()->getCell('H' . $totalsRow);
            $excel->getSheet()->getStyle('H' . $totalsRow)->getNumberFormat()->setFormatCode('#,##0.00');
        }
        
        $excel->autoSizeColumns($colCount);

        $filename = 'plan_' . \Str::slug($plan->name) . '_' . date('d-m-Y') . '.xlsx';
        return $excel->download($filename);
    }

    private function generatePdfHtml(PaymentPlan $plan, $payments): string
    {
        $rows = '';
        foreach ($payments as $payment) {
            $statusClass = $payment->status === 'PAID' ? 'status-paid' : 'status-planned';
            $statusText = $payment->status === 'PAID' ? 'Plaćeno' : 'Neplaceno';
            $amountClass = $payment->currency === 'KM' ? 'amount-km' : ($payment->currency === 'EUR' ? 'amount-eur' : 'amount-usd');
            $formattedAmount = number_format($payment->amount, 2, ',', '.');
            $formattedDate = $payment->planned_date->format('d.m.Y');
            $supplierName = $payment->supplier->name ?? 'N/A';
            $branchName = $payment->branch->name ?? 'N/A';
            $invoiceNumber = $payment->invoice_number ?? '-';
            
            $rows .= "<tr>
                <td class=\"invoice\">{$invoiceNumber}</td>
                <td>{$supplierName}</td>
                <td>{$branchName}</td>
                <td class=\"{$amountClass}\">{$formattedAmount} {$payment->currency}</td>
                <td><span class=\"status {$statusClass}\">{$statusText}</span></td>
                <td>{$formattedDate}</td>
            </tr>";
        }

        $totalKmFormatted = number_format($plan->total_km, 2, ',', '.');
        $totalEurFormatted = number_format($plan->total_eur, 2, ',', '.');
        $totalUsdFormatted = number_format($plan->total_usd ?? 0, 2, ',', '.');
        $createdAtFormatted = $plan->created_at->format('d.m.Y H:i');
        $exportDateFormatted = $plan->created_at->format('d.m.Y');
        $description = $plan->description ?? '';

        return <<<HTML
<!DOCTYPE html>
<html lang="bs">
<head>
    <meta charset="UTF-8">
    <title>Plan plaćanja - {$plan->name}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f8fafc; font-size: 11px; }
        .container { max-width: 100%; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; padding: 16px 20px; }
        .header h1 { font-size: 16px; margin-bottom: 4px; }
        .header p { opacity: 0.9; font-size: 10px; }
        .meta { display: flex; gap: 20px; padding: 12px 20px; background: #f1f5f9; border-bottom: 1px solid #e2e8f0; }
        .meta-item label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .meta-item value { display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-top: 2px; }
        .meta-item.km value { color: #3b82f6; }
        .meta-item.eur value { color: #10b981; }
        .meta-item.usd value { color: #9333ea; }
        .content { padding: 16px 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { background: #f8fafc; padding: 8px 10px; text-align: left; font-size: 9px; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0; }
        td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; white-space: nowrap; }
        tr:hover { background: #f8fafc; }
        .amount-km { color: #3b82f6; font-weight: 600; }
        .amount-eur { color: #10b981; font-weight: 600; }
        .amount-usd { color: #9333ea; font-weight: 600; }
        .invoice { font-family: monospace; color: #374151; }
        .status { padding: 2px 6px; border-radius: 10px; font-size: 9px; font-weight: 500; }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-planned { background: #fef3c7; color: #92400e; }
        .footer { padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center; color: #64748b; font-size: 9px; }
        @media print { 
            body { padding: 10px; background: white; } 
            .container { box-shadow: none; border: 1px solid #e2e8f0; } 
            @page { size: A4 portrait; margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$plan->name}</h1>
            <p>Kreiran: {$createdAtFormatted} | {$description}</p>
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
        </div>
        <div class="content">
            <table>
                <thead>
                    <tr>
                        <th>Br. fakture</th>
                        <th>Dobavljač</th>
                        <th>Poslovnica</th>
                        <th>Iznos</th>
                        <th>Status</th>
                        <th>Datum</th>
                    </tr>
                </thead>
                <tbody>
                    {$rows}
                </tbody>
            </table>
        </div>
        <div class="footer">
            e-Stanari - Plan plaćanja exportovan {$exportDateFormatted}
        </div>
    </div>
    <script>window.print();</script>
</body>
</html>
HTML;
    }
}
