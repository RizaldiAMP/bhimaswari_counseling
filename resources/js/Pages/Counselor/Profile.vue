<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/ui/PrimaryButton.vue';
import { ref } from 'vue';
import { getCounselorPhotoUrl, getCounselorFallbackSpecializations, DEFAULT_COUNSELOR_BIO } from '@/utils/counselorPhoto';

interface Profile {
    id: number;
    bio: string;
    photo_path: string | null;
    photo_url: string | null;
    specializations: string[] | null;
    practitioner_type: 'psychologist' | 'counselor';
    sipp_number: string | null;
    is_visible: boolean;
}

const props = defineProps<{
    profile: Profile;
}>();

const newSpec = ref('');

const user = usePage().props.auth.user;

const form = useForm({
    bio: props.profile.bio || DEFAULT_COUNSELOR_BIO,
    specializations: props.profile.specializations && props.profile.specializations.length > 0
        ? props.profile.specializations
        : getCounselorFallbackSpecializations(user?.name || ''),
    photo: null as File | null,
});

const previewPhoto = ref<string | null>(
    getCounselorPhotoUrl(props.profile.photo_url, props.profile.photo_path, user?.name || '')
);
const photoInput = ref<HTMLInputElement | null>(null);

const addSpec = () => {
    const val = newSpec.value.trim();
    if (val && !form.specializations.includes(val)) {
        form.specializations.push(val);
    }
    newSpec.value = '';
};

const removeSpec = (index: number) => {
    form.specializations.splice(index, 1);
};

const selectNewPhoto = (e: Event) => {
    const input = e.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        const file = input.files[0];
        form.photo = file;
        
        const reader = new FileReader();
        reader.onload = (e) => {
            previewPhoto.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const triggerPhotoSelect = () => {
    photoInput.value?.click();
};

const submit = () => {
    // We use router.post instead of form.put because of file uploads in Inertia
    router.post(route('counselor.profile.update'), {
        _method: 'post',
        bio: form.bio,
        specializations: form.specializations as any, // Typed as array
        photo: form.photo,
    }, {
        preserveScroll: true,
        onSuccess: () => { form.photo = null; if (photoInput.value) photoInput.value.value = ''; }
    });
};
</script>

<template>
    <Head title="Profil Publik" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pengaturan Profil Publik</h2>
        </template>

        <div class="max-w-3xl">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Data Profil</h3>
                        <p class="text-sm text-gray-600 mt-1 font-medium">Informasi ini akan ditampilkan ke klien di halaman Cari Konselor.</p>
                    </div>
                    <span :class="profile.is_visible ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium">
                        {{ profile.is_visible ? 'Visible (Klien dapat melihat)' : 'Hidden (Disembunyikan)' }}
                    </span>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-8">

                    <!-- Photo Section -->
                    <div class="flex items-center gap-6">
                        <div class="shrink-0 relative">
                            <div class="h-24 w-24 rounded-full overflow-hidden border-2 border-gray-200 bg-gray-100 flex items-center justify-center">
                                <img v-if="previewPhoto" :src="previewPhoto" alt="Avatar" class="h-full w-full object-cover" />
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                        </div>
                        <div>
                            <input type="file" ref="photoInput" @change="selectNewPhoto" accept="image/*" class="hidden" />
                            <PrimaryButton type="button" size="sm" variant="secondary" @click="triggerPhotoSelect">Pilih Foto Baru</PrimaryButton>
                            <p class="text-xs text-gray-600 mt-2 font-medium">Format JPG, PNG, atau WebP. Maks 2MB.</p>
                            <p v-if="form.errors.photo" class="text-red-500 text-xs mt-1">{{ form.errors.photo }}</p>
                        </div>
                    </div>

                    <!-- Readonly Info -->
                    <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <div>
                            <span class="block text-xs font-medium text-gray-500">Tipe Praktisi</span>
                            <span class="block text-sm font-semibold text-gray-900 mt-0.5">{{ profile.practitioner_type === 'psychologist' ? 'Psikolog Klinis' : 'Konselor' }}</span>
                        </div>
                        <div v-if="profile.practitioner_type === 'psychologist'">
                            <span class="block text-xs font-medium text-gray-500">Nomor SIPP</span>
                            <span class="block text-sm font-semibold text-gray-900 mt-0.5">{{ profile.sipp_number || 'Belum diisi (Hubungi Admin)' }}</span>
                        </div>
                    </div>

                    <!-- Bio -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Penjelasan Singkat (Bio)</label>
                        <textarea v-model="form.bio" rows="6" 
                                  class="w-full rounded-xl border-gray-300 text-gray-900 font-medium shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm p-4 leading-relaxed" 
                                  placeholder="Ceritakan latar belakang dan pendekatan konseling Anda..."></textarea>
                        <p v-if="form.errors.bio" class="text-red-500 text-xs mt-1">{{ form.errors.bio }}</p>
                    </div>

                    <!-- Specializations -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Spesialisasi Masalah</label>
                        
                        <div class="flex flex-wrap gap-2 mb-3 bg-gray-50 p-4 rounded-2xl border border-gray-100 min-h-[60px]">
                            <div v-for="(spec, index) in form.specializations" :key="index" class="inline-flex items-center gap-1.5 bg-primary-50 text-primary-700 border border-primary-200 px-3 py-1.5 rounded-full text-sm font-bold">
                                {{ spec }}
                                <button type="button" @click="removeSpec(index)" class="text-primary-400 hover:text-red-500 focus:outline-none rounded-full transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>
                                </button>
                            </div>
                            <div v-if="form.specializations.length === 0" class="text-sm text-gray-400 italic mt-0.5">Belum ada spesialisasi ditambahkan.</div>
                        </div>

                        <div class="flex w-full" style="max-width: 400px;">
                            <input v-model="newSpec" @keydown.enter.prevent="addSpec" type="text" 
                                   class="rounded-l-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm flex-1 p-3 text-gray-900 font-medium" 
                                   placeholder="Misal: Kecemasan, Karir..." />
                            <button type="button" @click="addSpec" 
                                    class="inline-flex items-center rounded-r-xl border border-l-0 border-gray-300 bg-gray-50 px-5 text-sm font-bold text-gray-700 hover:bg-gray-100 focus:outline-none transition-colors">Tambah</button>
                        </div>
                        <p v-if="form.errors.specializations" class="text-red-500 text-xs mt-1">{{ form.errors.specializations }}</p>
                        <p class="text-xs text-gray-600 mt-2 font-medium italic opacity-80">Tekan Enter atau klik Tambah untuk memasukkan ke dalam daftar.</p>
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex justify-end">
                        <PrimaryButton type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Simpan Perubahan Profil
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
