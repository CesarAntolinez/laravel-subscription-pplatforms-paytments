<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('event_id')->index();          // provider-side event id
            $table->string('event_type');
            $table->string('provider_transaction_id')->nullable()->index();
            $table->json('payload');
            $table->boolean('processed')->default(false);
            $table->timestamps();

            // Ensures no duplicate event processing per provider
            $table->unique(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
