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
            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('provider')->default('stripe'); // stripe, mercadopago
            $table->string('currency', 3)->default('MXN');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('iva_percentage', 5, 2)->default(0);
            $table->decimal('iva_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('modalidad_iva')->default('excluded'); // included, excluded
            $table->string('status')->default('pending'); // pending, approved, failed, refunded
            $table->string('idempotency_key')->unique();
            $table->string('provider_transaction_id')->nullable();
            $table->unsignedTinyInteger('attempt_count')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamp('charged_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
