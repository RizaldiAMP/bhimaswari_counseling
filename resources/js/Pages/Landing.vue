<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import {
    ArrowRight,
    Brain,
    Briefcase,
    Calendar,
    Check,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Flame,
    Globe2,
    GraduationCap,
    Heart,
    Instagram,
    Library,
    Mail,
    Phone,
    Puzzle,
    Share2,
    ShieldCheck,
    Sparkles,
    Target,
    Users,
    X,
    Zap,
} from 'lucide-vue-next';
import Modal from '@/Components/Modal.vue';
import { getCounselorPhotoUrl } from '@/utils/counselorPhoto';

interface Counselor {
    id: number;
    user_id?: number;
    practitioner_type: 'psychologist' | 'counselor';
    photo_path: string | null;
    photo_url: string | null;
    bio?: string | null;
    specializations?: string[] | null;
    sipp_number?: string | null;
    user?: {
        id?: number;
        name: string;
    };
    counselor_profile?: {
        bio: string;
        specializations: string[];
        sipp_number: string | null;
    };
}

interface ServicePrice {
    id: number;
    service_type: 'chat' | 'online' | 'offline';
    practitioner_type: 'psychologist' | 'counselor';
    duration_minutes: number;
    price: number;
    is_active: boolean;
}

const props = defineProps<{
    counselors: Counselor[];
    services: ServicePrice[];
    testimonials?: any[];
    canLogin?: boolean;
    canRegister?: boolean;
}>();

const activePackage = ref<'chat' | 'online' | 'offline'>('chat');
const pageRef = ref<HTMLElement | null>(null);
const counselorsContainer = ref<HTMLElement | null>(null);

const selectedCounselor = ref<any>(null);
const showProfileModal = ref(false);
const modalStep = ref<'details' | 'booking'>('details');
const modalLoading = ref(false);
const modalSlots = ref<Record<string, any[]>>({});
const modalPrices = ref<any[]>([]);
const modalActiveServiceTab = ref<'chat' | 'online' | 'offline'>('chat');

const openProfile = (person: any) => {
    selectedCounselor.value = person;
    modalStep.value = 'details';
    showProfileModal.value = true;
};

const openBookingStep = () => {
    modalStep.value = 'booking';
};

const handlePackageClick = (servicePriceId: number | string) => {
    const params = new URLSearchParams({
        counselor_id: String(selectedCounselor.value.user_id || selectedCounselor.value.id || 1),
        service_price_id: String(servicePriceId),
        skip_to: '2',
    });
    window.location.href = `/booking?${params.toString()}`;
};

// Get all active prices for the current counselor's type
const counselorPackages = computed(() => {
    if (!selectedCounselor.value) return [];
    
    const type = selectedCounselor.value.practitioner_type;
    const dynamic = props.services.filter(s => s.is_active && s.practitioner_type === type);
    
    if (dynamic.length > 0) {
        return dynamic;
    }
    
    // Fallback if no services from DB
    return [
        ...fallbackServices.chat.filter(s => s.practitioner_type === type).map(s => ({...s, service_type: 'chat'})),
        ...fallbackServices.online.filter(s => s.practitioner_type === type).map(s => ({...s, service_type: 'online'})),
        ...fallbackServices.offline.filter(s => s.practitioner_type === type).map(s => ({...s, service_type: 'offline'})),
    ];
});

// Group packages by service type
const groupedPackages = computed(() => {
    const groups: Record<string, any[]> = {
        'chat': [],
        'online': [],
        'offline': []
    };
    
    counselorPackages.value.forEach(p => {
        if (groups[p.service_type]) {
            groups[p.service_type].push(p);
        }
    });
    
    return groups;
});

const activeActivity = ref(0);
const activeFaq = ref<number | null>(null);
const toggleFaq = (index: number) => {
    activeFaq.value = activeFaq.value === index ? null : index;
};
const activityImages = [
    { src: '/images/loginregister.webp', title: 'Konseling Kelompok', desc: 'Sesi berbagi dan pemulihan bersama dalam lingkungan yang aman.' },
    { src: '/images/loginregister1.webp', title: 'Sesi Tatap Muka', desc: 'Pendampingan langsung dengan psikolog profesional kami.' },
    { src: '/images/tentangkami3.webp', title: 'Psikoedukasi', desc: 'Kegiatan edukasi kesehatan mental untuk masyarakat luas.' },
    { src: '/images/tentangkami4.webp', title: 'Terapi Bermain', desc: 'Pendekatan khusus untuk penanganan psikologis anak.' },
];

let activityInterval: ReturnType<typeof setInterval> | null = null;
const startActivityCarousel = () => {
    activityInterval = setInterval(() => {
        activeActivity.value = (activeActivity.value + 1) % activityImages.length;
    }, 4000);
};

const pauseActivityCarousel = () => {
    if (activityInterval) clearInterval(activityInterval);
};

const scrollCounselors = (direction: 'left' | 'right') => {
    if (counselorsContainer.value) {
        const itemWidth = 350; // card width + gap
        counselorsContainer.value.scrollBy({ 
            left: direction === 'left' ? -itemWidth : itemWidth, 
            behavior: 'smooth' 
        });
    }
};

const abbreviateName = (name: string) => {
    if (!name) return '';
    return name
        .split(' ')
        .filter(word => word.length > 0)
        .map(word => word.charAt(0).toUpperCase() + '.')
        .join(' ');
};

const packageTabs = [
    { key: 'chat', label: 'Via Chat', icon: '💬' },
    { key: 'online', label: 'Online', icon: '💻' },
    { key: 'offline', label: 'Offline', icon: '🏢' },
] as const;

type PackageCard = {
    id: string | number;
    practitioner_type: 'psychologist' | 'counselor';
    duration_minutes: number;
    price: number;
};

const fallbackServices: Record<'chat' | 'online' | 'offline', PackageCard[]> = {
    chat: [
        { id: 'chat-psy', practitioner_type: 'psychologist', duration_minutes: 60, price: 100000 },
        { id: 'chat-csl', practitioner_type: 'counselor', duration_minutes: 60, price: 50000 },
    ],
    online: [
        { id: 'online-psy-60', practitioner_type: 'psychologist', duration_minutes: 60, price: 200000 },
        { id: 'online-psy-90', practitioner_type: 'psychologist', duration_minutes: 90, price: 265000 },
        { id: 'online-csl', practitioner_type: 'counselor', duration_minutes: 60, price: 150000 },
    ],
    offline: [
        { id: 'offline-psy-60', practitioner_type: 'psychologist', duration_minutes: 60, price: 250000 },
        { id: 'offline-psy-90', practitioner_type: 'psychologist', duration_minutes: 90, price: 315000 },
        { id: 'offline-csl', practitioner_type: 'counselor', duration_minutes: 60, price: 200000 },
    ],
};

const packageCards = computed<PackageCard[]>(() => {
    const dynamic = props.services.filter(
        (service) => service.is_active && service.service_type === activePackage.value,
    );

    if (dynamic.length > 0) {
        return dynamic;
    }

    return fallbackServices[activePackage.value];
});

function getFeatures(service: PackageCard): string[] {
    const isOnline = activePackage.value === 'online';
    const isOffline = activePackage.value === 'offline';
    const isChat = activePackage.value === 'chat';
    
    if (isChat) {
        return [
            `Durasi ${service.duration_minutes} menit`,
            'Chat real-time terenkripsi',
            service.practitioner_type === 'psychologist' ? 'Privasi terjamin 100%' : 'Harga terjangkau'
        ];
    }
    
    return [
        `Sesi ${service.duration_minutes} menit${service.duration_minutes > 60 ? ' (lebih mendalam)' : ''}`,
        isOnline ? 'Video call interaktif' : 'Interaksi tatap muka langsung',
        service.practitioner_type === 'psychologist' ? 'Psikolog SIPP' : 'Harga terjangkau'
    ];
}

function formatRupiah(value: number): string {
    return `Rp${new Intl.NumberFormat('id-ID').format(value)}`;
}

function counselorPhoto(photoUrl: string | null, photoPath: string | null): string {
    if (photoUrl) return photoUrl;
    if (!photoPath) return '';
    if (photoPath.startsWith('http://') || photoPath.startsWith('https://')) return photoPath;
    if (photoPath.startsWith('/images/')) return photoPath;
    return `/storage/${photoPath}`;
}

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

const displayCounselors = computed(() => {
    const fallbacksWithDetails = fallbackCounselors.map((f, index) => ({
        ...f,
        id: index + 1,
        user_id: index + 1,
        practitioner_type: (f.role === 'Konselor' ? 'counselor' : 'psychologist') as 'psychologist' | 'counselor',
        bio: 'Praktisi kesehatan mental yang berdedikasi untuk mendampingi perjalanan pemulihan dan pertumbuhan diri Anda melalui sesi konseling yang aman dan suportif.',
        specializations: ['Pengembangan Diri', 'Manajemen Stres', 'Kecemasan'],
        sipp_number: null,
    }));

    if (!props.counselors.length) {
        return fallbacksWithDetails;
    }

    return props.counselors.map((counselor, index) => {
        const name = counselor.user?.name || '';
        // Match fallback based on whether the fallback name starts with or includes the DB name
        const matchedFallback = fallbacksWithDetails.find(f => f.name.toLowerCase().includes(name.toLowerCase()));
        const fallback = matchedFallback || fallbacksWithDetails[index % fallbacksWithDetails.length];
        
        // Use photo_url from DB (resolved by accessor), fallback to static /images/
        const resolvedPhoto = counselorPhoto(counselor.photo_url, counselor.photo_path) || fallback.photo;
        
        return {
            id: counselor.id,
            user_id: counselor.user_id || counselor.user?.id || counselor.id,
            practitioner_type: counselor.practitioner_type,
            name: matchedFallback ? matchedFallback.name : name, // Use fallback name if matched, else use DB name
            role: counselor.practitioner_type === 'psychologist' ? 'Psikolog Klinis' : 'Konselor',
            photo: resolvedPhoto,
            bio: counselor.bio || counselor.counselor_profile?.bio || fallback.bio,
            specializations: counselor.specializations || counselor.counselor_profile?.specializations || fallback.specializations,
            sipp_number: counselor.sipp_number || counselor.counselor_profile?.sipp_number || fallback.sipp_number,
        };
    });
});

