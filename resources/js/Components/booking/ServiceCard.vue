<script setup lang="ts">
import { computed } from 'vue';
import { MessageCircle, Video, Users } from 'lucide-vue-next';

interface ServicePrice {
    id: number;
    service_type: 'chat' | 'online' | 'offline';
    practitioner_type: 'psychologist' | 'counselor';
    duration_minutes: number;
    price: number;
    is_active: boolean;
}

const props = defineProps<{
    service: ServicePrice;
}>();

const formatRupiah = (number: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(number);
};

const serviceLabel = computed(() => {
    switch (props.service.service_type) {
        case 'chat': return 'Konseling Chat';
        case 'online': return 'Konseling Online';
        case 'offline': return 'Konseling Offline';
        default: return props.service.service_type;
    }
});

const practitionerLabel = computed(() => {
    return props.service.practitioner_type === 'psychologist' ? 'Psikolog' : 'Konselor';
});

defineEmits(['select']);
</script>

<template>
    <div class="bg-[#1f2128] rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 border border-[#2a2d3a] group h-full flex flex-col p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="text-primary-400 text-sm font-bold">
                {{ service.duration_minutes }} Menit
            </div>
            
            <div class="w-10 h-10 rounded-full border border-[#2a2d3a] flex items-center justify-center text-primary-400 group-hover:bg-primary-500 group-hover:text-white group-hover:border-primary-500 transition-all duration-300">
                <MessageCircle v-if="service.service_type === 'chat'" class="w-5 h-5" />
                <Video v-else-if="service.service_type === 'online'" class="w-5 h-5" />
                <Users v-else class="w-5 h-5" />
            </div>
        </div>
        
        <h3 class="text-lg font-bold text-slate-200 mt-2">
            {{ serviceLabel }}
        </h3>
        <p class="text-slate-500 text-sm mb-6">
            Sesi bersama {{ practitionerLabel }}
        </p>
        
        <div class="border-t border-[#2a2d3a] mb-6"></div>
        
        <div class="mt-auto">
            <div class="flex items-baseline gap-1 mb-6">
                <span class="text-2xl font-bold text-white">{{ formatRupiah(service.price) }}</span>
                <span class="text-slate-500 text-xs font-medium">/ sesi</span>
            </div>
            
            <button 
                @click="$emit('select', service)"
                class="w-full py-3 rounded-xl bg-[#17191f] border border-[#2a2d3a] text-slate-500 text-sm font-bold hover:bg-primary-500 hover:text-white hover:border-primary-500 transition-all duration-300 shadow-inner"
            >
                Pilih Layanan
            </button>
        </div>
    </div>
</template>
