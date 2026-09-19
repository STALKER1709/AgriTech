<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('XAF');
            $table->string('purpose', 30)->index();
            // What the payment pays for: an order, a training, a subscription
            // or the farmer registration fee.
            $table->nullableMorphs('payable');
            $table->string('provider', 40);
            $table->string('provider_reference', 64)->unique();
            $table->string('method', 30);
            $table->string('status', 20)->index();
            // Raw gateway payload, kept verbatim for after-the-fact auditing.
            $table->json('raw_payload')->nullable();
            // Business rule RG06: replaying a callback must not double-apply
            // its business effect. This unique key is what makes the webhook
            // idempotent.
            $table->string('idempotency_key', 64)->unique();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'purpose']);
            // Drives the reconciliation task looking for stale payments.
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
