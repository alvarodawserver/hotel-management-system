<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// The test client sends "Accept-Language: en-us" by default, so tests that
// depend on the browser language set the header explicitly.

it('defaults to Spanish when there is no preference', function () {
    $this->withHeader('Accept-Language', '')
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'es'));
});

it('uses the browser language when it is supported', function () {
    $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
});

it('falls back to Spanish when the browser language is not supported', function () {
    $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'es'));
});

it('prefers the session language over the browser language', function () {
    $this->withSession(['locale' => 'en'])
        ->withHeader('Accept-Language', 'es-ES')
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
});

it('prefers the user language over the session language', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)
        ->withSession(['locale' => 'es'])
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
});

it('shares the translations of the current language', function () {
    $this->withHeader('Accept-Language', 'es-ES')
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('translations.Log in', 'Iniciar sesión'));
});

it('stores the chosen language in the session for guests', function () {
    $this->from(route('home'))
        ->post(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect(route('home'))
        ->assertSessionHas('locale', 'en');
});

it('stores the chosen language on the signed-in user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('locale.update'), ['locale' => 'en'])
        ->assertSessionHas('locale', 'en');

    expect($user->refresh()->locale)->toBe('en');
});

it('rejects unsupported languages', function () {
    $this->post(route('locale.update'), ['locale' => 'fr'])
        ->assertSessionHasErrors('locale')
        ->assertSessionMissing('locale');
});

it('shows validation messages in Spanish', function () {
    $this->withHeader('Accept-Language', 'es-ES')
        ->post(route('register.store'), [])
        ->assertSessionHasErrors(['email' => 'El campo correo electrónico es obligatorio.']);
});
