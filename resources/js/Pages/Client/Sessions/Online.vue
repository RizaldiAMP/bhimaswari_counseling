<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import { computed } from 'vue';
import { getCounselorPhotoUrl } from '@/utils/counselorPhoto';

interface Booking {
    id: number;
    schedule_start: string;
    schedule_end: string;
    status: string;
    meeting_link: string | null;
    service_price: {
        duration_minutes: number;
    };
    counselor: {
        name: string;
        counselor_profile?: {
            full_title: string;
            photo_url?: string | null;
            photo_path?: string | null;
        };
    };
}

const props = defineProps<{
    bookings: Booking[];
}>();

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatTime = (dateStr: string) => {
    return new Date(dateStr).toLocaleString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const counselorPhotoUrl = (photoUrl: string | null | undefined, photoPath: string | null | undefined, name: string) => {
    return getCounselorPhotoUrl(photoUrl ?? null, photoPath ?? null, name);
};

const getDisplayStatus = (booking: Booking) => {
    if (['completed', 'cancelled', 'rejected', 'expired'].includes(booking.status)) {
        return booking.status;
    }

    if (booking.status === 'confirmed' || booking.status === 'in_session') {
        const now = new Date().getTime();
        const start = new Date(booking.schedule_start).getTime();
        let end = new Date(booking.schedule_end).getTime();

        if (now < start) {
            return 'confirmed';
        } else if (now >= start && now <= end) {
            return 'in_session';
        } else {
            return 'completed';
        }
    }

    return booking.status;
};

const activeBookings = computed(() => {
    return props.bookings
        .filter(b => ['confirmed', 'in_session'].includes(getDisplayStatus(b)))
        .sort((a, b) => new Date(a.schedule_start).getTime() - new Date(b.schedule_start).getTime());
});

const pastBookings = computed(() => {
    return props.bookings
        .filter(b => !['confirmed', 'in_session', 'pending_payment'].includes(getDisplayStatus(b)))
        .sort((a, b) => new Date(b.schedule_start).getTime() - new Date(a.schedule_start).getTime());
});

</script>

<template>
    <Head title="Sesi Online" />

    <AuthenticatedLayout>
        
        <div class="max-w-6xl mx-auto pb-24">
            
            <!-- Sleek Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-6 mb-12 mt-4">
                <div>
                    <h1 class="text-4xl font-black text-gray-900 tracking-tight mb-2">Sesi Online</h1>
                    <p class="text-gray-500 font-medium text-lg">Kelola jadwal konseling video call Anda dengan mudah.</p>
                </div>
                <Link :href="route('client.bookings.create')" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-primary text-white font-bold rounded-full hover:bg-primary-600 transition-all shadow-[0_8px_20px_rgba(130,62,135,0.25)] hover:shadow-[0_12px_25px_rgba(130,62,135,0.35)] hover:-translate-y-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    Buat Jadwal Baru
                </Link>
            </div>

            <!-- UPCOMING SESSIONS (Grid Card Layout - Option 1) -->
            <div class="mb-16">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <h2 class="text-2xl font-black text-gray-900">Sesi Mendatang</h2>
                </div>

                <div v-if="activeBookings.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="booking in activeBookings" :key="booking.id" class="bg-white rounded-3xl border border-gray-100 shadow-[0_4px_20px_rgb(0,0,0,0.03)] overflow-hidden flex flex-col hover:shadow-[0_10px_30px_rgb(0,0,0,0.08)] transition-all hover:-translate-y-1 hover:border-blue-200">
                        
                        <!-- Top Date/Time Bar -->
                        <div class="bg-gray-50/50 border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                            <div class="flex items-center gap-2 text-gray-500 font-bold text-xs sm:text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/></svg>
                                {{ formatDate(booking.schedule_start) }}
                            </div>
                            <div class="text-gray-900 font-black text-sm">
                                {{ formatTime(booking.schedule_start) }} WIB
                            </div>
                        </div>

                        <!-- Counselor Details -->
                        <div class="p-6 flex-1 flex flex-col items-center text-center relative">
                            <!-- Decorative glow behind photo -->
                            <div class="absolute top-10 left-1/2 -translate-x-1/2 w-24 h-24 bg-blue-100 rounded-full blur-2xl pointer-events-none"></div>
                            
                            <img :src="counselorPhotoUrl(booking.counselor.counselor_profile?.photo_url, booking.counselor.counselor_profile?.photo_path, booking.counselor.name)" class="relative w-24 h-24 rounded-full object-cover mb-5 shadow-sm border-4 border-white z-10" />
                            
                            <StatusBadge :status="getDisplayStatus(booking)" class="mb-4 relative z-10 scale-90" />
                            
                            <h3 class="text-xl font-black text-gray-900 mb-1 leading-tight relative z-10">{{ booking.counselor.name }}</h3>
                            <p class="text-xs font-bold text-blue-600 uppercase tracking-widest relative z-10">{{ booking.service_price.duration_minutes }} Menit Sesi</p>
                        </div>

                        <!-- Full Width Join Button -->
                        <div class="px-6 pb-6 mt-auto">
                            <a v-if="booking.meeting_link" :href="booking.meeting_link" target="_blank" 
                                class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-black rounded-2xl flex items-center justify-center gap-2 transition-all shadow-[0_4px_15px_rgba(37,99,235,0.25)] hover:shadow-[0_8px_20px_rgba(37,99,235,0.35)]">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14v-4z"/><rect x="3" y="6" width="12" height="12" rx="2" ry="2"/></svg>
                                Gabung Meeting
                            </a>
                            <div v-else class="w-full py-4 bg-gray-50 border border-gray-200 text-gray-400 font-bold rounded-2xl flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                Menunggu Link
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-[2rem] p-12 text-center">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm text-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Belum ada jadwal mendatang</h3>
                    <p class="text-gray-500">Jadwalkan sesi konsultasi pertama Anda sekarang.</p>
                </div>
            </div>

            <!-- PAST SESSIONS (Wide Horizontal Layout - Option 2) -->
            <div>
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-500 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                    </div>
                    <h2 class="text-2xl font-black text-gray-900">Riwayat Sesi</h2>
                </div>

                <div v-if="pastBookings.length === 0" class="text-center py-10 bg-white rounded-3xl border border-gray-100 shadow-sm">
                    <p class="text-gray-400 font-bold">Belum ada riwayat sesi.</p>
                </div>

                <div v-else class="flex flex-col gap-4">
                    <Link v-for="booking in pastBookings" :key="booking.id" :href="route('client.bookings.show', booking.id)" 
                            class="bg-white rounded-3xl p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 border border-gray-100 shadow-sm hover:shadow-md hover:border-blue-200 transition-all group">
                        
                        <!-- Left: Counselor Info -->
                        <div class="flex items-center gap-5 w-full sm:w-1/3">
                            <img :src="counselorPhotoUrl(booking.counselor.counselor_profile?.photo_url, booking.counselor.counselor_profile?.photo_path, booking.counselor.name)" class="w-16 h-16 rounded-full object-cover grayscale opacity-70 group-hover:grayscale-0 group-hover:opacity-100 transition-all shrink-0" />
                            <div>
                                <h4 class="font-bold text-gray-900 text-lg group-hover:text-blue-600 transition-colors">{{ booking.counselor.name }}</h4>
                                <StatusBadge :status="getDisplayStatus(booking)" class="mt-1 scale-90 origin-left" />
                            </div>
                        </div>
                        
                        <!-- Middle: Date & Time -->
                        <div class="flex items-center justify-start sm:justify-center gap-8 w-full sm:w-1/3 bg-gray-50 sm:bg-transparent p-4 sm:p-0 rounded-2xl">
                            <div>
                                <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-1">Tanggal</p>
                                <p class="text-sm font-bold text-gray-800">{{ formatDate(booking.schedule_start) }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-1">Waktu</p>
                                <p class="text-sm font-bold text-gray-800">{{ formatTime(booking.schedule_start) }}</p>
                            </div>
                        </div>

                        <!-- Right: Action Area -->
                        <div class="flex items-center justify-end w-full sm:w-1/3">
                            <div class="hidden sm:flex items-center gap-3 text-gray-400 group-hover:text-blue-600 font-bold text-sm transition-colors">
                                Lihat Detail
                                <div class="w-10 h-10 rounded-full bg-gray-50 border border-gray-100 flex items-center justify-center group-hover:bg-blue-50 group-hover:border-blue-200 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                </div>
                            </div>
                            <!-- Mobile fallback arrow -->
                            <div class="sm:hidden flex items-center justify-center w-full py-3 bg-gray-50 text-gray-600 font-bold rounded-xl">
                                Lihat Detail <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2"><path d="m9 18 6-6-6-6"/></svg>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
</style>
