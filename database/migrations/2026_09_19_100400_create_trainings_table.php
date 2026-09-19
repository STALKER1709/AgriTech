<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->unsignedInteger('price');
            $table->string('format', 20);
            $table->boolean('included_in_subscription')->default(false)->index();
            $table->string('status', 20)->index();
            $table->timestamps();

            $table->index(['farmer_id', 'status']);
        });

        Schema::create('training_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type', 20);
            // Path on the private disk. Business rule RG05 forbids serving
            // these files from a public URL, so the path never reaches a view.
            $table->string('path');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['training_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_contents');
        Schema::dropIfExists('trainings');
    }
};
