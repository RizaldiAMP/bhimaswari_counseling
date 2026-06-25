<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/ui/PrimaryButton.vue';
import { ref, computed } from 'vue';
import { Pencil, Power, Trash2 } from 'lucide-vue-next';

interface CounselorProfile {
    id: number;
    practitioner_type: 'psychologist' | 'counselor';
    sipp_number: string | null;
    bio: string;
    photo_path: string | null;
    specializations: string[];
    is_visible: boolean;
}

interface Counselor {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    created_at: string;
    counselor_profile: CounselorProfile | null;
    active_bookings_count: number;
}

const props = defineProps<{
    counselors: { data: Counselor[]; links: any };
}>();

const page = usePage();
const showAddForm = ref(false);
const editingId = ref<number | null>(null);
const confirmToggleId = ref<number | null>(null);

// ── Add Form ──
const form = useForm({
    name: '',
    email: '',
    password: '',
    practitioner_type: 'psychologist' as 'psychologist' | 'counselor',
    sipp_number: '',
});

// ── Edit Form ──
const editForm = useForm({
    name: '',
    email: '',
    practitioner_type: 'psychologist' as 'psychologist' | 'counselor',
    sipp_number: '',
    bio: '',
});

const practitionerLabel = (type: string) => type === 'psychologist' ? 'Psikolog' : 'Konselor';

const formatDate = (dateString: string) =>
    new Date(dateString).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' });

const submit = () => {
    form.post(route('admin.counselors.store'), {
        onSuccess: () => { showAddForm.value = false; form.reset(); },
    });
};

const startEdit = (counselor: Counselor) => {
    editingId.value = counselor.id;
    editForm.name = counselor.name;
    editForm.email = counselor.email;
    editForm.practitioner_type = counselor.counselor_profile?.practitioner_type || 'psychologist';
    editForm.sipp_number = counselor.counselor_profile?.sipp_number || '';
    editForm.bio = counselor.counselor_profile?.bio || '';
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.clearErrors();
};

const submitEdit = () => {
    if (editingId.value) {
        editForm.put(route('admin.counselors.update', editingId.value), {
            onSuccess: () => { editingId.value = null; },
        });
    }
};

const toggleActive = (counselor: Counselor) => {
    // Jika akan nonaktifkan dan ada booking aktif, tunjukkan konfirmasi dulu
    if (counselor.is_active && counselor.active_bookings_count > 0) {
        confirmToggleId.value = counselor.id;
        return;
    }

    doToggle(counselor);
};

const doToggle = (counselor: Counselor) => {
    confirmToggleId.value = null;
    useForm({}).post(route('admin.counselors.toggle', counselor.id));
};

const deleteCounselor = (counselor: Counselor) => {
    if (confirm(`Yakin ingin menghapus akun konselor ${counselor.name}? Data yang terhapus tidak dapat dikembalikan.`)) {
        useForm({}).delete(route('admin.counselors.destroy', counselor.id));
    }
};

const globalErrors = computed(() => {
    const errors = page.props.errors as Record<string, string>;
    return errors?.toggle || errors?.delete || null;
});
</script>

