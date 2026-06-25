<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import CounselorCard from '@/Components/counselor/CounselorCard.vue';

interface Counselor {
    id: number;
    user_id: number;
    practitioner_type: 'psychologist' | 'counselor';
    sipp_number: string | null;
    bio: string | null;
    photo_path: string | null;
    photo_url: string | null;
    full_title?: string | null;
    specializations: string[] | null;
    user?: {
        name: string;
    }
}

const props = defineProps<{
    counselors: Counselor[];
}>();

// Same fallback data as Landing page for photo + bio integration
const fallbackCounselors = [
    { name: 'Aisyah Tri Wardhani, S. Psi., Psikolog', role: 'Psikolog Klinis', photo: '/images/aisyah_tri_wardhani.webp' },
    { name: 'Bagas Alam, S. Psi., M. Psi., Psikolog', role: 'Psikolog Klinis', photo: '/images/bagas_alam.webp' },
    { name: 'Ghazali Fauzia, S. Psi., M. Psi., Psikolog', role: 'Psikolog Klinis', photo: '/images/ghazali_fauzia.webp' },
    { name: 'Ghina Ciptadewi, S. Psi., Psikolog', role: 'Psikolog', photo: '/images/ghina_ciptadewi.webp' },
    { name: 'Ifti Aisha, S. Psi., M. Psi., Psikolog', role: 'Psikolog Klinis', photo: '/images/ifti_aisha.webp' },
    { name: 'Joko Tri Hartanto, S. Psi., M. Psi., Psikolog', role: 'Psikolog Klinis', photo: '/images/joko_tri_hartanto.webp' },
    { name: 'Nadya Mubaraniz, S. Psi., Psikolog', role: 'Psikolog Klinis', photo: '/images/nadya_mubaraniz.webp' },
    { name: 'Nurul Nabila Annisa, S. Psi., Psikolog', role: 'Psikolog Klinis', photo: '/images/nurul_nabila_annisa.webp' },
    { name: 'Rizkie Alief Madani, S. Psi.', role: 'Konselor', photo: '/images/rizkie_alief_madani.webp' },
    { name: 'Shofura Hanifah, S. Psi., M. Psi., Psikolog', role: 'Psikolog Klinis', photo: '/images/shofura_hanifah.webp' },
    { name: 'Trya Dara Ruidahasi, S. Psi., M. Psi., Psikolog', role: 'Psikolog Klinis', photo: '/images/trya_dara_ruidahasi.webp' },
    { name: 'Winda Kusuma Ayu, S. Psi., Psikolog', role: 'Psikolog Klinis', photo: '/images/winda_kusuma_ayu.webp' },
];

const defaultBio = 'Praktisi kesehatan mental yang berdedikasi untuk mendampingi perjalanan pemulihan dan pertumbuhan diri Anda melalui sesi konseling yang aman dan suportif.';

function counselorPhoto(photoUrl: string | null, photoPath: string | null): string {
    if (photoUrl) return photoUrl;
    if (!photoPath) return '';
    if (photoPath.startsWith('http://') || photoPath.startsWith('https://')) return photoPath;
    if (photoPath.startsWith('/images/')) return photoPath;
    return `/storage/${photoPath}`;
}

const displayCounselors = computed(() => {
    if (!props.counselors.length) {
        return fallbackCounselors.map(f => ({
            id: f.name,
            name: f.name,
            role: f.role,
            photo: f.photo,
            bio: defaultBio,
            practitioner_type: f.role === 'Konselor' ? 'counselor' as const : 'psychologist' as const,
            sipp_number: null,
        }));
    }

    return props.counselors.map((counselor, index) => {
        const name = counselor.user?.name || '';
        // Match fallback based on whether the fallback name includes the DB name
        const matchedFallback = fallbackCounselors.find(f => f.name.toLowerCase().includes(name.toLowerCase()));
        const fallback = matchedFallback || fallbackCounselors[index % fallbackCounselors.length];

        // Use photo_url from DB, fallback to static /images/
        const resolvedPhoto = counselorPhoto(counselor.photo_url, counselor.photo_path) || fallback.photo;

        return {
            id: counselor.id,
            name: matchedFallback ? matchedFallback.name : (name && counselor.full_title ? `${name}, ${counselor.full_title}` : name || 'Konselor'),
            role: counselor.practitioner_type === 'psychologist' ? 'Psikolog Klinis' : 'Konselor',
            photo: resolvedPhoto,
            bio: counselor.bio || defaultBio,
            practitioner_type: counselor.practitioner_type,
            sipp_number: counselor.sipp_number,
        };
    });
});
</script>

<template>
    <Head title="Tim Kami" />

    <GuestLayout>
        <div class="py-12 bg-gradient-to-b from-primary-50/30 to-transparent min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl">
                        Tim Konselor & Psikolog
                    </h1>
                    <p class="mt-4 text-xl text-gray-600 max-w-3xl mx-auto">
                        Kenali praktisi Bhimaswari.id yang siap mendampingi proses pemulihanmu. Profil tim ditampilkan dinamis dari data konselor terverifikasi.
                    </p>
                </div>

                <div v-if="displayCounselors.length === 0" class="text-center text-gray-500 italic py-12">
                    Data profil tim sedang diperbarui.
                </div>

                <div class="flex flex-col gap-5">
                    <div v-for="person in displayCounselors" :key="person.id">
                        <CounselorCard
                            :name="person.name"
                            :role="person.role"
                            :photo="person.photo"
                            :bio="person.bio"
                            :practitioner-type="person.practitioner_type"
                            :sipp-number="person.sipp_number"
                        />
                    </div>
                </div>

                <div class="mt-16 rounded-[2rem] border border-slate-100 bg-white p-8 shadow-sm">
                    <h3 class="mb-3 text-xl font-bold text-gray-900">Standar Tim Profesional</h3>
                    <p class="text-sm leading-relaxed text-gray-600">
                        Tim terdiri dari psikolog dan konselor dengan pendekatan empatik, menjaga kerahasiaan klien, serta bekerja sesuai alur layanan Bhimaswari.id yang aman dan terjadwal.
                    </p>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
