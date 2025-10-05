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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('payment_id')->unique(); // Xendit payment ID
            $table->string('external_id')->unique(); // Our reference ID
            $table->string('payment_method'); // virtual_account, credit_card, ewallet, etc
            $table->string('payment_channel')->nullable(); // BCA, BNI, OVO, GOPAY, etc
            $table->enum('status', ['pending', 'paid', 'expired', 'failed', 'cancelled'])->default('pending');
            $table->decimal('amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->nullable();
            $table->string('currency', 3)->default('IDR');
            $table->text('payment_url')->nullable(); // URL for payment
            $table->json('xendit_response')->nullable(); // Store full Xendit response
            $table->json('callback_data')->nullable(); // Store webhook callback data
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            
            $table->index(['order_id', 'status']);
            $table->index(['payment_id']);
            $table->index(['external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
