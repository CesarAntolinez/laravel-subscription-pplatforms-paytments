<?php

namespace App\Services;

class TaxCalculationService
{
    /**
     * Calculate subtotal, IVA amount and total for a given price.
     *
     * When iva_modality is 'included', the price already contains IVA.
     * When iva_modality is 'excluded', IVA is added on top of the price.
     *
     * @param  float  $price  The configured price for the billing cycle.
     * @param  float  $ivaPercentage  IVA percentage (e.g. 16 for 16%).
     * @param  string $ivaModality  'included' or 'excluded'.
     * @param  int    $decimals  Rounding precision.
     * @return array{subtotal: float, iva_amount: float, total: float, base_imponible: float}
     */
    public function calculate(
        float $price,
        float $ivaPercentage,
        string $ivaModality,
        int $decimals = 2
    ): array {
        $rate = $ivaPercentage / 100;

        if ($ivaModality === 'included') {
            // Price already contains IVA: base = price / (1 + rate)
            $baseImponible = round($price / (1 + $rate), $decimals);
            $ivaAmount = round($price - $baseImponible, $decimals);
            $subtotal = $baseImponible;
            $total = round($price, $decimals);
        } else {
            // IVA excluded: IVA is added on top
            $baseImponible = round($price, $decimals);
            $ivaAmount = round($price * $rate, $decimals);
            $subtotal = $baseImponible;
            $total = round($price + $ivaAmount, $decimals);
        }

        return [
            'subtotal' => $subtotal,
            'iva_amount' => $ivaAmount,
            'total' => $total,
            'base_imponible' => $baseImponible,
        ];
    }
}
