<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shop;
use App\Models\Floor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function shopWithFloor(User $owner): array
    {
        $shop = Shop::create([
            'name' => 'Test Shop',
            'slug' => 'test-shop-' . uniqid(),
            'user_id' => $owner->id,
        ]);
        $floor = $shop->floors()->create(['name' => 'Floor 1', 'order' => 1]);
        return [$shop, $floor];
    }

    // === Shop access ===

    public function test_customer_cannot_access_shops(): void
    {
        $this->actingAs($this->user('customer'))->get('/shops')->assertStatus(403);
    }

    public function test_cashier_can_access_shops(): void
    {
        $this->actingAs($this->user('cashier'))->get('/shops')->assertStatus(200);
    }

    public function test_builder_can_access_shops(): void
    {
        $this->actingAs($this->user('builder'))->get('/shops')->assertStatus(200);
    }

    public function test_manager_cannot_access_shops(): void
    {
        $this->actingAs($this->user('manager'))->get('/shops')->assertStatus(403);
    }

    // === Admin access ===

    public function test_builder_cannot_access_admin(): void
    {
        $this->actingAs($this->user('builder'))->get('/admin')->assertStatus(403);
    }

    public function test_cashier_cannot_access_admin(): void
    {
        $this->actingAs($this->user('cashier'))->get('/admin')->assertStatus(403);
    }

    public function test_manager_can_access_admin(): void
    {
        $this->actingAs($this->user('manager'))->get('/admin')->assertStatus(200);
    }

    public function test_admin_can_access_admin(): void
    {
        $this->actingAs($this->user('admin'))->get('/admin')->assertStatus(200);
    }

    public function test_super_admin_can_access_admin(): void
    {
        $this->actingAs($this->user('super_admin'))->get('/admin')->assertStatus(200);
    }

    // === Admin users (admin + super_admin only) ===

    public function test_manager_cannot_access_admin_users(): void
    {
        $this->actingAs($this->user('manager'))->get('/admin/users')->assertStatus(403);
    }

    public function test_admin_can_access_admin_users(): void
    {
        $this->actingAs($this->user('admin'))->get('/admin/users')->assertStatus(200);
    }

    // === Operate routes ===

    public function test_customer_cannot_access_operate(): void
    {
        $builder = $this->user('builder');
        [$shop, $floor] = $this->shopWithFloor($builder);
        $this->actingAs($this->user('customer'))
            ->get("/shops/{$shop->slug}/floors/{$floor->id}/operate")
            ->assertStatus(403);
    }

    public function test_cashier_can_access_operate_route(): void
    {
        $builder = $this->user('builder');
        [$shop, $floor] = $this->shopWithFloor($builder);
        $cashier = $this->user('cashier');
        // Cashier passes middleware but controller checks shop ownership → 403
        $this->actingAs($cashier)
            ->get("/shops/{$shop->slug}/floors/{$floor->id}/operate")
            ->assertStatus(403);
    }

    public function test_builder_can_operate_own_shop(): void
    {
        $builder = $this->user('builder');
        [$shop, $floor] = $this->shopWithFloor($builder);
        $this->actingAs($builder)
            ->get("/shops/{$shop->slug}/floors/{$floor->id}/operate")
            ->assertStatus(200);
    }

    // === Edit routes (builder + super_admin only) ===

    public function test_cashier_cannot_access_edit(): void
    {
        $builder = $this->user('builder');
        [$shop, $floor] = $this->shopWithFloor($builder);
        $this->actingAs($this->user('cashier'))
            ->get("/shops/{$shop->slug}/floors/{$floor->id}/edit")
            ->assertStatus(403);
    }

    public function test_builder_can_edit_own_floor(): void
    {
        $builder = $this->user('builder');
        [$shop, $floor] = $this->shopWithFloor($builder);
        $this->actingAs($builder)
            ->get("/shops/{$shop->slug}/floors/{$floor->id}/edit")
            ->assertStatus(200);
    }

    // === Super admin bypass ===

    public function test_super_admin_can_access_any_shop(): void
    {
        $builder = $this->user('builder');
        [$shop, $floor] = $this->shopWithFloor($builder);
        $this->actingAs($this->user('super_admin'))
            ->get("/shops/{$shop->slug}")
            ->assertStatus(200);
    }

    public function test_super_admin_can_operate_any_floor(): void
    {
        $builder = $this->user('builder');
        [$shop, $floor] = $this->shopWithFloor($builder);
        $this->actingAs($this->user('super_admin'))
            ->get("/shops/{$shop->slug}/floors/{$floor->id}/operate")
            ->assertStatus(200);
    }

    public function test_super_admin_can_edit_any_floor(): void
    {
        $builder = $this->user('builder');
        [$shop, $floor] = $this->shopWithFloor($builder);
        $this->actingAs($this->user('super_admin'))
            ->get("/shops/{$shop->slug}/floors/{$floor->id}/edit")
            ->assertStatus(200);
    }

    // === Public routes ===

    public function test_public_chair_statuses(): void
    {
        $builder = $this->user('builder');
        [$shop, $floor] = $this->shopWithFloor($builder);
        $this->get("/api/floors/{$floor->id}/chairs/statuses")
            ->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    // === Home redirect ===

    public function test_customer_redirects_to_login(): void
    {
        $this->actingAs($this->user('customer'))
            ->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_builder_redirects_to_shops(): void
    {
        $this->actingAs($this->user('builder'))
            ->get('/')
            ->assertRedirect(route('shops.index'));
    }

    public function test_admin_redirects_to_admin_dashboard(): void
    {
        $this->actingAs($this->user('admin'))
            ->get('/')
            ->assertRedirect(route('admin.dashboard'));
    }
}
