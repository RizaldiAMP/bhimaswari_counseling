<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import AuthSplitLayout from '@/Components/auth/AuthSplitLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/ui/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, Lock, Mail } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
    redirect?: string | null;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <Head title="Masuk" />
    <AuthSplitLayout
        hero-title="Dukungan profesional untuk kesehatan mental Anda."
        hero-description="Booking sesi konseling online maupun offline dengan mudah."
        form-title="Selamat Datang"
        form-description="Silakan masuk untuk melanjutkan sesi konseling Anda"
    >
        <div v-if="status" class="mb-4 rounded-lg border px-4 py-3 text-sm font-medium"
             :class="status.includes('dinonaktifkan') ? 'border-red-200 bg-red-50 text-red-700' : 'border-green-200 bg-green-50 text-green-700'">
            {{ status }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Alamat Email" class="mb-1.5 block text-sm font-semibold text-slate-700" />
                <div class="relative">
                    <Mail class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                    <TextInput
                        id="email"
                        type="email"
                        class="block w-full rounded-lg border border-slate-200 bg-slate-50 py-3.5 pl-12 pr-4 text-slate-900 transition-all focus:border-primary focus:ring-2 focus:ring-primary/20"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nama@email.com"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <InputLabel for="password" value="Kata Sandi" class="text-sm font-semibold text-slate-700" />
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs font-bold text-primary hover:underline"
                    >
                        Lupa Password?
                    </Link>
                </div>
                <div class="relative">
                    <Lock class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                    <TextInput
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="block w-full rounded-lg border border-slate-200 bg-slate-50 py-3.5 pl-12 pr-12 text-slate-900 transition-all focus:border-primary focus:ring-2 focus:ring-primary/20"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                    <button
                        type="button"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 transition-colors hover:text-slate-600"
                        @click="showPassword = !showPassword"
                    >
                        <Eye v-if="!showPassword" class="h-5 w-5" />
                        <EyeOff v-else class="h-5 w-5" />
                    </button>
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center gap-2 py-1">
                <Checkbox id="remember" name="remember" v-model:checked="form.remember" />
                <label for="remember" class="text-sm text-slate-600">Ingat saya di perangkat ini</label>
            </div>

            <PrimaryButton
                type="submit"
                class="w-full justify-center !rounded-xl !border-0 !bg-gradient-to-r !from-[#8f4a94] !to-[#723577] py-4 text-sm font-bold text-white shadow-lg shadow-primary/30 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-primary/35 hover:!from-[#9a53a0] hover:!to-[#7b3a80] active:translate-y-0 active:scale-[0.98]"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Memproses...' : 'Masuk ke Akun' }}
            </PrimaryButton>

            <div class="relative my-8">
                <div class="absolute inset-0 flex items-center">
                    <span class="w-full border-t border-slate-200"></span>
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="bg-white px-4 font-medium text-slate-400">Atau masuk dengan</span>
                </div>
            </div>

            <a
                :href="route('auth.google')"
                class="flex w-full items-center justify-center gap-3 rounded-lg border border-slate-200 bg-white py-3.5 text-sm font-semibold text-slate-700 transition-all hover:bg-slate-50"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"></path>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"></path>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"></path>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"></path>
                </svg>
                Google
            </a>
        </form>

        <div class="mt-10 text-center">
            <p class="text-sm text-slate-500">
                Belum memiliki akun?
                <Link :href="route('register')" class="font-bold text-primary hover:underline">
                    Daftar Sekarang
                </Link>
            </p>
        </div>
    </AuthSplitLayout>
</template>
