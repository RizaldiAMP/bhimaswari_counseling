<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import { computed } from 'vue';

interface Booking {
    id: number;
    client: { name: string };
    service_type: string;
    duration_minutes: number;
    schedule_start: string;
    schedule_end: string;
    status: string;
    meeting_link: string | null;
}

const props = defineProps<{
    todaySessions: Booking[];
    upcomingSessions: Booking[];
    totalClients: number;
}>();

const user = computed(() => usePage().props.auth.user);

const formatTime = (dateString: string) => {
    return new Date(dateString).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getDay = (dateString: string) => {
    return new Date(dateString).getDate();
};

const getMonth = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', { month: 'short' });
};

const todayDate = computed(() => {
    return new Date().toLocaleDateString('id-ID', {
        weekday: 'long', 
        month: 'long', 
        day: 'numeric',
        year: 'numeric'
    });
});

const isSessionFinished = (session: Booking) => {
    if (['completed', 'cancelled', 'expired'].includes(session.status.toLowerCase())) {
        return true;
    }
    const now = new Date().getTime();
    const end = new Date(session.schedule_end).getTime();
    return now > end;
};
</script>

<template>
    <Head title="Dashboard Konselor" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-slate-50">
            <!-- Premium Hero Section -->
            <div class="relative bg-gradient-to-br from-purple-50 via-white to-primary-50 pb-32 pt-12 overflow-hidden border-b border-purple-100/50">
                <!-- Abstract decorative background shapes -->
                <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
                    <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[100%] rounded-full bg-purple-200 opacity-[0.4] blur-3xl transform rotate-12"></div>
                    <div class="absolute top-[20%] -right-[10%] w-[40%] h-[80%] rounded-full bg-primary-100 opacity-[0.4] blur-3xl transform -rotate-12"></div>
                </div>
                
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div>
                            <p class="text-primary-600 font-bold text-xs tracking-widest uppercase mb-2 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
                                {{ todayDate }}
                            </p>
                            <h1 class="text-4xl sm:text-5xl font-black text-gray-900 tracking-tight leading-tight">
                                Halo, <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-purple-500">{{ user.name }}</span> 👋
                            </h1>
                            <p class="mt-3 text-gray-600 text-lg max-w-xl font-medium">
                                Ruang kerjamu sudah siap. Mari bantu klien mencapai versi terbaik dari diri mereka hari ini.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content overlapping hero -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 relative z-20 mb-20">
                <!-- Glowing Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                    <!-- Sesi Hari Ini Stat -->
                    <div class="bg-white/90 backdrop-blur-xl rounded-3xl p-6 sm:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white flex items-center justify-between group hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all duration-300 transform hover:-translate-y-1">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Total Sesi Hari Ini</p>
                            <h3 class="text-5xl font-black text-gray-900 group-hover:text-primary-600 transition-colors">{{ todaySessions.length }}</h3>
                        </div>
                        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/30 transform group-hover:rotate-12 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 7.5V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3.5"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h5"/><path d="M17.5 17.5 16 16.3V14"/><circle cx="16" cy="16" r="6"/></svg>
                        </div>
                    </div>
                    <!-- Sesi Mendatang Stat -->
                    <div class="bg-white/90 backdrop-blur-xl rounded-3xl p-6 sm:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white flex items-center justify-between group hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all duration-300 transform hover:-translate-y-1">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Sesi Mendatang</p>
                            <h3 class="text-5xl font-black text-gray-900 group-hover:text-purple-600 transition-colors">{{ upcomingSessions.length }}</h3>
                        </div>
                        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white shadow-lg shadow-purple-500/30 transform group-hover:rotate-12 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>
                        </div>
                    </div>
                    <!-- Total Klien Stat -->
                    <div class="bg-white/90 backdrop-blur-xl rounded-3xl p-6 sm:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white flex items-center justify-between group hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all duration-300 transform hover:-translate-y-1">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Total Klien</p>
                            <h3 class="text-5xl font-black text-gray-900 group-hover:text-teal-500 transition-colors">{{ totalClients }}</h3>
                        </div>
                        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-teal-400 to-emerald-500 flex items-center justify-center text-white shadow-lg shadow-teal-500/30 transform group-hover:rotate-12 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                    <!-- Sesi Hari Ini List (Takes 2 columns on XL) -->
                    <div class="xl:col-span-2 space-y-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-3">
                                Jadwal Hari Ini
                                <span class="relative flex h-3 w-3">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                                </span>
                            </h2>
                        </div>
                        
                        <div v-if="todaySessions.length === 0" class="bg-white rounded-[2rem] p-16 text-center border border-dashed border-gray-200 shadow-sm flex flex-col items-center justify-center">
                            <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-300"><path d="M21 7.5V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3.5"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h5"/><path d="M17.5 17.5 16 16.3V14"/><circle cx="16" cy="16" r="6"/></svg>
                            </div>
                            <h4 class="text-xl font-bold text-gray-900 mb-2">Hari Ini Kosong</h4>
                            <p class="text-gray-500 font-medium">Anda tidak memiliki jadwal konseling hari ini. Waktunya bersantai!</p>
                        </div>
                        
                        <div v-else class="space-y-4">
                            <div v-for="session in todaySessions" :key="session.id" class="bg-white rounded-3xl p-5 sm:p-6 shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)] transition-all duration-300 group relative overflow-hidden flex flex-col sm:flex-row gap-6 items-center">
                                <!-- Colored left indicator -->
                                <div class="absolute top-0 left-0 w-2 h-full bg-primary-500"></div>
                                
                                <!-- Time Block -->
                                <div class="flex-shrink-0 w-full sm:w-28 h-28 rounded-2xl bg-slate-50 flex flex-col items-center justify-center border border-slate-100 group-hover:bg-primary-50 group-hover:border-primary-100 transition-colors">
                                    <span class="text-2xl font-black text-gray-900 group-hover:text-primary-700">{{ formatTime(session.schedule_start) }}</span>
                                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">WIB</span>
                                </div>
                                
                                <!-- Content -->
                                <div class="flex-1 text-center sm:text-left w-full">
                                    <h3 class="text-xl font-bold text-gray-900 mb-2">{{ session.client.name }}</h3>
                                    
                                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mb-3">
                                        <span class="inline-flex px-3 py-1 rounded-lg bg-gray-100 text-gray-600 text-[10px] font-bold uppercase tracking-widest">{{ session.service_type }}</span>
                                        <StatusBadge :status="session.status" />
                                    </div>
                                    
                                    <p class="text-sm text-gray-500 font-medium flex items-center justify-center sm:justify-start gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        Berakhir jam {{ formatTime(session.schedule_end) }} WIB
                                    </p>
                                </div>
                                
                                <!-- Action Button -->
                                <div class="flex-shrink-0 w-full sm:w-auto">
                                    <Link :href="route('counselor.bookings.show', session.id)" class="block w-full">
                                        <button 
                                            :class="[
                                                'w-full sm:w-auto px-8 py-4 font-bold rounded-2xl transition-all duration-300 flex items-center justify-center gap-2 transform active:scale-95 shadow-md',
                                                isSessionFinished(session)
                                                    ? 'bg-slate-100 hover:bg-slate-200 text-slate-700 hover:shadow-lg'
                                                    : 'bg-gray-900 hover:bg-primary-600 text-white hover:shadow-xl hover:shadow-primary-600/20'
                                            ]"
                                        >
                                            {{ isSessionFinished(session) ? 'Detail Sesi' : 'Mulai Sesi' }}
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="transition-transform group-hover:translate-x-1"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                        </button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sesi Mendatang List (Takes 1 column on XL) -->
                    <div class="xl:col-span-1 space-y-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-black text-gray-900 tracking-tight">Mendatang</h2>
                            <Link :href="route('counselor.bookings.index')" class="text-sm font-bold text-primary-600 hover:text-primary-800 transition-colors px-4 py-1.5 bg-primary-50 hover:bg-primary-100 rounded-full">Lihat Semua</Link>
                        </div>
                        
                        <div class="bg-white rounded-[2rem] shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden">
                            <div v-if="upcomingSessions.length === 0" class="p-12 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-gray-200 mx-auto mb-4"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
                                <p class="text-gray-500 font-medium text-sm">Belum ada sesi mendatang.</p>
                            </div>
                            
                            <div v-else class="divide-y divide-gray-100">
                                <div v-for="session in upcomingSessions" :key="session.id" class="p-6 hover:bg-slate-50 transition-colors group">
                                    <div class="flex gap-4 items-start mb-5">
                                        <!-- Date Badge -->
                                        <div class="w-16 h-16 rounded-2xl bg-purple-50 text-purple-600 flex flex-col items-center justify-center flex-shrink-0 border border-purple-100 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                                            <span class="text-2xl font-black leading-none">{{ getDay(session.schedule_start) }}</span>
                                            <span class="text-[10px] font-bold uppercase tracking-wider mt-1">{{ getMonth(session.schedule_start) }}</span>
                                        </div>
                                        
                                        <!-- Details -->
                                        <div class="pt-1">
                                            <h4 class="text-base font-bold text-gray-900 mb-1 break-words leading-tight">{{ session.client.name }}</h4>
                                            <p class="text-sm text-gray-500 font-medium flex items-center gap-1.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                {{ formatTime(session.schedule_start) }} - {{ formatTime(session.schedule_end) }} WIB
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between pt-4 border-t border-gray-50">
                                        <span class="inline-flex px-3 py-1 rounded-lg bg-gray-100 text-gray-600 text-[10px] font-bold uppercase tracking-widest">{{ session.service_type }}</span>
                                        <Link :href="route('counselor.bookings.show', session.id)">
                                            <button class="px-5 py-2.5 bg-primary-50 text-primary-700 hover:bg-primary-600 hover:text-white font-bold text-xs rounded-xl transition-colors flex items-center gap-2">
                                                Detail Sesi
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                            </button>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
