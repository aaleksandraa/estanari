<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = 'bs'; // Default language

        // Check if user is authenticated and has language preference
        if (auth()->check() && auth()->user()->language) {
            $locale = auth()->user()->language;
        } 
        // Otherwise check session
        elseif (session()->has('locale')) {
            $locale = session('locale');
        }

        App::setLocale($locale);
        
        return $next($request);
    }
}
