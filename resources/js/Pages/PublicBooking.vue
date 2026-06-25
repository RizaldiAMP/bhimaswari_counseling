<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import {
    ArrowLeft,
    ArrowRight,
    Calendar,
    CheckCircle,
    Clock,
    CreditCard,
    Search,
    User,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Copy,
    Info
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
    canLogin?: boolean;
    canRegister?: boolean;
}>();

const currentStep = ref(1);
const selectedServiceId = ref<number | null>(null);
const selectedCounselorUserId = ref<number | null>(null);
const selectedTime = ref('');
const preSelectedCounselorId = ref<number | null>(null);

// Tab state for Step 2
const activeTab = ref<'jadwal' | 'semua'>('jadwal');

const initToday = new Date();
const y = initToday.getFullYear();
const m = String(initToday.getMonth() + 1).padStart(2, '0');
const d = String(initToday.getDate()).padStart(2, '0');
const todayStr = `${y}-${m}-${d}`;

const selectedDate = ref(todayStr);
const expandedCounselorId = ref<number | null>(null);

// ── Fallback services when DB is empty ──
const fallbackServices: ServicePrice[] = [
    { id: -1, service_type: 'chat', practitioner_type: 'psychologist', duration_minutes: 60, price: 100000, is_active: true },
    { id: -2, service_type: 'chat', practitioner_type: 'counselor', duration_minutes: 60, price: 50000, is_active: true },
    { id: -3, service_type: 'online', practitioner_type: 'psychologist', duration_minutes: 60, price: 200000, is_active: true },
    { id: -4, service_type: 'online', practitioner_type: 'psychologist', duration_minutes: 90, price: 265000, is_active: true },
    { id: -5, service_type: 'online', practitioner_type: 'counselor', duration_minutes: 60, price: 150000, is_active: true },
    { id: -6, service_type: 'offline', practitioner_type: 'psychologist', duration_minutes: 60, price: 250000, is_active: true },
    { id: -7, service_type: 'offline', practitioner_type: 'psychologist', duration_minutes: 90, price: 315000, is_active: true },
    { id: -8, service_type: 'offline', practitioner_type: 'counselor', duration_minutes: 60, price: 200000, is_active: true },
];

const services = computed(() => props.servicePrices.length > 0 ? props.servicePrices : fallbackServices);

const serviceTypes = ['chat', 'online', 'offline'] as const;
const activeServiceType = ref<'chat' | 'online' | 'offline'>('chat');

const serviceTypeLabels: Record<string, { label: string; icon: string; description: string }> = {
    chat: { label: 'Via Chat', icon: '💬', description: 'Sesi konseling melalui chat real-time' },
    online: { label: 'Online', icon: '💻', description: 'Sesi konseling via video call' },
    offline: { label: 'Offline', icon: '🏢', description: 'Sesi konseling tatap muka langsung' },
};

const filteredServices = computed(() => services.value.filter(s => s.service_type === activeServiceType.value));
const selectedService = computed(() => services.value.find(s => s.id === selectedServiceId.value));

