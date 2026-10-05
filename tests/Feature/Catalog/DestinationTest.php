<?php

use App\Enums\Province;
use App\Models\Hotel;

it('suggests provinces, municipalities and hotels, ignoring accents', function () {
    Hotel::factory()->visible()->create([
        'name' => 'Hotel Almadraba',
        'municipality' => 'Almuñécar',
        'province' => Province::Granada,
    ]);

    $suggestions = $this->getJson(route('destinations.index', ['q' => 'alm']))
        ->assertOk()
        ->json('suggestions');

    expect(collect($suggestions)->pluck('type', 'value')->all())->toBe([
        'Almería' => 'province',
        'Almuñécar' => 'municipality',
        'Hotel Almadraba' => 'hotel',
    ]);
});

it('does not suggest hotels that are not published', function () {
    Hotel::factory()->create(['name' => 'Hotel Escondido', 'municipality' => 'Zahara']);

    $this->getJson(route('destinations.index', ['q' => 'escondido']))
        ->assertOk()
        ->assertJsonCount(0, 'suggestions');
});
