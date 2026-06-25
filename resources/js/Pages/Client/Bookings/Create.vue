<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ArrowLeft,
    ArrowRight,
    Calendar,
    CheckCircle,
    Clock,
    Copy,
    CreditCard,
    Search,
    User,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    FileText,
    MessageCircle,
    Video,
    Users
} from 'lucide-vue-next';
import { getCounselorPhotoUrl } from '@/utils/counselorPhoto';

interface ServicePrice {
    id: number;
    service_type: 'chat' | 'online' | 'offline';
    practitioner_type: 'psychologist' | 'counselor';
    duration_minutes: number;
    price: number;
    is_active: boolean;
}

interface CounselorProfile {
    id: number;
    user_id: number;
    practitioner_type: 'psychologist' | 'counselor';
    full_title: string;
    photo_path: string | null;
    photo_url: string | null;
    specializations: string[];
    bio: string;
    sipp_number: string | null;
    user: {
        id: number;
        name: string;
    };
}

interface DaySlot {
    date: string;
    label: string;
    dayStr: string;
    dayName: string;
    times: {
        value: string;
        label: string;
    }[];
}

const props = defineProps<{
    servicePrices: ServicePrice[];
    counselors?: CounselorProfile[];
    slots?: Record<number, DaySlot[]>;
}>();

const currentStep = ref(1);

const form = useForm({
    service_price_id: null as number | null,
    counselor_id: null as number | null,
    schedule_start: '',
    intake_form: '',
});

// Tab state for Step 2
const activeTab = ref<'jadwal' | 'semua'>('jadwal');

const initToday = new Date();
const y = initToday.getFullYear();
const m = String(initToday.getMonth() + 1).padStart(2, '0');
const d = String(initToday.getDate()).padStart(2, '0');
const todayStr = `${y}-${m}-${d}`;

const selectedDate = ref(todayStr);
const expandedCounselorId = ref<number | null>(null);

const services = computed(() => props.servicePrices);

const serviceTypes = ['chat', 'online', 'offline'] as const;
const activeServiceType = ref<'chat' | 'online' | 'offline'>('chat');

const serviceTypeLabels: Record<string, { label: string; icon: string; description: string }> = {
    chat: { label: 'Via Chat', icon: '💬', description: 'Sesi konseling melalui chat real-time' },
    online: { label: 'Online', icon: '💻', description: 'Sesi konseling via video call' },
    offline: { label: 'Offline', icon: '🏢', description: 'Sesi konseling tatap muka langsung' },
};

const filteredServices = computed(() => services.value.filter(s => s.service_type === activeServiceType.value));
const selectedService = computed(() => services.value.find(s => s.id === form.service_price_id));

// ── Counselors ──
const displayCounselors = computed(() => {
    return props.counselors || [];
});

const filteredCounselors = computed(() => {
    if (!selectedService.value) return [];
    return displayCounselors.value.filter(c => c.practitioner_type === selectedService.value?.practitioner_type);
});

// ── Slots & Dates Logic ──
const allAvailableDays = computed(() => {
    if (!props.slots) return [];
    const dayMap = new Map<string, Pick<DaySlot, 'date' | 'label' | 'dayStr' | 'dayName'>>();
    Object.values(props.slots).forEach(counselorDays => {
        counselorDays.forEach(day => {
            if (!dayMap.has(day.date)) {
                dayMap.set(day.date, { date: day.date, label: day.label, dayStr: day.dayStr, dayName: day.dayName });
            }
        });
    });
    return Array.from(dayMap.values()).sort((a, b) => a.date.localeCompare(b.date));
});

const windowStartDate = ref(todayStr);
const initEnd = new Date(initToday);
initEnd.setDate(initEnd.getDate() + 13);
const windowEndDate = ref(initEnd.toISOString().split('T')[0]);

const visibleDaysRange = computed(() => {
    if (!windowStartDate.value || !windowEndDate.value) return [];
    
    const days = [];
    const start = new Date(windowStartDate.value);
    const end = new Date(windowEndDate.value);
    
    const maxDays = 60;
    let current = new Date(start);
    let count = 0;
    
    while(current <= end && count < maxDays) {
        const cy = current.getFullYear();
        const cm = String(current.getMonth() + 1).padStart(2, '0');
        const cday = String(current.getDate()).padStart(2, '0');
        const dStr = `${cy}-${cm}-${cday}`;
        
        const dayName = new Intl.DateTimeFormat('id-ID', { weekday: 'long' }).format(current);
        const mon = new Intl.DateTimeFormat('id-ID', { month: 'short' }).format(current);
        
        days.push({
            date: dStr,
            dayStr: `${current.getDate()} ${mon}`,
            dayName: dayName
        });
        
        current.setDate(current.getDate() + 1);
        count++;
    }
    return days;
});

const dateRangeStr = computed(() => {
    if (visibleDaysRange.value.length === 0) return 'Belum Tersedia';
    const first = visibleDaysRange.value[0];
    const last = visibleDaysRange.value[visibleDaysRange.value.length - 1];
    if (first.date === last.date) return first.dayStr;
    return `${first.dayStr} - ${last.dayStr}`;
});

// Custom Date Range Picker Logic
const isDatePickerOpen = ref(false);
const pickerMonth = ref(new Date().getMonth());
const pickerYear = ref(new Date().getFullYear());
const tempRangeStart = ref('');
const tempRangeEnd = ref('');

