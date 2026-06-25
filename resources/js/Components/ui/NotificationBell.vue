<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface AppNotification {
    id: string;
    read_at: string | null;
    created_at: string;
    title: string;
    message: string;
    url: string | null;
}

const page = usePage();
const isOpen = ref(false);

const unreadCount = computed<number>(() => Number(page.props.notifications?.unread_count ?? 0));
const notifications = computed<AppNotification[]>(() => (page.props.notifications?.items ?? []) as AppNotification[]);

const markAndGo = (notification: AppNotification) => {
    router.post(route('notifications.read', { notification: notification.id }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            if (notification.url) {
                router.visit(notification.url);
            }
        },
    });
};

const markAllRead = () => {
    router.post(route('notifications.read_all'), {}, { preserveScroll: true });
};

const formatTime = (dateString: string) =>
    new Date(dateString).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
</script>

<template>
    <div class="relative">
        <button
            class="relative rounded-full p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
            @click="isOpen = !isOpen"
        >
            <span class="sr-only">Lihat notifikasi</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>

            <span
                v-if="unreadCount > 0"
                class="absolute -right-0.5 -top-0.5 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <div v-if="isOpen" class="fixed inset-0 z-40" @click="isOpen = false"></div>

        <div v-if="isOpen" class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                <p class="text-sm font-semibold text-gray-900">Notifikasi</p>
                <button
                    v-if="unreadCount > 0"
                    type="button"
                    class="text-xs font-medium text-primary-600 hover:text-primary-700"
                    @click="markAllRead"
                >
                    Tandai semua dibaca
                </button>
            </div>

            <div v-if="notifications.length === 0" class="px-4 py-6 text-center text-sm text-gray-500">
                Belum ada notifikasi.
            </div>

            <div v-else class="max-h-96 overflow-y-auto">
                <button
                    v-for="item in notifications"
                    :key="item.id"
                    type="button"
                    class="w-full border-b border-gray-100 px-4 py-3 text-left transition-colors last:border-b-0 hover:bg-gray-50"
                    :class="item.read_at ? 'bg-white' : 'bg-primary-50/40'"
                    @click="markAndGo(item)"
                >
                    <p class="text-sm font-medium text-gray-900">{{ item.title }}</p>
                    <p class="mt-1 text-xs text-gray-600">{{ item.message }}</p>
                    <p class="mt-1 text-[11px] text-gray-400">{{ formatTime(item.created_at) }}</p>
                </button>
            </div>

            <div class="border-t border-gray-100 bg-gray-50 px-4 py-2 text-right">
                <button type="button" class="text-xs text-gray-500 hover:text-gray-700" @click="isOpen = false">Tutup</button>
            </div>
        </div>
    </div>
</template>
