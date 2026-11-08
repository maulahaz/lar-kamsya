<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\BackupService;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BackupTest extends TestCase
{
    private function owner(): User
    {
        return User::query()->where('email', 'owner@kamsya.id')->first();
    }

    private function createUploadedFile(string $filename, string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($filename, $content);
    }

    public function test_settings_export_returns_json(): void
    {
        $response = $this->actingAs($this->owner())->get(route('settings.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json; charset=UTF-8');
        $response->assertHeaderContains('Content-Disposition', 'attachment; filename=kamsya-backup-');
        $response->assertHeaderContains('Content-Disposition', '.json');

        $payload = json_decode($response->streamedContent(), true);
        $this->assertIsArray($payload);
        $this->assertSame('kamsya', $payload['meta']['app'] ?? null);
        $this->assertSame(1, $payload['meta']['version'] ?? null);
        $this->assertArrayHasKey('suppliers', $payload);
        $this->assertArrayHasKey('customers', $payload);
        $this->assertArrayHasKey('goats', $payload);
        $this->assertArrayHasKey('purchases', $payload);
        $this->assertArrayHasKey('sales', $payload);
        $this->assertArrayHasKey('payments', $payload);
        $this->assertArrayHasKey('expenses', $payload);
        $this->assertArrayHasKey('settings', $payload);
        $this->assertArrayHasKey('sequences', $payload);
    }

    public function test_keeper_cannot_export_settings(): void
    {
        $keeper = User::query()->where('email', 'petugas@kamsya.id')->first();

        $response = $this->actingAs($keeper)->get(route('settings.export'));

        $response->assertStatus(403);
    }

    public function test_admin_cannot_export_settings(): void
    {
        $admin = User::query()->where('email', 'admin@kamsya.id')->first();

        $response = $this->actingAs($admin)->get(route('settings.export'));

        $response->assertStatus(403);
    }

    public function test_backup_import_round_trip_preserves_data(): void
    {
        // Export current data
        $exportResponse = $this->actingAs($this->owner())->get(route('settings.export'));
        $payload = json_decode($exportResponse->streamedContent(), true);

        $this->assertSame(5, count($payload['suppliers']));
        $this->assertSame(8, count($payload['customers']));
        $this->assertSame(15, count($payload['goats']));
        $this->assertSame(10, count($payload['purchases']));
        $this->assertSame(5, count($payload['sales']));

        // Modify a setting through import
        $payload['settings'][0]['farm_name'] = 'Test Farm After Import';

        // Import the data
        $response = $this->actingAs($this->owner())->from(route('settings.index'))->post(route('settings.import'), [
            'backup' => $this->createUploadedFile(
                'import-test.json',
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            ),
        ]);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('success');

        // Verify data was restored
        $this->assertSame(5, \App\Models\Supplier::query()->count());
        $this->assertSame(8, \App\Models\Customer::query()->count());
        $this->assertSame(15, \App\Models\Goat::query()->count());
        $this->assertSame(10, \App\Models\Purchase::query()->count());
        $this->assertSame(5, \App\Models\Sale::query()->count());

        // Verify setting was updated
        $this->assertSame('Test Farm After Import', Setting::current()->farm_name);
    }

    public function test_backup_import_with_invalid_json_returns_error(): void
    {
        $response = $this->actingAs($this->owner())->from(route('settings.index'))->post(route('settings.import'), [
            'backup' => $this->createUploadedFile('invalid.json', 'not valid json {{{'),
        ]);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('error');
    }

    public function test_backup_import_with_wrong_app_returns_error(): void
    {
        $response = $this->actingAs($this->owner())->from(route('settings.index'))->post(route('settings.import'), [
            'backup' => $this->createUploadedFile(
                'wrong.json',
                json_encode(['meta' => ['app' => 'not-kamsya', 'version' => 1]])
            ),
        ]);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('error');
    }

    public function test_backup_import_without_file_returns_validation_error(): void
    {
        $response = $this->actingAs($this->owner())->from(route('settings.index'))->post(route('settings.import'), []);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHasErrors('backup');
    }

    public function test_backup_service_export_has_expected_structure(): void
    {
        $backup = app(BackupService::class);
        $payload = $backup->export();

        $this->assertSame('kamsya', $payload['meta']['app']);
        $this->assertSame(1, $payload['meta']['version']);
        $this->assertIsArray($payload['suppliers']);
        $this->assertIsArray($payload['customers']);
        $this->assertIsArray($payload['goats']);
        $this->assertIsArray($payload['purchases']);
        $this->assertIsArray($payload['sales']);
        $this->assertIsArray($payload['payments']);
        $this->assertIsArray($payload['expenses']);
        $this->assertIsArray($payload['settings']);
        $this->assertIsArray($payload['sequences']);
    }

    public function test_backup_service_sync_sequences_after_import(): void
    {
        $backup = app(BackupService::class);
        $sequences = app(\App\Services\SequenceService::class);

        // Get current sequence values
        $goatSeq = \App\Models\Sequence::query()->where('key', 'goat')->value('last_number');

        // Export and import
        $payload = $backup->export();
        $backup->import($payload);
        $backup->syncSequences($sequences);

        // Sequences should still be correct after import
        $this->assertSame($goatSeq, \App\Models\Sequence::query()->where('key', 'goat')->value('last_number'));
    }

    public function test_backup_service_preserves_user_references(): void
    {
        $backup = app(BackupService::class);
        $payload = $backup->export();

        $this->assertIsArray($payload['activity_logs']);

        // Activity logs should reference valid user IDs
        foreach ($payload['activity_logs'] as $log) {
            $userId = $log['user_id'] ?? null;
            if ($userId !== null) {
                $this->assertNotNull(User::query()->find($userId), "Activity log references invalid user {$userId}");
            }
        }
    }
}
