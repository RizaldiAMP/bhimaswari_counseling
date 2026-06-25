<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import StatusBadge from '@/Components/ui/StatusBadge.vue';

interface Booking {
    id: number;
    status: string;
    schedule_start: string;
    schedule_end: string;
    client: {
        name: string;
    };
    chat_session: {
        id: number;
        last_message: {
            body: string;
            created_at: string;
        } | null;
    } | null;
}

const props = defineProps<{
    bookings: Booking[];
}>();

const formatTime = (dateString: string) =>
    new Date(dateString).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

const formatDate = (dateString: string) =>
    new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });

const selectedBooking = ref<Booking | null>(null);

const selectBooking = (booking: Booking) => {
    selectedBooking.value = booking;
};

const backToList = () => {
    selectedBooking.value = null;
};

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const bookingId = params.get('booking');
    if (bookingId) {
        const booking = props.bookings.find(b => b.id === parseInt(bookingId));
        if (booking) {
            selectBooking(booking);
        }
    }
});

const canEnterChat = (booking: Booking) => {
    if (booking.status === 'completed') {
        return true;
    }
    if (!['confirmed', 'in_session'].includes(booking.status)) {
        return false;
    }
    const now = new Date().getTime();
    const scheduleStart = new Date(booking.schedule_start).getTime();
    // Counselor can access 10 minutes before schedule start
    return now >= (scheduleStart - 10 * 60 * 1000);
};
</script>

