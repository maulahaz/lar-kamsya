<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseCategory;
use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Expense::class);

        $search = trim((string) $request->query('q', ''));
        $category = (string) $request->query('category', '');
        $filter = (string) $request->query('status', '');

        $query = Expense::query()
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
        $expenses = $query->paginate(12)->withQueryString();

        return view('expenses.index', [
            'expenses' => $expenses,
            'total' => $total,
            'search' => $search,
            'category' => $category,
            'filter' => $filter,
            'categories' => ExpenseCategory::cases(),
        ]);
    }

    public function store(ExpenseRequest $request, ExpenseService $service)
    {
        $this->authorize('create', Expense::class);

        $expense = $service->create($request->validated(), $request->user());

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Beban '.$expense->code.' berhasil dicatat.');
    }

    public function void(Request $request, Expense $expense, ExpenseService $service)
    {
        $this->authorize('void', $expense);

        $reason = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Alasan void wajib diisi.',
        ])['reason'];

        try {
            $service->void($expense, $reason, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Beban '.$expense->code.' berhasil di-void.');
    }
}
