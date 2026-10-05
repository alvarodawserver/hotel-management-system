<?php

namespace App\Actions\Images;

use App\Models\Hotel;
use App\Models\Image;
use App\Models\Room;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class StoreOptimizedImage
{
    public const MAX_DIMENSION = 1920;

    public const WEBP_QUALITY = 80;

    /**
     * GD decodes images to raw pixels (a 12-megapixel photo needs ~50 MB), so
     * the default 128 MB limit is not enough for large phone photos.
     */
    private const MEMORY_LIMIT = '512M';

    /**
     * Resize the upload to at most 1920px on its longest side, fix the
     * orientation of phone photos, re-encode it as WebP and attach it to the
     * hotel or room as its last image.
     */
    public function handle(Hotel|Room $imageable, UploadedFile $file): Image
    {
        $this->ensureEnoughMemory();

        $image = $this->load($file);
        $image = $this->fixOrientation($image, $file);
        $image = $this->resize($image);

        $path = $this->directoryFor($imageable).'/'.Str::uuid().'.webp';
        Storage::disk('public')->put($path, $this->encodeWebp($image));

        // Positions are kept contiguous (0..n-1) by ReorderImages and DeleteImage.
        return $imageable->images()->create([
            'path' => $path,
            'position' => $imageable->images()->count(),
        ]);
    }

    private function ensureEnoughMemory(): void
    {
        $current = ini_parse_quantity((string) ini_get('memory_limit'));

        if ($current !== -1 && $current < ini_parse_quantity(self::MEMORY_LIMIT)) {
            ini_set('memory_limit', self::MEMORY_LIMIT);
        }
    }

    /**
     * Decode straight from disk (instead of reading the file into a string
     * first) to avoid holding the compressed and decoded image at once.
     */
    private function load(UploadedFile $file): GdImage
    {
        $path = $file->getRealPath();

        $image = match ($file->getMimeType()) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };

        if ($image === false) {
            throw new RuntimeException('The uploaded file is not a readable image.');
        }

        imagepalettetotruecolor($image);

        return $image;
    }

    /**
     * Phone cameras store rotation in EXIF metadata instead of rotating the
     * pixels; apply it before the metadata is discarded by re-encoding.
     */
    private function fixOrientation(GdImage $image, UploadedFile $file): GdImage
    {
        if ($file->getMimeType() !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($file->getRealPath());
        $angle = match ($exif['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        return $rotated === false ? $image : $rotated;
    }

    private function resize(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = self::MAX_DIMENSION / max($width, $height);

        if ($scale >= 1) {
            return $image;
        }

        $resized = imagescale($image, (int) round($width * $scale), (int) round($height * $scale));

        return $resized === false ? $image : $resized;
    }

    private function encodeWebp(GdImage $image): string
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        imagewebp($image, null, self::WEBP_QUALITY);

        return (string) ob_get_clean();
    }

    private function directoryFor(Hotel|Room $imageable): string
    {
        return $imageable instanceof Room
            ? "hotels/{$imageable->hotel_id}/rooms/{$imageable->id}"
            : "hotels/{$imageable->id}";
    }
}
