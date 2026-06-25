# Controller Lama vs Baru

Dokumen ini merangkum perubahan controller yang sudah dilakukan pada fase perbaikan konsistensi payment dan implementasi Phase 3 (reschedule).

## 1) Admin Verification Controller

- File: `app/Http/Controllers/Admin/AdminVerificationController.php`

### Sebelum
- Method `approve()` melakukan update langsung di controller:
  - update payment ke `approved`
  - update booking ke `confirmed`
- Method `reject()` melakukan update langsung di controller:
  - update payment ke `rejected`
  - simpan `rejection_reason`

### Sesudah
- `approve()` memakai service:
  - `PaymentService->approve($booking->payment, $adminId)`
- `reject()` memakai service:
  - `PaymentService->reject($booking->payment, $adminId, $reason)`
- Audit log tetap dibuat di controller.

### Alasan perubahan
- Menjadikan `PaymentService` sebagai single source of truth untuk transisi state payment.
- Mengurangi risiko mismatch state antar endpoint/controller.

---

## 2) Client Booking Controller

- File: `app/Http/Controllers/Client/ClientBookingController.php`

### Sebelum
- Filter slot booked pada `create()` memakai:
  - `whereNotIn('status', ['expired', 'cancelled', 'rejected'])`
- Belum ada endpoint pengajuan reschedule oleh client.

### Sesudah
- Filter slot booked diperbaiki jadi:
  - `whereNotIn('status', ['expired', 'cancelled'])`
- Ditambahkan method baru:
  - `requestReschedule(Request $request, Booking $booking)`

### Perilaku baru pada `requestReschedule()`
- Validasi ownership booking (harus milik client yang login).
- Hanya untuk booking status `confirmed`.
- Kuota reschedule reguler maksimal 1x (`reschedule_count < 1`).
- Validasi aturan H-1.
- Cegah duplicate request jika masih ada reschedule `pending`.
- Cek konflik slot jadwal konselor (overlap booking aktif).
- Simpan data ke tabel `reschedules` status `pending`.
- Ubah status booking menjadi `pending_reschedule`.

### Alasan perubahan
- Memenuhi kebutuhan Phase 3: reschedule end-to-end dari sisi client.
- Memperbaiki kesalahan domain: `rejected` adalah payment state, bukan booking state.

---

## 3) Admin Reschedule Controller

- File: `app/Http/Controllers/Admin/AdminRescheduleController.php`

### Sebelum
- Hanya ada method `index()`.
- `index()` mengambil data dari booking status `pending_reschedule`.
- Belum ada aksi approve/reject reschedule.

### Sesudah
- `index()` diubah untuk membaca antrean dari entity `reschedules` status `pending`.
- Ditambahkan method:
  - `approve(Request $request, Reschedule $reschedule)`
  - `reject(Request $request, Reschedule $reschedule)`

### Perilaku baru pada `approve()`
- Validasi `new_schedule_start`.
- Lock transaksi (`DB::transaction` + row lock).
- Cek status request masih `pending`.
- Cek konflik slot jadwal konselor.
- Update reschedule:
  - set jadwal final baru
  - status `approved`
  - simpan `admin_notes` (opsional)
- Update booking:
  - `schedule_start`/`schedule_end` baru
  - status kembali `confirmed`
  - increment `reschedule_count` hanya jika pengaju adalah client
- Simpan audit log `reschedule_approved`.

### Perilaku baru pada `reject()`
- Validasi `admin_notes` (wajib).
- Lock transaksi.
- Cek status request masih `pending`.
- Update reschedule ke `rejected` + simpan catatan.
- Jika booking masih `pending_reschedule`, kembalikan ke `confirmed`.
- Simpan audit log `reschedule_rejected`.

### Alasan perubahan
- Memenuhi kebutuhan Phase 3 dari sisi admin.
- Menjadikan proses reschedule benar-benar operasional (bukan placeholder).

---

## 4) Client Payment Controller (catatan perubahan sebelumnya)

- File: `app/Http/Controllers/Client/ClientPaymentController.php`

### Sebelum
- Logic upload bukti pembayaran dan transisi state ada langsung di controller.

### Sesudah
- Upload bukti dipusatkan lewat service:
  - `PaymentService->uploadProof(...)`

### Alasan perubahan
- Menyatukan validasi upload, transisi state payment, dan update booking agar konsisten.

---

## 5) Ringkasan Dampak

- Controller sekarang lebih tipis (validasi request + response flow).
- Logic bisnis penting dipusatkan ke service atau transaksi terstruktur.
- Konsistensi status payment/booking lebih terjaga.
- Flow reschedule client-admin sudah berjalan end-to-end.
