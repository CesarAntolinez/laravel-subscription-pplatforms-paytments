<?php

namespace Tests\Feature;

use App\Models\Discount;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-E1 — Discount CRUD and validation rules
 */
class DiscountCrudTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'code'  => 'SAVE10',
            'name'  => 'Save 10%',
            'type'  => 'percentage',
            'value' => 10,
        ], $overrides);
    }

    public function test_can_create_discount(): void
    {
        $response = $this->postJson('/api/discounts', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonFragment(['code' => 'SAVE10', 'status' => 'active']);

        $this->assertDatabaseHas('discounts', ['code' => 'SAVE10']);
    }

    public function test_create_discount_requires_unique_code(): void
    {
        Discount::factory()->create(['code' => 'DUPLICATE']);

        $response = $this->postJson('/api/discounts', $this->validPayload(['code' => 'DUPLICATE']));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_create_discount_validates_type(): void
    {
        $response = $this->postJson('/api/discounts', $this->validPayload(['type' => 'invalid']));

        $response->assertStatus(422)->assertJsonValidationErrors(['type']);
    }

    public function test_can_list_discounts(): void
    {
        Discount::factory()->count(3)->create();

        $this->getJson('/api/discounts')->assertStatus(200)->assertJsonCount(3, 'data');
    }

    public function test_can_show_discount(): void
    {
        $discount = Discount::factory()->create();

        $this->getJson("/api/discounts/{$discount->id}")->assertStatus(200)
            ->assertJsonFragment(['id' => $discount->id]);
    }

    public function test_can_update_discount_status(): void
    {
        $discount = Discount::factory()->create(['status' => 'active']);

        $this->putJson("/api/discounts/{$discount->id}", ['status' => 'paused'])
            ->assertStatus(200)
            ->assertJsonFragment(['status' => 'paused']);
    }

    public function test_can_soft_delete_discount(): void
    {
        $discount = Discount::factory()->create();

        $this->deleteJson("/api/discounts/{$discount->id}")->assertStatus(200);

        $this->assertSoftDeleted('discounts', ['id' => $discount->id]);
    }

    public function test_validate_endpoint_returns_valid_for_active_code(): void
    {
        Discount::factory()->create(['code' => 'VALID20', 'status' => 'active']);

        $this->postJson('/api/discounts/validate', ['code' => 'VALID20'])
            ->assertStatus(200)
            ->assertJsonFragment(['valid' => true]);
    }

    public function test_validate_endpoint_rejects_expired_code(): void
    {
        Discount::factory()->create([
            'code'        => 'EXPIRED',
            'status'      => 'active',
            'valid_until' => now()->subDay(),
        ]);

        $this->postJson('/api/discounts/validate', ['code' => 'EXPIRED'])
            ->assertStatus(422)
            ->assertJsonFragment(['valid' => false]);
    }

    public function test_validate_endpoint_rejects_exhausted_code(): void
    {
        Discount::factory()->create([
            'code'      => 'MAXED',
            'status'    => 'active',
            'max_uses'  => 5,
            'used_count' => 5,
        ]);

        $this->postJson('/api/discounts/validate', ['code' => 'MAXED'])
            ->assertStatus(422)
            ->assertJsonFragment(['valid' => false]);
    }

    public function test_validate_endpoint_rejects_plan_restricted_code_for_wrong_plan(): void
    {
        $plan    = Plan::factory()->create();
        $other   = Plan::factory()->create();
        Discount::factory()->create([
            'code'             => 'PLANONLY',
            'status'           => 'active',
            'restrict_plan_id' => $plan->id,
        ]);

        $this->postJson('/api/discounts/validate', ['code' => 'PLANONLY', 'plan_id' => $other->id])
            ->assertStatus(422)
            ->assertJsonFragment(['valid' => false]);
    }
}
