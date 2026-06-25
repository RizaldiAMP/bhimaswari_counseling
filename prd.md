# Product Requirements Document (PRD) 

## 1. Ringkasan Produk
- **Nama Produk:** Bhimaswari.id - Platform Layanan Konseling Online.
- **Tujuan Utama:** Memudahkan Klien memesan sesi konseling dan mengikuti sesi secara aman, terjadwal, dan terdokumentasi.
- **Model Bisnis:** Berbayar, checkout via transfer manual, verifikasi bukti transfer oleh Admin.
- **Timezone Sistem:** `Asia/Jakarta` (WIB) untuk seluruh jadwal, reminder, dan validasi waktu.

## 2. Role dan Hak Akses

| Role | Hak Akses Utama |
| --- | --- |
| `admin` | Verifikasi pembayaran, override jadwal, buat akun konselor awal, kelola harga layanan, monitoring chat read-only, audit operasional. |
| `counselor` | Kelola dan edit profil publik secara mandiri, atur availability, isi info meeting, jalankan sesi chat kliennya. |
| `client` | Registrasi/login, booking layanan, upload bukti transfer, ikut sesi sesuai jadwal, ajukan reschedule sesuai aturan. |

## 3. Alur End-to-End MVP

User memiliki dua pilihan alur untuk melakukan pemesanan (booking):

**Alur A: Klien — Eksplorasi Dulu, Login Kemudian**
1. **Guest browse:** pengunjung (tanpa login) melihat-lihat layanan, harga, profil konselor, dan jadwal kosong di Landing Page.
2. **Klik Pesan:** pengunjung mengklik tombol "Pesan" atau memilih slot jadwal tertentu.
3. **Mandatory Auth Target:** sistem mendeteksi guest dan memaksa pengunjung masuk ke halaman Login/Registrasi.
4. **Redirect Booking:** setelah berhasil login/registrasi, sistem otomatis mengarahkan user kembali (redirect) ke halaman form booking layanan/jadwal yang tadi dipilihnya.
5. **Proses Form:** klien mengisi intake form lalu submit booking.

**Alur B: Klien — Login Dulu, Pesan Kemudian**
1. **Login Langsung:** user masuk ke aplikasi melalui tombol "Masuk"/"Daftar" di navigasi atas.
2. **Client Dashboard:** setelah login, user masuk ke halaman dashboard klien.
3. **Pilih Layanan:** dari dashboard, user memilih menu pemesanan layanan dan jadwal.
4. **Proses Form:** klien memilih layanan, konselor, jadwal, mengisi intake form, lalu submit booking.

**Lanjutan Alur (Sisi Klien):**
- **Checkout manual:** klien mentransfer dana lalu mengunggah bukti transfer ke tiket booking.
1. Setelah submit booking, sistem membuat booking `pending_payment` dan lock slot.
2. Klien transfer dana dan upload bukti transfer.
3. Status berubah menjadi `pending_verification`.
4. Admin approve/reject bukti transfer.

---

**Alur C: Konselor (Counselor Flow)**
1. **Login & Dashboard:** Konselor login dan masuk ke Counselor Dashboard.
2. **Kelola Profil Publik:** Konselor dapat mengedit/mengisi informasi profil publik mereka secara mandiri (bio, foto, pengalaman). Profil ini akan otomatis tampil di *Section Tim Konselor dan Psikolog* pada **Landing Page**.
3. **Kelola Ketersediaan (Availability):** Konselor mengatur jadwal kosong mereka (berulang atau pengecualian khusus) yang akan tampil di halaman booking publik.
4. **Terima Booking Baru:** Konselor melihat daftar tiket booking baru yang sudah berstatus `confirmed` (telah dibayar klien & diverifikasi admin).
5. **Isi Info Meeting:** Untuk layanan *Online* atau *Offline*, konselor mengisi tautan zoom/gmeet atau lokasi pertemuan fisik di tiket tersebut. Klien akan otomatis menerima notifikasi/pembaruan info di dashboard mereka.
6. **Jalankan Sesi:** Saat waktu sesi tiba dan konselor memasuki *Chat Room*, timer otomatis mulai berjalan sebagai referensi durasi.
7. **Selesaikan Sesi:** Konselor menekan tombol **"Selesaikan Sesi"** untuk mengakhiri sesi secara resmi. **Timer habis tidak otomatis menutup sesi** — sesi tetap aktif hingga konselor benar-benar menekan tombol tersebut.

