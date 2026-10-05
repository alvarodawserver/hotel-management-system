<?php

use App\Enums\UserRole;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

describe('index', function () {
    it('lists users for admins', function () {
        $admin = User::factory()->admin()->create(['name' => 'Ana Admin']);
        User::factory()->create(['name' => 'Carlos Cliente']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/index')
                ->has('users.data', 2)
                ->has('roles', 3));
    });

    it('filters users by name or email', function () {
        $admin = User::factory()->admin()->create(['name' => 'Ana Admin']);
        User::factory()->create(['name' => 'Carlos Cliente']);
        User::factory()->create(['name' => 'Otro', 'email' => 'carlos@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'carlos']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 2)
                ->where('filters.search', 'carlos'));
    });

    it('filters users by role', function () {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->owner()->create();
        User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['role' => 'owner']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.id', $owner->id));
    });

    it('filters users by status', function () {
        $admin = User::factory()->admin()->create();
        $deactivated = User::factory()->deactivated()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['status' => 'deactivated']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.id', $deactivated->id)
                ->where('users.data.0.is_active', false));
    });
});

describe('store', function () {
    it('creates a user with the chosen role', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Paula Propietaria',
                'email' => 'paula@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => UserRole::Owner->value,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.users.index'))
            ->assertInertiaFlash('toast.type', 'success');

        $user = User::where('email', 'paula@example.com')->sole();

        expect($user->role)->toBe(UserRole::Owner)
            ->and(Hash::check('password', $user->password))->toBeTrue()
            ->and($user->hasVerifiedEmail())->toBeTrue();
    });

    it('rejects an unknown role', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Paula',
                'email' => 'paula@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'superadmin',
            ])
            ->assertSessionHasErrors('role');

        expect(User::where('email', 'paula@example.com')->exists())->toBeFalse();
    });

    it('rejects a duplicated email', function () {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Paula',
                'email' => 'taken@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => UserRole::Customer->value,
            ])
            ->assertSessionHasErrors('email');
    });
});

describe('update', function () {
    it('updates the details and role of another user', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Nuevo Nombre',
                'email' => 'nuevo@example.com',
                'role' => UserRole::Owner->value,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        expect($user->refresh())
            ->name->toBe('Nuevo Nombre')
            ->email->toBe('nuevo@example.com')
            ->role->toBe(UserRole::Owner);
    });

    it('lets admins update their own details while keeping their role', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => 'Admin Renombrado',
                'email' => $admin->email,
                'role' => UserRole::Admin->value,
            ])
            ->assertSessionHasNoErrors();

        expect($admin->refresh()->name)->toBe('Admin Renombrado');
    });

    it('prevents changing the role of an owner who still has hotels', function () {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->owner()->create();
        Hotel::factory()->for($owner, 'owner')->create();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $owner), [
                'name' => $owner->name,
                'email' => $owner->email,
                'role' => UserRole::Customer->value,
            ])
            ->assertSessionHasErrors('role');

        expect($owner->refresh()->role)->toBe(UserRole::Owner);
    });

    it('prevents admins from changing their own role', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => UserRole::Customer->value,
            ])
            ->assertRedirect(route('dashboard'))
            ->assertInertiaFlash('toast.message', __('You cannot change your own role.'));

        expect($admin->refresh()->role)->toBe(UserRole::Admin);
    });
});

describe('deactivate', function () {
    it('deactivates another user', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.deactivate', $user))
            ->assertRedirect(route('admin.users.index'))
            ->assertInertiaFlash('toast.type', 'success');

        expect($user->refresh()->isActive())->toBeFalse();
    });

    it('prevents admins from deactivating themselves', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.deactivate', $admin))
            ->assertRedirect(route('dashboard'))
            ->assertInertiaFlash('toast.message', __('You cannot deactivate your own account from the admin panel.'));

        expect($admin->refresh()->isActive())->toBeTrue();
    });

    it('refuses to deactivate an owner who still has hotels', function () {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->owner()->create();
        Hotel::factory()->for($owner, 'owner')->count(2)->create();

        $this->actingAs($admin)
            ->post(route('admin.users.deactivate', $owner))
            ->assertSessionHasErrors('user');

        expect($owner->refresh()->isActive())->toBeTrue();
    });

    it('deactivates an owner whose hotels have all been deleted', function () {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->owner()->create();
        Hotel::factory()->for($owner, 'owner')->create()->delete();

        $this->actingAs($admin)
            ->post(route('admin.users.deactivate', $owner))
            ->assertSessionHasNoErrors();

        expect($owner->refresh()->isActive())->toBeFalse();
    });

    it('refuses to deactivate a customer with an upcoming reservation', function () {
        $admin = User::factory()->admin()->create();
        $customer = Reservation::factory()->stay(10)->create()->user;

        $this->actingAs($admin)
            ->post(route('admin.users.deactivate', $customer))
            ->assertSessionHasErrors(['user' => 'The customer has 1 active reservation; it must end or be cancelled before deactivating the account.']);

        expect($customer->refresh()->isActive())->toBeTrue();
    });

    it('deactivates a customer whose reservations are over or cancelled', function () {
        $admin = User::factory()->admin()->create();
        $customer = Reservation::factory()->stay(-10)->create()->user;
        Reservation::factory()->for($customer)->cancelled()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.deactivate', $customer))
            ->assertSessionHasNoErrors();

        expect($customer->refresh()->isActive())->toBeFalse();
    });

    it('rejects deactivating an account that is already deactivated', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->deactivated()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.deactivate', $user))
            ->assertSessionHasErrors(['user' => __('This account is already deactivated.')]);
    });
});

describe('reactivate', function () {
    it('reactivates a deactivated user', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->deactivated()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.reactivate', $user))
            ->assertRedirect(route('admin.users.index'));

        expect($user->refresh()->isActive())->toBeTrue();
    });
});
