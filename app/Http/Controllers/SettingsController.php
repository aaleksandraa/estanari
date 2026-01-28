<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        $exchangeRates = Setting::getExchangeRates();
        $companyName = Setting::get('company_name', 'WizFlussi');
        
        return Inertia::render('Settings', [
            'exchangeRates' => $exchangeRates,
            'companyName' => $companyName,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . auth()->id(),
        ]);

        auth()->user()->update($validated);

        return back()->with('success', 'Profil uspješno ažuriran.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Lozinka uspješno promijenjena.');
    }

    public function updateExchangeRates(Request $request): RedirectResponse
    {
        // Only admin can update exchange rates
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Nemate dozvolu za ovu akciju.');
        }

        $validated = $request->validate([
            'exchange_rate_eur' => 'required|numeric|min:0.01|max:10',
            'exchange_rate_usd' => 'required|numeric|min:0.01|max:10',
        ]);

        Setting::set('exchange_rate_eur', $validated['exchange_rate_eur']);
        Setting::set('exchange_rate_usd', $validated['exchange_rate_usd']);

        return back()->with('success', 'Kursevi valuta uspješno ažurirani.');
    }

    public function updateCompanyName(Request $request): RedirectResponse
    {
        // Only admin can update company name
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Nemate dozvolu za ovu akciju.');
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:100',
        ]);

        Setting::set('company_name', $validated['company_name']);

        return back()->with('success', 'Naziv firme uspješno ažuriran.');
    }

    public function updateLanguage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'language' => 'required|string|in:bs,de,en,it,sl,es,bg,hu,fr,el',
        ]);

        auth()->user()->update([
            'language' => $validated['language'],
        ]);

        // Set app locale
        app()->setLocale($validated['language']);
        session()->put('locale', $validated['language']);

        return back()->with('success', __('Language successfully updated.'));
    }

    public function backup()
    {
        // Only admin can backup
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Nemate dozvolu za ovu akciju.');
        }

        $data = [
            'backup_date' => now()->toDateTimeString(),
            'app_version' => '1.0.0',
            'suppliers' => \App\Models\Supplier::with('branches')->get()->toArray(),
            'payments' => \App\Models\Payment::with(['supplier:id,name', 'branch:id,name'])->get()->toArray(),
            'payment_plans' => \App\Models\PaymentPlan::all()->toArray(),
            'settings' => \App\Models\Setting::all()->toArray(),
        ];

        $filename = 'wizflussi_backup_' . now()->format('Y-m-d_His') . '.json';
        
        return response()->json($data)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function import(Request $request): RedirectResponse
    {
        // Only admin can import
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Nemate dozvolu za ovu akciju.');
        }

        $request->validate([
            'file' => 'required|file|mimes:json|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('file');
            $content = file_get_contents($file->getRealPath());
            $data = json_decode($content, true);

            if (!$data || !isset($data['suppliers']) || !isset($data['payments'])) {
                return back()->with('error', 'Nevažeći format backup fajla.');
            }

            \DB::beginTransaction();

            // Import suppliers and branches
            $supplierIdMap = [];
            $branchIdMap = [];
            
            foreach ($data['suppliers'] as $supplierData) {
                $oldSupplierId = $supplierData['id'];
                $branches = $supplierData['branches'] ?? [];
                unset($supplierData['id'], $supplierData['branches'], $supplierData['created_at'], $supplierData['updated_at']);
                
                $supplier = \App\Models\Supplier::create($supplierData);
                $supplierIdMap[$oldSupplierId] = $supplier->id;
                
                // Import branches
                foreach ($branches as $branchData) {
                    $oldBranchId = $branchData['id'];
                    unset($branchData['id'], $branchData['created_at'], $branchData['updated_at']);
                    $branchData['supplier_id'] = $supplier->id;
                    
                    $branch = \App\Models\Branch::create($branchData);
                    $branchIdMap[$oldBranchId] = $branch->id;
                }
            }

            // Import payments
            $paymentIdMap = [];
            foreach ($data['payments'] as $paymentData) {
                $oldPaymentId = $paymentData['id'];
                unset($paymentData['id'], $paymentData['supplier'], $paymentData['branch'], $paymentData['created_at'], $paymentData['updated_at']);
                
                // Map old IDs to new IDs
                if (isset($supplierIdMap[$paymentData['supplier_id']])) {
                    $paymentData['supplier_id'] = $supplierIdMap[$paymentData['supplier_id']];
                }
                if (isset($branchIdMap[$paymentData['branch_id']])) {
                    $paymentData['branch_id'] = $branchIdMap[$paymentData['branch_id']];
                }
                
                $paymentData['created_by'] = auth()->id();
                if ($paymentData['status'] === 'PAID') {
                    $paymentData['paid_by'] = auth()->id();
                }
                
                $payment = \App\Models\Payment::create($paymentData);
                $paymentIdMap[$oldPaymentId] = $payment->id;
            }

            // Import payment plans
            if (isset($data['payment_plans'])) {
                foreach ($data['payment_plans'] as $planData) {
                    unset($planData['id'], $planData['created_at'], $planData['updated_at']);
                    
                    // Map old payment IDs to new payment IDs
                    if (isset($planData['payment_ids']) && is_array($planData['payment_ids'])) {
                        $newPaymentIds = [];
                        foreach ($planData['payment_ids'] as $oldPaymentId) {
                            if (isset($paymentIdMap[$oldPaymentId])) {
                                $newPaymentIds[] = $paymentIdMap[$oldPaymentId];
                            }
                        }
                        $planData['payment_ids'] = $newPaymentIds;
                    }
                    
                    $planData['created_by'] = auth()->id();
                    \App\Models\PaymentPlan::create($planData);
                }
            }

            \DB::commit();

            return back()->with('success', 'Podaci uspješno importovani! Importovano: ' . count($data['suppliers']) . ' dobavljača, ' . count($data['payments']) . ' plaćanja.');
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Import error: ' . $e->getMessage());
            return back()->with('error', 'Greška pri importu: ' . $e->getMessage());
        }
    }
}
