<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Province;
use App\Http\Controllers\Controller;
use App\Http\Resources\HotelSummaryResource;
use App\Models\Hotel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HotelController extends Controller
{
    /**
     * List every hotel with search, province and status filters.
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', Rule::enum(Province::class)],
            'status' => ['nullable', Rule::in(['visible', 'hidden', 'blocked'])],
        ]);

        $hotels = Hotel::query()
            ->with('owner')
            ->withCount('rooms')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(fn (Builder $query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('municipality', 'like', "%{$search}%")
                    ->orWhereHas('owner', fn (Builder $query) => $query->where('name', 'like', "%{$search}%")));
            })
            ->when($filters['province'] ?? null, fn (Builder $query, string $province) => $query->where('province', $province))
            ->when(($filters['status'] ?? null) === 'visible', fn (Builder $query) => $query->published())
            ->when(($filters['status'] ?? null) === 'hidden', fn (Builder $query) => $query->where('is_visible', false)->whereNull('blocked_at'))
            ->when(($filters['status'] ?? null) === 'blocked', fn (Builder $query) => $query->whereNotNull('blocked_at'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Hotel $hotel): array => [
                ...HotelSummaryResource::make($hotel)->resolve(),
                'province' => $hotel->province->label(),
                'municipality' => $hotel->municipality,
                'rooms_count' => $hotel->rooms_count,
            ]);

        return Inertia::render('admin/hotels/index', [
            'hotels' => $hotels,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'province' => $filters['province'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'provinces' => Province::options(),
        ]);
    }
}
