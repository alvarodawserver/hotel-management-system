<?php

namespace App\Http\Controllers\Manage;

use App\Actions\Images\DeleteImage;
use App\Actions\Images\ReorderImages;
use App\Actions\Images\StoreOptimizedImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\ReorderImagesRequest;
use App\Http\Requests\Manage\StoreImagesRequest;
use App\Http\Resources\HotelSummaryResource;
use App\Models\Hotel;
use App\Models\Image;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HotelImageController extends Controller
{
    /**
     * Show the hotel's photo gallery.
     */
    public function index(Hotel $hotel): Response
    {
        Gate::authorize('update', $hotel);

        return Inertia::render('manage/hotels/photos', [
            'hotel' => HotelSummaryResource::make($hotel)->resolve(),
            'images' => $hotel->images->map(fn (Image $image): array => [
                'id' => $image->id,
                'url' => $image->url,
            ]),
            'maxImages' => Hotel::MAX_IMAGES,
        ]);
    }

    /**
     * Upload one or more photos.
     */
    public function store(StoreImagesRequest $request, Hotel $hotel, StoreOptimizedImage $storeImage): RedirectResponse
    {
        /** @var list<UploadedFile> $files */
        $files = $request->file('images');

        foreach ($files as $file) {
            $storeImage->handle($hotel, $file);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count photo uploaded.|:count photos uploaded.', count($files)),
        ]);

        return back();
    }

    /**
     * Save a new photo order; the first photo is the cover.
     */
    public function reorder(ReorderImagesRequest $request, Hotel $hotel, ReorderImages $reorderImages): RedirectResponse
    {
        $reorderImages->handle($hotel, $request->orderedIds());

        return back();
    }

    /**
     * Delete a photo and its file.
     */
    public function destroy(Hotel $hotel, Image $image, DeleteImage $deleteImage): RedirectResponse
    {
        Gate::authorize('update', $hotel);

        $deleteImage->handle($image);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Photo deleted.')]);

        return back();
    }
}
