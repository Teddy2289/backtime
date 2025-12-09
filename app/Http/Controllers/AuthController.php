<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Enums\UserRole;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Http\Requests\UpdateProfileRequest;

class AuthController extends Controller
{
    use ApiResponser;

    /**
     * Login user and get JWT token.
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $credentials = $request->only('email', 'password');

            if (!$token = auth()->attempt($credentials)) {
                return $this->unauthorizedResponse('Invalid credentials');
            }

            $user = auth()->user();
            return $this->authResponse($token, $user, 'Login successful');
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Login failed',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|between:2,100',
                'email' => 'required|string|email|max:100|unique:users',
                'password' => 'required|string|confirmed|min:6',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $user = User::create(array_merge(
                $validator->validated(),
                [
                    'password' => Hash::make($request->password),
                    'role' => UserRole::USER->value,
                ]
            ));

            // Ensure role exists
            $this->ensureRoleExists(UserRole::USER->value);

            // Assign role
            $user->assignRole(UserRole::USER->value);

            $token = auth()->login($user);

            DB::commit();

            return $this->authResponse($token, $user, 'Registration successful')
                ->setStatusCode(201);
        } catch (Throwable $e) {
            DB::rollBack();

            return $this->errorResponse(
                'Registration failed',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Logout user.
     */
    public function logout()
    {
        try {
            auth()->logout();

            return $this->successResponse(
                null,
                'Successfully logged out'
            );
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Logout failed',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Refresh JWT token.
     */
    public function refresh()
    {
        try {
            $token = auth()->refresh();
            $user = auth()->user();

            return $this->authResponse($token, $user, 'Token refreshed');
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Token refresh failed',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get authenticated user.
     */
    public function me()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse();
            }

            return $this->successResponse(
                $user,
                'User retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to get user data',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Ensure role exists in database.
     */
    protected function ensureRoleExists(string $roleName): void
    {
        try {
            $role = \Spatie\Permission\Models\Role::where('name', $roleName)
                ->where('guard_name', 'api')
                ->first();

            if (!$role) {
                \Spatie\Permission\Models\Role::create([
                    'name' => $roleName,
                    'guard_name' => 'api',
                ]);
            }
        } catch (Throwable $e) {
            // Log error but don't crash registration
            Log::error('Failed to ensure role exists: ' . $e->getMessage());
        }
    }

    public function updateProfile(UpdateProfileRequest  $request)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse();
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|between:2,100',
                'email' => 'sometimes|string|email|max:100|unique:users,email,' . $user->id,
                'current_password' => 'required_with:password',
                'password' => 'sometimes|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Vérifier le mot de passe actuel si changement de mot de passe
            if ($request->has('password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return $this->errorResponse('Current password is incorrect', null, 422);
                }

                $user->password = Hash::make($request->password);
            }

            // Mettre à jour les autres champs
            if ($request->has('name')) {
                $user->name = $request->name;
            }

            if ($request->has('email')) {
                $user->email = $request->email;
            }

            $user->save();

            DB::commit();

            return $this->successResponse(
                $user,
                'Profile updated successfully'
            );

        } catch (Throwable $e) {
            DB::rollBack();

            return $this->errorResponse(
                'Failed to update profile',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Upload user avatar.
     */
    public function uploadAvatar(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse();
            }

            $validator = Validator::make($request->all(), [
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10240', // Corrigé: 10240 au lieu de 10102400
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Supprimer l'ancien avatar si existe
            if ($user->avatar) {
                $oldAvatarPath = 'avatars/' . $user->avatar;
                if (Storage::disk('public')->exists($oldAvatarPath)) {
                    Storage::disk('public')->delete($oldAvatarPath);
                }
            }

            // Upload le nouvel avatar
            $avatar = $request->file('avatar');
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $avatar->getClientOriginalExtension();

            // Stocker dans storage/app/public/avatars
            $path = $avatar->storeAs('avatars', $filename, 'public');

            // Mettre à jour l'utilisateur
            $user->avatar = $filename;
            $user->save();

            return $this->successResponse(
                [
                    'avatar_url' => $user->avatar_url,
                    'filename' => $filename,
                ],
                'Avatar uploaded successfully'
            );

        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to upload avatar',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Remove user avatar.
     */
    public function removeAvatar()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse();
            }

            if (!$user->avatar) {
                return $this->errorResponse('No avatar to remove', null, 404);
            }

            // Supprimer le fichier
            $avatarPath = 'avatars/' . $user->avatar;
            if (Storage::disk('public')->exists($avatarPath)) {
                Storage::disk('public')->delete($avatarPath);
            }

            // Mettre à jour l'utilisateur
            $user->avatar = null;
            $user->save();

            return $this->successResponse(
                null,
                'Avatar removed successfully'
            );

        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to remove avatar',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get user profile with more details.
     */
    public function profile()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse();
            }

            // Charger les relations si nécessaire
            $user->load(['roles', 'permissions']);

            // Convertir le rôle en string pour éviter les problèmes de sérialisation
            $role = $user->role;

            $profileData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role instanceof UserRole ? $role->value : $role,
                'role_label' => $role instanceof UserRole ? $role->label() : null,
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatar_url,
                'initials' => $user->initials,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ];

            return $this->successResponse(
                $profileData,
                'Profile retrieved successfully'
            );

        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to get profile',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }
}
