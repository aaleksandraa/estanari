<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\Supplier;
use App\Services\ExcelExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $query = Payment::with(['supplier:id,name', 'branch:id,name'])
            ->where('status', 'PAID')
            ->orderBy('paid_date', 'desc');

        // Date filter
        if ($request->filled('paid_date')) {
            $query->whereDate('paid_date', $request->paid_date);
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('branch', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('invoice_number', 'like', "%{$search}%");
            });
        }

        $payments = $query->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name', 'supplier_id']);

        $summary = [
            'totalKM' => $payments->where('currency', 'KM')->sum('amount'),
            'totalEUR' => $payments->where('currency', 'EUR')->sum('amount'),
            'totalUSD' => $payments->where('currency', 'USD')->sum('amount'),
            'count' => $payments->count(),
        ];

        return Inertia::render('Payments', [
            'payments' => $payments,
            'suppliers' => $suppliers,
            'branches' => $branches,
            'summary' => $summary,
            'filters' => $request->only(['currency', 'supplier_id', 'branch_id', 'search', 'paid_date']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'branch_id' => 'required|exists:branches,id',
            'invoice_number' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:KM,EUR,USD',
            'planned_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:PLANNED,PAID',
            'save_description' => 'sometimes|boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['status'] = $validated['status'] ?? 'PLANNED';
        
        if ($validated['status'] === 'PAID') {
            $validated['paid_date'] = now()->toDateString();
            $validated['paid_by'] = auth()->id();
        }

        // Save description if checkbox is checked
        if ($request->boolean('save_description') && !empty($validated['description'])) {
            \App\Models\SavedDescription::updateOrCreate(
                [
                    'supplier_id' => $validated['supplier_id'],
                    'branch_id' => $validated['branch_id'],
                ],
                [
                    'description' => $validated['description'],
                ]
            );
        }

        unset($validated['save_description']);
        $payment = Payment::create($validated);
        AuditLog::log('payments', $payment->id, 'INSERT', null, $payment->toArray());

        return back()->with('success', 'Plaćanje uspješno kreirano.');
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'branch_id' => 'required|exists:branches,id',
            'invoice_number' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:KM,EUR,USD',
            'planned_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
        ]);

        $oldData = $payment->toArray();
        $payment->update($validated);
        AuditLog::log('payments', $payment->id, 'UPDATE', $oldData, $payment->fresh()->toArray());

        return back()->with('success', 'Plaćanje uspješno ažurirano.');
    }

    public function markAsPaid(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'paid_date' => 'nullable|date',
        ]);

        $oldData = $payment->toArray();
        $payment->markAsPaid(auth()->id(), $validated['paid_date'] ?? null);
        AuditLog::log('payments', $payment->id, 'UPDATE', $oldData, $payment->fresh()->toArray());

        return back()->with('success', 'Plaćanje označeno kao plaćeno.');
    }

    public function batchMarkAsPaid(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:payments,id',
            'paid_date' => 'nullable|date',
        ]);

        $payments = Payment::whereIn('id', $validated['ids'])->planned()->get();
        
        foreach ($payments as $payment) {
            $oldData = $payment->toArray();
            $payment->markAsPaid(auth()->id(), $validated['paid_date'] ?? null);
            AuditLog::log('payments', $payment->id, 'UPDATE', $oldData, $payment->fresh()->toArray());
        }

        return back()->with('success', count($payments) . ' plaćanja označeno kao plaćeno.');
    }

    public function markAsUnpaid(Payment $payment): RedirectResponse
    {
        $oldData = $payment->toArray();
        $payment->update([
            'status' => 'PLANNED',
            'paid_date' => null,
            'paid_by' => null,
        ]);
        AuditLog::log('payments', $payment->id, 'UPDATE', $oldData, $payment->fresh()->toArray());

        return back()->with('success', 'Plaćanje vraćeno u neplaćeno.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $oldData = $payment->toArray();
        AuditLog::log('payments', $payment->id, 'DELETE', $oldData, null);
        $payment->delete();

        return back()->with('success', 'Plaćanje uspješno obrisano.');
    }

    public function export(Request $request): Response
    {
        $query = Payment::with(['supplier:id,name', 'branch:id,name'])
            ->orderBy('planned_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('paid_date')) {
            $query->whereDate('paid_date', $request->paid_date);
        }
        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $payments = $query->get();

        $csv = "Dobavljač,Poslovnica,Broj fakture,Iznos,Valuta,Status,Planirani datum,Datum plaćanja,Opis\n";
        
        foreach ($payments as $payment) {
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",%.2f,%s,%s,%s,%s,\"%s\"\n",
                $payment->supplier->name ?? '',
                $payment->branch->name ?? '',
                $payment->invoice_number ?? '',
                $payment->amount,
                $payment->currency,
                $payment->status === 'PAID' ? 'Plaćeno' : 'Neplaceno',
                $payment->planned_date->format('d.m.Y'),
                $payment->paid_date?->format('d.m.Y') ?? '',
                $payment->description ?? ''
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="placanja_' . date('d-m-Y') . '.csv"');
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $query = Payment::with(['supplier:id,name', 'branch:id,name'])
            ->orderBy('planned_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('paid_date')) {
            $query->whereDate('paid_date', $request->paid_date);
        }
        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $payments = $query->get();

        $totalKM = $payments->where('currency', 'KM')->sum('amount');
        $totalEUR = $payments->where('currency', 'EUR')->sum('amount');
        $totalUSD = $payments->where('currency', 'USD')->sum('amount');

        $excel = new ExcelExportService();
        
        $excel->setTitle(
            'Sva plaćanja',
            'Exportovano: ' . now()->format('d.m.Y H:i'),
            8
        );

        $excel->setSummaryRow([
            'Ukupno KM' => number_format($totalKM, 2, ',', '.') . ' KM',
            'Ukupno EUR' => number_format($totalEUR, 2, ',', '.') . ' EUR',
            'Ukupno USD' => number_format($totalUSD, 2, ',', '.') . ' USD',
            'Broj stavki' => $payments->count(),
        ], 3, 8);

        $excel->setHeaders(['Br. fakture', 'Dobavljač', 'Poslovnica', 'Iznos', 'Valuta', 'Status', 'Planirani datum', 'Datum plaćanja'], 5);

        $data = [];
        foreach ($payments as $payment) {
            $data[] = [
                'invoice' => $payment->invoice_number ?? '-',
                'supplier' => $payment->supplier->name ?? '-',
                'branch' => $payment->branch->name ?? '-',
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status === 'PAID' ? 'Plaćeno' : 'Neplaceno',
                'planned_date' => $payment->planned_date->format('d.m.Y'),
                'paid_date' => $payment->paid_date?->format('d.m.Y') ?? '-',
            ];
        }

        $excel->setData($data, 6, ['amount' => 'currency']);
        $excel->autoSizeColumns(8);

        $filename = 'placanja_' . date('d-m-Y') . '.xlsx';
        return $excel->download($filename);
    }

    public function getSavedDescription($supplierId, $branchId)
    {
        $savedDescription = \App\Models\SavedDescription::where('supplier_id', $supplierId)
            ->where('branch_id', $branchId)
            ->first();

        return response()->json([
            'description' => $savedDescription?->description ?? '',
        ]);
    }
}
