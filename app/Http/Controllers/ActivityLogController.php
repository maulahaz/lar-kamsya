<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('view-activity');

        $search = trim((string) $request->query('q', ''));
        $action = (string) $request->query('action', '');
        $userId = $request->integer('user');

        $logs = ActivityLog::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(fn ($query) => $query
                    ->where('description', 'like', $like)
                    ->orWhere('subject_label', 'like', $like));
            })
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('activity.index', [
            'logs' => $logs,
            'search' => $search,
            'action' => $action,
            'userId' => $userId,
            'actions' => ActivityLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }
}
