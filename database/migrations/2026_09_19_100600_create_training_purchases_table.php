<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('training_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->timestamp('purchased_at');
            $table->timestamps();

            // A training is bought once and for all by a given client.
            $table->unique(['client_id', 'training_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_purchases');
    }
};
