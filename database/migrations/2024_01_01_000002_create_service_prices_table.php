<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_prices', function (Blueprint $table) {
            $table->id();
            $table->enum('service_type', ['chat', 'online', 'offline']);
            $table->enum('practitioner_type', ['psychologist', 'counselor']);
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedInteger('price');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['service_type', 'practitioner_type', 'duration_minutes'], 'unique_service_combo');
            $table->index('is_active');
        });

        Schema::create('service_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_price_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->constrained('users');
            $table->unsignedInteger('old_price');
            $table->unsignedInteger('new_price');
            $table->string('change_reason')->nullable();
            $table->timestamps();

            $table->index('service_price_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_price_histories');
        Schema::dropIfExists('service_prices');
    }
};
