<?php

namespace Database\Seeders\Demo;

use App\Models\Activity;
use App\Models\Amenity;
use App\Models\Category;
use App\Models\Hotel;
use App\Models\Offer;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * @phpstan-import-type DemoHotel from DemoSeeder
 */
class HotelSeeder extends Seeder
{
    /**
     * Room photos in database/seeders/images/rooms by room type (English
     * name). The first room of each group shows them on the hotel page.
     *
     * @var array<string, list<string>>
     */
    private const ROOM_PHOTOS = [
        'Single' => ['double-minimal', 'bed-detail'],
        'Double for single use' => ['bed-detail', 'double-minimal'],
        'Double' => ['double-classic', 'double-city', 'double-warm'],
        'Twin' => ['double-city', 'double-classic'],
        'Triple' => ['double-warm', 'double-city'],
        'Family' => ['double-warm', 'double-classic'],
        'Junior suite' => ['suite-modern', 'suite-lounge'],
        'Suite' => ['suite-lounge', 'suite-glass-bath', 'suite-modern'],
    ];

    /** @var Collection<string, int> */
    private Collection $amenityIds;

    /** @var Collection<string, int> */
    private Collection $categoryIds;

    /** @var Collection<string, int> */
    private Collection $roomTypeIds;

    /** How many hotels have used each room photo list, to vary the first photo. */
    private int $roomPhotoTurn = 0;

    /**
     * The hand-written hotels with their rooms, photos, activities and
     * offers. Photos of a previous seed are removed first, since the
     * database they belonged to is gone.
     */
    public function run(): void
    {
        $this->amenityIds = $this->idsByEnglishName(Amenity::all());
        $this->categoryIds = $this->idsByEnglishName(Category::all());
        $this->roomTypeIds = $this->idsByEnglishName(RoomType::all());

        Storage::disk('public')->deleteDirectory('hotels');

        foreach (DemoSeeder::hotels() as $data) {
            $this->createHotel($data);
        }
    }

    /**
     * @param  DemoHotel  $data
     */
    private function createHotel(array $data): void
    {
        $createdAt = now()->subDays($data['created_days_ago'])->setTime(10, 0);

        $hotel = Hotel::factory()
            ->for(User::where('email', $data['owner'])->firstOrFail(), 'owner')
            ->create([
                'name' => $data['name'],
                'description' => $data['description'],
                'province' => $data['province'],
                'municipality' => $data['municipality'],
                'address' => $data['address'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'stars' => $data['stars'],
                'cancellation_policy' => $data['cancellation_policy'] ?? Hotel::DEFAULT_CANCELLATION_POLICY,
                'is_visible' => $data['status'] !== 'draft',
                'blocked_at' => $data['status'] === 'blocked' ? now()->subDays(5) : null,
                'blocked_reason' => $data['blocked_reason'] ?? null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

        $hotel->amenities()->attach($this->ids($this->amenityIds, $data['amenities']));
        $hotel->categories()->attach($this->ids($this->categoryIds, $data['categories']));

        foreach ($data['photos'] as $position => $photo) {
            $this->attachPhoto($hotel, "hotels/{$photo}", $position);
        }

        $this->createRooms($hotel, $data, $createdAt);
        $this->createActivities($hotel, $data);
        $this->createOffers($hotel, $data, $createdAt);
    }

    /**
     * Rooms are numbered by floor, one floor per room type (101, 102…, 201…),
     * and identical within a type, so the hotel page groups them.
     *
     * @param  DemoHotel  $data
     */
    private function createRooms(Hotel $hotel, array $data, CarbonImmutable $createdAt): void
    {
        foreach ($data['rooms'] as $floor => $group) {
            for ($number = 1; $number <= $group['count']; $number++) {
                $room = Room::factory()->for($hotel)->create([
                    'room_type_id' => $this->roomTypeIds[$group['type']],
                    'name' => (string) (($floor + 1) * 100 + $number),
                    'capacity' => $group['capacity'],
                    'price_per_night' => $group['price'] * 100,
                    'is_active' => ! ($floor === 0 && $number > $group['count'] - ($data['inactive_rooms'] ?? 0)),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                if ($number === 1 && $data['photos'] !== []) {
                    $this->attachRoomPhotos($room, $group['type']);
                }
            }
        }
    }

    /**
     * One photo per room type, two for suites, rotating the list so hotels
     * do not all share the same first photo.
     */
    private function attachRoomPhotos(Room $room, string $roomType): void
    {
        $photos = self::ROOM_PHOTOS[$roomType];
        $count = str_contains($roomType, 'uite') ? 2 : 1;
        $first = $this->roomPhotoTurn++ % count($photos);

        for ($position = 0; $position < $count; $position++) {
            $this->attachPhoto($room, 'rooms/'.$photos[($first + $position) % count($photos)], $position);
        }
    }

    /**
     * Copy a photo of the bank to the place StoreOptimizedImage would have
     * stored an upload. The bank is already resized and in WebP.
     */
    private function attachPhoto(Hotel|Room $imageable, string $photo, int $position): void
    {
        $source = database_path("seeders/images/{$photo}.webp");

        if (! File::exists($source)) {
            throw new RuntimeException("Demo photo missing: {$source}");
        }

        $directory = $imageable instanceof Room
            ? "hotels/{$imageable->hotel_id}/rooms/{$imageable->id}"
            : "hotels/{$imageable->id}";
        $path = $directory.'/'.Str::uuid().'.webp';

        Storage::disk('public')->put($path, File::get($source));
        $imageable->images()->create(['path' => $path, 'position' => $position]);
    }

    /**
     * @param  DemoHotel  $data
     */
    private function createActivities(Hotel $hotel, array $data): void
    {
        foreach ($data['activities'] as [$name, $description, $price, $startsAt, $endsAt, $capacity]) {
            Activity::factory()->for($hotel)->create([
                'name' => $name,
                'description' => $description,
                'price' => $price * 100,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'capacity' => $capacity,
            ]);
        }
    }

    /**
     * Offers run between days relative to today; each was created a couple
     * of weeks before it started (never before the hotel).
     *
     * @param  DemoHotel  $data
     */
    private function createOffers(Hotel $hotel, array $data, CarbonImmutable $hotelCreatedAt): void
    {
        foreach ($data['offers'] as [$title, $percent, $fromDay, $toDay, $roomType, $isActive]) {
            $createdAt = today()->addDays($fromDay - 15)->setTime(9, 0)->max($hotelCreatedAt)->min(now());

            Offer::factory()->for($hotel)->create([
                'title' => $title,
                'discount_percent' => $percent,
                'starts_on' => today()->addDays($fromDay),
                'ends_on' => today()->addDays($toDay),
                'room_type_id' => $roomType === null ? null : $this->roomTypeIds[$roomType],
                'is_active' => $isActive,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    /**
     * @template TModel of Amenity|Category|RoomType
     *
     * @param  Collection<int, TModel>  $models
     * @return Collection<string, int>
     */
    private function idsByEnglishName(Collection $models): Collection
    {
        return $models->mapWithKeys(fn (Amenity|Category|RoomType $model): array => [$model->translation('en') => $model->id]);
    }

    /**
     * @param  Collection<string, int>  $idsByName
     * @param  list<string>  $names
     * @return list<int>
     */
    private function ids(Collection $idsByName, array $names): array
    {
        return array_map(fn (string $name): int => $idsByName[$name], $names);
    }
}
