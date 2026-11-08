<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\SettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Goat;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\BackupService;
use App\Services\SequenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SettingController extends BaseController
{
    public function show(): JsonResponse
    {
        Gate::authorize('manage-settings');

        return response()->json([
            'data' => [
                'settings' => new SettingResource(Setting::current()),
                'stats' => [
                    'goats' => Goat::query()->count(),
                    'available' => Goat::query()->available()->count(),
                    'purchases' => Purchase::query()->count(),
                    'sales' => Sale::query()->count(),
                    'payments' => Payment::query()->count(),
                    'expenses' => Expense::query()->count(),
                    'suppliers' => Supplier::query()->count(),
                    'customers' => Customer::query()->count(),
                    'users' => User::query()->count(),
                ],
                'next_barcode' => $this->nextBarcodeSample(),
            ],
        ]);
    }

    public function update(SettingRequest $request, ActivityLogger $logger): JsonResponse
    {
        Gate::authorize('manage-settings');

        $settings = Setting::current();
        $settings->update($request->validated());

        $logger->log('update', $settings, 'Memperbarui pengaturan peternakan');

        return response()->json([
            'message' => 'Pengaturan berhasil disimpan.',
            'data' => new SettingResource($settings->fresh()),
        ]);
    }

    public function export(BackupService $backup, ActivityLogger $logger): JsonResponse
    {
        Gate::authorize('manage-settings');

        $payload = $backup->export();
        $filename = 'kamsya-backup-'.now()->format('Ymd-His').'.json';

        $logger->log('export', null, 'Mengekspor cadangan data ke '.$filename);

        return response()
            ->json($payload)
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function import(Request $request, BackupService $backup, SequenceService $sequences, ActivityLogger $logger): JsonResponse
    {
        Gate::authorize('manage-settings');

        $request->validate([
            'backup' => ['required_without:payload', 'file', 'max:20480'],
            'payload' => ['required_without:backup', 'array'],
        ], [
            'backup.required_without' => 'Pilih berkas cadangan atau kirim payload terlebih dahulu.',
            'backup.max' => 'Ukuran berkas cadangan maksimal 20 MB.',
            'payload.required_without' => 'Pilih berkas cadangan atau kirim payload terlebih dahulu.',
        ]);

        if ($request->hasFile('backup')) {
            $contents = (string) file_get_contents($request->file('backup')->getRealPath());
            $payload = json_decode($contents, true);

            if (! is_array($payload)) {
                return $this->message('Berkas cadangan tidak dapat dibaca. Pastikan format JSON valid.', 422);
            }
        } else {
            $payload = (array) $request->input('payload');
        }

        try {
            $restored = $backup->import($payload);
            $backup->syncSequences($sequences);
        } catch (\Throwable $exception) {
            report($exception);

            return $this->message('Impor gagal: '.$exception->getMessage(), 422);
        }

        $total = array_sum($restored);
        $logger->log('import', null, 'Mengimpor cadangan data ('.$total.' baris)');

        return response()->json([
            'message' => 'Cadangan berhasil diimpor. '.$total.' baris data dipulihkan.',
            'restored' => $restored,
            'total' => $total,
        ]);
    }

    private function nextBarcodeSample(): string
    {
        $settings = Setting::current();
        $prefix = preg_replace('/\D/', '', (string) $settings->barcode_prefix) ?: '8991';
        $last = (int) (DB::table('sequences')->where('key', 'goat')->value('last_number') ?? 0);

        return $prefix.str_pad((string) ($last + 1), 5, '0', STR_PAD_LEFT);
    }
}
