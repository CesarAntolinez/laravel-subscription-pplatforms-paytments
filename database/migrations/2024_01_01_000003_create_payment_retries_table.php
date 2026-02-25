<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_retries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('attempt_number');
            $table->string('status');                     // pending | succeeded | failed
            $table->text('failure_reason')->nullable();
            $table->timestamp('scheduled_at');
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_retries');
    }
};
