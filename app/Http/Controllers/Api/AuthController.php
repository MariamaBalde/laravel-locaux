<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\AuthPayloadResource;
use App\Http\Resources\DataResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * POST /api/auth/register
     */
    #[OA\Post(
        path: '/api/auth/register',
        operationId: 'authRegister',
        tags: ['Auth'],
        summary: 'Inscription utilisateur',
        description: 'Crée un compte client ou vendeur puis envoie un email de vérification.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RegisterRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Inscription réussie',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthSuccessResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Erreur de validation',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')
            ),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie. Vérifiez votre email pour activer votre compte.',
                'data' => new AuthPayloadResource($result),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription',
            ], 500);
        }
    }

    /**
     * POST /api/auth/login
     */
    #[OA\Post(
        path: '/api/auth/login',
        operationId: 'authLogin',
        tags: ['Auth'],
        summary: 'Connexion utilisateur',
        description: "Connecte un utilisateur et retourne les informations d'authentification. Alias legacy disponible: /api/login.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connexion réussie',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiLoginSuccessResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Identifiants invalides',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Erreur lors de la connexion'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => new AuthPayloadResource($result),
                // Contrat legacy pour compatibilité frontend/API clients externes.
                'token' => $result['access_token'] ?? null,
                'user' => isset($result['user']) ? new UserResource($result['user']) : null,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de connexion',
                'errors' => $e->errors(),
            ], 401);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion',
            ], 500);
        }
    }

    /**
     * POST /api/auth/login-oauth
     */
    public function loginOAuth(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->loginWithPasswordGrant($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Connexion OAuth réussie',
                'data' => new DataResource($result),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de connexion OAuth',
                'errors' => $e->errors(),
            ], 401);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion OAuth',
            ], 500);
        }
    }

    /**
     * POST /api/auth/logout
     */
    #[OA\Post(
        path: '/api/auth/logout',
        operationId: 'authLogout',
        tags: ['Auth'],
        summary: 'Déconnexion',
        description: "Révoque le token d'accès courant de l'utilisateur connecté.",
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Déconnexion réussie',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthMessageResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')
            ),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->logout($request->user());

            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ], 200);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion',
            ], 500);
        }
    }

    /**
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $this->authService->me($request->user());

            return response()->json([
                'success' => true,
                'data' => new UserResource($user),
            ], 200);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'utilisateur',
            ], 500);
        }
    }

    /**
     * POST /api/auth/refresh
     */
    #[OA\Post(
        path: '/api/auth/refresh',
        operationId: 'authRefresh',
        tags: ['Auth'],
        summary: "Rafraîchir un token d'accès",
        description: "Retourne un nouveau token d'accès à partir d'un refresh token valide.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RefreshTokenRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token rafraîchi',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthSuccessResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Refresh token invalide',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')
            ),
        ]
    )]
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->refresh((string) $request->validated('refresh_token'));

            return response()->json([
                'success' => true,
                'message' => 'Token rafraîchi',
                'data' => new DataResource($result),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide',
                'errors' => $e->errors(),
            ], 401);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rafraîchissement',
            ], 500);
        }
    }

    /**
     * GET /api/auth/email/verify/{id}/{hash}
     */
    #[OA\Get(
        path: '/api/auth/email/verify/{id}/{hash}',
        operationId: 'authVerifyEmail',
        tags: ['Auth'],
        summary: 'Vérification email (lien signé)',
        description: "Valide l'email d'un utilisateur via le lien reçu par email.",
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID utilisateur', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'hash', description: "Hash SHA1 de l'email", in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'q', description: 'Recherche optionnelle', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email vérifié',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthMessageResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Lien invalide',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Utilisateur introuvable',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')
            ),
        ]
    )]
    public function verifyEmail(Request $request, int $id, string $hash)
    {
        $user = User::find($id);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable.',
            ], 404);
        }

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'success' => false,
                'message' => 'Lien de vérification invalide.',
            ], 403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $frontend = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');
        if (! $request->expectsJson()) {
            return redirect()->away($frontend.'/login?verified=1');
        }

        return response()->json([
            'success' => true,
            'message' => 'Adresse email vérifiée avec succès.',
        ], 200);
    }

    /**
     * POST /api/auth/email/verification-notification
     */
    #[OA\Post(
        path: '/api/auth/email/verification-notification',
        operationId: 'authResendVerification',
        tags: ['Auth'],
        summary: 'Renvoyer email de vérification',
        description: "Renvoie un email de vérification à l'utilisateur authentifié.",
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email de vérification renvoyé',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthMessageResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')
            ),
        ]
    )]
    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Votre adresse email est déjà vérifiée.',
            ], 200);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Email de vérification renvoyé.',
        ], 200);
    }

    /**
     * POST /api/email/verify
     */
    #[OA\Post(
        path: '/api/email/verify',
        operationId: 'authVerifyAuthenticatedEmail',
        tags: ['Auth'],
        summary: 'Vérifier email utilisateur connecté',
        description: "Marque l'email de l'utilisateur connecté comme vérifié.",
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Adresse email vérifiée',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthMessageResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')
            ),
        ]
    )]
    public function verifyAuthenticatedEmail(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return response()->json([
            'success' => true,
            'message' => 'Adresse email vérifiée avec succès.',
        ], 200);
    }
}
