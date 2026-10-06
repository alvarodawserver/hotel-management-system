<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('owners and admins can visit the dashboard', function () {
    $user = User::factory()->owner()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('owners get the numbers of their hotels after the page loads', function () {
    $this->actingAs(User::factory()->owner()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('today')
            ->missing('kpis')
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->has('kpis.occupancy')
                ->has('kpis.arrivals')
                ->has('months', 12)
                ->has('attention')
                ->has('arrivals')
                ->missing('topHotels')));
});

test('admins get the platform numbers and rankings', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->has('kpis.bookings')
                ->has('kpis.new_users')
                ->has('months', 12)
                ->has('topHotels')
                ->has('provinces', 5)
                ->missing('arrivals')));
});

test('customers are sent to the public home page instead of the dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('home'));
});
