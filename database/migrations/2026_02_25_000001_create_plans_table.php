<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->unsignedInteger('trial_days')->default(0);
            $table->boolean('auto_renew')->default(true);
            $table->decimal('iva_percentage', 5, 2)->default(0);
            $table->string('iva_modality')->default('excluded'); // included, excluded
            $table->string('currency', 3)->default('MXN');
            $table->unsignedTinyInteger('decimal_precision')->default(2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
