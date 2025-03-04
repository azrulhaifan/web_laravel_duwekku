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
        Schema::table('accounts', function (Blueprint $table) {
            // Rename existing currency column to currency_code for consistency
            $table->renameColumn('currency', 'currency_code');

            // Add new columns for custom unit support
            $table->string('custom_unit')->nullable()->after('currency_code');
            $table->decimal('custom_unit_amount', 15, 4)->nullable()->after('custom_unit');
            $table->decimal('estimated_balance', 15, 2)->nullable()->after('custom_unit_amount');
            $table->string('estimated_currency_code')->nullable()->after('estimated_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Remove the added columns
            $table->dropColumn([
                'custom_unit',
                'custom_unit_amount',
                'estimated_balance',
                'estimated_currency_code'
            ]);

            // Rename back to original column name
            $table->renameColumn('currency_code', 'currency');
        });
    }
};
