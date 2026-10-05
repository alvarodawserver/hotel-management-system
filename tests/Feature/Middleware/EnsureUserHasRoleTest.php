<?php

use App\Models\User;

it('redirects owners without the required role to the dashboard with an error toast', function () {
    $this->actingAs(User::factory()->owner()->create())
        ->get(route('admin.users.index'))
        ->assertRedirect(route('dashboard'))
        ->assertInertiaFlash('toast.type', 'error')
        ->assertInertiaFlash('toast.message', __('You do not have permission to access this section.'));
});

it('redirects customers without the required role to the home page with an error toast', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.users.index'))
        ->assertRedirect(route('home'))
        ->assertInertiaFlash('toast.message', __('You do not have permission to access this section.'));
});

it('returns 403 to JSON requests from users without the required role', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('admin.users.index'))
        ->assertForbidden();
});

it('redirects guests to the login page', function () {
    $this->get(route('admin.users.index'))
        ->assertRedirect(route('login'));
});

it('lets users with the required role through', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk();
});
