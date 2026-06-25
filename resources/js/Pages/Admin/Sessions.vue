<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import Modal from '@/Components/Modal.vue';
import { computed, ref } from 'vue';

interface Booking {
    id: number;
    client: { name: string } | null;
    counselor: { name: string } | null;
    service_type: string;
    duration_minutes: number;
    schedule_start: string;
    schedule_end: string;
    status: string;
    meeting_link: string | null;
    chat_session: { id: number } | null;
    payment: { id: number; proof_file_path?: string } | null;
}

const props = defineProps<{
    activeSessions: { data: Booking[]; links: any };
}>();

const showingProof = ref<string | null>(null);
const openProof = (paymentId: number) => { showingProof.value = route('admin.verifications.proof', paymentId); };
const closeProof = () => { showingProof.value = null; };

const formatDate = (dateString: string) =>
    new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const formatTime = (dateString: string) =>
    new Date(dateString).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

const hasChat = (booking: Booking): boolean =>
    booking.chat_session !== null && booking.chat_session !== undefined;

const serviceIcon = (type: string) => {
    switch(type) {
        case 'chat': return '💬';
        case 'online': return '🖥️';
        case 'offline': return '🏢';
        default: return '📋';
    }
};

const serviceLabel = (type: string) => {
    switch(type) {
        case 'chat': return 'Chat';
        case 'online': return 'Online';
        case 'offline': return 'Offline';
        default: return type;
    }
};

const inSessionCount = computed(() => props.activeSessions.data.filter(s => s.status === 'in_session').length);
const confirmedCount = computed(() => props.activeSessions.data.filter(s => s.status === 'confirmed').length);

const isSessionNow = (session: Booking): boolean => {
    const now = new Date();
    const start = new Date(session.schedule_start);
    const end = new Date(session.schedule_end);
    return now >= start && now <= end;
};

const getTimeUntil = (dateString: string): string => {
    const now = new Date();
    const target = new Date(dateString);
    const diffMs = target.getTime() - now.getTime();
    if (diffMs <= 0) return 'Sekarang';
    const diffMins = Math.floor(diffMs / 60000);
    const hours = Math.floor(diffMins / 60);
    const mins = diffMins % 60;
    if (hours > 0) return `${hours}j ${mins}m lagi`;
    return `${mins}m lagi`;
};
</script>

