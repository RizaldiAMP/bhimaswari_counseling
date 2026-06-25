<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import { ref, computed } from 'vue';
import { getCounselorPhotoUrl } from '@/utils/counselorPhoto';

interface Booking {
    id: number;
    counselor: { name: string; full_title: string; photo_path: string | null; photo_url: string | null };
    service_type: string;
    duration_minutes: number;
    schedule_start: string;
    schedule_end: string;
    status: string;
    meeting_link: string | null;
}

const props = defineProps<{
    activeBookings: Booking[];
    upcomingSchedule: Booking | null;
}>();

const activeTab = ref('all');

const filteredActiveBookings = computed(() => {
    if (activeTab.value === 'all') {
        return props.activeBookings;
    }
    return props.activeBookings.filter(b => b.service_type === activeTab.value);
});

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric'
    });
};

const formatTime = (dateString: string) => {
    return new Date(dateString).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const counselorPhotoUrl = (photoUrl: string | null, photoPath: string | null, name: string) => {
    return getCounselorPhotoUrl(photoUrl, photoPath, name);
};
</script>

<template>
    <Head title="Client Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Beranda Anda</h2>
        </template>

        <div class="space-y-6 max-w-6xl mx-auto pb-12 sm:px-6 lg:px-8">
            
            <!-- Welcome Alert / Next Appointment -->
            <div v-if="upcomingSchedule" class="bg-white rounded-3xl p-6 sm:p-8 lg:p-10 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 relative overflow-hidden transition-all duration-300 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)]">
                <!-- Subtle decorative background -->
                <div class="absolute top-0 right-0 p-12 opacity-5 pointer-events-none">
                    <svg width="200" height="200" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                </div>
                
                <div class="relative z-10 flex flex-col lg:flex-row gap-8 items-start lg:items-center justify-between">
                    <div class="flex-1 w-full lg:w-auto">
                        <div class="flex flex-wrap items-center gap-3 mb-5">
                            <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-green-50 text-green-700 text-xs font-bold rounded-lg uppercase tracking-wide">
                                <span class="relative flex h-2 w-2">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                </span>
                                Sesi Mendatang
                            </span>
                            <span class="px-3 py-1.5 bg-primary-50 text-primary-700 text-xs font-bold rounded-lg uppercase tracking-wide">
                                {{ upcomingSchedule?.service_type }}
                            </span>
                        </div>
                        
                        <h3 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold mb-4 text-slate-800 tracking-tight">
                            {{ upcomingSchedule?.schedule_start ? formatDate(upcomingSchedule.schedule_start) : '' }}
                        </h3>
                        
                        <div class="flex flex-wrap items-center gap-3 text-slate-600 font-medium mb-6">
                            <div class="flex items-center gap-2 bg-slate-50 px-4 py-2.5 rounded-xl border border-slate-100">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary-500"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                {{ upcomingSchedule?.schedule_start ? formatTime(upcomingSchedule.schedule_start) : '' }} - {{ upcomingSchedule?.schedule_end ? formatTime(upcomingSchedule.schedule_end) : '' }} WIB
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <img 
                                :src="counselorPhotoUrl(upcomingSchedule?.counselor?.photo_url ?? null, upcomingSchedule?.counselor?.photo_path ?? null, upcomingSchedule?.counselor?.name || 'Konselor')" 
                                class="w-14 h-14 rounded-full object-cover shadow-sm border border-slate-200"
                                alt="Foto Konselor"
                            />
                            <div>
                                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider mb-0.5">Konselor / Psikolog</p>
                                <p class="font-bold text-lg text-slate-800">{{ upcomingSchedule?.counselor?.name ? (upcomingSchedule.counselor.name + (upcomingSchedule.counselor.full_title ? ', ' + upcomingSchedule.counselor.full_title : '')) : 'Konselor' }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="w-full lg:w-auto mt-6 lg:mt-0 flex flex-col items-center sm:items-end">
                        <Link v-if="upcomingSchedule.status === 'confirmed'" :href="route('client.bookings.show', upcomingSchedule.id)" class="inline-flex items-center justify-center px-8 py-4 font-bold text-white bg-primary rounded-2xl transition-all duration-300 hover:bg-primary-700 hover:shadow-lg hover:shadow-primary/30 active:scale-95 w-full sm:w-auto gap-2 group">
                            Masuk ke Ruang Sesi
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-300 group-hover:translate-x-1"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </Link>
                        <div v-else class="bg-amber-50 px-6 py-4 rounded-2xl text-sm font-semibold text-amber-700 border border-amber-100 w-full sm:w-auto flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="animate-spin text-amber-500"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                            Menunggu Verifikasi Admin
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State Sesi Mendatang -->
            <div v-else class="bg-white rounded-3xl p-10 sm:p-14 text-center border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
                <div class="w-20 h-20 bg-primary-50 text-primary-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><line x1="10" x2="14" y1="16" y2="16"/><line x1="12" x2="12" y1="14" y2="18"/></svg>
                </div>
                <h3 class="text-2xl font-bold text-slate-800 mb-3">Belum ada jadwal konsultasi</h3>
                <p class="text-slate-500 max-w-md mx-auto mb-8 font-medium">Mulai langkah pertama untuk kesehatan mental Anda dengan memesan sesi konseling bersama ahli kami.</p>
                <Link as="button" :href="route('client.bookings.create')" class="inline-flex items-center justify-center px-8 py-3.5 bg-primary rounded-xl font-bold text-sm text-white hover:bg-primary-700 shadow-md shadow-primary/20 transition-all duration-300 hover:-translate-y-0.5 active:scale-95 gap-2 group">
                    Buat Jadwal Baru
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-300 group-hover:translate-x-1"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </Link>
            </div>

            <!-- Daftar Transaksi/Booking Aktif -->
            <div class="mt-12 mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-5">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-600 to-primary-800 text-white flex items-center justify-center shrink-0 shadow-lg shadow-primary/30">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-2xl sm:text-3xl font-black text-slate-800 tracking-tight">Aktivitas Terkini</h3>
                        <p class="text-sm text-slate-500 font-medium mt-1">Pantau status dan jadwal sesi konseling Anda</p>
                    </div>
                </div>
                
                <div class="bg-slate-200/50 p-1.5 rounded-2xl flex flex-wrap gap-1 w-full md:w-auto shadow-inner">
                    <button @click="activeTab = 'all'" 
                        class="px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all duration-300 flex-1 md:flex-none text-center"
                        :class="activeTab === 'all' ? 'bg-white text-primary-700 shadow-[0_2px_10px_rgb(0,0,0,0.06)]' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50'">
                        Semua
                    </button>
                    <button @click="activeTab = 'chat'" 
                        class="px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all duration-300 flex-1 md:flex-none text-center"
                        :class="activeTab === 'chat' ? 'bg-white text-primary-700 shadow-[0_2px_10px_rgb(0,0,0,0.06)]' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50'">
                        Chat
                    </button>
                    <button @click="activeTab = 'online'" 
                        class="px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all duration-300 flex-1 md:flex-none text-center"
                        :class="activeTab === 'online' ? 'bg-white text-primary-700 shadow-[0_2px_10px_rgb(0,0,0,0.06)]' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50'">
                        Online
                    </button>
                    <button @click="activeTab = 'offline'" 
                        class="px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all duration-300 flex-1 md:flex-none text-center"
                        :class="activeTab === 'offline' ? 'bg-white text-primary-700 shadow-[0_2px_10px_rgb(0,0,0,0.06)]' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50'">
                        Offline
                    </button>
                </div>
            </div>

            <div v-if="filteredActiveBookings.length === 0" class="bg-white rounded-[2.5rem] p-12 sm:p-20 text-center shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100 relative overflow-hidden">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 24px 24px;"></div>
                <div class="relative z-10">
                    <div class="w-20 h-20 bg-slate-50 shadow-inner rounded-3xl flex items-center justify-center mx-auto mb-6 text-slate-300 border border-slate-100">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <p class="text-slate-600 font-bold text-xl mb-6">Belum ada aktivitas untuk kategori ini.</p>
                    <button v-if="activeTab !== 'all'" @click="activeTab = 'all'" class="inline-flex items-center justify-center px-8 py-3.5 bg-white border-2 border-slate-200 rounded-xl font-bold text-sm text-slate-700 hover:border-slate-300 hover:bg-slate-50 transition-all shadow-sm">
                        Lihat Semua Kategori
                    </button>
                    <Link v-else :href="route('client.bookings.index')" class="inline-flex items-center justify-center px-8 py-3.5 bg-white border-2 border-slate-200 rounded-xl font-bold text-sm text-slate-700 hover:border-slate-300 hover:bg-slate-50 transition-all shadow-sm">
                        Lihat Riwayat Lengkap
                    </Link>
                </div>
            </div>

            <div v-else class="space-y-4">
                <div v-for="booking in filteredActiveBookings" :key="booking.id" 
                     class="group bg-white rounded-3xl p-5 sm:p-7 shadow-[0_4px_20px_rgb(0,0,0,0.02)] border border-slate-100 hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)] hover:border-primary-200 transition-all duration-300 relative overflow-hidden">
                    
                    <!-- Decorative subtle gradient blob -->
                    <div class="absolute -right-20 -top-20 w-64 h-64 bg-primary-50 rounded-full blur-[80px] opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                        <div class="flex items-start sm:items-center gap-5 sm:gap-6 w-full lg:w-auto">
                            <!-- Service Icon -->
                            <div class="relative shrink-0 hidden sm:block">
                                <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-inner border border-white"
                                     :class="{'bg-purple-50 text-purple-600': booking.service_type === 'chat', 'bg-blue-50 text-blue-600': booking.service_type === 'online', 'bg-emerald-50 text-emerald-600': booking.service_type === 'offline'}">
                                    <svg v-if="booking.service_type === 'chat'" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                                    <svg v-else-if="booking.service_type === 'online'" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m16 13 5.223 3.482a.5.5 0 0 0 .777-.416V7.87a.5.5 0 0 0-.752-.432L16 10.5"/><rect x="2" y="6" width="14" height="12" rx="2"/></svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                </div>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-md bg-slate-100 text-slate-500">
                                        {{ booking.duration_minutes }} Menit
                                    </span>
                                    <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-md"
                                          :class="{'bg-purple-100 text-purple-700': booking.service_type === 'chat', 'bg-blue-100 text-blue-700': booking.service_type === 'online', 'bg-emerald-100 text-emerald-700': booking.service_type === 'offline'}">
                                        {{ booking.service_type }}
                                    </span>
                                </div>
                                
                                <h4 class="font-black text-slate-800 text-xl sm:text-2xl mb-4 group-hover:text-primary-700 transition-colors truncate">
                                    Konseling {{ booking.service_type.charAt(0).toUpperCase() + booking.service_type.slice(1) }}
                                </h4>
                                
                                <!-- Counselor Info & Date -->
                                <div class="flex flex-col sm:flex-row gap-4 sm:gap-8">
                                    <!-- Counselor -->
                                    <div class="flex items-center gap-3">
                                        <img :src="counselorPhotoUrl(booking.counselor.photo_url, booking.counselor.photo_path, booking.counselor.name)" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" />
                                        <div>
                                            <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest leading-none mb-1.5">Konselor</p>
                                            <p class="text-sm font-bold text-slate-800 leading-none truncate max-w-[150px] sm:max-w-[200px]">{{ booking.counselor.name }}</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Separator -->
                                    <div class="hidden sm:block w-px h-10 bg-slate-200"></div>
                                    
                                    <!-- Date -->
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-500 border border-slate-200 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 2v4"/><path d="M16 2v4"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest leading-none mb-1.5">Jadwal Sesi</p>
                                            <p class="text-sm font-bold text-slate-800 leading-none">{{ formatDate(booking.schedule_start) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right side: Status and Action -->
                        <div class="flex flex-row lg:flex-col items-center lg:items-end justify-between w-full lg:w-auto gap-4 mt-4 lg:mt-0 pt-5 lg:pt-0 border-t lg:border-t-0 border-slate-100 shrink-0">
                            <StatusBadge :status="booking.status" class="w-max shadow-sm" />
                            <Link :href="booking.service_type === 'chat' ? route('client.chat.index', { booking: booking.id }) : route('client.bookings.show', booking.id)" class="w-full lg:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 bg-white border-2 border-slate-200 hover:border-primary hover:text-primary hover:bg-primary-50 rounded-xl font-bold text-sm text-slate-700 transition-all duration-300 shadow-sm hover:shadow-md group/btn">
                                Lihat Detail
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-300 group-hover/btn:translate-x-1"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </Link>
                        </div>
                    </div>
                </div>
                
                <div v-if="activeBookings.length > 0" class="pt-6 text-center">
                    <Link :href="route('client.bookings.index')" class="inline-flex items-center gap-2 px-8 py-3.5 bg-slate-900 text-white rounded-xl font-bold text-sm hover:bg-slate-800 transition-all duration-300 shadow-lg shadow-slate-900/20 hover:-translate-y-0.5 group">
                        Lihat Semua Riwayat
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-300 group-hover:translate-x-1"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
