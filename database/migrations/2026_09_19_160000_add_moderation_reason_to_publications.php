<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A refused publication needs to say why, on the publication itself.
     *
     * The audit trail already records the decision, but that is for
     * administrators. The farmer has to see the reason on their own product,
     * next to the fields they are being asked to correct — otherwise a refusal
     * teaches them nothing and the same item comes back unchanged.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
        });

        Schema::table('trainings', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });

        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
