<?php

use App\Jobs\AutoCompleteSession;
use App\Jobs\AutoExpireConfirmed;
use App\Jobs\AutoRescheduleOverdue;
use App\Jobs\PurgeExpiredData;
use App\Jobs\SendSessionReminderNotifications;
use Illuminate\Support\Facades\Schedule;

// PRD 4.1: Auto-expire unpaid bookings (handled by individual delayed jobs, fallback sweeper here)
Schedule::call(function () {
    app(\App\Services\BookingService::class)->expireOverdueBookings();
})->everyMinute()->name('auto-expire-unpaid-fallback');

// PRD 5.2: Auto-start session saat schedule_start tiba (fallback untuk delayed job)
Schedule::call(function () {
    \App\Models\Booking::where('status', 'confirmed')
        ->where('schedule_start', '<=', now())
        ->update(['status' => 'in_session']);
})->everyMinute()->name('auto-start-session-fallback');
// PRD 4.2: Auto-reschedule jika jadwal lewat sebelum admin approve
Schedule::job(new AutoRescheduleOverdue)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('auto-reschedule-overdue');

// PRD 6.4: Auto-complete session jika schedule_end + 60m terlewat
Schedule::job(new AutoCompleteSession)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('auto-complete-session');

// Auto-expire confirmed bookings yang jadwalnya sudah lewat tapi tidak pernah dimulai
Schedule::job(new AutoExpireConfirmed)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('auto-expire-confirmed');

// PRD 6.7: Reminder H-1 untuk client dan counselor
Schedule::job(new SendSessionReminderNotifications)
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('send-session-reminder-notifications');

// PRD 4.5: Retensi data sensitif
Schedule::job(new PurgeExpiredData)
    ->dailyAt('02:15')
    ->withoutOverlapping()
    ->onOneServer()
    ->name('purge-expired-data');
