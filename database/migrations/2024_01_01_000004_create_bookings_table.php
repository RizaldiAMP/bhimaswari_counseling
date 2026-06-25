<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users');
            $table->foreignId('counselor_id')->constrained('users');
            $table->foreignId('service_price_id')->constrained('service_prices');
            $table->enum('service_type', ['chat', 'online', 'offline']);
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedInteger('price_at_booking');
            $table->dateTime('schedule_start');
            $table->dateTime('schedule_end');
            $table->enum('status', [
                'pending_payment',
                'pending_verification',
                'confirmed',
                'in_session',
                'completed',
                'cancelled',
                'expired',
                'pending_reschedule',
            ])->default('pending_payment');
            $table->text('intake_form');
            $table->boolean('informed_consent')->default(false);
            $table->string('meeting_link')->nullable();
            $table->string('meeting_location')->nullable();
            $table->unsignedTinyInteger('reschedule_count')->default(0);
            $table->dateTime('payment_deadline')->nullable();
            $table->timestamps();

            // Composite index untuk cegah double booking
            $table->unique(['counselor_id', 'schedule_start', 'schedule_end'], 'unique_slot');
            // Dashboard queries
            $table->index(['client_id', 'status']);
            $table->index(['counselor_id', 'status']);
            $table->index(['status', 'schedule_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
