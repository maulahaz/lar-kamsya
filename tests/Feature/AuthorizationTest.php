<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    private const ROUTES_OWNER_ADMIN_ACCESSIBLE = [
        ['GET', 'purchases.index'],
        ['GET', 'sales.index'],
        ['GET', 'reports.index'],
        ['GET', 'expenses.index'],
        ['GET', 'suppliers.index'],
        ['GET', 'customers.index'],
        ['GET', 'purchases.create'],
        ['GET', 'sales.create'],
        ['GET', 'expenses.index'],
    ];

    private const ROUTES_OWNER_ONLY = [
        ['GET', 'users.index'],
        ['GET', 'settings.index'],
        ['GET', 'activity.index'],
    ];

    private const ROUTES_ALL_AUTHENTICATED = [
        ['GET', 'dashboard'],
        ['GET', 'goats.index'],
        ['GET', 'scan'],
        ['GET', 'goats.labels'],
        ['GET', 'profile.edit'],
    ];

    public function test_owner_can_access_all_routes(): void
    {
        $owner = User::query()->where('email', 'owner@kamsya.id')->first();

        foreach (self::ROUTES_ALL_AUTHENTICATED as [$method, $route]) {
            $response = $this->actingAs($owner)->{$method}(route($route));
            $response->assertStatus(200, "Owner should access $route");
        }

        foreach (self::ROUTES_OWNER_ADMIN_ACCESSIBLE as [$method, $route]) {
            $response = $this->actingAs($owner)->{$method}(route($route));
            $response->assertStatus(200, "Owner should access $route");
        }

        foreach (self::ROUTES_OWNER_ONLY as [$method, $route]) {
            $response = $this->actingAs($owner)->{$method}(route($route));
            $response->assertStatus(200, "Owner should access $route");
        }
    }

    public function test_admin_can_access_transaction_and_report_routes(): void
    {
        $admin = User::query()->where('email', 'admin@kamsya.id')->first();

        foreach (self::ROUTES_ALL_AUTHENTICATED as [$method, $route]) {
            $response = $this->actingAs($admin)->{$method}(route($route));
            $response->assertStatus(200, "Admin should access $route");
        }

        foreach (self::ROUTES_OWNER_ADMIN_ACCESSIBLE as [$method, $route]) {
            $response = $this->actingAs($admin)->{$method}(route($route));
            $response->assertStatus(200, "Admin should access $route");
        }

        // Admin cannot access owner-only routes
        foreach (self::ROUTES_OWNER_ONLY as [$method, $route]) {
            $response = $this->actingAs($admin)->{$method}(route($route));
            $response->assertStatus(403, "Admin should be denied $route");
        }
    }

    public function test_keeper_can_access_inventory_and_scan_only(): void
    {
        $keeper = User::query()->where('email', 'petugas@kamsya.id')->first();

        foreach (self::ROUTES_ALL_AUTHENTICATED as [$method, $route]) {
            $response = $this->actingAs($keeper)->{$method}(route($route));
            $response->assertStatus(200, "Keeper should access $route");
        }

        // Keeper cannot access transaction routes
        foreach (self::ROUTES_OWNER_ADMIN_ACCESSIBLE as [$method, $route]) {
            $response = $this->actingAs($keeper)->{$method}(route($route));
            $response->assertStatus(403, "Keeper should be denied $route");
        }

        // Keeper cannot access owner-only routes
        foreach (self::ROUTES_OWNER_ONLY as [$method, $route]) {
            $response = $this->actingAs($keeper)->{$method}(route($route));
            $response->assertStatus(403, "Keeper should be denied $route");
        }
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_access_protected_routes(): void
    {
        $routes = [
            'dashboard', 'goats.index', 'purchases.index', 'sales.index',
            'reports.index', 'settings.index', 'users.index',
        ];

        foreach ($routes as $route) {
            $response = $this->get(route($route));
            $response->assertRedirect('/login', "Guest should be redirected from $route");
        }
    }

    public function test_user_role_labels_are_correct(): void
    {
        $owner = User::query()->where('email', 'owner@kamsya.id')->first();
        $admin = User::query()->where('email', 'admin@kamsya.id')->first();
        $keeper = User::query()->where('email', 'petugas@kamsya.id')->first();

        $this->assertSame('Owner', $owner->role->label());
        $this->assertSame('Admin', $admin->role->label());
        $this->assertSame('Petugas Kandang', $keeper->role->label());

        $this->assertTrue($owner->isOwner());
        $this->assertFalse($admin->isOwner());
        $this->assertFalse($keeper->isOwner());

        $this->assertTrue($owner->canManageFinance());
        $this->assertTrue($admin->canManageFinance());
        $this->assertFalse($keeper->canManageFinance());
    }

    public function test_keeper_cannot_create_purchase(): void
    {
        $keeper = User::query()->where('email', 'petugas@kamsya.id')->first();
        $supplier = \App\Models\Supplier::query()->first();

        $response = $this->actingAs($keeper)->post(route('purchases.store'), [
            'date' => '2026-11-05',
            'supplier_id' => $supplier->id,
            'price' => '1000000',
            'additional_cost' => '50000',
            'goat' => [
                'name' => 'Test Goat',
                'type' => 'Boer',
                'gender' => 'jantan',
                'weight' => '30',
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_keeper_cannot_access_user_management(): void
    {
        $keeper = User::query()->where('email', 'petugas@kamsya.id')->first();

        $response = $this->actingAs($keeper)->get(route('users.index'));

        $response->assertStatus(403);
    }
}
