<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type'); // percentage, fixed, free_trial
            $table->decimal('value', 10, 2); // % or amount; trial_days when type=free_trial
            $table->unsignedInteger('max_uses')->nullable(); // null = unlimited
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->string('status')->default('active'); // active, paused, deleted
            $table->unsignedBigInteger('restrict_plan_id')->nullable(); // restrict to plan
            $table->string('restrict_user_segment')->nullable();
            $table->string('restrict_signup_type')->nullable(); // new, returning
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