const openPicker = () => {
    tempRangeStart.value = windowStartDate.value;
    tempRangeEnd.value = windowEndDate.value;
    const targetDate = new Date(windowStartDate.value);
    if (!isNaN(targetDate.getTime())) {
        pickerMonth.value = targetDate.getMonth();
        pickerYear.value = targetDate.getFullYear();
    }
    isDatePickerOpen.value = true;
};

const cancelPicker = () => {
    isDatePickerOpen.value = false;
};

const applyPicker = () => {
    if (tempRangeStart.value) {
        windowStartDate.value = tempRangeStart.value;
        windowEndDate.value = tempRangeEnd.value || tempRangeStart.value; 
        selectedDate.value = tempRangeStart.value; // Auto-select the first date in the new range
    }
    isDatePickerOpen.value = false;
};

const nextMonth = () => {
    if (pickerMonth.value === 11) {
        pickerMonth.value = 0;
        pickerYear.value++;
    } else pickerMonth.value++;
};

const prevMonth = () => {
    if (pickerMonth.value === 0) {
        pickerMonth.value = 11;
        pickerYear.value--;
    } else pickerMonth.value--;
};

const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
const dayNamesShort = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];

const pickerCalendarDays = computed(() => {
    const year = pickerYear.value;
    const month = pickerMonth.value;
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();
    
    const days = [];
    for(let i = firstDay - 1; i >= 0; i--) {
        days.push({ day: daysInPrevMonth - i, isCurrentMonth: false, dateStr: '' });
    }
    for(let i = 1; i <= daysInMonth; i++) {
        const dStr = `${year}-${String(month+1).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
        days.push({ day: i, isCurrentMonth: true, dateStr: dStr });
    }
    const remaining = 42 - days.length;
    for(let i = 1; i <= remaining; i++) {
        days.push({ day: i, isCurrentMonth: false, dateStr: '' });
    }
    return days;
});

const isDateAvailable = (dateStr: string) => {
    return allAvailableDays.value.some(d => d.date === dateStr);
};

const selectTempDate = (dateStr: string) => {
    if (!tempRangeStart.value || (tempRangeStart.value && tempRangeEnd.value)) {
        tempRangeStart.value = dateStr;
        tempRangeEnd.value = '';
    } else {
        const sTime = new Date(tempRangeStart.value).getTime();
        const eTime = new Date(dateStr).getTime();
        if (eTime < sTime) {
            tempRangeStart.value = dateStr;
        } else {
            tempRangeEnd.value = dateStr;
        }
    }
};

const isDateInRange = (dateStr: string) => {
    if (!dateStr || !tempRangeStart.value || !tempRangeEnd.value) return false;
    const dTime = new Date(dateStr).getTime();
    const sTime = new Date(tempRangeStart.value).getTime();
    const eTime = new Date(tempRangeEnd.value).getTime();
    return dTime > sTime && dTime < eTime;
};

const isRangeStart = (dateStr: string) => !!dateStr && dateStr === tempRangeStart.value;
const isRangeEnd = (dateStr: string) => !!dateStr && dateStr === tempRangeEnd.value;

const counselorsOnSelectedDate = computed(() => {
    if (!selectedDate.value || !props.slots) {
        return [];
    }
    
    return filteredCounselors.value.filter(c => {
        const cSlots = props.slots?.[c.user.id];
        return cSlots?.some(d => d.date === selectedDate.value);
    });
});

const getCounselorTimesList = (counselorId: number) => {
    if (!props.slots || !selectedDate.value) return [];
    const cSlots = props.slots[counselorId];
    if (!cSlots) return [];
    
    const day = cSlots.find(d => d.date === selectedDate.value);
    return day?.times ?? [];
};

// ── Actions ──
const selectService = (serviceId: number) => {
    form.service_price_id = serviceId;
    form.counselor_id = null;
    form.schedule_start = '';
    selectedDate.value = todayStr;
    expandedCounselorId.value = null;

    if (!props.counselors) {
        router.reload({ only: ['counselors'] });
    }

    router.reload({
        only: ['slots'],
        data: {
            service_price_id: serviceId,
        },
    });

    currentStep.value = 2;
};

const selectTimeAndProceed = (counselorId: number, timeValue: string) => {
    form.counselor_id = counselorId;
    form.schedule_start = timeValue;
    currentStep.value = 3;
};

const proceedToPayment = () => {
    if (!form.intake_form.trim()) return;
    currentStep.value = 4;
};

const isSubmitting = ref(false);

const submitBooking = () => {
    isSubmitting.value = true;
    form.post(route('client.bookings.store'), {
        preserveScroll: true,
        onError: () => {
            isSubmitting.value = false;
        }
    });
};

const formatSelectedTimeInfo = computed(() => {
    if (!form.schedule_start || !form.counselor_id || !props.slots) return form.schedule_start;
    const cSlots = props.slots[form.counselor_id];
    if (!cSlots) return form.schedule_start;

    for (const d of cSlots) {
        const timeObj = d.times.find(t => t.value === form.schedule_start);
        if (timeObj) {
            return `${d.dayName}, ${d.dayStr} — ${timeObj.label}`;
        }
    }
    return form.schedule_start;
});

const getSelectedCounselor = computed(() => {
    if (!form.counselor_id) return null;
    return displayCounselors.value.find(c => c.user.id === form.counselor_id) || null;
});

const goBack = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const toggleCounselorExpand = (id: number) => {
    if (expandedCounselorId.value === id) {
        expandedCounselorId.value = null;
    } else {
        expandedCounselorId.value = id;
    }
};

const formatPrice = (price: number) => `Rp${new Intl.NumberFormat('id-ID').format(price)}`;

const copyToClipboard = (text: string) => {
    navigator.clipboard.writeText(text);
};

const counselorPhotoUrl = (c: CounselorProfile) => {
    return getCounselorPhotoUrl(c.photo_url, c.photo_path, c.user.name);
};

const stepLabels = ['Layanan', 'Jadwal', 'Keluhan', 'Pembayaran'];

// Check URL Params for pre-filling
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const servicePriceId = urlParams.get('service_price_id');
    const counselorId = urlParams.get('counselor_id');
    const schedule = urlParams.get('schedule');

    if (servicePriceId && counselorId && schedule) {
        form.service_price_id = parseInt(servicePriceId, 10);
        form.counselor_id = parseInt(counselorId, 10);
        form.schedule_start = schedule;
        
        const matchingService = services.value.find(s => s.id === form.service_price_id);
        if(matchingService) activeServiceType.value = matchingService.service_type;

        // Always fetch the lazy-loaded counselors and slots, then advance step
        router.reload({
            only: ['counselors', 'slots'],
            data: {
                service_price_id: form.service_price_id,
            },
            onSuccess: () => {
                // Move to step 3 only after data is loaded
                currentStep.value = 3;
            },
        });
    }

    // Navigation guards to prevent accidental exit
    removeRouterListener = router.on('before', (event) => {
        if (isLeavingConfirmed.value) return;

        // Cek apakah tujuan URL adalah path yang sama (seperti saat router.reload)
        const isSamePage = event.detail.visit.url.pathname === window.location.pathname;
        
        // Munculkan konfirmasi hanya jika form belum disubmit, beda halaman, dan sudah melewati step 1
        if (!isSubmitting.value && !isSamePage && currentStep.value > 1) {
            event.preventDefault();
            pendingVisitUrl.value = event.detail.visit.url.href;
            showLeaveModal.value = true;
        }
    });

    window.addEventListener('beforeunload', handleBeforeUnload);
});

let removeRouterListener: (() => void) | null = null;
const showLeaveModal = ref(false);
const pendingVisitUrl = ref('');
const isLeavingConfirmed = ref(false);

const confirmLeave = () => {
    showLeaveModal.value = false;
    isLeavingConfirmed.value = true;
    if (pendingVisitUrl.value) {
        router.visit(pendingVisitUrl.value);
    }
};

const cancelLeave = () => {
    showLeaveModal.value = false;
    pendingVisitUrl.value = '';
};

const handleBeforeUnload = (e: BeforeUnloadEvent) => {
    if (!isSubmitting.value && currentStep.value > 1) {
        e.preventDefault();
        e.returnValue = '';
    }
};

onBeforeUnmount(() => {
    if (removeRouterListener) removeRouterListener();
    window.removeEventListener('beforeunload', handleBeforeUnload);
});
</script>

<template>
    <Head title="Booking Baru" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Booking Sesi</h2>
        </template>

        <div class="bg-gradient-to-b from-primary-50/20 to-transparent min-h-[80vh]">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-8">

                <!-- Header -->
                <div class="text-center mb-10">
                    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-3">Mulai Perjalanan Konselingmu</h1>
                    <p class="text-lg text-slate-500 max-w-2xl mx-auto">Pesan sesi konseling dengan psikolog atau konselor pilihanmu.</p>
                </div>

                <!-- Stepper -->
                <div class="mb-10 max-w-2xl mx-auto">
                    <div class="flex items-center justify-between">
                        <template v-for="(label, i) in stepLabels" :key="i">
                            <div class="flex flex-col items-center relative z-10 w-24">
                                <div
                                    class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm border-2 transition-all duration-300 bg-white"
                                    :class="[
                                        currentStep > i + 1 ? 'border-primary text-primary' : 
                                        currentStep === i + 1 ? 'border-primary text-primary shadow-md shadow-primary/20 bg-primary/5' : 'border-slate-200 text-slate-400'
                                    ]"
                                >
                                    <CheckCircle v-if="currentStep > i + 1" class="w-5 h-5 text-primary" />
                                    <span v-else>{{ i + 1 }}</span>
                                </div>
                                <span class="text-[11px] sm:text-xs font-bold mt-2 text-center" :class="currentStep >= i + 1 ? 'text-primary' : 'text-slate-400'">{{ label }}</span>
                            </div>
                            <div v-if="i < stepLabels.length - 1" class="flex-1 h-0.5 mx-0 -mt-6 transition-colors duration-300" :class="currentStep > i + 1 ? 'bg-primary' : 'bg-slate-200'"></div>
                        </template>
                    </div>
                </div>

                <!-- Step 1: Pilih Layanan -->
                <div v-if="currentStep === 1" class="animate-fade-in">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-slate-900 mb-2">Pilih Jenis Layanan</h2>
                        <p class="text-slate-500">Tentukan metode konseling yang paling nyaman untukmu</p>
                    </div>

                    <!-- Service Type Tabs -->
                    <div class="flex justify-center gap-2 mb-8">
                        <button
                            v-for="type in serviceTypes"
                            :key="type"
                            @click="activeServiceType = type"
                            class="flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold transition-all duration-200"
                            :class="activeServiceType === type
                                ? 'bg-primary text-white shadow-md shadow-primary/25'
                                : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'"
                        >
                            <span>{{ serviceTypeLabels[type].icon }}</span>
                            {{ serviceTypeLabels[type].label }}
                        </button>
                    </div>

                    <p class="text-center text-sm text-slate-400 mb-6">{{ serviceTypeLabels[activeServiceType].description }}</p>

                    <!-- Service Cards -->
                    <div :class="['grid grid-cols-1 gap-6 mx-auto', filteredServices.length === 1 ? 'max-w-md' : (filteredServices.length === 2 ? 'max-w-3xl lg:grid-cols-2' : 'max-w-5xl lg:grid-cols-2 xl:grid-cols-3')]">
                        <div
                            v-for="service in filteredServices"
                            :key="service.id"
                            @click="selectService(service.id)"
                            class="group relative cursor-pointer overflow-hidden rounded-[2rem] bg-white p-8 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/10 border border-slate-100 flex flex-col h-full"
                        >
                            <div class="absolute -right-8 -top-8 h-32 w-32 rounded-bl-[4rem] transition-all duration-500 group-hover:scale-110" 
                                 :class="service.practitioner_type === 'psychologist' ? 'bg-primary-50/50' : 'bg-blue-50/50'"></div>
                            
                            <div class="relative z-10 flex h-full flex-col">
                                <div class="mb-6 flex justify-start">
                                    <span class="inline-flex items-center rounded-full px-4 py-1.5 text-xs font-bold uppercase tracking-wider"
                                        :class="service.practitioner_type === 'psychologist' ? 'bg-primary-100 text-primary-700' : 'bg-blue-100 text-blue-700'"
                                    >
                                        {{ service.practitioner_type === 'psychologist' ? 'Psikolog Klinis' : 'Konselor Praktisi' }}
                                    </span>
                                </div>

                                <h3 class="mb-2 text-xl font-bold text-slate-900 border-b border-slate-100 pb-4">
                                     Paket Sesi {{ service.duration_minutes }} Menit
                                </h3>

                                <div class="mb-6 mt-4 space-y-3">
                                    <div class="flex items-start gap-3 text-sm text-slate-600 font-medium">
                                        <Clock class="h-5 w-5 shrink-0 text-slate-400" />
                                        <span>Durasi konsultasi {{ service.duration_minutes }} menit</span>
                                    </div>
                                    <div class="flex items-start gap-3 text-sm text-slate-600 font-medium">
                                        <CheckCircle class="h-5 w-5 shrink-0" :class="service.practitioner_type === 'psychologist' ? 'text-primary' : 'text-blue-500'" />
                                        <span>{{ serviceTypeLabels[activeServiceType].description }}</span>
                                    </div>
                                    <div class="flex items-start gap-3 text-sm text-slate-600 font-medium">
                                        <CheckCircle class="h-5 w-5 shrink-0" :class="service.practitioner_type === 'psychologist' ? 'text-primary' : 'text-blue-500'" />
                                        <span>{{ service.practitioner_type === 'psychologist' ? 'Ditangani oleh Psikolog Berlisensi / Bersertifikat SIPP' : 'Harga lebih terjangkau dengan Praktisi Terlatih' }}</span>
                                    </div>
                                </div>

                                <div class="mt-auto pt-6 border-t border-slate-50">
                                    <div class="mb-6 flex items-baseline gap-1">
                                        <span class="text-3xl font-black text-slate-900">{{ formatPrice(service.price) }}</span>
                                    </div>
                                    
                                    <div class="w-full rounded-xl border-2 border-primary/20 bg-white py-3.5 text-center text-sm font-bold text-primary transition-all group-hover:bg-primary group-hover:text-white group-hover:border-primary shadow-sm hover:shadow-md">
                                        Pilih Paket Ini
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Pilih Jadwal & Konselor -->
                <div v-if="currentStep === 2" class="animate-fade-in max-w-5xl mx-auto">
                    <div class="flex justify-between items-center mb-8">
                        <button @click="goBack" class="flex items-center gap-1 text-sm bg-white border border-slate-200 px-4 py-2 rounded-xl text-slate-600 font-medium hover:bg-slate-50 transition-colors">
                            <ArrowLeft class="w-4 h-4" /> Kembali
                        </button>
                        
                        <!-- Toggle Tabs -->
                        <div class="inline-flex bg-white border border-slate-200 p-1 rounded-xl shadow-sm">
                            <button
                                @click="activeTab = 'jadwal'"
                                class="px-5 py-2 rounded-lg text-sm font-bold transition-all"
                                :class="activeTab === 'jadwal' ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            >
                                Jadwal {{ selectedService?.practitioner_type === 'psychologist' ? 'Psikolog' : 'Konselor' }}
                            </button>
                            <button
                                @click="activeTab = 'semua'"
                                class="px-5 py-2 rounded-lg text-sm font-bold transition-all"
                                :class="activeTab === 'semua' ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            >
                                Semua Profil
                            </button>
                        </div>
                        <div class="w-24"></div> <!-- Spacer for balancing -->
                    </div>

                    <div v-if="!slots" class="py-24 text-center text-slate-500">
                        <div class="animate-spin w-8 h-8 border-4 border-primary border-t-transparent rounded-full mx-auto mb-4"></div>
                        Memuat data jadwal konselor...
                    </div>

                    <!-- Tab 1: Jadwal View -->
                    <div v-else-if="activeTab === 'jadwal'" class="animate-fade-in">
                        
                        <!-- Date Pill Scroller -->
                        <div class="mb-8 relative flex flex-col items-start bg-white p-4 rounded-3xl border border-slate-100 shadow-[0_4px_24px_rgba(0,0,0,0.02)]">
                            <div class="flex gap-2.5 overflow-x-auto pb-2 hide-scrollbar snap-x scroll-smooth items-stretch w-full rounded-2xl">
                                <!-- Date Range Indicator (Pilih Tanggal)  -->
                                <div class="relative flex-shrink-0 snap-start">
                                    <button @click="openPicker" class="flex items-center justify-center pl-4 pr-5 py-2.5 rounded-2xl bg-primary text-white shadow-sm hover:bg-primary-700 transition-colors h-full w-full">
                                        <Calendar class="w-6 h-6 mr-3 opacity-90" />
                                        <div class="text-left">
                                            <span class="block text-[10px] uppercase font-bold text-white/80 leading-tight">Pilih Tanggal</span>
                                            <span class="block text-sm font-bold whitespace-nowrap">{{ dateRangeStr }}</span>
                                        </div>
                                    </button>
                                </div>



                                <!-- Specific Date Pills -->
                                <button
                                    v-for="day in visibleDaysRange"
                                    :key="day.date"
                                    @click="selectedDate = day.date"
                                    class="flex-shrink-0 flex flex-col items-center justify-center min-w-[90px] py-3 px-3 rounded-xl border-2 transition-all relative snap-start"
                                    :class="selectedDate === day.date ? 'bg-primary-50 text-primary border-primary shadow-sm' : 'bg-white text-slate-500 border-slate-100 hover:bg-slate-50'"
                                >
                                    <span v-if="isDateAvailable(day.date)" class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                    <span class="text-xs font-semibold mb-0.5" :class="selectedDate === day.date ? 'text-primary' : 'text-slate-400'">{{ day.dayName.slice(0,3) }}</span>
                                    <span class="text-base font-bold whitespace-nowrap" :class="selectedDate === day.date ? 'text-primary-800' : 'text-slate-800'">{{ day.dayStr }}</span>
                                </button>
                            </div>

                            <!-- Popover Picker -->
                            <div v-if="isDatePickerOpen" class="w-full max-w-[320px] bg-white rounded-2xl shadow-xl border border-slate-200 p-4 z-20 mt-2 absolute top-[100%] left-4 animate-fade-in-up">
                                <div class="absolute -top-[10px] left-10 w-0 h-0 border-l-[10px] border-l-transparent border-r-[10px] border-r-transparent border-b-[10px] border-b-slate-200"></div>
                                <div class="absolute -top-[9px] left-10 w-0 h-0 border-l-[10px] border-l-transparent border-r-[10px] border-r-transparent border-b-[10px] border-b-white z-10"></div>
                                
                                <div class="flex items-center justify-between mb-4">
                                    <button type="button" @click.stop="prevMonth" class="p-1 rounded hover:bg-slate-100"><ChevronLeft class="w-4 h-4 text-slate-600" /></button>
                                    <span class="font-bold text-sm text-slate-800">{{ monthNames[pickerMonth] }} {{ pickerYear }}</span>
                                    <button type="button" @click.stop="nextMonth" class="p-1 rounded hover:bg-slate-100"><ChevronRight class="w-4 h-4 text-slate-600" /></button>
                                </div>
                                <div class="grid grid-cols-7 mb-2"><div v-for="day in dayNamesShort" :key="day" class="text-center text-[10px] font-bold text-slate-400">{{ day }}</div></div>
                                <div class="grid grid-cols-7 gap-y-1">
                                    <div
                                        v-for="(day, idx) in pickerCalendarDays" :key="idx"
                                        class="h-8 flex items-center justify-center rounded-full text-xs font-medium transition"
                                        :class="day.isCurrentMonth ? (day.dateStr < todayStr ? 'text-slate-300' : (isDateInRange(day.dateStr) ? 'bg-primary text-white cursor-pointer' : 'text-slate-800 hover:bg-slate-100 cursor-pointer')) : 'text-slate-300'"
                                        @click.stop="day.isCurrentMonth && day.dateStr >= todayStr && selectTempDate(day.dateStr)"
                                    >
                                        {{ day.day }}
                                    </div>
                                </div>
                                <div class="mt-4 flex gap-2 justify-end">
                                    <button @click="cancelPicker" class="px-3 py-1.5 text-xs text-slate-500">Batal</button>
                                    <button @click="applyPicker" class="px-3 py-1.5 text-xs text-white bg-primary rounded-lg">Simpan</button>
                                </div>
                            </div>
                        </div>

                        <!-- Counselors List for selected date -->
                        <div class="space-y-4">
                            <div v-if="counselorsOnSelectedDate.length === 0" class="text-center py-12 bg-white rounded-[2rem] border border-slate-100">
                                <Calendar class="w-10 h-10 text-slate-300 mx-auto mb-3" />
                                <p class="text-slate-500 font-medium">Tidak ada jadwal tersedia pada hari ini.</p>
                            </div>

                            <div v-for="counselor in counselorsOnSelectedDate" :key="counselor.id" class="bg-white rounded-[2rem] border border-slate-100 p-6 sm:p-8 hover:shadow-lg hover:shadow-slate-200/50 transition-all">
                                <div class="flex flex-col lg:flex-row gap-8">
                                    <div class="flex gap-4 lg:w-[35%] shrink-0 items-start">
                                        <div class="w-20 h-20 rounded-full overflow-hidden shrink-0 border border-slate-100 bg-slate-50">
                                            <img :src="counselorPhotoUrl(counselor)" class="w-full h-full object-cover">
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-bold text-primary bg-primary-50 px-2 py-0.5 rounded-full inline-block mb-2">
                                                {{ counselor.practitioner_type === 'psychologist' ? 'Psikolog Klinis' : 'Konselor Praktisi' }}
                                            </span>
                                            <h3 class="font-bold text-lg text-slate-900 leading-tight mb-1">{{ counselor.user.name }}, {{ counselor.full_title }}</h3>
                                        </div>
                                    </div>
                                    <div class="lg:w-[65%] border-t lg:border-t-0 lg:border-l border-slate-100 pt-6 lg:pt-0 lg:pl-8">
                                        <div class="flex justify-between items-center mb-3">
                                            <p class="text-xs font-bold text-slate-800 uppercase tracking-widest">Waktu Konseling <span v-if="selectedDate">({{ selectedDate }})</span></p>
                                        </div>
                                        <div class="flex flex-wrap gap-2.5">
                                            <button
                                                v-for="time in getCounselorTimesList(counselor.user.id)" :key="time.value"
                                                @click="selectTimeAndProceed(counselor.user.id, time.value)"
                                                class="px-4 py-2 bg-primary-50/50 hover:bg-primary hover:text-white text-primary border border-primary-100 hover:border-primary rounded-xl text-sm font-bold transition-all shadow-sm"
                                            >
                                                {{ time.label }}
                                            </button>
                                        </div>
                                        <p v-if="!selectedDate && getCounselorTimesList(counselor.user.id).length > 0" class="text-xs text-slate-400 mt-3">* 10 jadwal terdekat.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Tab 2: Semua Psikolog View -->
                    <div v-else-if="activeTab === 'semua'" class="animate-fade-in space-y-4">
                        <div v-for="counselor in filteredCounselors" :key="counselor.id" class="bg-white rounded-[2rem] border border-slate-100 p-6 sm:p-8 hover:shadow-lg transition-all">
                            <div class="flex flex-col lg:flex-row gap-6 justify-between items-start lg:items-center">
                                <div class="flex items-center gap-5">
                                    <div class="w-16 h-16 rounded-full overflow-hidden shrink-0 border border-slate-100 bg-slate-50">
                                        <img :src="counselorPhotoUrl(counselor)" class="w-full h-full object-cover">
                                    </div>
                                    <div>
                                        <div class="flex gap-2 mb-1">
                                            <span class="text-[10px] font-bold text-primary bg-primary-50 px-2 py-0.5 rounded-full inline-flex">
                                                {{ counselor.practitioner_type === 'psychologist' ? 'Psikolog Klinis' : 'Konselor Praktisi' }}
                                            </span>
                                            <span v-if="(slots[counselor.user.id] || []).length > 0" class="text-[10px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full border border-green-100">
                                                Jadwal Tersedia
                                            </span>
                                        </div>
                                        <h3 class="font-bold text-lg text-slate-900">{{ counselor.user.name }}, {{ counselor.full_title }}</h3>
                                    </div>
                                </div>
                                <button
                                    @click="toggleCounselorExpand(counselor.user.id)"
                                    class="w-full lg:w-auto px-6 py-2.5 bg-slate-50 border border-slate-200 hover:bg-slate-100 hover:border-slate-300 text-slate-700 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2"
                                >
                                    Lihat Jadwal <ChevronDown class="w-4 h-4 transition-transform" :class="expandedCounselorId === counselor.user.id ? 'rotate-180' : ''" />
                                </button>
                            </div>

                            <div v-if="expandedCounselorId === counselor.user.id" class="mt-6 pt-6 border-t border-slate-100 animate-fade-in">
                                <div v-if="(slots[counselor.user.id] || []).length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div v-for="day in slots[counselor.user.id]" :key="day.date" class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                                        <p class="text-xs font-bold uppercase text-slate-500 mb-3">{{ day.label }}</p>
                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                v-for="time in day.times" :key="time.value"
                                                @click="selectTimeAndProceed(counselor.user.id, time.value)"
                                                class="px-3 py-1.5 bg-white hover:bg-primary hover:text-white text-primary border border-slate-200 hover:border-primary rounded-lg text-sm font-bold shadow-sm transition-colors"
                                            >
                                                {{ time.label }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-6">
                                    <p class="text-slate-500 text-sm">Tidak ada jadwal dalam 14 hari ke depan.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Intake Form -->
                <div v-if="currentStep === 3" class="animate-fade-in max-w-3xl mx-auto">
                    <button @click="goBack" class="flex items-center gap-1 text-sm bg-white border border-slate-200 px-4 py-2 rounded-xl text-slate-600 font-medium hover:bg-slate-50 transition-colors mb-6">
                        <ArrowLeft class="w-4 h-4" /> Kembali
                    </button>

                    <div class="bg-white rounded-[2rem] border border-slate-100 shadow-[0_4px_24px_rgba(0,0,0,0.02)] p-6 sm:p-10 relative overflow-hidden">
                        <div class="absolute -right-16 -top-16 w-48 h-48 bg-primary-50 rounded-full opacity-50 pointer-events-none"></div>
                        <div class="relative z-10">
                            <div class="flex items-center gap-4 mb-6">
                                <div class="w-12 h-12 rounded-2xl bg-primary-50 text-primary flex items-center justify-center shrink-0">
                                    <FileText class="w-6 h-6" />
                                </div>
                                <div>
                                    <h2 class="text-2xl font-bold text-slate-900">Keluhan & Ekspektasi</h2>
                                    <p class="text-sm text-slate-500">Bantu konselor memahami kondisi Anda sebelum sesi dimulai.</p>
                                </div>
                            </div>
                            
                            <div class="p-4 bg-yellow-50 border border-yellow-100 rounded-2xl mb-6">
                                <p class="text-sm text-yellow-800 font-medium flex items-start gap-2">
                                    <CheckCircle class="w-5 h-5 text-yellow-500 shrink-0" />
                                    Data Anda sangat rahasia dan hanya dapat diakses oleh konselor yang Anda pilih.
                                </p>
                            </div>

                            <form @submit.prevent="proceedToPayment" class="space-y-6">
                                <div>
                                    <label for="intake_form" class="block text-sm font-bold text-slate-700 mb-2">Ceritakan apa yang sedang Anda rasakan atau alami akhir-akhir ini</label>
                                    <textarea
                                        id="intake_form"
                                        v-model="form.intake_form"
                                        rows="6"
                                        class="block w-full rounded-xl border-slate-200 bg-slate-50 focus:border-primary focus:ring focus:ring-primary/20 transition-colors text-sm text-slate-900"
                                        placeholder="Contoh: Saya merasa cemas dan sulit tidur beberapa hari terakhir karena masalah pekerjaan..."
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-200': form.errors.intake_form }"
                                    ></textarea>
                                    
                                    <div class="flex justify-between items-center mt-2">
                                        <p class="text-xs text-red-500" v-if="form.errors.intake_form">{{ form.errors.intake_form }}</p>
                                        <p v-else class="text-xs text-slate-400">Jelaskan dengan jujur apa adanya.</p>
                                        <p class="text-xs font-medium text-slate-500">
                                            {{ form.intake_form.length }} Karakter
                                        </p>
                                    </div>
                                </div>

                                <div class="flex justify-end pt-4 border-t border-slate-100">
                                    <button 
                                        type="submit" 
                                        class="px-8 py-3 bg-primary hover:bg-primary-700 text-white rounded-xl text-sm font-bold transition-all shadow-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                                        :disabled="form.processing || !form.intake_form.trim()"
                                    >
                                        Lanjut ke Pembayaran <ArrowRight class="w-4 h-4" />
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Ringkasan Sesi -->
                <div v-if="currentStep === 4" class="animate-fade-in max-w-3xl mx-auto">
                    <button @click="goBack" class="flex items-center gap-1 text-sm bg-white border border-slate-200 px-4 py-2 rounded-xl text-slate-600 font-medium hover:bg-slate-50 transition-colors mb-6">
                        <ArrowLeft class="w-4 h-4" /> Kembali
                    </button>

                    <div class="text-center mb-8">
                        <h2 class="text-2xl lg:text-3xl font-bold text-slate-900 mb-2">Ringkasan Pemesanan</h2>
                        <p class="text-slate-500">Periksa kembali detail sesi konseling Anda sebelum konfirmasi pembayaran.</p>
                    </div>

                    <!-- Summary Card -->
                    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-xl shadow-primary/5 p-6 sm:p-10 mb-8 relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-primary-50 rounded-bl-[4rem] -z-0"></div>
                        
                        <div class="relative z-10 space-y-8">
                            <!-- Counselor Summary -->
                            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6 text-center sm:text-left">
                                <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-white shadow-md bg-slate-50 shrink-0">
                                    <img v-if="getSelectedCounselor" :src="counselorPhotoUrl(getSelectedCounselor)" :alt="getSelectedCounselor.user.name" class="w-full h-full object-cover">
                                </div>
                                <div class="pt-2">
                                    <h3 class="font-bold text-xl text-slate-900 mb-1">{{ getSelectedCounselor?.user.name }}, {{ getSelectedCounselor?.full_title }}</h3>
                                    <span class="text-[10px] font-bold text-primary bg-primary-50 px-3 py-1 rounded-full uppercase tracking-wider inline-block">
                                        {{ getSelectedCounselor?.practitioner_type === 'psychologist' ? 'Psikolog Klinis' : 'Konselor Praktisi' }}
                                    </span>
                                </div>
                            </div>

                            <hr class="border-slate-100">

                            <!-- Details Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Layanan</p>
                                    <div class="flex items-center gap-3 text-slate-800 font-semibold">
                                        <span class="text-xl -mt-1">{{ activeServiceType ? serviceTypeLabels[activeServiceType].icon : '' }}</span>
                                        Sesi Konseling {{ activeServiceType ? serviceTypeLabels[activeServiceType].label : '' }}
                                    </div>
                                </div>
                                
                                <div>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Durasi</p>
                                    <div class="flex items-center gap-3 text-slate-800 font-semibold">
                                        <Clock class="w-5 h-5 text-slate-400" />
                                        {{ selectedService?.duration_minutes }} Menit
                                    </div>
                                </div>

                                <div class="sm:col-span-2 bg-slate-50/50 p-5 rounded-2xl border border-slate-100">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Slot Jadwal Terpilih</p>
                                    <div class="flex items-center gap-3 text-slate-900 font-bold text-lg">
                                        <Calendar class="w-6 h-6 text-primary" />
                                        {{ formatSelectedTimeInfo }}
                                    </div>
                                </div>
                                
                                <div class="sm:col-span-2 bg-slate-50/50 p-5 rounded-2xl border border-slate-100">
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Keluhan (Intake)</p>
                                    <p class="text-sm text-slate-700 italic border-l-2 border-primary pl-3 whitespace-pre-wrap break-words">
                                        "{{ form.intake_form }}"
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Info -->
                    <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm p-6 sm:p-8 mb-8">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                <CreditCard class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900">Informasi Pembayaran</h3>
                                <p class="text-xs text-slate-500">Transfer ke rekening berikut setelah konfirmasi booking</p>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-blue-50 to-blue-100/50 rounded-2xl border border-blue-200/60 p-5 sm:p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="bg-white px-3 py-1.5 rounded-lg border border-blue-200 shadow-sm">
                                    <span class="text-sm font-extrabold text-blue-700 tracking-wide">BCA Digital</span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <p class="text-[10px] font-bold text-blue-400 uppercase tracking-widest mb-1">Nomor Rekening</p>
                                    <div class="flex items-center gap-3">
                                        <span class="text-2xl font-black text-slate-900 tracking-wider">005244626066</span>
                                        <button
                                            @click="copyToClipboard('005244626066')"
                                            class="p-2 rounded-lg bg-white border border-blue-200 hover:bg-blue-50 text-blue-600 transition-colors shadow-sm"
                                            title="Salin nomor rekening"
                                        >
                                            <Copy class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-blue-400 uppercase tracking-widest mb-1">Atas Nama</p>
                                    <p class="text-base font-bold text-slate-800">NADYA MUBARANIZ RACHMAPUTRI</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-100 rounded-xl">
                            <p class="text-xs text-yellow-800 font-medium flex items-start gap-2">
                                <CheckCircle class="w-4 h-4 text-yellow-500 shrink-0 mt-0.5" />
                                Pastikan transfer sesuai nominal yang tertera. Konfirmasi pembayaran akan diverifikasi oleh admin kami.
                            </p>
                        </div>
                    </div>

                    <!-- CTA Box -->
                    <div class="bg-primary/5 rounded-[2rem] border border-primary/20 p-6 sm:p-8 flex flex-col sm:flex-row items-center justify-between gap-6">
                        <div>
                            <p class="text-sm font-semibold text-primary mb-1">Total Transaksi</p>
                            <p class="text-3xl font-black text-slate-900">{{ formatPrice(selectedService?.price || 0) }}</p>
                        </div>
                        
                        <div class="w-full sm:w-auto text-center">
                            <button
                                @click="submitBooking"
                                :disabled="form.processing"
                                class="w-full sm:w-auto px-8 py-4 bg-primary hover:bg-primary-700 text-white rounded-xl text-lg font-bold transition-all shadow-lg shadow-slate-400 flex items-center justify-center gap-3 group disabled:opacity-50"
                            >
                                <span v-if="form.processing">Memproses...</span>
                                <span v-else class="flex items-center gap-2">Konfirmasi Booking <CheckCircle class="w-5 h-5" /></span>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>

    <!-- Custom Leave Modal (Modern & Glassmorphism) -->
    <div v-if="showLeaveModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="cancelLeave"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden animate-fade-in-up border border-slate-100">
            <!-- Decorative Background -->
            <div class="absolute -right-20 -top-20 w-40 h-40 bg-red-50 rounded-full opacity-70 pointer-events-none"></div>
            <div class="absolute -left-20 -bottom-20 w-40 h-40 bg-primary-50 rounded-full opacity-70 pointer-events-none"></div>
            
            <div class="relative p-6 sm:p-8 z-10 text-center">
                <!-- Icon -->
                <div class="w-20 h-20 mx-auto bg-red-50 text-red-500 rounded-full flex items-center justify-center mb-6 shadow-sm border-4 border-white">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                
                <!-- Content -->
                <h3 class="text-2xl font-black text-slate-900 mb-2">Tunggu Dulu!</h3>
                <p class="text-slate-500 text-sm mb-8 leading-relaxed">
                    Yakin ingin meninggalkan halaman ini? 
                    <strong class="text-slate-700">Proses pemesanan Anda belum selesai</strong> dan semua data yang sudah diisi akan hilang.
                </p>
                
                <!-- Actions -->
                <div class="flex flex-col-reverse sm:flex-row gap-3">
                    <button 
                        @click="confirmLeave" 
                        class="w-full px-5 py-3 rounded-xl font-bold text-slate-500 bg-slate-50 hover:bg-red-50 hover:text-red-600 transition-colors border border-slate-200 hover:border-red-100"
                    >
                        Ya, Tinggalkan
                    </button>
                    <button 
                        @click="cancelLeave" 
                        class="w-full px-5 py-3 rounded-xl font-bold text-white bg-primary hover:bg-primary-600 transition-colors shadow-md shadow-primary/25 border border-transparent"
                    >
                        Lanjutkan Pesanan
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.35s ease-out;
}
.animate-fade-in-up {
    animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
