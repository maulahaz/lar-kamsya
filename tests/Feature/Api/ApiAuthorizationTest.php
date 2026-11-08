<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAuthorizationTest extends TestCase
{
    private function user(string $email): User
    {
        return User::query()->where('email', $email)->firstOrFail();
    }

    public function test_keeper_can_access_inventory_read_endpoints(): void
    {
        Sanctum::actingAs($this->user('petugas@kamsya.id'));

        foreach ([
            '/api/v1/dashboard',
            '/api/v1/goats',
            '/api/v1/goats/KMB-0001',
            '/api/v1/scan?code=899100001',
            '/api/v1/options',
        ] as $uri) {
            $this->getJson($uri)->assertStatus(200, "Keeper should access $uri");
        }
    }

    public function test_keeper_is_denied_finance_endpoints(): void
    {
        Sanctum::actingAs($this->user('petugas@kamsya.id'));

        foreach ([
            '/api/v1/purchases',
            '/api/v1/sales',
            '/api/v1/payments',
            '/api/v1/expenses',
            '/api/v1/reports',
            '/api/v1/suppliers',
            '/api/v1/customers',
            '/api/v1/users',
            '/api/v1/settings',
            '/api/v1/activity-logs',
        ] as $uri) {
            $this->getJson($uri)->assertStatus(403, "Keeper should be denied $uri");
        }
    }

    public function test_admin_can_access_finance_but_not_owner_only_endpoints(): void
    {
        Sanctum::actingAs($this->user('admin@kamsya.id'));

        foreach ([
            '/api/v1/purchases',
            '/api/v1/sales',
            '/api/v1/payments',
            '/api/v1/expenses',
            '/api/v1/reports',
            '/api/v1/suppliers',
            '/api/v1/customers',
        ] as $uri) {
            $this->getJson($uri)->assertStatus(200, "Admin should access $uri");
        }

        foreach ([
            '/api/v1/users',
            '/api/v1/settings',
            '/api/v1/activity-logs',
        ] as $uri) {
            $this->getJson($uri)->assertStatus(403, "Admin should be denied $uri");
        }
    }

    public function test_owner_can_access_owner_only_endpoints(): void
    {
        Sanctum::actingAs($this->user('owner@kamsya.id'));

        foreach ([
            '/api/v1/users',
            '/api/v1/settings',
            '/api/v1/activity-logs',
        ] as $uri) {
            $this->getJson($uri)->assertStatus(200, "Owner should access $uri");
        }
    }

    public function test_unauthenticated_requests_receive_json_401(): void
    {
        $response = $this->getJson('/api/v1/goats');

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Sesi berakhir atau token tidak valid. Silakan masuk kembali.');
    }
}
