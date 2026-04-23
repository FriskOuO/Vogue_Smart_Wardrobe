<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = ['en', 'zh_TW'];

        $incoming = $request->input('locale');
        $sessionLocale = $request->session()->get('locale');

        if ($incoming === 'zh') {
            $incoming = 'zh_TW';
        }

        if (in_array($incoming, $allowed, true)) {
            $locale = $incoming;
            $request->session()->put('locale', $locale);
        } elseif (in_array($sessionLocale, $allowed, true)) {
            $locale = $sessionLocale;
        } else {
            $locale = config('app.locale', 'en');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
