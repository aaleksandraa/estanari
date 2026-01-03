<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        $exchangeRates = Setting::getExchangeRates();
        
        return Inertia::render('Settings', [
            'exchangeRates' => $exchangeRates,
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
}
