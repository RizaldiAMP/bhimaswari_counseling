<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import { ref, computed } from 'vue';

interface Booking {
    id: number;
    client: { name: string; email: string };
    service_type: string;
    duration_minutes: number;
    schedule_start: string;
    schedule_end: string;
    status: string;
    meeting_link: string | null;
    service_price: any;
}

const props = defineProps<{
    bookings: { data: Booking[]; links: any; total?: number; last_page?: number };
}>();

const activeTab = ref('all');

const filteredBookings = computed(() => {
    if (activeTab.value === 'all') {
        return props.bookings.data;
    }
    return props.bookings.data.filter(b => b.service_type === activeTab.value);
});

const getDisplayStatus = (booking: Booking) => {
    const status = booking.status.toLowerCase();
    if (['completed', 'cancelled', 'rejected', 'expired'].includes(status)) {
        return status;
    }

    if (status === 'confirmed' || status === 'in_session') {
        const now = new Date().getTime();
        const start = new Date(booking.schedule_start).getTime();
        const end = new Date(booking.schedule_end).getTime();

        if (now < start) {
            return 'confirmed';
        } else if (now >= start && now <= end) {
            return 'in_session';
        } else {
            return 'completed';
        }
    }

    return status;
};

// Grouping logic for clean separation
const upcomingBookings = computed(() => {
    return filteredBookings.value.filter(b => !['completed', 'cancelled', 'expired', 'rejected'].includes(getDisplayStatus(b)));
});

