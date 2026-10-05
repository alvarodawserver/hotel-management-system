<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::emailVerification());
});

test('new users are sent a verification email', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    Notification::assertSentTo(User::where('email', 'test@example.com')->sole(), VerifyEmail::class);
});

test('unverified users cannot book until they verify their email', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('reservations.index'))
        ->assertRedirect(route('verification.notice'));
});

test('the verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/verify-email'));
});

test('the signed link in the email verifies the account', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($url)->assertRedirect();

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
});

test('a link for another email address does not verify the account', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1('someone-else@example.com'),
    ]);

    $this->actingAs($user)->get($url);

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});
