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

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        if (! method_exists($user, 'hasAnyRole')) {
            abort(403, 'Role checking is not available for this user model.');
        }

        if (empty($roles) || $user->hasAnyRole($roles)) {
            return $next($request);
        }

        abort(403, 'You do not have the required role to access this resource.');
    }
}
