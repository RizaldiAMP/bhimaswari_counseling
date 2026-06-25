<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
</script>

<template>
    <Head title="Kebijakan Privasi" />

    <GuestLayout>
        <div class="py-16 bg-white">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 prose prose-primary">
                <h1 class="text-3xl font-bold text-gray-900 mb-8">Kebijakan Privasi</h1>
                <p>Terakhir diperbarui: {{ new Date().toLocaleDateString('id-ID') }}</p>

                <h3>1. Pengumpulan Data</h3>
                <p>Kami mengumpulkan informasi yang Anda berikan secara langsung, termasuk data profil, riwayat screening, intake form, dan isi percakapan selama sesi konseling.</p>

                <h3>2. Penggunaan Data</h3>
                <p>Informasi Anda kami gunakan semata-mata untuk: <br />
                - Mendiagnosa dan memberikan pelayanan psikologis yang sesuai. <br />
                - Memproses transaksi layanan Anda. <br />
                - Menyediakan dukungan pengguna (customer service). <br />
                - Mengelola akun dan rekam medis Anda di sistem kami.</p>

                <h3>3. Keamanan Data</h3>
                <p>Kami sangat peduli terhadap keamanan data Anda. Semua komunikasi (chat dan form) dienkripsi. Kami membatasi akses ke data sensitif hanya kepada profesional (Psikolog/Konselor) yang ditugaskan khusus untuk menangani Anda.</p>

                <h3>4. Berbagi Informasi Pihak Ketiga</h3>
                <p>Bhimaswari <strong>tidak akan pernah</strong> menjual, menyewakan, atau menukar informasi pribadi atau rekam medis Anda kepada pihak ketiga untuk alasan apapun, kecuali diperintahkan oleh pengadilan/hukum berdasarkan perundang-undangan di Republik Indonesia.</p>

                <h3>5. Retensi Data</h3>
                <p>Rekam medis dan chat disimpan minimal 2 tahun dan maksimal 5 tahun sesuai dengan ketentuan hukum yang berlaku, sebelum dihancurkan secara permanen oleh sistem.</p>
            </div>
        </div>
    </GuestLayout>
</template>
