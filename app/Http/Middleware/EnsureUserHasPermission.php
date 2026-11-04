<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        abort_unless($user && ($user->isAdmin() || $user->hasPermission($permission)), 403, 'Acesso não autorizado.');

        return $next($request);
    }
}
