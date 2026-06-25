<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Client {
    id: number;
    name: string;
    email: string;
    whatsapp: string | null;
    created_at: string;
    bookings_count: number;
}

interface BookingDetail {
    id: number;
    service_type: string;
    duration: number;
    price: number;
    status: string;
    schedule_start: string | null;
    schedule_end: string | null;
    counselor_name: string;
    payment_status: string | null;
    created_at: string;
}

const props = defineProps<{
    clients: { data: Client[]; links: any };
}>();

const showModal = ref(false);
const selectedClient = ref<{ id: number; name: string; email: string } | null>(null);
const bookings = ref<BookingDetail[]>([]);
const loading = ref(false);

const formatDate = (dateString: string) =>
    new Date(dateString).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' });

const formatDateTime = (dateString: string | null) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const formatPrice = (price: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(price);

const serviceTypeLabel = (type: string) => {
    const map: Record<string, string> = {
        chat: 'Chat',
        online: 'Online',
        offline: 'Offline',
    };
    return map[type] || type;
};

const openDetail = async (client: Client) => {
    showModal.value = true;
    loading.value = true;
    selectedClient.value = { id: client.id, name: client.name, email: client.email };
    bookings.value = [];

    try {
        const response = await fetch(`/admin/clients/${client.id}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await response.json();
        bookings.value = data.bookings;
    } catch (e) {
        console.error('Failed to fetch client bookings', e);
    } finally {
        loading.value = false;
    }
};

const closeModal = () => {
    showModal.value = false;
    selectedClient.value = null;
    bookings.value = [];
};
</script>

<template>
    <Head title="Manajemen Klien" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manajemen Klien</h2>
        </template>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-bold text-gray-900">Daftar Klien Terdaftar</h3>
            </div>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. HP</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Booking</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bergabung</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="client in clients.data" :key="client.id" class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 text-xs font-bold">
                                    {{ client.name.charAt(0).toUpperCase() }}
                                </div>
                                <span class="text-sm font-medium text-gray-900">{{ client.name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ client.email }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ client.whatsapp || '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                                {{ client.bookings_count }} booking
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(client.created_at) }}</td>
                        <td class="px-6 py-4 text-right">
                            <button
                                @click="openDetail(client)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg
                                       bg-primary-50 text-primary-700 border border-primary-200
                                       hover:bg-primary-100 hover:border-primary-300
                                       transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                Detail
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="clients.data.length === 0" class="p-12 text-center text-gray-500">
                <p class="text-lg font-medium">Belum ada klien terdaftar</p>
            </div>
        </div>

        <!-- Center-aligned Popup Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

                    <!-- Panel -->
                    <Transition
                        enter-active-class="transition duration-300 ease-out"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition duration-200 ease-in"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4"
                    >
                        <div v-if="showModal" class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl flex flex-col max-h-[85vh] overflow-hidden transform transition-all">
                            <!-- Header -->
                            <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-primary-50 to-white flex-shrink-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 text-sm font-bold shadow-sm">
                                            {{ selectedClient?.name?.charAt(0)?.toUpperCase() }}
                                        </div>
                                        <div class="text-left">
                                            <h3 class="text-lg font-bold text-gray-900 leading-tight">{{ selectedClient?.name }}</h3>
                                            <p class="text-xs text-gray-500">{{ selectedClient?.email }}</p>
                                        </div>
                                    </div>
                                    <button
                                        @click="closeModal"
                                        class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 overflow-y-auto px-6 py-5 text-left">
                                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">
                                    Riwayat Booking
                                </h4>

                                <!-- Loading State -->
                                <div v-if="loading" class="flex flex-col items-center justify-center py-16 gap-3">
                                    <div class="h-8 w-8 rounded-full border-2 border-primary-200 border-t-primary-600 animate-spin"></div>
                                    <span class="text-sm text-gray-400">Memuat riwayat...</span>
                                </div>

                                <!-- Empty State -->
                                <div v-else-if="bookings.length === 0" class="flex flex-col items-center justify-center py-16 gap-3 text-center">
                                    <div class="h-14 w-14 rounded-full bg-gray-100 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                            <path d="M15 2H9a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1Z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm text-gray-400">Belum ada riwayat booking</p>
                                </div>

                                <!-- Booking Cards -->
                                <div v-else class="space-y-3">
                                    <div
                                        v-for="booking in bookings"
                                        :key="booking.id"
                                        class="rounded-xl border border-gray-100 bg-white shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden"
                                    >
                                        <!-- Card Header -->
                                        <div class="px-4 py-3 bg-gray-50/70 flex items-center justify-between border-b border-gray-100">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-gray-400">#{{ booking.id }}</span>
                                                <span class="inline-flex items-center gap-1 rounded-md bg-primary-50 px-2 py-0.5 text-xs font-semibold text-primary-700">
                                                    {{ serviceTypeLabel(booking.service_type) }}
                                                    <span class="text-primary-400">·</span>
                                                    {{ booking.duration }} mnt
                                                </span>
                                            </div>
                                            <StatusBadge :status="booking.status" />
                                        </div>

                                        <!-- Card Body -->
                                        <div class="px-4 py-3 space-y-2">
                                            <div class="flex items-center gap-2 text-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                                    <circle cx="9" cy="7" r="4"/>
                                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                                </svg>
                                                <span class="text-gray-700">{{ booking.counselor_name }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                                                    <line x1="16" x2="16" y1="2" y2="6"/>
                                                    <line x1="8" x2="8" y1="2" y2="6"/>
                                                    <line x1="3" x2="21" y1="10" y2="10"/>
                                                </svg>
                                                <span class="text-gray-700">{{ formatDateTime(booking.schedule_start) }}</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2 text-sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <line x1="12" x2="12" y1="2" y2="22"/>
                                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                                    </svg>
                                                    <span class="font-semibold text-gray-900">{{ formatPrice(booking.price) }}</span>
                                                </div>
                                                <StatusBadge v-if="booking.payment_status" :status="booking.payment_status" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex-shrink-0">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-400">
                                        Total: {{ bookings.length }} booking
                                    </span>
                                    <button
                                        @click="closeModal"
                                        class="px-4 py-2 text-sm font-medium rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors focus:outline-none"
                                    >
                                        Tutup
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
