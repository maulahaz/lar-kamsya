<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\ExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Expense::class);

        $search = trim((string) $request->query('q', ''));
        $category = (string) $request->query('category', '');
        $filter = (string) $request->query('status', '');

        $query = Expense::query()
            ->with('creator')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(fn ($query) => $query
                    ->where('code', 'like', $like)
                    ->orWhere('notes', 'like', $like));
            })
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($filter === 'voided', fn ($query) => $query->whereNotNull('voided_at'))
            ->when($filter === 'active', fn ($query) => $query->whereNull('voided_at'))
            ->orderByDesc('date')
            ->orderByDesc('id');

        $total = (float) (clone $query)->whereNull('voided_at')->sum('amount');
        $expenses = $query->paginate($this->perPage($request))->withQueryString();

        return ExpenseResource::collection($expenses)->additional(['totals' => ['amount' => $total]])->response();
    }

    public function show(Expense $expense): JsonResponse
    {
        $this->authorize('viewAny', Expense::class);

        $expense->load('creator');

        return response()->json(['data' => new ExpenseResource($expense)]);
    }

    public function store(ExpenseRequest $request, ExpenseService $service): JsonResponse
    {
        $this->authorize('create', Expense::class);

        $expense = $service->create($request->validated(), $request->user());

        return response()->json([
            'message' => 'Beban '.$expense->code.' berhasil dicatat.',
            'data' => new ExpenseResource($expense),
        ], 201);
    }

    public function void(Request $request, Expense $expense, ExpenseService $service): JsonResponse
    {
        $this->authorize('void', $expense);

        $reason = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Alasan void wajib diisi.',
        ])['reason'];

        $service->void($expense, $reason, $request->user());
        $expense = $expense->fresh('creator');

        return response()->json([
            'message' => 'Beban '.$expense->code.' berhasil di-void.',
            'data' => new ExpenseResource($expense),
        ]);
    }
}
