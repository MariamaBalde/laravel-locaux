<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Requests\StoreUserAddressRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateUserAddressRequest;
use App\Http\Resources\UserAddressResource;
use App\Http\Resources\UserPaymentMethodResource;
use App\Http\Resources\UserResource;
use App\Models\UserAddress;
use App\Models\UserPaymentMethod;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        try {
            $this->authorize('view', $request->user());

            return response()->json([
                'success' => true,
                'data' => new UserResource($request->user()->load(['addresses', 'paymentMethods', 'vendeur.user'])),
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du profil',
            ], 500);
        }
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $this->authorize('update', $user);

            $user->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour',
                'data' => new UserResource($user->fresh()->load(['addresses', 'paymentMethods', 'vendeur.user'])),
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil',
            ], 500);
        }
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $this->authorize('update', $user);
            $validated = $request->validated();

            if (! Hash::check($validated['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['Mot de passe actuel incorrect.'],
                ]);
            }

            $user->update([
                'password' => Hash::make($validated['new_password']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe mis à jour',
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du mot de passe',
            ], 500);
        }
    }

    public function addresses(Request $request): JsonResponse
    {
        try {
            $this->authorize('manageSettings', $request->user());
            $perPage = min(max((int) $request->query('per_page', 10), 1), 100);
            $addresses = $request->user()->addresses()->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => UserAddressResource::collection($addresses->getCollection()),
                'meta' => [
                    'current_page' => $addresses->currentPage(),
                    'per_page' => $addresses->perPage(),
                    'last_page' => $addresses->lastPage(),
                    'from' => $addresses->firstItem(),
                    'to' => $addresses->lastItem(),
                    'total' => $addresses->total(),
                ],
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des adresses',
            ], 500);
        }
    }

    public function addAddress(StoreUserAddressRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $this->authorize('manageSettings', $user);
            $validated = $request->validated();

            if (! empty($validated['is_default'])) {
                $user->addresses()->update(['is_default' => false]);
            }

            $address = $user->addresses()->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Adresse ajoutée',
                'data' => new UserAddressResource($address),
            ], 201);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de l\'adresse',
            ], 500);
        }
    }

    public function updateAddress(UpdateUserAddressRequest $request, int $id): JsonResponse
    {
        try {
            $this->authorize('manageSettings', $request->user());
            $address = UserAddress::where('user_id', $request->user()->id)->find($id);
            if (! $address) {
                throw ValidationException::withMessages([
                    'address' => ['Adresse non trouvée.'],
                ]);
            }

            $validated = $request->validated();

            if (! empty($validated['is_default'])) {
                $request->user()->addresses()->update(['is_default' => false]);
            }

            $address->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Adresse mise à jour',
                'data' => new UserAddressResource($address->fresh()),
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'adresse',
            ], 500);
        }
    }

    public function deleteAddress(Request $request, int $id): JsonResponse
    {
        try {
            $this->authorize('manageSettings', $request->user());
            $address = UserAddress::where('user_id', $request->user()->id)->find($id);
            if (! $address) {
                throw ValidationException::withMessages([
                    'address' => ['Adresse non trouvée.'],
                ]);
            }

            $address->delete();

            return response()->json([
                'success' => true,
                'message' => 'Adresse supprimée',
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'adresse',
            ], 500);
        }
    }

    public function paymentMethods(Request $request): JsonResponse
    {
        try {
            $this->authorize('manageSettings', $request->user());
            $perPage = min(max((int) $request->query('per_page', 10), 1), 100);
            $methods = $request->user()->paymentMethods()->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => UserPaymentMethodResource::collection($methods->getCollection()),
                'meta' => [
                    'current_page' => $methods->currentPage(),
                    'per_page' => $methods->perPage(),
                    'last_page' => $methods->lastPage(),
                    'from' => $methods->firstItem(),
                    'to' => $methods->lastItem(),
                    'total' => $methods->total(),
                ],
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des moyens de paiement',
            ], 500);
        }
    }

    public function addPaymentMethod(StorePaymentMethodRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $this->authorize('manageSettings', $user);
            $validated = $request->validated();

            if (! empty($validated['is_default'])) {
                $user->paymentMethods()->update(['is_default' => false]);
            }

            $method = $user->paymentMethods()->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Méthode de paiement ajoutée',
                'data' => new UserPaymentMethodResource($method),
            ], 201);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout de la méthode de paiement',
            ], 500);
        }
    }

    public function deletePaymentMethod(Request $request, int $id): JsonResponse
    {
        try {
            $this->authorize('manageSettings', $request->user());
            $method = UserPaymentMethod::where('user_id', $request->user()->id)->find($id);
            if (! $method) {
                throw ValidationException::withMessages([
                    'payment_method' => ['Méthode de paiement non trouvée.'],
                ]);
            }

            $method->delete();

            return response()->json([
                'success' => true,
                'message' => 'Méthode de paiement supprimée',
            ], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la méthode de paiement',
            ], 500);
        }
    }
}
