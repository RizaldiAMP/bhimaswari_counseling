<script setup lang="ts">
import { reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import PrimaryButton from '@/Components/ui/PrimaryButton.vue';

interface RescheduleItem {
    id: number;
    reason: string | null;
    status: string;
    old_schedule_start: string;
    old_schedule_end: string;
    new_schedule_start: string;
    booking: {
        id: number;
        status: string;
        client: { id: number; name: string; email: string };
        counselor: { id: number; name: string };
        service_type: string;
        duration_minutes: number;
        price_at_booking: number;
    };
    requester: {
        id: number;
        name: string;
    };
}

const props = defineProps<{
    pendingReschedules: { data: RescheduleItem[]; links: any };
}>();

const rows = reactive<Record<number, { new_schedule_start: string; admin_notes: string }>>({});
const page = usePage();

const initRow = (id: number, suggestedStart: string) => {
    if (!rows[id]) {
        rows[id] = {
            new_schedule_start: suggestedStart ? suggestedStart.slice(0, 16) : '',
            admin_notes: '',
        };
    }
};

const approve = (item: RescheduleItem) => {
    initRow(item.id, item.new_schedule_start);
    const row = rows[item.id];

    router.post(route('admin.reschedules.approve', item.id), {
        new_schedule_start: row.new_schedule_start,
        admin_notes: row.admin_notes || null,
    }, {
        preserveScroll: true,
    });
};

const reject = (item: RescheduleItem) => {
    initRow(item.id, item.new_schedule_start);
    const row = rows[item.id];

    router.post(route('admin.reschedules.reject', item.id), {
        admin_notes: row.admin_notes,
    }, {
        preserveScroll: true,
    });
};

const errors = page.props.errors as Record<string, string>;

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(amount);

const formatDate = (dateString: string) =>
    new Date(dateString).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
</script>

<template>
    <Head title="Manajemen Reschedule" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manajemen Reschedule</h2>
        </template>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-yellow-50">
                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-yellow-600"><path d="M12 6v6l4 2"/><path d="M16 21.16a10 10 0 1 1 5-13.516"/><path d="M20 11.5v6"/><path d="M20 21.5h.01"/></svg>
                    Booking Menunggu Reschedule
                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-bold text-yellow-700">
                        {{ pendingReschedules.data.length }}
                    </span>
                </h3>
            </div>

            <div v-if="pendingReschedules.data.length === 0" class="p-12 text-center text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4 text-gray-300"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                <p class="text-lg font-medium">Tidak ada permintaan reschedule</p>
                <p class="text-sm text-gray-400 mt-1">Semua jadwal berjalan normal.</p>
            </div>

            <div v-else class="divide-y divide-gray-100">
                <div v-for="item in pendingReschedules.data" :key="item.id" class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="w-full">
                            <div class="flex items-center gap-3 mb-2">
                                <h4 class="font-bold text-gray-900">{{ item.booking.client.name }}</h4>
                                <StatusBadge :status="item.booking.status" size="sm" />
                            </div>
                            <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm text-gray-600">
                                <p>Konselor: <span class="text-gray-900">{{ item.booking.counselor?.name }}</span></p>
                                <p>Layanan: <span class="text-gray-900 capitalize">{{ item.booking.service_type }} {{ item.booking.duration_minutes }}m</span></p>
                                <p>Jadwal Awal: <span class="text-gray-900">{{ formatDate(item.old_schedule_start) }}</span></p>
                                <p>Usulan Klien: <span class="text-gray-900">{{ formatDate(item.new_schedule_start) }}</span></p>
                                <p>Nominal: <span class="font-bold text-gray-900">{{ formatCurrency(item.booking.price_at_booking) }}</span></p>
                                <p>Pengaju: <span class="text-gray-900">{{ item.requester?.name || '-' }}</span></p>
                            </div>

                            <div class="mt-3 rounded-md bg-gray-50 border border-gray-200 p-3 text-sm text-gray-700">
                                <span class="font-semibold">Alasan:</span> {{ item.reason || '-' }}
                            </div>

                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Jadwal Pengganti Final</label>
                                    <input
                                        :value="rows[item.id]?.new_schedule_start ?? item.new_schedule_start?.slice(0, 16)"
                                        type="datetime-local"
                                        class="w-full rounded-lg border-gray-300 text-slate-800 text-sm font-semibold shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2.5 px-3 bg-white"
                                        @input="initRow(item.id, item.new_schedule_start); rows[item.id].new_schedule_start = ($event.target as HTMLInputElement).value"
                                    >
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Catatan Admin</label>
                                    <textarea
                                        :value="rows[item.id]?.admin_notes ?? ''"
                                        rows="2"
                                        maxlength="500"
                                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                        placeholder="Catatan internal/ke klien"
                                        @input="initRow(item.id, item.new_schedule_start); rows[item.id].admin_notes = ($event.target as HTMLTextAreaElement).value"
                                    ></textarea>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <PrimaryButton @click="approve(item)">Setujui Reschedule</PrimaryButton>
                                <PrimaryButton variant="danger" @click="reject(item)">Tolak Reschedule</PrimaryButton>
                            </div>

                            <p v-if="errors.new_schedule_start" class="mt-2 text-xs text-red-600">{{ errors.new_schedule_start }}</p>
                            <p v-if="errors.admin_notes" class="mt-1 text-xs text-red-600">{{ errors.admin_notes }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
