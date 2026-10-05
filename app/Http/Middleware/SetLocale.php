<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Resolve the request locale: the user's preference, then the session,
     * then the browser's Accept-Language header, then the app default.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var list<string> $availableLocales */
        $availableLocales = config('app.available_locales');

        $candidates = [
            $request->user()?->locale,
            $request->session()->get('locale'),
        ];

        $locale = collect($candidates)->first(
            fn (?string $candidate): bool => in_array($candidate, $availableLocales, true),
        ) ?? $this->localeFromBrowser($request, $availableLocales);

        App::setLocale($locale);

        return $next($request);
    }

    /**
     * Pick the best supported locale from the Accept-Language header.
     *
     * @param  list<string>  $availableLocales
     */
    private function localeFromBrowser(Request $request, array $availableLocales): string
    {
        if ($request->getLanguages() === []) {
            return config('app.locale');
        }

        return $request->getPreferredLanguage($availableLocales) ?? config('app.locale');
    }
}