const completedBookings = computed(() => {
    return filteredBookings.value.filter(b => ['completed', 'cancelled', 'expired', 'rejected'].includes(getDisplayStatus(b)));
});

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Daftar Booking Saya" />

    <AuthenticatedLayout>
        <div class="min-h-[85vh] bg-gradient-to-b from-primary-50/30 to-transparent pb-12">
            <!-- Header Section -->
            <div class="bg-white/60 backdrop-blur-md border-b border-white/40 sticky top-0 z-10">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-black text-2xl text-slate-900 tracking-tight">Daftar Booking Saya</h2>
                            <p class="text-sm text-slate-500 mt-1 font-medium">Daftar lengkap jadwal sesi konseling yang telah di-booking oleh klien Anda.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
                
                <!-- Category Tabs -->
                <div v-if="bookings.data.length > 0" class="bg-slate-200/50 p-1.5 rounded-2xl flex flex-wrap gap-1 w-full md:w-auto shadow-inner mb-6 mx-auto sm:mx-0 max-w-fit animate-fade-in-up">
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

                <!-- Empty State -->
                <div v-if="filteredBookings.length === 0" class="animate-fade-in-up mt-4">
                    <div class="bg-white rounded-[2rem] border border-slate-100 p-12 sm:p-20 text-center shadow-[0_8px_30px_rgb(0,0,0,0.04)] relative overflow-hidden">
                        <div class="absolute -top-24 -right-24 w-64 h-64 bg-primary-50 rounded-full blur-3xl opacity-60"></div>
                        <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-blue-50 rounded-full blur-3xl opacity-60"></div>
                        
                        <div class="relative z-10 max-w-sm mx-auto">
                            <div class="w-24 h-24 bg-gradient-to-tr from-primary-100 to-primary-50 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-slate-800 mb-2">Belum Terdapat Booking</h3>
                            <p class="text-slate-500 text-sm leading-relaxed mb-6">
                                Belum ada jadwal sesi konseling yang di-booking untuk kategori ini.
                            </p>
                            <button v-if="activeTab !== 'all'" @click="activeTab = 'all'" class="inline-flex items-center gap-2 px-8 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold rounded-xl transition-all">
                                ← Lihat Semua Kategori
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Bookings Split Sections -->
                <div v-else class="space-y-10 animate-fade-in-up">
                    
                    <!-- Section 1: Upcoming / Active Sessions -->
                    <div v-if="upcomingBookings.length > 0" class="space-y-4">
                        <div class="flex items-center gap-2 px-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-500 animate-pulse"></span>
                            <h3 class="text-[15px] font-black text-slate-800 tracking-wider uppercase">Sesi Aktif & Mendatang</h3>
                            <span class="text-xs font-black bg-purple-100 text-purple-700 px-2 py-0.5 rounded-md">{{ upcomingBookings.length }}</span>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-5">
                            <Link
                                v-for="(booking, idx) in upcomingBookings"
                                :key="booking.id"
                                :href="route('counselor.bookings.show', booking.id)"
                                class="group bg-white rounded-3xl p-5 sm:p-7 shadow-[0_4px_20px_rgb(0,0,0,0.02)] border border-slate-100 hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)] hover:border-primary-200 transition-all duration-300 relative overflow-hidden block"
                                :style="`animation-delay: ${idx * 50}ms`"
                            >
                                <!-- Decorative subtle gradient blob -->
                                <div class="absolute -right-20 -top-20 w-64 h-64 bg-primary-50 rounded-full blur-[80px] opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

                                <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 w-full">
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
                                                Sesi Konseling {{ booking.service_type.charAt(0).toUpperCase() + booking.service_type.slice(1) }}
                                            </h4>
                                            
                                            <!-- Client Info & Date -->
                                            <div class="flex flex-col sm:flex-row gap-4 sm:gap-8">
                                                <!-- Client -->
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-full border border-slate-200 shadow-sm flex items-center justify-center bg-gradient-to-br from-primary-50 to-primary-100 text-primary-700 font-black text-sm">
                                                        {{ booking.client.name.charAt(0).toUpperCase() }}
                                                    </div>
                                                    <div>
                                                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest leading-none mb-1.5">Klien</p>
                                                        <p class="text-sm font-bold text-slate-800 leading-none truncate max-w-[150px] sm:max-w-[200px]">{{ booking.client.name }}</p>
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
                                        <div class="flex flex-col gap-2 items-start lg:items-end w-full sm:w-auto">
                                            <StatusBadge :status="getDisplayStatus(booking)" class="w-max shadow-sm" />
                                        </div>
                                        
                                        <div class="hidden lg:flex w-10 h-10 rounded-full items-center justify-center bg-slate-50 text-slate-400 group-hover:bg-primary-50 group-hover:text-primary transition-colors duration-300">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-x-0.5 transition-transform"><path d="m9 18 6-6-6-6"/></svg>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>

                    <!-- Section 2: Completed / Historical Sessions -->
                    <div v-if="completedBookings.length > 0" class="space-y-4 pt-6 border-t border-slate-100/80">
                        <div class="flex items-center gap-2 px-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                            <h3 class="text-[15px] font-black text-slate-400 tracking-wider uppercase">Riwayat Sesi Selesai</h3>
                            <span class="text-xs font-black bg-slate-100 text-slate-400 px-2 py-0.5 rounded-md">{{ completedBookings.length }}</span>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-5">
                            <Link
                                v-for="(booking, idx) in completedBookings"
                                :key="booking.id"
                                :href="route('counselor.bookings.show', booking.id)"
                                class="group bg-white/70 rounded-3xl p-5 sm:p-7 shadow-[0_2px_10px_rgb(0,0,0,0.01)] border border-slate-200/50 hover:shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:border-slate-300 transition-all duration-300 relative overflow-hidden block opacity-85 hover:opacity-100"
                                :style="`animation-delay: ${idx * 50}ms`"
                            >
                                <!-- Decorative subtle gradient blob -->
                                <div class="absolute -right-20 -top-20 w-64 h-64 bg-slate-100 rounded-full blur-[80px] opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

                                <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 w-full">
                                    <div class="flex items-start sm:items-center gap-5 sm:gap-6 w-full lg:w-auto">
                                        <!-- Service Icon -->
                                        <div class="relative shrink-0 hidden sm:block">
                                            <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-inner border border-white bg-slate-100 text-slate-500">
                                                <svg v-if="booking.service_type === 'chat'" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                                                <svg v-else-if="booking.service_type === 'online'" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m16 13 5.223 3.482a.5.5 0 0 0 .777-.416V7.87a.5.5 0 0 0-.752-.432L16 10.5"/><rect x="2" y="6" width="14" height="12" rx="2"/></svg>
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                            </div>
                                        </div>
                                        
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-md bg-slate-100 text-slate-400">
                                                    {{ booking.duration_minutes }} Menit
                                                </span>
                                                <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-md bg-slate-100 text-slate-400">
                                                    {{ booking.service_type }}
                                                </span>
                                            </div>
                                            
                                            <h4 class="font-black text-slate-500 text-xl sm:text-2xl mb-4 group-hover:text-slate-700 transition-colors truncate">
                                                Sesi Konseling {{ booking.service_type.charAt(0).toUpperCase() + booking.service_type.slice(1) }}
                                            </h4>
                                            
                                            <!-- Client Info & Date -->
                                            <div class="flex flex-col sm:flex-row gap-4 sm:gap-8">
                                                <!-- Client -->
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-full border border-slate-200 shadow-sm flex items-center justify-center bg-slate-50 text-slate-400 font-bold text-sm">
                                                        {{ booking.client.name.charAt(0).toUpperCase() }}
                                                    </div>
                                                    <div>
                                                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest leading-none mb-1.5">Klien</p>
                                                        <p class="text-sm font-bold text-slate-500 leading-none truncate max-w-[150px] sm:max-w-[200px]">{{ booking.client.name }}</p>
                                                    </div>
                                                </div>
                                                
                                                <!-- Separator -->
                                                <div class="hidden sm:block w-px h-10 bg-slate-200/60"></div>
                                                
                                                <!-- Date -->
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 border border-slate-200 shadow-sm">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 2v4"/><path d="M16 2v4"/></svg>
                                                    </div>
                                                    <div>
                                                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest leading-none mb-1.5">Jadwal Sesi</p>
                                                        <p class="text-sm font-bold text-slate-500 leading-none">{{ formatDate(booking.schedule_start) }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Right side: Status and Action -->
                                    <div class="flex flex-row lg:flex-col items-center lg:items-end justify-between w-full lg:w-auto gap-4 mt-4 lg:mt-0 pt-5 lg:pt-0 border-t lg:border-t-0 border-slate-100 shrink-0">
                                        <div class="flex flex-col gap-2 items-start lg:items-end w-full sm:w-auto">
                                            <StatusBadge :status="getDisplayStatus(booking)" class="w-max shadow-sm" />
                                        </div>
                                        
                                        <div class="hidden lg:flex w-10 h-10 rounded-full items-center justify-center bg-slate-50 text-slate-400 group-hover:bg-slate-100 group-hover:text-slate-600 transition-colors duration-300">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-x-0.5 transition-transform"><path d="m9 18 6-6-6-6"/></svg>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>

                </div>

                <!-- Pagination info -->
                <div v-if="bookings.last_page && bookings.last_page > 1" class="flex justify-center mt-8">
                    <p class="text-xs font-bold text-slate-400">Total Data: {{ bookings.total }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
