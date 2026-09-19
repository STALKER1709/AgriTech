<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_callbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            // Business rule RG06: replaying a callback must produce no second
            // effect. payments.idempotency_key identifies a payment, not a
            // callback, so the guarantee lives here — and it is the database
            // that enforces it, not an if statement that could lose a race.
            $table->string('event_id', 64)->unique();
            $table->string('signature', 128);
            $table->json('payload');
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_callbacks');
    }
};
