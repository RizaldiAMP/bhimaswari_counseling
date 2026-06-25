<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/ui/PrimaryButton.vue';
import { ref, computed } from 'vue';
import {
    CalendarDays, Plus, X, Clock, AlertTriangle,
    Trash2, CalendarOff,
    CheckCircle2, XCircle, CalendarPlus, Repeat
} from 'lucide-vue-next';

interface Rule {
    id: number;
    day_of_week: number;
    start_time: string;
}

interface Exception {
    id: number;
    exception_date: string;
    start_time: string | null;
    type: 'blocked' | 'added';
    reason: string | null;
}

const props = defineProps<{
    rules: Rule[];
    dayOffs: number[];
    exceptions: Exception[];
}>();

const daysOfWeek = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

// ─── Rules grouped by day_of_week ───
const rulesGroupedByDay = computed(() => {
    const grouped = new Map<number, Rule[]>();
    for (let i = 0; i < 7; i++) grouped.set(i, []);
    props.rules.forEach(rule => {
        grouped.get(rule.day_of_week)?.push(rule);
    });
    for (let i = 0; i < 7; i++) {
        grouped.get(i)?.sort((a, b) => a.start_time.localeCompare(b.start_time));
    }
    return grouped;
});

const totalSlots = computed(() => props.rules.length);

const addedExceptions = computed(() => props.exceptions.filter(e => e.type === 'added'));
const blockedExceptions = computed(() => props.exceptions.filter(e => e.type === 'blocked'));

// ─── Inline form state ───
const addingDay = ref<number | null>(null);
const scheduleMode = ref<'rutin' | 'khusus'>('rutin');

const ruleForm = useForm({
    day_of_week: 0,
    start_time: '09:00',
});

const specificDateForm = useForm({
    exception_date: '',
    type: 'added' as 'added',
    start_time: '09:00',
    reason: '',
});

const openAddForm = (dayIndex: number) => {
    addingDay.value = dayIndex;
    scheduleMode.value = 'rutin';
    ruleForm.day_of_week = dayIndex;
    ruleForm.start_time = '09:00';
    ruleForm.clearErrors();
    specificDateForm.clearErrors();
    // Pre-fill specific date to next occurrence of this day
    const today = new Date();
    const dayDiff = (dayIndex + 1 - today.getDay() + 7) % 7 || 7;
    const nextDate = new Date(today);
    nextDate.setDate(today.getDate() + dayDiff);
    specificDateForm.exception_date = nextDate.toISOString().split('T')[0];
    specificDateForm.start_time = '09:00';
    specificDateForm.reason = '';
};

const cancelAdd = () => {
    addingDay.value = null;
    ruleForm.clearErrors();
    specificDateForm.clearErrors();
};

const submitRule = () => {
    ruleForm.post(route('counselor.availability.rules.store'), {
        preserveScroll: true,
        onSuccess: () => {
            addingDay.value = null;
            ruleForm.reset('start_time');
        },
    });
};

const submitSpecificDate = () => {
    specificDateForm.post(route('counselor.availability.exceptions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            addingDay.value = null;
            specificDateForm.reset();
        },
    });
};

const deleteRule = (id: number) => {
    router.delete(route('counselor.availability.rules.destroy', id), {
        preserveScroll: true,
    });
};

// ─── Recurring Day Offs ───
const isDayOff = (dayIndex: number) => props.dayOffs.includes(dayIndex);

const toggleDayOff = (dayIndex: number) => {
    router.post(route('counselor.availability.dayoffs.toggle'), {
        day_of_week: dayIndex,
    }, {
        preserveScroll: true,
    });
};

// ─── Exception / Holiday Dates ───
const showExceptionForm = ref(false);

const exceptionForm = useForm({
    exception_date: '',
    type: 'blocked' as 'blocked' | 'added',
    start_time: '',
    reason: '',
});

const submitException = () => {
    if (exceptionForm.type === 'blocked') {
        exceptionForm.start_time = '';
    }
    exceptionForm.post(route('counselor.availability.exceptions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            exceptionForm.reset();
            showExceptionForm.value = false;
        },
    });
};

const deleteException = (id: number) => {
    if (window.confirm('Hapus jadwal khusus ini?')) {
        router.delete(route('counselor.availability.exceptions.destroy', id), {
            preserveScroll: true,
        });
    }
};

const formatTime = (t: string) => t.substring(0, 5);
const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
</script>