<template>
    <Head title="Manajemen Konselor" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manajemen Konselor</h2>
        </template>

        <div class="space-y-6">
            <!-- Global Toggle Error Banner -->
            <div v-if="globalErrors" class="bg-red-50 border border-red-200 rounded-2xl p-4 flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-500 shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                <p class="text-sm text-red-700 font-medium">{{ globalErrors }}</p>
            </div>

            <!-- Flash success message -->
            <div v-if="($page.props.flash as any)?.success" class="bg-green-50 border border-green-200 rounded-2xl p-4 flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500 shrink-0 mt-0.5"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                <p class="text-sm text-green-700 font-medium">{{ ($page.props.flash as any).success }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900">Daftar Konselor & Psikolog</h3>
                    <PrimaryButton size="sm" @click="showAddForm = !showAddForm">
                        {{ showAddForm ? 'Batal' : '+ Buat Akun Konselor' }}
                    </PrimaryButton>
                </div>

                <!-- Add Form -->
                <div v-if="showAddForm" class="p-6 bg-primary-50 border-b border-primary-100">
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                                <input v-model="form.name" type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-slate-900 bg-white" required />
                                <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input v-model="form.email" type="email" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-slate-900 bg-white" required />
                                <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Password Awal</label>
                                <input v-model="form.password" type="password" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-slate-900 bg-white" required />
                                <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Praktisi</label>
                                <select v-model="form.practitioner_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-slate-900 bg-white">
                                    <option value="psychologist">Psikolog</option>
                                    <option value="counselor">Konselor</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor SIPP (opsional)</label>
                                <input v-model="form.sipp_number" type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-slate-900 bg-white" />
                            </div>
                        </div>

                        <PrimaryButton size="sm" type="submit" :disabled="form.processing">
                            Buat Akun
                        </PrimaryButton>
                    </form>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SIPP</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jadwal Aktif</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bergabung</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template v-for="counselor in counselors.data" :key="counselor.id">
                                <!-- Normal Row -->
                                <tr v-if="editingId !== counselor.id" class="hover:bg-gray-50 transition-colors" :class="{ 'opacity-50': !counselor.is_active }">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold" :class="counselor.is_active ? 'bg-primary-100 text-primary-700' : 'bg-gray-200 text-gray-500'">
                                                {{ counselor.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <span class="text-sm font-medium text-gray-900">{{ counselor.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ counselor.email }}</td>
                                    <td class="px-6 py-4">
                                        <span :class="counselor.counselor_profile?.practitioner_type === 'psychologist' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                                            {{ practitionerLabel(counselor.counselor_profile?.practitioner_type || '') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ counselor.counselor_profile?.sipp_number || '-' }}</td>
                                    <td class="px-6 py-4">
                                        <span :class="counselor.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                                            {{ counselor.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span v-if="counselor.active_bookings_count > 0" class="inline-flex items-center rounded-full bg-amber-100 text-amber-700 px-2.5 py-0.5 text-xs font-medium">
                                            {{ counselor.active_bookings_count }} booking
                                        </span>
                                        <span v-else class="text-xs text-gray-400">Tidak ada</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(counselor.created_at) }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="startEdit(counselor)" title="Edit" class="p-1.5 text-primary-600 bg-primary-50 hover:bg-primary-100 rounded-lg transition-colors">
                                                <Pencil class="w-4 h-4" />
                                            </button>
                                            <button
                                                @click="toggleActive(counselor)"
                                                :title="counselor.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                                class="p-1.5 rounded-lg transition-colors"
                                                :class="counselor.is_active ? 'text-amber-600 bg-amber-50 hover:bg-amber-100' : 'text-green-600 bg-green-50 hover:bg-green-100'"
                                            >
                                                <Power class="w-4 h-4" />
                                            </button>
                                            <button @click="deleteCounselor(counselor)" title="Hapus" class="p-1.5 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Confirm toggle warning (inline row) -->
                                <tr v-if="confirmToggleId === counselor.id && editingId !== counselor.id">
                                    <td colspan="8" class="px-6 py-4 bg-amber-50 border-t border-amber-200">
                                        <div class="flex items-start gap-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500 shrink-0 mt-0.5"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-amber-800">
                                                    Konselor ini memiliki {{ counselor.active_bookings_count }} jadwal aktif. Yakin ingin menonaktifkan?
                                                </p>
                                                <p class="text-xs text-amber-600 mt-1">Backend akan menolak jika masih ada jadwal aktif yang berlangsung.</p>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0">
                                                <button @click="doToggle(counselor)" class="px-3 py-1.5 text-xs font-medium bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                                                    Ya, Nonaktifkan
                                                </button>
                                                <button @click="confirmToggleId = null" class="px-3 py-1.5 text-xs font-medium bg-white text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                                                    Batal
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Edit Inline Row -->
                                <tr v-if="editingId === counselor.id" class="bg-primary-50">
                                    <td colspan="8" class="px-6 py-5">
                                        <form @submit.prevent="submitEdit">
                                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                                                    <input v-model="editForm.name" type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-slate-900 bg-white" required />
                                                    <p v-if="editForm.errors.name" class="text-red-500 text-xs mt-1">{{ editForm.errors.name }}</p>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                                    <input v-model="editForm.email" type="email" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-slate-900 bg-white" required />
                                                    <p v-if="editForm.errors.email" class="text-red-500 text-xs mt-1">{{ editForm.errors.email }}</p>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Praktisi</label>
                                                    <select v-model="editForm.practitioner_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-slate-900 bg-white">
                                                        <option value="psychologist">Psikolog</option>
                                                        <option value="counselor">Konselor</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor SIPP</label>
                                                    <input v-model="editForm.sipp_number" type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-slate-900 bg-white" />
                                                    <p v-if="editForm.errors.sipp_number" class="text-red-500 text-xs mt-1">{{ editForm.errors.sipp_number }}</p>
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                                                    <textarea v-model="editForm.bio" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-slate-900 bg-white" placeholder="Deskripsi singkat tentang konselor..."></textarea>
                                                    <p v-if="editForm.errors.bio" class="text-red-500 text-xs mt-1">{{ editForm.errors.bio }}</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <PrimaryButton size="sm" type="submit" :disabled="editForm.processing">
                                                    Simpan Perubahan
                                                </PrimaryButton>
                                                <button type="button" @click="cancelEdit" class="text-sm text-gray-500 hover:text-gray-700 font-medium transition-colors">
                                                    Batal
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div v-if="counselors.data.length === 0" class="p-12 text-center text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4 text-gray-300"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <p class="text-lg font-medium">Belum ada data konselor</p>
                    <p class="text-sm text-gray-400 mt-1">Klik "+ Buat Akun Konselor" untuk menambahkan.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
