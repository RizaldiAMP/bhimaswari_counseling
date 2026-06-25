<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NotificationBell from '@/Components/ui/NotificationBell.vue';
import { getCounselorPhotoUrl } from '@/utils/counselorPhoto';

const isSidebarOpen = ref(false);

const user = computed(() => usePage().props.auth.user);

const navigation = computed(() => {
    const role = user.value?.role;
    
    if (role === 'admin') {
        return [
            { name: 'Dashboard', href: route('admin.dashboard'), icon: 'layout-dashboard', active: route().current('admin.dashboard') },
            { name: 'Verifikasi Pembayaran', href: route('admin.verifications.index'), icon: 'receipt', active: route().current('admin.verifications.*') },
            { name: 'Reschedule', href: route('admin.reschedules.index'), icon: 'calendar-clock', active: route().current('admin.reschedules.*') },
            { name: 'Monitoring Sesi Chat', href: route('admin.sessions.index'), icon: 'monitor', active: route().current('admin.sessions.*') },
            { name: 'Rekap Sesi', href: route('admin.rekap.index'), icon: 'clipboard-list', active: route().current('admin.rekap.*') },
            { name: 'Konselor', href: route('admin.counselors.index'), icon: 'users', active: route().current('admin.counselors.*') },
            { name: 'Klien', href: route('admin.clients.index'), icon: 'user-search', active: route().current('admin.clients.*') },
            { name: 'Harga Layanan', href: route('admin.prices.index'), icon: 'tags', active: route().current('admin.prices.*') },
            { name: 'Testimoni', href: route('admin.testimonials.index'), icon: 'message-square', active: route().current('admin.testimonials.*') },
        ];
    } else if (role === 'counselor') {
         return [
            { name: 'Dashboard', href: route('counselor.dashboard'), icon: 'layout-dashboard', active: route().current('counselor.dashboard') },
            { name: 'Ketersediaan Jadwal', href: route('counselor.availability.index'), icon: 'calendar', active: route().current('counselor.availability.*') },
            { name: 'Ruang Chat', href: route('counselor.chat.index'), icon: 'message-square', active: route().current('counselor.chat.*') },
            { name: 'Sesi Online', href: route('counselor.sessions.online'), icon: 'video', active: route().current('counselor.sessions.online') },
            { name: 'Sesi Offline', href: route('counselor.sessions.offline'), icon: 'users', active: route().current('counselor.sessions.offline') },
            { name: 'Daftar Booking', href: route('counselor.bookings.index'), icon: 'calendar-check', active: route().current('counselor.bookings.*') },
            { name: 'Profil Publik', href: route('counselor.profile.edit'), icon: 'user-circle', active: route().current('counselor.profile.*') },
        ];
    } else {
         return [
            { name: 'Home', href: route('client.dashboard'), icon: 'home', active: route().current('client.dashboard') },
            { name: 'Booking Baru', href: route('client.bookings.create'), icon: 'calendar-plus', active: route().current('client.bookings.create') },
            { name: 'Sesi Chat', href: route('client.chat.index'), icon: 'message-square', active: route().current('client.chat.*') },
            { name: 'Sesi Online', href: route('client.sessions.online'), icon: 'video', active: route().current('client.sessions.online') },
            { name: 'Sesi Offline', href: route('client.sessions.offline'), icon: 'users', active: route().current('client.sessions.offline') },
            { name: 'Riwayat Booking', href: route('client.bookings.index'), icon: 'history', active: route().current('client.bookings.index') || route().current('client.bookings.show') },
        ];
    }
});

const counselorPhotoUrl = computed(() => usePage().props.auth.counselor_photo_url as string | null);

const defaultAvatar = computed(() => {
    return getCounselorPhotoUrl(
        counselorPhotoUrl.value,
        null,
        user.value?.name || 'User'
    );
});

