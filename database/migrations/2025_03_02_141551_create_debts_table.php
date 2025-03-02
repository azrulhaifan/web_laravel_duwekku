<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->string('type'); // receivable (piutang), payable (hutang)
            $table->string('person_name');
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_settled')->default(false);
            $table->date('settled_date')->nullable();
            $table->foreignId('settlement_transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