const faqs = [
    {
        question: 'Bagaimana cara melakukan booking konseling?',
        answer: 'Anda dapat memilih layanan, menentukan konselor serta jadwal yang tersedia, kemudian mengisi form booking dan melakukan pembayaran sesuai instruksi yang diberikan.'
    },
    {
        question: 'Apakah saya harus memiliki akun terlebih dahulu?',
        answer: 'Ya, booking hanya dapat dilakukan setelah login atau registrasi akun agar jadwal dan sesi konseling dapat dikelola dengan aman.'
    },
    {
        question: 'Layanan apa saja yang tersedia?',
        answer: 'Kami menyediakan layanan: Konseling Chat, Konseling Online, dan Konseling Offline dengan pilihan Konselor maupun Psikolog profesional.'
    },
    {
        question: 'Metode pembayaran apa yang digunakan?',
        answer: 'Pembayaran dilakukan melalui transfer bank manual dan bukti transfer diupload melalui dashboard booking.'
    },
    {
        question: 'Berapa lama proses verifikasi pembayaran?',
        answer: 'Verifikasi pembayaran dilakukan maksimal 12 jam setelah bukti transfer diupload oleh klien.'
    },
    {
        question: 'Apakah saya bisa melakukan reschedule jadwal?',
        answer: 'Bisa. Reschedule maksimal 1 kali dan diajukan minimal H-1 sebelum jadwal sesi berlangsung.'
    },
    {
        question: 'Apakah data dan sesi konseling bersifat rahasia?',
        answer: 'Ya. Seluruh data pribadi, chat, dan hasil konseling dijaga kerahasiaannya serta hanya dapat diakses oleh pihak terkait.'
    },
    {
        question: 'Kapan saya bisa masuk ke ruang chat sesi?',
        answer: 'Klien dapat masuk saat waktu sesi dimulai.'
    }
];

let animationContext: gsap.Context | null = null;

