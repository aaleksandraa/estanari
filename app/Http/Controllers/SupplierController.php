<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Supplier;
use App\Services\ExcelExportService;
use App\Services\ExcelImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $query = Supplier::with('branches')->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Suppliers', [
            'suppliers' => $query->get(),
            'search' => $request->search,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'branches' => 'nullable|array',
            'branches.*.name' => 'required|string|max:255',
            'branches.*.address' => 'nullable|string|max:500',
            'branches.*.is_active' => 'boolean',
        ]);

        DB::transaction(function () use ($validated) {
            $supplier = Supplier::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
            ]);
            
            AuditLog::log('suppliers', $supplier->id, 'INSERT', null, $supplier->toArray());

            // Create branches if provided
            if (!empty($validated['branches'])) {
                foreach ($validated['branches'] as $branchData) {
                    $branch = Branch::create([
                        'supplier_id' => $supplier->id,
                        'name' => $branchData['name'],
                        'address' => $branchData['address'] ?? null,
                        'is_active' => $branchData['is_active'] ?? true,
                    ]);
                    
                    AuditLog::log('branches', $branch->id, 'INSERT', null, $branch->toArray());
                }
            }
        });

        return back()->with('success', 'Dobavljač uspješno kreiran.');
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
            'branches' => 'nullable|array',
            'branches.*.id' => 'nullable|integer|exists:branches,id',
            'branches.*.name' => 'required|string|max:255',
            'branches.*.address' => 'nullable|string|max:500',
            'branches.*.is_active' => 'boolean',
            'branches.*._isNew' => 'boolean',
            'branchesToDelete' => 'nullable|array',
            'branchesToDelete.*' => 'integer|exists:branches,id',
        ]);

        DB::transaction(function () use ($validated, $supplier) {
            $oldData = $supplier->toArray();
            
            // Update supplier
            $supplier->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'is_active' => $validated['is_active'] ?? $supplier->is_active,
            ]);
            
            AuditLog::log('suppliers', $supplier->id, 'UPDATE', $oldData, $supplier->fresh()->toArray());

            // Handle branch deletions
            if (!empty($validated['branchesToDelete'])) {
                foreach ($validated['branchesToDelete'] as $branchId) {
                    $branch = Branch::find($branchId);
                    if ($branch && $branch->supplier_id === $supplier->id) {
                        AuditLog::log('branches', $branch->id, 'DELETE', $branch->toArray(), null);
                        $branch->delete();
                    }
                }
            }

            // Handle branch updates and creations
            if (!empty($validated['branches'])) {
                foreach ($validated['branches'] as $branchData) {
                    if (!empty($branchData['_isNew']) && $branchData['_isNew']) {
                        // Create new branch
                        $branch = Branch::create([
                            'supplier_id' => $supplier->id,
                            'name' => $branchData['name'],
                            'address' => $branchData['address'] ?? null,
                            'is_active' => $branchData['is_active'] ?? true,
                        ]);
                        
                        AuditLog::log('branches', $branch->id, 'INSERT', null, $branch->toArray());
                    } elseif (!empty($branchData['id'])) {
                        // Update existing branch
                        $branch = Branch::find($branchData['id']);
                        if ($branch && $branch->supplier_id === $supplier->id) {
                            $oldBranchData = $branch->toArray();
                            $branch->update([
                                'name' => $branchData['name'],
                                'address' => $branchData['address'] ?? null,
                                'is_active' => $branchData['is_active'] ?? true,
                            ]);
                            
                            AuditLog::log('branches', $branch->id, 'UPDATE', $oldBranchData, $branch->fresh()->toArray());
                        }
                    }
                }
            }
        });

        return back()->with('success', 'Dobavljač uspješno ažuriran.');
    }

    public function deactivate(Supplier $supplier): RedirectResponse
    {
        $oldData = $supplier->toArray();
        $supplier->update(['is_active' => false]);
        AuditLog::log('suppliers', $supplier->id, 'UPDATE', $oldData, $supplier->fresh()->toArray());

        return back()->with('success', 'Dobavljač deaktiviran.');
    }

    public function activate(Supplier $supplier): RedirectResponse
    {
        $oldData = $supplier->toArray();
        $supplier->update(['is_active' => true]);
        AuditLog::log('suppliers', $supplier->id, 'UPDATE', $oldData, $supplier->fresh()->toArray());

        return back()->with('success', 'Dobavljač aktiviran.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        // Check if supplier has payments
        if ($supplier->payments()->exists()) {
            return back()->with('error', 'Nije moguće obrisati dobavljača koji ima plaćanja. Deaktivirajte ga umjesto toga.');
        }

        $oldData = $supplier->toArray();
        
        // Delete all branches first
        foreach ($supplier->branches as $branch) {
            AuditLog::log('branches', $branch->id, 'DELETE', $branch->toArray(), null);
        }
        $supplier->branches()->delete();
        
        AuditLog::log('suppliers', $supplier->id, 'DELETE', $oldData, null);
        $supplier->delete();

        return back()->with('success', 'Dobavljač uspješno obrisan.');
    }

    public function export(): Response
    {
        $suppliers = Supplier::with('branches')->orderBy('name')->get();

        $csv = "Naziv,Email,Telefon,Adresa,Status,Broj poslovnica,Datum kreiranja\n";
        
        foreach ($suppliers as $supplier) {
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",%s,%d,%s\n",
                $supplier->name,
                $supplier->email ?? '',
                $supplier->phone ?? '',
                $supplier->address ?? '',
                $supplier->is_active ? 'Aktivan' : 'Neaktivan',
                $supplier->branches->count(),
                $supplier->created_at->format('d.m.Y')
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="dobavljaci_' . date('d-m-Y') . '.csv"');
    }

    public function exportExcel(): StreamedResponse
    {
        $suppliers = Supplier::with('branches')->orderBy('name')->get();

        $excel = new ExcelExportService();
        
        $excel->setTitle('Dobavljači', 'Exportovano: ' . now()->format('d.m.Y H:i'), 7);
        $excel->setHeaders(['Naziv', 'Email', 'Telefon', 'Adresa', 'Status', 'Broj poslovnica', 'Datum kreiranja'], 3);

        $data = [];
        foreach ($suppliers as $supplier) {
            $data[] = [
                'name' => $supplier->name,
                'email' => $supplier->email ?? '-',
                'phone' => $supplier->phone ?? '-',
                'address' => $supplier->address ?? '-',
                'status' => $supplier->is_active ? 'Aktivan' : 'Neaktivan',
                'branches' => $supplier->branches->count(),
                'created' => $supplier->created_at->format('d.m.Y'),
            ];
        }

        $excel->setData($data, 4);
        $excel->autoSizeColumns(7);

        return $excel->download('dobavljaci_' . date('d-m-Y') . '.xlsx');
    }

    public function downloadTemplate(): StreamedResponse
    {
        return ExcelImportService::generateSupplierTemplate();
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120', // Max 5MB
        ]);

        try {
            $file = $request->file('file');
            $rows = ExcelImportService::readFile($file->getPathname());
            
            if (count($rows) < 2) {
                return back()->with('error', 'Excel fajl je prazan ili nema podataka za import.');
            }

            $result = ExcelImportService::parseSupplierData($rows);
            $suppliers = $result['suppliers'];
            $errors = $result['errors'];

            if (empty($suppliers)) {
                return back()->with('error', 'Nisu pronađeni validni dobavljači za import.');
            }

            $importedCount = 0;
            $branchesCount = 0;
            $skippedCount = 0;

            foreach ($suppliers as $supplierData) {
                // Check if supplier already exists
                $existingSupplier = Supplier::where('name', $supplierData['name'])->first();
                
                if ($existingSupplier) {
                    $skippedCount++;
                    continue;
                }

                // Create supplier
                $supplier = Supplier::create([
                    'name' => $supplierData['name'],
                    'email' => $supplierData['email'],
                    'phone' => $supplierData['phone'],
                    'address' => $supplierData['address'],
                    'is_active' => true,
                ]);

                AuditLog::log('suppliers', $supplier->id, 'INSERT', null, $supplier->toArray());
                $importedCount++;

                // Create branches
                foreach ($supplierData['branches'] as $branchData) {
                    $branch = Branch::create([
                        'supplier_id' => $supplier->id,
                        'name' => $branchData['name'],
                        'is_active' => true,
                    ]);
                    AuditLog::log('branches', $branch->id, 'INSERT', null, $branch->toArray());
                    $branchesCount++;
                }
            }

            $message = "Uspješno importovano {$importedCount} dobavljača i {$branchesCount} poslovnica.";
            if ($skippedCount > 0) {
                $message .= " Preskočeno {$skippedCount} postojećih dobavljača.";
            }
            if (!empty($errors)) {
                $message .= " Upozorenja: " . implode('; ', array_slice($errors, 0, 3));
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Greška pri importu: ' . $e->getMessage());
        }
    }
}