---

**Alur D: Admin (Admin Flow)**
1. **Login & Dashboard:** Admin login dan masuk ke Admin Dashboard.
2. **Kelola Data Induk:** Admin membuatkan **akun kredensial awal** untuk konselor baru (email & password bawaan) dan mengatur Harga Layanan spesifik. Admin *tidak* bertugas mengisi isi profil personal/foto konselor tersebut.
3. **Pusat Verifikasi (Core Job):** 
   - Admin memantau antrean pembayaran (status `pending_verification`).
   - Admin memeriksa kecocokan Mutasi Rekening Bank dengan Bukti Transfer yang diupload klien.
   - Admin mengeklik tombol **Approve** (ubah tiket jadi `confirmed` dan informasikan ke klien/konselor) atau **Reject** (tiket dikembalikan ke klien untuk diupload ulang buktinya).
4. **Reschedule & Override:** Jika sesi sudah terlewat karena terlambat approve, admin memfasilitasi (`pending_reschedule`) klien untuk memilih waktu baru tanpa perlu bayar lagi.
5. **Monitoring (Read-Only):** Admin dapat memantau percakapan di *Chat Room* yang sedang berjalan untuk tujuan operasional atau pemantauan darurat, namun tidak dapat ikut mengirim chat.

## 4. Business Rules (Final)

### 4.1 Booking dan Pembayaran
- Booking hanya bisa dibuat oleh `client` yang sudah login.
- Saat booking dibuat, slot di-lock selama **15 menit**.
- Jika bukti transfer belum diupload dalam 15 menit, booking menjadi `expired` dan slot otomatis dilepas.
- Bukti transfer wajib diupload pada tiket booking yang masih valid.
- Jika bukti ditolak (`rejected`), klien dapat upload ulang tanpa membuat booking baru selama booking belum `expired`.
- Endpoint submit booking, upload bukti, dan approve/reject wajib bersifat idempoten untuk mencegah double-submit.
- Sistem wajib menggunakan proteksi konkurensi (transaction/locking) agar 1 slot tidak bisa dikonfirmasi lebih dari 1 booking.
- Sistem tidak menyediakan payment gateway pada MVP.

### 4.2 SLA Verifikasi Admin
- Target operasional verifikasi bukti transfer: maksimal **12 jam** sejak bukti diupload.
- Jika bukti belum diverifikasi dan jadwal sesi sudah lewat, sistem otomatis memindahkan booking ke `pending_reschedule`.
- SLA 12 jam berlaku untuk hari operasional Senin-Minggu, termasuk hari libur nasional.

### 4.3 Reschedule
- Reschedule reguler maksimal 1x, pengajuan minimal H-1.
- Definisi H-1: paling lambat pukul 23:59 WIB pada 1 hari kalender sebelum `schedule_start`.
- Tidak ada refund untuk pembatalan sepihak oleh klien.
- **Kondisi khusus:** jika admin telat verifikasi dan sesi terlewat, status menjadi `pending_reschedule` dan **tidak mengurangi kuota reschedule klien**.

### 4.4 Akses Chat
- Chat hanya aktif untuk booking `confirmed` atau `in_session`.
- Tombol masuk chat klien aktif saat `now >= schedule_start` (WIB).
- Tombol masuk chat konselor aktif mulai **10 menit sebelum** jadwal (`schedule_start - 10m`) untuk persiapan.
- Jika sesi belum diselesaikan manual, akses chat tetap aktif hingga **60 menit setelah `schedule_end`**, lalu ditutup sistem.
- Timer habis tidak otomatis menyelesaikan sesi.

### 4.5 NFR, Privasi, dan Retensi Data
- Target performa MVP: endpoint baca p95 <= 500ms dan endpoint tulis p95 <= 1500ms (di luar proses upload file).
- Target ketersediaan layanan MVP: uptime bulanan >= 99.5% (di luar maintenance terjadwal).
- Data sensitif (intake form, chat, bukti transfer) wajib terenkripsi saat transit (HTTPS/TLS) dan akses dibatasi berbasis role.
- Retensi minimum: chat 2 tahun, bukti transfer 2 tahun, audit log 3 tahun; setelah lewat masa retensi dapat diarsipkan/dihapus sesuai kebijakan operasional.
- Sistem harus mendukung minimal 200 sesi chat aktif bersamaan tanpa degradasi mayor.