onMounted(() => {
    startActivityCarousel();

    if (!pageRef.value) {
        return;
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    gsap.registerPlugin(ScrollTrigger);

    animationContext = gsap.context(() => {
        gsap.fromTo(
            '[data-animate-hero]',
            { y: 28, opacity: 0 },
            {
                y: 0,
                opacity: 1,
                duration: 0.9,
                stagger: 0.12,
                ease: 'power3.out',
            },
        );

        const sections = gsap.utils.toArray<HTMLElement>('[data-animate-section]');

        sections.forEach((section) => {
            const revealItems = section.querySelectorAll('[data-animate]');
            if (revealItems.length > 0) {
                gsap.fromTo(
                    revealItems,
                    { y: 24, opacity: 0 },
                    {
                        y: 0,
                        opacity: 1,
                        duration: 0.75,
                        stagger: 0.1,
                        ease: 'power2.out',
                        scrollTrigger: {
                            trigger: section,
                            start: 'top 82%',
                            once: true,
                        },
                    },
                );
            }

            const revealCards = section.querySelectorAll('[data-animate-card]');
            if (revealCards.length > 0) {
                gsap.fromTo(
                    revealCards,
                    { y: 20, opacity: 0, scale: 0.98 },
                    {
                        y: 0,
                        opacity: 1,
                        scale: 1,
                        duration: 0.7,
                        stagger: 0.08,
                        ease: 'power2.out',
                        scrollTrigger: {
                            trigger: section,
                            start: 'top 78%',
                            once: true,
                        },
                    },
                );
            }

            const flipCards = section.querySelectorAll('[data-animate-flip]');
            if (flipCards.length > 0) {
                gsap.fromTo(
                    flipCards,
                    { y: 60, opacity: 0, rotationX: -30, scale: 0.9 },
                    {
                        y: 0,
                        opacity: 1,
                        rotationX: 0,
                        scale: 1,
                        duration: 0.9,
                        stagger: 0.15,
                        ease: 'back.out(1.4)',
                        scrollTrigger: {
                            trigger: section,
                            start: 'top 80%',
                            once: true,
                        },
                    },
                );
            }
        });

        // About Image Animation
        const aboutImages = gsap.utils.toArray<HTMLElement>('[data-animate-about-image]');
        aboutImages.forEach((img) => {
            gsap.fromTo(img,
                { y: 40, opacity: 0 },
                {
                    y: 0,
                    opacity: 1,
                    duration: 0.6,
                    ease: 'power2.out',
                    scrollTrigger: {
                        trigger: img,
                        start: 'top 80%',
                        once: true
                    }
                }
            );
        });

        // Parallax Effect
        const parallaxImages = gsap.utils.toArray<HTMLElement>('[data-parallax-image]');
        parallaxImages.forEach((pImg) => {
            gsap.to(pImg, {
                y: -20,
                ease: 'none',
                scrollTrigger: {
                    trigger: pImg,
                    start: 'top bottom',
                    end: 'bottom top',
                    scrub: true
                }
            });
        });
    }, pageRef.value);
});

onBeforeUnmount(() => {
    if (activityInterval) clearInterval(activityInterval);
    animationContext?.revert();
    animationContext = null;
});
</script>

<template>
    <Head title="Bhimaswari.id | Platform Layanan Konseling Online" />

    <div ref="pageRef" class="font-display bg-[#fafafa] text-slate-900">
        <header class="fixed top-0 z-50 w-full border-b border-white/60 bg-white/70 backdrop-blur-xl transition-all duration-300 shadow-sm">
            <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link :href="route('landing')" class="flex items-center gap-2.5 group relative">
                    <div class="absolute -inset-1.5 rounded-full bg-primary/10 blur-md transition-all duration-500 opacity-0 group-hover:opacity-100 group-hover:scale-110"></div>
                    <img src="/images/logo.webp" alt="Bhimaswari.id Logo" class="relative h-10 w-auto transition-transform duration-500 group-hover:scale-105" />
                    <span class="hidden sm:flex items-center text-2xl font-extrabold tracking-tight ml-0.5 select-none">
                        <span class="bg-gradient-to-r from-primary-700 to-primary-500 bg-clip-text text-transparent transition-all duration-500 group-hover:from-primary-600 group-hover:to-primary-400">Bhimaswari</span>
                        <span class="text-primary-500 transition-colors duration-500 mx-[1.5px] font-black">.</span>
                        <span class="text-slate-800 transition-colors duration-500 group-hover:text-primary-700 font-extrabold">id</span>
                    </span>
                </Link>

                <nav class="hidden items-center gap-8 lg:flex">
                    <a class="group relative text-sm font-bold text-slate-700 transition-colors hover:text-primary" href="#about">
                        Tentang
                        <span class="absolute -bottom-1.5 left-0 h-0.5 w-full origin-left scale-x-0 rounded-full bg-primary transition-transform duration-300 ease-out group-hover:scale-x-100"></span>
                    </a>
                    <a class="group relative text-sm font-bold text-slate-700 transition-colors hover:text-primary" href="#services">
                        Layanan
                        <span class="absolute -bottom-1.5 left-0 h-0.5 w-full origin-left scale-x-0 rounded-full bg-primary transition-transform duration-300 ease-out group-hover:scale-x-100"></span>
                    </a>
                    <a class="group relative text-sm font-bold text-slate-700 transition-colors hover:text-primary" href="#counselors">
                        Tim
                        <span class="absolute -bottom-1.5 left-0 h-0.5 w-full origin-left scale-x-0 rounded-full bg-primary transition-transform duration-300 ease-out group-hover:scale-x-100"></span>
                    </a>
                    <a class="group relative text-sm font-bold text-slate-700 transition-colors hover:text-primary" href="#why-us">
                        Keunggulan
                        <span class="absolute -bottom-1.5 left-0 h-0.5 w-full origin-left scale-x-0 rounded-full bg-primary transition-transform duration-300 ease-out group-hover:scale-x-100"></span>
                    </a>
                    <a class="group relative text-sm font-bold text-slate-700 transition-colors hover:text-primary" href="#mitra">
                        Mitra
                        <span class="absolute -bottom-1.5 left-0 h-0.5 w-full origin-left scale-x-0 rounded-full bg-primary transition-transform duration-300 ease-out group-hover:scale-x-100"></span>
                    </a>
                </nav>

                <div class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth?.user"
                        :href="route('dashboard')"
                        class="group relative inline-flex items-center justify-center rounded-full bg-primary-50 px-6 py-2.5 text-sm font-bold text-primary transition-all duration-300 hover:bg-primary/20 hover:scale-105"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            v-if="canLogin"
                            :href="route('login')"
                            class="hidden sm:inline-flex relative items-center justify-center px-5 py-2.5 text-sm font-bold text-slate-700 transition-all duration-300 hover:text-primary"
                        >
                            <span class="relative z-10">Masuk</span>
                            <div class="absolute inset-0 rounded-full border-2 border-transparent transition-all duration-300 hover:border-primary/20 hover:bg-primary/5"></div>
                        </Link>
                        <Link
                            :href="route('booking.public')"
                            class="group relative inline-flex items-center justify-center overflow-hidden rounded-full p-0.5 font-bold transition-all hover:scale-105 hover:shadow-[0_8px_25px_-8px_rgba(168,85,247,0.5)]"
                        >
                            <span class="absolute h-full w-full bg-gradient-to-br from-primary via-primary-500 to-primary-700 group-hover:via-primary-600 group-hover:to-primary-800"></span>
                            <span class="absolute bottom-0 right-0 -mr-4 -mb-4 h-12 w-12 rounded-full bg-white/20 blur-md transition-all duration-500 group-hover:h-24 group-hover:w-24 group-hover:-mr-8 group-hover:-mb-8"></span>
                            <span class="relative rounded-full px-6 py-2.5 text-sm text-white transition-all">Mulai Konseling</span>
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <main>
            <section class="relative flex min-h-screen items-center overflow-hidden pt-20">
                <div class="absolute inset-0">
                    <img
                        src="/images/hero2.webp"
                        alt="Ruang Aman untuk Pulih"
                        class="h-full w-full object-cover"
                    />
                    <div class="absolute inset-0 bg-slate-900/20"></div>
                    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/40 via-transparent to-slate-900/40"></div>
                </div>

                <div class="relative z-10 mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-4xl rounded-[3rem] border border-white/30 bg-white/20 p-8 text-center shadow-2xl backdrop-blur-sm sm:p-10 md:p-14">
                        <div data-animate-hero class="mb-6 inline-flex items-center gap-2 border-b border-white/30 pb-2 text-base font-bold uppercase tracking-[0.25em] text-white md:text-lg">
                            Ruang Aman untuk Pulih
                        </div>
                        <h1 data-animate-hero class="mb-8 text-4xl font-extrabold leading-[1.1] tracking-tight text-white sm:text-5xl lg:text-7xl">
                            Perjalananmu Menuju <br />
                            <span class="text-primary-50">Mental Lebih Sehat</span> <br />
                            Dimulai di Sini
                        </h1>
                        <p data-animate-hero class="mx-auto mb-10 max-w-2xl text-lg leading-relaxed text-white/90">
                            Bhimaswari.id menghadirkan layanan konseling online dan offline yang aman, terjadwal, dan terdokumentasi untuk mendukung proses pulihmu bersama psikolog dan konselor profesional.
                        </p>
                        <div data-animate-hero class="mx-auto flex max-w-xl flex-col items-center justify-center gap-4 sm:flex-row">
                            <Link
                                href="/booking"
                                class="w-full rounded-full bg-primary px-8 py-4 text-lg font-bold text-white shadow-xl shadow-primary/30 transition hover:opacity-90 sm:w-auto"
                            >
                                Jadwalkan Sesi Pertama
                            </Link>
                            <a
                                href="#counselors"
                                class="w-full rounded-full border border-white/40 bg-white/10 px-8 py-4 text-lg font-bold text-white transition hover:bg-white/20 sm:w-auto"
                            >
                                Lihat Tim Konselor
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <section id="about" data-animate-section class="relative overflow-hidden bg-white py-32">
                <div class="absolute left-0 top-0 h-[600px] w-[600px] -translate-x-1/3 -translate-y-1/4 rounded-full bg-primary-100/40 blur-[120px]"></div>
                <div class="absolute right-0 bottom-0 h-[500px] w-[500px] translate-x-1/3 translate-y-1/4 rounded-full bg-blue-100/40 blur-[100px]"></div>
                
                <div class="relative z-10 mx-auto grid max-w-7xl items-center gap-16 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                    <!-- Text Area (Order 1 in mobile, order 1 in desktop anyway) -->
                    <div class="order-1 flex flex-col gap-8">
                        <div data-animate class="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-4 py-1.5 w-max">
                            <span class="flex h-2 w-2 rounded-full bg-primary animate-pulse"></span>
                            <span class="text-xs font-bold uppercase tracking-widest text-primary">TENTANG KAMI</span>
                        </div>
                        <h2 data-animate class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl leading-tight">
                            Ruang aman untuk memulai langkah <span class="bg-gradient-to-r from-primary to-primary-600 bg-clip-text text-transparent">pemulihan Anda.</span>
                        </h2>
                        <p data-animate class="text-lg leading-relaxed text-slate-600">
                            Bhimaswari.id hadir sebagai biro psikologi yang berkomitmen untuk memberikan layanan psikologis yang berkualitas, mudah diakses, dan inklusif bagi individu, keluarga, kelompok, dan organisasi di Indonesia.
                        </p>
                        <p data-animate class="text-lg leading-relaxed text-slate-600">
                            Kami berfokus pada suatu pendekatan holistik yang memandang individu sebagai sebuah kesatuan yang utuh, yang terdiri dari berbagai aspek yang saling terkait dan saling memengaruhi dalam mendukung kesejahteraan psikologis dan pengembangan individu.
                        </p>
                    </div>

                    <!-- Image Area (Order 2 in mobile) -->
                    <div class="order-2 relative w-full px-2 sm:px-12 lg:px-0 mt-8 lg:mt-0 h-[450px] md:h-[550px] flex items-center justify-center lg:justify-end" data-animate>
                        <div class="absolute inset-0 -m-6 rounded-[4rem] bg-gradient-to-tr from-primary-200/50 to-blue-200/50 blur-3xl opacity-60"></div>
                        
                        <div 
                            class="relative w-full max-w-[280px] sm:max-w-sm h-[85%] lg:mr-16"
                            @mouseenter="pauseActivityCarousel"
                            @mouseleave="startActivityCarousel"
                            @touchstart="pauseActivityCarousel"
                            @touchend="startActivityCarousel"
                        >
                            <div 
                                v-for="(img, idx) in activityImages" 
                                :key="idx"
                                @click="activeActivity = idx"
                                class="absolute top-0 left-0 w-full h-full rounded-[3rem] shadow-2xl transition-all duration-700 ease-[cubic-bezier(0.25,1,0.5,1)] overflow-hidden cursor-pointer border-[6px] border-white/90 bg-white"
                                :style="{
                                    transform: `
                                        translateX(${ ((idx - activeActivity + activityImages.length) % activityImages.length) * 40 }px) 
                                        translateY(${ ((idx - activeActivity + activityImages.length) % activityImages.length) * 20 }px) 
                                        scale(${ 1 - ((idx - activeActivity + activityImages.length) % activityImages.length) * 0.08 })
                                    `,
                                    zIndex: 40 - ((idx - activeActivity + activityImages.length) % activityImages.length),
                                    opacity: ((idx - activeActivity + activityImages.length) % activityImages.length) > 2 ? 0 : 1 - ((idx - activeActivity + activityImages.length) % activityImages.length) * 0.3
                                }"
                            >
                                <img :src="img.src" class="w-full h-full object-cover transition-transform duration-[2s] hover:scale-105" :alt="img.title" />
                            </div>
                            
                            <!-- Custom Navigation Controls -->
                            <div class="absolute -bottom-20 left-0 right-0 flex justify-center gap-4 z-50">
                                <button @click="activeActivity = (activeActivity - 1 + activityImages.length) % activityImages.length" class="flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-md hover:bg-primary hover:text-white transition-all hover:-translate-y-1 text-slate-700">
                                    <ChevronLeft class="w-6 h-6" />
                                </button>
                                <button @click="activeActivity = (activeActivity + 1) % activityImages.length" class="flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-md hover:bg-primary hover:text-white transition-all hover:-translate-y-1 text-slate-700">
                                    <ChevronRight class="w-6 h-6" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="services" data-animate-section class="bg-slate-50 py-24">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-12 text-center">
                        <div data-animate class="mb-4 inline-flex items-center justify-center rounded-full bg-primary/10 px-4 py-1 text-xs font-bold uppercase tracking-widest text-primary">
                            Pilihan Paket
                        </div>
                        <h3 data-animate class="mb-4 text-3xl font-extrabold tracking-tight text-slate-900 md:text-5xl">
                            Pilih Layanan Sesuai <span class="text-primary">Kebutuhanmu</span>
                        </h3>
                        <p data-animate class="mx-auto mb-10 max-w-2xl text-lg text-slate-500">
                            Tarif transparan tanpa biaya tersembunyi. Pilih metode konseling yang paling nyaman untuk Anda.
                        </p>
                        
                        <div data-animate class="inline-flex rounded-full border border-slate-100 bg-white p-1.5 shadow-sm">
                            <button
                                v-for="tab in packageTabs"
                                :key="tab.key"
                                type="button"
                                @click="activePackage = tab.key"
                                class="flex items-center gap-2 rounded-full px-8 py-3 text-sm font-bold transition-all"
                                :class="activePackage === tab.key ? 'bg-primary text-white shadow-md' : 'text-slate-600 hover:bg-slate-50'"
                            >
                                <span class="text-base">{{ tab.icon }}</span>
                                {{ tab.label }}
                            </button>
                        </div>
                    </div>

                    <div class="mx-auto grid items-center gap-8" :class="activePackage === 'chat' ? 'max-w-4xl md:grid-cols-2' : 'max-w-6xl md:grid-cols-3'">
                        <div
                            v-for="(service, index) in packageCards"
                            :key="service.id"
                            data-animate-card
                            class="group relative flex h-full flex-col justify-between overflow-hidden rounded-[2.5rem] p-8 transition-all duration-300"
                            :class="[
                                index === 1 && activePackage !== 'chat'
                                    ? 'z-10 bg-gradient-to-br from-primary-400 to-primary-600 text-white shadow-2xl md:-translate-y-4 md:scale-105' 
                                    : activePackage === 'chat' && index === 0 
                                        ? 'border-2 border-primary bg-white text-slate-900 shadow-xl' 
                                        : 'border border-slate-100 bg-white text-slate-900 shadow-xl'
                            ]"
                        >
                            <div v-if="(index === 1 && activePackage !== 'chat') || (index === 0 && activePackage === 'chat')" class="absolute -right-8 -top-8 h-32 w-32 rounded-bl-[5rem] transition-all group-hover:scale-110" :class="activePackage === 'chat' ? 'bg-primary-50' : 'bg-white/10'"></div>
                            
                            <div class="relative z-10 flex h-full flex-col">
                                <div class="mb-6 flex items-start justify-between">
                                    <div class="inline-flex rounded-full px-4 py-1.5 text-xs font-bold"
                                        :class="index === 1 && activePackage !== 'chat' ? 'bg-white/20 text-white' : 'bg-primary-50 text-primary'">
                                        {{ service.practitioner_type === 'psychologist' ? (activePackage === 'chat' ? 'Psikolog Berlisensi' : 'Psikolog') : 'Konselor' }}
                                    </div>
                                    <div v-if="index === 1 && activePackage !== 'chat'" class="inline-flex rounded-full bg-white px-4 py-1.5 text-xs font-bold text-primary">
                                        Rekomendasi
                                    </div>
                                </div>

                                <h4 class="mb-1 text-2xl font-black">
                                    {{ activePackage === 'online' ? 'Online' : activePackage === 'offline' ? 'Tatap Muka' : 'Via Chat' }} — {{ activePackage === 'chat' ? (service.practitioner_type === 'psychologist' ? 'Psikolog' : 'Konselor') : (activePackage === 'offline' && service.practitioner_type === 'counselor' ? 'Konselor' : service.duration_minutes + ' Menit') }}
                                </h4>
                                <p class="mb-6 text-sm" :class="index === 1 && activePackage !== 'chat' ? 'text-white/80' : 'text-slate-500'">
                                    {{ activePackage === 'online' ? 'Via Google Meet / Zoom' : activePackage === 'offline' ? 'Konseling langsung di lokasi' : (service.practitioner_type === 'psychologist' ? 'Konseling chat real-time dengan psikolog klinis berlisensi SIPP.' : 'Konseling chat real-time dengan konselor profesional.') }}
                                </p>

                                <div class="mb-8 flex items-baseline gap-1">
                                    <span class="text-4xl font-black">{{ formatRupiah(service.price) }}</span>
                                    <span v-if="activePackage === 'chat'" class="text-sm font-bold text-slate-400">/ {{ service.duration_minutes }} menit</span>
                                </div>

                                <ul class="mb-8 flex-1 space-y-4">
                                    <li v-for="feature in getFeatures(service)" :key="feature" class="flex items-center gap-3">
                                        <Check class="h-5 w-5 shrink-0" :class="index === 1 && activePackage !== 'chat' ? 'text-emerald-400' : 'text-emerald-500'" />
                                        <span class="text-sm font-medium" :class="index === 1 && activePackage !== 'chat' ? 'text-white' : 'text-slate-600'">{{ feature }}</span>
                                    </li>
                                </ul>

                                <Link
                                    :href="route('booking.public', { service_price_id: service.id, skip_to: 2 })"
                                    class="block w-full rounded-2xl py-4 text-center font-bold transition-all text-sm"
                                    :class="index === 1 && activePackage !== 'chat' ? 'bg-white text-primary shadow-lg hover:bg-slate-50' : 'border-2 border-primary/20 bg-white text-primary hover:bg-primary-50'"
                                >
                                    Mulai Konseling
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mx-auto mb-16 mt-32 max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                    <div data-animate class="mb-4 inline-flex items-center justify-center rounded-full border border-primary/20 bg-white px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-primary shadow-sm">
                        LAYANAN LAINNYA
                    </div>
                    <h2 data-animate class="mb-4 text-4xl font-extrabold tracking-tight sm:text-5xl">
                        Layanan Psikologi <span class="bg-gradient-to-r from-primary to-primary-600 bg-clip-text text-transparent">Profesional</span>
                    </h2>
                </div>

                <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 pb-32 flex flex-col gap-8 md:gap-12 relative">
                    <!-- Card 1: Konseling -->
                    <div class="sticky top-28 z-10 w-full rounded-[3rem] border border-slate-100 bg-white shadow-xl transition-all duration-500 overflow-hidden flex flex-col md:flex-row h-auto md:h-[450px]">
                        <div class="w-full md:w-1/2 h-64 md:h-full relative overflow-hidden group">
                            <img src="/images/konseling.webp" alt="Konseling" class="absolute inset-0 h-full w-full object-cover transition-transform duration-1000 group-hover:scale-110" />
                        </div>
                        <div class="w-full md:w-1/2 p-8 md:p-14 flex flex-col justify-center bg-white relative">
                            <div class="absolute -left-6 top-1/2 -translate-y-1/2 hidden md:flex h-12 w-12 items-center justify-center rounded-full bg-primary text-white font-bold shadow-lg z-20">01</div>
                            <div class="mb-4 inline-flex items-center gap-2 text-primary font-bold tracking-widest text-xs uppercase">
                                Layanan Utama
                            </div>
                            <h3 class="mb-4 text-3xl font-black text-slate-900 md:text-4xl">Konseling</h3>
                            <p class="text-lg leading-relaxed text-slate-600">Menangani permasalahan anak, remaja, dewasa, hingga pasangan suami istri dengan aman dan rahasia bersama praktisi tersertifikasi.</p>
                        </div>
                    </div>

                    <!-- Card 2: Psikoedukasi -->
                    <div class="sticky top-36 z-20 w-full rounded-[3rem] border border-slate-200 bg-slate-50 shadow-2xl transition-all duration-500 overflow-hidden flex flex-col md:flex-row h-auto md:h-[450px]">
                        <div class="w-full md:w-1/2 p-8 md:p-14 flex flex-col justify-center relative order-2 md:order-1">
                            <div class="absolute -right-6 top-1/2 -translate-y-1/2 hidden md:flex h-12 w-12 items-center justify-center rounded-full bg-slate-900 text-white font-bold shadow-lg z-20">02</div>
                            <div class="mb-4 inline-flex items-center gap-2 text-slate-900 font-bold tracking-widest text-xs uppercase">
                                Edukasi & Publik
                            </div>
                            <h3 class="mb-4 text-3xl font-black text-slate-900 md:text-4xl">Psikoedukasi</h3>
                            <p class="text-lg leading-relaxed text-slate-600">Pemberian wawasan mendalam mengenai isu kesehatan mental, pencegahan bullying, tumbuh kembang anak, hingga parenting aplikatif.</p>
                        </div>
                        <div class="w-full md:w-1/2 h-64 md:h-full relative overflow-hidden group order-1 md:order-2">
                            <img src="/images/psikoedukasi.webp" alt="Psikoedukasi" class="absolute inset-0 h-full w-full object-cover transition-transform duration-1000 group-hover:scale-110" />
                        </div>
                    </div>

                    <!-- Card 3: Asesmen -->
                    <div class="sticky top-44 z-30 w-full rounded-[3rem] border border-slate-800 bg-slate-900 shadow-2xl transition-all duration-500 overflow-hidden flex flex-col md:flex-row h-auto md:h-[450px] text-white">
                        <div class="w-full md:w-1/2 h-64 md:h-full relative overflow-hidden group">
                            <img src="/images/asesmen.webp" alt="Asesmen" class="absolute inset-0 h-full w-full object-cover transition-transform duration-1000 group-hover:scale-110" />
                            <div class="absolute inset-0 bg-slate-900/40"></div>
                        </div>
                        <div class="w-full md:w-1/2 p-8 md:p-14 flex flex-col justify-center relative">
                            <div class="absolute -left-6 top-1/2 -translate-y-1/2 hidden md:flex h-12 w-12 items-center justify-center rounded-full bg-white text-slate-900 font-bold shadow-lg z-20">03</div>
                            <div class="mb-4 inline-flex items-center gap-2 text-slate-300 font-bold tracking-widest text-xs uppercase">
                                Tes & Evaluasi
                            </div>
                            <h3 class="mb-4 text-3xl font-black text-white md:text-4xl">Asesmen</h3>
                            <p class="text-lg leading-relaxed text-slate-300">Evaluasi komprehensif mulai dari Tes IQ, Kepribadian, Minat Bakat, hingga Kesiapan Sekolah serta penjurusan karir profesional.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="counselors" data-animate-section class="overflow-hidden bg-slate-50 py-32 relative">
                <div class="absolute left-0 top-1/2 h-[500px] w-[500px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary-100/30 blur-[100px]"></div>
                <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-16 flex flex-col gap-6 text-center md:flex-row md:items-end md:justify-between md:text-left">
                        <div class="mx-auto max-w-2xl md:mx-0">
                            <div data-animate class="mb-4 inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-4 py-1.5">
                                <span class="flex h-2 w-2 rounded-full bg-primary animate-pulse"></span>
                                <span class="text-xs font-bold uppercase tracking-widest text-primary">TIM KAMI</span>
                            </div>
                            <h2 data-animate class="mb-4 text-4xl font-extrabold tracking-tight sm:text-5xl">Konselor & <span class="bg-gradient-to-r from-primary to-primary-600 bg-clip-text text-transparent">Psikolog</span></h2>
                            <p data-animate class="text-lg text-slate-500">Berpengalaman, terverifikasi, dan siap mendengarkan tanpa menghakimi.</p>
                        </div>
                        <div data-animate class="flex justify-center gap-4">
                            <button @click="scrollCounselors('left')" type="button" class="group flex h-14 w-14 items-center justify-center rounded-full bg-white shadow-sm transition-all hover:-translate-y-1 hover:bg-primary hover:shadow-md">
                                <ChevronLeft class="h-6 w-6 text-slate-700 transition-colors group-hover:text-white" />
                            </button>
                            <button @click="scrollCounselors('right')" type="button" class="group flex h-14 w-14 items-center justify-center rounded-full bg-white shadow-sm transition-all hover:-translate-y-1 hover:bg-primary hover:shadow-md">
                                <ChevronRight class="h-6 w-6 text-slate-700 transition-colors group-hover:text-white" />
                            </button>
                        </div>
                    </div>

                    <div ref="counselorsContainer" class="hide-scrollbar scroll-smooth flex gap-8 overflow-x-auto pb-16 pt-4 items-stretch px-4 -mx-4 sm:px-0 sm:mx-0">
                        <div v-for="person in displayCounselors" :key="person.name" data-animate-card class="w-[340px] flex-none relative">
                            <div class="group h-full flex flex-col overflow-hidden rounded-[2.5rem] bg-white shadow-md transition-all duration-500 hover:-translate-y-1 hover:shadow-xl">
                                <div class="relative h-64 w-full overflow-hidden bg-slate-100">
                                    <img :src="person.photo" :alt="person.name" class="h-full w-full object-cover object-top transition-transform duration-700 group-hover:scale-105" />
                                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/20 to-transparent opacity-80"></div>
                                    <div class="absolute bottom-6 left-6 right-6">
                                        <p class="mb-2 inline-block rounded-full bg-white/20 px-3 py-1 text-xs font-bold uppercase tracking-wider text-white backdrop-blur-md">{{ person.role }}</p>
                                        <h4 class="text-xl font-bold leading-tight text-white">{{ person.name }}</h4>
                                    </div>
                                </div>
                                <div class="flex flex-1 flex-col justify-between p-6">
                                    <p class="mb-6 line-clamp-3 text-sm leading-relaxed text-slate-600">{{ person.bio }}</p>
                                    <button type="button" @click="openProfile(person)" class="mt-auto w-full rounded-2xl bg-primary-50/50 py-4 text-sm font-bold text-primary transition-all hover:bg-primary hover:text-white">
                                        Lihat Profil Lengkap
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section data-animate-section class="py-28 relative overflow-hidden bg-slate-50">
                <!-- Decorative background elements -->
                <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-primary-100/40 blur-[100px] pointer-events-none"></div>
                <div class="absolute right-0 bottom-0 h-96 w-96 rounded-full bg-blue-100/30 blur-[100px] pointer-events-none"></div>
                
                <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
                        
                        <!-- Left Side: Editorial Header (5 Columns) -->
                        <div class="lg:col-span-5 flex flex-col gap-6 text-left relative">
                            <div data-animate class="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-4 py-1.5 w-max">
                                <span class="flex h-2 w-2 rounded-full bg-primary animate-pulse"></span>
                                <span class="text-xs font-bold uppercase tracking-widest text-primary">NILAI-NILAI UTAMA</span>
                            </div>
                            
                            <h2 data-animate class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl leading-tight">
                                Komitmen Nilai yang <br/>
                                <span class="bg-gradient-to-r from-primary to-primary-600 bg-clip-text text-transparent">Menjadi Fondasi Kami</span>
                            </h2>
                            
                            <p data-animate class="text-lg leading-relaxed text-slate-500 font-medium">
                                Bhimaswari.id senantiasa memegang teguh tiga pilar nilai utama (Company Values) untuk memastikan standar tertinggi, kerahasiaan penuh, dan kompetensi terbaik dalam setiap pendampingan psikologis Anda.
                            </p>
                            
                            <!-- A subtle visual accent -->
                            <div class="hidden lg:block w-24 h-1.5 bg-gradient-to-r from-primary to-blue-500 rounded-full mt-4"></div>
                        </div>
                        
                        <!-- Right Side: Staggered Premium Horizontal Cards (7 Columns) -->
                        <div class="lg:col-span-7 flex flex-col gap-6 relative pl-0 lg:pl-6">
                            
                            <!-- Value 1: Integritas -->
                            <div data-animate-card class="group relative flex flex-col sm:flex-row items-center sm:items-start gap-6 p-8 rounded-[2.5rem] bg-white border border-slate-100/80 shadow-[0_10px_35px_rgba(0,0,0,0.015)] hover:shadow-[0_20px_50px_rgba(168,85,247,0.12)] transition-all duration-500 hover:-translate-y-1 hover:border-purple-200 overflow-hidden">
                                <!-- Top/Left Accent Bar -->
                                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-purple-500 to-indigo-600"></div>
                                
                                <!-- Floating Background Number -->
                                <div class="absolute right-8 bottom-4 text-7xl font-black text-slate-50 select-none pointer-events-none group-hover:text-purple-50 group-hover:scale-105 transition-all duration-500 font-sans opacity-60">01</div>
                                
                                <!-- Icon Container -->
                                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-[1.5rem] rounded-tr-[0.75rem] bg-gradient-to-tr from-purple-50 to-indigo-50/50 text-purple-600 shadow-sm transition-all duration-500 group-hover:scale-110 group-hover:from-purple-500 group-hover:to-indigo-600 group-hover:text-white">
                                    <Target class="h-8 w-8 transition-transform duration-500 group-hover:rotate-12" />
                                </div>
                                
                                <div class="flex-1 text-center sm:text-left pt-1">
                                    <h3 class="text-xl font-extrabold text-slate-900 mb-2 group-hover:text-purple-600 transition-colors tracking-tight">Integritas</h3>
                                    <p class="text-sm leading-relaxed text-slate-500 font-semibold max-w-md">Menjunjung tinggi nilai-nilai moral</p>
                                </div>
                            </div>

                            <!-- Value 2: Profesional -->
                            <div data-animate-card class="group relative flex flex-col sm:flex-row items-center sm:items-start gap-6 p-8 rounded-[2.5rem] bg-white border border-slate-100/80 shadow-[0_10px_35px_rgba(0,0,0,0.015)] hover:shadow-[0_20px_50px_rgba(59,130,246,0.12)] transition-all duration-500 hover:-translate-y-1 hover:border-blue-200 overflow-hidden lg:translate-x-8">
                                <!-- Top/Left Accent Bar -->
                                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-blue-500 to-cyan-600"></div>
                                
                                <!-- Floating Background Number -->
                                <div class="absolute right-8 bottom-4 text-7xl font-black text-slate-50 select-none pointer-events-none group-hover:text-blue-50 group-hover:scale-105 transition-all duration-500 font-sans opacity-60">02</div>
                                
                                <!-- Icon Container -->
                                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-[1.5rem] rounded-tr-[0.75rem] bg-gradient-to-tr from-blue-50 to-cyan-50/50 text-blue-600 shadow-sm transition-all duration-500 group-hover:scale-110 group-hover:from-blue-500 group-hover:to-cyan-600 group-hover:text-white">
                                    <Briefcase class="h-8 w-8 transition-transform duration-500 group-hover:rotate-12" />
                                </div>
                                
                                <div class="flex-1 text-center sm:text-left pt-1">
                                    <h3 class="text-xl font-extrabold text-slate-900 mb-2 group-hover:text-blue-600 transition-colors tracking-tight">Profesional</h3>
                                    <p class="text-sm leading-relaxed text-slate-500 font-semibold max-w-md">Menjaga sikap bertanggung jawab dan kompeten</p>
                                </div>
                            </div>

                            <!-- Value 3: Adaptif -->
                            <div data-animate-card class="group relative flex flex-col sm:flex-row items-center sm:items-start gap-6 p-8 rounded-[2.5rem] bg-white border border-slate-100/80 shadow-[0_10px_35px_rgba(0,0,0,0.015)] hover:shadow-[0_20px_50px_rgba(245,158,11,0.12)] transition-all duration-500 hover:-translate-y-1 hover:border-amber-200 overflow-hidden">
                                <!-- Top/Left Accent Bar -->
                                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-amber-500 to-orange-600"></div>
                                
                                <!-- Floating Background Number -->
                                <div class="absolute right-8 bottom-4 text-7xl font-black text-slate-50 select-none pointer-events-none group-hover:text-amber-50 group-hover:scale-105 transition-all duration-500 font-sans opacity-60">03</div>
                                
                                <!-- Icon Container -->
                                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-[1.5rem] rounded-tr-[0.75rem] bg-gradient-to-tr from-amber-50 to-orange-50/50 text-amber-600 shadow-sm transition-all duration-500 group-hover:scale-110 group-hover:from-amber-500 group-hover:to-orange-600 group-hover:text-white">
                                    <Zap class="h-8 w-8 transition-transform duration-500 group-hover:rotate-12" />
                                </div>
                                
                                <div class="flex-1 text-center sm:text-left pt-1">
                                    <h3 class="text-xl font-extrabold text-slate-900 mb-2 group-hover:text-amber-600 transition-colors tracking-tight">Adaptif</h3>
                                    <p class="text-sm leading-relaxed text-slate-500 font-semibold max-w-md">Menyesuaikan dengan segala situasi dan kondisi yang ada</p>
                                </div>
                            </div>
                            
                        </div>
                        
                    </div>
                </div>
            </section>

            <section id="how-it-works" data-animate-section class="bg-white py-32 relative overflow-hidden">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                    <div class="mb-24 text-center">
                        <div data-animate class="mb-4 inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-4 py-1.5">
                            <span class="flex h-2 w-2 rounded-full bg-primary animate-pulse"></span>
                            <span class="text-xs font-bold uppercase tracking-widest text-primary">ALUR LAYANAN</span>
                        </div>
                        <h2 data-animate class="mb-6 text-4xl font-extrabold tracking-tight sm:text-5xl">Mudah, Cepat, dan <span class="bg-gradient-to-r from-primary to-primary-600 bg-clip-text text-transparent">Aman</span></h2>
                        <p data-animate class="mx-auto max-w-2xl text-lg text-slate-500">Kami merancang alur booking yang sederhana agar Anda bisa lebih fokus pada proses pemulihan, bukan administrasi.</p>
                    </div>
                    
                    <div class="relative grid gap-16 md:grid-cols-3">
                        <div class="absolute top-12 left-[16%] right-[16%] hidden h-0.5 border-t-2 border-dashed border-primary/20 md:block z-0"></div>
                        
                        <div data-animate-card class="group relative text-center z-10">
                            <div class="mx-auto mb-8 flex h-24 w-24 items-center justify-center rounded-full bg-white text-4xl font-black text-primary shadow-sm ring-8 ring-primary-50 transition-all duration-500 group-hover:-translate-y-2 group-hover:bg-primary group-hover:text-white group-hover:ring-primary/20 group-hover:shadow-md">1</div>
                            <h4 class="mb-4 text-2xl font-bold text-slate-900 transition-colors group-hover:text-primary">Pilih & Isi Form</h4>
                            <p class="px-4 leading-relaxed text-slate-500">Pilih layanan, tentukan jadwal, dan isi formulir kondisi awal secara rahasia.</p>
                        </div>
                        <div data-animate-card class="group relative text-center z-10">
                            <div class="mx-auto mb-8 flex h-24 w-24 items-center justify-center rounded-full bg-white text-4xl font-black text-primary shadow-sm ring-8 ring-primary-50 transition-all duration-500 group-hover:-translate-y-2 group-hover:bg-primary group-hover:text-white group-hover:ring-primary/20 group-hover:shadow-md">2</div>
                            <h4 class="mb-4 text-2xl font-bold text-slate-900 transition-colors group-hover:text-primary">Pembayaran</h4>
                            <p class="px-4 leading-relaxed text-slate-500">Lakukan pembayaran dan unggah bukti transfer. Admin akan segera memverifikasi.</p>
                        </div>
                        <div data-animate-card class="group relative text-center z-10">
                            <div class="mx-auto mb-8 flex h-24 w-24 items-center justify-center rounded-full bg-white text-4xl font-black text-primary shadow-sm ring-8 ring-primary-50 transition-all duration-500 group-hover:-translate-y-2 group-hover:bg-primary group-hover:text-white group-hover:ring-primary/20 group-hover:shadow-md">3</div>
                            <h4 class="mb-4 text-2xl font-bold text-slate-900 transition-colors group-hover:text-primary">Mulai Sesi</h4>
                            <p class="px-4 leading-relaxed text-slate-500">Ikuti sesi konseling via chat, online, atau tatap muka di jadwal yang telah ditentukan.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="why-us" data-animate-section class="relative overflow-hidden bg-slate-50 py-32">
                <!-- Decorative background elements -->
                <div class="absolute left-0 top-0 h-[600px] w-[600px] -translate-x-1/3 -translate-y-1/4 rounded-full bg-primary-100/40 blur-[120px]"></div>
                <div class="absolute right-0 bottom-0 h-[500px] w-[500px] translate-x-1/3 translate-y-1/4 rounded-full bg-blue-100/40 blur-[120px]"></div>
                
                <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-20 flex flex-col items-center text-center">
                        <div data-animate class="mb-4 inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-4 py-1.5 backdrop-blur-sm">
                            <span class="flex h-2 w-2 rounded-full bg-primary animate-pulse"></span>
                            <span class="text-xs font-bold uppercase tracking-widest text-primary">MENGAPA KAMI</span>
                        </div>
                        <h2 data-animate class="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl text-slate-900">
                            Kepercayaan Anda adalah <br class="hidden sm:block" />
                            <span class="bg-gradient-to-r from-primary to-blue-600 bg-clip-text text-transparent">Prioritas Kami</span>
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6" style="perspective: 1200px;">
                        <!-- Card 1: Konselor Profesional (Col Span 7) -->
                        <div data-animate-flip class="group md:col-span-7 relative overflow-hidden rounded-[2.5rem] bg-white border border-slate-100 p-8 md:p-12 shadow-[0_10px_40px_-15px_rgba(0,0,0,0.05)] transition-all duration-500 hover:-translate-y-2 hover:scale-[1.02] hover:shadow-[0_20px_50px_-15px_rgba(168,85,247,0.2)]">
                            <div class="absolute -right-8 -top-8 opacity-[0.03] transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6">
                                <Users class="w-80 h-80 text-primary" />
                            </div>
                            <div class="relative z-10 flex h-full flex-col justify-between gap-12">
                                <div class="flex h-16 w-16 items-center justify-center rounded-[1.5rem] bg-primary-50 text-primary shadow-sm transition-transform duration-500 group-hover:scale-110 group-hover:bg-primary group-hover:text-white">
                                    <Users class="h-8 w-8" />
                                </div>
                                <div>
                                    <h3 class="mb-4 text-3xl font-black text-slate-900 sm:text-4xl">Konselor Profesional</h3>
                                    <p class="text-lg leading-relaxed text-slate-500 max-w-md">Didampingi oleh tim konselor dan psikolog klinis tersertifikasi yang berpengalaman dengan pendekatan empatik, hangat, dan tanpa penghakiman.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Aman & Rahasia (Col Span 5) -->
                        <div data-animate-flip class="group md:col-span-5 relative overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-blue-50 to-indigo-50/50 border border-blue-100/50 p-8 md:p-12 shadow-[0_10px_40px_-15px_rgba(0,0,0,0.05)] transition-all duration-500 hover:-translate-y-2 hover:scale-[1.02] hover:shadow-[0_20px_50px_-15px_rgba(59,130,246,0.2)]">
                            <div class="absolute inset-0 bg-white/40 mix-blend-overlay opacity-0 transition-opacity duration-500 group-hover:opacity-100"></div>
                            <div class="relative z-10 flex h-full flex-col justify-between gap-12">
                                <div class="flex h-16 w-16 items-center justify-center rounded-[1.5rem] bg-white text-blue-600 shadow-sm transition-transform duration-500 group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white">
                                    <ShieldCheck class="h-8 w-8" />
                                </div>
                                <div>
                                    <h3 class="mb-4 text-3xl font-black text-slate-900">Aman & Rahasia</h3>
                                    <p class="text-lg leading-relaxed text-slate-600">Seluruh sesi konseling dan data pribadi klien dijaga kerahasiaannya dengan standar etika psikologi ketat.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Layanan Fleksibel (Col Span 4) -->
                        <div data-animate-flip class="group md:col-span-4 relative overflow-hidden rounded-[2.5rem] bg-white border border-slate-100 p-8 shadow-[0_10px_40px_-15px_rgba(0,0,0,0.05)] transition-all duration-500 hover:-translate-y-2 hover:scale-[1.03] hover:border-amber-200 hover:shadow-[0_20px_40px_-15px_rgba(251,191,36,0.15)]">
                            <div class="relative z-10 flex flex-col items-start h-full">
                                <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-[1.25rem] bg-amber-50 text-amber-500 transition-transform duration-500 group-hover:scale-110 group-hover:bg-amber-500 group-hover:text-white">
                                    <Zap class="h-7 w-7" />
                                </div>
                                <h3 class="mb-3 text-xl font-extrabold text-slate-900">Layanan Fleksibel</h3>
                                <p class="text-sm leading-relaxed text-slate-500">Tersedia layanan Chat, Online (Video Call), dan Offline dengan durasi yang dapat disesuaikan.</p>
                            </div>
                        </div>

                        <!-- Card 4: Booking Mudah (Col Span 4) -->
                        <div data-animate-flip class="group md:col-span-4 relative overflow-hidden rounded-[2.5rem] bg-white border border-slate-100 p-8 shadow-[0_10px_40px_-15px_rgba(0,0,0,0.05)] transition-all duration-500 hover:-translate-y-2 hover:scale-[1.03] hover:border-emerald-200 hover:shadow-[0_20px_40px_-15px_rgba(52,211,153,0.15)]">
                            <div class="relative z-10 flex flex-col items-start h-full">
                                <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-[1.25rem] bg-emerald-50 text-emerald-500 transition-transform duration-500 group-hover:scale-110 group-hover:bg-emerald-500 group-hover:text-white">
                                    <Calendar class="h-7 w-7" />
                                </div>
                                <h3 class="mb-3 text-xl font-extrabold text-slate-900">Booking Mudah</h3>
                                <p class="text-sm leading-relaxed text-slate-500">Proses pemesanan cepat dan terorganisir, terhubung dengan ketersediaan jadwal secara real-time.</p>
                            </div>
                        </div>

                        <!-- Card 5: Hangat & Personal (Col Span 4) -->
                        <div data-animate-flip class="group md:col-span-4 relative overflow-hidden rounded-[2.5rem] bg-white border border-slate-100 p-8 shadow-[0_10px_40px_-15px_rgba(0,0,0,0.05)] transition-all duration-500 hover:-translate-y-2 hover:scale-[1.03] hover:border-rose-200 hover:shadow-[0_20px_40px_-15px_rgba(251,113,133,0.15)]">
                            <div class="relative z-10 flex flex-col items-start h-full">
                                <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-[1.25rem] bg-rose-50 text-rose-500 transition-transform duration-500 group-hover:scale-110 group-hover:bg-rose-500 group-hover:text-white">
                                    <Heart class="h-7 w-7" />
                                </div>
                                <h3 class="mb-3 text-xl font-extrabold text-slate-900">Hangat & Personal</h3>
                                <p class="text-sm leading-relaxed text-slate-500">Pendampingan suportif yang dirancang secara khusus untuk fokus pada kebutuhan unik individu.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="mitra" data-animate-section class="py-24 relative overflow-hidden bg-white">
                <!-- Decorative element -->
                <div class="absolute left-1/2 top-0 h-[400px] w-[800px] -translate-x-1/2 -translate-y-1/2 rounded-[100%] bg-primary-50/50 blur-[80px]"></div>
                
                <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-16 text-center">
                        <div data-animate class="mb-4 inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-4 py-1.5">
                            <span class="flex h-2 w-2 rounded-full bg-primary animate-pulse"></span>
                            <span class="text-xs font-bold uppercase tracking-widest text-primary">MITRA KAMI</span>
                        </div>
                        <h2 data-animate class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">
                            Instansi yang Menjadi <span class="text-primary">Mitra Kerjasama</span>
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8" style="perspective: 1200px;">
                        <!-- Partner 1 -->
                        <div data-animate-flip class="group relative flex flex-col items-center justify-center rounded-[2.5rem] border border-slate-100 bg-white p-10 text-center shadow-[0_10px_40px_-15px_rgba(0,0,0,0.05)] transition-all duration-500 hover:-translate-y-2 hover:scale-[1.03] hover:border-purple-200 hover:shadow-[0_20px_40px_-15px_rgba(168,85,247,0.15)]">
                            <div class="mb-8 flex h-20 w-20 items-center justify-center rounded-[1.5rem] bg-purple-50 text-purple-600 transition-transform duration-500 group-hover:scale-110 group-hover:bg-purple-600 group-hover:text-white">
                                <Library class="h-10 w-10" />
                            </div>
                            <h4 class="text-2xl font-black text-slate-800 transition-colors group-hover:text-purple-600">Pondok Pesantren Al-Kautsar</h4>
                            <p class="mt-3 text-sm font-bold text-slate-400 uppercase tracking-widest">Jawa Barat</p>
                        </div>
                        
                        <!-- Partner 2 -->
                        <div data-animate-flip class="group relative flex flex-col items-center justify-center rounded-[2.5rem] border border-slate-100 bg-white p-10 text-center shadow-[0_10px_40px_-15px_rgba(0,0,0,0.05)] transition-all duration-500 hover:-translate-y-2 hover:scale-[1.03] hover:border-blue-200 hover:shadow-[0_20px_40px_-15px_rgba(59,130,246,0.15)]">
                            <div class="mb-8 flex h-20 w-20 items-center justify-center rounded-[1.5rem] bg-blue-50 text-blue-600 transition-transform duration-500 group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white">
                                <GraduationCap class="h-10 w-10" />
                            </div>
                            <h4 class="text-2xl font-black text-slate-800 transition-colors group-hover:text-blue-600">Fakultas Psikologi Universitas Padjajaran</h4>
                            <p class="mt-3 text-sm font-bold text-slate-400 uppercase tracking-widest">Bandung</p>
                        </div>
                        
                        <!-- Partner 3 -->
                        <div data-animate-flip class="group relative flex flex-col items-center justify-center rounded-[2.5rem] border border-slate-100 bg-white p-10 text-center shadow-[0_10px_40px_-15px_rgba(0,0,0,0.05)] transition-all duration-500 hover:-translate-y-2 hover:scale-[1.03] hover:border-rose-200 hover:shadow-[0_20px_40px_-15px_rgba(244,63,94,0.15)]">
                            <div class="mb-8 flex h-20 w-20 items-center justify-center rounded-[1.5rem] bg-rose-50 text-rose-600 transition-transform duration-500 group-hover:scale-110 group-hover:bg-rose-600 group-hover:text-white">
                                <Puzzle class="h-10 w-10" />
                            </div>
                            <h4 class="text-2xl font-black text-slate-800 transition-colors group-hover:text-rose-600">Kidspace Playdate</h4>
                            <p class="mt-3 text-sm font-bold text-slate-400 uppercase tracking-widest">Malang</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Testimonials Section -->
            <section v-if="testimonials && testimonials.length > 0" id="testimonials" data-animate-section class="py-24 relative overflow-hidden bg-white">
                <div class="absolute left-0 top-0 h-[500px] w-[500px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary/10 blur-[100px]"></div>
                
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                    <div class="mb-16 text-center">
                        <div data-animate class="mb-4 inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-4 py-1.5">
                            <span class="flex h-2 w-2 rounded-full bg-primary animate-pulse"></span>
                            <span class="text-xs font-bold uppercase tracking-widest text-primary">Testimoni</span>
                        </div>
                        <h2 data-animate class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">Apa Kata <span class="text-primary">Klien Kami?</span></h2>
                    </div>

                    <div class="relative max-w-full overflow-hidden" style="mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);">
                        <!-- Carousel Container -->
                        <div class="flex animate-marquee gap-6 w-max py-4 hover:[animation-play-state:paused]">
                            <!-- Loop the items twice to create a seamless infinite scroll -->
                            <div v-for="t in [...testimonials, ...testimonials]" :key="t.id + Math.random()" class="w-[350px] shrink-0 bg-white rounded-3xl p-8 border border-primary/10 shadow-[0_10px_30px_-15px_rgba(0,0,0,0.05)] transition-transform hover:-translate-y-1">
                                <div class="inline-flex gap-1 mb-6">
                                    <svg v-for="s in 5" :key="s" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" :class="s <= t.rating ? 'fill-amber-400 text-amber-400' : 'fill-slate-200 text-slate-200'" viewBox="0 0 24 24" stroke="none"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                </div>
                                <p class="text-base text-slate-600 leading-relaxed italic mb-8">"{{ t.content }}"</p>
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center text-primary font-black text-lg border-2 border-white shadow-sm shrink-0">
                                        {{ t.client_name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="text-left">
                                        <h4 class="font-bold text-slate-900 text-sm">{{ abbreviateName(t.client_name) }}</h4>
                                        <p class="text-xs text-slate-500 mt-0.5" v-if="t.booking?.counselor">Konselor: <span class="font-medium text-slate-700">{{ t.booking.counselor.name }}</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="faq" data-animate-section class="py-24 relative overflow-hidden bg-slate-50">
                <div class="absolute right-0 top-0 h-[500px] w-[500px] translate-x-1/3 -translate-y-1/3 rounded-full bg-primary-100/30 blur-[100px]"></div>
                
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 relative z-10">
                    <div class="mb-16 text-center">
                        <div data-animate class="mb-4 inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-4 py-1.5">
                            <span class="flex h-2 w-2 rounded-full bg-primary animate-pulse"></span>
                            <span class="text-xs font-bold uppercase tracking-widest text-primary">FAQ</span>
                        </div>
                        <h2 data-animate class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">Pertanyaan yang <span class="text-primary">Sering Diajukan</span></h2>
                    </div>

                    <div class="space-y-4">
                        <div v-for="(faq, index) in faqs" :key="index" data-animate-card 
                            class="group rounded-[2rem] border border-slate-100 bg-white p-2 transition-all duration-300 hover:border-primary/20 hover:shadow-xl hover:shadow-primary/5"
                        >
                            <button @click="toggleFaq(index)" class="flex w-full items-center justify-between py-3 px-6 text-left outline-none">
                                <span class="text-lg font-bold text-slate-900 transition-colors group-hover:text-primary">{{ faq.question }}</span>
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-50 transition-all duration-300 group-hover:bg-primary/10"
                                    :class="activeFaq === index ? 'rotate-180 bg-primary text-white group-hover:bg-primary' : 'text-slate-400'"
                                >
                                    <ChevronDown class="h-5 w-5" />
                                </div>
                            </button>
                            <Transition
                                enter-active-class="transition duration-300 ease-out"
                                enter-from-class="transform scale-95 opacity-0"
                                enter-to-class="transform scale-100 opacity-100"
                                leave-active-class="transition duration-200 ease-in"
                                leave-from-class="transform scale-100 opacity-100"
                                leave-to-class="transform scale-95 opacity-0"
                            >
                                <div v-show="activeFaq === index" class="px-6 pb-4">
                                    <p class="text-base leading-relaxed text-slate-500 whitespace-pre-line">{{ faq.answer }}</p>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer data-animate-section class="border-t border-slate-200 bg-slate-50 pb-10 pt-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-20 grid gap-12 md:grid-cols-12">
                    <div data-animate class="md:col-span-4">
                        <div class="mb-8 flex items-center gap-3">
                            <img src="/images/logo.webp" alt="Bhimaswari.id Logo" class="h-10 w-auto" />
                            <span class="text-2xl font-black tracking-tight text-slate-900">Bhimaswari.id</span>
                        </div>
                        <p class="mb-8 leading-relaxed text-slate-500 pr-8">Platform layanan konseling online dan offline profesional untuk membantu proses pulih klien secara aman, terjadwal, dan terdokumentasi.</p>
                        <div class="flex gap-4">
                            <a class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm transition-all hover:-translate-y-1 hover:border-primary hover:bg-primary hover:text-white" href="https://instagram.com/bhimaswari.id" target="_blank"><Instagram class="h-5 w-5" /></a>
                            <a class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm transition-all hover:-translate-y-1 hover:border-primary hover:bg-primary hover:text-white" href="mailto:bhimaswarifamily@gmail.com"><Mail class="h-5 w-5" /></a>
                        </div>
                    </div>
                    <div data-animate class="md:col-span-2 md:col-start-6">
                        <h6 class="mb-6 font-bold text-slate-900 uppercase tracking-wider text-sm">Layanan Klinis</h6>
                        <ul class="space-y-4 text-slate-500 font-medium">
                            <li><Link class="transition-colors hover:text-primary" :href="route('booking.public')">Konseling Individu</Link></li>
                            <li><Link class="transition-colors hover:text-primary" :href="route('booking.public')">Konseling Pasangan</Link></li>
                            <li><Link class="transition-colors hover:text-primary" :href="route('booking.public')">Psikologi Anak</Link></li>
                            <li><Link class="transition-colors hover:text-primary" :href="route('booking.public')">Pemulihan Trauma</Link></li>
                        </ul>
                    </div>
                    <div data-animate class="md:col-span-2">
                        <h6 class="mb-6 font-bold text-slate-900 uppercase tracking-wider text-sm">Informasi</h6>
                        <ul class="space-y-4 text-slate-500 font-medium">
                            <li><a class="transition-colors hover:text-primary" href="#services">Layanan</a></li>
                            <li><a class="transition-colors hover:text-primary" href="#about">Tentang Kami</a></li>
                            <li><a class="transition-colors hover:text-primary" href="#faq">FAQ</a></li>
                            <li><a class="transition-colors hover:text-primary" href="#how-it-works">Alur Booking</a></li>
                        </ul>
                    </div>
                    <div data-animate class="md:col-span-3">
                        <h6 class="mb-6 font-bold text-slate-900 uppercase tracking-wider text-sm">Hubungi Kami</h6>
                        <ul class="space-y-4 text-slate-500 font-medium">
                            <li>
                                <a class="group flex items-center gap-3 transition-colors hover:text-primary" href="https://wa.me/6282311467657" target="_blank">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm transition-colors group-hover:bg-primary group-hover:text-white">
                                        <Phone class="h-4 w-4 text-primary group-hover:text-white transition-colors" />
                                    </div>
                                    <span class="text-sm">0823-1146-7657</span>
                                </a>
                            </li>
                            <li>
                                <a class="group flex items-center gap-3 transition-colors hover:text-primary" href="mailto:bhimaswarifamily@gmail.com">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm transition-colors group-hover:bg-primary group-hover:text-white">
                                        <Mail class="h-4 w-4 text-primary group-hover:text-white transition-colors" />
                                    </div>
                                    <span class="text-sm break-all">bhimaswarifamily@gmail.com</span>
                                </a>
                            </li>
                            <li>
                                <a class="group flex items-center gap-3 transition-colors hover:text-primary" href="https://instagram.com/bhimaswari.id" target="_blank">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm transition-colors group-hover:bg-primary group-hover:text-white">
                                        <Instagram class="h-4 w-4 text-primary group-hover:text-white transition-colors" />
                                    </div>
                                    <span class="text-sm">@bhimaswari.id</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div data-animate class="flex flex-col items-center justify-between gap-6 border-t border-slate-200 pt-8 text-sm font-medium text-slate-500 md:flex-row">
                    <p>© 2024 Bhimaswari.id. Hak cipta dilindungi.</p>
                    <div class="flex gap-8">
                        <Link :href="route('privacy')" class="transition-colors hover:text-primary">Kebijakan Privasi</Link>
                        <Link :href="route('terms')" class="transition-colors hover:text-primary">Syarat & Ketentuan</Link>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Counselor Profile Modal -->
        <Modal :show="showProfileModal" max-width="4xl" rounded="rounded-[3rem]" @close="showProfileModal = false">
            <div v-if="selectedCounselor" class="relative flex flex-col md:flex-row h-full max-h-[90vh] overflow-hidden">
                <!-- Close Button -->
                <button @click="showProfileModal = false" class="absolute right-6 top-6 z-50 flex h-10 w-10 items-center justify-center rounded-full bg-white/80 text-slate-500 shadow-md backdrop-blur-md transition-all hover:bg-white hover:text-primary">
                    <X class="h-5 w-5" />
                </button>

                <!-- Left Area: Image -->
                <div class="w-full md:w-[45%] bg-slate-100 relative">
                    <img :src="selectedCounselor.photo" :alt="selectedCounselor.name" class="h-full w-full object-cover" />
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/40 via-transparent to-transparent"></div>
                </div>

                <!-- Right Area: Content -->
                <div class="w-full md:w-[55%] bg-white p-8 md:p-12 overflow-y-auto hide-scrollbar">
                    <div v-if="modalStep === 'details'" class="animate-in fade-in duration-300">
                        <div class="mb-10">
                            <div class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-4 py-1.5 text-[10px] font-black uppercase tracking-[0.2em] text-primary mb-4">
                                {{ selectedCounselor.role }}
                            </div>
                            <h4 class="text-3xl font-black text-slate-900 leading-tight mb-2">{{ selectedCounselor.name }}</h4>
                            <div v-if="selectedCounselor.sipp_number" class="text-sm font-bold text-slate-400">
                                SIPP: {{ selectedCounselor.sipp_number }}
                            </div>
                        </div>

                        <div class="space-y-10">
                            <!-- Bidang Keahlian -->
                            <div>
                                <div class="flex items-center gap-3 text-slate-900 font-extrabold mb-5">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-primary-50 text-xl">🎯</span>
                                    Bidang Keahlian
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="tag in selectedCounselor.specializations" :key="tag" class="rounded-2xl border border-primary/10 bg-primary/5 px-5 py-2 text-sm font-bold text-primary">
                                        {{ tag }}
                                    </span>
                                </div>
                            </div>

                            <!-- Tentang -->
                            <div>
                                <div class="flex items-center gap-3 text-slate-900 font-extrabold mb-5">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-xl">💼</span>
                                    Tentang Praktisi
                                </div>
                                <p class="text-base text-slate-600 leading-relaxed font-medium">
                                    {{ selectedCounselor.bio }}
                                </p>
                            </div>

                            <!-- CTA inside modal -->
                            <div class="pt-6">
                                <button 
                                    @click="openBookingStep" 
                                    class="flex w-full items-center justify-center gap-3 rounded-[2rem] bg-primary px-8 py-5 text-lg font-black text-white shadow-xl shadow-primary/30 transition-all hover:opacity-90 hover:-translate-y-0.5"
                                >
                                    Jadwalkan Konsultasi
                                    <ArrowRight class="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="modalStep === 'booking'" class="flex flex-col h-full animate-in fade-in duration-300">
                        <button @click="modalStep = 'details'" class="flex items-center gap-2 text-slate-500 hover:text-primary mb-6 font-bold transition-colors w-fit">
                            <ChevronLeft class="h-4 w-4" />
                            Kembali ke Profil
                        </button>

                        <div class="flex flex-col space-y-8 pb-8">
                            <div class="text-center mb-2">
                                <h4 class="text-2xl font-black text-slate-900 mb-2">Pilih Layanan</h4>
                                <p class="text-slate-500 text-sm">Pilih jenis layanan dan durasi yang sesuai dengan kebutuhan Anda untuk melanjutkan ke pemilihan jadwal.</p>
                            </div>

                            <!-- Via Chat -->
                            <div v-if="groupedPackages.chat.length > 0">
                                <h5 class="text-sm font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-50 text-blue-500 text-lg">💬</span>
                                    Konseling via Chat
                                </h5>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <button
                                        v-for="pkg in groupedPackages.chat"
                                        :key="pkg.id"
                                        @click="handlePackageClick(pkg.id)"
                                        class="group flex flex-col p-5 rounded-2xl border-2 border-slate-100 bg-white text-left transition-all hover:border-primary/50 hover:bg-primary/5 hover:shadow-lg hover:-translate-y-1"
                                    >
                                        <div class="font-black text-lg text-slate-900 mb-1">{{ pkg.duration_minutes }} Menit</div>
                                        <div class="text-primary font-bold text-xl mb-3">Rp {{ pkg.price.toLocaleString('id-ID') }}</div>
                                        <div class="mt-auto flex items-center text-sm font-bold text-slate-400 group-hover:text-primary transition-colors">
                                            Pilih Layanan <ArrowRight class="ml-1 h-4 w-4" />
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Online -->
                            <div v-if="groupedPackages.online.length > 0">
                                <h5 class="text-sm font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-500 text-lg">💻</span>
                                    Konseling Online (Video Call)
                                </h5>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <button
                                        v-for="pkg in groupedPackages.online"
                                        :key="pkg.id"
                                        @click="handlePackageClick(pkg.id)"
                                        class="group flex flex-col p-5 rounded-2xl border-2 border-slate-100 bg-white text-left transition-all hover:border-primary/50 hover:bg-primary/5 hover:shadow-lg hover:-translate-y-1"
                                    >
                                        <div class="font-black text-lg text-slate-900 mb-1">{{ pkg.duration_minutes }} Menit</div>
                                        <div class="text-primary font-bold text-xl mb-3">Rp {{ pkg.price.toLocaleString('id-ID') }}</div>
                                        <div class="mt-auto flex items-center text-sm font-bold text-slate-400 group-hover:text-primary transition-colors">
                                            Pilih Layanan <ArrowRight class="ml-1 h-4 w-4" />
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Offline -->
                            <div v-if="groupedPackages.offline.length > 0">
                                <h5 class="text-sm font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-50 text-purple-500 text-lg">🏢</span>
                                    Konseling Tatap Muka
                                </h5>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <button
                                        v-for="pkg in groupedPackages.offline"
                                        :key="pkg.id"
                                        @click="handlePackageClick(pkg.id)"
                                        class="group flex flex-col p-5 rounded-2xl border-2 border-slate-100 bg-white text-left transition-all hover:border-primary/50 hover:bg-primary/5 hover:shadow-lg hover:-translate-y-1"
                                    >
                                        <div class="font-black text-lg text-slate-900 mb-1">{{ pkg.duration_minutes }} Menit</div>
                                        <div class="text-primary font-bold text-xl mb-3">Rp {{ pkg.price.toLocaleString('id-ID') }}</div>
                                        <div class="text-xs text-slate-500 mb-3">Tersedia di klinik kami</div>
                                        <div class="mt-auto flex items-center text-sm font-bold text-slate-400 group-hover:text-primary transition-colors">
                                            Pilih Layanan <ArrowRight class="ml-1 h-4 w-4" />
                                        </div>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap');

.font-display {
    font-family: 'DM Sans', ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif;
}

.hide-scrollbar::-webkit-scrollbar {
    display: none;
}

.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 1.5s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

@keyframes marquee {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}

.animate-marquee {
    animation: marquee 30s linear infinite;
}
</style>
