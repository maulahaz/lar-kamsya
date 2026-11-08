<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keep API validation and error copy in Bahasa Indonesia without changing the
 * locale used by the Blade application.
 */
class SetApiLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale('id');

        return $next($request);
    }
}