## 5. State Transition

> **Catatan:** Payment State dan Booking/Session State adalah **dua entity terpisah**.
> - **Booking State** (`bookings` table) melacak siklus hidup pemesanan secara keseluruhan.
> - **Payment State** (`payments` table) melacak siklus hidup bukti pembayaran yang terkait dengan satu booking.
> Saat payment state berubah, booking state ikut ter-update sesuai mapping di bawah.

### 5.1 Payment State (entity: `payments`)

| State | Trigger | Actor | Next State | Efek ke Booking State |
| --- | --- | --- | --- | --- |
| `pending_payment` | Booking dibuat | System | `pending_verification` setelah upload bukti | Booking tetap `pending_payment` → berubah ke `pending_verification` |
| `pending_verification` | Approve bukti | Admin | `approved` | Booking berubah ke `confirmed` |
| `pending_verification` | Reject bukti | Admin | `rejected` | Booking tetap `pending_verification` (klien bisa upload ulang) |
| `rejected` | Upload ulang bukti | Client | `pending_verification` | Booking tetap `pending_verification` |

> **Catatan:** Selama siklus reject–reupload, **Booking State tidak berubah** dan tetap berada di `pending_verification`. Hanya Payment State yang berpindah antara `rejected` ↔ `pending_verification`.
>
> **Konsistensi wajib implementasi:** Status Payment yang valid hanya `pending_payment`, `pending_verification`, `approved`, `rejected`. Tidak boleh ada status lain seperti `pending` atau `verified` di backend maupun frontend.

### 5.2 Booking/Session State (entity: `bookings`)

| State | Trigger | Actor | Next State |
| --- | --- | --- | --- |
| `pending_payment` | Booking submit berhasil, slot di-lock | System | `pending_payment` (state awal) |
| `pending_payment` | 15 menit terlewat tanpa upload bukti | System | `expired` |
| `pending_payment` | Bukti diupload | Client | `pending_verification` |
| `pending_verification` | Pembayaran disetujui (payment `approved`) | Admin | `confirmed` |
| `pending_verification` | Pembayaran ditolak (payment `rejected`) | Admin | Booking **tetap** `pending_verification` — klien dapat upload ulang bukti selama booking belum `expired` |
| `pending_verification` | Jadwal lewat sebelum approve | System | `pending_reschedule` |
| `confirmed` | Waktu sesi mulai | System | `in_session` |
| `confirmed` | Klien ajukan reschedule reguler (minimal H-1) | Client | `pending_reschedule` |
| `confirmed` | Dibatalkan sesuai kebijakan | Admin | `cancelled` |
| `in_session` | Konselor menekan tombol selesai | Counselor | `completed` |
| `in_session` | Tidak diselesaikan manual hingga `schedule_end + 60m` | System | `completed` |
| `pending_reschedule` | Jadwal baru dipilih dan disetujui | Admin/Client | `confirmed` |

## 6. Fitur MVP

### 6.1 Auth dan Akun
- Registrasi email/password.
- Login email/password.
- Laravel Sanctum untuk autentikasi sesi/token.
- Role-based access control (`admin`, `counselor`, `client`).
- Otorisasi role menggunakan middleware role dan policy per aksi sensitif.
- Tidak menggunakan CAPTCHA pada MVP.
- Rate limit keamanan:
  - Login: 5 percobaan / 10 menit per kombinasi IP+email, lock 15 menit.
  - Register: 3 percobaan / jam per IP.

### 6.2 Booking
- Pilih layanan: Chat (60), Online (60/90), Offline (60/90).
- Pilihan card layanan pada halaman booking:
  - Chat: 2 card (Psikolog 60 menit, Konselor 60 menit).
  - Online: 3 card (Psikolog 60 menit, Psikolog 90 menit, Konselor 60 menit).
  - Offline: 3 card (Psikolog 60 menit, Psikolog 90 menit, Konselor 60 menit).
- Pilih konselor dan slot jadwal.
- Isi intake form dan informed consent.
- Lock slot agar tidak terjadi double booking.

