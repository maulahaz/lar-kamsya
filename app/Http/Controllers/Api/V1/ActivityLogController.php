<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ActivityLogController extends BaseController
{
    public function index(Request $request): JsonResponse
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
            ->paginate($this->perPage($request))
            ->withQueryString();

        return ActivityLogResource::collection($logs)->additional([
            'filters' => [
                'actions' => ActivityLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
                'users' => User::query()->orderBy('name')->get(['id', 'name']),
            ],
        ])->response();
    }
}
