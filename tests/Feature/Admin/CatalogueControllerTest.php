<?php

use App\Enums\AmenityIcon;
use App\Models\Amenity;
use App\Models\Category;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('creates an amenity with a name per locale and an allowed icon', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.amenities.store'), [
            'name' => ['es' => 'Piscina', 'en' => 'Swimming pool'],
            'icon' => AmenityIcon::Waves->value,
        ])
        ->assertSessionHasNoErrors();

    expect(Amenity::sole())
        ->name->toBe(['es' => 'Piscina', 'en' => 'Swimming pool'])
        ->icon->toBe('Waves');
});

it('rejects icons outside the allowed list', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.amenities.store'), [
            'name' => ['es' => 'Piscina', 'en' => 'Swimming pool'],
            'icon' => 'Skull',
        ])
        ->assertSessionHasErrors('icon');
});

it('rejects a name already used in the same locale', function () {
    Category::factory()->create(['name' => ['es' => 'Lujo', 'en' => 'Luxury']]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.categories.store'), [
            'name' => ['es' => 'Lujo', 'en' => 'Premium'],
        ])
        ->assertSessionHasErrors('name.es')
        ->assertSessionDoesntHaveErrors('name.en');
});

it('shows catalogue names in the user\'s language', function () {
    Category::factory()->create(['name' => ['es' => 'Lujo', 'en' => 'Luxury']]);
    $admin = User::factory()->admin()->create(['locale' => 'en']);

    $this->actingAs($admin)
        ->get(route('admin.categories.index'))
        ->assertInertia(fn (Assert $page) => $page->where('categories.0.name', 'Luxury'));
});

it('falls back to another language when a translation is missing', function () {
    $category = Category::factory()->create(['name' => ['es' => 'Romántico']]);

    expect($category->translation('en'))->toBe('Romántico');
});

it('detaches a deleted category from its hotels', function () {
    $category = Category::factory()->create();
    $hotel = Hotel::factory()->create();
    $hotel->categories()->attach($category);

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.categories.destroy', $category))
        ->assertSessionHasNoErrors();

    expect($hotel->categories()->count())->toBe(0);
});

it('refuses to delete a room type that rooms still use', function () {
    $room = Room::factory()->create();
    $room->delete();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.room-types.destroy', $room->room_type_id))
        ->assertSessionHasErrors('room_type');

    expect(RoomType::find($room->room_type_id))->not->toBeNull();
});

it('deletes an unused room type', function () {
    $roomType = RoomType::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.room-types.destroy', $roomType))
        ->assertSessionHasNoErrors();

    expect(RoomType::count())->toBe(0);
});

it('is not available to owners', function () {
    $this->actingAs(User::factory()->owner()->create())
        ->get(route('admin.amenities.index'))
        ->assertRedirect(route('dashboard'));
});
