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
        Schema::table('account_balance_histories', function (Blueprint $table) {
            $table->boolean('is_custom_unit')->default(false)->after('description');
            $table->boolean('is_estimation')->default(false)->after('is_custom_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_balance_histories', function (Blueprint $table) {
            $table->dropColumn(['is_custom_unit', 'is_estimation']);
        });
    }
};
