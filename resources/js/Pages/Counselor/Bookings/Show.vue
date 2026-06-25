<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/ui/PrimaryButton.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import { computed } from 'vue';
import { 
    MapPin, Building2, Navigation, Car, Phone, Landmark, Globe, 
    FileText, Clock, Calendar, CreditCard, User, Mail, 
    MessageSquare, Save, CheckCircle2, ExternalLink, Info
} from 'lucide-vue-next';

interface MeetingLocation {
    place_name: string;
    address: string;
    city: string;
    google_maps_url: string;
    landmark: string;
    parking_info: string;
    contact_on_site: string;
}

interface Booking {
    id: number;
    client: { name: string; email: string; whatsapp?: string; created_at?: string };
    service_type: string;
    practitioner_type: string;
    duration_minutes: number;
    schedule_start: string;
    schedule_end: string;
    status: string;
    price_at_booking: number;
    intake_form: string;
    meeting_link: string | null;
    meeting_location: MeetingLocation | null;
    meeting_notes: string | null;
    service_price: any;
    payment: any;
    chat_session: any;
}

const props = defineProps<{
    booking: Booking;
}>();

const loc = props.booking.meeting_location;

const meetingForm = useForm({
    meeting_link: props.booking.meeting_link || '',
    meeting_location: {
        place_name: loc?.place_name || '',
        address: loc?.address || '',
        city: loc?.city || '',
        google_maps_url: loc?.google_maps_url || '',
        landmark: loc?.landmark || '',
        parking_info: loc?.parking_info || '',
        contact_on_site: loc?.contact_on_site || '',
    },
    meeting_notes: props.booking.meeting_notes || '',
});

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(amount);

const formatDate = (dateString: string) =>
    new Date(dateString).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

const formatTime = (dateString: string) =>
    new Date(dateString).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

const serviceLabel = computed(() => {
    switch (props.booking.service_type) {
        case 'chat': return 'Chat Konseling';
        case 'online': return 'Online (Video Call)';
        case 'offline': return 'Tatap Muka (Offline)';
        default: return props.booking.service_type;
    }
});

const serviceIcon = computed(() => {
    switch (props.booking.service_type) {
        case 'chat': return '💬';
        case 'online': return '📹';
        case 'offline': return '🏢';
        default: return '📋';
    }
});

const isFinished = computed(() => {
    if (['completed', 'cancelled', 'rejected', 'expired'].includes(props.booking.status)) {
        return true;
    }
    const now = new Date().getTime();
    const end = new Date(props.booking.schedule_end).getTime();
    return now > end;
});

const canEditMeeting = computed(() => {
    return ['confirmed', 'in_session'].includes(props.booking.status) && !isFinished.value;
});

const hasLocationFilled = computed(() => {
    const l = props.booking.meeting_location;
    return l && (l.place_name || l.address || l.city);
});

const submitMeeting = () => {
    meetingForm.put(route('counselor.bookings.meeting.update', props.booking.id));
};

const canEnterChat = computed(() => {
    if (!['confirmed', 'in_session'].includes(props.booking.status)) {
        return false;
    }
    const now = new Date().getTime();
    const start = new Date(props.booking.schedule_start).getTime() - 10 * 60 * 1000; // 10 minutes before
    return now >= start;
});
</script>

