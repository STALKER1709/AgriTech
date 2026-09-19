<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            // One cart per client, kept in the database rather than the
            // session: a cart that vanishes when a phone reloads the page is
            // a cart that never becomes an order.
            $table->foreignId('client_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            // Restricted rather than cascading: a product that is part of a
            // cart is never hard-deleted anyway, and losing the line silently
            // would change the total under the client's eyes.
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->timestamps();

            // Adding a product twice adds to the line, it does not duplicate
            // it — and the database is what guarantees that, not the service.
            $table->unique(['cart_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