### 6.3 Payment Verification & Keamanan Upload
- Upload bukti transfer melalui endpoint Laravel dan disimpan di local server (`storage/app/private/payments`).
- **Security Constraint (Wajib diterapkan di Laravel):**
  - **Storage Access:** File bukti transfer disimpan di private storage dan tidak boleh diakses langsung via URL publik.
  - **MIME Type Validation:** Wajib melakukan verifikasi *magic number* (Hex signature) melalui validator/custom check di backend (bukan hanya ekstensi nama file atau header MIME dari client) untuk mencegah malware (misal file `.exe` yang di-rename jadi `.png`). File yang diizinkan hanya: `image/jpeg`, `image/png`, dan `image/webp`.
  - **Size Limit:** Maksimal ukuran file upload bukti transfer adalah 5MB.
  - **Authentication:** Endpoint upload bukti transfer hanya bisa diakses user `client` yang sudah login (`auth:sanctum`).
  - **File Naming:** Nama file disimpan dalam format acak/UUID, bukan nama file asli dari klien.
- Jika file berhasil tersimpan tetapi metadata di DB gagal disimpan, sistem harus menyediakan mekanisme retry/kompensasi (cleanup file orphan) agar status booking/payment tetap konsisten.
- Antrean verifikasi admin (approve/reject).
- Riwayat keputusan verifikasi tersimpan (audit basic).

### 6.4 Chat dan Sesi
- Tombol mulai sesi / masuk *Chat Room* akan tampil **abu-abu (disabled) dan tidak bisa diklik** jika waktu belum tiba.
- **Klien:** tombol akses chat aktif hanya jika status tiket `confirmed` dan waktu saat ini `>= schedule_start` (WIB).
- **Konselor:** tombol akses chat aktif jika status tiket `confirmed` dan waktu saat ini `>= schedule_start - 10 menit`.
- Realtime chat berbasis jadwal aktif.
- **Countdown timer mulai berjalan saat konselor memasuki Chat Room**, bukan saat status berubah ke `in_session`. Timer berfungsi sebagai referensi durasi sesi.
- **Timer habis TIDAK otomatis mengakhiri sesi.** Sesi tetap berjalan menunggu konselor menekan tombol **"Selesaikan Sesi"**.
- Jika konselor lupa menyelesaikan sesi secara manual, akses chat tetap aktif hingga **60 menit setelah `schedule_end`**, lalu sistem menutup sesi otomatis.
- Sesi baru berakhir (`completed`) ketika konselor menekan tombol selesai, baik sebelum maupun setelah timer habis.

### 6.5 Dashboard per Role
- **Client:** ringkasan booking, status pembayaran, jadwal mendatang/riwayat, akses chat.
- **Counselor:** ketersediaan jadwal, tiket masuk, info meeting, chat sesi.
- **Admin:** verifikasi pembayaran, manajemen tiket/reschedule, monitoring chat read-only.

### 6.6 Manajemen Harga Layanan (Admin)
- Admin dapat membuat, mengubah, mengaktifkan/nonaktifkan harga layanan.
- Harga dikonfigurasi per kombinasi jenis layanan, tipe praktisi, dan durasi.
- Perubahan harga hanya berlaku untuk booking baru.
- Riwayat perubahan harga wajib tercatat di audit log.

### 6.7 Notifikasi MVP
- Notifikasi in-app (database-driven) untuk event penting:
  - **Klien:** booking dikonfirmasi, pembayaran ditolak (perlu upload ulang), info meeting diisi konselor, pengingat sesi H-1.
  - **Konselor:** booking baru masuk (sudah `confirmed`), pengingat sesi H-1.
  - **Admin:** bukti transfer baru masuk ke antrean verifikasi.
- Notifikasi ditampilkan di dashboard masing-masing role dengan indikator badge (jumlah belum dibaca).
- Notifikasi email bersifat opsional pada MVP dan dapat ditambahkan bertahap.
- Pengiriman notifikasi menggunakan Laravel Notification + Queue agar tidak memblokir proses utama.

## 7. Phase 2 (Di luar MVP)
- Dashboard BI lanjutan (chart pendapatan, tren layanan, performa konselor).
- Export Excel multi-modul skala penuh.
- Pencarian transkrip lanjutan dan quality-control analytics.
- Automasi reminder bertingkat dan workflow operasional tambahan.
- Add-on laporan hasil konseling sebagai fitur transaksi terpisah (opsional).

## 8. Acceptance Criteria Ringkas

### 8.1 Auth
- User tidak bisa mengakses dashboard sebelum login.
- Endpoint role tertentu ditolak jika role tidak sesuai.
- Rate limit login/register aktif sesuai kebijakan keamanan.

