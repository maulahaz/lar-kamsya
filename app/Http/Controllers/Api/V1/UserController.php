<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(fn ($query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like));
            })
            ->orderByRaw("CASE role WHEN 'owner' THEN 1 WHEN 'admin' THEN 2 WHEN 'keeper' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $counts = [
            'owner' => User::query()->where('role', UserRole::Owner->value)->count(),
            'admin' => User::query()->where('role', UserRole::Admin->value)->count(),
            'keeper' => User::query()->where('role', UserRole::Keeper->value)->count(),
        ];

        return UserResource::collection($users)->additional(['counts' => $counts])->response();
    }

    public function store(UserRequest $request, ActivityLogger $logger): JsonResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validated();

        $user = DB::transaction(function () use ($data, $logger) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'role' => $data['role'],
                'is_active' => $data['is_active'] ?? true,
                'password' => $data['password'],
            ]);

            $logger->log('create', $user, 'Menambahkan pengguna '.$user->name.' ('.$user->role->label().')');

            return $user;
        });

        return response()->json([
            'message' => 'Pengguna '.$user->name.' berhasil ditambahkan.',
            'data' => new UserResource($user),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return response()->json(['data' => new UserResource($user)]);
    }

    public function update(UserRequest $request, User $user, ActivityLogger $logger): JsonResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();

        if ($request->user()->id === $user->id) {
            if ($data['role'] !== UserRole::Owner->value) {
                throw ValidationException::withMessages([
                    'role' => 'Anda tidak dapat mengubah role akun sendiri.',
                ]);
            }

            $data['is_active'] = true;
        }

        if ($user->role === UserRole::Owner
            && $data['role'] !== UserRole::Owner->value
            && User::query()->where('role', UserRole::Owner->value)->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages([
                'role' => 'Minimal harus ada satu Owner aktif.',
            ]);
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'is_active' => $data['is_active'] ?? false,
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        $logger->log('update', $user, 'Memperbarui pengguna '.$user->name);

        return response()->json([
            'message' => 'Pengguna '.$user->name.' berhasil diperbarui.',
            'data' => new UserResource($user->fresh()),
        ]);
    }

    public function destroy(Request $request, User $user, ActivityLogger $logger): JsonResponse
    {
        $this->authorize('delete', $user);

        if ($request->user()->id === $user->id) {
            throw ValidationException::withMessages([
                'user' => 'Anda tidak dapat menonaktifkan akun sendiri.',
            ]);
        }

        if ($user->role === UserRole::Owner
            && User::query()->where('role', UserRole::Owner->value)->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages([
                'user' => 'Minimal harus ada satu Owner aktif.',
            ]);
        }

        $user->forceFill(['is_active' => false])->save();

        $logger->log('update', $user, 'Menonaktifkan pengguna '.$user->name);

        return $this->message('Pengguna '.$user->name.' berhasil dinonaktifkan.');
    }
}
