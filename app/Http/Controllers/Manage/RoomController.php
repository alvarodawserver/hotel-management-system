<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\RoomRequest;
use App\Http\Resources\HotelSummaryResource;
use App\Models\Hotel;
use App\Models\Image;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    /**
     * List the hotel's rooms.
     */
    public function index(Hotel $hotel): Response
    {
        Gate::authorize('update', $hotel);

        $rooms = $hotel->rooms()
            ->with('roomType')
            ->withCount('images')
            ->get()
            ->sortBy('name', SORT_NATURAL)
            ->values()
            ->map(fn (Room $room): array => [
                ...$this->roomData($room),
                'room_type_name' => $room->roomType->translation(),
                'images_count' => $room->images_count,
            ]);

        return Inertia::render('manage/hotels/rooms/index', [
            'hotel' => HotelSummaryResource::make($hotel)->resolve(),
            'rooms' => $rooms,
            'roomTypes' => $this->roomTypeOptions(),
        ]);
    }

    /**
     * Store a single room.
     */
    public function store(RoomRequest $request, Hotel $hotel): RedirectResponse
    {
        $hotel->rooms()->create($request->roomAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Room created.')]);

        return to_route('manage.hotels.rooms.index', $hotel);
    }

    /**
     * Show the form for editing a room and its photos.
     */
    public function edit(Hotel $hotel, Room $room): Response
    {
        Gate::authorize('update', $hotel);

        return Inertia::render('manage/hotels/rooms/edit', [
            'hotel' => HotelSummaryResource::make($hotel)->resolve(),
            'room' => $this->roomData($room),
            'images' => $room->images->map(fn (Image $image): array => [
                'id' => $image->id,
                'url' => $image->url,
            ]),
            'maxImages' => Room::MAX_IMAGES,
            'roomTypes' => $this->roomTypeOptions(),
        ]);
    }

    /**
     * Update a room.
     */
    public function update(RoomRequest $request, Hotel $hotel, Room $room): RedirectResponse
    {
        $room->update($request->roomAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Room updated.')]);

        return back();
    }

    /**
     * Delete a room (soft delete, so past reservations keep it).
     */
    public function destroy(Hotel $hotel, Room $room): RedirectResponse
    {
        Gate::authorize('update', $hotel);

        if ($room->reservations()->active()->exists()) {
            throw ValidationException::withMessages([
                'room' => __('This room has active reservations and cannot be deleted. You can deactivate it instead.'),
            ]);
        }

        $room->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Room deleted.')]);

        return to_route('manage.hotels.rooms.index', $hotel);
    }

    /**
     * @return array<string, mixed>
     */
    private function roomData(Room $room): array
    {
        return [
            'id' => $room->id,
            'name' => $room->name,
            'room_type_id' => $room->room_type_id,
            'capacity' => $room->capacity,
            'price_per_night' => $room->price_per_night,
            'description' => $room->description,
            'is_active' => $room->is_active,
        ];
    }

    /**
     * @return Collection<int, array{id: int, name: string, default_capacity: int}>
     */
    private function roomTypeOptions(): Collection
    {
        return RoomType::query()->get()
            ->map(fn (RoomType $roomType): array => [
                'id' => $roomType->id,
                'name' => $roomType->translation(),
                'default_capacity' => $roomType->default_capacity,
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->toBase();
    }
}
