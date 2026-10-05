<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * Usage: ->middleware('role:admin') or ->middleware('role:admin,owner').
     * Access denials are turned into a redirect with an error toast by the
     * exception handler in bootstrap/app.php.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowedRoles = array_map(fn (string $role): UserRole => UserRole::from($role), $roles);

        if (! $request->user()?->hasRole(...$allowedRoles)) {
            throw new AccessDeniedHttpException;
        }

        return $next($request);
    }
}
