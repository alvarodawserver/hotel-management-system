<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Users\DeactivateUser;
use App\Actions\Users\ReactivateUser;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * List users with search, role and status filters.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'status' => ['nullable', Rule::in(['active', 'deactivated'])],
        ]);

        $users = User::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(fn (Builder $query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($filters['role'] ?? null, fn (Builder $query, string $role) => $query->where('role', $role))
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $query) => $query->whereNull('deactivated_at'))
            ->when(($filters['status'] ?? null) === 'deactivated', fn (Builder $query) => $query->whereNotNull('deactivated_at'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'role_label' => $user->role->label(),
                'is_active' => $user->isActive(),
                'created_at' => $user->created_at?->toDateString(),
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'role' => $filters['role'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'roles' => UserRole::options(),
        ]);
    }

    /**
     * Show the form for creating a user.
     */
    public function create(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('admin/users/create', [
            'roles' => UserRole::options(),
        ]);
    }

    /**
     * Store a newly created user with the chosen role.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = new User($request->safe()->only(['name', 'email', 'password']));
        $user->role = $request->enum('role', UserRole::class);
        // The admin vouches for the address, so the account works at once.
        $user->forceFill(['email_verified_at' => now()])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('admin.users.index');
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(Request $request, User $user): Response
    {
        Gate::authorize('update', $user);

        return Inertia::render('admin/users/edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->isActive(),
            ],
            'roles' => UserRole::options(),
            'canChangeRole' => $request->user()->can('changeRole', $user),
            'canDeactivate' => $request->user()->can('deactivate', $user),
        ]);
    }

    /**
     * Update a user's details and role.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $role = $request->enum('role', UserRole::class);

        if ($role !== $user->role) {
            Gate::authorize('changeRole', $user);
        }

        $user->fill($request->safe()->only(['name', 'email']));
        $user->role = $role;
        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('admin.users.index');
    }

    /**
     * Deactivate a user's account.
     */
    public function deactivate(User $user, DeactivateUser $deactivateUser): RedirectResponse
    {
        Gate::authorize('deactivate', $user);

        $deactivateUser->handle($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deactivated.')]);

        return back();
    }

    /**
     * Reactivate a user's account.
     */
    public function reactivate(User $user, ReactivateUser $reactivateUser): RedirectResponse
    {
        Gate::authorize('reactivate', $user);

        $reactivateUser->handle($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User reactivated.')]);

        return back();
    }
}
