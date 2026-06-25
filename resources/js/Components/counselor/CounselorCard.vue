<script setup lang="ts">
import { computed } from 'vue';
import { ShieldCheck } from 'lucide-vue-next';

const props = defineProps<{
    name: string;
    role: string;
    photo: string;
    bio: string;
    practitionerType: 'psychologist' | 'counselor';
    sippNumber?: string | null;
}>();

const title = computed(() => {
    return props.practitionerType === 'psychologist' ? 'Psikolog Klinis Umum' : 'Konselor Praktisi';
});
</script>

<template>
    <div class="group relative bg-white rounded-[2rem] border border-slate-100/80 p-6 md:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_8px_30px_rgba(107,33,168,0.08)] hover:-translate-y-1 transition-all duration-500 overflow-hidden">
        
        <!-- Subtle background accent -->
        <div class="absolute -top-32 -right-32 w-64 h-64 bg-primary/5 rounded-full blur-3xl opacity-50 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>

        <div class="relative flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-6">
            <!-- Avatar with modern glow -->
            <div class="relative shrink-0">
                <div class="absolute inset-0 bg-primary-300 rounded-full blur-xl opacity-20 group-hover:opacity-40 transition-opacity duration-500 transform scale-90"></div>
                <div class="relative w-32 h-32 rounded-full overflow-hidden border-4 border-white bg-slate-50 shadow-md">
                    <img 
                        :src="photo" 
                        :alt="name"
                        class="w-full h-full object-cover object-top transition-transform duration-700 group-hover:scale-105"
                        loading="lazy"
                    />
                </div>
            </div>
            
            <!-- Text Info -->
            <div class="flex-1 mt-2 sm:mt-4">
                <!-- Badge -->
                <span class="inline-flex items-center gap-1.5 text-xs font-extrabold text-primary-700 bg-primary-50 ring-1 ring-primary-100/50 px-4 py-2 rounded-full mb-4 shadow-sm">
                    <ShieldCheck class="w-4 h-4" />
                    {{ title }}
                </span>
                
                <!-- Name -->
                <h3 class="font-black text-2xl text-slate-900 leading-tight mb-2 tracking-tight group-hover:text-primary transition-colors duration-300">
                    {{ name }}
                </h3>

                <!-- SIPP Number -->
                <div v-if="sippNumber" class="text-[11px] font-extrabold text-slate-400 mb-4 uppercase tracking-wider flex items-center justify-center sm:justify-start gap-1">
                    <span>No. SIPP:</span>
                    <span class="text-slate-500 font-mono">{{ sippNumber }}</span>
                </div>
                
                <!-- Bio Description -->
                <p class="text-slate-600 text-sm leading-relaxed mb-5 line-clamp-3">
                    {{ bio }}
                </p>
                

            </div>
        </div>
    </div>
</template>
