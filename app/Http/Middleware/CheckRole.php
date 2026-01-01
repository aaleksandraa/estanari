<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !$user->userRole) {
            abort(403, 'Nemate pristup ovoj stranici.');
        }

        if (!in_array($user->getRole(), $roles)) {
            abort(403, 'Nemate dozvolu za ovu akciju.');
        }

        return $next($request);
    }
}
