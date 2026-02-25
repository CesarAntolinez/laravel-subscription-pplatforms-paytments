<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_billing_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('cycle'); // monthly, quarterly, annual
            $table->decimal('price', 10, 2);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['plan_id', 'cycle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_billing_cycles');
    }
};
