<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            // First check if settled_at already exists
            if (!Schema::hasColumn('debts', 'settled_at')) {
                // If settled_at doesn't exist, check if settled_date exists
                if (Schema::hasColumn('debts', 'settled_date')) {
                    // Rename settled_date to settled_at
                    $table->renameColumn('settled_date', 'settled_at');
                } else {
                    // Neither column exists, so add settled_at
                    $table->timestamp('settled_at')->nullable();
                }
            }
            // If settled_at already exists, do nothing
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            if (Schema::hasColumn('debts', 'settled_at')) {
                $table->dropColumn('settled_at');
            }
        });
    }
};
