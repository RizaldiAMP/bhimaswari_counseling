<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/ui/PrimaryButton.vue';
import { ref, computed } from 'vue';
import { Pencil, Power, Trash2 } from 'lucide-vue-next';

interface ServicePrice {
    id: number;
    service_type: 'chat' | 'online' | 'offline';
    practitioner_type: 'psychologist' | 'counselor';
    duration_minutes: number;
    price: number;
    is_active: boolean;
}

interface PriceHistory {
    id: number;
    service_price_id: number;
    old_price: number | null;
    new_price: number;
    change_reason: string;
    changed_by_user: { name: string } | null;
    created_at: string;
}

const props = defineProps<{
    prices: ServicePrice[];
    history: PriceHistory[];
}>();

const page = usePage();
const showAddForm = ref(false);
const editingId = ref<number | null>(null);

const addForm = useForm({
    service_type: 'chat' as 'chat' | 'online' | 'offline',
    practitioner_type: 'psychologist' as 'psychologist' | 'counselor',
    duration_minutes: 60,
    price: 0,
});

const editForm = useForm({
    price: 0,
    change_reason: '',
});

const serviceTypeLabel = (type: string) => {
    switch (type) {
        case 'chat': return 'Chat';
        case 'online': return 'Online';
        case 'offline': return 'Offline';
        default: return type;
    }
};

const practitionerLabel = (type: string) => type === 'psychologist' ? 'Psikolog' : 'Konselor';

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(amount);

const formatDate = (dateString: string) =>
    new Date(dateString).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });

const submitAdd = () => {
    addForm.post(route('admin.prices.store'), {
        onSuccess: () => { showAddForm.value = false; addForm.reset(); },
    });
};

const startEdit = (price: ServicePrice) => {
    editingId.value = price.id;
    editForm.price = price.price;
    editForm.change_reason = '';
};

const submitEdit = () => {
    if (editingId.value) {
        editForm.put(route('admin.prices.update', editingId.value), {
            onSuccess: () => { editingId.value = null; },
        });
    }
};

const toggleActive = (price: ServicePrice) => {
    useForm({}).post(route('admin.prices.toggle', price.id));
};

const deletePrice = (price: ServicePrice) => {
    if (confirm('Yakin ingin menghapus harga ini?')) {
        useForm({}).delete(route('admin.prices.destroy', price.id));
    }
};

const globalErrors = computed(() => {
    const errors = page.props.errors as Record<string, string>;
    return errors?.delete || null;
});
</script>

<template>
    <Head title="Harga Layanan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manajemen Harga Layanan</h2>
        </template>

        <div class="space-y-6">
            <!-- Global Error Banner -->
            <div v-if="globalErrors" class="bg-red-50 border border-red-200 rounded-2xl p-4 flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-500 shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                <p class="text-sm text-red-700 font-medium">{{ globalErrors }}</p>
            </div>

            <!-- Flash success message -->
            <div v-if="($page.props.flash as any)?.success" class="bg-green-50 border border-green-200 rounded-2xl p-4 flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500 shrink-0 mt-0.5"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                <p class="text-sm text-green-700 font-medium">{{ ($page.props.flash as any).success }}</p>
            </div>

            <!-- Prices Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900">Daftar Harga Aktif</h3>
                    <PrimaryButton size="sm" @click="showAddForm = !showAddForm">
                        {{ showAddForm ? 'Batal' : '+ Tambah Harga' }}
                    </PrimaryButton>
                </div>

                <!-- Add Form -->
                <div v-if="showAddForm" class="p-6 bg-primary-50 border-b border-primary-100">
                    <!-- Error banner -->
                    <div v-if="addForm.errors.service_type" class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3 flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-500 shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                        <p class="text-sm text-red-700 font-medium">{{ addForm.errors.service_type }}</p>
                    </div>
                    <form @submit.prevent="submitAdd" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Layanan</label>
                            <select v-model="addForm.service_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-slate-900 bg-white">
                                <option value="chat">Chat</option>
                                <option value="online">Online</option>
                                <option value="offline">Offline</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Praktisi</label>
                            <select v-model="addForm.practitioner_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-slate-900 bg-white">
                                <option value="psychologist">Psikolog</option>
                                <option value="counselor">Konselor</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Durasi (Menit)</label>
                            <input v-model.number="addForm.duration_minutes" type="number" min="15" step="15" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-slate-900 bg-white" placeholder="Cth: 60" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Harga (Rp)</label>
                            <input v-model.number="addForm.price" type="number" min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-slate-900 bg-white" />
                            <p v-if="addForm.errors.price" class="text-red-500 text-xs mt-1">{{ addForm.errors.price }}</p>
                        </div>
                        <div class="flex items-end">
                            <PrimaryButton size="sm" type="submit" :disabled="addForm.processing" class="w-full justify-center">Simpan</PrimaryButton>
                        </div>
                    </form>
                </div>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Layanan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Praktisi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durasi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Harga</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="price in prices" :key="price.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 capitalize">{{ serviceTypeLabel(price.service_type) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ practitionerLabel(price.practitioner_type) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ price.duration_minutes }} menit</td>
                            <td class="px-6 py-4">
                                <template v-if="editingId === price.id">
                                    <div class="flex flex-col gap-2">
                                        <input v-model.number="editForm.price" type="number" min="0" class="w-32 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-slate-900 bg-white" />
                                        <input v-model="editForm.change_reason" type="text" placeholder="Alasan perubahan" class="w-48 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm text-slate-900 bg-white" />
                                    </div>
                                </template>
                                <template v-else>
                                    <span class="text-sm font-bold text-gray-900">{{ formatCurrency(price.price) }}</span>
                                </template>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="price.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                                    {{ price.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <template v-if="editingId === price.id">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="submitEdit" class="text-sm px-3 py-1.5 bg-green-500 text-white rounded-md hover:bg-green-600 font-medium transition-colors">Simpan</button>
                                        <button @click="editingId = null" class="text-sm px-3 py-1.5 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors">Batal</button>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="startEdit(price)" title="Edit" class="p-1.5 text-primary-600 bg-primary-50 hover:bg-primary-100 rounded-lg transition-colors">
                                            <Pencil class="w-4 h-4" />
                                        </button>
                                        <button @click="toggleActive(price)" :title="price.is_active ? 'Nonaktifkan' : 'Aktifkan'" class="p-1.5 rounded-lg transition-colors" :class="price.is_active ? 'text-amber-600 bg-amber-50 hover:bg-amber-100' : 'text-green-600 bg-green-50 hover:bg-green-100'">
                                            <Power class="w-4 h-4" />
                                        </button>
                                        <button @click="deletePrice(price)" title="Hapus" class="p-1.5 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Price History -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900">Riwayat Perubahan Harga</h3>
                </div>
                <div v-if="history.length === 0" class="p-8 text-center text-gray-500 text-sm">Belum ada riwayat perubahan.</div>
                <ul v-else class="divide-y divide-gray-100">
                    <li v-for="item in history" :key="item.id" class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                        <div>
                            <p class="text-sm text-gray-900">
                                <span v-if="item.old_price !== null">{{ formatCurrency(item.old_price) }} → </span>
                                <span class="font-bold">{{ formatCurrency(item.new_price) }}</span>
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ item.change_reason }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">{{ item.changed_by_user?.name || 'System' }}</p>
                            <p class="text-xs text-gray-400">{{ formatDate(item.created_at) }}</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
