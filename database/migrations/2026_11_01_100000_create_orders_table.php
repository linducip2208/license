<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_ref', 40)->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 30)->nullable();
            $table->unsignedInteger('amount');
            $table->string('status', 20)->default('UNPAID');
            $table->string('tripay_reference', 60)->nullable();
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_name')->nullable();
            $table->string('pay_code')->nullable();
            $table->text('pay_url')->nullable();
            $table->text('checkout_url')->nullable();
            $table->foreignId('license_id')->nullable()->constrained('licenses')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
