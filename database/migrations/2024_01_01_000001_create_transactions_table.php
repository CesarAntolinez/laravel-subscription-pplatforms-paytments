<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('subscription_id')->nullable()->index();
            $table->string('provider');                  // stripe | mercadopago
            $table->string('provider_transaction_id')->nullable()->index();
            $table->string('currency', 3)->default('USD');
            $table->decimal('subtotal', 12, 4);
            $table->decimal('vat_rate', 5, 4);           // e.g. 0.1600
            $table->decimal('vat_amount', 12, 4);
            $table->decimal('total', 12, 4);
            $table->string('vat_mode')->default('excluded'); // included | excluded
            $table->string('status')->default('pending'); // pending | paid | failed
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('charged_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
