<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import PrimaryButton from '@/Components/ui/PrimaryButton.vue';
import { getCounselorPhotoUrl } from '@/utils/counselorPhoto';
import { 
    Clock, Calendar, MessageSquare, FileText, UploadCloud, 
    CreditCard, CheckCircle, AlertCircle, ArrowLeft, Copy, Shield,
    Video, MapPin, ExternalLink, Building2, Navigation, Car, Phone, Landmark as LandmarkIcon, Globe
} from 'lucide-vue-next';

interface MeetingLocation {
    place_name?: string;
    address?: string;
    city?: string;
    google_maps_url?: string;
    landmark?: string;
    parking_info?: string;
    contact_on_site?: string;
}

interface Booking {
    id: number;
    schedule_start: string;
    schedule_end: string;
    status: string;
    service_type: string;
    price_at_booking: number;
    payment_deadline: string;
    intake_form: string;
    meeting_link: string | null;
    meeting_location: MeetingLocation | string | null;
    meeting_notes: string | null;
    counselor: {
        id: number;
        name: string;
        counselor_profile?: {
            full_title: string;
            photo_path: string | null;
            photo_url: string | null;
        };
    };
    payment?: {
        status: string;
        rejection_reason?: string;
    };
    service_price: {
        duration_minutes: number;
    };
}

const props = defineProps<{
    booking: Booking;
}>();

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(price);
};

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

const counselorPhotoUrl = (photoPath: string | null | undefined, name: string, photoUrl?: string | null) => {
    return getCounselorPhotoUrl(photoUrl ?? null, photoPath ?? null, name);
};

// Helper: parse meeting_location (backward compatible with old string data)
const meetingLoc = computed(() => {
    const loc = props.booking.meeting_location;
    if (!loc) return null;
    // If it's a string (legacy data), wrap it
    if (typeof loc === 'string') {
        return { place_name: loc, address: '', city: '', google_maps_url: '', landmark: '', parking_info: '', contact_on_site: '' } as MeetingLocation;
    }
    return loc as MeetingLocation;
});

const hasLocationData = computed(() => {
    const l = meetingLoc.value;
    return l && (l.place_name || l.address || l.city);
});

const form = useForm({
    proof: null as File | null,
});

const rescheduleForm = useForm({
    new_schedule_start: '',
    reason: '',
});

const previewUrl = ref<string | null>(null);

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const file = target.files[0];
        form.proof = file;
        previewUrl.value = URL.createObjectURL(file);
    }
};

const submitPayment = () => {
    form.post(route('client.bookings.payment.store', props.booking.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('proof');
            previewUrl.value = null;
        },
    });
};

const submitReschedule = () => {
    rescheduleForm.post(route('client.bookings.reschedule.request', props.booking.id), {
        preserveScroll: true,
        onSuccess: () => {
            rescheduleForm.reset();
        },
    });
};

const copyToClipboard = (text: string) => {
    navigator.clipboard.writeText(text);
};

const canEnterChat = () => {
    const displayStatus = getDisplayStatus(props.booking);
    if (!['confirmed', 'in_session'].includes(displayStatus)) {
        return false;
    }

    const now = new Date().getTime();
    const scheduleStart = new Date(props.booking.schedule_start).getTime();
    const scheduleEnd = new Date(props.booking.schedule_end).getTime();

    return now >= scheduleStart && now <= scheduleEnd + 60 * 60 * 1000;
};

