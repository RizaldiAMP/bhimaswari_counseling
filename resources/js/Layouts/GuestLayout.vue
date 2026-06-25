<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Menu, X, Instagram, Linkedin, MapPin, Phone, Mail, Brain } from 'lucide-vue-next';

const isMobileMenuOpen = ref(false);

const navigation = [
    { name: 'Beranda', href: route('landing') },
    { name: 'Layanan', href: route('booking.public') },
    { name: 'Tim', href: route('team') },
];
</script>

<template>
    <div class="min-h-screen bg-[#fafafa] font-sans text-gray-900 flex flex-col selection:bg-primary selection:text-white">
        <!-- Navigation -->
        <nav class="bg-white/70 backdrop-blur-xl sticky top-0 z-50 border-b border-white/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <div class="flex items-center shrink-0">
                        <Link :href="route('landing')" class="flex items-center gap-2 group">
                            <img src="/images/logo.webp" alt="Bhimaswari Logo" class="h-10 w-auto" />
                            <span class="font-bold text-2xl tracking-tight">Bhimaswari<span class="text-primary">.id</span></span>
                        </Link>
                    </div>
                    
                    <!-- Desktop Menu -->
                    <div class="hidden md:flex md:items-center md:gap-8 absolute left-1/2 transform -translate-x-1/2">
                        <Link 
                            v-for="item in navigation" 
                            :key="item.name" 
                            :href="item.href"
                            class="text-slate-700 hover:text-primary px-3 py-2 text-sm font-semibold transition-colors"
                        >
                            {{ item.name }}
                        </Link>
                    </div>

                    <!-- Right side buttons -->
                    <div class="hidden md:flex md:items-center gap-3 shrink-0">
                        <Link 
                            v-if="$page.props.auth?.user"
                            :href="route('dashboard')"
                            class="btn btn-ghost btn-sm text-primary"
                        >
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link 
                                :href="route('login')" 
                                class="rounded-full px-5 py-2.5 text-sm font-bold transition-all"
                                :class="route().current('login')
                                    ? 'bg-primary text-white shadow-md shadow-primary/25 hover:opacity-90'
                                    : 'border border-slate-200 bg-white text-slate-700 hover:border-primary hover:text-primary'"
                            >
                                Masuk
                            </Link>
                            <Link 
                                :href="route('register')" 
                                class="rounded-full px-5 py-2.5 text-sm font-bold transition-all"
                                :class="route().current('register') || (!route().current('login') && !route().current('register'))
                                    ? 'bg-primary text-white shadow-md shadow-primary/25 hover:opacity-90'
                                    : 'border border-slate-200 bg-white text-slate-700 hover:border-primary hover:text-primary'"
                            >
                                Daftar
                            </Link>
                        </template>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="flex items-center md:hidden shrink-0">
                        <button 
                            @click="isMobileMenuOpen = !isMobileMenuOpen" 
                            type="button" 
                            class="btn btn-ghost btn-circle"
                        >
                            <span class="sr-only">Open main menu</span>
                            <X v-if="isMobileMenuOpen" class="w-6 h-6" />
                            <Menu v-else class="w-6 h-6" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div v-show="isMobileMenuOpen" class="md:hidden bg-base-100 border-t border-base-200 shadow-xl absolute w-full">
                <div class="p-4 space-y-2">
                    <Link 
                        v-for="item in navigation" 
                        :key="item.name" 
                        :href="item.href"
                        class="btn btn-ghost w-full justify-start text-base"
                    >
                        {{ item.name }}
                    </Link>
                </div>
                <div class="p-4 border-t border-base-200">
                    <div v-if="$page.props.auth?.user" class="space-y-2">
                         <Link 
                            :href="route('dashboard')"
                            class="btn btn-ghost w-full justify-start text-primary text-base"
                        >
                            Dashboard
                        </Link>
                    </div>
                    <div v-else class="flex flex-col gap-3">
                        <Link 
                            :href="route('login')" 
                            class="w-full rounded-full px-5 py-2.5 text-sm font-bold transition-all"
                            :class="route().current('login')
                                ? 'bg-primary border-primary text-white hover:opacity-90'
                                : 'border border-slate-200 bg-white text-slate-700 hover:border-primary hover:text-primary'"
                        >
                            Masuk
                        </Link>
                        <Link 
                            :href="route('register')" 
                            class="w-full rounded-full px-5 py-2.5 text-sm font-bold transition-all"
                            :class="route().current('register') || (!route().current('login') && !route().current('register'))
                                ? 'bg-primary border-primary text-white hover:opacity-90'
                                : 'border border-slate-200 bg-white text-slate-700 hover:border-primary hover:text-primary'"
                        >
                            Daftar
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-grow w-full overflow-hidden">
            <slot />
        </main>

        <!-- Footer Background section that matches image -->
        <footer class="bg-neutral text-neutral-content">
            <div class="max-w-7xl mx-auto py-16 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
                    <div class="md:col-span-1">
                        <div class="flex items-center gap-2 mb-6">
                            <span class="font-bold text-2xl tracking-tight text-white">bhimaswari.<span class="text-primary">id</span></span>
                        </div>
                        <p class="text-neutral-content/70 text-sm leading-relaxed mb-6">
                            Platform layanan konseling online dan offline profesional untuk kesehatan mental Anda.
                        </p>
                        <div class="flex gap-4 items-center">
                            <!-- Social Icons using Lucide -->
                            <a href="#" class="btn btn-circle btn-sm btn-ghost bg-white/10 hover:bg-primary text-white border-0">
                                <Instagram class="w-4 h-4" />
                            </a>
                            <a href="#" class="btn btn-circle btn-sm btn-ghost bg-white/10 hover:bg-primary text-white border-0">
                                <Linkedin class="w-4 h-4" />
                            </a>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-semibold text-white tracking-wider uppercase mb-6">Layanan</h3>
                        <ul class="space-y-3 shrink-0">
                            <li><Link :href="route('booking.public')" class="link link-hover text-sm text-neutral-content/80">Konseling Psikolog</Link></li>
                            <li><Link :href="route('booking.public')" class="link link-hover text-sm text-neutral-content/80">Konseling Konselor</Link></li>
                            <li><Link :href="route('booking.public')" class="link link-hover text-sm text-neutral-content/80">Psikoedukasi</Link></li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-semibold text-white tracking-wider uppercase mb-6">Perusahaan</h3>
                        <ul class="space-y-3">
                            <li><Link :href="route('team')" class="link link-hover text-sm text-neutral-content/80">Tim Kami</Link></li>
                            <li><Link :href="route('terms')" class="link link-hover text-sm text-neutral-content/80">Syarat & Ketentuan</Link></li>
                            <li><Link :href="route('privacy')" class="link link-hover text-sm text-neutral-content/80">Kebijakan Privasi</Link></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-white tracking-wider uppercase mb-6">Hubungi Kami</h3>
                        <ul class="space-y-4 text-sm text-neutral-content/70">
                            <li class="flex items-start gap-3">
                                <MapPin class="w-5 h-5 shrink-0 mt-0.5 opacity-80" />
                                <span>Jakarta, Indonesia</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <Phone class="w-5 h-5 shrink-0 opacity-80" />
                                0823-1146-7657
                            </li>
                            <li class="flex items-center gap-3">
                                <Mail class="w-5 h-5 shrink-0 opacity-80" />
                                bhimaswarifamily@gmail.com
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="mt-16 pt-8 border-t border-white/10 flex flex-col md:flex-row items-center justify-between text-sm text-neutral-content/50 gap-4">
                    <p>
                        &copy; {{ new Date().getFullYear() }} Bhimaswari. Hak cipta dilindungi.
                    </p>
                    <div class="flex gap-6">
                        <Link :href="route('privacy')" class="hover:text-white transition-colors">Kebijakan Privasi</Link>
                        <Link :href="route('terms')" class="hover:text-white transition-colors">Syarat dan Ketentuan</Link>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
