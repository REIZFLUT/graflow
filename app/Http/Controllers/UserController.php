<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->latest()
            ->paginate(15);

        return Inertia::render('users/index', [
            'users' => $users,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('users/create', [
            'roles' => $this->roleOptions(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = User::query()->create([
            ...$request->safe()->only([
                'name',
                'email',
                'password',
                'role',
            ]),
            'email_verified_at' => now(),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.users.created'),
        ]);

        return to_route('users.edit', $user);
    }

    public function edit(User $user): Response
    {
        $this->authorize('view', $user);

        return Inertia::render('users/edit', [
            'user' => $user->only(['id', 'name', 'email', 'role', 'created_at', 'updated_at']),
            'roles' => $this->roleOptions(),
            'can_delete' => ! $user->hasBlockingRelationships()
                && ! $this->isLastAdmin($user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->safe()->only(['name', 'email', 'role']);
        $newRole = UserRole::from($validated['role']);

        if ($this->isLastAdmin($user) && $newRole !== UserRole::Admin) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('messages.users.cannot_demote_last_admin'),
            ]);

            return to_route('users.edit', $user);
        }

        $user->fill([
            ...$validated,
            'role' => $newRole,
        ]);

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        $user->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.users.saved'),
        ]);

        return to_route('users.edit', $user);
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($this->isLastAdmin($user)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('messages.users.cannot_delete_last_admin'),
            ]);

            return to_route('users.edit', $user);
        }

        if ($user->hasBlockingRelationships()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('messages.users.has_related_records'),
            ]);

            return to_route('users.edit', $user);
        }

        $user->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('messages.users.deleted'),
        ]);

        return to_route('users.index');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        return collect(UserRole::cases())
            ->map(fn (UserRole $role): array => [
                'value' => $role->value,
                'label' => __('users.roles.'.$role->value),
            ])
            ->values()
            ->all();
    }

    private function isLastAdmin(User $user): bool
    {
        if ($user->role !== UserRole::Admin) {
            return false;
        }

        return User::query()
            ->where('role', UserRole::Admin)
            ->count() === 1;
    }
}
