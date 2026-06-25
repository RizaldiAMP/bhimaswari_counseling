<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ArrowLeft, Star, Send } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    bookingId?: number;
    counselorName?: string;
}>();

const form = useForm({
    booking_id: props.bookingId || null,
    rating: 5,
    content: '',
});

const hoveredRating = ref(0);

const setRating = (val: number) => {
    form.rating = val;
};

const submit = () => {
    form.post(route('client.testimonials.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Berikan Ulasan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('client.dashboard')" class="text-gray-500 hover:text-gray-700 font-bold">
                    <ArrowLeft class="w-5 h-5" />
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Berikan Ulasan
                </h2>
            </div>
        </template>

        <div class="py-12 bg-slate-50 min-h-screen">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-[2rem] border border-slate-200 shadow-xl shadow-slate-100 p-8 sm:p-12 text-center relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-bl from-primary/10 to-transparent rounded-bl-[4rem]"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-amber-50 to-transparent rounded-tr-[3rem]"></div>
                    
                    <div class="relative z-10">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-primary/10 text-primary mb-6 shadow-sm border border-primary/20">
                            <Star class="w-10 h-10 fill-primary" />
                        </div>
                        
                        <h3 class="font-black text-2xl text-slate-900 mb-2">Bagaimana Pengalaman Anda?</h3>
                        <p class="text-slate-500 text-sm mb-8 max-w-md mx-auto">
                            <span v-if="counselorName">Ceritakan pengalaman konseling Anda bersama <strong>{{ counselorName }}</strong>.</span>
                            <span v-else>Ceritakan pengalaman Anda menggunakan layanan kami.</span>
                            Ulasan Anda sangat berarti bagi kami dan klien lainnya.
                        </p>

                        <form @submit.prevent="submit" class="space-y-8 text-left">
                            
                            <!-- Rating System -->
                            <div class="flex flex-col items-center justify-center space-y-4">
                                <label class="block text-sm font-bold text-slate-700">Pilih Penilaian Anda</label>
                                <div class="flex gap-2">
                                    <button 
                                        type="button"
                                        v-for="star in 5" 
                                        :key="star"
                                        @click="setRating(star)"
                                        @mouseenter="hoveredRating = star"
                                        @mouseleave="hoveredRating = 0"
                                        class="p-2 transition-transform hover:scale-110 focus:outline-none"
                                    >
                                        <Star 
                                            class="w-10 h-10 transition-colors duration-200"
                                            :class="(hoveredRating ? star <= hoveredRating : star <= form.rating) ? 'fill-amber-400 text-amber-400' : 'fill-slate-100 text-slate-300'"
                                        />
                                    </button>
                                </div>
                                <p class="text-xs font-bold text-amber-600 bg-amber-50 px-3 py-1 rounded-full uppercase tracking-widest">
                                    {{ ['Sangat Kurang', 'Kurang', 'Cukup', 'Baik', 'Sangat Baik'][form.rating - 1] }}
                                </p>
                            </div>

                            <!-- Review Text -->
                            <div>
                                <label for="content" class="block text-sm font-bold text-slate-700 mb-2">Ceritakan Pengalaman Anda</label>
                                <textarea
                                    id="content"
                                    v-model="form.content"
                                    rows="5"
                                    class="block w-full rounded-2xl border-slate-200 bg-slate-50 focus:border-primary focus:ring focus:ring-primary/20 transition-all text-sm text-slate-900 p-4"
                                    placeholder="Konselor sangat ramah dan mendengarkan dengan baik..."
                                    required
                                    maxlength="1000"
                                ></textarea>
                                <div class="flex justify-between items-center mt-2">
                                    <p v-if="form.errors.content" class="text-xs text-red-500 font-bold">{{ form.errors.content }}</p>
                                    <p v-else class="text-xs text-slate-400"></p>
                                    <span class="text-xs text-slate-400 font-medium">{{ form.content.length }}/1000</span>
                                </div>
                            </div>

                            <button 
                                type="submit" 
                                class="w-full px-6 py-4 bg-primary hover:bg-primary-700 text-white rounded-xl text-base font-bold transition-all shadow-lg flex items-center justify-center gap-2"
                                :class="{'opacity-50 cursor-not-allowed': form.processing}"
                                :disabled="form.processing"
                            >
                                <Send class="w-5 h-5" />
                                {{ form.processing ? 'Mengirim...' : 'Kirim Ulasan' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
