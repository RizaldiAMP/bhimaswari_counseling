<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/ui/PrimaryButton.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import { computed } from 'vue';

// Chart.js imports
import { Line, Doughnut, Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    ArcElement,
    Filler
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Filler);

interface Booking {
    id: number;
    client: { name: string };
    counselor: { name: string };
    service_type: string;
    schedule_start: string;
    schedule_end: string;
    price_at_booking: number;
    status: string;
    payment: { id: number; status: string; created_at: string } | null;
}

const props = defineProps<{
    stats: {
        total_bookings: number;
        active_bookings: number;
        pending_payments: number;
        pending_verifications: number;
        completed: number;
        cancelled: number;
        expired: number;
        total_revenue: number;
        total_counselors: number;
        total_clients: number;
    };
    upcomingSessions: Booking[];
    recentVerifications: Booking[];
    recentBookings: Booking[];
    chartData: {
        bookingsByMonth: { month: string; total_bookings: number; total_revenue: number }[];
        bookingsByService: { chat: number; online: number; offline: number };
        counselorPerformance: { name: string; total_sessions: number }[];
    };
}>();

const formatCurrency = (n: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(n);
const formatDate = (d: string) => new Date(d).toLocaleDateString('id-ID', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
const formatDateShort = (d: string) => new Date(d).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' });
const serviceIcon = (t: string) => ({ chat: '💬', online: '🖥️', offline: '🏢' }[t] || '📋');
const serviceLabel = (t: string) => ({ chat: 'Chat', online: 'Online', offline: 'Offline' }[t] || t);

const isNow = (s: Booking) => {
    const now = new Date();
    return now >= new Date(s.schedule_start) && now <= new Date(s.schedule_end);
};

// --- Chart Computed Properties ---

const lineChartData = computed(() => ({
    labels: props.chartData.bookingsByMonth.map(d => d.month),
    datasets: [
        {
            label: 'Total Booking Selesai',
            backgroundColor: 'rgba(168, 85, 247, 0.2)', // purple-500 with opacity
            borderColor: 'rgb(168, 85, 247)', // purple-500
            borderWidth: 2,
            pointBackgroundColor: 'rgb(168, 85, 247)',
            fill: true,
            tension: 0.4,
            data: props.chartData.bookingsByMonth.map(d => d.total_bookings),
        }
    ]
}));

const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { display: true, color: 'rgba(0,0,0,0.05)' } },
        x: { grid: { display: false } }
    }
};

const doughnutChartData = computed(() => ({
    labels: ['Chat', 'Online (Video)', 'Offline (Tatap Muka)'],
    datasets: [
        {
            backgroundColor: ['#c084fc', '#a855f7', '#7e22ce'], // purple shades
            hoverBackgroundColor: ['#d8b4fe', '#9333ea', '#6b21a8'],
            data: [
                props.chartData.bookingsByService.chat,
                props.chartData.bookingsByService.online,
                props.chartData.bookingsByService.offline,
            ],
            borderWidth: 0,
        }
    ]
}));

const doughnutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '70%',
    plugins: {
        legend: { position: 'bottom' as const, labels: { usePointStyle: true, padding: 20 } }
    }
};

const barChartData = computed(() => ({
    labels: props.chartData.counselorPerformance.map(d => d.name),
    datasets: [
        {
            label: 'Sesi Ditangani',
            backgroundColor: 'rgba(168, 85, 247, 0.8)', // purple-500
            borderRadius: 4,
            data: props.chartData.counselorPerformance.map(d => d.total_sessions),
        }
    ]
}));

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y' as const, // Horizontal
    plugins: { legend: { display: false } },
    scales: {
        x: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { display: true, color: 'rgba(0,0,0,0.05)' } },
        y: { grid: { display: false } }
    }
};
</script>

