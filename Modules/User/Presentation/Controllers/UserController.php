<?php

namespace Modules\User\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Modules\User\Application\Services\UserService;
use Modules\User\Presentation\Requests\CreateUserRequest;
use Modules\User\Presentation\Requests\UpdateUserRequest;
use Modules\User\Presentation\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $filters = $request->only(['search', 'role']);

        $users = $this->userService->paginate($perPage, $filters);

        return response()->json([
            'data' => UserResource::collection($users),
            'meta' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User created successfully',
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $user = \Modules\User\Domain\Entities\User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $user = \Modules\User\Domain\Entities\User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        $updatedUser = $this->userService->update($user, $request->validated());

        return response()->json([
            'data' => new UserResource($updatedUser),
            'message' => 'User updated successfully',
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $user = \Modules\User\Domain\Entities\User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        $this->userService->delete($user);

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
