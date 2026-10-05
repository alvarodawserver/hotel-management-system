<?php

use App\Models\User;

test('only admins can list, create, update and reactivate users', function (User $actor, bool $allowed) {
    $target = User::factory()->create();

    expect($actor->can('viewAny', User::class))->toBe($allowed)
        ->and($actor->can('create', User::class))->toBe($allowed)
        ->and($actor->can('update', $target))->toBe($allowed)
        ->and($actor->can('reactivate', $target))->toBe($allowed)
        ->and($actor->can('changeRole', $target))->toBe($allowed)
        ->and($actor->can('deactivate', $target))->toBe($allowed);
})->with([
    'admin' => [fn () => User::factory()->admin()->create(), true],
    'owner' => [fn () => User::factory()->owner()->create(), false],
    'customer' => [fn () => User::factory()->create(), false],
]);

test('admins cannot change their own role', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('changeRole', $admin))->toBeFalse();
});

test('admins cannot deactivate themselves', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('deactivate', $admin))->toBeFalse();
});
