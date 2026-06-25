<?php

use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminVerificationController;
use App\Http\Controllers\Admin\AdminPriceController;
use App\Http\Controllers\Admin\AdminRescheduleController;
use App\Http\Controllers\Admin\AdminCounselorController;
use App\Http\Controllers\Admin\AdminClientController;
use App\Http\Controllers\Admin\AdminSessionController;
use App\Http\Controllers\Client\ClientDashboardController;
use App\Http\Controllers\Client\ClientBookingController;
use App\Http\Controllers\Client\ClientPaymentController;
use App\Http\Controllers\Client\ClientChatController;
use App\Http\Controllers\Client\ClientSessionController;
use App\Http\Controllers\Counselor\CounselorDashboardController;
use App\Http\Controllers\Counselor\CounselorBookingController;
use App\Http\Controllers\Counselor\CounselorChatController;
use App\Http\Controllers\Counselor\CounselorAvailabilityController;
use App\Http\Controllers\Counselor\CounselorProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicBookingController;
use Illuminate\Support\Facades\Route;

// === PUBLIC ===
Route::get('/', [LandingPageController::class, 'index'])->name('landing');
Route::get('/tim-kami', [TeamController::class, 'index'])->name('team');
Route::get('/syarat-ketentuan', [LegalController::class, 'terms'])->name('terms');
Route::get('/kebijakan-privasi', [LegalController::class, 'privacy'])->name('privacy');
Route::get('/booking', [PublicBookingController::class, 'index'])->name('booking.public');
Route::get('/api/counselors/{counselorUserId}/booking-data', [\App\Http\Controllers\Api\CounselorScheduleController::class, 'show'])->name('api.counselor.booking-data');

// Public Testimonial
Route::get('/ulasan', [\App\Http\Controllers\PublicTestimonialController::class, 'create'])->name('public.testimonials.create');
Route::post('/ulasan', [\App\Http\Controllers\PublicTestimonialController::class, 'store'])->name('public.testimonials.store');

// === CLIENT ===
Route::middleware(['auth', 'verified', 'active', 'role:client'])->prefix('client')->name('client.')->group(function () {
    Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('dashboard');
    Route::resource('bookings', ClientBookingController::class);
    Route::post('bookings/{booking}/payment', [ClientPaymentController::class, 'store'])->name('bookings.payment.store');
    Route::post('bookings/{booking}/reschedule', [ClientBookingController::class, 'requestReschedule'])->name('bookings.reschedule.request');
    Route::get('chat', [ClientChatController::class, 'index'])->name('chat.index');
    Route::get('chat/{booking}', [ClientChatController::class, 'show'])->name('chat.show');
    Route::post('chat/{booking}/messages', [ClientChatController::class, 'storeMessage'])->name('chat.messages.store');
    Route::get('sessions/online', [ClientSessionController::class, 'online'])->name('sessions.online');
    Route::get('sessions/offline', [ClientSessionController::class, 'offline'])->name('sessions.offline');

    // Testimonials
    Route::get('/beri-ulasan', [ClientBookingController::class, 'createTestimonial'])->name('testimonials.create');
    Route::post('/beri-ulasan', [ClientBookingController::class, 'storeTestimonial'])->name('testimonials.store');
});

