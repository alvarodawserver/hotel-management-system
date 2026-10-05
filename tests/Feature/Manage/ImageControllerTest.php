<?php

use App\Models\Hotel;
use App\Models\Image;
use App\Models\Room;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('stores uploaded photos as WebP resized to at most 1920 pixels', function () {
    $hotel = Hotel::factory()->create();

    $this->actingAs($hotel->owner)
        ->post(route('manage.hotels.images.store', $hotel), [
            'images' => [UploadedFile::fake()->image('playa.jpg', 4000, 3000)],
        ])
        ->assertSessionHasNoErrors();

    $image = Image::sole();
    [$width, $height, $type] = getimagesizefromstring(Storage::disk('public')->get($image->path));

    expect($image->path)->toStartWith("hotels/{$hotel->id}/")->toEndWith('.webp')
        ->and([$width, $height])->toBe([1920, 1440])
        ->and($type)->toBe(IMAGETYPE_WEBP)
        ->and($image->position)->toBe(0);
});

it('appends new photos after the existing ones', function () {
    $hotel = Hotel::factory()->create();
    Image::factory()->for($hotel, 'imageable')->create(['position' => 0]);

    $this->actingAs($hotel->owner)
        ->post(route('manage.hotels.images.store', $hotel), [
            'images' => [UploadedFile::fake()->image('a.png', 800, 600)],
        ]);

    expect($hotel->images()->pluck('position')->all())->toBe([0, 1]);
});

it('rejects files that are not images', function () {
    $hotel = Hotel::factory()->create();

    $this->actingAs($hotel->owner)
        ->post(route('manage.hotels.images.store', $hotel), [
            'images' => [UploadedFile::fake()->create('menu.pdf', 100, 'application/pdf')],
        ])
        ->assertSessionHasErrors('images.0');

    expect(Image::count())->toBe(0);
});

it('rejects uploads beyond the photo limit', function () {
    $hotel = Hotel::factory()->create();
    Image::factory()->for($hotel, 'imageable')->count(Hotel::MAX_IMAGES)->create();

    $this->actingAs($hotel->owner)
        ->post(route('manage.hotels.images.store', $hotel), [
            'images' => [UploadedFile::fake()->image('extra.jpg')],
        ])
        ->assertSessionHasErrors('images');

    expect($hotel->images()->count())->toBe(Hotel::MAX_IMAGES);
});

it('reorders photos so the first one becomes the cover', function () {
    $hotel = Hotel::factory()->create();
    $first = Image::factory()->for($hotel, 'imageable')->create(['position' => 0]);
    $second = Image::factory()->for($hotel, 'imageable')->create(['position' => 1]);

    $this->actingAs($hotel->owner)
        ->put(route('manage.hotels.images.reorder', $hotel), ['images' => [$second->id, $first->id]])
        ->assertSessionHasNoErrors();

    expect($hotel->coverImage->is($second))->toBeTrue()
        ->and($first->refresh()->position)->toBe(1);
});

it('rejects an order that does not match the current photos', function () {
    $hotel = Hotel::factory()->create();
    $image = Image::factory()->for($hotel, 'imageable')->create();
    $foreignImage = Image::factory()->create();

    $this->actingAs($hotel->owner)
        ->put(route('manage.hotels.images.reorder', $hotel), ['images' => [$foreignImage->id, $image->id]])
        ->assertSessionHasErrors('images');
});

it('deletes a photo, its file, and closes the gap in the order', function () {
    $hotel = Hotel::factory()->create();
    $cover = Image::factory()->for($hotel, 'imageable')->create(['position' => 0]);
    $next = Image::factory()->for($hotel, 'imageable')->create(['position' => 1]);
    Storage::disk('public')->put($cover->path, 'image-bytes');

    $this->actingAs($hotel->owner)
        ->delete(route('manage.hotels.images.destroy', [$hotel, $cover]))
        ->assertSessionHasNoErrors();

    expect(Image::find($cover->id))->toBeNull()
        ->and($next->refresh()->position)->toBe(0);
    Storage::disk('public')->assertMissing($cover->path);
});

it('returns 404 when deleting a photo of another hotel', function () {
    $hotel = Hotel::factory()->create();
    $foreignImage = Image::factory()->create();

    $this->actingAs($hotel->owner)
        ->delete(route('manage.hotels.images.destroy', [$hotel, $foreignImage]))
        ->assertNotFound();
});

it('stores room photos in the room folder', function () {
    $room = Room::factory()->create();

    $this->actingAs($room->hotel->owner)
        ->post(route('manage.hotels.rooms.images.store', [$room->hotel, $room]), [
            'images' => [UploadedFile::fake()->image('cama.jpg', 1200, 800)],
        ])
        ->assertSessionHasNoErrors();

    expect($room->images()->sole()->path)->toStartWith("hotels/{$room->hotel_id}/rooms/{$room->id}/");
});
