<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('to_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('type'); // income, expense, transfer
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->time('time')->nullable();
            $table->text('description')->nullable();
            $table->string('attachment')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_type')->nullable(); // daily, weekly, monthly, yearly
            $table->integer('recurring_day')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
