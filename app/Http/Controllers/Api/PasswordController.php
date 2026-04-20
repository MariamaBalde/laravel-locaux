<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\DataResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->validated());

        // Always return 200 to avoid leaking whether an email exists.
        return response()->json([
            'success' => true,
            'message' => __($status === Password::RESET_LINK_SENT ? $status : Password::RESET_LINK_SENT),
            'data' => new DataResource(['email' => $request->validated('email')]),
        ], 200);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->validated(),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'success' => false,
                'message' => 'Réinitialisation impossible.',
                'errors' => [
                    'token' => [__($status)],
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => __($status),
            'data' => new DataResource(['email' => $request->validated('email')]),
        ], 200);
    }
}
