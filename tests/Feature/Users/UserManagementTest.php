<?php

namespace Tests\Feature\Users;

use App\Enums\UserRole;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_users_index(): void
    {
        $user = User::factory()->author()->create();

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_users_index(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->author()->create(['name' => 'Author One']);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('users/index')
                ->has('users.data', 2));
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'New Editor',
                'email' => 'editor@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => UserRole::Editor->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'New Editor',
            'email' => 'editor@example.com',
            'role' => UserRole::Editor->value,
        ]);
    }

    public function test_admin_can_update_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->author()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $this->actingAs($admin)
            ->put(route('users.update', $user), [
                'name' => 'New Name',
                'email' => 'new@example.com',
                'role' => UserRole::Lector->value,
            ])
            ->assertRedirect(route('users.edit', $user));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
            'role' => UserRole::Lector->value,
        ]);
    }

    public function test_admin_can_demote_another_admin_when_more_than_one_exists(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('users.update', $otherAdmin), [
                'name' => $otherAdmin->name,
                'email' => $otherAdmin->email,
                'role' => UserRole::Editor->value,
            ])
            ->assertRedirect(route('users.edit', $otherAdmin));

        $this->assertDatabaseHas('users', [
            'id' => $otherAdmin->id,
            'role' => UserRole::Editor->value,
        ]);
    }

    public function test_admin_can_delete_user_without_relationships(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->author()->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $user))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    public function test_admin_cannot_edit_or_update_self(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('users.edit', $admin))
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => UserRole::Author->value,
            ])
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->role === UserRole::Admin);
    }

    public function test_admin_cannot_delete_user_with_owned_articles(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->author()->create();
        Article::factory()->create([
            'owner_id' => $author->id,
            'author_id' => $author->id,
            'product_manager_id' => User::factory()->productManager(),
            'current_assignee_id' => $author->id,
        ]);

        $this->actingAs($admin)
            ->from(route('users.edit', $author))
            ->delete(route('users.destroy', $author))
            ->assertRedirect(route('users.edit', $author));

        $this->assertDatabaseHas('users', [
            'id' => $author->id,
        ]);
    }

    public function test_manage_users_shared_prop_is_true_for_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('can.manageUsers', true));
    }

    public function test_manage_users_shared_prop_is_false_for_non_admin(): void
    {
        $user = User::factory()->editor()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('can.manageUsers', false));
    }
}
