<script setup>
import { ref } from 'vue';
import Sidebar from '@/Components/Sidebar.vue';

defineProps({
    fullWidth: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <div class="min-h-screen bg-zinc-950 flex relative">
        <!-- Optional global background gradients for extra premium feel -->
        <div class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-blue-900/20 to-transparent pointer-events-none z-0"></div>

        <!-- Sidebar (fixed) -->
        <Sidebar class="z-20 shadow-2xl shadow-black/50" />

        <div class="flex-1 flex flex-col h-screen overflow-hidden relative z-10">
            <!-- Top Header -->
            <header class="bg-zinc-900/40 backdrop-blur-md border-b border-zinc-800/50 sticky top-0 z-10" v-if="$slots.header">
                <div class="max-w-full mx-auto py-4 px-8 text-zinc-100 flex items-center justify-between">
                    <div class="w-full">
                        <slot name="header" />
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-auto p-8 relative">
                <div :class="[fullWidth ? 'max-w-full' : 'max-w-7xl', 'mx-auto']">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
