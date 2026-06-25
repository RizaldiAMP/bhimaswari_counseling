# Laporan Bug dan Integrasi (error.md)

Dokumen ini berisi catatan bug, error, dan masalah integrasi yang ditemukan selama pengujian sistem serta langkah-langkah resolusi yang telah diterapkan secara sukses.

---

## 1. Bug: Mismatch Skema Kolom pada `AvailabilityService` (Kritis)
- **Modul Terkait:** Availability, Booking & Reschedule (Klien, Konselor, Admin)
- **Gejala / Error:** Query SQL error ketika melacak jadwal ketersediaan karena kolom `end_time` tidak ditemukan pada tabel `availability_rules` and `availability_exceptions`.
- **Penyebab:** Migrasi refaktorisasi terbaru (`2026_05_21_160000_refactor_availability_to_unified_slots.php`) menghapus kolom `end_time` untuk beralih ke model **Unified Slots** berbasis jam tunggal (`start_time` saja). Namun, `AvailabilityService.php` masih mempertahankan query berbasis rentang waktu (`end_time`).
- **Resolusi:** 
  Refaktorisasi fungsi `isCounselorAvailableForRange` di [AvailabilityService.php](file:///c:/PROJECT%20WEBSITE/bhimaswari_counseling/app/Services/AvailabilityService.php) agar selaras dengan skema baru:
  - Memvalidasi slot berdasarkan kecocokan spesifik `start_time` (bukan rentang waktu).
  - Melakukan cross-check dengan pengecualian jadwal khusus (tipe `added` dan `blocked` spesifik slot/hari).

---

## 2. Bug: `MassAssignmentException` pada `RescheduleServiceTest` (Blocker Pengujian)
- **Modul Terkait:** Reschedule Service (Admin & Klien)
- **Gejala / Error:** Pengujian unit/fitur reschedule gagal total dengan error:
  `Add fillable property [end_time] to allow mass assignment on [App\Models\AvailabilityRule].`
- **Penyebab:** Fungsi pembantu pengujian `createConfirmedBooking` mencoba menyisipkan `'end_time' => '18:00:00'` ke database, padahal kolom tersebut sudah dibuang dari skema dan array `$fillable` model `AvailabilityRule`.
- **Resolusi:**
  - Menghapus entri `end_time` dari kode seeding di [RescheduleServiceTest.php](file:///c:/PROJECT%20WEBSITE/bhimaswari_counseling/tests/Feature/Services/RescheduleServiceTest.php).
  - Menambahkan slot-slot jam tunggal (unified slots) yang dibutuhkan pengujian secara eksplisit.

---

## 3. Bug: Mismatch Indeks Hari `day_of_week` (Logika Integrasi)
- **Modul Terkait:** Reschedule & Slot Generation
- **Gejala / Error:** `RescheduleServiceTest` gagal dengan pesan: 
  `Jadwal yang dipilih berada di luar ketersediaan konselor.`
- **Penyebab:** Pada berkas pengujian, hari Senin diidentifikasi dengan indeks `1` dan Selasa dengan `2`. Padahal, standar logika internal di `SlotGeneratorService` dan `AvailabilityService` memetakan Senin = `0` dan Selasa = `1` (`$date->dayOfWeekIso - 1`).
- **Resolusi:**
  Memperbaiki data seeding di [RescheduleServiceTest.php](file:///c:/PROJECT%20WEBSITE/bhimaswari_counseling/tests/Feature/Services/RescheduleServiceTest.php) agar menggunakan indeks standard (Senin = 0, Selasa = 1) untuk rule ketersediaan.

---

## 4. Bug: Mismatch Pesan Validasi Double Booking (Minor)
- **Modul Terkait:** Booking (Klien)
- **Gejala / Error:** Pengujian `test_prevents_double_booking_on_same_slot` gagal karena assertion:
  `Failed asserting that an array contains 'Slot jadwal ini sudah terisi.'.`
- **Penyebab:** Kelas `BookingService` melempar `SlotAlreadyBookedException` dengan pesan `'Slot jadwal ini bentrok dengan sesi yang sudah ada.'`, namun pengujian mengharapkan pesan lama `'Slot jadwal ini sudah terisi.'`.
- **Resolusi:**
  Memperbarui baris ekspektasi pesan kesalahan di [BookingTest.php](file:///c:/PROJECT%20WEBSITE/bhimaswari_counseling/tests/Feature/BookingTest.php) menjadi `'Slot jadwal ini bentrok dengan sesi yang sudah ada.'` sehingga selaras dengan validasi riil dari service.

---

---

## 5. Bug: Data Hilang dan Gagal Login (Kritis)
- **Modul Terkait:** Basis Data, Autentikasi, Testing
- **Gejala / Error:** Seluruh data di database (termasuk user admin dan klien) terhapus setelah menjalankan `php artisan test`, menyebabkan gagal login.
- **Penyebab:** Konfigurasi `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:` di `phpunit.xml` dikomentari, sehingga trait `RefreshDatabase` pada pengujian unit menjalankan migrasi `fresh` langsung pada database MySQL (production/local default), yang mengakibatkan semua data terhapus.
- **Resolusi:**
  - Menghapus komentar (uncomment) variabel environment in-memory database di [phpunit.xml](file:///c:/PROJECT%20WEBSITE/bhimaswari_counseling/phpunit.xml).
  - Menjalankan ulang `php artisan migrate:fresh --seed` untuk merestorasi seluruh data esensial.

---

## 6. Bug: Seeder Ketersediaan Konselor Gagal karena Skema Baru
- **Modul Terkait:** Seeder Database (`CounselorAvailabilitySeeder`)
- **Gejala / Error:** Error SQL `Unknown column 'service_type' in 'field list'` saat menjalankan `php artisan db:seed`.
- **Penyebab:** Perubahan skema *unified slots* menghapus kolom `service_type` dan `end_time`, namun [CounselorAvailabilitySeeder.php](file:///c:/PROJECT%20WEBSITE/bhimaswari_counseling/database/seeders/CounselorAvailabilitySeeder.php) masih memasukkan nilai untuk kedua kolom tersebut.
- **Resolusi:**
  Memperbarui `CounselorAvailabilitySeeder.php` untuk membuang atribut `service_type` dan iterasi menyuntikkan slot jadwal jam tunggal per harinya.

---

## 7. Bug: Loop Reload Tanpa Henti (Infinite Reload Loop) di Halaman Detail Booking Klien (Kritis)
- **Modul Terkait:** Detail Booking & Pembayaran Klien (`Client/Bookings/Show.vue`)
- **Gejala / Error:** Saat klien membuka halaman detail booking dengan status `pending_payment` yang sisa waktu pembayarannya telah habis (`payment_deadline` berada di masa lampau), halaman melakukan refresh otomatis terus-menerus (infinite page reload loop) setiap 1.5 detik.
- **Penyebab:** Pada berkas [Show.vue](file:///c:/PROJECT%20WEBSITE/bhimaswari_counseling/resources/js/Pages/Client/Bookings/Show.vue) baris 136-138, ketika fungsi `calculateTimeLeft` mendeteksi sisa waktu habis (`diff <= 0`), ia memanggil `window.location.reload()` di dalam `setTimeout` dengan jeda 1500ms. Karena halaman di-mount ulang dengan status booking yang masih `pending_payment` dan deadline yang tetap kedaluwarsa, fungsi ini dipanggil kembali dan memicu siklus refresh tiada henti. Hal ini melumpuhkan seluruh UI bagi klien.
- **Resolusi:**
  - Mengganti `window.location.reload()` dengan `router.reload({ only: ['booking'] })` dari Inertia agar hanya melakukan partial prop refresh tanpa full page reload.
  - Menambahkan flag guard `hasReloaded` untuk mencegah reload berulang — hanya satu kali reload dipicu setelah waktu habis.
  - Import `router` dari `@inertiajs/vue3` ditambahkan ke file.

---

## 8. Bug: Timer Sesi Chat Tidak Berjalan Otomatis Saat Pertama Kali Dimulai (Major)
- **Modul Terkait:** Chat Room Klien & Konselor (`Client/Chat.vue` dan `Counselor/Chat.vue`)
- **Gejala / Error:** Ketika konselor mengirimkan pesan pertama untuk mengaktifkan sesi chat dan memulai countdown timer, timer di header chat (baik di sisi klien maupun konselor) tidak muncul/berjalan secara real-time. Pengguna harus melakukan refresh halaman secara manual agar timer countdown dan progress bar muncul dan berfungsi.
- **Penyebab:** Baik di [Client/Chat.vue](file:///c:/PROJECT%20WEBSITE/bhimaswari_counseling/resources/js/Pages/Client/Chat.vue) maupun [Counselor/Chat.vue](file:///c:/PROJECT%20WEBSITE/bhimaswari_counseling/resources/js/Pages/Counselor/Chat.vue), inisialisasi timer `startTimer()` hanya diletakkan di dalam `onMounted()` dengan kondisi `if (props.timerStartedAt)`. Ketika sesi chat pertama kali diaktifkan melalui pengiriman pesan pertama, properti `timerStartedAt` berubah dari `null` menjadi format datetime string via pembaruan properti Inertia (tanpa unmount component). Karena tidak ada `watch` terhadap `props.timerStartedAt`, perubahan nilai ini tidak memicu fungsi `startTimer()` untuk berjalan.
- **Resolusi:**
  Menambahkan watcher pada `props.timerStartedAt` di kedua file (`Client/Chat.vue` dan `Counselor/Chat.vue`) agar ketika nilainya berubah dari `null` menjadi datetime string, fungsi `startTimer()` otomatis terpanggil:
  ```typescript
  watch(() => props.timerStartedAt, (newVal) => {
      if (newVal) {
          startTimer();
      }
  });
  ```

---

## 9. Bug: Konselor Dinonaktifkan Masih Bisa Login dan Akses Sistem (Major)
- **Modul Terkait:** Autentikasi, Middleware, Manajemen Konselor
- **Gejala / Error:** Ketika admin menonaktifkan akun konselor melalui panel admin (toggle `is_active = false`), konselor tersebut masih bisa login ke sistem dan mengakses seluruh halaman konselor (dashboard, booking, chat, availability, profile). Jadwal ketersediaan konselor sudah hilang dari tampilan publik (karena filter `is_visible`), namun akses ke akun tidak dibatasi.
- **Penyebab:** 
  - [LoginRequest.php](file:///c:/PROJECT%20WEBSITE/bhimaswari_counseling/app/Http/Requests/Auth/LoginRequest.php) hanya memvalidasi email + password tanpa memeriksa status `is_active` pada user.
  - [EnsureUserHasRole.php](file:///c:/PROJECT%20WEBSITE/bhimaswari_counseling/app/Http/Middleware/EnsureUserHasRole.php) hanya memeriksa role pengguna, tidak memeriksa status aktif akun.
  - Tidak ada middleware yang mendeteksi apakah user yang sedang login sudah dinonaktifkan admin di tengah sesi.
- **Resolusi:**
  1. Menambahkan pengecekan `is_active` di [LoginRequest.php](file:///c:/PROJECT%20WEBSITE/bhimaswari_counseling/app/Http/Requests/Auth/LoginRequest.php): setelah `Auth::attempt` berhasil, jika `is_active = false`, langsung logout dan lempar error "Akun Anda telah dinonaktifkan".
  2. Membuat middleware baru [EnsureUserIsActive.php](file:///c:/PROJECT%20WEBSITE/bhimaswari_counseling/app/Http/Middleware/EnsureUserIsActive.php) yang auto-logout user jika `is_active = false` pada setiap request — menangani kasus admin menonaktifkan akun saat konselor sedang login.
  3. Mendaftarkan middleware `active` di [bootstrap/app.php](file:///c:/PROJECT%20WEBSITE/bhimaswari_counseling/bootstrap/app.php) dan menerapkannya pada route group `client` dan `counselor` di [web.php](file:///c:/PROJECT%20WEBSITE/bhimaswari_counseling/routes/web.php).
  4. Memperbarui tampilan [Login.vue](file:///c:/PROJECT%20WEBSITE/bhimaswari_counseling/resources/js/Pages/Auth/Login.vue) agar pesan nonaktifasi ditampilkan dengan warna merah (warning), bukan hijau (success).

---

## Kesimpulan Status Integrasi Fitur
Setelah perbaikan di atas diterapkan dan diuji secara E2E:
- **Pemesanan Layanan (Chat, Online, Offline):** Semua pemesanan diverifikasi aman dan terintegrasi dari mulai klien ke admin (`admin.verifications.index`), lalu masuk ke dasbor konselor (`counselor.bookings.index`). Pengujian _end-to-end_ khusus [EndToEndIntegrationTest.php](file:///c:/PROJECT%20WEBSITE/bhimaswari_counseling/tests/Feature/EndToEndIntegrationTest.php) ditambahkan dan memastikan flow ini mulus dengan mem-bypass waktu (time-travel testing).
- **Konselor & Admin:** Panel admin dapat mengonfirmasi, dan konselor dapat melihat, membuka _chat room_, hingga mengakhiri sesi.
- **Transisi Status & Durasi Sessi:** Transisi `pending_payment` -> `confirmed` -> `in_session` -> `completed` dijamin aman berkat update validasi integrasi yang akurat dan berhasil lolos pengujian test suite.
- **Semua Test Suite (33/33 skenario) Lulus 100%.**