### 8.2 Booking
- Slot yang sudah dipilih klien A tidak bisa dipilih klien B pada waktu yang sama.
- Booking tanpa intake form tidak dapat disubmit.

### 8.3 Payment
- Bukti transfer wajib ada sebelum tiket masuk antrean verifikasi.
- Admin approve/reject mengubah state tiket secara konsisten.

### 8.4 Exception Reschedule
- Jika admin telat approve hingga sesi lewat, sistem otomatis ubah ke `pending_reschedule`.
- Kasus tersebut tidak menambah hitungan kuota reschedule klien.

### 8.5 Chat
- Klien hanya dapat masuk chat jika status tiket `confirmed` dan waktu saat ini `>= schedule_start` (WIB).
- Konselor hanya dapat masuk chat jika status tiket `confirmed` dan waktu saat ini `>= schedule_start - 10 menit`.
- Tombol akses chat/sesi wajib disabled sebelum waktunya tiba untuk mencegah interaksi di luar jadwal.
- Admin dapat melihat chat tanpa hak kirim pesan.

### 8.6 Pricing
- Admin dapat mengubah harga layanan dari panel admin.
- Harga terbaru tampil di landing page dan form booking.
- Booking yang sudah tersimpan tetap menggunakan harga saat booking dibuat.

### 8.7 Dashboard & Profil
- Setiap role hanya dapat mengakses dashboard sesuai rolenya.
- Konselor dapat mengedit profil publik dan perubahan langsung tampil di landing page.
- Perubahan harga oleh admin tercatat di audit log.

### 8.8 Notifikasi
- Notifikasi in-app muncul di dashboard saat event penting terjadi.
- Badge indikator notifikasi menampilkan jumlah yang belum dibaca.

## 9. Arsitektur Teknis

### 9.1 Stack Final
- **Frontend:** Laravel Inertia.js + Vue 3 + TypeScript + Tailwind CSS + `tailwindcss-motion`.
- **UI & Form:** VueUse (opsional), Headless UI (opsional), Vue form validation (`vee-validate`) + `zod`.
- **Animasi & Interaksi UI:** Vue `<Transition>`/`<TransitionGroup>` untuk dashboard, GSAP dan `tailwindcss-motion` untuk motion storytelling pada landing page.
- **Icon Library:** `lucide-vue-next` sebagai ikon utama aplikasi.
- **Design Token (Primary Color):** `#823E87`.
- **Backend:** Laravel 11.
- **Auth:** Laravel Sanctum. Google OAuth direncanakan untuk **Phase 2**.
- **Database:** MySQL 8.
- **Cache/Queue:** Redis 7 + Laravel Queue + Horizon.
- **Realtime:** Laravel Reverb + Laravel Echo.
- **File Storage:** Local private storage (`storage/app/private`) untuk bukti transfer.
- **Infra:** Single VPS + Nginx + PHP-FPM + MySQL + Redis + Supervisor + Let's Encrypt.

### 9.2 Struktur Repository (Single App Laravel)
- `app/` -> domain logic, service layer, policy, job, event/listener.
- `routes/` -> route web, auth, dan endpoint aplikasi.
- `resources/js/` -> halaman Inertia (Vue), komponen UI, composables.
- `database/` -> migration, seeder, factory.
- `storage/app/private/payments` -> penyimpanan bukti transfer (private).
- `tests/` -> unit dan feature test.

## 10. Data Model v1 (Laravel Migration-Oriented)

### 10.1 Entity Inti
- `users`: data akun, role, status verifikasi.
- `counselor_profiles`: profil publik konselor, SIPP, spesialisasi.
- `availability_rules`: pola jadwal berulang.
- `availability_exceptions`: blokir tanggal/jam khusus.
- `bookings`: data inti pemesanan, layanan, jadwal, status.
- `payments`: data bukti transfer dan hasil verifikasi admin.
- `service_prices`: konfigurasi harga aktif per layanan, praktisi, dan durasi.
- `service_price_histories`: riwayat perubahan harga oleh admin.
- `chat_sessions`: room sesi aktif berdasarkan booking.
- `messages`: pesan realtime per sesi.
- `audit_logs`: jejak aksi penting admin.

