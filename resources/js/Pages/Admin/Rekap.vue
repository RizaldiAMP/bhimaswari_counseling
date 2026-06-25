<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import PrimaryButton from '@/Components/ui/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';
import { ref } from 'vue';

interface Booking {
    id: number;
    client: { name: string } | null;
    counselor: { name: string } | null;
    service_type: string;
    duration_minutes: number;
    schedule_start: string;
    schedule_end: string;
    status: string;
    created_at: string;
    chat_session: { id: number } | null;
    payment: { id: number; proof_file_path: string | null } | null;
}

interface Summary { total: number; completed: number; in_session: number; confirmed: number; cancelled: number; expired: number; pending_reschedule: number; }
interface Filters { q: string; status: string; service_type: string; date_from: string; date_to: string; }

const props = defineProps<{ sessions: { data: Booking[]; links: any }; summary: Summary; filters: Filters; }>();

const showingProof = ref<string | null>(null);
const openProof = (paymentId: number) => { showingProof.value = route('admin.verifications.proof', paymentId); };
const closeProof = () => { showingProof.value = null; };

const filterQ = ref(props.filters.q);
const filterStatus = ref(props.filters.status);
const filterServiceType = ref(props.filters.service_type);
const filterDateFrom = ref(props.filters.date_from);
const filterDateTo = ref(props.filters.date_to);

const formatFullDate = (d: string) => new Date(d).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
const formatTime = (d: string) => new Date(d).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
const hasChat = (b: Booking) => b.chat_session !== null && b.chat_session !== undefined;
const serviceIcon = (t: string) => ({ chat: '💬', online: '🖥️', offline: '🏢' }[t] || '📋');
const serviceLabel = (t: string) => ({ chat: 'Chat', online: 'Online', offline: 'Offline' }[t] || t);

const getFilterParams = () => {
    const p: Record<string, string> = {};
    if (filterQ.value) p.q = filterQ.value;
    if (filterStatus.value) p.status = filterStatus.value;
    if (filterServiceType.value) p.service_type = filterServiceType.value;
    if (filterDateFrom.value) p.date_from = filterDateFrom.value;
    if (filterDateTo.value) p.date_to = filterDateTo.value;
    return p;
};

const submitFilter = () => router.get(route('admin.rekap.index'), getFilterParams(), { preserveState: true, preserveScroll: true });

const resetFilter = () => {
    filterQ.value = ''; filterStatus.value = ''; filterServiceType.value = ''; filterDateFrom.value = ''; filterDateTo.value = '';
    router.get(route('admin.rekap.index'), {}, { preserveState: false });
};

const exportMonth = ref('');

const monthOptions = (() => {
    const opts = [{ value: '', label: 'Semua Bulan' }];
    const now = new Date();
    for (let i = 0; i < 12; i++) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
        const val = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        const label = d.toLocaleDateString('id-ID', { year: 'numeric', month: 'long' });
        opts.push({ value: val, label });
    }
    return opts;
})();

const exportExcel = () => {
    const p = getFilterParams();
    if (exportMonth.value) p.month = exportMonth.value;
    const params = new URLSearchParams(p).toString();
    window.location.href = route('admin.rekap.export') + (params ? '?' + params : '');
};

const statusOptions = [
    { value: '', label: 'Semua Status' }, { value: 'completed', label: 'Selesai' },
    { value: 'in_session', label: 'Sesi Berjalan' }, { value: 'confirmed', label: 'Dikonfirmasi' },
    { value: 'pending_payment', label: 'Menunggu Pembayaran' }, { value: 'pending_verification', label: 'Menunggu Verifikasi' },
    { value: 'pending_reschedule', label: 'Reschedule' }, { value: 'cancelled', label: 'Dibatalkan' },
    { value: 'expired', label: 'Kadaluarsa' },
];
const serviceTypeOptions = [
    { value: '', label: 'Semua Layanan' }, { value: 'chat', label: 'Chat' },
    { value: 'online', label: 'Online' }, { value: 'offline', label: 'Offline' },
];

const hasActiveFilters = filterQ.value || filterStatus.value || filterServiceType.value || filterDateFrom.value || filterDateTo.value;
</script>

