<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Store as StoreIcon, Plus, Trash2, Key, CheckCircle2, XCircle } from 'lucide-vue-next';

const props = defineProps({
    stores: Array,
});

const isCreateModalOpen = ref(false);

const createForm = useForm({
    name: '',
    api_key_standard: '',
    api_key_stat: '',
    api_key_advert: '',
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    isCreateModalOpen.value = true;
};

const submitCreate = () => {
    createForm.post(route('stores.store'), {
        onSuccess: () => {
            isCreateModalOpen.value = false;
        }
    });
};

const deleteStore = (id) => {
    if (confirm('Вы уверены, что хотите удалить этот магазин? Все привязанные данные могут быть потеряны.')) {
        router.delete(route('stores.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Магазины" />

    <AuthenticatedLayout :fullWidth="false">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h2 class="font-semibold text-2xl tracking-tight text-white animate-fade-in flex items-center">
                    <StoreIcon class="w-6 h-6 mr-3 text-emerald-400" />
                    Магазины (Кабинеты)
                </h2>
                <Button @click="openCreateModal" class="bg-emerald-600 hover:bg-emerald-700 text-white border-none shadow-lg shadow-emerald-900/20">
                    <Plus class="w-4 h-4 mr-2" />
                    Добавить магазин
                </Button>
            </div>
        </template>

        <div class="mt-8 animate-slide-up">
            <Card class="glass-panel text-zinc-100 border-zinc-800">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow class="border-zinc-800/50 hover:bg-transparent">
                                <TableHead class="text-zinc-400 py-4 pl-6 w-16">ID</TableHead>
                                <TableHead class="text-zinc-400 py-4">Название</TableHead>
                                <TableHead class="text-zinc-400 py-4 text-center">API Контент</TableHead>
                                <TableHead class="text-zinc-400 py-4 text-center">API Статистика</TableHead>
                                <TableHead class="text-zinc-400 py-4 text-center">API Реклама</TableHead>
                                <TableHead class="text-zinc-400 py-4 text-right pr-6">Действия</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="store in stores" :key="store.id" class="border-zinc-800/30 hover:bg-zinc-800/40 group">
                                <TableCell class="text-zinc-500 font-medium py-4 pl-6">{{ store.id }}</TableCell>
                                <TableCell class="font-bold text-zinc-200 py-4">
                                    {{ store.name }}
                                </TableCell>
                                <TableCell class="text-center py-4">
                                    <CheckCircle2 v-if="store.api_key_standard" class="w-5 h-5 text-emerald-400 mx-auto" />
                                    <XCircle v-else class="w-5 h-5 text-zinc-600 mx-auto" />
                                </TableCell>
                                <TableCell class="text-center py-4">
                                    <CheckCircle2 v-if="store.api_key_stat" class="w-5 h-5 text-emerald-400 mx-auto" />
                                    <XCircle v-else class="w-5 h-5 text-zinc-600 mx-auto" />
                                </TableCell>
                                <TableCell class="text-center py-4">
                                    <CheckCircle2 v-if="store.api_key_advert" class="w-5 h-5 text-emerald-400 mx-auto" />
                                    <XCircle v-else class="w-5 h-5 text-zinc-600 mx-auto" />
                                </TableCell>
                                <TableCell class="text-right pr-6 py-4">
                                    <Button variant="ghost" size="sm" @click="deleteStore(store.id)" class="text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <Trash2 class="w-4 h-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                            
                            <TableRow v-if="stores.length === 0">
                                <TableCell colspan="6" class="text-center text-zinc-500 py-12">
                                    Магазины еще не добавлены.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <!-- Create Store Modal -->
        <Dialog :open="isCreateModalOpen" @update:open="val => isCreateModalOpen = val">
            <DialogContent class="bg-zinc-950 border-zinc-800 text-zinc-100 sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>Добавление магазина</DialogTitle>
                    <DialogDescription class="text-zinc-500">
                        Введите название и ключи API Wildberries для синхронизации данных.
                    </DialogDescription>
                </DialogHeader>
                
                <form @submit.prevent="submitCreate" class="space-y-4 mt-4">
                    <div class="space-y-2">
                        <Label for="name" class="text-zinc-400">Название магазина</Label>
                        <Input id="name" v-model="createForm.name" type="text" placeholder="ИП Иванов И.И." class="bg-zinc-900 border-zinc-800 focus-visible:ring-emerald-500 text-white" required />
                        <div v-if="createForm.errors.name" class="text-rose-500 text-xs">{{ createForm.errors.name }}</div>
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="api_key_standard" class="text-zinc-400 flex items-center">
                            <Key class="w-3 h-3 mr-1" /> API-ключ (Стандартный / Контент)
                        </Label>
                        <Input id="api_key_standard" v-model="createForm.api_key_standard" type="password" placeholder="Токен..." class="bg-zinc-900 border-zinc-800 focus-visible:ring-emerald-500 text-white font-mono text-xs" />
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="api_key_stat" class="text-zinc-400 flex items-center">
                            <Key class="w-3 h-3 mr-1" /> API-ключ (Статистика)
                        </Label>
                        <Input id="api_key_stat" v-model="createForm.api_key_stat" type="password" placeholder="Токен..." class="bg-zinc-900 border-zinc-800 focus-visible:ring-emerald-500 text-white font-mono text-xs" />
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="api_key_advert" class="text-zinc-400 flex items-center">
                            <Key class="w-3 h-3 mr-1" /> API-ключ (Реклама)
                        </Label>
                        <Input id="api_key_advert" v-model="createForm.api_key_advert" type="password" placeholder="Токен..." class="bg-zinc-900 border-zinc-800 focus-visible:ring-emerald-500 text-white font-mono text-xs" />
                    </div>
                    
                    <DialogFooter class="pt-4">
                        <Button type="button" variant="outline" @click="isCreateModalOpen = false" class="border-zinc-700 text-zinc-300 hover:bg-zinc-800">
                            Отмена
                        </Button>
                        <Button type="submit" :disabled="createForm.processing" class="bg-emerald-600 hover:bg-emerald-700 text-white">
                            Сохранить
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
