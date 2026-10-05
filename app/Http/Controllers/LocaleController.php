<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Switch the interface language for the session and, when signed in, the user.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(config('app.available_locales'))],
        ]);

        $request->session()->put('locale', $validated['locale']);

        $request->user()?->forceFill(['locale' => $validated['locale']])->save();

        return back();
    }
}
