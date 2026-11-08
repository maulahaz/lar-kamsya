<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingRequest;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingController extends Controller
{
    public function index()
    {
        Gate::authorize('manage-settings');

        $stats = [
            'goats' => Goat::query()->count(),
            'available' => Goat::query()->available()->count(),
            'purchases' => Purchase::query()->count(),
            'sales' => Sale::query()->count(),
            'payments' => Payment::query()->count(),
            'expenses' => Expense::query()->count(),
            'suppliers' => Supplier::query()->count(),
            'customers' => Customer::query()->count(),
            'users' => User::query()->count(),
        ];

        return view('settings.index', [
            'settings' => Setting::current(),
            'stats' => $stats,
            'nextBarcode' => $this->nextBarcodeSample(),
        ]);
    }

    public function update(SettingRequest $request, ActivityLogger $logger)
    {
        Gate::authorize('manage-settings');

        $settings = Setting::current();
        $settings->update($request->validated());

        $logger->log('update', $settings, 'Memperbarui pengaturan peternakan');

        return redirect()
            ->route('settings.index')
            ->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function export(BackupService $backup, ActivityLogger $logger): StreamedResponse
    {
        Gate::authorize('manage-settings');

        $payload = $backup->export();
        $filename = 'kamsya-backup-'.now()->format('Ymd-His').'.json';
        $logger->log('export', null, 'Mengekspor cadangan data ke '.$filename);

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $filename, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public function import(Request $request, BackupService $backup, SequenceService $sequences, ActivityLogger $logger)
    {
        Gate::authorize('manage-settings');

        $request->validate([
            'backup' => ['required', 'file', 'max:20480'],
        ], [
            'backup.required' => 'Pilih berkas cadangan terlebih dahulu.',
            'backup.max' => 'Ukuran berkas cadangan maksimal 20 MB.',
        ]);

        $contents = (string) file_get_contents($request->file('backup')->getRealPath());
        $payload = json_decode($contents, true);

        if (! is_array($payload)) {
            return back()->with('error', 'Berkas cadangan tidak dapat dibaca. Pastikan format JSON valid.');
        }

        try {
            $restored = $backup->import($payload);
            $backup->syncSequences($sequences);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Impor gagal: '.$exception->getMessage());
        }

        $total = array_sum($restored);
        $logger->log('import', null, 'Mengimpor cadangan data ('.$total.' baris)');

        return redirect()
            ->route('settings.index')
            ->with('success', 'Cadangan berhasil diimpor. '.$total.' baris data dipulihkan.');
    }

    private function nextBarcodeSample(): string
    {
        $settings = Setting::current();
        $prefix = preg_replace('/\D/', '', (string) $settings->barcode_prefix) ?: '8991';
        $last = (int) (DB::table('sequences')->where('key', 'goat')->value('last_number') ?? 0);

        return $prefix.str_pad((string) ($last + 1), 5, '0', STR_PAD_LEFT);
    }
}
