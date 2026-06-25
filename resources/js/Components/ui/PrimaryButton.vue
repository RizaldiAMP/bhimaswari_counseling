<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    type?: 'button' | 'submit' | 'reset';
    variant?: 'primary' | 'secondary' | 'danger' | 'ghost';
    size?: 'sm' | 'md' | 'lg';
    disabled?: boolean;
}>(), {
    type: 'button',
    variant: 'primary',
    size: 'md',
    disabled: false,
});

const baseClasses = 'inline-flex items-center justify-center font-semibold rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2';

const variantClasses = computed(() => {
    switch (props.variant) {
        case 'primary':
            return 'bg-primary-600 border border-transparent text-white hover:bg-primary-700 focus:ring-primary-500 disabled:bg-primary-300';
        case 'secondary':
            return 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-primary-500 disabled:bg-gray-100 disabled:text-gray-400';
        case 'danger':
            return 'bg-red-600 border border-transparent text-white hover:bg-red-700 focus:ring-red-500 disabled:bg-red-300';
        case 'ghost':
            return 'bg-transparent text-primary-600 hover:bg-primary-50 hover:text-primary-700 focus:ring-primary-500 disabled:text-gray-400';
    }
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'px-3 py-1.5 text-sm';
        case 'md':
            return 'px-4 py-2 text-base';
        case 'lg':
            return 'px-6 py-3 text-lg';
    }
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        :class="[
            baseClasses,
            variantClasses,
            sizeClasses,
            { 'opacity-75 cursor-not-allowed': disabled }
        ]"
    >
        <slot />
    </button>
</template>
