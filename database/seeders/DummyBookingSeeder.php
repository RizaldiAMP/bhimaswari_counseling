<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ServicePrice;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\ChatSession;
use App\Models\Message;

class DummyBookingSeeder extends Seeder
{
    public function run()
    {
        $client = User::where('role', 'client')->first();
        $counselor = User::where('role', 'counselor')->first();
        $service = ServicePrice::where('service_type', 'chat')->first();

        if (!$client || !$counselor || !$service) {
            echo "Data kurang lengkap, pastikan seeder utama sudah dijalankan.\n";
            return;
        }

        $booking = Booking::create([
            'client_id' => $client->id,
            'counselor_id' => $counselor->id,
            'service_price_id' => $service->id,
            'service_type' => 'chat',
            'status' => 'in_session', // Sedang berjalan agar chat bisa dibuka
            'schedule_start' => now()->subMinutes(10), // Mulai 10 menit lalu
            'schedule_end' => now()->addMinutes(50), // Berakhir 50 menit lagi
            'duration_minutes' => 60,
            'price_at_booking' => $service->price,
            'payment_deadline' => now()->addMinutes(15),
            'intake_form' => 'Ini adalah keluhan percobaan buatan sistem agar Anda bisa mencoba fitur Chat Room yang sedang In Session.',
        ]);

        Payment::create([
            'booking_id' => $booking->id,
            'status' => 'approved',
            'proof_file_path' => 'dummy.jpg',
            'proof_original_name' => 'dummy.jpg',
            'proof_mime_type' => 'image/jpeg',
            'proof_file_size' => 1024,
            'verified_at' => now()
        ]);

        $chat = ChatSession::create([
            'booking_id' => $booking->id,
            'started_at' => now()->subMinutes(10)
        ]);

        Message::create([
            'chat_session_id' => $chat->id,
            'sender_id' => $counselor->id,
            'body' => 'Halo! Saya konselor Bhimaswari Anda. Selamat datang di sesi percobaan. Apakah antarmuka ruang chat ini sudah berfungsi dengan baik untuk Anda?'
        ]);

        echo "Dummy booking In-Session berhasil dibuat.\n";
    }
}