<template>
    <Head title="Monitoring Sesi Chat" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Monitoring Sesi Chat</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Pantau sesi chat yang sedang berjalan dan akan datang</p>
                </div>
                <Link :href="route('admin.rekap.index')" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl shadow-sm hover:bg-gray-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.376 3.622a1 1 0 0 1 3.002 3.002L7.368 18.635a2 2 0 0 1-.855.506l-2.872.838a.5.5 0 0 1-.62-.62l.838-2.872a2 2 0 0 1 .506-.854z"/></svg>
                    Rekap Sesi
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <!-- ══════════════════════════════════════════ -->
            <!-- Quick Stats                               -->
            <!-- ══════════════════════════════════════════ -->
            <dl class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <!-- Total Aktif -->
                <div class="relative overflow-hidden rounded-lg bg-white px-4 pt-5 pb-5 shadow sm:px-6 sm:pt-6 border border-gray-100 flex flex-col justify-between">
                    <dt>
                        <div class="absolute rounded-md bg-primary-100 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary-600"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
                        </div>
                        <p class="ml-16 truncate text-sm font-medium text-gray-500">Total Sesi Chat (24 Jam)</p>
                    </dt>
                    <dd class="ml-16 flex items-baseline pb-1">
                        <p class="text-2xl font-semibold text-gray-900">{{ activeSessions.data.length }}</p>
                    </dd>
                </div>

                <!-- Sedang Berjalan -->
                <div class="relative overflow-hidden rounded-lg bg-green-50 px-4 pt-5 pb-5 shadow sm:px-6 sm:pt-6 border border-green-100 flex flex-col justify-between">
                    <dt>
                        <div class="absolute rounded-md bg-green-200 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-700"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <p class="ml-16 truncate text-sm font-medium text-green-800">Sedang Berjalan</p>
                    </dt>
                    <dd class="ml-16 flex items-baseline pb-1">
                        <p class="text-2xl font-bold text-green-700">{{ inSessionCount }}</p>
                    </dd>
                </div>

                <!-- Menunggu -->
                <div class="relative overflow-hidden rounded-lg bg-white px-4 pt-5 pb-5 shadow sm:px-6 sm:pt-6 border border-blue-100 flex flex-col justify-between">
                    <dt>
                        <div class="absolute rounded-md bg-blue-100 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        </div>
                        <p class="ml-16 truncate text-sm font-medium text-gray-500">Menunggu Dimulai</p>
                    </dt>
                    <dd class="ml-16 flex items-baseline pb-1">
                        <p class="text-2xl font-semibold text-gray-900">{{ confirmedCount }}</p>
                    </dd>
                </div>
            </dl>

            <!-- ══════════════════════════════════════════ -->
            <!-- Session List                              -->
            <!-- ══════════════════════════════════════════ -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2.5">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                        </span>
                        Sesi Chat Aktif & Mendatang
                        <span v-if="activeSessions.data.length > 0" class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-bold text-green-700">
                            {{ activeSessions.data.length }}
                        </span>
                    </h3>
                    <span class="text-xs text-gray-400 font-medium">Data 24 jam ke depan</span>
                </div>

                <!-- Empty State -->
                <div v-if="activeSessions.data.length === 0" class="p-16 text-center text-gray-500">
                    <div class="w-16 h-16 mx-auto mb-5 rounded-full bg-gray-100 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                    </div>
                    <p class="text-lg font-medium text-gray-700">Tidak ada sesi chat aktif</p>
                    <p class="text-sm text-gray-400 mt-1">Sesi chat yang masuk 24 jam ke depan akan muncul di sini.</p>
                </div>

                <!-- Session Cards (Mobile-friendly) -->
                <div v-else>
                    <!-- Desktop Table -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50/80">
                                <tr>
                                    <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Klien & Konselor</th>
                                    <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Layanan</th>
                                    <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Jadwal</th>
                                    <th class="px-5 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-5 py-3 text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Bukti</th>
                                    <th class="px-5 py-3 text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="session in activeSessions.data"
                                    :key="session.id"
                                    class="hover:bg-gray-50/50 transition-colors"
                                    :class="{
                                        'bg-green-50/60': session.status === 'in_session',
                                    }"
                                >
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold"
                                                 :class="session.status === 'in_session' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'"
                                            >
                                                {{ session.client?.name?.charAt(0) || '?' }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900">{{ session.client?.name || '-' }}</p>
                                                <p class="text-xs text-gray-500">dengan {{ session.counselor?.name || '-' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-sm">{{ serviceIcon(session.service_type) }}</span>
                                            <span class="text-sm font-medium text-gray-700">{{ serviceLabel(session.service_type) }}</span>
                                            <span class="text-xs text-gray-400">({{ session.duration_minutes }}m)</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <p class="text-sm text-gray-800">{{ formatDate(session.schedule_start) }}</p>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="text-xs text-gray-400">s/d {{ formatTime(session.schedule_end) }}</span>
                                            <span v-if="isSessionNow(session)" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                                LIVE
                                            </span>
                                            <span v-else class="text-[10px] text-blue-500 font-medium">{{ getTimeUntil(session.schedule_start) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <StatusBadge :status="session.status" />
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <button v-if="session.payment && session.payment.proof_file_path" @click="openProof(session.payment.id)" class="text-blue-600 hover:text-blue-800 text-xs font-medium underline">
                                            Lihat
                                        </button>
                                        <span v-else class="text-gray-300 text-xs">-</span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <Link
                                            v-if="hasChat(session)"
                                            :href="route('admin.sessions.chat.show', { booking: session.id })"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-primary-700 bg-primary-50 rounded-lg hover:bg-primary-100 transition-colors"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                            Lihat Chat
                                        </Link>
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="md:hidden divide-y divide-gray-100">
                        <div
                            v-for="session in activeSessions.data"
                            :key="session.id"
                            class="p-5"
                            :class="session.status === 'in_session' ? 'bg-green-50/60' : ''"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold"
                                         :class="session.status === 'in_session' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'"
                                    >
                                        {{ session.client?.name?.charAt(0) || '?' }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ session.client?.name }}</p>
                                        <p class="text-xs text-gray-500">dengan {{ session.counselor?.name }}</p>
                                    </div>
                                </div>
                                <StatusBadge :status="session.status" />
                            </div>

                            <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
                                <span class="flex items-center gap-1">
                                    <span>{{ serviceIcon(session.service_type) }}</span>
                                    {{ serviceLabel(session.service_type) }} ({{ session.duration_minutes }}m)
                                </span>
                                <span>{{ formatDate(session.schedule_start) }}</span>
                                <span v-if="isSessionNow(session)" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                    LIVE
                                </span>
                            </div>

                            <div v-if="hasChat(session)" class="mt-3">
                                <Link
                                    :href="route('admin.sessions.chat.show', { booking: session.id })"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-primary-700 bg-primary-50 rounded-lg hover:bg-primary-100 transition-colors"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                    Lihat Chat
                                </Link>
                            </div>
                        </div>
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
