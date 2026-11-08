<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('farm_name')->default('Kamsya Farm');
            $table->string('owner_name')->default('Admin Kamsya');
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('barcode_prefix', 10)->default('8991');
            $table->unsignedInteger('low_stock_limit')->default(5);
            $table->timestamps();
        });

        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('goats', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('barcode', 40)->unique();
            $table->string('name');
            $table->string('type', 40);
            $table->string('gender', 10);
            $table->date('birth_date')->nullable();
            $table->decimal('weight', 8, 2)->default(0);
            $table->string('color', 40)->nullable();
            $table->string('origin', 20)->default('pembelian');
            $table->foreignId('mother_id')->nullable()->constrained('goats')->nullOnDelete();
            $table->date('entry_date');
            $table->string('status', 20)->default('tersedia');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'deleted_at']);
            $table->index('type');
            $table->index('entry_date');
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->date('date');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('goat_id')->unique()->constrained('goats');
            $table->decimal('price', 15, 2);
            $table->decimal('additional_cost', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['date', 'voided_at']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->date('date');
            $table->foreignId('goat_id')->constrained('goats');
            $table->foreignId('customer_id')->constrained('customers');
            $table->decimal('price', 15, 2);
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['date', 'voided_at']);
            $table->index('goat_id');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->date('date');
            $table->string('payable_type', 30);
            $table->unsignedBigInteger('payable_id');
            $table->decimal('amount', 15, 2);
            $table->string('method', 30)->default('tunai');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['payable_type', 'payable_id']);
            $table->index(['date', 'voided_at']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->date('date');
            $table->string('category', 40);
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['date', 'voided_at']);
            $table->index('category');
        });

        Schema::create('scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('goat_id')->nullable()->constrained('goats')->nullOnDelete();
            $table->string('barcode', 40);
            $table->boolean('found')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['barcode', 'created_at']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);
            $table->string('subject_type', 80)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->text('description')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('scan_logs');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('goats');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('sequences');
        Schema::dropIfExists('settings');
    }
};
