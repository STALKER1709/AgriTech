<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Business rule RG08 removes a deleted user's personal data while keeping
     * the row, so that their orders and payments stay readable.
     *
     * Anonymising means removing the data, not replacing it with plausible
     * fiction, so these two columns have to accept NULL. MySQL allows several
     * NULLs under a unique index, so uniqueness still holds for live accounts.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->string('phone', 20)->nullable(false)->change();
        });
    }
};
