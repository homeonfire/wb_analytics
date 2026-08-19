<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Mail, Lock, LogIn, Loader2 } from 'lucide-vue-next';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Вход в систему" />

        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-white mb-2">Вход в панель</h1>
            <p class="text-zinc-400 text-sm">Введите ваши данные для доступа к аналитике</p>
        </div>

        <div v-if="status" class="mb-4 p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-sm font-medium text-emerald-400 text-center">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div class="space-y-2">
                <Label for="email" class="text-zinc-300">Email адрес</Label>
                <div class="relative">
                    <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
                    <Input
                        id="email"
                        type="email"
                        class="pl-10 bg-zinc-950/50 border-zinc-800 focus-visible:ring-blue-500 text-white h-11"
                        v-model="form.email"
                        placeholder="admin@example.com"
                        required
                        autofocus
                        autocomplete="username"
                    />
                </div>
                <div v-if="form.errors.email" class="text-rose-500 text-xs mt-1 animate-fade-in">{{ form.errors.email }}</div>
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <Label for="password" class="text-zinc-300">Пароль</Label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs text-blue-400 hover:text-blue-300 transition-colors"
                    >
                        Забыли пароль?
                    </Link>
                </div>
                <div class="relative">
                    <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
                    <Input
                        id="password"
                        type="password"
                        class="pl-10 bg-zinc-950/50 border-zinc-800 focus-visible:ring-blue-500 text-white h-11"
                        v-model="form.password"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    />
                </div>
                <div v-if="form.errors.password" class="text-rose-500 text-xs mt-1 animate-fade-in">{{ form.errors.password }}</div>
            </div>

            <div class="flex items-center mt-2">
                <label class="flex items-center cursor-pointer group">
                    <div class="relative flex items-center justify-center">
                        <input 
                            type="checkbox" 
                            name="remember" 
                            v-model="form.remember"
                            class="peer appearance-none w-4 h-4 border border-zinc-700 rounded bg-zinc-950/50 checked:bg-blue-600 checked:border-blue-600 transition-all focus:ring-1 focus:ring-blue-500 focus:ring-offset-1 focus:ring-offset-zinc-900 cursor-pointer"
                        />
                        <svg class="absolute w-3 h-3 text-white opacity-0 peer-checked:opacity-100 pointer-events-none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </div>
                    <span class="ms-2 text-sm text-zinc-400 group-hover:text-zinc-300 transition-colors select-none">
                        Запомнить меня
                    </span>
                </label>
            </div>

            <Button
                type="submit"
                class="w-full h-11 bg-gradient-to-r from-blue-600 to-emerald-500 hover:from-blue-500 hover:to-emerald-400 text-white border-0 shadow-lg shadow-blue-900/20 transition-all duration-300 font-medium text-base mt-2"
                :class="{ 'opacity-80 cursor-wait': form.processing }"
                :disabled="form.processing"
            >
                <Loader2 v-if="form.processing" class="w-5 h-5 mr-2 animate-spin" />
                <LogIn v-else class="w-5 h-5 mr-2" />
                Войти в систему
            </Button>
        </form>
    </GuestLayout>
</template>
