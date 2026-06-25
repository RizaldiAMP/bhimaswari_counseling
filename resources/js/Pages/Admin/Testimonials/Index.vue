<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Star, MessageSquare, Copy, Eye, EyeOff, Trash2, CheckCircle } from 'lucide-vue-next';
import { ref } from 'vue';
import Pagination from '@/Components/ui/Pagination.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';

interface Testimonial {
    id: number;
    client_name: string;
    content: string;
    rating: number;
    is_visible: boolean;
    created_at: string;
    booking?: {
        counselor?: {
            name: string;
        }
    }
}

const props = defineProps<{
    testimonials: {
        data: Testimonial[];
        links: any[];
    };
}>();

const toggleVisibility = (id: number) => {
    router.post(route('admin.testimonials.toggle', id), {}, {
        preserveScroll: true,
    });
};

const deleteTestimonial = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus testimoni ini? Tindakan ini tidak bisa dibatalkan.')) {
        router.delete(route('admin.testimonials.destroy', id), {
            preserveScroll: true,
        });
    }
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleString('id-ID', {
        year: 'numeric', month: 'long', day: 'numeric',
    });
};

const copySuccess = ref(false);

const copyLink = () => {
    try {
        const link = route('public.testimonials.create');
        
        // Use modern clipboard API if available
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(link);
        } else {
            // Fallback for non-secure contexts
            const textArea = document.createElement("textarea");
            textArea.value = link;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            document.execCommand('copy');
            textArea.remove();
        }

        copySuccess.value = true;
        setTimeout(() => {
            copySuccess.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy text: ', err);
    }
};
</script>

<template>
    <Head title="Kelola Testimoni" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-black text-slate-900 leading-tight">Kelola Testimoni</h2>
                    <p class="text-sm text-slate-500 mt-1">Atur ulasan dari klien yang akan ditampilkan di Landing Page.</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            
            <!-- Link Copy Card -->
            <div class="bg-gradient-to-r from-primary-50 to-white rounded-2xl border border-primary-100 p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-primary-100 flex items-center justify-center shrink-0">
                            <MessageSquare class="w-6 h-6 text-primary-700" />
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-lg">Link Pengisian Ulasan</h3>
                            <p class="text-sm text-slate-500 mt-0.5">Bagikan link ini ke klien (via WhatsApp) agar mereka bisa mengisi testimoni.</p>
                        </div>
                    </div>
                    <button 
                        @click="copyLink"
                        class="flex items-center gap-2 px-6 py-3 bg-white border border-primary-200 hover:bg-primary-50 text-primary-700 rounded-xl font-bold shadow-sm transition-all whitespace-nowrap"
                    >
                        <CheckCircle v-if="copySuccess" class="w-5 h-5 text-green-500" />
                        <Copy v-else class="w-5 h-5" />
                        {{ copySuccess ? 'Berhasil Disalin!' : 'Salin Link Ulasan' }}
                    </button>
                </div>
            </div>

            <!-- List of Testimonials -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4">Klien & Rating</th>
                                <th class="px-6 py-4 w-1/3">Ulasan</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="t in testimonials.data" :key="t.id" class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-900">{{ t.client_name }}</p>
                                    <p class="text-xs text-slate-400 mb-2">{{ formatDate(t.created_at) }}</p>
                                    <div class="flex items-center gap-0.5">
                                        <Star v-for="s in 5" :key="s" class="w-4 h-4" :class="s <= t.rating ? 'fill-amber-400 text-amber-400' : 'fill-slate-100 text-slate-300'" />
                                    </div>
                                    <p v-if="t.booking?.counselor?.name" class="text-xs text-slate-500 mt-2">
                                        Konselor: <span class="font-semibold">{{ t.booking.counselor.name }}</span>
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-slate-600 line-clamp-4">{{ t.content }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold"
                                        :class="t.is_visible ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'"
                                    >
                                        {{ t.is_visible ? 'Ditampilkan' : 'Disembunyikan' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button 
                                            @click="toggleVisibility(t.id)" 
                                            class="p-2 rounded-lg transition-colors border"
                                            :class="t.is_visible ? 'bg-amber-50 text-amber-600 border-amber-200 hover:bg-amber-100' : 'bg-green-50 text-green-600 border-green-200 hover:bg-green-100'"
                                            :title="t.is_visible ? 'Sembunyikan dari Landing Page' : 'Tampilkan di Landing Page'"
                                        >
                                            <EyeOff v-if="t.is_visible" class="w-4 h-4" />
                                            <Eye v-else class="w-4 h-4" />
                                        </button>
                                        
                                        <button 
                                            @click="deleteTestimonial(t.id)"
                                            class="p-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition-colors border border-red-200"
                                            title="Hapus"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="testimonials.data.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                    Belum ada testimoni yang masuk.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="testimonials.links.length > 3" class="px-6 py-4 border-t border-slate-200">
                    <Pagination :links="testimonials.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
