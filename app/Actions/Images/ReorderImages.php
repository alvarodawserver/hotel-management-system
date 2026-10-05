<?php

namespace App\Actions\Images;

use App\Models\Hotel;
use App\Models\Image;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReorderImages
{
    /**
     * Apply a new order to the hotel's or room's images. The first image
     * becomes the cover. The list must contain exactly the current images.
     *
     * @param  list<int>  $orderedIds
     *
     * @throws ValidationException
     */
    public function handle(Hotel|Room $imageable, array $orderedIds): void
    {
        $currentIds = $imageable->images()->pluck('id')->all();

        if (count($orderedIds) !== count($currentIds) || array_diff($currentIds, $orderedIds) !== []) {
            throw ValidationException::withMessages([
                'images' => __('The photo list is out of date. Reload the page and try again.'),
            ]);
        }

        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $position => $id) {
                Image::whereKey($id)->update(['position' => $position]);
            }
        });
    }
}