<template>
    <Head title="Ketersediaan Jadwal" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-gradient-to-br from-primary-50 to-primary-100 rounded-2xl flex items-center justify-center shadow-sm border border-primary-100">
                    <CalendarDays class="w-6 h-6 text-primary-600" />
                </div>
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight">Pengaturan Jadwal</h2>
                    <p class="text-sm text-gray-500 font-medium mt-0.5">Atur jam operasional Anda — berlaku untuk semua metode layanan (Chat, Online, Offline)</p>
                </div>
            </div>
        </template>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

            <!-- ═══════════════════════════════════════════════ -->
            <!-- SECTION 1: JADWAL MINGGUAN UNIFIED             -->
            <!-- ═══════════════════════════════════════════════ -->
            <div class="bg-white rounded-3xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 overflow-hidden">

                <!-- Header -->
                <div class="px-8 py-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-50">
                    <div>
                        <h3 class="font-bold text-lg text-gray-900 flex items-center gap-2">
                            <span>🗓️</span>
                            Jadwal Slot Mingguan
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">Atur jam praktik per hari. Jadwal berlaku untuk <strong>semua metode layanan</strong> (Chat, Online, Offline). Jarak minimal antar slot: <strong>90 menit</strong>.</p>
                    </div>
                    <div class="flex items-center gap-2 bg-primary-50/50 border border-primary-100 px-4 py-2 rounded-xl">
                        <Clock class="w-4 h-4 text-primary-600" />
                        <span class="text-sm font-bold text-primary-700">
                            {{ totalSlots }} Slot Tersedia
                        </span>
                    </div>
                </div>

                <!-- Info Banner -->
                <div class="px-8 py-4 bg-blue-50/50 border-b border-blue-100/50">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                            <AlertTriangle class="w-4 h-4 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-sm text-blue-800 font-medium">Satu Jadwal untuk Semua Layanan</p>
                            <p class="text-xs text-blue-600 mt-0.5">Jika Anda membuat slot jam 09:00 di hari Senin, maka klien bisa mem-booking Chat, Online, <em>atau</em> Offline di jam tersebut. Booking apapun yang masuk akan otomatis memblokir slot tersebut untuk metode lainnya.</p>
                        </div>
                    </div>
                </div>

                <!-- Day List -->
                <div class="divide-y divide-gray-50/80">
                    <div v-for="(dayName, dayIdx) in daysOfWeek" :key="dayIdx"
                         class="px-8 py-6 transition-all duration-300"
                         :class="isDayOff(dayIdx) ? 'bg-gray-50/50 opacity-75' : 'hover:bg-gray-50/30'">

                        <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-6">
                            <!-- Left: Day name + status -->
                            <div class="w-full lg:w-48 shrink-0 flex items-center lg:items-start justify-between lg:flex-col gap-2">
                                <h4 class="text-base font-bold text-gray-800" :class="isDayOff(dayIdx) ? 'text-gray-400' : ''">
                                    {{ dayName }}
                                </h4>
                                <span v-if="isDayOff(dayIdx)" class="inline-flex items-center gap-1 text-[11px] font-bold bg-red-50 text-red-600 px-2.5 py-1 rounded-md border border-red-100">
                                    <CalendarOff class="w-3 h-3" />
                                    LIBUR RUTIN
                                </span>
                            </div>

                            <!-- Middle: Time slots or Empty State -->
                            <div class="flex-1 min-w-0">
                                <div v-if="!isDayOff(dayIdx)" class="flex flex-wrap gap-2.5">
                                    <!-- Time Chip -->
                                    <div
                                        v-for="rule in rulesGroupedByDay.get(dayIdx)"
                                        :key="rule.id"
                                        class="group flex items-center gap-2 bg-white border border-gray-200 shadow-sm text-gray-700 rounded-xl pl-3 pr-1.5 py-1.5 text-sm font-medium transition-all hover:border-red-200 hover:shadow-md"
                                    >
                                        <div class="flex items-center gap-1.5 group-hover:text-red-500 transition-colors">
                                            <Clock class="w-3.5 h-3.5 text-gray-400 group-hover:text-red-400" />
                                            <span>{{ formatTime(rule.start_time) }}</span>
                                        </div>
                                        <div class="w-px h-4 bg-gray-100 mx-1 group-hover:bg-red-100 transition-colors"></div>
                                        <button @click.stop.prevent="deleteRule(rule.id)" type="button" class="p-1.5 text-gray-400 rounded-lg hover:bg-red-50 hover:text-red-600 transition-colors" title="Hapus Jadwal">
                                            <X class="w-3.5 h-3.5" />
                                        </button>
                                    </div>

                                    <!-- Empty state if no slots -->
                                    <div v-if="(rulesGroupedByDay.get(dayIdx)?.length || 0) === 0" class="flex items-center gap-2 text-sm text-gray-400 bg-gray-50/50 px-4 py-2 rounded-xl border border-dashed border-gray-200">
                                        <span class="block w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                                        Belum ada jadwal
                                    </div>
                                </div>
                                <div v-else class="text-sm text-gray-400 italic flex items-center gap-2">
                                    <div class="w-8 h-px bg-gray-200"></div>
                                    Tidak melayani booking pada hari ini
                                    <div class="w-8 h-px bg-gray-200"></div>
                                </div>

                                <!-- Inline Add Form -->
                                <div v-if="addingDay === dayIdx && !isDayOff(dayIdx)" class="mt-4 p-5 bg-white rounded-2xl border border-gray-200 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] animate-fade-in relative ring-1 ring-black/5">
                                    <div class="absolute top-0 left-0 w-1.5 h-full rounded-l-2xl" :class="scheduleMode === 'rutin' ? 'bg-primary-500' : 'bg-emerald-500'"></div>
                                    
                                    <h5 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 ml-2">Tambah Jadwal</h5>
                                    
                                    <!-- Mode Toggle -->
                                    <div class="flex gap-2 mb-5 ml-2">
                                        <button
                                            type="button"
                                            @click="scheduleMode = 'rutin'"
                                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-all border"
                                            :class="scheduleMode === 'rutin'
                                                ? 'bg-primary-50 text-primary-700 border-primary-200 shadow-sm'
                                                : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50'"
                                        >
                                            <Repeat class="w-4 h-4" />
                                            Rutin Mingguan
                                        </button>
                                        <button
                                            type="button"
                                            @click="scheduleMode = 'khusus'"
                                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-all border"
                                            :class="scheduleMode === 'khusus'
                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200 shadow-sm'
                                                : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50'"
                                        >
                                            <CalendarPlus class="w-4 h-4" />
                                            Tanggal Khusus
                                        </button>
                                    </div>

                                    <!-- Rutin Mode -->
                                    <div v-if="scheduleMode === 'rutin'" class="ml-2">
                                        <p class="text-xs text-gray-400 mb-3">Jadwal ini berlaku <span class="font-bold text-gray-600">setiap {{ daysOfWeek[dayIdx] }}</span> secara berulang. Jarak minimal antar slot: <span class="font-bold text-primary-600">90 menit</span>.</p>
                                        <div class="flex flex-wrap items-center gap-4">
                                            <div class="flex items-center gap-3">
                                                <div>
                                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Jam Mulai Sesi</label>
                                                    <input v-model="ruleForm.start_time" type="time" required
                                                           class="w-[140px] rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-base font-bold text-gray-900 shadow-sm focus:border-primary-400 focus:ring-primary-400 hover:border-primary-300 transition-colors cursor-pointer" />
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 ml-auto">
                                                <button @click="cancelAdd" class="px-4 py-2.5 text-sm text-gray-500 hover:text-gray-700 hover:bg-gray-100 font-semibold rounded-xl transition-colors">
                                                    Batal
                                                </button>
                                                <button
                                                    @click="submitRule"
                                                    :disabled="ruleForm.processing"
                                                    class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-bold rounded-xl transition-all shadow-sm shadow-primary-600/20 disabled:opacity-50 flex items-center gap-2"
                                                >
                                                    <Plus class="w-4 h-4" />
                                                    {{ ruleForm.processing ? 'Menyimpan...' : 'Simpan' }}
                                                </button>
                                            </div>
                                        </div>
                                        <div v-if="ruleForm.errors.start_time" class="mt-3 flex flex-col gap-1">
                                            <div class="text-xs text-red-500 font-medium flex items-center gap-1"><AlertTriangle class="w-3 h-3"/> {{ ruleForm.errors.start_time }}</div>
                                        </div>
                                    </div>

                                    <!-- Khusus Mode -->
                                    <div v-else class="ml-2">
                                        <p class="text-xs text-gray-400 mb-3">Jadwal ini hanya berlaku pada <span class="font-bold text-emerald-600">satu tanggal tertentu</span> saja.</p>
                                        <div class="flex flex-wrap items-end gap-4">
                                            <div>
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Tanggal</label>
                                                <input v-model="specificDateForm.exception_date" type="date" required :min="new Date().toISOString().split('T')[0]"
                                                       class="w-[180px] rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-base font-bold text-gray-900 shadow-sm focus:border-emerald-400 focus:ring-emerald-400 hover:border-emerald-300 transition-colors cursor-pointer" />
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Jam Mulai Sesi</label>
                                                <input v-model="specificDateForm.start_time" type="time" required
                                                       class="w-[140px] rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-base font-bold text-gray-900 shadow-sm focus:border-emerald-400 focus:ring-emerald-400 hover:border-emerald-300 transition-colors cursor-pointer" />
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Keterangan (Opsional)</label>
                                            <input v-model="specificDateForm.reason" type="text" placeholder="Misal: Ganti jam dari jadwal rutin"
                                                   class="w-full max-w-md rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-900 shadow-sm focus:border-emerald-400 focus:ring-emerald-400 hover:border-emerald-300 transition-colors" />
                                        </div>
                                        <div class="flex items-center gap-2 mt-4 justify-end">
                                            <button @click="cancelAdd" class="px-4 py-2.5 text-sm text-gray-500 hover:text-gray-700 hover:bg-gray-100 font-semibold rounded-xl transition-colors">
                                                Batal
                                            </button>
                                            <button
                                                @click="submitSpecificDate"
                                                :disabled="specificDateForm.processing"
                                                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl transition-all shadow-sm shadow-emerald-600/20 disabled:opacity-50 flex items-center gap-2"
                                            >
                                                <CalendarPlus class="w-4 h-4" />
                                                {{ specificDateForm.processing ? 'Menyimpan...' : 'Simpan Tanggal Khusus' }}
                                            </button>
                                        </div>
                                        <div v-if="specificDateForm.errors.exception_date || specificDateForm.errors.start_time" class="mt-3 flex flex-col gap-1">
                                            <div v-if="specificDateForm.errors.exception_date" class="text-xs text-red-500 font-medium flex items-center gap-1"><AlertTriangle class="w-3 h-3"/> Tanggal: {{ specificDateForm.errors.exception_date }}</div>
                                            <div v-if="specificDateForm.errors.start_time" class="text-xs text-red-500 font-medium flex items-center gap-1"><AlertTriangle class="w-3 h-3"/> {{ specificDateForm.errors.start_time }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Add button -->
                            <div class="lg:w-32 shrink-0 flex justify-end">
                                <button
                                    v-if="addingDay !== dayIdx && !isDayOff(dayIdx)"
                                    @click="openAddForm(dayIdx)"
                                    class="flex items-center gap-2 px-4 py-2 bg-white hover:bg-primary-50 text-gray-600 hover:text-primary-600 border border-gray-200 hover:border-primary-200 rounded-xl text-sm font-bold transition-all shadow-sm hover:shadow-md"
                                >
                                    <Plus class="w-4 h-4" />
                                    Tambah
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- ═══════════════════════════════════════════════ -->
            <!-- SECTION 2: JADWAL TANGGAL KHUSUS               -->
            <!-- ═══════════════════════════════════════════════ -->
            <div class="bg-white rounded-3xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 overflow-hidden">
                <div class="p-8 border-b border-gray-50 flex flex-col sm:flex-row gap-4 justify-between sm:items-center">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center border border-emerald-100 shrink-0">
                            <CalendarPlus class="w-6 h-6 text-emerald-500" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Jadwal Tanggal Khusus</h3>
                            <p class="text-sm text-gray-500 mt-1">Jadwal tambahan di luar rutinitas yang hanya berlaku pada tanggal spesifik.</p>
                        </div>
                    </div>
                </div>

                <!-- Exceptions List (Added) -->
                <div v-if="addedExceptions.length === 0" class="p-16 flex flex-col items-center justify-center text-center">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                        <CalendarPlus class="w-8 h-8 text-gray-300" />
                    </div>
                    <h4 class="text-base font-bold text-gray-900 mb-1">Belum Ada Jadwal Khusus</h4>
                    <p class="text-sm text-gray-500 max-w-sm">Gunakan tombol "Tambah" di jadwal slot mingguan dan pilih mode "Tanggal Khusus" untuk menambahkan jadwal spesifik.</p>
                </div>

                <div v-if="addedExceptions.length > 0" class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 bg-gray-50/30">
                    <div v-for="exception in addedExceptions" :key="exception.id"
                         class="bg-white p-5 rounded-2xl border border-emerald-200 shadow-sm hover:shadow-md hover:border-emerald-300 transition-all group relative overflow-hidden">
                        
                        <!-- Accent line -->
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-emerald-400"></div>

                        <div class="flex items-start justify-between gap-4 relative z-10">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 font-bold text-lg border bg-emerald-50 text-emerald-600 border-emerald-100">
                                    {{ new Date(exception.exception_date).getDate() }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ formatDate(exception.exception_date) }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded-md border border-emerald-100">
                                            <CalendarPlus class="w-3 h-3" />
                                            JAM KHUSUS
                                        </span>
                                    </div>
                                    <p v-if="exception.start_time" class="text-xs text-emerald-600 font-bold mt-1 flex items-center gap-1">
                                        <Clock class="w-3 h-3" />
                                        {{ formatTime(exception.start_time) }}
                                    </p>
                                    <p v-if="exception.reason" class="text-xs text-gray-500 mt-1 flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full block bg-emerald-400"></span>
                                        {{ exception.reason }}
                                    </p>
                                </div>
                            </div>
                            <button @click.stop.prevent="deleteException(exception.id)" type="button"
                                    class="text-gray-400 hover:text-red-500 p-2 rounded-xl hover:bg-red-50 transition-all opacity-0 group-hover:opacity-100 bg-white shadow-sm border border-gray-100" title="Hapus">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════ -->
            <!-- SECTION 3: HARI LIBUR RUTIN                    -->
            <!-- ═══════════════════════════════════════════════ -->
            <div class="bg-white rounded-3xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 overflow-hidden">
                <div class="p-8 border-b border-gray-50 flex flex-col md:flex-row gap-4 justify-between items-start md:items-center">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-rose-50 rounded-2xl flex items-center justify-center border border-rose-100 shrink-0">
                            <CalendarOff class="w-6 h-6 text-rose-500" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Hari Libur Rutin Mingguan</h3>
                            <p class="text-sm text-gray-500 mt-1">Pilih hari di mana Anda tidak menerima sesi layanan apapun secara otomatis setiap minggunya.</p>
                        </div>
                    </div>
                </div>

                <div class="p-8 bg-gray-50/30">
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                        <button
                            v-for="(dayName, dayIdx) in daysOfWeek"
                            :key="dayIdx"
                            @click="toggleDayOff(dayIdx)"
                            class="relative flex flex-col items-center gap-3 p-5 rounded-2xl transition-all duration-300 overflow-hidden group"
                            :class="isDayOff(dayIdx)
                                ? 'bg-rose-50 border-2 border-rose-500 shadow-sm'
                                : 'bg-white border-2 border-transparent shadow-sm hover:shadow-md hover:border-gray-200'"
                        >
                            <div v-if="isDayOff(dayIdx)" class="absolute -top-6 -right-6 w-16 h-16 bg-rose-100 rounded-full opacity-50"></div>
                            
                            <div class="relative z-10 w-10 h-10 rounded-full flex items-center justify-center transition-colors"
                                 :class="isDayOff(dayIdx) ? 'bg-rose-500 text-white' : 'bg-gray-100 text-gray-400 group-hover:bg-gray-200'">
                                <XCircle v-if="isDayOff(dayIdx)" class="w-5 h-5" />
                                <CheckCircle2 v-else class="w-5 h-5" />
                            </div>
                            
                            <div class="relative z-10 text-center">
                                <span class="block text-base font-bold" :class="isDayOff(dayIdx) ? 'text-rose-900' : 'text-gray-700'">{{ dayName }}</span>
                                <span class="block text-[11px] font-bold uppercase tracking-wider mt-1" 
                                      :class="isDayOff(dayIdx) ? 'text-rose-500' : 'text-emerald-500'">
                                    {{ isDayOff(dayIdx) ? 'Libur' : 'Aktif' }}
                                </span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════ -->
            <!-- SECTION 4: CUTI                               -->
            <!-- ═══════════════════════════════════════════════ -->
            <div class="bg-white rounded-3xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-gray-100 overflow-hidden">
                <div class="p-8 border-b border-gray-50 flex flex-col sm:flex-row gap-4 justify-between sm:items-center">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center border border-amber-100 shrink-0">
                            <AlertTriangle class="w-6 h-6 text-amber-500" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Cuti / Blokir Tanggal</h3>
                            <p class="text-sm text-gray-500 mt-1">Atur cuti insidental di tanggal tertentu untuk menonaktifkan semua layanan.</p>
                        </div>
                    </div>
                    <button
                        @click="showExceptionForm = !showExceptionForm"
                        class="flex items-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-bold transition-all shadow-sm shadow-amber-500/20 shrink-0"
                    >
                        <Plus class="w-4 h-4" />
                        Tambah Cuti
                    </button>
                </div>

                <!-- Add Exception Form -->
                <div v-if="showExceptionForm" class="p-8 bg-amber-50/50 border-b border-amber-100/50 animate-fade-in relative">
                    <div class="absolute top-0 left-0 w-1.5 h-full bg-amber-500"></div>
                    <form @submit.prevent="submitException" class="max-w-2xl bg-white p-8 rounded-2xl shadow-[0_4px_20px_-4px_rgba(245,158,11,0.1)] border border-amber-200/60 ring-1 ring-black/5">
                        <h4 class="text-sm font-bold text-gray-900 mb-6 flex items-center gap-2">
                            <CalendarDays class="w-5 h-5 text-amber-500"/>
                            Form Tambah Cuti / Blokir Tanggal
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Tanggal</label>
                                <input v-model="exceptionForm.exception_date" type="date" required :min="new Date().toISOString().split('T')[0]"
                                       class="w-full rounded-xl border border-gray-300 bg-white shadow-sm focus:border-amber-400 focus:ring-amber-400 text-base font-bold text-gray-900 py-3 px-4 cursor-pointer hover:border-amber-300 transition-colors" />
                                <p v-if="exceptionForm.errors.exception_date" class="text-red-500 text-xs mt-1">{{ exceptionForm.errors.exception_date }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Alasan / Keterangan (Opsional)</label>
                                <input v-model="exceptionForm.reason" type="text" placeholder="Misal: Cuti Idul Fitri, Sakit, dll."
                                       class="w-full rounded-xl border border-gray-300 bg-white shadow-sm focus:border-amber-400 focus:ring-amber-400 text-base font-medium text-gray-900 py-3 px-4 hover:border-amber-300 transition-colors" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-50">
                            <button type="button" @click="showExceptionForm = false" class="px-5 py-2.5 text-sm text-gray-500 hover:text-gray-700 font-semibold transition-colors">
                                Batal
                            </button>
                            <button type="submit" :disabled="exceptionForm.processing"
                                    class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl transition-all shadow-sm shadow-amber-500/20 disabled:opacity-50">
                                {{ exceptionForm.processing ? 'Menyimpan...' : 'Simpan Cuti' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Exceptions List (Blocked) -->
                <div v-if="blockedExceptions.length === 0 && !showExceptionForm" class="p-16 flex flex-col items-center justify-center text-center">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                        <CalendarOff class="w-8 h-8 text-gray-300" />
                    </div>
                    <h4 class="text-base font-bold text-gray-900 mb-1">Belum Ada Cuti Khusus</h4>
                    <p class="text-sm text-gray-500 max-w-sm">Gunakan tombol "Tambah Cuti" untuk memblokir tanggal dan tidak menerima sesi apapun pada hari tersebut.</p>
                </div>

                <div v-if="blockedExceptions.length > 0" class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 bg-gray-50/30">
                    <div v-for="exception in blockedExceptions" :key="exception.id"
                         class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm hover:shadow-md hover:border-amber-200 transition-all group relative overflow-hidden">
                        
                        <!-- Accent line -->
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-amber-400"></div>

                        <div class="flex items-start justify-between gap-4 relative z-10">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 font-bold text-lg border bg-amber-50 text-amber-600 border-amber-100">
                                    {{ new Date(exception.exception_date).getDate() }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ formatDate(exception.exception_date) }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold bg-red-50 text-red-600 px-2 py-0.5 rounded-md border border-red-100">
                                            <CalendarOff class="w-3 h-3" />
                                            CUTI
                                        </span>
                                    </div>
                                    <p v-if="exception.reason" class="text-xs text-gray-500 mt-1 flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full block bg-amber-400"></span>
                                        {{ exception.reason }}
                                    </p>
                                </div>
                            </div>
                            <button @click.stop.prevent="deleteException(exception.id)" type="button"
                                    class="text-gray-400 hover:text-red-500 p-2 rounded-xl hover:bg-red-50 transition-all opacity-0 group-hover:opacity-100 bg-white shadow-sm border border-gray-100" title="Hapus">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
