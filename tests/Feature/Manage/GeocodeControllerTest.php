<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

it('returns coordinates for an address from Nominatim', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([
            ['display_name' => 'Calle del Mar 1, Nerja, Málaga', 'lat' => '36.7456', 'lon' => '-3.8754'],
        ]),
    ]);

    $this->actingAs(User::factory()->owner()->create())
        ->getJson(route('manage.geocode', ['q' => 'Calle del Mar 1, Nerja']))
        ->assertOk()
        ->assertJsonPath('results.0.latitude', 36.7456)
        ->assertJsonPath('results.0.longitude', -3.8754);

    Http::assertSent(fn ($request) => $request->hasHeader('User-Agent')
        && $request['countrycodes'] === 'es');
});

it('reports when the map service is down', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response(null, 503)]);

    $this->actingAs(User::factory()->owner()->create())
        ->getJson(route('manage.geocode', ['q' => 'Calle del Mar 1']))
        ->assertStatus(503);
});

it('is only available to owners and admins', function () {
    Http::fake();

    $this->actingAs(User::factory()->create())
        ->getJson(route('manage.geocode', ['q' => 'Calle del Mar 1']))
        ->assertForbidden();

    Http::assertNothingSent();
});
