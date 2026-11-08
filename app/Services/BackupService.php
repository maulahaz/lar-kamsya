<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackupService
{
    /**
     * Tables included in the backup, ordered children-first for deletion.
     *
     * @var array<int, string>
     */
    private const TABLES = [
        'activity_logs',
        'scan_logs',
        'payments',
        'expenses',
        'sales',
        'purchases',
        'goats',
        'customers',
        'suppliers',
        'sequences',
        'settings',
    ];

    /**
     * Build the export payload as a plain array (JSON serialisable).
     *
     * @return array<string, mixed>
     */
    public function export(): array
    {
        $data = [
            'meta' => [
                'app' => 'kamsya',
                'version' => 1,
                'exported_at' => now()->toIso8601String(),
                'farm_name' => Setting::current()->farm_name,
            ],
        ];

        foreach (array_reverse(self::TABLES) as $table) {
            $data[$table] = DB::table($table)->orderBy('id')->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        return $data;
    }

    /**
     * Import a decoded backup payload, replacing all domain data.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, int> number of rows restored per table
     *
     * @throws \InvalidArgumentException
     */
    public function import(array $payload): array
    {
        if (($payload['meta']['app'] ?? null) !== 'kamsya') {
            throw new \InvalidArgumentException('Berkas bukan cadangan Kamsya yang valid.');
        }

        if ((int) ($payload['meta']['version'] ?? 0) < 1) {
            throw new \InvalidArgumentException('Versi berkas cadangan tidak didukung.');
        }

        $validUsers = DB::table('users')->pluck('id')->map(fn ($id) => (int) $id)->all();
        $validUserIds = array_flip($validUsers);
        $driver = DB::getDriverName();
        $restored = [];

        $this->disableForeignKeys($driver);

        try {
            DB::transaction(function () use ($payload, $validUserIds, &$restored) {
                foreach (self::TABLES as $table) {
                    DB::table($table)->delete();
                }

                foreach (array_reverse(self::TABLES) as $table) {
                    $rows = $payload[$table] ?? [];
                    if (! is_array($rows) || $rows === []) {
                        $restored[$table] = 0;

                        continue;
                    }

                    $prepared = array_map(
                        fn ($row) => $this->prepareRow($table, (array) $row, $validUserIds),
                        $rows
                    );

                    foreach (array_chunk($prepared, 200) as $chunk) {
                        DB::table($table)->insert($chunk);
                    }

                    $restored[$table] = count($prepared);
                }
            });
        } finally {
            $this->enableForeignKeys($driver);
        }

        Setting::current();

        return $restored;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, int>  $validUserIds
     * @return array<string, mixed>
     */
    private function prepareRow(string $table, array $row, array $validUserIds): array
    {
        if ($table === 'activity_logs') {
            if (isset($row['properties']) && is_array($row['properties'])) {
                $row['properties'] = json_encode($row['properties'], JSON_UNESCAPED_UNICODE);
            }

            if (! isset($validUserIds[(int) ($row['user_id'] ?? 0)])) {
                $row['user_id'] = null;
            }
        }

        if ($table === 'scan_logs' && ! isset($validUserIds[(int) ($row['user_id'] ?? 0)])) {
            $row['user_id'] = null;
        }

        if (in_array($table, ['purchases', 'sales', 'payments', 'expenses'], true)) {
            foreach (['created_by', 'voided_by'] as $column) {
                if (array_key_exists($column, $row) && ! isset($validUserIds[(int) ($row[$column] ?? 0)])) {
                    $row[$column] = null;
                }
            }
        }

        return $row;
    }

    public function disableForeignKeys(string $driver): void
    {
        match ($driver) {
            'mysql', 'mariadb' => DB::statement('SET FOREIGN_KEY_CHECKS=0'),
            // `PRAGMA foreign_keys` cannot change inside a transaction (which is
            // how RefreshDatabase runs tests), so defer checks until commit.
            'sqlite' => DB::statement('PRAGMA defer_foreign_keys = ON'),
            'pgsql' => DB::statement("SET session_replication_role = 'replica'"),
            default => null,
        };
    }

    public function enableForeignKeys(string $driver): void
    {
        match ($driver) {
            'mysql', 'mariadb' => DB::statement('SET FOREIGN_KEY_CHECKS=1'),
            'sqlite' => DB::statement('PRAGMA defer_foreign_keys = OFF'),
            'pgsql' => DB::statement("SET session_replication_role = 'origin'"),
            default => null,
        };
    }

    /**
     * Recalculate sequence counters from the current data (used after imports).
     */
    public function syncSequences(SequenceService $sequences): void
    {
        $values = DB::table('sequences')->pluck('last_number', 'key')
            ->map(fn ($number) => (int) $number)
            ->all();

        $identityTables = [
            'goats' => ['key' => 'goat', 'pattern' => '/^KMB-(\d+)$/i'],
            'suppliers' => ['key' => 'supplier', 'pattern' => '/^SUP-(\d+)$/i'],
            'customers' => ['key' => 'customer', 'pattern' => '/^CUS-(\d+)$/i'],
        ];

        foreach ($identityTables as $table => $config) {
            $max = 0;
            foreach (DB::table($table)->whereNotNull('code')->pluck('code') as $code) {
                if (preg_match($config['pattern'], (string) $code, $matches)) {
                    $max = max($max, (int) $matches[1]);
                }
            }

            $values[$config['key']] = $max;
        }

        foreach (['purchases' => 'purchase', 'sales' => 'sale', 'payments' => 'payment', 'expenses' => 'expense'] as $table => $prefix) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $codes = DB::table($table)->whereNotNull('code')->pluck('code');

            foreach ($codes as $code) {
                if (! preg_match('/-(\d{4})-(\d+)$/', (string) $code, $matches)) {
                    continue;
                }

                $year = $matches[1];
                $number = (int) $matches[2];
                $key = $prefix.':'.$year;

                $values[$key] = max($values[$key] ?? 0, $number);
            }
        }

        $sequences->sync($values);
    }
}
