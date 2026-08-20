<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Users, Plus, Link as LinkIcon, Search, ArrowRight } from 'lucide-vue-next';

const props = defineProps({
    managers: Array,
    products: Array,
    errors: Object,
});

const isCreateModalOpen = ref(false);
const isBindModalOpen = ref(false);

const createForm = useForm({
    name: '',
    email: '',
    password: '',
});

const bindForm = useForm({
    product_ids: []
});

const activeManager = ref(null);
const searchQuery = ref('');

const filteredProducts = computed(() => {
    if (!searchQuery.value) return props.products;
    const q = searchQuery.value.toLowerCase();
    return props.products.filter(p => 
        (p.title && p.title.toLowerCase().includes(q)) || 
        (p.vendor_code && p.vendor_code.toLowerCase().includes(q))
    );
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    isCreateModalOpen.value = true;
};

const submitCreate = () => {
    createForm.post(route('managers.store'), {
        onSuccess: () => {
            isCreateModalOpen.value = false;
        }
    });
};

const openBindModal = (manager) => {
    activeManager.value = manager;
    bindForm.product_ids = manager.products ? manager.products.map(p => p.id) : [];
    searchQuery.value = '';
    isBindModalOpen.value = true;
};

const toggleProduct = (productId) => {
    const index = bindForm.product_ids.indexOf(productId);
    if (index === -1) {
        bindForm.product_ids.push(productId);
    } else {
        bindForm.product_ids.splice(index, 1);
    }
};

const submitBind = () => {
    if (!activeManager.value) return;
    
    bindForm.post(route('managers.bind', activeManager.value.id), {
        onSuccess: () => {
            isBindModalOpen.value = false;
        }
    });
};
</script>

<template>
    <Head title="Менеджеры" />

    <AuthenticatedLayout :fullWidth="false">
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h2 class="font-semibold text-2xl tracking-tight text-white animate-fade-in flex items-center">
                    <Users class="w-6 h-6 mr-3 text-emerald-400" />
                    Менеджеры
                </h2>
                <Button @click="openCreateModal" class="bg-emerald-600 hover:bg-emerald-700 text-white border-none shadow-lg shadow-emerald-900/20">
                    <Plus class="w-4 h-4 mr-2" />
                    Добавить менеджера
                </Button>
            </div>
        </template>

        <div class="mt-8 animate-slide-up">
            <Card class="glass-panel text-zinc-100 border-zinc-800">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow class="border-zinc-800/50 hover:bg-transparent">
                                <TableHead class="text-zinc-400 py-4 pl-6">ID</TableHead>
                                <TableHead class="text-zinc-400 py-4">Имя</TableHead>
                                <TableHead class="text-zinc-400 py-4">Email</TableHead>
                                <TableHead class="text-zinc-400 py-4 text-center">Привязано товаров</TableHead>
                                <TableHead class="text-zinc-400 py-4 text-right pr-6">Действия</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="manager in managers" :key="manager.id" class="border-zinc-800/30 hover:bg-zinc-800/40">
                                <TableCell class="text-zinc-500 font-medium py-4 pl-6">{{ manager.id }}</TableCell>
                                <TableCell class="font-bold text-zinc-200 py-4">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-zinc-800 border border-zinc-700 flex items-center justify-center text-xs text-emerald-400 mr-3">
                                            {{ manager.name.charAt(0).toUpperCase() }}
                                        </div>
                                        {{ manager.name }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-zinc-400 py-4">{{ manager.email }}</TableCell>
                                <TableCell class="text-center py-4">
                                    <span class="inline-flex items-center justify-center bg-zinc-900 border border-zinc-700 px-3 py-1 rounded-full text-sm font-semibold text-zinc-300">
                                        {{ manager.products_count || 0 }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right pr-6 py-4">
                                    <Link :href="route('managers.show', manager.id)" class="inline-flex items-center rounded-md border border-zinc-700 px-3 py-2 text-sm font-medium text-zinc-300 transition hover:bg-zinc-800 hover:text-white">
                                        Открыть
                                        <ArrowRight class="ml-2 h-4 w-4 text-blue-400" />
                                    </Link>
                                </TableCell>
                            </TableRow>
                            
                            <TableRow v-if="managers.length === 0">
                                <TableCell colspan="5" class="text-center text-zinc-500 py-12">
                                    Менеджеров пока нет. Добавьте первого!
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <!-- Create Manager Modal -->
        <Dialog :open="isCreateModalOpen" @update:open="val => isCreateModalOpen = val">
            <DialogContent class="bg-zinc-950 border-zinc-800 text-zinc-100 sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Добавление менеджера</DialogTitle>
                    <DialogDescription class="text-zinc-500">
                        Создайте новый аккаунт для сотрудника, задав ему email и пароль.
                    </DialogDescription>
                </DialogHeader>
                
                <form @submit.prevent="submitCreate" class="space-y-4 mt-4">
                    <div class="space-y-2">
                        <Label for="name" class="text-zinc-400">Имя</Label>
                        <Input id="name" v-model="createForm.name" type="text" placeholder="Иван Иванов" class="bg-zinc-900 border-zinc-800 focus-visible:ring-emerald-500 text-white" required />
                        <div v-if="createForm.errors.name" class="text-rose-500 text-xs">{{ createForm.errors.name }}</div>
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="email" class="text-zinc-400">Email</Label>
                        <Input id="email" v-model="createForm.email" type="email" placeholder="manager@example.com" class="bg-zinc-900 border-zinc-800 focus-visible:ring-emerald-500 text-white" required />
                        <div v-if="createForm.errors.email" class="text-rose-500 text-xs">{{ createForm.errors.email }}</div>
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="password" class="text-zinc-400">Пароль</Label>
                        <Input id="password" v-model="createForm.password" type="password" placeholder="Минимум 8 символов" class="bg-zinc-900 border-zinc-800 focus-visible:ring-emerald-500 text-white" required />
                        <div v-if="createForm.errors.password" class="text-rose-500 text-xs">{{ createForm.errors.password }}</div>
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

        <!-- Bind Products Modal -->
        <Dialog :open="isBindModalOpen" @update:open="val => isBindModalOpen = val">
            <DialogContent class="bg-zinc-950 border-zinc-800 text-zinc-100 sm:max-w-[700px] max-h-[85vh] flex flex-col p-6">
                <DialogHeader class="mb-4">
                    <DialogTitle>Привязка товаров к менеджеру</DialogTitle>
                    <DialogDescription class="text-zinc-500">
                        Выберите товары, доступ к аналитике которых будет у <b class="text-white">{{ activeManager?.name }}</b>.
                    </DialogDescription>
                </DialogHeader>
                
                <div class="flex-1 flex flex-col overflow-hidden min-h-[400px]">
                    <div class="relative mb-4 shrink-0">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
                        <Input v-model="searchQuery" type="text" placeholder="Поиск по названию или артикулу..." class="pl-10 bg-zinc-900 border-zinc-800 focus-visible:ring-blue-500 text-zinc-200" />
                    </div>
                    
                    <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar">
                        <div v-if="filteredProducts.length === 0" class="text-center text-zinc-500 py-10">
                            Ничего не найдено
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <label 
                                v-for="product in filteredProducts" 
                                :key="product.id"
                                class="flex items-start space-x-3 p-3 rounded-lg border cursor-pointer transition-colors"
                                :class="bindForm.product_ids.includes(product.id) ? 'bg-blue-500/10 border-blue-500/30' : 'bg-zinc-900/50 border-zinc-800/50 hover:bg-zinc-800/50'"
                            >
                                <div class="pt-0.5">
                                    <input 
                                        type="checkbox" 
                                        :checked="bindForm.product_ids.includes(product.id)"
                                        @change="toggleProduct(product.id)"
                                        class="rounded border-zinc-700 bg-zinc-900 text-blue-500 focus:ring-blue-500 focus:ring-offset-zinc-950"
                                    />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-zinc-200 truncate">{{ product.title || 'Без названия' }}</div>
                                    <div class="text-xs text-zinc-500 truncate">{{ product.vendor_code }}</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <DialogFooter class="pt-4 border-t border-zinc-800 mt-4 shrink-0">
                    <div class="flex-1 flex items-center text-sm text-zinc-400">
                        Выбрано: <strong class="text-white ml-1">{{ bindForm.product_ids.length }}</strong>
                    </div>
                    <Button type="button" variant="outline" @click="isBindModalOpen = false" class="border-zinc-700 text-zinc-300 hover:bg-zinc-800">
                        Отмена
                    </Button>
                    <Button type="button" @click="submitBind" :disabled="bindForm.processing" class="bg-blue-600 hover:bg-blue-700 text-white ml-2">
                        Сохранить привязку
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #3f3f46;
    border-radius: 20px;
}
</style>
