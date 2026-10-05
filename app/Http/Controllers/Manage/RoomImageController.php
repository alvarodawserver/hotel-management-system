<?php

namespace App\Http\Controllers\Manage;

use App\Actions\Images\DeleteImage;
use App\Actions\Images\ReorderImages;
use App\Actions\Images\StoreOptimizedImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\ReorderImagesRequest;
use App\Http\Requests\Manage\StoreImagesRequest;
use App\Models\Hotel;
use App\Models\Image;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class RoomImageController extends Controller
{
    /**
     * Upload one or more photos of a room.
     */
    public function store(StoreImagesRequest $request, Hotel $hotel, Room $room, StoreOptimizedImage $storeImage): RedirectResponse
    {
        /** @var list<UploadedFile> $files */
        $files = $request->file('images');

        foreach ($files as $file) {
            $storeImage->handle($room, $file);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count photo uploaded.|:count photos uploaded.', count($files)),
        ]);

        return back();
    }

    /**
     * Save a new photo order for the room.
     */
    public function reorder(ReorderImagesRequest $request, Hotel $hotel, Room $room, ReorderImages $reorderImages): RedirectResponse
    {
        $reorderImages->handle($room, $request->orderedIds());

        return back();
    }

    /**
     * Delete a room photo and its file.
     */
    public function destroy(Hotel $hotel, Room $room, Image $image, DeleteImage $deleteImage): RedirectResponse
    {
        Gate::authorize('update', $hotel);

        $deleteImage->handle($image);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Photo deleted.')]);

        return back();
    }
}
