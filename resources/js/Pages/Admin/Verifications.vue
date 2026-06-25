<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/ui/PrimaryButton.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import Modal from '@/Components/Modal.vue';
import { ref, computed, watch } from 'vue';
import type { PageProps } from '@/types';

interface Payment {
    id: number;
    status: string;
    proof_file_path: string;
    proof_original_name: string;
    proof_mime_type: string;
    proof_file_size: number;
    rejection_reason: string | null;
    created_at: string;
}

interface Booking {
    id: number;
    client: { id: number; name: string; email: string };
    counselor: { id: number; name: string };
    service_type: string;
    practitioner_type: string;
    duration_minutes: number;
    schedule_start: string;
    price_at_booking: number;
    status: string;
    payment: Payment | null;
    service_price: { service_type: string; practitioner_type: string; duration_minutes: number } | null;
}

interface RecentDecision {
    id: number;
    status: string;
    verified_at: string;
    rejection_reason: string | null;
    booking: { client: { name: string }; counselor: { name: string } };
    verifier: { name: string } | null;
}

const props = defineProps<{
    pendingVerifications: { data: Booking[]; links: any; meta?: any };
    recentDecisions: RecentDecision[];
}>();

const rejectingBooking = ref<number | null>(null);
const rejectForm = useForm({ rejection_reason: '' });

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(amount);

const formatDate = (dateString: string) =>
    new Date(dateString).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });

const formatBytes = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const approvePayment = (bookingId: number) => {
    if (confirm('Yakin ingin menyetujui pembayaran ini?')) {
        useForm({}).post(route('admin.verifications.approve', bookingId));
    }
};

const openReject = (bookingId: number) => {
    rejectingBooking.value = bookingId;
    rejectForm.rejection_reason = '';
};

const submitReject = () => {
    if (rejectingBooking.value) {
        rejectForm.post(route('admin.verifications.reject', rejectingBooking.value), {
            onSuccess: () => { rejectingBooking.value = null; },
        });
    }
};

const showingProof = ref<string | null>(null);

const openProof = (paymentId: number) => {
    showingProof.value = route('admin.verifications.proof', paymentId);
};

const closeProof = () => {
    showingProof.value = null;
};

const flashSuccess = computed(() => usePage<PageProps>().props.flash.success);
const showFlash = ref(true);

watch(flashSuccess, (newVal) => {
    if (newVal) {
        showFlash.value = true;
    }
});
</script>

<template>
    <Head title="Verifikasi Pembayaran" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Verifikasi Pembayaran</h2>
        </template>

        <div class="space-y-6">
            <!-- Flash Message -->
            <div v-if="flashSuccess && showFlash" class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-md flex justify-between items-start shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-bold text-green-800">{{ flashSuccess }}</p>
                    </div>
                </div>
                <button @click="showFlash = false" class="ml-auto bg-green-50 text-green-500 hover:text-green-600 focus:outline-none p-1.5 rounded-md transition-colors">
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                    </svg>
                </button>
            </div>

            <!-- Pending Verifications -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 bg-red-50">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-500"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/></svg>
                        Antrean Verifikasi
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-bold text-red-700">
                            {{ pendingVerifications.data.length }}
                        </span>
                    </h3>
                </div>

                <div v-if="pendingVerifications.data.length === 0" class="p-12 text-center text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4 text-gray-300"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                    <p class="text-lg font-medium">Semua bukti pembayaran sudah diverifikasi</p>
                    <p class="text-sm text-gray-400 mt-1">Tidak ada antrean verifikasi saat ini.</p>
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <div v-for="booking in pendingVerifications.data" :key="booking.id" class="p-6 hover:bg-gray-50 transition-colors">
                        <div class="flex flex-col lg:flex-row lg:items-start gap-4">
                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-2">
                                    <h4 class="font-bold text-gray-900">{{ booking.client.name }}</h4>
                                    <StatusBadge :status="booking.status" size="sm" />
                                </div>
                                <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm text-gray-600">
                                    <p>Email: <span class="text-gray-900">{{ booking.client.email }}</span></p>
                                    <p>Konselor: <span class="text-gray-900">{{ booking.counselor?.name }}</span></p>
                                    <p>Layanan: <span class="text-gray-900 capitalize">{{ booking.service_type }} {{ booking.duration_minutes }} menit</span></p>
                                    <p>Nominal: <span class="font-bold text-gray-900">{{ formatCurrency(booking.price_at_booking) }}</span></p>
                                    <p>Jadwal: <span class="text-gray-900">{{ formatDate(booking.schedule_start) }}</span></p>
                                </div>

                                <!-- Payment Proof Info -->
                                <div v-if="booking.payment" class="mt-3 p-3 bg-blue-50 rounded-lg border border-blue-100">
                                    <p class="text-xs font-semibold text-blue-700 uppercase mb-1">Info Bukti Transfer</p>
                                    <p class="text-sm text-gray-700">File: <span class="font-medium">{{ booking.payment.proof_original_name }}</span> ({{ formatBytes(booking.payment.proof_file_size) }})</p>
                                    <p class="text-xs text-gray-500 mt-1">Upload: {{ formatDate(booking.payment.created_at) }}</p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-col gap-2 shrink-0 lg:w-48 mt-4 lg:mt-0">
                                <PrimaryButton size="sm" class="bg-green-600 hover:bg-green-700 w-full justify-center" @click="approvePayment(booking.id)">
                                    Approve
                                </PrimaryButton>
                                <PrimaryButton size="sm" variant="danger" class="w-full justify-center" @click="openReject(booking.id)">
                                    Reject
                                </PrimaryButton>
                                <button v-if="booking.payment" @click="openProof(booking.payment.id)" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-bold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm mt-1">
                                    Lihat Bukti
                                </button>
                            </div>
                        </div>

                        <!-- Reject Form -->
                        <div v-if="rejectingBooking === booking.id" class="mt-4 p-4 bg-red-50 rounded-lg border border-red-200">
                            <label class="block text-sm font-medium text-red-700 mb-2">Alasan Penolakan</label>
                            <textarea v-model="rejectForm.rejection_reason" rows="3" class="w-full rounded-md border-red-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm" placeholder="Jelaskan alasan penolakan bukti transfer..."></textarea>
                            <div class="flex gap-2 mt-3">
                                <PrimaryButton size="sm" variant="danger" @click="submitReject" :disabled="!rejectForm.rejection_reason || rejectForm.processing">
                                    Kirim Penolakan
                                </PrimaryButton>
                                <PrimaryButton size="sm" variant="secondary" @click="rejectingBooking = null">Batal</PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Decisions -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900">Riwayat Keputusan Terakhir</h3>
                </div>
                <div v-if="recentDecisions.length === 0" class="p-8 text-center text-gray-500 text-sm">
                    Belum ada riwayat keputusan.
                </div>
                <table v-else class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Klien</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keputusan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bukti</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Verifikator</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="decision in recentDecisions" :key="decision.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-900">{{ decision.booking?.client?.name }}</td>
                            <td class="px-6 py-4">
                                <span :class="decision.status === 'approved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                                    {{ decision.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <button @click="openProof(decision.id)" class="text-blue-600 hover:text-blue-800 text-sm font-medium underline">
                                    Lihat Bukti
                                </button>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ decision.verifier?.name || '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(decision.verified_at) }}</td>
                        </tr>
                    </tbody>
                </table>
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
