<?php

declare(strict_types=1);

namespace App\Notifications;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class InAppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $event,
        private readonly string $title,
        private readonly string $message,
        private readonly ?string $url = null,
        private readonly array $meta = [],
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database', 'mail'];

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->getBooking();
        $targetUrl = $this->url ? (str_starts_with($this->url, 'http') ? $this->url : url($this->url)) : url('/');
        $mail = new MailMessage;
        $mail->subject($this->title);

        if (!$booking) {
            return $mail->view('emails.custom-notification', [
                'name' => $notifiable->name,
                'introLines' => $this->message,
                'bookingDetails' => null,
                'locationInfo' => null,
                'isOnline' => false,
                'rejectionReason' => null,
                'actionText' => 'Lihat Detail',
                'actionUrl' => $targetUrl,
                'outroLines' => null,
            ]);
        }

        $clientName = $booking->client->name ?? 'Klien';
        $counselorName = $booking->counselor->name ?? 'Konselor';
        $serviceType = $this->formatServiceType($booking->service_type);
        $tanggal = $booking->schedule_start->translatedFormat('l, d F Y');
        $jam = $booking->schedule_start->translatedFormat('H:i') . ' - ' . $booking->schedule_end->translatedFormat('H:i') . ' WIB';
        
        $isOnline = in_array($booking->service_type, ['chat', 'online']);
        $lokasi = $this->formatLocation($booking->meeting_location);

        $viewData = [
            'name' => '',
            'introLines' => '',
            'bookingDetails' => [
                'Layanan' => $booking->servicePrice->title ?? 'Layanan Konseling',
                'Metode' => $serviceType,
                'Konselor' => $counselorName,
                'Tanggal' => $tanggal,
                'Jam' => $jam,
            ],
            'locationInfo' => null,
            'isOnline' => $isOnline,
            'rejectionReason' => null,
            'actionText' => '',
            'actionUrl' => $targetUrl,
            'outroLines' => null,
        ];

        switch ($this->event) {
            case 'booking_created':
                $viewData['name'] = $clientName;
                $viewData['introLines'] = 'Booking layanan konseling Anda telah berhasil dibuat.';
                $viewData['actionText'] = 'Lihat Detail Booking & Bayar';
                $viewData['outroLines'] = "Silakan unggah bukti pembayaran dalam waktu 15 menit agar jadwal booking Anda tidak kedaluwarsa. Setelah pembayaran dikonfirmasi, Anda dapat masuk ke website sebelum sesi dimulai.\n\nJika ingin melakukan perubahan jadwal atau pembatalan, silakan melalui halaman booking pada website.";
                break;

            case 'booking_confirmed':
                $viewData['name'] = $clientName;
                $viewData['introLines'] = 'Pembayaran Anda telah diverifikasi. Booking layanan konseling Anda kini telah dikonfirmasi dan siap dilaksanakan.';
                $viewData['actionText'] = 'Lihat Detail Sesi';
                $viewData['locationInfo'] = $isOnline 
                    ? 'Silakan masuk ke website beberapa menit sebelum sesi dimulai untuk memulai sesi online dengan konselor.' 
                    : $lokasi;
                $viewData['outroLines'] = 'Jika ingin melakukan perubahan jadwal atau pembatalan, silakan melalui halaman booking pada website.';
                break;

            case 'new_confirmed_booking':
                $viewData['name'] = $counselorName;
                $viewData['introLines'] = 'Sesi konseling baru telah dikonfirmasi dan ditugaskan kepada Anda.';
                $viewData['bookingDetails'] = [
                    'Layanan' => $booking->servicePrice->title ?? 'Layanan Konseling',
                    'Metode' => $serviceType,
                    'Klien' => $clientName,
                    'Tanggal' => $tanggal,
                    'Jam' => $jam,
                ];
                $viewData['actionText'] = 'Lihat Detail Sesi';
                $viewData['locationInfo'] = $isOnline 
                    ? 'Silakan masuk ke dashboard konselor beberapa menit sebelum sesi dimulai untuk memulai sesi online dengan klien.' 
                    : $lokasi;
                break;

            case 'payment_proof_uploaded':
                $viewData['name'] = 'Admin';
                $viewData['introLines'] = 'Bukti transfer baru telah diunggah oleh klien dan menunggu verifikasi Anda.';
                $viewData['bookingDetails'] = [
                    'Booking ID' => '#' . $booking->id,
                    'Klien' => $clientName,
                    'Layanan' => $booking->servicePrice->title ?? 'Layanan Konseling',
                    'Metode' => $serviceType,
                    'Konselor' => $counselorName,
                    'Tanggal' => $tanggal,
                    'Jam' => $jam,
                ];
                $viewData['actionText'] = 'Verifikasi Pembayaran';
                break;

            case 'payment_rejected':
                $viewData['name'] = $clientName;
                $viewData['introLines'] = 'Mohon maaf, bukti pembayaran Anda untuk booking #' . $booking->id . ' ditolak oleh admin.';
                $viewData['rejectionReason'] = $booking->payment->rejection_reason ?? $this->meta['reason'] ?? 'Bukti transfer kurang jelas atau tidak valid.';
                $viewData['actionText'] = 'Unggah Ulang Bukti Pembayaran';
                $viewData['outroLines'] = 'Silakan unggah kembali bukti pembayaran yang valid agar jadwal Anda dapat segera dikonfirmasi.';
                break;

            case 'session_reminder_h1':
                $isCounselor = ($notifiable->id === $booking->counselor_id);
                if ($isCounselor) {
                    $viewData['name'] = $counselorName;
                    $viewData['introLines'] = 'Sesi konseling Anda bersama klien dijadwalkan besok.';
                    $viewData['bookingDetails'] = [
                        'Layanan' => $booking->servicePrice->title ?? 'Layanan Konseling',
                        'Metode' => $serviceType,
                        'Klien' => $clientName,
                        'Tanggal' => $tanggal,
                        'Jam' => $jam,
                    ];
                    $viewData['actionText'] = 'Lihat Detail Sesi';
                } else {
                    $viewData['name'] = $clientName;
                    $viewData['introLines'] = 'Sesi konseling Anda dijadwalkan besok. Ini adalah pengingat agar Anda dapat mempersiapkan diri.';
                    $viewData['actionText'] = 'Lihat Detail Sesi';
                    $viewData['locationInfo'] = $isOnline 
                        ? 'Silakan bersiap masuk ke website besok beberapa menit sebelum sesi dimulai.' 
                        : $lokasi;
                }
                break;

            case 'session_auto_completed':
                $isCounselor = ($notifiable->id === $booking->counselor_id);
                $viewData['name'] = $isCounselor ? $counselorName : $clientName;
                $otherPartyName = $isCounselor ? $clientName : $counselorName;
                $otherPartyRole = $isCounselor ? 'Klien' : 'Konselor';
                
                $viewData['introLines'] = 'Sesi konseling Anda telah diselesaikan otomatis oleh sistem karena waktu sesi telah berakhir.';
                $viewData['bookingDetails'] = [
                    'Layanan' => $booking->servicePrice->title ?? 'Layanan Konseling',
                    'Metode' => $serviceType,
                    $otherPartyRole => $otherPartyName,
                    'Tanggal' => $tanggal,
                    'Jam' => $jam,
                ];
                $viewData['actionText'] = 'Lihat Riwayat Sesi';
                $viewData['outroLines'] = 'Terima kasih telah mempercayakan layanan Anda bersama Bhimaswari Counseling.';
                break;

            case 'meeting_info_updated':
                $viewData['name'] = $clientName;
                $viewData['introLines'] = 'Konselor Anda telah memperbarui informasi link meeting atau lokasi untuk sesi konseling Anda.';
                $viewData['actionText'] = 'Lihat Detail Sesi';
                if ($isOnline) {
                    $meetingLink = $booking->meeting_link ?? 'Belum tersedia';
                    $viewData['locationInfo'] = 'Link Meeting Online: ' . $meetingLink;
                } else {
                    $viewData['locationInfo'] = $lokasi;
                }
                break;

            default:
                $viewData['name'] = $notifiable->name;
                $viewData['introLines'] = $this->message;
                $viewData['actionText'] = 'Lihat Detail';
                $viewData['outroLines'] = 'Terima kasih telah mempercayakan layanan Anda bersama Bhimaswari Counseling.';
                break;
        }

        return $mail->view('emails.custom-notification', $viewData);
    }


    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'meta' => $this->meta,
        ];
    }

    private function getBooking(): ?\App\Models\Booking
    {
        $bookingId = $this->meta['booking_id'] ?? null;
        if (!$bookingId) {
            return null;
        }

        return \App\Models\Booking::with(['client', 'counselor', 'servicePrice'])->find($bookingId);
    }

    private function formatServiceType(string $type): string
    {
        return match ($type) {
            'chat' => 'Chat Online',
            'online' => 'Video Call Online',
            'offline' => 'Tatap Muka Offline',
            default => ucfirst($type),
        };
    }

    private function formatLocation(?array $location): string
    {
        if (empty($location)) {
            return 'Kantor Bhimaswari Counseling';
        }

        $parts = [];
        if (!empty($location['place_name'])) {
            $parts[] = $location['place_name'];
        }
        if (!empty($location['address'])) {
            $parts[] = $location['address'];
        }
        if (!empty($location['landmark'])) {
            $parts[] = "Patokan: " . $location['landmark'];
        }
        if (!empty($location['city'])) {
            $parts[] = $location['city'];
        }

        return implode("\n", $parts);
    }
}