// ── Counselors ──
const fallbackCounselors: CounselorProfile[] = [
    { id: 1, user_id: 1, practitioner_type: 'psychologist', full_title: 'S. Psi., Psikolog', photo_path: '/images/aisyah_tri_wardhani.webp', photo_url: '/images/aisyah_tri_wardhani.webp', specializations: ['Pengembangan Diri', 'Kecemasan'], bio: '', sipp_number: '20240320-2024-01-4250', user: { id: 1, name: 'Aisyah Tri Wardhani' } },
    { id: 2, user_id: 2, practitioner_type: 'psychologist', full_title: 'S. Psi., M. Psi., Psikolog', photo_path: '/images/bagas_alam.webp', photo_url: '/images/bagas_alam.webp', specializations: ['Manajemen Stres', 'Depresi'], bio: '', sipp_number: '20250110-2025-01-0100', user: { id: 2, name: 'Bagas Alam' } },
    { id: 3, user_id: 3, practitioner_type: 'psychologist', full_title: 'S. Psi., M. Psi., Psikolog', photo_path: '/images/ghazali_fauzia.webp', photo_url: '/images/ghazali_fauzia.webp', specializations: ['Trauma', 'Kecemasan'], bio: '', sipp_number: '20241005-2024-01-5400', user: { id: 3, name: 'Ghazali Fauzia' } },
    { id: 4, user_id: 4, practitioner_type: 'psychologist', full_title: 'S. Psi., Psikolog', photo_path: '/images/ghina_ciptadewi.webp', photo_url: '/images/ghina_ciptadewi.webp', specializations: ['Pengembangan Diri'], bio: '', sipp_number: '20240830-2024-01-5250', user: { id: 4, name: 'Ghina Ciptadewi' } },
    { id: 5, user_id: 5, practitioner_type: 'psychologist', full_title: 'S. Psi., M. Psi., Psikolog', photo_path: '/images/ifti_aisha.webp', photo_url: '/images/ifti_aisha.webp', specializations: ['Keluarga', 'Anak'], bio: '', sipp_number: '20241120-2024-01-5500', user: { id: 5, name: 'Ifti Aisha' } },
    { id: 6, user_id: 6, practitioner_type: 'psychologist', full_title: 'S. Psi., M. Psi., Psikolog', photo_path: '/images/joko_tri_hartanto.webp', photo_url: '/images/joko_tri_hartanto.webp', specializations: ['Karier', 'Manajemen Stres'], bio: '', sipp_number: '20241011-2025-01-0006', user: { id: 6, name: 'Joko Tri Hartanto' } },
    { id: 7, user_id: 7, practitioner_type: 'psychologist', full_title: 'S. Psi., Psikolog', photo_path: '/images/nadya_mubaraniz.webp', photo_url: '/images/nadya_mubaraniz.webp', specializations: ['Kecemasan', 'Keluarga'], bio: '', sipp_number: '20241011-2025-01-0007', user: { id: 7, name: 'Nadya Mubaraniz' } },
    { id: 8, user_id: 8, practitioner_type: 'psychologist', full_title: 'S. Psi., Psikolog', photo_path: '/images/nurul_nabila_annisa.webp', photo_url: '/images/nurul_nabila_annisa.webp', specializations: ['Klinis Dewasa'], bio: '', sipp_number: '20241011-2025-01-0008', user: { id: 8, name: 'Nurul Nabila Annisa' } },
    { id: 9, user_id: 9, practitioner_type: 'counselor', full_title: 'S. Psi.', photo_path: '/images/rizkie_alief_madani.webp', photo_url: '/images/rizkie_alief_madani.webp', specializations: ['Pendidikan', 'Remaja'], bio: '', sipp_number: '', user: { id: 9, name: 'Rizkie Alief Madani' } },
    { id: 10, user_id: 10, practitioner_type: 'psychologist', full_title: 'S. Psi., M. Psi., Psikolog', photo_path: '/images/shofura_hanifah.webp', photo_url: '/images/shofura_hanifah.webp', specializations: ['Pengembangan Diri'], bio: '', sipp_number: '20241011-2025-01-0010', user: { id: 10, name: 'Shofura Hanifah' } },
    { id: 11, user_id: 11, practitioner_type: 'psychologist', full_title: 'S. Psi., M. Psi., Psikolog', photo_path: '/images/trya_dara_ruidahasi.webp', photo_url: '/images/trya_dara_ruidahasi.webp', specializations: ['Tumbuh Kembang'], bio: '', sipp_number: '20241011-2025-01-0011', user: { id: 11, name: 'Trya Dara Ruidahasi' } },
    { id: 12, user_id: 12, practitioner_type: 'psychologist', full_title: 'S. Psi., Psikolog', photo_path: '/images/winda_kusuma_ayu.webp', photo_url: '/images/winda_kusuma_ayu.webp', specializations: ['Psikologi Anak'], bio: '', sipp_number: '20241011-2025-01-0012', user: { id: 12, name: 'Winda Kusuma Ayu' } },
];

const displayCounselors = computed(() => {
    if (props.counselors && props.counselors.length > 0) {
        return props.counselors;
    }
    return fallbackCounselors;
});

const preSelectedCounselor = computed(() => {
    if (!preSelectedCounselorId.value) return null;
    return displayCounselors.value.find(c => c.user.id === preSelectedCounselorId.value || c.user_id === preSelectedCounselorId.value) || null;
});