<template>
    <Head title="Rekap Sesi" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rekap Sesi</h2>
                <p class="text-sm text-gray-500 mt-0.5">Riwayat lengkap semua sesi konseling</p>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Stats (Dashboard style) -->
            <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div class="relative overflow-hidden rounded-lg bg-white px-4 pt-5 pb-5 shadow sm:px-6 sm:pt-6 border border-gray-100">
                    <dt>
                        <div class="absolute rounded-md bg-primary-100 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary-600"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
                        </div>
                        <p class="ml-16 truncate text-sm font-medium text-gray-500">Total Semua Sesi</p>
                    </dt>
                    <dd class="ml-16 pb-1"><p class="text-2xl font-semibold text-gray-900">{{ summary.total }}</p></dd>
                </div>
                <div class="relative overflow-hidden rounded-lg bg-green-50 px-4 pt-5 pb-5 shadow sm:px-6 sm:pt-6 border border-green-100">
                    <dt>
                        <div class="absolute rounded-md bg-green-200 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-700"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        </div>
                        <p class="ml-16 truncate text-sm font-medium text-green-800">Sesi Selesai</p>
                    </dt>
                    <dd class="ml-16 pb-1"><p class="text-2xl font-bold text-green-700">{{ summary.completed }}</p></dd>
                </div>
                <div class="relative overflow-hidden rounded-lg bg-white px-4 pt-5 pb-5 shadow sm:px-6 sm:pt-6 border border-blue-100">
                    <dt>
                        <div class="absolute rounded-md bg-blue-100 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <p class="ml-16 truncate text-sm font-medium text-gray-500">Sedang Berjalan</p>
                    </dt>
                    <dd class="ml-16 pb-1"><p class="text-2xl font-semibold text-gray-900">{{ summary.in_session + summary.confirmed }}</p></dd>
                </div>
                <div class="relative overflow-hidden rounded-lg bg-white px-4 pt-5 pb-5 shadow sm:px-6 sm:pt-6 border border-gray-100">
                    <dt>
                        <div class="absolute rounded-md bg-red-100 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-600"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        </div>
                        <p class="ml-16 truncate text-sm font-medium text-gray-500">Batal / Kadaluarsa</p>
                    </dt>
                    <dd class="ml-16 pb-1"><p class="text-2xl font-semibold text-gray-900">{{ summary.cancelled + summary.expired }}</p></dd>
                </div>
            </dl>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Filters -->
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-500"><path d="M12 20h9"/><path d="M16.376 3.622a1 1 0 0 1 3.002 3.002L7.368 18.635a2 2 0 0 1-.855.506l-2.872.838a.5.5 0 0 1-.62-.62l.838-2.872a2 2 0 0 1 .506-.854z"/></svg>
                        Daftar Semua Booking
                    </h3>
                    <form @submit.prevent="submitFilter" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wider">Cari</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8.5" cy="8.5" r="5.5"/><path d="M18.5 18.5L13 13"/></svg>
                                </span>
                                <input v-model="filterQ" type="text" placeholder="Nama atau Booking ID..." class="w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-gray-900 bg-white h-[38px]" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wider">Status</label>
                            <select v-model="filterStatus" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-gray-900 bg-white h-[38px]">
                                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value" class="text-gray-900">{{ opt.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wider">Layanan</label>
                            <select v-model="filterServiceType" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-gray-900 bg-white h-[38px]">
                                <option v-for="opt in serviceTypeOptions" :key="opt.value" :value="opt.value" class="text-gray-900">{{ opt.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wider">Dari Tanggal</label>
                            <input v-model="filterDateFrom" type="date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-gray-900 bg-white h-[38px]" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wider">Sampai</label>
                            <input v-model="filterDateTo" type="date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-gray-900 bg-white h-[38px]" />
                        </div>
                        <div class="lg:col-span-6 flex flex-wrap gap-2 pt-1 items-center">
                            <PrimaryButton size="sm" type="submit" class="!rounded-lg h-[36px] px-5 shadow-sm text-xs font-semibold">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-1.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                                Terapkan Filter
                            </PrimaryButton>
                            <button v-if="hasActiveFilters" type="button" @click="resetFilter" class="h-[36px] px-4 rounded-lg text-xs font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-100 border border-gray-200 bg-white shadow-sm transition-colors">Reset</button>
                            <span class="w-px h-6 bg-gray-200 mx-1"></span>
                            <select v-model="exportMonth" class="h-[36px] rounded-lg border-gray-300 shadow-sm text-xs font-semibold text-gray-700 bg-white focus:border-green-500 focus:ring-green-500 pr-8">
                                <option v-for="opt in monthOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                            <button type="button" @click="exportExcel" class="inline-flex items-center gap-1.5 h-[36px] px-4 rounded-lg text-xs font-semibold text-green-700 bg-green-50 border border-green-200 hover:bg-green-100 transition-colors shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                                Export Excel
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Empty -->
                <div v-if="sessions.data.length === 0" class="p-16 text-center text-gray-500">
                    <div class="w-16 h-16 mx-auto mb-5 rounded-full bg-gray-100 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-gray-400"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
                    </div>
                    <p class="text-lg font-medium text-gray-700">{{ hasActiveFilters ? 'Tidak ditemukan sesi sesuai filter' : 'Belum ada data sesi' }}</p>
                    <p class="text-sm text-gray-400 mt-1">{{ hasActiveFilters ? 'Coba ubah kriteria pencarian Anda.' : 'Data sesi booking akan tampil di sini.' }}</p>
                </div>

                <!-- Table -->
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Klien</th>
                                <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Konselor</th>
                                <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Layanan</th>
                                <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Jadwal</th>
                                <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Tgl Pemesanan</th>
                                <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-5 py-3 text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Bukti</th>
                                <th class="px-5 py-3 text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="s in sessions.data" :key="s.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-5 py-4 text-sm font-mono text-gray-400">#{{ s.id }}</td>
                                <td class="px-5 py-4 text-sm font-medium text-gray-900">{{ s.client?.name || '-' }}</td>
                                <td class="px-5 py-4 text-sm text-gray-600">{{ s.counselor?.name || '-' }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-sm">{{ serviceIcon(s.service_type) }}</span>
                                        <span class="text-sm font-medium text-gray-700">{{ serviceLabel(s.service_type) }}</span>
                                        <span class="text-xs text-gray-400">({{ s.duration_minutes }}m)</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm text-gray-800">{{ formatFullDate(s.schedule_start) }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">s/d {{ formatTime(s.schedule_end) }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600">{{ formatFullDate(s.created_at) }}</td>
                                <td class="px-5 py-4"><StatusBadge :status="s.status" /></td>
                                <td class="px-5 py-4 text-center">
                                    <button v-if="s.payment && s.payment.proof_file_path" @click="openProof(s.payment.id)" class="text-blue-600 hover:text-blue-800 text-xs font-medium underline">
                                        Lihat Bukti
                                    </button>
                                    <span v-else class="text-gray-300 text-xs">-</span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <Link v-if="hasChat(s)" :href="route('admin.sessions.chat.show', { booking: s.id })" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-primary-700 bg-primary-50 rounded-lg hover:bg-primary-100 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                        Lihat Chat
                                    </Link>
                                    <span v-else-if="s.service_type === 'chat'" class="text-xs text-gray-400 italic">Belum ada chat</span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="sessions.data.length > 0 && sessions.links" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/30">
                    <p class="text-sm text-gray-500">Menampilkan <span class="font-semibold text-gray-700">{{ sessions.data.length }}</span> sesi</p>
                    <div class="flex gap-1">
                        <template v-for="(link, i) in sessions.links" :key="i">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 text-sm rounded-lg transition-colors" :class="link.active ? 'bg-primary-600 text-white font-bold shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'" v-html="link.label" preserve-scroll />
                            <span v-else class="px-3 py-1.5 text-sm rounded-lg bg-gray-50 text-gray-300 border border-gray-100" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showingProof !== null" @close="closeProof">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Bukti Transfer</h3>
                    <button @click="closeProof" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="flex justify-center bg-gray-100 rounded p-2">
                    <img v-if="showingProof" :src="showingProof" alt="Bukti Transfer" class="max-w-full max-h-[70vh] object-contain rounded" />
                </div>
                <div class="mt-6 flex justify-end">
                    <PrimaryButton @click="closeProof">Tutup</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