### 10.2 Constraint Wajib
- Unique slot per konselor + rentang waktu untuk cegah double booking.
- Foreign key ketat antara booking, payment, session, dan message.
- Index pada kolom status + schedule_at untuk dashboard operasional.
- Seluruh transaksi booking kritikal wajib memakai DB transaction + row locking untuk mencegah race condition pada MySQL.

## 11. Sitemap MVP

### 11.1 Public
- Landing page.
- Daftar layanan dan harga.
- Tim konselor dan psikolog.
- Halaman auth (login/register).
- Syarat ketentuan dan privasi.

### 11.1.1 Landing Page Motion Requirement
- Landing page menjadi area dengan animasi paling dominan untuk memperkuat storytelling brand.
- Setiap layout utama Company Profile memiliki animasi masuk (stagger reveal): Hero, Tentang Kami, Visi-Misi, Nilai Perusahaan, Layanan, Tim Konselor dan Psikolog, Testimoni, Mitra, dan Kontak.
- Gunakan kombinasi animasi `fade`, `slide`, `scale`, dan `parallax ringan` dengan durasi halus agar tetap nyaman dibaca.
- Implementasi motion landing page menggunakan GSAP dan utilitas praktis dari `tailwindcss-motion` dengan fallback sederhana berbasis CSS/Vue transition.
- Animasi harus responsif dan tetap optimal di mobile serta tidak mengganggu aksesibilitas (dukungan `prefers-reduced-motion`).
- Prioritas performa: hindari animasi berat yang menurunkan Core Web Vitals.

### 11.1.2 Section Tim Konselor dan Psikolog (Landing Page)
- Landing page wajib menampilkan daftar profil `konselor` dan `psikolog` dalam satu section khusus.
- Setiap kartu profil minimal memuat foto, nama, gelar, peran (Konselor/Psikolog), spesialisasi ringkas, dan status SIPP bila tersedia.
- Kartu profil dapat dibuka ke detail (modal atau halaman detail) untuk menampilkan bio lengkap.
- Informasi di form ini ditarik secara dinamis dari pengaturan profil masing-masing konselor yang dapat mereka edit sendiri melalui dashboard Konselor. Urutan kemunculan di landing page dapat diurutkan oleh admin.

#### Data Awal Tim (Seed Content)
- **Associate Psikolog:**
  - Joko Tri Hartanto, S. Psi., M. Psi., Psikolog (SIPP 20230201-2023-0367).
  - Shofura Hanifah, S. Psi., M. Psi., Psikolog (SIPP 20231129-2023-01-1520).
  - Trya Dara Ruidahasi, S. Psi., M. Psi., Psikolog (SIPP 20240215-2024-01-4100).
  - Aisyah Tri Wardhani, S. Psi., Psikolog (SIPP 20240320-2024-01-4250).
  - Winda Kusuma Ayu, S. Psi., Psikolog (SIPP 20240501-2024-01-4600).
  - Nurul Nabila Annisa, S. Psi., Psikolog (SIPP 20240610-2024-01-4800).
  - Nadya Mubaraniz, S. Psi., Psikolog (SIPP 20240725-2024-01-5100).
  - Ghina Ciptadewi, S. Psi., Psikolog (SIPP 20240830-2024-01-5250).
  - Ghazali Fauzia, S. Psi., M. Psi., Psikolog (SIPP 20241005-2024-01-5400).
  - Ifti Aisha, S. Psi., M. Psi., Psikolog (SIPP 20241120-2024-01-5500).
  - Bagas Alam, S. Psi., M. Psi., Psikolog (SIPP 20250110-2025-01-0100).
- **Konselor:**
  - Rizkie Alief Madani, S. Psi.

### 11.1.3 Section Testimoni (Landing Page)
- Menampilkan daftar testimoni dan ulasan ("Apa Kata Klien Kami?") dari klien yang telah menggunakan layanan.
- Berisi rating bintang, ulasan teks, inisial klien, dan keterangan pendukung lainnya.
- Tampil dengan desain carousel atau grid card dengan efek animasi masuk.

### 11.2 Client Dashboard
- Home.
- Booking/paket konseling.
- Jadwal mendatang dan riwayat.
- Chat room.
- Pengaturan akun.

### 11.3 Counselor Dashboard
- Overview.
- Availability calendar.
- Info meeting (link online/lokasi offline).
- Chat sesi.
- Profil publik.