const roleLabel = computed(() => {
    switch (user.value?.role) {
        case 'admin': return 'Administrator';
        case 'counselor': return 'Konselor / Psikolog';
        default: return 'Klien';
    }
});

const dashboardRoute = computed(() => {
    switch (user.value?.role) {
        case 'admin': return route('admin.dashboard');
        case 'counselor': return route('counselor.dashboard');
        default: return route('client.dashboard');
    }
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex">
        
        <!-- Mobile Sidebar Overlay -->
        <div v-show="isSidebarOpen" class="fixed inset-0 z-40 lg:hidden" @click="isSidebarOpen = false">
            <div class="fixed inset-0 bg-gray-600 bg-opacity-75 transition-opacity"></div>
        </div>

        <!-- Sidebar -->
        <div 
            :class="[
                isSidebarOpen ? 'translate-x-0' : '-translate-x-full',
                'fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0'
            ]"
        >
            <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200 bg-white">
                <Link :href="dashboardRoute" class="flex items-center gap-2">
                    <img src="/images/logo.webp" alt="Bhimaswari Logo" class="h-8 w-auto" />
                    <span class="font-bold text-xl text-gray-900">Bhimaswari</span>
                </Link>
                <button @click="isSidebarOpen = false" class="lg:hidden text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <div class="h-[calc(100vh-4rem)] overflow-y-auto w-full flex flex-col justify-between">
                <!-- Nav Links -->
                <nav class="p-4 space-y-1">
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 px-3 mt-4">Main Menu</div>
                    <Link 
                        v-for="item in navigation" 
                        :key="item.name" 
                        :href="item.href"
                        :class="[
                            item.active ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                            'group flex items-center px-3 py-2.5 text-sm font-medium rounded-md transition-colors'
                        ]"
                    >
                        <!-- Icons placeholder based on text mapping (will add actual icons later) -->
                        <div class="mr-3 shrink-0 h-5 w-5" :class="item.active ? 'text-primary-600' : 'text-gray-400 group-hover:text-gray-500'">
                           <!-- Default generic icon if specific not loaded -->
                           <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                        </div>
                        {{ item.name }}
                    </Link>
                </nav>

                <!-- Sidebar Footer (Profile Snippet) -->
                <div class="p-4 border-t border-gray-200">
                    <div class="flex items-center gap-3">
                        <img :src="defaultAvatar" alt="Avatar" class="h-10 w-10 rounded-full bg-gray-100 border border-gray-200" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ user?.name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ roleLabel }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            
            <!-- Top Header -->
            <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8 z-10 sticky top-0 shrink-0" style="background-color: #ffffff !important; z-index: 9999 !important; transform: translateZ(0); -webkit-transform: translateZ(0);">
                <div class="flex items-center">
                    <button @click="isSidebarOpen = true" class="lg:hidden mr-4 text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                    </button>
                    <!-- Slot for page specific header/breadcrumb -->
                    <slot name="header">
                        <h2 class="font-semibold text-lg text-gray-800 leading-tight">Overview</h2>
                    </slot>
                </div>

                <div class="flex items-center gap-4">
                    <NotificationBell />

                    <!-- User Dropdown -->
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-gray-700 transition">
                                <span class="hidden sm:inline-block">{{ user?.name }}</span>
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </template>

                        <template #content>
                            <div class="px-4 py-3 border-b border-gray-100 sm:hidden">
                                <p class="text-sm text-gray-900 font-medium">{{ user?.name }}</p>
                                <p class="text-xs text-gray-500 truncate mt-0.5">{{ user?.email }}</p>
                            </div>
                            <DropdownLink :href="route('profile.edit')">
                                Pengaturan Akun
                            </DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button" class="text-red-600 focus:text-red-700 hover:text-red-700 hover:bg-red-50">
                                Keluar
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 focus:outline-none">
                <div class="py-8">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <slot />
                    </div>
                </div>
            </main>
        </div>
        
    </div>
</template>