// === COUNSELOR ===
Route::middleware(['auth', 'verified', 'active', 'role:counselor'])->prefix('counselor')->name('counselor.')->group(function () {
    Route::get('/dashboard', [CounselorDashboardController::class, 'index'])->name('dashboard');

    // Bookings
    Route::get('/bookings', [CounselorBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [CounselorBookingController::class, 'show'])->name('bookings.show');
    Route::put('/bookings/{booking}/meeting', [CounselorBookingController::class, 'updateMeetingInfo'])->name('bookings.meeting.update');
    Route::get('/chat', [CounselorChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{booking}', [CounselorChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{booking}/messages', [CounselorChatController::class, 'storeMessage'])->name('chat.messages.store');
    Route::post('/chat/{booking}/complete', [CounselorChatController::class, 'complete'])->name('chat.complete');

    // Sessions
    Route::get('/sessions/online', [\App\Http\Controllers\Counselor\CounselorSessionController::class, 'online'])->name('sessions.online');
    Route::get('/sessions/offline', [\App\Http\Controllers\Counselor\CounselorSessionController::class, 'offline'])->name('sessions.offline');

    // Availability
    Route::get('/availability', [CounselorAvailabilityController::class, 'index'])->name('availability.index');
    Route::post('/availability/rules', [CounselorAvailabilityController::class, 'storeRule'])->name('availability.rules.store');
    Route::delete('/availability/rules/{rule}', [CounselorAvailabilityController::class, 'destroyRule'])->name('availability.rules.destroy');
    Route::post('/availability/day-offs/toggle', [CounselorAvailabilityController::class, 'toggleDayOff'])->name('availability.dayoffs.toggle');
    Route::post('/availability/exceptions', [CounselorAvailabilityController::class, 'storeException'])->name('availability.exceptions.store');
    Route::delete('/availability/exceptions/{exception}', [CounselorAvailabilityController::class, 'destroyException'])->name('availability.exceptions.destroy');

    // Profile
    Route::get('/profile', [CounselorProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [CounselorProfileController::class, 'update'])->name('profile.update');
});

// === ADMIN ===
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Verifications
    Route::get('/verifications', [AdminVerificationController::class, 'index'])->name('verifications.index');
    Route::post('/verifications/{booking}/approve', [AdminVerificationController::class, 'approve'])->name('verifications.approve');
    Route::post('/verifications/{booking}/reject', [AdminVerificationController::class, 'reject'])->name('verifications.reject');
    Route::get('/verifications/{payment}/proof', [AdminVerificationController::class, 'showProof'])->name('verifications.proof');

    // Prices
    Route::get('/prices', [AdminPriceController::class, 'index'])->name('prices.index');
    Route::post('/prices', [AdminPriceController::class, 'store'])->name('prices.store');
    Route::put('/prices/{price}', [AdminPriceController::class, 'update'])->name('prices.update');
    Route::post('/prices/{price}/toggle', [AdminPriceController::class, 'toggleActive'])->name('prices.toggle');
    Route::delete('/prices/{price}', [AdminPriceController::class, 'destroy'])->name('prices.destroy');

    // Reschedules
    Route::get('/reschedules', [AdminRescheduleController::class, 'index'])->name('reschedules.index');
    Route::post('/reschedules/{reschedule}/approve', [AdminRescheduleController::class, 'approve'])->name('reschedules.approve');
    Route::post('/reschedules/{reschedule}/reject', [AdminRescheduleController::class, 'reject'])->name('reschedules.reject');

    // Counselors
    Route::get('/counselors', [AdminCounselorController::class, 'index'])->name('counselors.index');
    Route::post('/counselors', [AdminCounselorController::class, 'store'])->name('counselors.store');
    Route::put('/counselors/{counselor}', [AdminCounselorController::class, 'update'])->name('counselors.update');
    Route::post('/counselors/{counselor}/toggle', [AdminCounselorController::class, 'toggleActive'])->name('counselors.toggle');
    Route::delete('/counselors/{counselor}', [AdminCounselorController::class, 'destroy'])->name('counselors.destroy');

    // Clients
    Route::get('/clients', [AdminClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/{client}', [AdminClientController::class, 'show'])->name('clients.show');

    // Sessions Monitoring
    Route::get('/sessions', [AdminSessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/{booking}/chat', [AdminSessionController::class, 'showChat'])->name('sessions.chat.show');

    // Rekap Sesi
    Route::get('/rekap', [AdminSessionController::class, 'rekap'])->name('rekap.index');
    Route::get('/rekap/export', [AdminSessionController::class, 'export'])->name('rekap.export');

    // Testimoni
    Route::get('/testimonials', [\App\Http\Controllers\Admin\AdminTestimonialController::class, 'index'])->name('testimonials.index');
    Route::delete('/testimonials/{testimonial}', [\App\Http\Controllers\Admin\AdminTestimonialController::class, 'destroy'])->name('testimonials.destroy');
    Route::post('/testimonials/{testimonial}/toggle', [\App\Http\Controllers\Admin\AdminTestimonialController::class, 'toggleActive'])->name('testimonials.toggle');
});

// === PROFILE (all authenticated users) ===
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read_all');
});

require __DIR__.'/auth.php';

