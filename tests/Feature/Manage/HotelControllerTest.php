<?php

use App\Enums\Province;
use App\Models\Amenity;
use App\Models\Category;
use App\Models\Hotel;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array<string, mixed>
 */
function hotelPayload(array $overrides = []): array
{
    return [
        'name' => 'Hotel Mar Azul',
        'description' => 'Frente al mar.',
        'province' => Province::Malaga->value,
        'municipality' => 'Nerja',
        'address' => 'Calle del Mar 1',
        'stars' => 4,
        'cancellation_policy' => [
            ['days_before' => 7, 'refund_percent' => 100],
            ['days_before' => 3, 'refund_percent' => 50],
        ],
        ...$overrides,
    ];
}

describe('index', function () {
    it('lists only the signed-in owner\'s hotels', function () {
        $owner = User::factory()->owner()->create();
        $ownHotel = Hotel::factory()->for($owner, 'owner')->create();
        Hotel::factory()->create();

        $this->actingAs($owner)
            ->get(route('manage.hotels.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('manage/hotels/index')
                ->has('hotels', 1)
                ->where('hotels.0.id', $ownHotel->id));
    });

    it('sends admins to the admin hotel list', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('manage.hotels.index'))
            ->assertRedirect(route('admin.hotels.index'));
    });

    it('is not available to customers', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('manage.hotels.index'))
            ->assertRedirect(route('home'));
    });
});

describe('store', function () {
    it('creates a hidden hotel owned by the owner with its amenities and categories', function () {
        $owner = User::factory()->owner()->create();
        $amenity = Amenity::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($owner)->post(route('manage.hotels.store'), hotelPayload([
            'amenity_ids' => [$amenity->id],
            'category_ids' => [$category->id],
        ]));

        $hotel = Hotel::sole();
        $response->assertRedirect(route('manage.hotels.rooms.index', $hotel));

        expect($hotel)
            ->owner_id->toBe($owner->id)
            ->is_visible->toBeFalse()
            ->slug->toBe('hotel-mar-azul')
            ->and($hotel->amenities->modelKeys())->toBe([$amenity->id])
            ->and($hotel->categories->modelKeys())->toBe([$category->id]);
    });

    it('gives each hotel a unique slug', function () {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->post(route('manage.hotels.store'), hotelPayload());
        $this->actingAs($owner)->post(route('manage.hotels.store'), hotelPayload());

        expect(Hotel::pluck('slug')->all())->toBe(['hotel-mar-azul', 'hotel-mar-azul-2']);
    });

    it('stores the cancellation tiers sorted from the most to the least days before', function () {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->post(route('manage.hotels.store'), hotelPayload([
            'cancellation_policy' => [
                ['days_before' => 2, 'refund_percent' => 25],
                ['days_before' => 10, 'refund_percent' => 100],
            ],
        ]))->assertSessionHasNoErrors();

        expect(Hotel::sole()->cancellation_policy)->toBe([
            ['days_before' => 10, 'refund_percent' => 100],
            ['days_before' => 2, 'refund_percent' => 25],
        ]);
    });

    it('rejects tiers that refund more when cancelling later', function () {
        $this->actingAs(User::factory()->owner()->create())
            ->post(route('manage.hotels.store'), hotelPayload([
                'cancellation_policy' => [
                    ['days_before' => 7, 'refund_percent' => 50],
                    ['days_before' => 3, 'refund_percent' => 100],
                ],
            ]))
            ->assertSessionHasErrors('cancellation_policy');

        expect(Hotel::count())->toBe(0);
    });

    it('rejects tiers with repeated days', function () {
        $this->actingAs(User::factory()->owner()->create())
            ->post(route('manage.hotels.store'), hotelPayload([
                'cancellation_policy' => [
                    ['days_before' => 7, 'refund_percent' => 100],
                    ['days_before' => 7, 'refund_percent' => 50],
                ],
            ]))
            ->assertSessionHasErrors('cancellation_policy.0.days_before');
    });

    it('does not let admins create hotels', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('manage.hotels.store'), hotelPayload())
            ->assertRedirect(route('dashboard'));

        expect(Hotel::count())->toBe(0);
    });
});

describe('update', function () {
    it('lets the owner update the hotel without changing its slug', function () {
        $hotel = Hotel::factory()->create(['name' => 'Hotel Viejo']);
        $originalSlug = $hotel->slug;

        $this->actingAs($hotel->owner)
            ->put(route('manage.hotels.update', $hotel), hotelPayload(['name' => 'Hotel Nuevo']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('manage.hotels.edit', $hotel));

        expect($hotel->refresh())
            ->name->toBe('Hotel Nuevo')
            ->slug->toBe($originalSlug);
    });

    it('lets admins update any hotel', function () {
        $hotel = Hotel::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('manage.hotels.update', $hotel), hotelPayload(['name' => 'Editado por admin']))
            ->assertSessionHasNoErrors();

        expect($hotel->refresh()->name)->toBe('Editado por admin');
    });

    it('sends other owners back to the dashboard with an error toast', function () {
        $hotel = Hotel::factory()->create(['name' => 'Ajeno']);

        $this->actingAs(User::factory()->owner()->create())
            ->put(route('manage.hotels.update', $hotel), hotelPayload())
            ->assertRedirect(route('dashboard'))
            ->assertInertiaFlash('toast.message', __('You can only manage your own hotels.'));

        expect($hotel->refresh()->name)->toBe('Ajeno');
    });
});

describe('destroy', function () {
    it('soft deletes the hotel', function () {
        $hotel = Hotel::factory()->create();

        $this->actingAs($hotel->owner)
            ->delete(route('manage.hotels.destroy', $hotel))
            ->assertRedirect(route('manage.hotels.index'));

        expect($hotel->refresh()->trashed())->toBeTrue();
    });
});
