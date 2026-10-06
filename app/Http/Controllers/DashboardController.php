<?php

namespace App\Http\Controllers;

use App\Actions\Dashboard\CalculateDashboardStats;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The management dashboard: owners see their hotels, admins the whole
     * platform. The numbers load after the page, in parallel groups.
     * Customers never see the management sidebar, so they go to the public
     * home page instead.
     */
    public function __invoke(Request $request, CalculateDashboardStats $stats): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->isCustomer()) {
            return to_route('home');
        }

        $today = Reservation::today()->toDateString();

        if ($user->isAdmin()) {
            return Inertia::render('dashboard', [
                'today' => $today,
                'kpis' => Inertia::defer(fn (): array => [
                    'revenue' => $stats->revenue($user),
                    'bookings' => $stats->bookings(),
                    'hotels' => $stats->hotelCounts(),
                    'new_users' => $stats->newUsers(),
                ]),
                'months' => Inertia::defer(fn (): array => $stats->monthlyRevenue($user)),
                'attention' => Inertia::defer(fn (): array => $stats->adminAttention(), 'lists'),
                'topHotels' => Inertia::defer(fn (): array => $stats->topHotels(), 'lists'),
                'provinces' => Inertia::defer(fn (): array => $stats->staysByProvince(), 'lists'),
            ]);
        }

        return Inertia::render('dashboard', [
            'today' => $today,
            'kpis' => Inertia::defer(fn (): array => [
                'revenue' => $stats->revenue($user),
                'occupancy' => $stats->occupancy($user),
                'arrivals' => $stats->arrivalCounts($user),
                'rating' => $stats->rating($user),
            ]),
            'months' => Inertia::defer(fn (): array => $stats->monthlyRevenue($user)),
            'attention' => Inertia::defer(fn (): array => $stats->ownerAttention($user), 'lists'),
            'arrivals' => Inertia::defer(fn (): array => $stats->upcomingArrivals($user), 'lists'),
        ]);
    }
}
