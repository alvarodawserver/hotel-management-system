<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Hotels\BlockHotel;
use App\Actions\Hotels\UnblockHotel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlockHotelRequest;
use App\Models\Hotel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class HotelBlockController extends Controller
{
    /**
     * Block the hotel with a reason the owner can see.
     */
    public function store(BlockHotelRequest $request, Hotel $hotel, BlockHotel $blockHotel): RedirectResponse
    {
        $blockHotel->handle($hotel, $request->string('reason')->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Hotel blocked.')]);

        return back();
    }

    /**
     * Lift the block.
     */
    public function destroy(Hotel $hotel, UnblockHotel $unblockHotel): RedirectResponse
    {
        Gate::authorize('block', $hotel);

        $unblockHotel->handle($hotel);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Hotel unblocked.')]);

        return back();
    }
}
