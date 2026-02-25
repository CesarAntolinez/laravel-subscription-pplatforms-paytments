<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 1 used slug, auto_renewal, iva_mode on plans.
 * Sprint 2 used iva_modality, auto_renew (no slug).
 * This migration adds the Sprint 1 columns so both domains work correctly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('auto_renewal')->nullable()->after('auto_renew');
            $table->string('iva_mode')->nullable()->after('iva_modality');
            $table->softDeletes();
        });

        Schema::table('plan_billing_cycles', function (Blueprint $table) {
            $table->unsignedInteger('interval_days')->nullable()->after('cycle');
            $table->decimal('price_modifier', 5, 2)->default(1.00)->after('price');
            $table->boolean('is_active')->default(true)->after('enabled');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['slug', 'auto_renewal', 'iva_mode']);
            $table->dropSoftDeletes();
        });

        Schema::table('plan_billing_cycles', function (Blueprint $table) {
            $table->dropColumn(['interval_days', 'price_modifier', 'is_active']);
        });
    }
};
