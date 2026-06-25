<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}>();
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap items-center gap-1 justify-center">
        <template v-for="(link, p) in links" :key="p">
            <div 
                v-if="link.url === null" 
                class="px-3 py-1.5 text-sm font-medium text-slate-400 bg-slate-50 border border-slate-100 rounded-lg" 
                v-html="link.label" 
            />
            <Link 
                v-else 
                class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors border"
                :class="link.active ? 'bg-primary text-white border-primary shadow-sm shadow-primary/20' : 'bg-white text-slate-600 hover:bg-slate-50 border-slate-200 hover:text-slate-900'"
                :href="link.url" 
                v-html="link.label" 
            />
        </template>
    </div>
</template>
