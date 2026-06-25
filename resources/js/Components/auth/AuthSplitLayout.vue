<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Link } from '@inertiajs/vue3';
import { Leaf } from 'lucide-vue-next';
import { ref, onMounted, onUnmounted } from 'vue';

withDefaults(defineProps<{
    heroBadge?: string;
    heroTitle: string;
    heroDescription: string;
    formTitle: string;
    formDescription: string;
}>(), {
    heroBadge: '',
});

const heroImages = [
    '/images/loginregister.webp',
    '/images/loginregister1.webp'
];

const currentImageIndex = ref(0);
let intervalId: any = null;

onMounted(() => {
    intervalId = setInterval(() => {
        currentImageIndex.value = (currentImageIndex.value + 1) % heroImages.length;
    }, 5000); // 5 seconds
});

onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
});
</script>

<template>
    <GuestLayout>
        <section
            class="relative isolate overflow-hidden bg-[radial-gradient(circle_at_top_right,_rgba(130,62,135,0.18),_transparent_42%),radial-gradient(circle_at_bottom_left,_rgba(130,62,135,0.12),_transparent_38%)] py-10 lg:py-14"
        >
            <div class="mx-auto w-full max-w-6xl px-0 lg:px-4">
                <div class="auth-card grid w-full overflow-hidden bg-white shadow-2xl shadow-primary/10 lg:rounded-3xl lg:border lg:border-primary/10 lg:grid-cols-2">
                    <aside
                        class="relative hidden overflow-hidden bg-slate-900 text-white lg:block"
                    >
                        <!-- Background Image Carousel -->
                        <transition-group name="fade" tag="div">
                            <div 
                                v-for="(image, index) in heroImages" 
                                :key="image"
                                v-show="index === currentImageIndex"
                                class="absolute inset-0 h-full w-full"
                            >
                                <img :src="image" alt="Background" class="h-full w-full object-cover" />
                            </div>
                        </transition-group>
                        
                        <!-- Gradient for text readability at bottom -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent"></div>

                        <!-- Content at bottom -->
                        <div class="relative z-10 flex h-full flex-col justify-end p-8 sm:p-10 lg:p-12 pb-16">
                            <div>
                                <p
                                    v-if="heroBadge"
                                    class="inline-flex items-center rounded-full border border-white/30 bg-white/20 px-4 py-1 text-xs font-semibold uppercase tracking-[0.16em] backdrop-blur-md mb-6"
                                >
                                    {{ heroBadge }}
                                </p>
                                <div class="mb-5 flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/20 backdrop-blur-md shadow-sm">
                                        <Leaf class="h-5 w-5" />
                                    </div>
                                    <h2 class="text-2xl font-bold tracking-tight drop-shadow-md">Bhimaswari</h2>
                                </div>
                                <h1 class="mb-4 text-4xl font-extrabold leading-tight text-white drop-shadow-md">{{ heroTitle }}</h1>
                                <p class="text-lg font-medium text-white/95 drop-shadow-md">{{ heroDescription }}</p>
                            </div>

                            <div v-if="$slots.leftExtras" class="mt-8">
                                <slot name="leftExtras" />
                            </div>
                        </div>
                    </aside>

                    <div class="flex w-full flex-col justify-center px-6 py-10 sm:px-8 lg:px-14 lg:py-12">
                        <div class="mb-8 lg:hidden">
                            <div class="flex items-center gap-2 text-primary">
                                <Leaf class="h-7 w-7" />
                                <span class="text-xl font-bold tracking-tight text-slate-900">Bhimaswari</span>
                            </div>
                        </div>

                        <div class="mb-7">
                            <h2 class="text-3xl font-extrabold text-slate-900">{{ formTitle }}</h2>
                            <p class="mt-2 text-slate-500">{{ formDescription }}</p>
                        </div>

                        <slot />

                        <div class="mt-auto flex justify-center gap-6 pt-8 text-[10px] font-semibold uppercase tracking-widest text-slate-400">
                            <Link :href="route('privacy')" class="transition-colors hover:text-primary">Kebijakan Privasi</Link>
                            <Link :href="route('terms')" class="transition-colors hover:text-primary">Syarat &amp; Ketentuan</Link>
                            <Link :href="route('landing')" class="transition-colors hover:text-primary">Bantuan</Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </GuestLayout>
</template>

<style scoped>
@keyframes authCardIn {
    from {
        opacity: 0;
        transform: translateY(14px) scale(0.985);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.auth-card {
    animation: authCardIn 520ms cubic-bezier(0.22, 1, 0.36, 1);
}

@media (prefers-reduced-motion: reduce) {
    .auth-card {
        animation: none;
    }
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 1.5s ease-in-out;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