const getDisplayStatus = (booking: Booking) => {
    if (['completed', 'cancelled', 'rejected', 'expired'].includes(booking.status)) {
        return booking.status;
    }

    if (booking.status === 'confirmed' || booking.status === 'in_session') {
        const now = new Date().getTime();
        const start = new Date(booking.schedule_start).getTime();
        let end = new Date(booking.schedule_end).getTime();

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

const timeLeft = ref('--:--');
let timerInterval: any = null;
let hasReloaded = false;

const calculateTimeLeft = () => {
    const deadline = new Date(props.booking.payment_deadline).getTime();
    const now = new Date().getTime();
    const diff = deadline - now;

    if (diff <= 0) {
        timeLeft.value = 'Waktu Habis';
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
        // Use Inertia reload instead of window.location.reload() to avoid infinite loop
        if (!hasReloaded) {
            hasReloaded = true;
            setTimeout(() => {
                router.reload({ only: ['booking'] });
            }, 1500);
        }
        return;
    }

    const minutes = Math.floor(diff / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

    const minutesStr = minutes.toString().padStart(2, '0');
    const secondsStr = seconds.toString().padStart(2, '0');

    timeLeft.value = `${minutesStr}:${secondsStr}`;
};

let removeRouterListener: (() => void) | null = null;

const handleBeforeUnload = (event: BeforeUnloadEvent) => {
    if (props.booking.status === 'pending_payment' && !form.processing) {
        event.preventDefault();
        event.returnValue = '';
    }
};

onMounted(() => {
    if (props.booking.status === 'pending_payment') {
        calculateTimeLeft();
        timerInterval = setInterval(calculateTimeLeft, 1000);

        window.addEventListener('beforeunload', handleBeforeUnload);

        removeRouterListener = router.on('before', (event) => {
            // Prevent warning when the timer automatically reloads or form is submitting
            if (props.booking.status === 'pending_payment' && !form.processing && event.detail.visit.method === 'get') {
                if (!confirm('Anda belum mengunggah bukti pembayaran. Yakin ingin meninggalkan halaman ini?')) {
                    event.preventDefault();
                }
            }
        });
    }
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
    window.removeEventListener('beforeunload', handleBeforeUnload);
    if (removeRouterListener) {
        removeRouterListener();
    }
});
</script>

<template>
    <Head title="Detail Booking" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('client.bookings.index')" class="text-gray-500 hover:text-gray-700 font-bold">
                    &larr;
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Booking #{{ booking.id.toString().padStart(5, '0') }}
                </h2>
                <StatusBadge :status="getDisplayStatus(booking)" />
            </div>
        </template>

        <div class="py-8 sm:py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- Counselor Profile Card -->
                <div class="bg-white rounded-[2rem] border border-slate-200 shadow-lg shadow-slate-100/50 p-8 sm:p-10 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-bl from-primary/10 to-transparent rounded-bl-[5rem]"></div>
                    <div class="absolute bottom-0 left-0 w-32 h-32 bg-gradient-to-tr from-blue-50 to-transparent rounded-tr-[4rem]"></div>

                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-8">
                            <FileText class="w-5 h-5 text-primary" />
                            <h3 class="font-black text-xl text-slate-900">Detail Sesi Konseling</h3>
                        </div>

                        <!-- Counselor Info -->
                        <div class="flex flex-col sm:flex-row items-center text-center sm:text-left gap-6 mb-8">
                            <div class="w-24 h-24 rounded-2xl overflow-hidden border-2 border-white bg-slate-50 shrink-0 shadow-lg shadow-primary/10 rotate-3">
                                <img 
                                    :src="counselorPhotoUrl(booking.counselor.counselor_profile?.photo_path, booking.counselor.name, booking.counselor.counselor_profile?.photo_url)" 
                                    class="w-full h-full object-cover -rotate-3 scale-110"
                                >
                            </div>
                            <div>
                                <h4 class="font-black text-xl text-slate-900 leading-tight mb-2">{{ booking.counselor.name }}{{ booking.counselor.counselor_profile?.full_title ? ', ' + booking.counselor.counselor_profile.full_title : '' }}</h4>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/10 text-primary text-[10px] font-bold uppercase tracking-widest">
                                    {{ booking.service_type }} Session
                                </span>
                            </div>
                        </div>

                        <!-- Session Details Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 flex items-center gap-4">
                                <div class="w-10 h-10 rounded-lg bg-white flex items-center justify-center shadow-sm border border-slate-100">
                                    <Clock class="w-5 h-5 text-primary" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Durasi</p>
                                    <p class="font-bold text-slate-800">{{ booking.service_price.duration_minutes }} Menit</p>
                                </div>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 flex items-center gap-4">
                                <div class="w-10 h-10 rounded-lg bg-white flex items-center justify-center shadow-sm border border-slate-100">
                                    <Calendar class="w-5 h-5 text-primary" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Jadwal</p>
                                    <p class="font-bold text-slate-800 text-sm">{{ formatDate(booking.schedule_start) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Intake Form -->
                        <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100">
                            <h4 class="font-bold text-slate-700 mb-3 flex items-center gap-2 text-sm">
                                <MessageSquare class="w-4 h-4 text-slate-400" />
                                Topik Keluhan (Intake Form)
                            </h4>
                            <p class="text-slate-600 whitespace-pre-wrap break-words leading-relaxed text-sm">{{ booking.intake_form }}</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Section -->
                <div class="bg-white rounded-[2rem] border border-slate-200 shadow-lg shadow-slate-100/50 p-8 sm:p-10">
                    <div class="flex items-center gap-2 mb-6">
                        <CreditCard class="w-5 h-5 text-primary" />
                        <h3 class="font-black text-xl text-slate-900">Pembayaran</h3>
                    </div>
                    
                    <!-- Price Display -->
                    <div class="flex items-center justify-between bg-gradient-to-r from-primary/5 to-transparent rounded-2xl p-6 mb-8 border border-primary/10">
                        <span class="text-slate-500 font-medium">Total Harga</span>
                        <span class="font-black text-primary-700 text-3xl">{{ formatPrice(booking.price_at_booking) }}</span>
                    </div>

                    <!-- Pending Payment -->
                    <div v-if="booking.status === 'pending_payment' || booking.payment?.status === 'rejected'" class="space-y-6">
                        <div v-if="booking.status === 'pending_payment'">
                            <!-- Deadline -->
                            <div class="flex items-center justify-between bg-red-50 px-5 py-4 rounded-xl border border-red-100">
                                <div class="flex items-center gap-2">
                                    <AlertCircle class="w-4 h-4 text-red-500" />
                                    <span class="text-sm font-bold text-red-800">Sisa Waktu Pembayaran</span>
                                </div>
                                <span class="text-lg font-black text-red-600 font-mono tracking-wider">
                                    {{ timeLeft }}
                                </span>
                            </div>

                            <!-- Bank Info -->
                            <div class="bg-gradient-to-br from-blue-50 via-blue-50/80 to-white rounded-2xl border border-blue-200/60 p-6 shadow-sm">
                                <p class="text-sm text-blue-800 font-medium mb-4">Transfer ke rekening berikut:</p>
                                <div class="bg-white rounded-xl p-5 border border-blue-100 shadow-sm">
                                    <div class="flex items-center gap-2 mb-3">
                                        <div class="bg-blue-600 px-3 py-1 rounded-lg">
                                            <span class="text-xs font-black text-white tracking-wide">BCA Digital</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-2xl font-black tracking-wider text-slate-800">005244626066</p>
                                        <button @click="copyToClipboard('005244626066')" class="p-2 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 transition-colors border border-blue-200" title="Salin">
                                            <Copy class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-500 mt-2">a/n NADYA MUBARANIZ RACHMAPUTRI</p>
                                </div>
                                <p class="text-sm text-blue-700 mt-4">Sejumlah <span class="font-bold">{{ formatPrice(booking.price_at_booking) }}</span>. Setelah itu unggah bukti transfer di bawah ini.</p>
                            </div>
                        </div>

                        <!-- Rejected Notice -->
                        <div v-if="booking.payment?.status === 'rejected'" class="bg-red-50 border border-red-200 p-5 rounded-xl flex items-start gap-4">
                            <AlertCircle class="w-6 h-6 text-red-500 shrink-0 mt-0.5" />
                            <div>
                                <h4 class="font-bold text-red-800 mb-1">Bukti Pembayaran Ditolak</h4>
                                <p class="text-sm text-red-700 leading-relaxed">Alasan: {{ booking.payment?.rejection_reason || 'Bukti tidak sah / buram.' }}</p>
                                <p class="text-sm text-red-600 mt-2 font-semibold">Silakan unggah ulang bukti yang benar.</p>
                            </div>
                        </div>

                        <!-- Upload Form -->
                        <form @submit.prevent="submitPayment" class="space-y-5">
                            <div class="border-2 border-dashed rounded-2xl p-8 text-center transition-all cursor-pointer relative group" 
                                 :class="previewUrl ? 'border-primary/30 bg-primary/5' : 'border-slate-300 hover:border-primary-400 hover:bg-slate-50'">
                                <input type="file" @change="handleFileChange" accept="image/jpeg, image/png, image/jpg, image/webp" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                                
                                <div v-if="!previewUrl" class="text-sm text-slate-500">
                                    <div class="w-16 h-16 rounded-2xl bg-slate-100 group-hover:bg-primary-50 flex items-center justify-center mx-auto mb-4 transition-colors">
                                        <UploadCloud class="w-8 h-8 text-slate-400 group-hover:text-primary transition-colors" />
                                    </div>
                                    <p class="text-base font-bold text-slate-700 mb-1"><span class="text-primary-600">Klik untuk unggah</span> atau seret file ke sini</p>
                                    <p class="text-xs text-slate-400 font-medium">PNG, JPG, JPEG, WEBP — Maks 5MB</p>
                                </div>
                                
                                <div v-else class="relative z-20">
                                    <p class="text-xs font-bold text-slate-700 mb-4 uppercase tracking-wider">Preview Bukti Transfer</p>
                                    <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm bg-white mb-4 inline-block">
                                        <img :src="previewUrl" class="max-h-64 object-contain" alt="Preview payment">
                                    </div>
                                    <p class="text-xs text-primary-600 font-medium pointer-events-none inline-flex items-center gap-1.5">
                                        <UploadCloud class="w-3.5 h-3.5" /> Klik kotak ini untuk ganti file
                                    </p>
                                </div>
                            </div>
                            <div v-if="form.errors.proof" class="text-red-500 text-xs font-bold px-1">{{ form.errors.proof }}</div>

                            <button type="submit" 
                                    class="w-full px-6 py-4 bg-primary hover:bg-primary-700 text-white rounded-xl text-base font-bold transition-all shadow-lg flex items-center justify-center gap-2" 
                                    :disabled="!form.proof || form.processing"
                                    :class="(!form.proof || form.processing) ? 'opacity-50 cursor-not-allowed shadow-none' : 'shadow-primary/30'">
                                <UploadCloud class="w-5 h-5" />
                                {{ form.processing ? 'Mengunggah...' : 'Unggah Bukti Pembayaran' }}
                            </button>
                        </form>
                    </div>

                    <!-- Waiting Verification -->
                    <div v-else-if="booking.status === 'pending_verification'" class="text-center py-10">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-blue-50 text-blue-600 mb-5 border border-blue-100 shadow-sm">
                            <Clock class="w-10 h-10 animate-pulse" />
                        </div>
                        <h4 class="text-blue-700 font-black text-2xl mb-3">Menunggu Verifikasi</h4>
                        <p class="text-base text-slate-500 leading-relaxed max-w-sm mx-auto">Bukti pembayaran Anda sedang dicek oleh tim admin kami. Anda akan mendapat notifikasi segera.</p>
                    </div>

                    <!-- Approved -->
                    <div v-else-if="booking.payment?.status === 'approved'" class="text-center py-10">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-primary/10 text-primary mb-5 border border-primary/20 shadow-sm">
                            <CheckCircle class="w-10 h-10" />
                        </div>
                        <h4 class="text-primary font-black text-2xl mb-2">Pembayaran Diterima</h4>
                        <p class="text-base text-slate-500">Sesi konseling Anda sudah terkonfirmasi.</p>
                    </div>
                    
                    <!-- Expired -->
                    <div v-else-if="booking.status === 'expired'" class="text-center py-10">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-50 text-red-600 mb-5 border border-red-100 shadow-sm">
                            <AlertCircle class="w-10 h-10" />
                        </div>
                        <h4 class="text-red-600 font-black text-2xl mb-2">Sesi Kedaluwarsa</h4>
                        <p class="text-base text-slate-500">Anda tidak mengunggah bukti pembayaran dalam batas waktu.</p>
                    </div>
                </div>

                <!-- Meeting Info / Lokasi Pertemuan -->
                <div v-if="(booking.meeting_link || hasLocationData || booking.meeting_notes) && (booking.status === 'confirmed' || booking.status === 'in_session')" class="bg-white rounded-[2rem] border border-slate-200 shadow-lg shadow-slate-100/50 p-8 sm:p-10 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-bl from-primary/10 to-transparent rounded-bl-[4rem]"></div>
                    <div class="relative z-10 space-y-6">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10 text-primary border border-primary/20">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15.6 11.6L22 7v10l-6.4-4.5v-1z"/><rect width="14" height="14" x="2" y="5" rx="2" ry="2"/></svg>
                            </span>
                            <h3 class="font-black text-xl text-slate-900">{{ booking.service_type === 'online' ? 'Informasi Link Meeting' : 'Informasi Lokasi Pertemuan' }}</h3>
                        </div>

                        <div v-if="booking.service_type === 'online' && booking.meeting_link" class="bg-primary/5 border border-primary/20 rounded-2xl p-6">
                            <p class="text-[10px] font-bold text-primary uppercase tracking-widest mb-2">Link Google Meet / Zoom</p>
                            <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
                                <a :href="booking.meeting_link" target="_blank" class="text-sm font-bold text-primary-700 hover:text-primary-800 break-all underline decoration-2 decoration-primary/30 hover:decoration-primary transition-all">
                                    {{ booking.meeting_link }}
                                </a>
                                <a :href="booking.meeting_link" target="_blank" class="w-full sm:w-auto px-4 py-2.5 bg-primary hover:bg-primary-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-primary/25 flex items-center justify-center gap-1.5 shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                    Gabung Sekarang
                                </a>
                            </div>
                        </div>

                        <!-- Structured Offline Location Display -->
                        <div v-if="booking.service_type === 'offline' && hasLocationData" class="space-y-4">
                            <!-- Place Name & Address Card -->
                            <div class="bg-gradient-to-br from-amber-50 via-orange-50/50 to-white rounded-2xl border border-amber-200/60 p-6">
                                <div class="flex items-start gap-3 mb-4">
                                    <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center shrink-0 border border-amber-200/60">
                                        <Building2 class="w-5 h-5 text-amber-700" />
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-900 text-base leading-tight">{{ meetingLoc?.place_name }}</h4>
                                        <p v-if="meetingLoc?.city" class="text-xs text-slate-500 font-medium mt-0.5">{{ meetingLoc.city }}</p>
                                    </div>
                                </div>

                                <div v-if="meetingLoc?.address" class="flex items-start gap-2.5 mb-3">
                                    <Navigation class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                                    <p class="text-sm text-slate-700 leading-relaxed">{{ meetingLoc.address }}</p>
                                </div>

                                <div v-if="meetingLoc?.landmark" class="flex items-start gap-2.5 mb-3">
                                    <LandmarkIcon class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                                    <p class="text-sm text-slate-600">
                                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Patokan:</span>
                                        <span class="ml-1">{{ meetingLoc.landmark }}</span>
                                    </p>
                                </div>

                                <!-- Google Maps Button -->
                                <a v-if="meetingLoc?.google_maps_url" :href="meetingLoc.google_maps_url" target="_blank" class="mt-4 inline-flex items-center gap-2 px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 transition-all shadow-sm hover:shadow-md group">
                                    <Globe class="w-4 h-4 text-blue-500 group-hover:text-blue-600 transition-colors" />
                                    Buka di Google Maps
                                    <ExternalLink class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-600 transition-colors" />
                                </a>
                            </div>

                            <!-- Additional Info Cards -->
                            <div v-if="meetingLoc?.parking_info || meetingLoc?.contact_on_site" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div v-if="meetingLoc.parking_info" class="bg-slate-50 border border-slate-100 rounded-xl p-4 flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center shadow-sm border border-slate-100 shrink-0">
                                        <Car class="w-4 h-4 text-slate-500" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Info Parkir</p>
                                        <p class="text-xs text-slate-700 font-medium leading-relaxed">{{ meetingLoc.parking_info }}</p>
                                    </div>
                                </div>
                                <div v-if="meetingLoc.contact_on_site" class="bg-slate-50 border border-slate-100 rounded-xl p-4 flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center shadow-sm border border-slate-100 shrink-0">
                                        <Phone class="w-4 h-4 text-slate-500" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Kontak di Lokasi</p>
                                        <p class="text-xs text-slate-700 font-medium leading-relaxed">{{ meetingLoc.contact_on_site }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="booking.meeting_notes" class="bg-slate-50 border border-slate-100 rounded-2xl p-6">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Catatan dari Konselor</p>
                            <p class="text-slate-600 text-sm leading-relaxed whitespace-pre-wrap break-words">{{ booking.meeting_notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actions Panel -->
                <div v-if="['confirmed', 'in_session', 'pending_reschedule'].includes(getDisplayStatus(booking))" class="bg-white rounded-[2rem] border border-slate-200 shadow-lg shadow-slate-100/50 p-8 sm:p-10 space-y-5">
                    <div class="flex items-center gap-2 mb-2">
                        <Shield class="w-5 h-5 text-primary" />
                        <h3 class="font-black text-xl text-slate-900">Tindakan</h3>
                    </div>

                    <!-- Chat Session Actions -->
                    <template v-if="booking.service_type === 'chat'">
                        <Link v-if="canEnterChat()" :href="route('client.chat.show', booking.id)" class="block">
                            <button class="w-full px-6 py-4 bg-primary hover:bg-primary-700 text-white rounded-xl text-base font-bold transition-all shadow-lg shadow-primary/30 flex items-center justify-center gap-2">
                                <MessageSquare class="w-5 h-5" />
                                Masuk Chat Room
                            </button>
                        </Link>
                        <button v-else class="w-full px-6 py-4 bg-slate-100 text-slate-400 rounded-xl text-base font-bold cursor-not-allowed flex items-center justify-center gap-2" disabled>
                            <MessageSquare class="w-5 h-5" />
                            Masuk Chat Room
                        </button>
                        <p v-if="!canEnterChat()" class="text-sm text-slate-400 text-center">
                            Chat aktif saat jadwal dimulai hingga 60 menit setelah sesi berakhir.
                        </p>
                    </template>

                    <!-- Online Session Actions -->
                    <template v-if="booking.service_type === 'online'">
                        <a v-if="booking.meeting_link" :href="booking.meeting_link" target="_blank" class="block">
                            <button class="w-full px-6 py-4 bg-primary hover:bg-primary-700 text-white rounded-xl text-base font-bold transition-all shadow-lg shadow-primary/30 flex items-center justify-center gap-2 animate-pulse hover:animate-none">
                                <Video class="w-5 h-5" />
                                Gabung Zoom / Meet
                                <ExternalLink class="w-4 h-4 opacity-80" />
                            </button>
                        </a>
                        <div v-else>
                            <button class="w-full px-6 py-4 bg-slate-100 text-slate-400 rounded-xl text-base font-bold cursor-not-allowed flex items-center justify-center gap-2" disabled>
                                <Video class="w-5 h-5" />
                                Gabung Zoom / Meet
                            </button>
                            <p class="text-sm text-slate-400 text-center mt-3 leading-relaxed">
                                Link meeting belum tersedia. Konselor akan mengunggah link sebelum sesi dimulai.
                            </p>
                        </div>
                        <p v-if="booking.meeting_link" class="text-sm text-slate-400 text-center">
                            Link meeting aktif. Silakan masuk ke dalam panggilan video call.
                        </p>
                    </template>

                    <!-- Offline Session Actions -->
                    <template v-if="booking.service_type === 'offline'">
                        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-6 space-y-4">
                            <div class="flex items-center gap-2 pb-3 border-b border-slate-200/60">
                                <MapPin class="w-5 h-5 text-primary shrink-0" />
                                <span class="font-bold text-slate-800 text-sm uppercase tracking-wider">Lokasi Pertemuan</span>
                            </div>
                            
                            <div v-if="hasLocationData">
                                <div class="space-y-2">
                                    <p class="font-bold text-slate-800 text-base leading-relaxed">
                                        {{ meetingLoc?.place_name }}
                                    </p>
                                    <p v-if="meetingLoc?.address" class="text-sm text-slate-600 leading-relaxed">
                                        {{ meetingLoc.address }}<span v-if="meetingLoc?.city">, {{ meetingLoc.city }}</span>
                                    </p>
                                    <p v-if="meetingLoc?.landmark" class="text-xs text-slate-500 italic">
                                        Patokan: {{ meetingLoc.landmark }}
                                    </p>
                                </div>

                                <!-- Google Maps Button -->
                                <a v-if="meetingLoc?.google_maps_url" :href="meetingLoc.google_maps_url" target="_blank" class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-600 hover:text-blue-600 hover:border-blue-200 transition-all">
                                    <Globe class="w-3.5 h-3.5" />
                                    Buka Maps
                                    <ExternalLink class="w-3 h-3 opacity-60" />
                                </a>

                                <div v-if="meetingLoc?.parking_info || meetingLoc?.contact_on_site" class="mt-3 pt-3 border-t border-slate-200/40 space-y-2">
                                    <div v-if="meetingLoc.parking_info" class="flex items-start gap-2">
                                        <Car class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" />
                                        <p class="text-xs text-slate-600">{{ meetingLoc.parking_info }}</p>
                                    </div>
                                    <div v-if="meetingLoc.contact_on_site" class="flex items-start gap-2">
                                        <Phone class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" />
                                        <p class="text-xs text-slate-600">{{ meetingLoc.contact_on_site }}</p>
                                    </div>
                                </div>

                                <div v-if="booking.meeting_notes" class="mt-3 pt-3 border-t border-slate-200/40">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Catatan Tambahan</p>
                                    <p class="text-slate-600 text-sm leading-relaxed whitespace-pre-wrap break-words">
                                        {{ booking.meeting_notes }}
                                    </p>
                                </div>
                            </div>
                            <div v-else class="py-2">
                                <p class="text-slate-500 text-sm italic">
                                    Menunggu informasi lokasi pertemuan dari konselor.
                                </p>
                            </div>
                        </div>
                        <p class="text-xs text-slate-400 text-center font-medium leading-relaxed mt-2">
                            Sesi ini adalah tatap muka (offline). Silakan datang ke lokasi pertemuan tepat waktu sesuai jadwal.
                        </p>
                    </template>

                    <div v-if="booking.status === 'pending_reschedule'" class="rounded-xl border border-orange-200 bg-orange-50 p-4 text-sm text-orange-800 font-medium">
                        Pengajuan reschedule sedang diproses admin.
                    </div>

                    <form v-if="booking.status === 'confirmed'" class="space-y-4 border-t border-slate-100 pt-6" @submit.prevent="submitReschedule">
                        <p class="font-bold text-slate-800">Ajukan Reschedule Jadwal</p>
                        <input
                            v-model="rescheduleForm.new_schedule_start"
                            type="datetime-local"
                            class="w-full rounded-xl border-2 border-slate-300 text-slate-800 text-base font-semibold shadow-sm focus:border-primary-500 focus:ring-primary-500 py-3 px-4 bg-white"
                            required
                        >
                        <textarea
                            v-model="rescheduleForm.reason"
                            rows="3"
                            maxlength="500"
                            class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            placeholder="Tuliskan alasan reschedule"
                            required
                        ></textarea>
                        <p v-if="rescheduleForm.errors.new_schedule_start" class="text-xs text-red-600">{{ rescheduleForm.errors.new_schedule_start }}</p>
                        <p v-if="rescheduleForm.errors.reason" class="text-xs text-red-600">{{ rescheduleForm.errors.reason }}</p>
                        <button type="submit" 
                                class="w-full px-6 py-3.5 bg-orange-500 hover:bg-orange-600 text-white rounded-xl text-sm font-bold transition-all shadow-md shadow-orange-200 flex items-center justify-center gap-2"
                                :disabled="rescheduleForm.processing">
                            {{ rescheduleForm.processing ? 'Mengirim...' : 'Ajukan Reschedule Jadwal' }}
                        </button>
                    </form>
                </div>

                <!-- Testimonial Panel (For Completed) -->
                <div v-if="getDisplayStatus(booking) === 'completed'" class="bg-gradient-to-br from-primary/5 to-primary/10 rounded-[2rem] border border-primary/20 shadow-lg shadow-primary/5 p-8 sm:p-10 space-y-5 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white text-primary mb-2 shadow-sm border border-primary/10">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                    </div>
                    <h3 class="font-black text-xl text-slate-900">Bagaimana Sesi Anda?</h3>
                    <p class="text-slate-600 text-sm max-w-md mx-auto leading-relaxed">Sesi konseling telah selesai. Ceritakan pengalaman Anda dan bantu orang lain menemukan layanan yang tepat.</p>
                    
                    <Link :href="route('client.testimonials.create')" class="inline-block mt-4">
                        <button class="px-8 py-4 bg-primary hover:bg-primary-700 text-white rounded-xl text-base font-bold transition-all shadow-lg shadow-primary/30 flex items-center justify-center gap-2 mx-auto">
                            Berikan Ulasan
                        </button>
                    </Link>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
