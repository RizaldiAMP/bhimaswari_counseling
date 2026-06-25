<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/ui/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, watch, nextTick } from 'vue';

interface Message {
    id: number;
    body: string;
    created_at: string;
    sender_id: number;
    sender: { id: number; name: string };
}

interface Booking {
    id: number;
    status: string;
    schedule_start: string;
    schedule_end: string;
    client: { name: string };
    chat_session?: { id: number; started_at?: string | null; timer_started_at?: string | null };
}

const props = defineProps<{
    booking: Booking;
    messages: Message[];
    auth: { user: { id: number; name: string } };
    timerStartedAt: string | null;
    durationMinutes: number;
    timerExpired: boolean;
}>();

const messageForm = useForm({
    body: '',
});

const completeForm = useForm({});

// Chat container & scroll helper
const chatContainer = ref<HTMLDivElement | null>(null);
const scrollToBottom = () => {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }
    });
};

// Timer state
const remainingSeconds = ref<number | null>(null);
const isTimerExpired = ref(props.timerExpired);
const timerInterval = ref<ReturnType<typeof setInterval> | null>(null);

const canSend = computed(() => {
    return props.booking.status === 'in_session' 
        && props.booking.chat_session 
        && !isTimerExpired.value;
});

const timerDisplay = computed(() => {
    if (remainingSeconds.value === null) return null;
    const total = Math.max(0, remainingSeconds.value);
    const hours = Math.floor(total / 3600);
    const minutes = Math.floor((total % 3600) / 60);
    const seconds = total % 60;
    if (hours > 0) {
        return `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});

const timerPercentage = computed(() => {
    if (remainingSeconds.value === null) return 100;
    const totalSeconds = props.durationMinutes * 60;
    return Math.max(0, (remainingSeconds.value / totalSeconds) * 100);
});

const timerColor = computed(() => {
    if (isTimerExpired.value) return 'text-red-600';
    if (remainingSeconds.value !== null && remainingSeconds.value <= 300) return 'text-red-500';
    if (remainingSeconds.value !== null && remainingSeconds.value <= 600) return 'text-amber-500';
    return 'text-emerald-600';
});

const timerBgColor = computed(() => {
    if (isTimerExpired.value) return 'bg-red-500';
    if (remainingSeconds.value !== null && remainingSeconds.value <= 300) return 'bg-red-500';
    if (remainingSeconds.value !== null && remainingSeconds.value <= 600) return 'bg-amber-500';
    return 'bg-emerald-500';
});

function startTimer() {
    if (!props.timerStartedAt) return;
    
    const startTime = new Date(props.timerStartedAt).getTime();
    const endTime = startTime + (60 * 60 * 1000); // Always 60 minutes max

    const updateTimer = () => {
        const now = Date.now();
        const remaining = Math.floor((endTime - now) / 1000);
        remainingSeconds.value = Math.max(0, remaining);
        
        if (remaining <= 0) {
            isTimerExpired.value = true;
            if (timerInterval.value) {
                clearInterval(timerInterval.value);
                timerInterval.value = null;
            }
        }
    };

    updateTimer();
    timerInterval.value = setInterval(updateTimer, 1000);
}

onMounted(() => {
    scrollToBottom();
    if (props.timerStartedAt) {
        startTimer();
    }
    
    if (props.booking.chat_session) {
        // @ts-ignore
        if (window.Echo) {
            // @ts-ignore
            window.Echo.private(`chat-session.${props.booking.chat_session.id}`)
                .listen('MessageSent', (e: any) => {
                    if (!props.messages.find(m => m.id === e.message.id)) {
                        props.messages.push(e.message);
                        scrollToBottom();
                    }
                });
        }
    }
});

watch(() => props.timerStartedAt, (newVal) => {
    if (newVal) {
        startTimer();
    }
});

watch(() => props.messages.length, () => {
    scrollToBottom();
});

onUnmounted(() => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value);
    }
    
    if (props.booking.chat_session) {
        // @ts-ignore
        if (window.Echo) {
            // @ts-ignore
            window.Echo.leave(`chat-session.${props.booking.chat_session.id}`);
        }
    }
});

const submitMessage = () => {
    if (!canSend.value || messageForm.body.trim() === '') return;

    messageForm.post(route('counselor.chat.messages.store', props.booking.id), {
        preserveScroll: true,
        onSuccess: () => messageForm.reset('body'),
    });
};

const completeSession = () => {
    completeForm.post(route('counselor.chat.complete', props.booking.id), {
        preserveScroll: true,
    });
};

const formatTime = (dateString: string) =>
    new Date(dateString).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
</script>

<template>
    <Head title="Chat Konselor" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <Link :href="route('counselor.bookings.show', booking.id)" class="text-gray-400 hover:text-gray-600 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </Link>
                <div class="min-w-0 flex-1">
                    <h2 class="font-semibold text-base sm:text-xl text-gray-800 leading-tight truncate">Chat: {{ booking.client.name }}</h2>
                    <p class="hidden sm:block text-xs text-gray-500 truncate mt-0.5">
                        <span v-if="isTimerExpired">Waktu sesi telah habis.</span>
                        <span v-else-if="timerStartedAt">Timer berjalan.</span>
                        <span v-else>Kirim pesan pertama untuk memulai timer.</span>
                    </p>
                </div>
            </div>
        </template>

        <div class="mx-auto flex h-[calc(100dvh-120px)] md:h-[72vh] max-w-4xl flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            <!-- Timer & Status Bar -->
            <div class="border-b border-gray-100 px-4 py-3 sm:px-6 sm:py-4" :class="booking.status === 'completed' ? 'bg-green-50' : (isTimerExpired ? 'bg-red-50' : 'bg-primary-50')">
                <div class="flex flex-wrap sm:flex-nowrap items-center justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs sm:text-sm font-medium truncate" :class="booking.status === 'completed' ? 'text-green-800' : (isTimerExpired ? 'text-red-800' : 'text-primary-800')">
                            <span v-if="booking.status === 'completed'">✅ Sesi konseling telah selesai</span>
                            <span v-else-if="isTimerExpired">⏰ Waktu habis</span>
                            <span v-else-if="timerStartedAt">⏱️ Sesi berjalan</span>
                            <span v-else>💬 Menunggu pesan pertama...</span>
                        </p>
                        <p class="mt-0.5 text-[10px] sm:text-xs truncate" :class="booking.status === 'completed' ? 'text-green-700' : (isTimerExpired ? 'text-red-600' : 'text-primary-700')">
                            {{ new Date(booking.schedule_start).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }} WIB · Klien: {{ booking.client.name }}
                        </p>
                    </div>
                    
                    <div class="flex items-center gap-3 shrink-0">
                        <template v-if="booking.status === 'completed'">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/60 backdrop-blur-sm border border-green-200 text-xs font-bold text-green-700 shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                                Riwayat Chat
                            </span>
                        </template>
                        <template v-else>
                            <PrimaryButton
                                type="button"
                                class="!rounded-xl !border-0 !bg-gradient-to-r !from-[#8f4a94] !to-[#723577] px-3 sm:px-4 py-1.5 sm:py-2 text-[10px] sm:text-xs font-bold text-white shadow-sm transition-all shrink-0"
                                :disabled="booking.status !== 'in_session' || completeForm.processing"
                                @click="completeSession"
                            >
                                Selesai
                            </PrimaryButton>

                            <!-- Timer Display -->
                            <div v-if="timerStartedAt" class="text-right">
                                <p class="text-lg sm:text-2xl font-black tabular-nums tracking-tight leading-none" :class="timerColor">
                                    {{ timerDisplay }}
                                </p>
                            </div>
                            <div v-else class="flex items-center gap-1.5 bg-white/60 backdrop-blur-sm rounded-xl px-2 sm:px-3 py-1.5 border border-primary-100">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary-500"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span class="text-[10px] sm:text-xs font-bold text-primary-700">{{ durationMinutes }}m</span>
                            </div>
                        </template>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div v-if="timerStartedAt && booking.status !== 'completed'" class="mt-3 h-1.5 rounded-full bg-gray-200 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-1000 ease-linear" 
                         :class="timerBgColor" 
                         :style="`width: ${timerPercentage}%`">
                    </div>
                </div>
            </div>

            <div ref="chatContainer" class="flex-1 space-y-3 overflow-y-auto bg-gray-50 p-6">
                <div v-if="messages.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-6 text-center text-sm text-gray-500">
                    Belum ada pesan pada sesi ini.
                </div>

                <div
                    v-for="message in messages"
                    :key="message.id"
                    class="flex"
                    :class="message.sender_id === auth.user.id ? 'justify-end' : 'justify-start'"
                >
                    <div
                        class="max-w-[78%] rounded-2xl px-4 py-3 text-sm shadow-sm"
                        :class="message.sender_id === auth.user.id ? 'rounded-tr-sm bg-primary text-white' : 'rounded-tl-sm border border-gray-200 bg-white text-gray-800'"
                    >
                        <p class="break-words whitespace-pre-wrap">{{ message.body }}</p>
                        <p class="mt-2 text-[10px]" :class="message.sender_id === auth.user.id ? 'text-white/80' : 'text-gray-400'">
                            {{ message.sender.name }} · {{ formatTime(message.created_at) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 bg-white p-4">
                <!-- Completed or Timer expired warning -->
                <div v-if="booking.status === 'completed'" class="mb-3 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-center">
                    <p class="text-sm font-bold text-green-700">✅ Sesi konseling telah selesai</p>
                    <p class="text-xs text-green-600 mt-1">Sesi chat ini telah selesai. Anda tetap dapat membaca seluruh riwayat percakapan di atas.</p>
                </div>
                <div v-else-if="isTimerExpired" class="mb-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-center">
                    <p class="text-sm font-bold text-red-700">⏰ Waktu sesi telah habis</p>
                    <p class="text-xs text-red-500 mt-1">Silakan klik "Selesaikan Sesi" untuk menutup sesi ini.</p>
                </div>
                
                <form class="flex items-end gap-3" @submit.prevent="submitMessage">
                    <textarea
                        v-model="messageForm.body"
                        rows="2"
                        class="w-full resize-none rounded-xl border-gray-300 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed"
                        :disabled="!canSend || messageForm.processing"
                        :placeholder="booking.status === 'completed' ? 'Sesi konseling telah selesai...' : (isTimerExpired ? 'Waktu sesi telah habis...' : 'Ketik pesan untuk klien...')"
                    ></textarea>
                    <PrimaryButton type="submit" class="h-[46px] px-5" :disabled="!canSend || messageForm.processing || messageForm.body.trim() === ''">
                        Kirim
                    </PrimaryButton>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
