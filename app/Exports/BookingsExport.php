<?php

namespace App\Exports;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Illuminate\Support\Facades\Storage;

class BookingsExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithEvents
{
    protected Request $request;
    protected Collection $bookings;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    protected function getBookings(): Collection
    {
        if (isset($this->bookings)) {
            return $this->bookings;
        }

        $query = Booking::with(['client:id,name', 'counselor:id,name', 'payment']);

        if ($q = $this->request->input('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('id', (int) $q)
                    ->orWhereHas('client', fn ($s) => $s->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('counselor', fn ($s) => $s->where('name', 'like', "%{$q}%"));
            });
        }

        if ($status = $this->request->input('status')) {
            $query->where('status', $status);
        }

        if ($type = $this->request->input('service_type')) {
            $query->where('service_type', $type);
        }

        if ($dateFrom = $this->request->input('date_from')) {
            $query->whereDate('schedule_start', '>=', $dateFrom);
        }

        if ($dateTo = $this->request->input('date_to')) {
            $query->whereDate('schedule_start', '<=', $dateTo);
        }

        // Month filter (format: "2026-05" for May 2026)
        if ($month = $this->request->input('month')) {
            [$year, $mon] = explode('-', $month);
            $query->whereYear('created_at', (int) $year)
                  ->whereMonth('created_at', (int) $mon);
        }

        $this->bookings = $query->orderBy('created_at', 'desc')->get();

        return $this->bookings;
    }

    public function collection(): Collection
    {
        $statusLabels = [
            'pending_payment' => 'Menunggu Pembayaran',
            'pending_verification' => 'Menunggu Verifikasi',
            'confirmed' => 'Dikonfirmasi',
            'in_session' => 'Sesi Berjalan',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'expired' => 'Kadaluarsa',
            'pending_reschedule' => 'Reschedule',
        ];

        $serviceLabels = [
            'chat' => 'Chat',
            'online' => 'Online',
            'offline' => 'Offline',
        ];

        return $this->getBookings()->map(function ($booking) use ($statusLabels, $serviceLabels) {
            return [
                $booking->id,
                $booking->client?->name ?? '-',
                $booking->counselor?->name ?? '-',
                $serviceLabels[$booking->service_type] ?? $booking->service_type,
                $booking->duration_minutes,
                $booking->schedule_start?->format('d/m/Y H:i'),
                $booking->schedule_end?->format('d/m/Y H:i'),
                $statusLabels[$booking->status] ?? $booking->status,
                $booking->price_at_booking,
                $booking->created_at?->format('d/m/Y H:i'),
                '', // placeholder for Bukti Pembayaran (image will be inserted via AfterSheet)
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Klien',
            'Konselor',
            'Layanan',
            'Durasi (menit)',
            'Jadwal Mulai',
            'Jadwal Selesai',
            'Status',
            'Harga',
            'Tanggal Pemesanan',
            'Bukti Pembayaran',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE8E0F0'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $bookings = $this->getBookings();
                $imageColumn = 'K'; // Column K = Bukti Pembayaran

                $row = 2; // Start after header
                foreach ($bookings as $booking) {
                    if (
                        $booking->payment
                        && $booking->payment->proof_file_path
                        && Storage::disk('private')->exists($booking->payment->proof_file_path)
                    ) {
                        $filePath = Storage::disk('private')->path($booking->payment->proof_file_path);

                        try {
                            $drawing = new Drawing();
                            $drawing->setName('Bukti #' . $booking->id);
                            $drawing->setDescription('Bukti Transfer');
                            $drawing->setPath($filePath);
                            $drawing->setHeight(80);
                            $drawing->setCoordinates($imageColumn . $row);
                            $drawing->setOffsetX(5);
                            $drawing->setOffsetY(5);
                            $drawing->setWorksheet($sheet);

                            // Set row height to fit the image
                            $sheet->getRowDimension($row)->setRowHeight(65);
                        } catch (\Throwable $e) {
                            // If image can't be loaded, put a text fallback
                            $sheet->setCellValue($imageColumn . $row, 'Gagal memuat gambar');
                        }
                    } else {
                        $sheet->setCellValue($imageColumn . $row, '-');
                    }
                    $row++;
                }

                // Set column K width to accommodate images
                $sheet->getColumnDimension($imageColumn)->setWidth(18);
            },
        ];
    }
}