<template>
    <Head title="Ruang Chat Konselor" />

    <AuthenticatedLayout>
        <!-- Full height layout minus header (approx 73px) -->
        <div class="h-[calc(100vh-73px)] min-h-[600px] flex overflow-hidden bg-slate-50 border-t border-slate-200">
            
            <!-- Left Pane: Chat List -->
            <div class="w-full md:w-1/3 lg:w-[400px] bg-white border-r-2 border-slate-200 flex flex-col h-full shadow-[4px_0_24px_rgba(0,0,0,0.02)] z-20 relative"
                 :class="{ 'hidden md:flex': selectedBooking !== null }">
                 
                <!-- Header Left Pane -->
                <div class="p-5 border-b border-slate-100 flex flex-wrap gap-2 items-center justify-between bg-white shrink-0 shadow-sm z-10">
                    <div>
                        <h2 class="font-black text-2xl text-slate-900 tracking-tight">Obrolan</h2>
                    </div>
                    <Link :href="route('counselor.bookings.index')" class="px-3 py-2 rounded-lg bg-primary hover:bg-primary-700 text-white text-[13px] font-bold shadow-sm shadow-primary/20 flex items-center gap-1.5 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        Daftar Booking
                    </Link>
                </div>


                <!-- Chat List Items -->
                <div class="flex-1 overflow-y-auto overflow-x-hidden bg-slate-50/30">
                    <div v-if="bookings.length === 0" class="p-8 text-center text-slate-400 mt-10">
                        <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <p class="text-sm font-bold text-slate-600 mb-1">Belum ada obrolan</p>
                        <p class="text-xs text-slate-400 font-medium">Sesi chat aktif Anda akan muncul di sini.</p>
                    </div>

                    <ul v-else class="divide-y divide-slate-100">
                        <li v-for="booking in bookings" :key="booking.id">
                            <button @click="selectBooking(booking)" 
                                    class="w-full text-left p-4 hover:bg-slate-50 transition-colors flex gap-4 items-center group relative border-l-4"
                                    :class="selectedBooking?.id === booking.id ? 'bg-primary-50/40 border-primary' : 'border-transparent'">
                                
                                <!-- Avatar -->
                                <div class="relative flex-shrink-0">
                                    <div class="w-12 h-12 rounded-full overflow-hidden bg-white border border-slate-200 shadow-sm flex items-center justify-center bg-gradient-to-br from-primary-50 to-primary-100 text-primary-700 font-bold">
                                        {{ booking.client.name.charAt(0) }}
                                    </div>

                                </div>

                                <!-- Details -->
                                <div class="flex-1 min-w-0 py-1">
                                    <div class="flex justify-between items-baseline mb-1">
                                        <h3 class="font-bold text-slate-900 truncate text-[15px] flex items-center gap-2">
                                            {{ booking.client.name }}
                                            <span v-if="booking.status === 'completed'" class="px-2 py-0.5 rounded-md bg-green-100 text-green-700 text-[9px] font-black uppercase tracking-wider shrink-0">SELESAI</span>
                                            <span v-else-if="booking.status === 'in_session'" class="px-2 py-0.5 rounded-md bg-purple-100 text-purple-700 text-[9px] font-black uppercase tracking-wider shrink-0 animate-pulse border border-purple-200">AKTIF</span>
                                            <span v-else-if="booking.status === 'confirmed'" class="px-2 py-0.5 rounded-md bg-blue-100 text-blue-700 text-[9px] font-black uppercase tracking-wider shrink-0">MENDATANG</span>
                                        </h3>
                                        <span class="text-[11px] text-slate-400 font-semibold whitespace-nowrap pl-2">
                                            <template v-if="booking.chat_session?.last_message">
                                                {{ formatTime(booking.chat_session.last_message.created_at) }}
                                            </template>
                                            <template v-else>
                                                {{ formatDate(booking.schedule_start) }}
                                            </template>
                                        </span>
                                    </div>
                                    
                                    <p class="text-sm text-slate-500 truncate" :class="{'font-semibold text-slate-700': booking.status === 'in_session' && !booking.chat_session?.last_message}">
                                        <template v-if="booking.chat_session?.last_message">
                                            {{ booking.chat_session.last_message.body }}
                                        </template>
                                        <template v-else>
                                            Jadwal: {{ formatTime(booking.schedule_start) }} WIB
                                        </template>
                                    </p>
                                </div>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Right Pane: Details & Actions -->
            <div class="flex-1 bg-slate-50/50 flex flex-col h-full overflow-hidden relative"
                 :class="{ 'hidden md:flex': selectedBooking === null }">
                 
                <!-- Decorative background image pattern -->
                <div class="absolute inset-0 opacity-20 pointer-events-none" style="background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 24px 24px;"></div>
                 
                <!-- Empty State Right Pane -->
                <div v-if="!selectedBooking" class="h-full flex flex-col items-center justify-center text-center p-8 relative z-10">
                    <div class="w-32 h-32 bg-white rounded-full flex items-center justify-center shadow-md mb-6 border border-slate-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-800 mb-2">Pilih Obrolan</h3>
                    <p class="text-slate-500 font-medium max-w-sm mb-6">Pilih jadwal obrolan dari daftar di sebelah kiri untuk melihat detail sesi dan bergabung ke dalam ruangan obrolan.</p>
                    <Link :href="route('counselor.bookings.index')" class="px-6 py-3 bg-white border-2 border-slate-200 text-slate-700 hover:border-primary hover:text-primary rounded-xl font-bold shadow-sm transition-all flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        Lihat Daftar Booking
                    </Link>
                </div>

                <!-- Selected Booking Detail -->
                <div v-else class="h-full flex flex-col relative z-10">
                    <!-- Right Pane Header (Mobile Back Button) -->
                    <div class="md:hidden p-4 border-b border-slate-200 bg-white flex items-center gap-3 shrink-0 shadow-sm">
                        <button @click="backToList" class="p-2 -ml-2 rounded-full hover:bg-slate-100 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-slate-700"><path d="m15 18-6-6 6-6"/></svg>
                        </button>
                        <h3 class="font-bold text-slate-800 text-lg">Detail Obrolan</h3>
                    </div>

                    <!-- Right Pane Content -->
                    <div class="flex-1 overflow-y-auto relative p-4 sm:p-8 flex flex-col items-center justify-center py-10 bg-slate-100/80">
                        <!-- Decorative background blur elements -->
                        <div class="absolute top-10 right-10 w-72 h-72 bg-primary-300/40 rounded-full blur-[100px] pointer-events-none"></div>
                        <div class="absolute bottom-10 left-10 w-96 h-96 bg-blue-300/30 rounded-full blur-[100px] pointer-events-none"></div>

                        <div class="w-full max-w-2xl flex flex-col items-center bg-white/80 backdrop-blur-2xl border-2 border-white shadow-[0_20px_60px_-15px_rgba(0,0,0,0.15)] rounded-[2.5rem] p-6 sm:p-10 relative z-10">
                            
                            <!-- Profile Header -->
                            <div class="flex flex-col items-center text-center mb-10 w-full">
                                <div class="w-28 h-28 sm:w-32 sm:h-32 rounded-full overflow-hidden bg-white border-4 border-white shadow-lg mb-5 flex items-center justify-center bg-gradient-to-br from-primary-50 to-primary-100 text-primary-700 font-black text-4xl">
                                    {{ selectedBooking.client.name.charAt(0) }}
                                </div>
                                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mb-2">
                                    {{ selectedBooking.client.name }}
                                </h2>
                                <div class="flex flex-wrap justify-center items-center gap-3">
                                    <p class="text-[11px] font-black text-slate-500 uppercase tracking-widest">Klien Sesi Chat</p>
                                    <div class="hidden sm:block w-1.5 h-1.5 rounded-full bg-slate-300"></div>
                                    <StatusBadge :status="selectedBooking.status" />
                                </div>
                            </div>

                            <!-- Schedule Cards (Side by side) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full mb-10">
                                <!-- Date Card -->
                                <div class="bg-white rounded-2xl p-6 border-2 border-slate-200 shadow-sm flex flex-col items-center text-center">
                                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                                    </div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Tanggal Sesi</p>
                                    <p class="font-black text-slate-800 text-[15px]">{{ formatDate(selectedBooking.schedule_start) }}</p>
                                </div>
                                <!-- Time Card -->
                                <div class="bg-white rounded-2xl p-6 border-2 border-slate-200 shadow-sm flex flex-col items-center text-center">
                                    <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary border border-primary/20 flex items-center justify-center mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    </div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Waktu Sesi</p>
                                    <p class="font-black text-slate-800 text-[15px]">{{ formatTime(selectedBooking.schedule_start) }} - {{ formatTime(selectedBooking.schedule_end) }} WIB</p>
                                </div>
                            </div>

                            <!-- Action Area -->
                            <div class="w-full max-w-md flex flex-col gap-3">
                                <!-- Enter Chat -->
                                <div v-if="canEnterChat(selectedBooking)">
                                    <Link :href="route('counselor.chat.show', selectedBooking.id)" 
                                          class="w-full py-4 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-2xl flex items-center justify-center gap-2 transition-transform hover:-translate-y-0.5 shadow-lg shadow-slate-900/20 text-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                                        {{ selectedBooking.status === 'completed' ? 'Lihat Riwayat Chat' : 'Masuk Ruang Chat' }}
                                    </Link>
                                </div>
                                <div v-else>
                                    <button disabled class="w-full py-4 bg-white border-2 border-slate-100 text-slate-400 font-bold rounded-2xl flex items-center justify-center gap-2 cursor-not-allowed">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                                        Chat Belum Aktif
                                    </button>
                                    <p class="text-[11px] text-center text-slate-500 font-semibold mt-3">
                                        Akses chat konselor dibuka mulai 10 menit sebelum jadwal sesi.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.3s ease-out forwards;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Custom Scrollbar for Chat List */
.overflow-y-auto::-webkit-scrollbar {
    width: 5px;
}
.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.overflow-y-auto:hover::-webkit-scrollbar-thumb {
    background: #cbd5e1;
}
</style>
