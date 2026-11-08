<?php

namespace App\Http\Controllers;

use App\Models\Goat;
use App\Models\ScanLog;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->authorize('viewAny', Goat::class);

        $code = trim((string) $request->query('code', ''));
        $goat = null;
        $found = null;

        if ($code !== '') {
            $normalized = strtoupper(preg_replace('/\s+/', '', $code));

            $goat = Goat::query()
                ->with(['purchase.supplier', 'activeSale.customer', 'mother'])
                ->where(function ($query) use ($normalized) {
                    $query->where('barcode', $normalized)->orWhere('code', $normalized);
                })
                ->first();

            $found = $goat !== null;

            ScanLog::query()->create([
                'user_id' => $request->user()->id,
                'goat_id' => $goat?->id,
                'barcode' => $normalized,
                'found' => $found,
                'ip_address' => $request->ip(),
            ]);
        }

        $history = ScanLog::query()
            ->with('goat')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->limit(8)
            ->get();

        return view('scan.index', [
            'code' => $code,
            'goat' => $goat,
            'found' => $found,
            'history' => $history,
            'samples' => Goat::query()->available()->orderBy('code')->limit(3)->get(),
        ]);
    }
}
