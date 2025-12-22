<?php

namespace Modules\User\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Modules\User\Application\Services\UserService;
use Modules\User\Presentation\Requests\CreateUserRequest;
use Modules\User\Presentation\Requests\UpdateUserRequest;
use Modules\User\Presentation\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

/**
     * Upload d'avatar séparé
     */
    public function uploadAvatar(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        $user = \Modules\User\Domain\Entities\User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        try {
            $updatedUser = $this->userService->uploadAvatar($user, $request->file('avatar'));
            
            return response()->json([
                'data' => new UserResource($updatedUser),
                'message' => 'Avatar updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Avatar upload error: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to upload avatar',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer l'avatar
     */
    public function removeAvatar(string $id): JsonResponse
    {
        $user = \Modules\User\Domain\Entities\User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        try {
            $updatedUser = $this->userService->removeAvatar($user);
            
            return response()->json([
                'data' => new UserResource($updatedUser),
                'message' => 'Avatar removed successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Avatar removal error: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to remove avatar',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour un utilisateur (inclut l'avatar)
     */
    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $user = \Modules\User\Domain\Entities\User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        try {
            // Récupérer les données validées
            $validatedData = $request->validated();
            
            // Ajouter le fichier avatar s'il existe
            if ($request->hasFile('avatar')) {
                $validatedData['avatar'] = $request->file('avatar');
            }

            $updatedUser = $this->userService->update($user, $validatedData);

            return response()->json([
                'data' => new UserResource($updatedUser),
                'message' => 'User updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('User update error: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to update user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer un utilisateur (inclut l'avatar)
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        try {
            // Récupérer les données validées
            $validatedData = $request->validated();
            
            // Ajouter le fichier avatar s'il existe
            if ($request->hasFile('avatar')) {
                $validatedData['avatar'] = $request->file('avatar');
            }

            $user = $this->userService->create($validatedData);

            return response()->json([
                'data' => new UserResource($user),
                'message' => 'User created successfully',
            ], 201);
        } catch (\Exception $e) {
            Log::error('User creation error: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to create user',
                'error' => $e->getMessage(),
            ], 500);
        }
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
