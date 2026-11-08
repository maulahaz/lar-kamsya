<?php

namespace App\Http\Controllers;

use App\Enums\GoatOrigin;
use App\Enums\GoatStatus;
use App\Http\Requests\GoatRequest;
use App\Models\Goat;
use App\Services\ActivityLogger;
use App\Services\GoatService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GoatController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Goat::class);

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $type = (string) $request->query('type', '');

        $goats = Goat::query()
            ->with(['purchase.supplier', 'activeSale.customer'])
            ->search($search)
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->orderBy('code')
            ->paginate(12)
            ->withQueryString();

        $types = Goat::query()->select('type')->distinct()->orderBy('type')->pluck('type');

        return view('goats.index', [
            'goats' => $goats,
            'types' => $types,
            'search' => $search,
            'status' => $status,
            'type' => $type,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Goat::class);

        return view('goats.create', [
            'mothers' => $this->motherOptions(),
        ]);
    }

    public function store(GoatRequest $request, GoatService $service)
    {
        $this->authorize('create', Goat::class);

        $data = $request->validated();
        $data['origin'] = GoatOrigin::Peranakan->value;

        $goat = $service->create($data, $request->user());

        return redirect()
            ->route('goats.show', $goat)
            ->with('success', 'Kambing '.$goat->code.' berhasil ditambahkan.');
    }

    public function show(Goat $goat)
    {
        $this->authorize('view', $goat);

        $goat->load([
            'purchase.supplier',
            'purchase.activePayments',
            'sales' => fn ($query) => $query->with(['customer', 'payments'])->orderByDesc('date'),
            'children',
            'mother',
            'scanLogs' => fn ($query) => $query->latest()->limit(6),
        ]);

        $sale = $goat->sales->firstWhere('voided_at', null);

        return view('goats.show', [
            'goat' => $goat,
            'sale' => $sale,
        ]);
    }

    public function edit(Goat $goat)
    {
        $this->authorize('update', $goat);

        return view('goats.edit', [
            'goat' => $goat,
            'mothers' => $this->motherOptions($goat->id),
        ]);
    }

    public function update(GoatRequest $request, Goat $goat, GoatService $service)
    {
        $this->authorize('update', $goat);

        $data = $request->validated();

        if ($goat->origin === GoatOrigin::Pembelian) {
            unset($data['origin'], $data['mother_id']);
        }

        $service->update($goat, $data, $request->user());

        return redirect()
            ->route('goats.show', $goat)
            ->with('success', 'Data kambing '.$goat->code.' berhasil diperbarui.');
    }

    public function destroy(Request $request, Goat $goat, GoatService $service)
    {
        $this->authorize('delete', $goat);

        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:255']])['reason'] ?? null;

        try {
            $service->archive($goat, $request->user(), $reason);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('goats.index')
            ->with('success', 'Kambing '.$goat->code.' berhasil diarsipkan.');
    }

    public function labels(Request $request)
    {
        $this->authorize('viewAny', Goat::class);

        $selected = collect($request->query('goats', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->all();

        $format = $request->query('format') === 'qr' ? 'qr' : 'barcode';

        $goats = Goat::query()
            ->when($selected, fn ($query) => $query->whereIn('id', $selected))
            ->orderBy('code')
            ->get();

        if (! $selected) {
            $goats = Goat::query()->available()->orderBy('code')->limit(3)->get();
        }

        return view('goats.labels', [
            'goats' => $goats,
            'selected' => $selected,
            'allGoats' => Goat::query()->orderBy('code')->get(),
            'format' => $format,
        ]);
    }

    private function motherOptions(?int $excludeId = null)
    {
        return Goat::query()
            ->where('gender', 'betina')
            ->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))
            ->orderBy('code')
            ->get();
    }
}
