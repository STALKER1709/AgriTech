<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privileges', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('privilege_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('privilege_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['privilege_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privilege_user');
        Schema::dropIfExists('privileges');
    }
};
