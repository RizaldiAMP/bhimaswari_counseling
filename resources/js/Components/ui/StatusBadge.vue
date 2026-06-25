<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: string;
}>();

const config = computed(() => {
    switch (props.status.toLowerCase()) {
        // Payment & Booking Statuses
        case 'pending_payment':
            return { color: 'bg-yellow-100 text-yellow-800 border-yellow-200', label: 'Menunggu Bukti Pembayaran' };
        case 'pending_verification':
            return { color: 'bg-blue-100 text-blue-800 border-blue-200', label: 'Menunggu Verifikasi Admin' };
        case 'confirmed':
            return { color: 'bg-green-100 text-green-800 border-green-200', label: 'Dikonfirmasi' };
        case 'in_session':
            return { color: 'bg-primary-100 text-primary-800 border-primary-200', label: 'Sesi Berjalan' };
        case 'completed':
            return { color: 'bg-gray-100 text-gray-800 border-gray-200', label: 'Selesai' };
        case 'pending_reschedule':
            return { color: 'bg-orange-100 text-orange-800 border-orange-200', label: 'Menunggu Reschedule' };
        case 'cancelled':
            return { color: 'bg-red-100 text-red-800 border-red-200', label: 'Dibatalkan' };
        case 'expired':
            return { color: 'bg-red-100 text-red-800 border-red-200', label: 'Kadaluarsa' };
            
        // Payment specific
        case 'rejected':
            return { color: 'bg-red-100 text-red-800 border-red-200', label: 'Ditolak (Upload Ulang)' };
        case 'approved':
            return { color: 'bg-green-100 text-green-800 border-green-200', label: 'Disetujui' };

        default:
            return { color: 'bg-gray-100 text-gray-800 border-gray-200', label: props.status };
    }
});
</script>

<template>
    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border', config.color]">
        {{ config.label }}
    </span>
</template>
