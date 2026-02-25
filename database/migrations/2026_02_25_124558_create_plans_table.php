<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->boolean('auto_renewal')->default(true);
            $table->unsignedInteger('trial_days')->default(0);
            $table->decimal('iva_percentage', 5, 2)->default(0);
            $table->string('iva_mode')->default('excluded'); // included, excluded
            $table->string('currency', 3)->default('USD');
            $table->unsignedTinyInteger('decimal_precision')->default(2);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plans');
    }
};
