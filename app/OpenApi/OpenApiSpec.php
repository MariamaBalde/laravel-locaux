<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'App Produits Locaux API',
    description: 'Documentation des endpoints du backend Laravel.'
)]
#[OA\Server(
    url: '/',
    description: 'Serveur courant'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Token Passport au format: Bearer {token}'
)]
#[OA\Tag(
    name: 'Auth',
    description: 'Authentification, session et verification email'
)]
#[OA\Tag(name: 'Products', description: 'Catalogue produits')]
#[OA\Tag(name: 'Categories', description: 'Gestion des catégories')]
#[OA\Tag(name: 'Cart', description: 'Panier utilisateur')]
#[OA\Tag(name: 'Orders', description: 'Gestion des commandes')]
#[OA\Tag(name: 'Payments', description: 'Paiement et callbacks fournisseurs')]
#[OA\Tag(name: 'Shipping', description: 'Méthodes et estimation de livraison')]
#[OA\Tag(name: 'User', description: 'Profil, adresses et moyens de paiement utilisateur')]
#[OA\Tag(name: 'Vendor', description: 'Endpoints vendeur et dashboard vendeur')]
#[OA\Tag(name: 'Admin', description: 'Administration et supervision')]
#[OA\Tag(name: 'General', description: 'Endpoints divers')]
class OpenApiSpec {}

#[OA\Schema(
    schema: 'AuthUserSummary',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Mariam Balde'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'client@example.com'),
        new OA\Property(property: 'role', type: 'string', example: 'client'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+221771234567'),
        new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Dakar, Senegal'),
        new OA\Property(property: 'country', type: 'string', example: 'SN'),
        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
    type: 'object'
)]
class AuthUserSummarySchema {}

#[OA\Schema(
    schema: 'LoginRequest',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'client@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Password123!'),
    ],
    type: 'object'
)]
class LoginRequestSchema {}

#[OA\Schema(
    schema: 'RegisterRequest',
    required: ['name', 'email', 'password', 'password_confirmation', 'role', 'country'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Mariam Balde'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'mariam@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'Password123!'),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', minLength: 8, example: 'Password123!'),
        new OA\Property(property: 'role', type: 'string', enum: ['client', 'vendeur'], example: 'client'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, maxLength: 20, example: '+221771234567'),
        new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Dakar, Senegal'),
        new OA\Property(property: 'country', type: 'string', minLength: 2, maxLength: 2, example: 'SN'),
        new OA\Property(property: 'shop_name', type: 'string', nullable: true, example: 'Boutique Teranga'),
        new OA\Property(property: 'shop_description', type: 'string', nullable: true, example: 'Produits frais et locaux'),
    ],
    type: 'object'
)]
class RegisterRequestSchema {}

#[OA\Schema(
    schema: 'RefreshTokenRequest',
    required: ['refresh_token'],
    properties: [
        new OA\Property(property: 'refresh_token', type: 'string', maxLength: 4096, example: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...'),
    ],
    type: 'object'
)]
class RefreshTokenRequestSchema {}

#[OA\Schema(
    schema: 'AuthPayload',
    properties: [
        new OA\Property(property: 'user', ref: '#/components/schemas/AuthUserSummary'),
        new OA\Property(property: 'access_token', type: 'string', nullable: true, example: 'eyJ0eXAiOiJKV1QiLCJhbGciOi...'),
        new OA\Property(property: 'refresh_token', type: 'string', nullable: true, example: 'def50200e8...'),
        new OA\Property(property: 'token_type', type: 'string', nullable: true, example: 'Bearer'),
        new OA\Property(property: 'expires_at', type: 'string', nullable: true, example: '2026-04-13T18:30:00.000000Z'),
        new OA\Property(property: 'requires_email_verification', type: 'boolean', nullable: true, example: false),
        new OA\Property(property: 'verification_sent', type: 'boolean', nullable: true, example: false),
    ],
    type: 'object'
)]
class AuthPayloadSchema {}

#[OA\Schema(
    schema: 'AuthSuccessResponse',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Succès'),
        new OA\Property(property: 'data', ref: '#/components/schemas/AuthPayload'),
    ],
    type: 'object'
)]
class AuthSuccessResponseSchema {}

#[OA\Schema(
    schema: 'AuthUserResponse',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', ref: '#/components/schemas/AuthUserSummary'),
    ],
    type: 'object'
)]
class AuthUserResponseSchema {}

#[OA\Schema(
    schema: 'AuthMessageResponse',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Opération réussie'),
    ],
    type: 'object'
)]
class AuthMessageResponseSchema {}

#[OA\Schema(
    schema: 'ApiLoginSuccessResponse',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Connexion réussie'),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'user',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Mariam Balde'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'client@example.com'),
                        new OA\Property(property: 'role', type: 'string', example: 'client'),
                    ]
                ),
                new OA\Property(property: 'access_token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOi...'),
                new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                new OA\Property(property: 'expires_at', type: 'string', example: '2026-04-13T18:30:00.000000Z'),
                new OA\Property(property: 'requires_email_verification', type: 'boolean', example: false),
                new OA\Property(property: 'verification_sent', type: 'boolean', example: false),
            ]
        ),
        new OA\Property(property: 'token', type: 'string', nullable: true, example: 'eyJ0eXAiOiJKV1QiLCJhbGciOi...'),
        new OA\Property(
            property: 'user',
            type: 'object',
            nullable: true,
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'Mariam Balde'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'client@example.com'),
                new OA\Property(property: 'role', type: 'string', example: 'client'),
            ]
        ),
    ],
    type: 'object'
)]
class ApiLoginSuccessResponseSchema {}

#[OA\Schema(
    schema: 'ApiErrorResponse',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Erreur de connexion'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            )
        ),
    ],
    type: 'object'
)]
class ApiErrorResponseSchema {}
