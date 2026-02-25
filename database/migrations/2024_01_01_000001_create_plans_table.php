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
            $table->string('description')->nullable();
            $table->string('level')->default('basic'); // basic, standard, premium
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('MXN');
            $table->decimal('iva_porcentaje', 5, 2)->default(0);
            $table->string('modalidad_iva')->default('excluded'); // included, excluded
            $table->json('billing_cycles')->nullable(); // ["monthly","quarterly","annual"]
            $table->unsignedInteger('trial_days')->default(0);
            $table->boolean('auto_renew')->default(true);
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
