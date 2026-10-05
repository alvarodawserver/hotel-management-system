<?php

namespace App\Http\Controllers\Manage;

use App\Actions\Rooms\CreateRoomsInBulk;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\BulkStoreRoomsRequest;
use App\Models\Hotel;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class BulkRoomController extends Controller
{
    /**
     * Create several consecutively numbered rooms at once.
     */
    public function store(BulkStoreRoomsRequest $request, Hotel $hotel, CreateRoomsInBulk $createRooms): RedirectResponse
    {
        $names = $createRooms->handle($hotel, $request->bulkData());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count room created.|:count rooms created.', count($names)),
        ]);

        return to_route('manage.hotels.rooms.index', $hotel);
    }
}
