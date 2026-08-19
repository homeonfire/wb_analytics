<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-zinc-950 flex flex-col justify-center items-center relative overflow-hidden">
        <!-- Background Elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none z-0">
            <div class="absolute -top-[30%] -left-[10%] w-[70%] h-[70%] rounded-full bg-blue-900/20 blur-[120px]"></div>
            <div class="absolute top-[60%] -right-[10%] w-[60%] h-[60%] rounded-full bg-emerald-900/20 blur-[120px]"></div>
        </div>

        <div class="z-10 w-full sm:max-w-md px-6">
            <div class="flex justify-center mb-8 animate-fade-in">
                <Link href="/" class="flex items-center gap-3 group">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg shadow-blue-500/30 group-hover:shadow-blue-500/50 transition-all duration-300">
                        <span class="font-bold text-white text-xl">WB</span>
                    </div>
                    <span class="font-bold text-3xl text-zinc-100 tracking-tight group-hover:text-white transition-colors">Analytics</span>
                </Link>
            </div>

            <div class="bg-zinc-900/60 backdrop-blur-xl border border-zinc-800/80 rounded-2xl shadow-2xl p-8 animate-slide-up relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-emerald-400"></div>
                <slot />
            </div>
        </div>
        
        <div class="mt-8 text-zinc-600 text-sm z-10 animate-fade-in">
            &copy; {{ new Date().getFullYear() }} WB Analytics System
        </div>
    </div>
</template>
