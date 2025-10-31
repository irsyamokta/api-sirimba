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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('member_id')->nullable();
            $table->uuid('category_id');
            $table->string('title');
            $table->float('amount');
            $table->timestamp('transaction_date');
            $table->string('note')->nullable();
            $table->enum('type', ['income', 'expense']);
            $table->string('evidence');
            $table->enum('payment_method', ['cash', 'bank_transfer']);
            $table->string('public_id')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
