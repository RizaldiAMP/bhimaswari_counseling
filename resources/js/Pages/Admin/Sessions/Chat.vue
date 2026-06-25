<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface Message {
    id: number;
    body: string;
    created_at: string;
    sender: { id: number; name: string };
}

interface BookingInfo {
    id: number;
    status: string;
    schedule_start: string;
    schedule_end: string;
    client: { name: string };
    counselor: { name: string };
}

defineProps<{
    booking: BookingInfo;
    messages: Message[];
}>();

const formatTime = (dateString: string) =>
    new Date(dateString).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
</script>

<template>
    <Head :title="`Monitoring Chat #${booking.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.sessions.index')" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </Link>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Monitoring Chat (Read-Only)</h2>
                    <p class="text-xs text-gray-500">Booking #{{ booking.id }} - {{ booking.client.name }} dengan {{ booking.counselor.name }}</p>
                </div>
            </div>
        </template>

        <div class="mx-auto flex h-[72vh] max-w-4xl flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-amber-50 px-6 py-4">
                <p class="text-sm font-semibold text-amber-800">Mode monitoring read-only (admin tidak dapat mengirim pesan)</p>
                <p class="mt-1 text-xs text-amber-700">
                    Jadwal: {{ new Date(booking.schedule_start).toLocaleString('id-ID') }} - {{ new Date(booking.schedule_end).toLocaleString('id-ID') }} WIB
                </p>
            </div>

            <div class="flex-1 space-y-3 overflow-y-auto bg-gray-50 p-6">
                <div v-if="messages.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-6 text-center text-sm text-gray-500">
                    Belum ada pesan pada sesi ini.
                </div>

                <div v-for="message in messages" :key="message.id" class="flex justify-start">
                    <div class="max-w-[78%] rounded-2xl rounded-tl-sm border border-gray-200 bg-white px-4 py-3 text-sm text-gray-800 shadow-sm">
                        <p>{{ message.body }}</p>
                        <p class="mt-2 text-[10px] text-gray-400">{{ message.sender.name }} - {{ formatTime(message.created_at) }}</p>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 bg-white p-4 text-sm text-gray-500">
                Input chat dinonaktifkan pada mode admin monitoring.
            </div>
        </div>
    </AuthenticatedLayout>
</template>
