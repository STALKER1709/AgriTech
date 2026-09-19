<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Human-readable and quotable over the phone: CMD-2026-000123.
            $table->string('reference', 30)->unique();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('total_amount');
            $table->string('status', 30)->index();
            // Past this instant an unpaid order is cancelled by the scheduler.
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['client_id', 'status']);
        });

        Schema::create('sub_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
            $table->string('reference', 30)->unique();
            $table->unsignedInteger('subtotal_amount');
            // The commission rate is frozen when the order is placed: changing
            // the platform setting later must never rewrite past accounting.
            $table->unsignedTinyInteger('commission_rate_snapshot');
            $table->unsignedInteger('commission_amount');
            $table->string('status', 30)->index();
            $table->timestamps();

            $table->index(['farmer_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            // Items hang off the sub-order: with a multi-farmer cart every
            // line belongs to exactly one farmer by construction.
            $table->foreignId('sub_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->unsignedInteger('unit_price_snapshot');
            $table->unsignedInteger('line_total');
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('sub_orders');
        Schema::dropIfExists('orders');
    }
};
