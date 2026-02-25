<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions');
            $table->string('provider')->default('manual'); // stripe, mercadopago, manual
            $table->string('currency', 3)->default('MXN');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('iva_percentage_applied', 5, 2);
            $table->decimal('iva_amount', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('iva_modality'); // included, excluded
            $table->decimal('base_imponible', 10, 2);
            $table->string('idempotency_key')->unique();
            $table->timestamp('billed_at');
            $table->string('status')->default('paid'); // paid, failed, pending
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