<template>
    <Head :title="`Booking #${booking.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('counselor.bookings.index')" class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center transition-all shadow-sm hover:shadow-md hover:-translate-x-0.5 text-slate-500 hover:text-primary-600 group">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="transition-colors"><path d="m15 18-6-6 6-6"/></svg>
                </Link>
                <div>
                    <h2 class="font-black text-2xl text-slate-800 tracking-tight">Detail Sesi #{{ booking.id }}</h2>
                </div>
            </div>
        </template>

        <div class="max-w-6xl mx-auto pb-12 space-y-8 mt-4">
            
            <!-- VIBRANT Hero Summary Card -->
            <div class="rounded-3xl p-6 sm:p-8 md:p-10 shadow-2xl flex flex-col md:flex-row justify-between items-start md:items-center gap-8 relative overflow-hidden bg-gradient-to-br from-primary-600 via-primary-700 to-purple-900 shadow-primary-900/20">
                
                <!-- Decorative animated meshes -->
                <div class="absolute -right-20 -top-20 w-80 h-80 rounded-full mix-blend-screen opacity-20 blur-3xl pointer-events-none bg-white"></div>
                <div class="absolute -left-20 -bottom-20 w-64 h-64 rounded-full mix-blend-overlay opacity-30 blur-2xl pointer-events-none bg-white"></div>

                <div class="flex items-start sm:items-center gap-6 z-10 relative">
                    <!-- Glass Icon -->
                    <div class="w-20 h-20 rounded-2xl flex items-center justify-center shrink-0 bg-white/10 backdrop-blur-md border border-white/20 shadow-inner text-white">
                        <Globe v-if="booking.service_type === 'online'" class="w-10 h-10 drop-shadow-md" />
                        <Building2 v-else-if="booking.service_type === 'offline'" class="w-10 h-10 drop-shadow-md" />
                        <MessageSquare v-else class="w-10 h-10 drop-shadow-md" />
                    </div>
                    
                    <div>
                        <div class="flex flex-wrap items-center gap-3 mb-2">
                            <h2 class="text-3xl font-black text-white tracking-tight drop-shadow-sm">{{ booking.client.name }}</h2>
                        </div>
                        
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm font-medium mb-4 text-white/90">
                            <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/10 backdrop-blur-sm border border-white/10">
                                {{ serviceIcon }} {{ serviceLabel }}
                            </span>
                            <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/10 backdrop-blur-sm border border-white/10">
                                <Clock class="w-4 h-4" /> {{ booking.duration_minutes }} menit
                            </span>
                            <StatusBadge :status="booking.status" class="scale-105 shadow-sm" />
                        </div>
                        
                        <p class="text-white font-semibold flex items-center gap-2 text-sm drop-shadow-sm">
                            <Calendar class="w-4 h-4 text-white/80" />
                            {{ formatDate(booking.schedule_start) }} &nbsp;•&nbsp; {{ formatTime(booking.schedule_start) }} - {{ formatTime(booking.schedule_end) }} WIB
                        </p>
                    </div>
                </div>
                
                <!-- Action Button -->
                <div v-if="booking.status === 'confirmed' || booking.status === 'in_session'" class="w-full md:w-auto z-20 relative shrink-0">
                    
                    <!-- Chat Session Action -->
                    <template v-if="booking.service_type === 'chat'">
                        <Link :href="route('counselor.chat.index', { booking: booking.id })" class="w-full md:w-auto px-8 py-4 bg-white hover:bg-slate-50 active:scale-95 text-primary-700 rounded-2xl text-base font-black transition-all shadow-xl flex items-center justify-center gap-2 group border border-white/50 cursor-pointer block relative z-20">
                            Masuk Chat Room
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-x-1 transition-transform"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </Link>
                    </template>

                    <!-- Online Session Action -->
                    <template v-if="booking.service_type === 'online'">
                        <a v-if="booking.meeting_link" :href="booking.meeting_link" target="_blank" class="block">
                            <button class="w-full md:w-auto px-8 py-4 bg-white hover:bg-slate-50 active:scale-95 text-primary-700 rounded-2xl text-base font-black transition-all shadow-xl flex items-center justify-center gap-2 group border border-white/50">
                                Mulai Video Call
                                <ExternalLink class="w-5 h-5 group-hover:-translate-y-0.5 group-hover:translate-x-0.5 transition-transform" />
                            </button>
                        </a>
                        <div v-else class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center md:text-right max-w-[220px] shadow-inner text-white">
                            <p class="text-xs font-bold leading-relaxed">
                                Link meeting belum diatur.
                            </p>
                        </div>
                    </template>

                    <!-- Offline Session Action -->
                    <template v-if="booking.service_type === 'offline'">
                        <div v-if="hasLocationFilled" class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center md:text-right max-w-[250px] shadow-inner text-white">
                            <p class="text-sm font-bold mb-1 truncate drop-shadow-sm">{{ booking.meeting_location?.place_name }}</p>
                            <a v-if="booking.meeting_location?.google_maps_url" :href="booking.meeting_location.google_maps_url" target="_blank" class="inline-flex items-center gap-1 text-xs font-black text-white hover:text-purple-100 mt-1 bg-white/20 px-3 py-1.5 rounded-lg transition-colors">
                                Buka Maps <ExternalLink class="w-3.5 h-3.5" />
                            </a>
                        </div>
                        <div v-else class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center md:text-right max-w-[220px] shadow-inner text-white">
                            <p class="text-xs font-bold leading-relaxed">
                                Lokasi belum diatur.
                            </p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Two Column Layout for Details -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 items-stretch">
                
                <!-- Left Column (Intake Form & Info) -->
                <div class="lg:col-span-3 space-y-8 flex flex-col">
                    
                    <!-- Intake Form Card -->
                    <div class="bg-white rounded-3xl shadow-[0_4px_25px_rgb(0,0,0,0.03)] border border-slate-100 overflow-hidden">
                        <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/50">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-50 to-blue-50 text-indigo-500 flex items-center justify-center shrink-0 shadow-sm border border-indigo-100/50">
                                <FileText class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-slate-800 tracking-tight">Intake Form Klien</h3>
                                <p class="text-xs font-medium text-slate-500 mt-0.5">Keluhan dan topik yang diajukan klien</p>
                            </div>
                        </div>
                        <div class="p-8">
                            <div v-if="booking.intake_form" class="relative bg-slate-50 rounded-2xl p-6 border border-slate-100 shadow-inner">
                                <div class="absolute left-0 top-6 bottom-6 w-1.5 bg-gradient-to-b from-indigo-400 to-blue-500 rounded-r-full"></div>
                                <div class="pl-4">
                                    <MessageSquare class="w-6 h-6 text-indigo-200 mb-3" />
                                    <p class="text-base text-slate-700 leading-relaxed font-medium whitespace-pre-wrap break-words">
                                        "{{ booking.intake_form }}"
                                    </p>
                                </div>
                            </div>
                            <div v-else class="bg-slate-50 rounded-2xl p-8 flex flex-col items-center justify-center text-center border border-dashed border-slate-200">
                                <FileText class="w-8 h-8 text-slate-300 mb-3" />
                                <p class="text-sm font-semibold text-slate-500">Tidak Ada Intake Form</p>
                                <p class="text-xs text-slate-400 mt-1">Klien tidak menyertakan catatan khusus untuk sesi ini.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Meeting Info Form (Visible for Online/Offline) -->
                    <div v-if="booking.service_type !== 'chat'" class="bg-white rounded-3xl shadow-[0_4px_25px_rgb(0,0,0,0.03)] border border-slate-100 overflow-hidden relative">
                        <div class="px-8 py-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/50">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 shadow-sm border"
                                 :class="booking.service_type === 'online' ? 'bg-emerald-50 text-emerald-500 border-emerald-100/50' : 'bg-amber-50 text-amber-500 border-amber-100/50'">
                                <Info class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-slate-800 tracking-tight">Pengaturan {{ booking.service_type === 'online' ? 'Video Call' : 'Lokasi Tatap Muka' }}</h3>
                                <p class="text-xs font-medium text-slate-500 mt-0.5">Berikan detail agar klien bisa hadir ke sesi</p>
                            </div>
                        </div>
                        
                        <div class="p-8">
                            <!-- Flash Success -->
                            <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2 scale-95" enter-to-class="opacity-100 translate-y-0 scale-100" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0 scale-95">
                                <div v-if="($page.props.flash as any)?.success" class="mb-8 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-2xl p-4 flex items-center gap-3 shadow-sm">
                                    <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center shrink-0 shadow-sm border border-emerald-100">
                                        <CheckCircle2 class="w-4 h-4 text-emerald-500" />
                                    </div>
                                    <span class="font-bold text-sm">{{ ($page.props.flash as any).success }}</span>
                                </div>
                            </Transition>

                            <form @submit.prevent="submitMeeting" class="space-y-6">
                                <!-- Online: Link Meeting -->
                                <div v-if="booking.service_type === 'online'">
                                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">
                                        <span class="text-emerald-500 mr-1">✦</span> Link Meeting (Zoom/GMeet)
                                    </label>
                                    <input v-model="meetingForm.meeting_link" type="url" :disabled="!canEditMeeting" placeholder="https://meet.google.com/..." class="w-full rounded-2xl border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:bg-white text-base disabled:bg-slate-100 py-4 px-5 outline-none transition-all shadow-inner" />
                                    <p v-if="meetingForm.errors.meeting_link" class="mt-2 text-xs text-rose-500 font-bold">{{ meetingForm.errors.meeting_link }}</p>
                                </div>

                                <!-- Offline: Structured Location Form -->
                                <div v-if="booking.service_type === 'offline'" class="space-y-6">
                                    <div>
                                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">
                                            <span class="text-amber-500 mr-1">✦</span> Nama Tempat <span class="text-rose-500">*</span>
                                        </label>
                                        <input v-model="meetingForm.meeting_location.place_name" type="text" :disabled="!canEditMeeting" placeholder="Klinik Bhimaswari..." class="w-full rounded-2xl border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:bg-white text-base disabled:bg-slate-100 py-4 px-5 outline-none transition-all shadow-inner" />
                                        <p v-if="(meetingForm.errors as any)['meeting_location.place_name']" class="mt-2 text-xs text-rose-500 font-bold">{{ (meetingForm.errors as any)['meeting_location.place_name'] }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">
                                            <span class="text-amber-500 mr-1">✦</span> Alamat Lengkap <span class="text-rose-500">*</span>
                                        </label>
                                        <textarea v-model="meetingForm.meeting_location.address" rows="3" :disabled="!canEditMeeting" placeholder="Jl. Sudirman No. 123..." class="w-full rounded-2xl border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:bg-white text-base disabled:bg-slate-100 py-4 px-5 outline-none resize-none transition-all shadow-inner"></textarea>
                                        <p v-if="(meetingForm.errors as any)['meeting_location.address']" class="mt-2 text-xs text-rose-500 font-bold">{{ (meetingForm.errors as any)['meeting_location.address'] }}</p>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">
                                                Kota <span class="text-rose-500">*</span>
                                            </label>
                                            <input v-model="meetingForm.meeting_location.city" type="text" :disabled="!canEditMeeting" placeholder="Jakarta..." class="w-full rounded-2xl border-slate-200 bg-slate-50 text-slate-900 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:bg-white text-base disabled:bg-slate-100 py-4 px-5 outline-none transition-all shadow-inner" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">
                                                Maps Link
                                            </label>
                                            <input v-model="meetingForm.meeting_location.google_maps_url" type="url" :disabled="!canEditMeeting" placeholder="https://maps..." class="w-full rounded-2xl border-slate-200 bg-slate-50 text-slate-900 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:bg-white text-base disabled:bg-slate-100 py-4 px-5 outline-none transition-all shadow-inner" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">
                                                Patokan
                                            </label>
                                            <input v-model="meetingForm.meeting_location.landmark" type="text" :disabled="!canEditMeeting" placeholder="Dekat halte..." class="w-full rounded-2xl border-slate-200 bg-slate-50 text-slate-900 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:bg-white text-base disabled:bg-slate-100 py-4 px-5 outline-none transition-all shadow-inner" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">
                                                Info Parkir
                                            </label>
                                            <input v-model="meetingForm.meeting_location.parking_info" type="text" :disabled="!canEditMeeting" placeholder="Ada basement..." class="w-full rounded-2xl border-slate-200 bg-slate-50 text-slate-900 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:bg-white text-base disabled:bg-slate-100 py-4 px-5 outline-none transition-all shadow-inner" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Catatan Tambahan -->
                                <div class="pt-2">
                                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3">
                                        <span class="text-blue-500 mr-1">✦</span> Catatan Tambahan
                                    </label>
                                    <textarea v-model="meetingForm.meeting_notes" rows="3" :disabled="!canEditMeeting" placeholder="Instruksi khusus untuk klien sebelum sesi..." class="w-full rounded-2xl border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white text-base disabled:bg-slate-100 py-4 px-5 outline-none resize-none transition-all shadow-inner"></textarea>
                                </div>

                                <!-- Submit Button -->
                                <div class="flex justify-end pt-4">
                                    <button 
                                        type="submit" 
                                        :disabled="meetingForm.processing || !canEditMeeting"
                                        :class="[
                                            'w-full sm:w-auto px-10 py-4 rounded-2xl text-base font-black transition-all flex items-center justify-center gap-3',
                                            canEditMeeting 
                                                ? 'bg-slate-900 hover:bg-slate-800 active:scale-95 text-white shadow-xl shadow-slate-900/20 disabled:opacity-60' 
                                                : 'bg-slate-200 text-slate-400 cursor-not-allowed shadow-none'
                                        ]"
                                    >
                                        <div v-if="meetingForm.processing" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                        <Save v-else class="w-5 h-5" />
                                        {{ meetingForm.processing ? 'Menyimpan...' : 'Simpan Pembaruan' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Column (Extra Info) -->
                <div class="lg:col-span-2 space-y-8 h-full flex flex-col">
                    <!-- Client Additional Info -->
                    <div class="bg-gradient-to-b from-white to-slate-50/50 rounded-3xl shadow-[0_4px_25px_rgb(0,0,0,0.03)] border border-slate-100 flex-grow relative overflow-hidden flex flex-col">
                        <!-- Decorative Top Border & Watermark -->
                        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-primary-400 to-primary-600"></div>
                        <User class="absolute -right-6 -top-6 w-32 h-32 text-slate-50/80 rotate-12 pointer-events-none" />

                        <div class="p-8 relative z-10 flex-grow flex flex-col">
                            <h3 class="font-black text-lg text-slate-800 tracking-tight mb-8">Informasi Klien</h3>
                            
                            <div class="space-y-6 flex-grow overflow-hidden">
                                <!-- Nama Lengkap -->
                                <div class="flex items-start gap-4 w-full">
                                    <div class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-slate-100 flex items-center justify-center shrink-0">
                                        <User class="w-5 h-5 text-slate-400" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 mt-0.5">Nama Lengkap</p>
                                        <p class="font-bold text-slate-800 text-sm break-words">{{ booking.client.name }}</p>
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="flex items-start gap-4 w-full">
                                    <div class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-slate-100 flex items-center justify-center shrink-0">
                                        <Mail class="w-5 h-5 text-slate-400" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 mt-0.5">Email Kontak</p>
                                        <p class="font-bold text-slate-800 text-sm break-words">{{ booking.client.email }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Biaya Sesi di Bagian Bawah -->
                            <div class="flex items-start gap-4 pt-6 mt-6 border-t border-slate-200/60 w-full mt-auto">
                                <div class="w-12 h-12 rounded-2xl bg-primary-50 border border-primary-100 flex items-center justify-center shrink-0 shadow-sm relative overflow-hidden">
                                    <div class="absolute inset-0 bg-gradient-to-br from-primary-50 to-primary-100/50"></div>
                                    <CreditCard class="w-5 h-5 text-primary-500 relative z-10" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-primary-500/70 uppercase tracking-widest mb-1 mt-0.5">Biaya Sesi Terbayar</p>
                                    <p class="font-black text-primary-600 text-lg">{{ formatCurrency(booking.price_at_booking) }}</p>
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
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
