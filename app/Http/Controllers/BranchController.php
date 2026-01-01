<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $branch = Branch::create($validated);
        AuditLog::log('branches', $branch->id, 'INSERT', null, $branch->toArray());

        return back()->with('success', 'Poslovnica uspješno kreirana.');
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        $oldData = $branch->toArray();
        $branch->update($validated);
        AuditLog::log('branches', $branch->id, 'UPDATE', $oldData, $branch->fresh()->toArray());

        return back()->with('success', 'Poslovnica uspješno ažurirana.');
    }

    public function forSupplier(int $supplierId): JsonResponse
    {
        $branches = Branch::where('supplier_id', $supplierId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'address']);

        return response()->json($branches);
    }

    public function deactivate(Branch $branch): RedirectResponse
    {
        $oldData = $branch->toArray();
        $branch->update(['is_active' => false]);
        AuditLog::log('branches', $branch->id, 'UPDATE', $oldData, $branch->fresh()->toArray());

        return back()->with('success', 'Poslovnica deaktivirana.');
    }

    public function activate(Branch $branch): RedirectResponse
    {
        $oldData = $branch->toArray();
        $branch->update(['is_active' => true]);
        AuditLog::log('branches', $branch->id, 'UPDATE', $oldData, $branch->fresh()->toArray());

        return back()->with('success', 'Poslovnica aktivirana.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        // Check if branch has payments
        if ($branch->payments()->exists()) {
            return back()->with('error', 'Nije moguće obrisati poslovnicu koja ima plaćanja. Deaktivirajte je umjesto toga.');
        }

        $oldData = $branch->toArray();
        AuditLog::log('branches', $branch->id, 'DELETE', $oldData, null);
        $branch->delete();

        return back()->with('success', 'Poslovnica uspješno obrisana.');
    }
}
