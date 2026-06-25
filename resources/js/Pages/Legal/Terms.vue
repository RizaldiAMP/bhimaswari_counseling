<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
</script>

<template>
    <Head title="Syarat & Ketentuan" />

    <GuestLayout>
        <div class="py-16 bg-white">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 prose prose-primary">
                <h1 class="text-3xl font-bold text-gray-900 mb-8">Syarat dan Ketentuan Layanan</h1>
                <p>Terakhir diperbarui: {{ new Date().toLocaleDateString('id-ID') }}</p>
                
                <h3>1. Pendaftaran dan Akun</h3>
                <p>Pengguna wajib memberikan informasi yang akurat saat mendaftar. Kerahasiaan kredensial akun merupakan tanggung jawab sepenuhnya dari pengguna.</p>

                <h3>2. Pemesanan Sesi</h3>
                <p>Pemesanan (booking) layanan konseling dianggap sah apabila klien telah menyelesaikan formulir pemesanan dan mengunggah bukti pembayaran yang valid dalam batas waktu yang ditentukan (15 menit).</p>

                <h3>3. Kebijakan Pembayaran</h3>
                <p>Pembayaran dilakukan secara transfer manual ke rekening resmi Bhimaswari. Verifikasi memakan waktu maksimal 12 jam. Bukti palsu dapat mengakibatkan penangguhan akun.</p>

                <h3>4. Kebijakan Reschedule (Penjadwalan Ulang)</h3>
                <p>Reschedule hanya dapat dilakukan maksimal 1 (satu) kali per tiket pemesanan, dengan syarat pengajuan maksimal H-1 (sebelum pukul 23:59 pada hari sebelum sesi dimulai). Kami tidak melayani pembatalan uang kembali (refund).</p>

                <h3>5. Privasi dan Kerahasiaan Sesi</h3>
                <p>Sesi chat, panggilan, dan rekam medis dijamin kerahasiaannya oleh Psikolog/Konselor sesuai Kode Etik Psikologi Indonesia. Data hanya digunakan untuk kepentingan terapi dan rekam medis internal.</p>

                <h3>6. Pengecualian Tanggung Jawab (Kondisi Darurat)</h3>
                <p>Platform layanan ini tidak dirancang untuk menangani kondisi darurat psikiatrik yang mengancam nyawa. Jika Anda atau orang terdekat sedang dalam kondisi gawat darurat (ingin menyakiti diri sendiri/orang lain), segera kunjungi IGD Rumah Sakit terdekat atau hubungi layanan darurat 119.</p>
            </div>
        </div>
    </GuestLayout>
</template>
