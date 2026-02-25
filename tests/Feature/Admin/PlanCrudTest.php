<?php

namespace Tests\Feature\Admin;

use App\Domains\Plans\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlanCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        $this->regularUser = User::factory()->create();
        $this->regularUser->assignRole('user');
    }

    public function test_admin_can_list_plans(): void
    {
        Plan::create([
            'name' => 'Plan Básico',
            'slug' => 'plan-basico',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/admin/plans');

        $response->assertOk()
            ->assertJsonStructure(['data', 'total', 'per_page']);
    }

    public function test_regular_user_cannot_list_plans(): void
    {
        $response = $this->actingAs($this->regularUser, 'sanctum')
            ->getJson('/api/admin/plans');

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_list_plans(): void
    {
        $response = $this->getJson('/api/admin/plans');

        $response->assertUnauthorized();
    }

    public function test_admin_can_create_plan(): void
    {
        $payload = [
            'name'           => 'Plan Premium',
            'description'    => 'El mejor plan disponible',
            'status'         => 'active',
            'auto_renewal'   => true,
            'iva_percentage' => 16.00,
            'iva_mode'       => 'excluded',
            'currency'       => 'MXN',
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/plans', $payload);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'Plan Premium', 'slug' => 'plan-premium']);

        $this->assertDatabaseHas('plans', ['name' => 'Plan Premium']);
    }

    public function test_plan_name_must_be_unique(): void
    {
        Plan::create(['name' => 'Plan Único', 'slug' => 'plan-unico']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/plans', ['name' => 'Plan Único']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_admin_can_create_plan_with_levels_and_cycles(): void
    {
        $payload = [
            'name'   => 'Plan Completo',
            'status' => 'active',
            'levels' => [
                ['name' => 'Básico', 'price' => 9.99, 'sort_order' => 1],
                ['name' => 'Estándar', 'price' => 19.99, 'sort_order' => 2],
            ],
            'billing_cycles' => [
                ['cycle' => 'monthly', 'interval_days' => 30, 'price_modifier' => 1.00],
                ['cycle' => 'annual', 'interval_days' => 365, 'price_modifier' => 10.00],
            ],
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/plans', $payload);

        $response->assertCreated();

        $plan = Plan::where('name', 'Plan Completo')->first();
        $this->assertCount(2, $plan->levels);
        $this->assertCount(2, $plan->billingCycles);
    }

    public function test_admin_can_view_plan(): void
    {
        $plan = Plan::create(['name' => 'Plan Vista', 'slug' => 'plan-vista']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/admin/plans/{$plan->id}");

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Plan Vista']);
    }

    public function test_admin_can_update_plan(): void
    {
        $plan = Plan::create(['name' => 'Plan Original', 'slug' => 'plan-original']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/admin/plans/{$plan->id}", [
                'name'        => 'Plan Actualizado',
                'description' => 'Descripción nueva',
            ]);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Plan Actualizado']);

        $this->assertDatabaseHas('plans', ['name' => 'Plan Actualizado']);
    }

    public function test_super_admin_can_delete_plan(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $plan = Plan::create(['name' => 'Plan a Eliminar', 'slug' => 'plan-eliminar']);

        $response = $this->actingAs($superAdmin, 'sanctum')
            ->deleteJson("/api/admin/plans/{$plan->id}");

        $response->assertOk()
            ->assertJsonFragment(['message' => 'Plan eliminado correctamente.']);

        $this->assertSoftDeleted('plans', ['id' => $plan->id]);
    }

    public function test_admin_cannot_delete_plan(): void
    {
        $plan = Plan::create(['name' => 'Plan No Delete', 'slug' => 'plan-no-delete']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/admin/plans/{$plan->id}");

        $response->assertForbidden();
    }

    public function test_admin_can_toggle_plan_status(): void
    {
        $plan = Plan::create(['name' => 'Plan Toggle', 'slug' => 'plan-toggle', 'status' => 'active']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/admin/plans/{$plan->id}/toggle-status");

        $response->assertOk();
        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'status' => 'inactive']);
    }

    public function test_audit_log_created_on_plan_creation(): void
    {
        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/admin/plans', [
                'name'   => 'Plan Auditado',
                'status' => 'active',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'         => 'created',
            'auditable_type' => Plan::class,
        ]);
    }
}
