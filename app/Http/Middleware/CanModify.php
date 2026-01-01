<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanModify
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->canModify()) {
            abort(403, 'Nemate dozvolu za ovu akciju.');
        }

        return $next($request);
    }
}
