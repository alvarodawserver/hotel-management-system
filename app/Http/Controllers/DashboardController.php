<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The management dashboard for owners and admins. Customers never see
     * the management sidebar, so they go to the public home page instead.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if ($request->user()->isCustomer()) {
            return to_route('home');
        }

        return Inertia::render('dashboard');
    }
}
