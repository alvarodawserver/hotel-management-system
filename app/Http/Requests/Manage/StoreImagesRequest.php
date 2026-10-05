<?php

namespace App\Http\Requests\Manage;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Uploads photos to a hotel, or to one of its rooms when the route has a room.
 */
class StoreImagesRequest extends FormRequest
{
    public const MAX_FILE_KILOBYTES = 10240;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        /** @var Hotel $hotel */
        $hotel = $this->route('hotel');

        return Gate::inspect('update', $hotel);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $imageable = $this->imageable();
        $maxImages = $imageable instanceof Room ? Room::MAX_IMAGES : Hotel::MAX_IMAGES;
        $remaining = max(0, $maxImages - $imageable->images()->count());

        return [
            'images' => ['required', 'array', 'min:1', 'max:'.$remaining],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_FILE_KILOBYTES],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $imageable = $this->imageable();

        return [
            'images.max' => __('You can have at most :max photos; delete some before uploading more.', [
                'max' => $imageable instanceof Room ? Room::MAX_IMAGES : Hotel::MAX_IMAGES,
            ]),
        ];
    }

    public function imageable(): Hotel|Room
    {
        /** @var Room|null $room */
        $room = $this->route('room');

        /** @var Hotel $hotel */
        $hotel = $this->route('hotel');

        return $room ?? $hotel;
    }
}
