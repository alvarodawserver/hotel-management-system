<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoomTypeRequest;
use App\Models\RoomType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RoomTypeController extends Controller
{
    /**
     * List room types with the number of rooms using each one.
     */
    public function index(): Response
    {
        return Inertia::render('admin/room-types/index', [
            'roomTypes' => RoomType::query()
                ->withCount('rooms')
                ->get()
                ->map(fn (RoomType $roomType): array => [
                    'id' => $roomType->id,
                    'name' => $roomType->translation(),
                    'translations' => $roomType->translations(),
                    'default_capacity' => $roomType->default_capacity,
                    'usage_count' => $roomType->rooms_count,
                ])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
        ]);
    }

    public function store(RoomTypeRequest $request): RedirectResponse
    {
        RoomType::create([
            'name' => $request->translatedName(),
            'default_capacity' => $request->integer('default_capacity'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Room type created.')]);

        return to_route('admin.room-types.index');
    }

    public function update(RoomTypeRequest $request, RoomType $roomType): RedirectResponse
    {
        $roomType->update([
            'name' => $request->translatedName(),
            'default_capacity' => $request->integer('default_capacity'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Room type updated.')]);

        return to_route('admin.room-types.index');
    }

    /**
     * Delete the room type, unless rooms (including deleted ones, which past
     * reservations still reference) use it.
     *
     * @throws ValidationException
     */
    public function destroy(RoomType $roomType): RedirectResponse
    {
        $roomCount = $roomType->rooms()->count();

        if ($roomCount > 0) {
            throw ValidationException::withMessages([
                'room_type' => trans_choice(
                    'This room type is used by :count room and cannot be deleted.|This room type is used by :count rooms and cannot be deleted.',
                    $roomCount,
                ),
            ]);
        }

        $roomType->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Room type deleted.')]);

        return to_route('admin.room-types.index');
    }
}
