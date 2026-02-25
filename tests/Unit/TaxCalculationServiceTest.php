<?php

namespace Tests\Unit;

use App\Services\TaxCalculationService;
use Tests\TestCase;

class TaxCalculationServiceTest extends TestCase
{
    private TaxCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TaxCalculationService();
    }

    public function test_iva_excluded_calculates_correctly(): void
    {
        $result = $this->service->calculate(100.00, 16, 'excluded');

        $this->assertEquals(100.00, $result['subtotal']);
        $this->assertEquals(16.00, $result['iva_amount']);
        $this->assertEquals(116.00, $result['total']);
        $this->assertEquals(100.00, $result['base_imponible']);
    }

    public function test_iva_included_calculates_correctly(): void
    {
        // Price of 116 with 16% IVA included: base = 116 / 1.16 = 100.00
        $result = $this->service->calculate(116.00, 16, 'included');

        $this->assertEquals(100.00, $result['subtotal']);
        $this->assertEquals(16.00, $result['iva_amount']);
        $this->assertEquals(116.00, $result['total']);
        $this->assertEquals(100.00, $result['base_imponible']);
    }

    public function test_zero_iva_percentage(): void
    {
        $result = $this->service->calculate(200.00, 0, 'excluded');

        $this->assertEquals(200.00, $result['subtotal']);
        $this->assertEquals(0.00, $result['iva_amount']);
        $this->assertEquals(200.00, $result['total']);
    }

    public function test_iva_excluded_respects_decimal_precision(): void
    {
        $result = $this->service->calculate(99.99, 16, 'excluded', 2);

        $this->assertEquals(99.99, $result['subtotal']);
        $this->assertEquals(round(99.99 * 0.16, 2), $result['iva_amount']);
        $this->assertEquals(round(99.99 * 1.16, 2), $result['total']);
    }

    public function test_iva_included_subtotal_plus_iva_equals_total(): void
    {
        $result = $this->service->calculate(500.00, 16, 'included');

        $this->assertEquals($result['total'], $result['subtotal'] + $result['iva_amount']);
    }
}
