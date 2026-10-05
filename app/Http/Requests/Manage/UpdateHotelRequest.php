<?php

namespace App\Http\Requests\Manage;

use App\Models\Hotel;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class UpdateHotelRequest extends StoreHotelRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        /** @var Hotel $hotel */
        $hotel = $this->route('hotel');

        return Gate::inspect('update', $hotel);
    }
}
