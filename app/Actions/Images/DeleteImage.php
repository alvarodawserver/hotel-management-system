<?php

namespace App\Actions\Images;

use App\Models\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteImage
{
    /**
     * Delete the image and its file, then close the gap in the positions so
     * the next image becomes the cover if this one was.
     */
    public function handle(Image $image): void
    {
        DB::transaction(function () use ($image): void {
            $image->delete();

            Image::query()
                ->where('imageable_type', $image->imageable_type)
                ->where('imageable_id', $image->imageable_id)
                ->where('position', '>', $image->position)
                ->decrement('position');
        });

        Storage::disk('public')->delete($image->path);
    }
}
