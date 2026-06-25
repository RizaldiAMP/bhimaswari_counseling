<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import PrimaryButton from '@/Components/ui/PrimaryButton.vue';
import { ref, computed, onMounted, watch } from 'vue';
import { getCounselorPhotoUrl } from '@/utils/counselorPhoto';

interface Pagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
}

interface Booking {
    id: number;
    schedule_start: string;
    schedule_end: string;
    status: string;
    service_type: string;
    counselor: {
        id: number;
        name: string;
        counselor_profile?: {
            full_title: string;
            photo_url?: string | null;
            photo_path?: string | null;
        };
    };
    payment?: {
        status: string;
    };
    service_price: {
        duration_minutes: number;
    };
}

const props = defineProps<{
    bookings: Pagination<Booking>;
}>();

const activeTab = ref('all');

const updateTabFromUrl = () => {
    const params = new URLSearchParams(window.location.search);
    if (params.has('tab')) {
        const tab = params.get('tab');
        if (tab && ['chat', 'online', 'offline', 'all'].includes(tab)) {
            activeTab.value = tab;
        }
    } else {
        activeTab.value = 'all';
    }
};

onMounted(updateTabFromUrl);

const page = usePage();
watch(() => page.url, updateTabFromUrl);

const filteredBookings = computed(() => {
    if (activeTab.value === 'all') {
        return props.bookings.data;
    }
    return props.bookings.data.filter(b => b.service_type === activeTab.value);
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

        // Chat sessions remain active for 1 hour after the schedule ends
        if (booking.service_type === 'chat') {
            end += 60 * 60 * 1000;
        }

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
</script>

<template>
    <Head title="Riwayat Booking" />

    <AuthenticatedLayout>
        <div class="min-h-[85vh] bg-gradient-to-b from-primary-50/30 to-transparent pb-12">
            <!-- Header Section -->
            <div class="bg-white/60 backdrop-blur-md border-b border-white/40 sticky top-0 z-10">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-black text-2xl text-slate-900 tracking-tight">Riwayat Booking</h2>
                            <p class="text-sm text-slate-500 mt-1 font-medium">Daftar rekaman jadwal sesi konseling Anda.</p>
                        </div>
                        <Link :href="route('client.bookings.create')" class="hidden sm:flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-primary/20 transition-all hover:-translate-y-0.5">
                            <span class="text-lg">+</span> Booking Baru
                        </Link>
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
                            <h3 class="text-2xl font-bold text-slate-800 mb-2">Belum Terdapat Riwayat</h3>
                            <p class="text-slate-500 text-sm leading-relaxed mb-8">
                                Kosong! Sepertinya Anda belum memiliki jadwal pertemuan atau sesi untuk kategori ini.
                            </p>
                            <button v-if="activeTab !== 'all'" @click="activeTab = 'all'" class="inline-flex items-center gap-2 px-8 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold rounded-xl transition-all">
                                ← Lihat Semua Kategori
                            </button>
                            <Link v-else :href="route('client.bookings.create')" class="inline-flex items-center gap-2 px-8 py-3.5 bg-slate-900 hover:bg-primary-700 text-white text-sm font-bold rounded-xl shadow-lg transition-all hover:shadow-primary/30 hover:-translate-y-1">
                                Mulai Sesi Pertama
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Bookings List -->
                <div v-else class="grid grid-cols-1 gap-5 animate-fade-in-up">
                    <Link
                        v-for="(booking, idx) in filteredBookings"
                        :key="booking.id"
                        :href="booking.service_type === 'chat' ? route('client.chat.index', { booking: booking.id }) : route('client.bookings.show', booking.id)"
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
                                    <div class="flex items-center flex-wrap gap-2 mb-2">
                                        <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-md bg-slate-100 text-slate-500">
                                            {{ booking.service_price.duration_minutes }} Menit
                                        </span>
                                        <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-md"
                                              :class="{'bg-purple-100 text-purple-700': booking.service_type === 'chat', 'bg-blue-100 text-blue-700': booking.service_type === 'online', 'bg-emerald-100 text-emerald-700': booking.service_type === 'offline'}">
                                            {{ booking.service_type }}
                                        </span>
                                        <StatusBadge :status="booking.status" class="w-max shadow-sm" />
                                    </div>
                                    
                                    <h4 class="font-black text-slate-800 text-xl sm:text-2xl mb-4 group-hover:text-primary-700 transition-colors truncate">
                                        Konseling {{ booking.service_type.charAt(0).toUpperCase() + booking.service_type.slice(1) }}
                                    </h4>
                                    
                                    <!-- Counselor Info & Date -->
                                    <div class="flex flex-col sm:flex-row gap-4 sm:gap-8">
                                        <!-- Counselor -->
                                        <div class="flex items-center gap-3">
                                            <img :src="counselorPhotoUrl(booking.counselor.counselor_profile?.photo_url, booking.counselor.counselor_profile?.photo_path, booking.counselor.name)" class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm" />
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
                                <div class="flex flex-col gap-2 items-start lg:items-end w-full sm:w-auto">
                                    <span v-if="getDisplayStatus(booking) === 'completed'" class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1.5 rounded-md bg-green-100 text-green-700 shrink-0 border shadow-sm">SELESAI</span>
                                    <span v-else-if="getDisplayStatus(booking) === 'in_session'" class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1.5 rounded-md bg-purple-100 text-purple-700 animate-pulse border border-purple-200 shrink-0 shadow-sm">AKTIF</span>
                                    <span v-else-if="getDisplayStatus(booking) === 'confirmed'" class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1.5 rounded-md bg-blue-100 text-blue-700 shrink-0 border shadow-sm">MENDATANG</span>
                                </div>
                                
                                <div class="hidden lg:flex w-10 h-10 rounded-full items-center justify-center bg-slate-50 text-slate-400 group-hover:bg-primary-50 group-hover:text-primary transition-colors duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-x-0.5 transition-transform"><path d="m9 18 6-6-6-6"/></svg>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Pagination if needed -->
                <div v-if="bookings.last_page > 1" class="flex justify-center mt-8">
                    <p class="text-xs font-bold text-slate-400">Total Data: {{ bookings.total }}</p>
                </div>

                <!-- Mobile floating action button -->
                <div class="fixed bottom-6 right-6 sm:hidden z-30">
                    <Link :href="route('client.bookings.create')" class="w-14 h-14 bg-primary text-white rounded-full shadow-lg shadow-primary/30 flex items-center justify-center hover:scale-105 transition-transform active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </Link>
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
