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
        Schema::create('plan_billing_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('cycle'); // monthly, quarterly, annual
            $table->unsignedInteger('interval_days'); // 30, 90, 365
            $table->decimal('price_modifier', 5, 2)->default(1.00); // multiplier vs base price
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['plan_id', 'cycle']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plan_billing_cycles');
    }
};
