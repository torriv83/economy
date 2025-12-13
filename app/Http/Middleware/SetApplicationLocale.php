<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;

class SetApplicationLocale
{
    public function handle($request, Closure $next)
    {
        app()->setLocale(app(SettingsService::class)->getLocale());
        return $next($request);
    }
}