// Used for both "Jadwal Psikolog" and "Semua Psikolog" Tab -> Filters by selected service
const filteredCounselors = computed(() => {
    if (!selectedService.value) return [];
    const baseList = displayCounselors.value.filter(c => c.practitioner_type === selectedService.value?.practitioner_type);
    if (preSelectedCounselorId.value) {
        return baseList.filter(c => c.user.id === preSelectedCounselorId.value || c.user_id === preSelectedCounselorId.value);
    }
    return baseList;
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
initEnd.setDate(initEnd.getDate() + 13); // Default 14 days initially, but user can change it
const windowEndDate = ref(initEnd.toISOString().split('T')[0]);

const visibleDaysRange = computed(() => {
    if (!windowStartDate.value || !windowEndDate.value) return [];
    
    const days = [];
    const start = new Date(windowStartDate.value);
    const end = new Date(windowEndDate.value);
    
    const maxDays = 60; // Sensible max limit
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
        // If they only picked one date, end date is the same
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
    // prev month pads
    for(let i = firstDay - 1; i >= 0; i--) {
        days.push({ day: daysInPrevMonth - i, isCurrentMonth: false, dateStr: '' });
    }
    // current month
    for(let i = 1; i <= daysInMonth; i++) {
        const dStr = `${year}-${String(month+1).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
        days.push({ day: i, isCurrentMonth: true, dateStr: dStr });
    }
    // next month pads
    const remaining = 42 - days.length;
    for(let i = 1; i <= remaining; i++) {
        days.push({ day: i, isCurrentMonth: false, dateStr: '' });
    }
    return days;
});

const isDateAvailable = (dateStr: string) => {
    if (preSelectedCounselorId.value && props.slots) {
        const cSlots = props.slots[preSelectedCounselorId.value];
        return cSlots?.some(d => d.date === dateStr) ?? false;
    }
    return allAvailableDays.value.some(d => d.date === dateStr);
};

const selectTempDate = (dateStr: string) => {
    if (dateStr < todayStr) return; // Prevent selecting dates before today

    if (!tempRangeStart.value || (tempRangeStart.value && tempRangeEnd.value)) {
        // Start a new selection
        tempRangeStart.value = dateStr;
        tempRangeEnd.value = '';
    } else {
        // Evaluate as end date
        const sTime = new Date(tempRangeStart.value).getTime();
        const eTime = new Date(dateStr).getTime();
        if (eTime < sTime) {
            // Selected before start date, make it the new start date
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
    selectedServiceId.value = serviceId;
    selectedDate.value = todayStr;
    expandedCounselorId.value = null;

    router.reload({
        only: ['counselors', 'slots'],
        data: {
            service_price_id: serviceId,
        },
    });

    currentStep.value = 2;
};

const selectTimeAndProceed = (counselorId: number, timeValue: string) => {
    selectedCounselorUserId.value = counselorId;
    selectedTime.value = timeValue;
    currentStep.value = 3;
};

const goToBooking = () => {
    if (!selectedServiceId.value || !selectedCounselorUserId.value || !selectedTime.value) return;

    const bookingUrl = `/client/bookings/create?service_price_id=${selectedServiceId.value}&counselor_id=${selectedCounselorUserId.value}&schedule=${encodeURIComponent(selectedTime.value)}`;

    const authUser = (window as any).__page?.props?.auth?.user;
    if (authUser) {
        window.location.href = bookingUrl;
    } else {
        window.location.href = `/login?redirect=${encodeURIComponent(bookingUrl)}`;
    }
};

const formatSelectedTimeInfo = computed(() => {
    if (!selectedTime.value || !selectedCounselorUserId.value || !props.slots) return selectedTime.value;
    const cSlots = props.slots[selectedCounselorUserId.value];
    if (!cSlots) return selectedTime.value;

    for (const d of cSlots) {
        const timeObj = d.times.find(t => t.value === selectedTime.value);
        if (timeObj) {
            return `${d.dayName}, ${d.dayStr} — ${timeObj.label}`;
        }
    }
    return selectedTime.value;
});

const getSelectedCounselor = computed(() => {
    if (!selectedCounselorUserId.value) return null;
    return displayCounselors.value.find(c => c.user.id === selectedCounselorUserId.value) || null;
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

const stepLabels = ['Layanan', 'Jadwal', 'Pembayaran'];

// ── Handle booking from Team page via query params ──
onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const fromTeam = params.get('from_team');
    const servicePriceId = params.get('service_price_id');
    const counselorId = params.get('counselor_id');
    const scheduleStart = params.get('schedule_start');
    const skipTo = params.get('skip_to');

    if (fromTeam === '1' && servicePriceId && counselorId && scheduleStart) {
        const priceId = parseInt(servicePriceId);
        const cId = parseInt(counselorId);
        const service = services.value.find(s => s.id === priceId);
        
        if (service) {
            // Auto-fill all state
            activeServiceType.value = service.service_type;
            selectedServiceId.value = priceId;
            selectedCounselorUserId.value = cId;
            selectedTime.value = scheduleStart;
            
            // Jump directly to Step 3 (Confirmation)
            currentStep.value = 3;
            
            // Clean URL without reloading
            window.history.replaceState({}, '', window.location.pathname);
        }
    } else if (servicePriceId && skipTo === '2') {
        const priceId = parseInt(servicePriceId);
        const service = services.value.find(s => s.id === priceId);
        
        if (service) {
            activeServiceType.value = service.service_type;
            selectedServiceId.value = priceId;
            currentStep.value = 2;
            
            if (counselorId) {
                const cId = parseInt(counselorId);
                preSelectedCounselorId.value = cId;
                selectedCounselorUserId.value = cId;
                expandedCounselorId.value = cId;
            }

            router.reload({
                only: ['counselors', 'slots'],
                data: {
                    service_price_id: priceId,
                },
            });

            // Clean URL without reloading
            window.history.replaceState({}, '', window.location.pathname);
        }
    }
});
</script>

<template>
    <Head title="Pilih Layanan & Jadwal" />

    <GuestLayout>
        <div class="bg-gradient-to-b from-primary-50/50 to-white min-h-[80vh]">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-12">

                <!-- Header -->
                <div class="text-center mb-10">
                    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-3">Mulai Perjalanan Konselingmu</h1>
                    <p class="text-lg text-slate-500 max-w-2xl mx-auto">Pilih layanan, konselor, dan jadwal yang sesuai. Kamu bisa mengeksplorasi tanpa perlu login terlebih dahulu.</p>
                </div>

                <!-- Stepper -->
                <div class="mb-10 max-w-md mx-auto">
                    <div class="flex items-center justify-between">
                        <template v-for="(label, i) in stepLabels" :key="i">
                            <div class="flex flex-col items-center relative z-10 w-24">
                                <div
                                    class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm border-2 transition-all duration-300 bg-white"
                                    :class="[
                                        currentStep > i + 1 ? 'border-primary text-primary' : 
                                        currentStep === i + 1 ? 'border-primary text-primary shadow-md shadow-primary/20' : 'border-slate-200 text-slate-400'
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
                    <div :class="['grid grid-cols-1 gap-6 mx-auto', filteredServices.length === 1 ? 'max-w-md' : (filteredServices.length === 2 ? 'max-w-3xl sm:grid-cols-2' : 'max-w-5xl sm:grid-cols-2 lg:grid-cols-3')]">
                        <div
                            v-for="service in filteredServices"
                            :key="service.id"
                            @click="selectService(service.id)"
                            class="group relative cursor-pointer overflow-hidden rounded-[2rem] bg-white p-8 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/10 border border-slate-100"
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
                <div v-if="currentStep === 2" class="animate-fade-in">
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
                                Jadwal Psikolog
                            </button>
                            <button
                                @click="activeTab = 'semua'"
                                class="px-5 py-2 rounded-lg text-sm font-bold transition-all"
                                :class="activeTab === 'semua' ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            >
                                Semua Psikolog
                            </button>
                        </div>
                        <div class="w-24"></div> <!-- Spacer for balancing -->
                    </div>

                    <!-- Pre-selected Counselor Alert/Filter -->
                    <div v-if="preSelectedCounselor" class="mb-8 flex flex-col sm:flex-row items-center justify-between gap-4 rounded-3xl bg-primary/5 border border-primary/10 px-6 py-4 animate-in fade-in duration-300">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-full overflow-hidden border border-primary/20 bg-white shrink-0 shadow-sm">
                                <img :src="counselorPhotoUrl(preSelectedCounselor)" :alt="preSelectedCounselor.user.name" class="w-full h-full object-cover">
                            </div>
                            <div class="text-left">
                                <p class="text-xs font-bold text-primary uppercase tracking-wider mb-0.5">Konselor Pilihan Anda</p>
                                <h4 class="font-extrabold text-lg text-slate-900 leading-tight">{{ preSelectedCounselor.user.name }}, {{ preSelectedCounselor.full_title }}</h4>
                            </div>
                        </div>
                        <button 
                            type="button"
                            @click="preSelectedCounselorId = null" 
                            class="w-full sm:w-auto text-xs font-bold text-slate-600 hover:text-primary transition-all bg-white hover:bg-primary-50/50 px-5 py-3 rounded-2xl border border-slate-200 hover:border-primary/30 shadow-sm active:scale-95"
                        >
                            Tampilkan Semua Konselor
                        </button>
                    </div>

                    <div v-if="!slots" class="py-24 text-center text-slate-500">
                        <div class="animate-spin w-8 h-8 border-4 border-primary border-t-transparent rounded-full mx-auto mb-4"></div>
                        Memuat data jadwal konselor...
                    </div>

                    <!-- Tab 1: Jadwal View -->
                    <div v-else-if="activeTab === 'jadwal'" class="animate-fade-in">
                        
                        <!-- Filter Bar -->
                        <div class="flex gap-4 mb-6">
                            <!-- Optional filters could go here, currently matched to reference picture design -->
                        </div>

                        <!-- Date Pill Scroller -->
                        <!-- Date Pill Scroller -->
                        <div class="mb-8 relative flex flex-col items-start">
                            <div class="flex gap-2.5 overflow-x-auto pb-4 hide-scrollbar snap-x scroll-smooth items-stretch w-full">
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
                                    class="flex-shrink-0 flex flex-col items-center justify-center min-w-[90px] py-2 px-3 rounded-xl border-2 transition-all relative snap-start"
                                    :class="selectedDate === day.date ? 'bg-primary-50 text-primary border-primary ring-1 ring-primary/10 shadow-sm' : 'bg-white text-slate-500 border-slate-100 hover:bg-slate-50 hover:border-slate-300'"
                                >
                                    <!-- Indicator dot if slots are available -->
                                    <span v-if="isDateAvailable(day.date)" class="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                    
                                    <span class="text-sm font-bold mb-0.5 whitespace-nowrap" :class="selectedDate === day.date ? 'text-primary' : 'text-slate-800'">{{ day.dayStr }}</span>
                                    <span class="text-xs font-semibold">{{ day.dayName }}</span>
                                </button>
                            </div>

                            <!-- Popover Picker (moved below the dates row to match dropdown visual expectation) -->
                            <div v-if="isDatePickerOpen" class="w-full max-w-[320px] bg-white rounded-2xl shadow-md border border-slate-200 p-4 z-10 mb-4 animate-fade-in-up mt-1 relative">
                                <!-- Triangle pointer to simulate popup joining to the button above -->
                                <div class="absolute -top-3 left-10 w-0 h-0 border-l-[10px] border-l-transparent border-r-[10px] border-r-transparent border-b-[12px] border-b-slate-200"></div>
                                <div class="absolute -top-[11px] left-10 w-0 h-0 border-l-[10px] border-l-transparent border-r-[10px] border-r-transparent border-b-[12px] border-b-white z-10"></div>
                                
                                <div class="flex items-center justify-between mb-4">
                                    <button type="button" @click.stop="prevMonth" class="p-1 rounded hover:bg-slate-100"><ChevronLeft class="w-4 h-4 text-slate-600" /></button>
                                    <span class="font-bold text-sm text-slate-800">{{ monthNames[pickerMonth] }} {{ pickerYear }}</span>
                                    <button type="button" @click.stop="nextMonth" class="p-1 rounded hover:bg-slate-100"><ChevronRight class="w-4 h-4 text-slate-600" /></button>
                                </div>
                                
                                <!-- Days Header -->
                                <div class="grid grid-cols-7 mb-2 gap-1">
                                    <div v-for="dayName in dayNamesShort" :key="dayName" class="text-center text-[10px] font-bold text-slate-400">
                                        {{ dayName }}
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-7 gap-y-2 gap-x-0">
                                    <div
                                        v-for="(day, idx) in pickerCalendarDays" :key="idx"
                                        class="h-8 flex items-center justify-center rounded-full text-xs font-medium transition"
                                        :class="day.isCurrentMonth ? (day.dateStr < todayStr ? 'text-slate-300' : (isDateInRange(day.dateStr) || isRangeStart(day.dateStr) || isRangeEnd(day.dateStr) ? 'bg-primary text-white cursor-pointer' : 'text-slate-800 hover:bg-slate-100 cursor-pointer')) : 'text-slate-300'"
                                        @click.stop="day.isCurrentMonth && day.dateStr >= todayStr && selectTempDate(day.dateStr)"
                                    >
                                        <div v-if="day.isCurrentMonth"
                                             class="w-9 h-9 flex items-center justify-center rounded-full z-10 text-xs font-bold transition-colors"
                                             :class="[
                                                (isRangeStart(day.dateStr) || isRangeEnd(day.dateStr) || isDateInRange(day.dateStr)) ? 'text-white' : (day.dateStr < todayStr ? 'text-slate-300' : 'text-slate-700 hover:bg-slate-100'),
                                                isDateAvailable(day.dateStr) && !isDateInRange(day.dateStr) && !isRangeStart(day.dateStr) && !isRangeEnd(day.dateStr) ? 'bg-primary-50 text-primary ring-1 ring-primary/20' : ''
                                             ]">
                                            {{ day.day }}
                                            <span v-if="isDateAvailable(day.dateStr) && !(isRangeStart(day.dateStr) || isRangeEnd(day.dateStr) || isDateInRange(day.dateStr))" class="absolute top-1.5 right-1.5 w-1 h-1 bg-primary rounded-full"></span>
                                        </div>
                                        <div v-else class="text-xs font-medium text-slate-300">
                                            {{ day.day }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 flex gap-2 justify-end pt-3 border-t border-slate-100">
                                    <button @click="cancelPicker" class="px-4 py-1.5 text-xs font-semibold text-slate-500 hover:text-slate-700 bg-slate-50 hover:bg-slate-100 rounded-lg transition-colors">Batal</button>
                                    <button @click="applyPicker" class="px-4 py-1.5 text-xs font-semibold text-white bg-primary hover:bg-primary-700 rounded-lg transition-colors shadow-sm">Simpan</button>
                                </div>
                            </div>
                            
                            <!-- Deep Blue Divider as in reference -->
                            <div class="h-1 w-full bg-slate-200 rounded-full mt-2 overflow-hidden flex">
                                <div class="h-full bg-primary rounded-full transition-all" style="width: 25%"></div>
                            </div>
                        </div>

                        <!-- Counselors List for selected date -->
                        <div class="space-y-5">
                            <div v-if="counselorsOnSelectedDate.length === 0" class="text-center py-16 bg-white rounded-2xl border border-slate-100 shadow-sm">
                                <Calendar class="w-12 h-12 text-slate-300 mx-auto mb-4" />
                                <p class="text-slate-500 font-medium">Jadwal tidak tersedia pada hari yang dipilih.</p>
                                <p class="text-sm text-slate-400 mt-1">Silakan pilih tanggal lain.</p>
                            </div>

                            <div v-for="counselor in counselorsOnSelectedDate" :key="counselor.id" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex flex-col md:flex-row gap-6">
                                    <div class="flex items-start gap-5 md:w-[40%] shrink-0">
                                        <div class="w-24 h-24 rounded-full overflow-hidden border border-slate-200 bg-slate-50 shrink-0 shadow-sm">
                                            <img :src="counselorPhotoUrl(counselor)" :alt="counselor.user.name" class="w-full h-full object-cover">
                                        </div>
                                        <div>
                                            <span class="text-[11px] font-bold text-primary-700 bg-primary-100 px-2.5 py-1 rounded-full inline-block mb-3">
                                                {{ counselor.practitioner_type === 'psychologist' ? 'Psikolog Klinis Umum' : 'Konselor Umum' }}
                                            </span>
                                            <h3 class="font-bold text-lg text-slate-900 leading-tight mb-2">{{ counselor.user.name }}, {{ counselor.full_title }}</h3>
                                        </div>
                                    </div>
                                    <div class="md:w-[60%] border-t md:border-t-0 md:border-l border-slate-100 pt-5 md:pt-0 md:pl-8">
                                        <div class="flex justify-between items-center mb-4">
                                            <p class="text-xs font-bold text-slate-800 uppercase tracking-wider">Jadwal Tersedia <span v-if="selectedDate">({{ selectedDate }})</span></p>
                                        </div>
                                        <div class="flex flex-wrap gap-2.5">
                                            <template v-for="time in getCounselorTimesList(counselor.user.id)" :key="time.value">
                                                <button
                                                    @click="selectTimeAndProceed(counselor.user.id, time.value)"
                                                    class="px-5 py-2.5 bg-primary-50 hover:bg-primary hover:text-white text-primary border border-primary-200 hover:border-primary rounded-xl text-sm font-bold transition-all shadow-sm"
                                                >
                                                    Booking Sesi - {{ time.label }}
                                                </button>
                                            </template>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Tab 2: Semua Psikolog View -->
                    <div v-else-if="activeTab === 'semua'" class="animate-fade-in">
                        <div class="space-y-5">
                            <div v-for="counselor in filteredCounselors" :key="counselor.id" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex flex-col md:flex-row gap-6 items-center md:items-start justify-between">
                                    <div class="flex items-start gap-5 w-full md:w-auto">
                                        <div class="w-24 h-24 rounded-full overflow-hidden border border-slate-200 bg-slate-50 shrink-0 shadow-sm">
                                            <img :src="counselorPhotoUrl(counselor)" :alt="counselor.user.name" class="w-full h-full object-cover">
                                        </div>
                                        <div class="pt-1">
                                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                                <span class="text-[11px] font-bold text-primary-700 bg-primary-100 px-2.5 py-1 rounded-full">
                                                    {{ counselor.practitioner_type === 'psychologist' ? 'Psikolog Klinis Umum' : 'Konselor Umum' }}
                                                </span>
                                                <span v-if="(slots[counselor.user.id] || []).length > 0" class="text-[11px] font-bold text-green-600 bg-green-50 px-2.5 py-1 rounded-full border border-green-100">
                                                    Tersedia
                                                </span>
                                                <span v-else class="text-[11px] font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full border border-slate-200">
                                                    Belum Tersedia
                                                </span>
                                            </div>
                                            <h3 class="font-bold text-lg text-slate-900 leading-tight mb-2">{{ counselor.user.name }}, {{ counselor.full_title }}</h3>
                                        </div>
                                    </div>
                                    <div class="mt-4 md:mt-0 w-full md:w-auto text-right flex items-center shrink-0">
                                        <button
                                            @click="toggleCounselorExpand(counselor.user.id)"
                                            class="w-full md:w-auto px-6 py-3 bg-primary hover:bg-primary-600 text-white rounded-xl text-sm font-bold transition-all shadow-md flex items-center justify-center gap-2"
                                        >
                                            Lihat Selengkapnya <ChevronDown class="w-4 h-4 transition-transform" :class="expandedCounselorId === counselor.user.id ? 'rotate-180' : ''" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Expanded Schedule for Counselor -->
                                <div v-if="expandedCounselorId === counselor.user.id" class="mt-8 pt-6 border-t border-slate-100 animate-fade-in bg-slate-50/50 rounded-b-2xl -mx-6 -mb-6 px-6 pb-6">
                                    <h4 class="font-bold text-slate-800 mb-4 flex items-center gap-2"><Calendar class="w-4 h-4" /> Jadwal Tersedia (14 Hari ke Depan)</h4>
                                    
                                    <div v-if="(slots[counselor.user.id] || []).length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        <div v-for="day in slots[counselor.user.id]" :key="day.date" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 pb-2 mb-3">{{ day.label }}</p>
                                            <div class="flex flex-wrap gap-2">
                                                <button
                                                    v-for="time in day.times" :key="time.value"
                                                    @click="selectTimeAndProceed(counselor.user.id, time.value)"
                                                    class="px-3 py-1.5 bg-primary-50 hover:bg-primary hover:text-white text-primary border border-primary-200 rounded-lg text-sm font-bold transition-colors"
                                                >
                                                    {{ time.label }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-6 bg-white rounded-xl border border-slate-100 shadow-sm mt-2">
                                        <p class="text-slate-500 text-sm font-medium">Jadwal belum tersedia untuk konselor ini.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Pembayaran / Konfirmasi -->
                <div v-if="currentStep === 3" class="animate-fade-in">
                    <button @click="goBack" class="flex items-center gap-1 text-sm bg-white border border-slate-200 px-4 py-2 rounded-xl text-slate-600 font-medium hover:bg-slate-50 transition-colors mb-6">
                        <ArrowLeft class="w-4 h-4" /> Kembali
                    </button>

                    <div class="max-w-2xl mx-auto">
                        <div class="text-center mb-10">
                            <h2 class="text-2xl lg:text-3xl font-bold text-slate-900 mb-3">Ringkasan Sesi Anda</h2>
                            <p class="text-slate-500">Periksa kembali detail pilihan Anda sebelum melanjutkan ke pembayaran.</p>
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
                                        <span class="text-xs font-bold text-primary bg-primary-50 px-3 py-1 rounded-full uppercase tracking-wider inline-block">
                                            {{ getSelectedCounselor?.practitioner_type === 'psychologist' ? 'Psikolog Klinis Umum' : 'Konselor Umum' }}
                                        </span>
                                    </div>
                                </div>

                                <hr class="border-slate-100">

                                <!-- Details Grid -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Layanan</p>
                                        <div class="flex items-center gap-3 text-slate-800 font-semibold">
                                            <span class="text-xl">{{ activeServiceType && serviceTypeLabels[activeServiceType].icon }}</span>
                                            Sesi Konseling {{ activeServiceType && serviceTypeLabels[activeServiceType].label }}
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Durasi</p>
                                        <div class="flex items-center gap-3 text-slate-800 font-semibold">
                                            <Clock class="w-6 h-6 text-slate-400" />
                                            {{ selectedService?.duration_minutes }} Menit
                                        </div>
                                    </div>

                                    <div class="sm:col-span-2 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Waktu Sesi</p>
                                        <div class="flex items-center gap-3 text-slate-900 font-bold text-lg">
                                            <Calendar class="w-6 h-6 text-primary" />
                                            {{ formatSelectedTimeInfo }} WIB
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Next Steps Info -->
                        <div class="bg-gradient-to-br from-primary/5 via-white to-primary/5 rounded-[2rem] border border-primary/10 shadow-sm p-6 sm:p-8 mb-8 relative overflow-hidden">
                            <!-- Background decoration -->
                            <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-primary/10 blur-2xl"></div>
                            <div class="absolute bottom-0 left-0 -ml-8 -mb-8 w-32 h-32 rounded-full bg-blue-400/10 blur-2xl"></div>

                            <div class="relative z-10 flex flex-col md:flex-row items-center gap-6 sm:gap-8">
                                <div class="w-20 h-20 sm:w-24 sm:h-24 shrink-0 rounded-2xl bg-gradient-to-br from-primary to-primary-600 flex items-center justify-center shadow-lg shadow-primary/30 rotate-3">
                                    <User class="w-10 h-10 text-white -rotate-3" />
                                </div>
                                
                                <div class="text-center md:text-left flex-1">
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/10 text-primary text-[10px] font-bold uppercase tracking-widest mb-3">
                                        <Info class="w-3 h-3" />
                                        Langkah Terakhir
                                    </div>
                                    <h3 class="font-black text-slate-900 text-xl sm:text-2xl mb-2">Login / Buat Akun</h3>
                                    <p class="text-slate-600 text-sm leading-relaxed max-w-lg mx-auto md:mx-0">
                                        Anda perlu masuk ke akun atau mendaftar (bagi pengguna baru) untuk dapat melihat instruksi pembayaran dan menyelesaikan proses booking sesi Anda.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- CTA Box -->
                        <div class="bg-white rounded-[2rem] border border-slate-200 p-6 sm:p-8 flex flex-col lg:flex-row items-center justify-between gap-6 shadow-sm">
                            <div class="text-center lg:text-left">
                                <p class="text-sm font-semibold text-slate-500 mb-1">Total Biaya</p>
                                <p class="text-3xl font-black text-primary-700">{{ formatPrice(selectedService?.price || 0) }}</p>
                            </div>
                            
                            <div class="w-full lg:w-auto">
                                <button
                                    @click="goToBooking"
                                    class="w-full lg:w-auto px-6 sm:px-8 py-4 bg-primary hover:bg-primary-700 text-white rounded-xl text-sm sm:text-base font-bold transition-all shadow-lg shadow-primary/30 flex items-center justify-center gap-3 group"
                                >
                                    <span>Lanjut Login / Buat Akun untuk Menyelesaikan Pembayaran</span>
                                    <ArrowRight class="w-5 h-5 transition-transform group-hover:translate-x-1 shrink-0" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.35s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
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
