<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\ServicePrice;
use App\Models\User;
use App\Jobs\ExpireUnpaidBookings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Exceptions\SlotAlreadyBookedException;

class BookingService
{
    public function createBooking(User $client, array $data): Booking
    {
        return DB::transaction(function () use ($client, $data) {
            $price = ServicePrice::findOrFail($data['service_price_id']);

            $scheduleStart = Carbon::parse($data['schedule_start']);
            $scheduleEnd = $scheduleStart->copy()->addMinutes($price->duration_minutes);

            // Anti-bentrok lintas SEMUA metode layanan (chat, online, offline):
            // Semua metode memakai waktu konselor yang sama.
            // Jika ada booking 90 menit di 09:00, maka slot 10:00 juga terblokir
            $existingBooking = Booking::where('counselor_id', $data['counselor_id'])
                ->where('schedule_start', '<', $scheduleEnd)   // booking lama mulai sebelum sesi baru selesai
                ->where('schedule_end', '>', $scheduleStart)   // booking lama selesai setelah sesi baru mulai
                ->whereNotIn('status', ['expired', 'cancelled'])
                ->where(function ($query) {
                    $query->where('status', '!=', 'pending_payment')
                          ->orWhere('payment_deadline', '>', now());
                })
                ->lockForUpdate()
                ->first();

            if ($existingBooking) {
                throw new SlotAlreadyBookedException('Slot jadwal ini bentrok dengan sesi yang sudah ada.');
            }

            $booking = Booking::create([
                'client_id'        => $client->id,
                'counselor_id'     => $data['counselor_id'],
                'service_price_id' => $price->id,
                'service_type'     => $price->service_type,
                'duration_minutes' => $price->duration_minutes,
                'price_at_booking' => $price->price,         // Snapshot harga
                'schedule_start'   => $data['schedule_start'],
                'schedule_end'     => Carbon::parse($data['schedule_start'])
                                        ->addMinutes($price->duration_minutes),
                'status'           => 'pending_payment',
                'intake_form'      => $data['intake_form'],
                'informed_consent' => true,
                'payment_deadline' => now()->addMinutes(15),  // Lock 15 menit
            ]);

            // Buat payment record
            $booking->payment()->create([
                'status' => 'pending_payment',
            ]);

            // Dispatch job: auto-expire jika 15 menit tidak bayar
            ExpireUnpaidBookings::dispatch($booking->id)
                ->delay(now()->addMinutes(15));

            return $booking;
        });
    }

    /**
     * Expire semua booking pending_payment yang sudah melewati batas waktu pembayaran.
     * Dipanggil oleh scheduled job dan bisa dipanggil kapan saja untuk membersihkan data.
     *
     * @return int Jumlah booking yang di-expire
     */
    public function expireOverdueBookings(): int
    {
        $overdueBookings = Booking::where('status', 'pending_payment')
            ->whereNotNull('payment_deadline')
            ->where('payment_deadline', '<', now())
            ->get();

        $count = 0;
        foreach ($overdueBookings as $booking) {
            /** @var Booking $booking */
            $this->expireBooking($booking);
            $count++;
        }

        return $count;
    }

    /**
     * Expire satu booking tertentu.
     * Status booking diubah menjadi 'expired', dan payment juga ditandai 'expired'.
     * Jadwal konselor otomatis kembali available karena status 'expired' tidak memblokir slot.
     */
    public function expireBooking(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $lockedBooking = Booking::lockForUpdate()->find($booking->id);

            if (!$lockedBooking || $lockedBooking->getRawOriginal('status') !== 'pending_payment') {
                return;
            }

            $lockedBooking->update(['status' => 'expired']);

            if ($lockedBooking->payment) {
                $lockedBooking->payment->update(['status' => 'expired']);
            }
        });
    }
}
