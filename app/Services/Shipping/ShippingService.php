<?php

namespace App\Services\Shipping;

class ShippingService
{
    private const BASE_COSTS = [
        'pickup' => 0,
        'standard' => 3000,
        'express' => 5000,
        'international' => 9000,
        'diaspora' => 12000,
    ];

    private const DESTINATION_MULTIPLIERS = [
        'SN' => 1.00,
        'CI' => 1.15,
        'GH' => 1.20,
        'NG' => 1.25,
        'FR' => 1.40,
        'US' => 1.50,
        'DEFAULT' => 1.35,
    ];

    public function methods(): array
    {
        return [
            ['code' => 'pickup', 'label' => 'Retrait sur place', 'base_cost' => self::BASE_COSTS['pickup']],
            ['code' => 'standard', 'label' => 'Livraison standard', 'base_cost' => self::BASE_COSTS['standard']],
            ['code' => 'express', 'label' => 'Livraison express', 'base_cost' => self::BASE_COSTS['express']],
            ['code' => 'international', 'label' => 'Livraison internationale', 'base_cost' => self::BASE_COSTS['international']],
            ['code' => 'diaspora', 'label' => 'Livraison diaspora', 'base_cost' => self::BASE_COSTS['diaspora']],
        ];
    }

    public function estimate(string $method, string $destinationCountry, float $subtotal, ?float $weightKg = null): float
    {
        $normalizedMethod = strtolower($method);
        $base = self::BASE_COSTS[$normalizedMethod] ?? self::BASE_COSTS['standard'];

        if ($normalizedMethod === 'pickup') {
            return 0.0;
        }

        $country = strtoupper(trim($destinationCountry));
        $multiplier = self::DESTINATION_MULTIPLIERS[$country] ?? self::DESTINATION_MULTIPLIERS['DEFAULT'];

        $weightCost = 0.0;
        if ($weightKg !== null && $weightKg > 0) {
            $weightCost = $weightKg * 700;
        }

        if (! in_array($normalizedMethod, ['international', 'diaspora'], true) && $subtotal >= 50000.0) {
            return 0.0;
        }

        return round(($base * $multiplier) + $weightCost, 2);
    }
}