<template>
    <Head title="Admin Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-2xl text-gray-800 leading-tight">Dashboard Admin</h2>
                    <p class="text-sm text-gray-500 mt-1">Ringkasan aktivitas dan analitik performa platform.</p>
                </div>
            </div>
        </template>

        <div class="space-y-8">
            
            <!-- ========================================== -->
            <!-- 1. PRIORITAS UTAMA: VERIFIKASI PEMBAYARAN  -->
            <!-- ========================================== -->
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-600"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/></svg>
                        Perlu Tindakan: Verifikasi Pembayaran
                        <span v-if="stats.pending_verifications > 0" class="flex h-3 w-3 relative ml-1">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                        </span>
                    </h3>
                    <Link :href="route('admin.verifications.index')" class="text-sm font-medium text-red-600 hover:text-red-700">Lihat Semua Antrean &rarr;</Link>
                </div>

                <div v-if="stats.pending_verifications > 0" class="bg-white rounded-2xl shadow-md border-2 border-red-200 overflow-hidden">
                    <div class="bg-red-50 px-6 py-4 border-b border-red-100 flex items-center justify-between">
                        <p class="text-red-800 font-medium">Ada <strong>{{ stats.pending_verifications }}</strong> bukti transfer klien yang menunggu untuk Anda verifikasi.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Waktu Upload</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Klien</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Layanan</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nominal</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="b in recentVerifications" :key="b.id" class="hover:bg-red-50/30 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ b.payment ? formatDate(b.payment.created_at) : '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap"><p class="text-sm font-bold text-gray-900">{{ b.client.name }}</p></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700">
                                            {{ serviceIcon(b.service_type) }} {{ serviceLabel(b.service_type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">{{ formatCurrency(b.price_at_booking) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link :href="route('admin.verifications.index')">
                                            <PrimaryButton class="!bg-red-600 hover:!bg-red-700 shadow-sm animate-pulse">Cek Bukti & Proses</PrimaryButton>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-else class="bg-green-50 rounded-2xl border border-green-200 p-8 text-center flex flex-col items-center justify-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4 text-green-600">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <h4 class="text-lg font-bold text-green-800">Semua Pembayaran Clear!</h4>
                    <p class="text-green-700 mt-1">Tidak ada bukti transfer yang menunggu untuk diverifikasi saat ini.</p>
                </div>
            </section>

            <!-- ========================================== -->
            <!-- 2. QUICK STATS                             -->
            <!-- ========================================== -->
            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm flex flex-col justify-center">
                    <p class="text-sm font-medium text-gray-500 mb-1">Total Klien Terdaftar</p>
                    <p class="text-3xl font-black text-gray-900">{{ stats.total_clients }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm flex flex-col justify-center">
                    <p class="text-sm font-medium text-gray-500 mb-1">Total Konselor Aktif</p>
                    <p class="text-3xl font-black text-gray-900">{{ stats.total_counselors }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm flex flex-col justify-center">
                    <p class="text-sm font-medium text-gray-500 mb-1">Total Booking Keseluruhan</p>
                    <p class="text-3xl font-black text-gray-900">{{ stats.total_bookings }}</p>
                </div>

            </section>

            <!-- ========================================== -->
            <!-- 3. ANALITIK BISNIS & LAYANAN (CHART)       -->
            <!-- ========================================== -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Tren Booking (Line Chart) -->
                <section class="xl:col-span-2">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 h-[420px] flex flex-col">
                        <h3 class="text-lg font-bold text-gray-900 mb-6">Tren Booking Selesai (6 Bulan Terakhir)</h3>
                        <div class="grow relative">
                            <Line :data="lineChartData" :options="lineChartOptions" />
                        </div>
                    </div>
                </section>

                <!-- Proporsi Layanan (Doughnut Chart) -->
                <section>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 h-[420px] flex flex-col">
                        <h3 class="text-lg font-bold text-gray-900 mb-6">Proporsi Popularitas Layanan</h3>
                        <div class="grow relative">
                            <Doughnut :data="doughnutChartData" :options="doughnutChartOptions" />
                        </div>
                    </div>
                </section>
            </div>

            <!-- ========================================== -->
            <!-- 4. ANALITIK SDM & STATUS BOOKING           -->
            <!-- ========================================== -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Performa Konselor (Bar Chart) -->
                <section class="xl:col-span-2">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 h-[420px] flex flex-col">
                        <h3 class="text-lg font-bold text-gray-900 mb-6">Performa Konselor Teratas</h3>
                        <div class="grow relative">
                            <Bar :data="barChartData" :options="barChartOptions" />
                        </div>
                    </div>
                </section>

                <!-- Breakdown Status -->
                <section>
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm h-full">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Breakdown Status Booking</h3>
                        <p class="text-sm text-gray-500 mb-6">Rincian status seluruh booking yang ada di sistem saat ini.</p>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between items-center p-3 rounded-xl bg-emerald-50 text-sm">
                                <span class="font-medium text-emerald-800">Selesai</span>
                                <span class="font-bold text-emerald-600 bg-white px-3 py-1 rounded-full shadow-sm">{{ stats.completed }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 rounded-xl bg-blue-50 text-sm">
                                <span class="font-medium text-blue-800">Sesi Aktif / Konfirmasi</span>
                                <span class="font-bold text-blue-600 bg-white px-3 py-1 rounded-full shadow-sm">{{ stats.active_bookings }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 rounded-xl bg-yellow-50 text-sm">
                                <span class="font-medium text-yellow-800">Menunggu Pembayaran</span>
                                <span class="font-bold text-yellow-600 bg-white px-3 py-1 rounded-full shadow-sm">{{ stats.pending_payments }}</span>
                            </div>
                            <div class="flex justify-between items-center p-3 rounded-xl bg-red-50 text-sm">
                                <span class="font-medium text-red-800">Batal / Kadaluarsa</span>
                                <span class="font-bold text-red-600 bg-white px-3 py-1 rounded-full shadow-sm">{{ stats.cancelled + stats.expired }}</span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- ========================================== -->
            <!-- 5. OPERASIONAL HARIAN (TABEL)              -->
            <!-- ========================================== -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <!-- Sesi Mendatang -->
                <section>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            Sesi Mendatang (24 Jam Ke Depan)
                        </h3>
                        <Link :href="route('admin.sessions.index')" class="text-sm font-medium text-blue-600 hover:text-blue-700">Live Monitor &rarr;</Link>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div v-if="upcomingSessions.length === 0" class="p-10 text-center">
                            <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            </div>
                            <p class="text-gray-500 font-medium">Belum ada jadwal sesi untuk 24 jam ke depan.</p>
                        </div>
                        <ul v-else class="divide-y divide-gray-100">
                            <li v-for="s in upcomingSessions" :key="s.id" class="p-5 hover:bg-gray-50 transition-colors" :class="isNow(s) ? 'bg-green-50/50' : ''">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <div class="shrink-0 w-12 h-12 rounded-full flex items-center justify-center text-sm font-bold shadow-sm border"
                                                :class="isNow(s) ? 'bg-green-100 text-green-700 border-green-200' : 'bg-blue-100 text-blue-700 border-blue-200'">
                                            {{ s.client?.name?.charAt(0) || '?' }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <p class="text-base font-bold text-gray-900">{{ s.client.name }}</p>
                                                <span v-if="isNow(s)" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700 uppercase">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>LIVE
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-500 mt-0.5">dengan <span class="font-medium text-gray-700">{{ s.counselor.name }}</span></p>
                                        </div>
                                    </div>
                                    <div class="sm:text-right bg-gray-50 sm:bg-transparent p-3 sm:p-0 rounded-lg sm:rounded-none">
                                        <p class="text-sm font-bold text-gray-900">{{ formatDate(s.schedule_start) }}</p>
                                        <p class="text-xs font-medium text-gray-600 mt-1 flex items-center sm:justify-end gap-1.5">
                                            {{ serviceIcon(s.service_type) }} {{ serviceLabel(s.service_type) }}
                                        </p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </section>

                <!-- Riwayat Booking -->
                <section>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Riwayat Booking Terbaru</h3>
                        <Link :href="route('admin.rekap.index')" class="text-sm font-medium text-primary-600 hover:text-primary-700">Rekap Lengkap &rarr;</Link>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <ul class="divide-y divide-gray-100">
                            <li v-for="b in recentBookings" :key="b.id" class="px-5 py-4 hover:bg-gray-50 transition-colors flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ b.client.name }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ formatDateShort(b.schedule_start) }} • {{ serviceLabel(b.service_type) }}</p>
                                </div>
                                <StatusBadge :status="b.status" size="sm" />
                            </li>
                        </ul>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