### 11.4 Admin Dashboard
- Overview operasional.
- Pusat verifikasi pembayaran.
- Manajemen harga layanan.
- Manajemen jadwal dan reschedule.
- Monitoring sesi (read-only).
- Manajemen akun awal konselor (buat akun kredensial).
- Manajemen klien.

## 12. Konten dan Kebijakan Layanan

### 12.1 Harga Layanan
- Chat: Psikolog Rp100.000 (60 menit), Konselor Rp50.000 (60 menit).
- Online: Psikolog Rp200.000 (60 menit) / Rp265.000 (90 menit), Konselor Rp150.000 (60 menit).
- Offline: Psikolog Rp250.000 (60 menit) / Rp315.000 (90 menit), Konselor Rp200.000 (60 menit).
- Harga dapat diubah admin melalui panel manajemen harga dan berlaku untuk booking baru.

### 12.2 Catatan Layanan
- Informasi biaya tambahan laporan hasil konseling Rp50.000 hanya sebagai keterangan layanan, bukan fitur transaksi terpisah di MVP.
- Tidak melayani refund; reschedule maksimal 1x sesuai ketentuan.

### 12.3 Kontak
- Telepon/WhatsApp: 0823-1146-7657
- Email: bhimaswarifamily@gmail.com
- Instagram: @bhimaswari.id
- Website: bhimaswari.id

## 13. Rencana Implementasi Perbaikan (Execution Plan)

### Phase 1 (Prioritas Kritis: Konsistensi State + Keamanan Payment)
- Status implementasi: **Selesai**.
- Standarisasi status payment ke 4 state final: `pending_payment`, `pending_verification`, `approved`, `rejected`.
- Hapus penggunaan status non-standar (`pending`, `verified`) di seluruh backend/frontend.
- Pusatkan upload bukti transfer melalui service backend agar validasi dan transisi state konsisten.
- Terapkan security upload sesuai PRD: private storage, validasi MIME + magic number, batas 5MB, nama file acak.
- Ubah auto-complete sesi dari `schedule_end + 120m` menjadi `schedule_end + 60m`.
- Sinkronkan PRD, job scheduler, controller, dan UI agar tidak ada mismatch aturan.

### Phase 2 (Akses Chat + Lifecycle Sesi)
- Status implementasi: **Selesai (MVP)**.
- Terapkan gate akses chat berbasis waktu untuk klien dan konselor.
- Implementasi start session, timer referensi, dan tombol selesai manual oleh konselor.
- Pastikan fallback auto-complete tetap berjalan di +60 menit.

### Phase 3 (Reschedule End-to-End)
- Status implementasi: **Selesai (MVP + hardening availability)**.
- Implementasi pengajuan reschedule reguler oleh klien (H-1, maksimal 1x).
- Implementasi proses admin untuk memilih/menetapkan jadwal pengganti.
- Pastikan kasus telat verifikasi admin tidak menambah kuota reschedule klien.
- Validasi jadwal reschedule wajib lolos dua lapis: konflik booking aktif dan ketersediaan konselor (availability rules + exceptions blocked/added).

### Phase 4 (Notifikasi In-App MVP)
- Status implementasi: **Selesai (MVP)**.
- Implementasi notifikasi berbasis database + queue untuk event penting client/counselor/admin.
- Tambahkan badge unread dan daftar notifikasi di dashboard per role.

### Phase 5 (Hardening Auth + Timezone)
- Status implementasi: **Selesai (MVP)**.
- Sinkronkan rate limit login/register sesuai kebijakan PRD.
- Set timezone sistem default ke `Asia/Jakarta` agar konsisten untuk seluruh jadwal dan validasi waktu.
- Flow guest booking kini mendukung redirect intent ke login/register lalu kembali ke form booking yang dipilih.

### Phase 6 (Admin Monitoring Read-Only)
- Status implementasi: **Selesai (MVP)**.
- Lengkapi monitoring chat admin dalam mode read-only tanpa hak kirim pesan.

### Phase 7 (Retensi Data + Hardening Payment Idempotency)
- Status implementasi: **Selesai (MVP hardening)**.
- Tambahkan job retensi terjadwal untuk pembersihan data melewati batas retensi: chat 2 tahun, bukti transfer 2 tahun, audit log 3 tahun.
- Tambahkan hardening idempotensi dan concurrency pada upload/approve/reject pembayaran untuk mencegah state race dan duplikasi proses.
