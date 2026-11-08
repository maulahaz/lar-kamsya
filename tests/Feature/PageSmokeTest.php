<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use Tests\TestCase;

class PageSmokeTest extends TestCase
{
    /**
     * @var array<string, array<int, string>> Routes expected to return 200 per role
     */
    private const OWNER_200_ROUTES = [
        'dashboard', 'goats.index', 'scan', 'goats.labels',
        'purchases.index', 'purchases.create', 'sales.index', 'sales.create',
        'expenses.index',
        'suppliers.index', 'customers.index',
        'reports.index',
        'users.index',
        'activity.index',
        'settings.index',
        'profile.edit',
    ];

    private const ADMIN_200_ROUTES = [
        'dashboard', 'goats.index', 'scan', 'goats.labels',
        'purchases.index', 'purchases.create', 'sales.index', 'sales.create',
        'expenses.index',
        'suppliers.index', 'customers.index',
        'reports.index',
        'profile.edit',
    ];

    private const KEEPER_200_ROUTES = [
        'dashboard', 'goats.index', 'scan', 'goats.labels',
        'profile.edit',
    ];

    private const ROUTES_WITH_ID_PARAM = [
        ['goats.show', 'KMB-0001'],
        ['purchases.show', 'TRX-B-2026-001'],
        ['sales.show', 'TRX-J-2026-001'],
    ];

    public function test_owner_can_access_all_pages(): void
    {
        $owner = User::query()->where('email', 'owner@kamsya.id')->first();

        foreach (self::OWNER_200_ROUTES as $route) {
            $response = $this->actingAs($owner)->get(route($route));
            $response->assertStatus(200, "Owner should access $route (got {$response->getStatusCode()})");
        }
    }

    public function test_admin_can_access_allowed_pages(): void
    {
        $admin = User::query()->where('email', 'admin@kamsya.id')->first();

        foreach (self::ADMIN_200_ROUTES as $route) {
            $response = $this->actingAs($admin)->get(route($route));
            $response->assertStatus(200, "Admin should access $route");
        }

        // Admin cannot access owner-only routes
        $ownerOnlyRoutes = ['users.index', 'settings.index', 'activity.index'];
        foreach ($ownerOnlyRoutes as $route) {
            $response = $this->actingAs($admin)->get(route($route));
            $response->assertStatus(403, "Admin should be denied $route");
        }
    }

    public function test_keeper_can_access_allowed_pages(): void
    {
        $keeper = User::query()->where('email', 'petugas@kamsya.id')->first();

        foreach (self::KEEPER_200_ROUTES as $route) {
            $response = $this->actingAs($keeper)->get(route($route));
            $response->assertStatus(200, "Keeper should access $route");
        }

        // Keeper cannot access transaction routes
        $deniedRoutes = ['purchases.index', 'sales.index', 'expenses.index', 'reports.index', 'users.index', 'settings.index'];
        foreach ($deniedRoutes as $route) {
            $response = $this->actingAs($keeper)->get(route($route));
            $response->assertStatus(403, "Keeper should be denied $route");
        }
    }

    public function test_show_pages_with_existing_records(): void
    {
        $owner = User::query()->where('email', 'owner@kamsya.id')->first();

        foreach (self::ROUTES_WITH_ID_PARAM as [$route, $code]) {
            $response = $this->actingAs($owner)->get(route($route, $code));
            $response->assertStatus(200, "Route $route with code $code should return 200");
        }
    }

    public function test_create_pages_for_transactions(): void
    {
        $owner = User::query()->where('email', 'owner@kamsya.id')->first();

        $createRoutes = ['purchases.create', 'sales.create'];
        foreach ($createRoutes as $route) {
            $response = $this->actingAs($owner)->get(route($route));
            $response->assertStatus(200, "Route $route should return 200");
        }
    }

    public function test_settings_page_has_backup_ui(): void
    {
        $owner = User::query()->where('email', 'owner@kamsya.id')->first();

        $response = $this->actingAs($owner)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Ekspor Data');
        $response->assertSee('Impor Data');
        $response->assertSee('Cadangan');
    }

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk');
    }

    public function test_unauthenticated_access_to_all_routes_redirects(): void
    {
        $routes = ['dashboard', 'goats.index', 'purchases.index', 'reports.index', 'settings.index', 'users.index'];

        foreach ($routes as $route) {
            $response = $this->get(route($route));
            $response->assertRedirect('/login', "Unauthenticated access to $route should redirect");
        }
    }

    public function test_all_role_labels_display_correctly(): void
    {
        $users = User::query()->get();

        foreach ($users as $user) {
            $this->assertNotEmpty($user->role->label());
            $this->assertNotEmpty($user->role->description());
            $this->assertNotEmpty($user->role->badge());
        }
    }

    public function test_can_access_user_detail_pages(): void
    {
        $owner = User::query()->where('email', 'owner@kamsya.id')->first();
        $admin = User::query()->where('email', 'admin@kamsya.id')->first();

        // Owner can edit any user
        $response = $this->actingAs($owner)->get(route('users.index'));
        $response->assertStatus(200);

        // Admin cannot access users page
        $response = $this->actingAs($admin)->get(route('users.index'));
        $response->assertStatus(403);
    }

    public function test_activity_log_page_is_owner_only(): void
    {
        $owner = User::query()->where('email', 'owner@kamsya.id')->first();
        $admin = User::query()->where('email', 'admin@kamsya.id')->first();

        $response = $this->actingAs($owner)->get(route('activity.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($admin)->get(route('activity.index'));
        $response->assertStatus(403);
    }

    public function test_report_tabs_all_load(): void
    {
        $owner = User::query()->where('email', 'owner@kamsya.id')->first();

        $tabs = ['laba-rugi', 'arus-kas', 'piutang', 'utang', 'laba-kambing', 'penjualan', 'pembelian', 'stok'];

        foreach ($tabs as $tab) {
            $response = $this->actingAs($owner)->get(route('reports.index', ['tab' => $tab]));
            $response->assertStatus(200, "Report tab $tab should load");
        }
    }
}
