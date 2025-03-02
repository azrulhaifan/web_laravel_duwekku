<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_balance_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->decimal('old_balance', 15, 2);
            $table->decimal('new_balance', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->string('type'); // adjustment, transaction
            $table->string('source_type')->nullable(); // Transaction, Manual
            $table->unsignedBigInteger('source_id')->nullable(); // ID of the transaction if applicable
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_balance_histories');
    }
};
