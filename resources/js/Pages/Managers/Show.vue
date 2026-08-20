<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ArrowLeft, Building2, Check, Mail, Package, Save, Search, ShieldCheck, UserRound } from 'lucide-vue-next';

const props = defineProps({ manager: Object, stores: Array, products: Array });
const storeForm = useForm({ store_ids: props.manager.stores.map(store => store.id) });
const productForm = useForm({ product_ids: props.manager.products.map(product => product.id) });
const permissionForm = useForm({
    is_super_admin: Boolean(props.manager.is_super_admin),
    can_run_sync: Boolean(props.manager.can_run_sync),
    can_manage_plans: Boolean(props.manager.can_manage_plans),
});
const search = ref('');
const storeFilter = ref('all');

const storeName = storeId => props.stores.find(store => store.id === storeId)?.name || `Магазин #${storeId}`;
const filteredProducts = computed(() => {
    const query = search.value.trim().toLowerCase();
    return props.products.filter(product => {
        const matchesStore = storeFilter.value === 'all' || String(product.store_id) === String(storeFilter.value);
        const matchesSearch = !query || product.title?.toLowerCase().includes(query) || product.vendor_code?.toLowerCase().includes(query);
        return matchesStore && matchesSearch;
    });
});

const toggle = (items, id) => {
    const index = items.indexOf(id);
    index === -1 ? items.push(id) : items.splice(index, 1);
};
const saveStores = () => storeForm.post(route('managers.stores.bind', props.manager.id), { preserveScroll: true });
const saveProducts = () => productForm.post(route('managers.bind', props.manager.id), { preserveScroll: true });
const savePermissions = () => permissionForm.patch(route('managers.permissions.update', props.manager.id), { preserveScroll: true });
</script>

<template>
    <Head :title="manager.name" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('managers.index')" class="rounded-lg border border-zinc-800 p-2 text-zinc-400 transition hover:bg-zinc-800 hover:text-white"><ArrowLeft class="h-5 w-5" /></Link>
                <div><h2 class="text-2xl font-semibold tracking-tight text-white">{{ manager.name }}</h2><p class="mt-1 flex items-center gap-1.5 text-sm text-zinc-500"><Mail class="h-3.5 w-3.5" />{{ manager.email }}</p></div>
            </div>
        </template>

        <div class="mt-6 grid gap-6 lg:grid-cols-[340px_1fr]">
            <div class="space-y-6">
                <Card class="glass-panel border-zinc-800 text-zinc-100">
                    <CardContent class="pt-6">
                        <div class="flex items-center gap-4"><div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-emerald-500/20 bg-emerald-500/10"><UserRound class="h-7 w-7 text-emerald-400" /></div><div><div class="text-lg font-semibold">{{ manager.name }}</div><div class="text-sm text-zinc-500">{{ manager.is_super_admin ? 'Супер-администратор' : 'Менеджер' }} #{{ manager.id }}</div></div></div>
                        <div class="mt-5 grid grid-cols-2 gap-3"><div class="rounded-xl border border-zinc-800 bg-zinc-900/60 p-3"><div class="text-2xl font-semibold">{{ manager.stores.length }}</div><div class="text-xs text-zinc-500">магазинов</div></div><div class="rounded-xl border border-zinc-800 bg-zinc-900/60 p-3"><div class="text-2xl font-semibold">{{ productForm.product_ids.length }}</div><div class="text-xs text-zinc-500">товаров</div></div></div>
                    </CardContent>
                </Card>

                <Card class="border-zinc-800 text-zinc-100">
                    <CardHeader><CardTitle class="text-base">Роль и права доступа</CardTitle><CardDescription>Роль определяет доступ пользователя к разделам и магазинам.</CardDescription></CardHeader>
                    <CardContent class="space-y-3">
                        <label class="flex cursor-pointer items-start justify-between gap-4 rounded-lg border border-amber-500/20 bg-amber-500/5 p-3">
                            <div><div class="flex items-center gap-2 text-sm font-medium"><ShieldCheck class="h-4 w-4 text-amber-400" />Супер-администратор</div><div class="mt-1 text-xs leading-relaxed text-zinc-500">Полный доступ ко всем разделам и всем магазинам без отдельных привязок.</div></div>
                            <button type="button" class="flex h-5 w-5 shrink-0 items-center justify-center rounded border" :class="permissionForm.is_super_admin ? 'border-amber-500 bg-amber-500 text-white' : 'border-zinc-600'" @click="permissionForm.is_super_admin = !permissionForm.is_super_admin"><Check v-if="permissionForm.is_super_admin" class="h-3.5 w-3.5" /></button>
                        </label>
                        <label v-if="!permissionForm.is_super_admin" class="flex cursor-pointer items-start justify-between gap-4 rounded-lg border border-zinc-800 bg-zinc-900/40 p-3">
                            <div><div class="text-sm font-medium">Ручная синхронизация</div><div class="mt-1 text-xs leading-relaxed text-zinc-500">Разрешить запуск команд для назначенных магазинов. Расписания останутся недоступны.</div></div>
                            <button type="button" class="flex h-5 w-5 shrink-0 items-center justify-center rounded border" :class="permissionForm.can_run_sync ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-zinc-600'" @click="permissionForm.can_run_sync = !permissionForm.can_run_sync"><Check v-if="permissionForm.can_run_sync" class="h-3.5 w-3.5" /></button>
                        </label>
                        <div v-else class="rounded-lg border border-zinc-800 bg-zinc-900/40 p-3 text-xs leading-relaxed text-zinc-500">Супер-администратору ручная синхронизация доступна автоматически.</div>
                        <label v-if="!permissionForm.is_super_admin" class="flex cursor-pointer items-start justify-between gap-4 rounded-lg border border-zinc-800 bg-zinc-900/40 p-3">
                            <div><div class="text-sm font-medium">Управление план-фактом</div><div class="mt-1 text-xs leading-relaxed text-zinc-500">Разрешить назначать планы в карточках доступных менеджеру товаров.</div></div>
                            <button type="button" class="flex h-5 w-5 shrink-0 items-center justify-center rounded border" :class="permissionForm.can_manage_plans ? 'border-violet-500 bg-violet-500 text-white' : 'border-zinc-600'" @click="permissionForm.can_manage_plans = !permissionForm.can_manage_plans"><Check v-if="permissionForm.can_manage_plans" class="h-3.5 w-3.5" /></button>
                        </label>
                        <Button class="w-full bg-emerald-600 text-white hover:bg-emerald-500" :disabled="permissionForm.processing" @click="savePermissions"><Save class="mr-2 h-4 w-4" />Сохранить роль и права</Button>
                    </CardContent>
                </Card>

                <Card v-if="!permissionForm.is_super_admin" class="border-zinc-800 text-zinc-100">
                    <CardHeader><CardTitle class="flex items-center gap-2 text-base"><Building2 class="h-4 w-4 text-blue-400" />Доступ к магазинам</CardTitle><CardDescription>Менеджер сможет переключаться только между выбранными магазинами.</CardDescription></CardHeader>
                    <CardContent class="space-y-2">
                        <label v-for="store in stores" :key="store.id" class="flex cursor-pointer items-center justify-between rounded-lg border p-3 transition" :class="storeForm.store_ids.includes(store.id) ? 'border-blue-500/30 bg-blue-500/10' : 'border-zinc-800 bg-zinc-900/40 hover:bg-zinc-800/60'">
                            <span class="text-sm font-medium">{{ store.name }}</span><button type="button" class="flex h-5 w-5 items-center justify-center rounded border" :class="storeForm.store_ids.includes(store.id) ? 'border-blue-500 bg-blue-500 text-white' : 'border-zinc-600'" @click="toggle(storeForm.store_ids, store.id)"><Check v-if="storeForm.store_ids.includes(store.id)" class="h-3.5 w-3.5" /></button>
                        </label>
                        <div v-if="storeForm.errors.store_ids" class="text-xs text-rose-400">{{ storeForm.errors.store_ids }}</div>
                        <Button class="mt-3 w-full bg-blue-600 text-white hover:bg-blue-500" :disabled="storeForm.processing" @click="saveStores"><Save class="mr-2 h-4 w-4" />Сохранить магазины</Button>
                    </CardContent>
                </Card>
            </div>

            <Card v-if="!permissionForm.is_super_admin" class="border-zinc-800 text-zinc-100">
                <CardHeader><div class="flex items-start justify-between gap-4"><div><CardTitle class="flex items-center gap-2"><Package class="h-5 w-5 text-violet-400" />Привязанные товары</CardTitle><CardDescription class="mt-1">Доступны товары только из назначенных выше магазинов.</CardDescription></div><Button class="bg-violet-600 text-white hover:bg-violet-500" :disabled="productForm.processing" @click="saveProducts"><Save class="mr-2 h-4 w-4" />Сохранить товары</Button></div></CardHeader>
                <CardContent>
                    <div v-if="!manager.stores.length" class="rounded-xl border border-dashed border-zinc-700 px-6 py-14 text-center"><Building2 class="mx-auto h-8 w-8 text-zinc-600" /><p class="mt-3 text-zinc-400">Сначала назначьте менеджеру хотя бы один магазин</p></div>
                    <template v-else>
                        <div class="mb-4 grid gap-3 sm:grid-cols-[1fr_220px]"><div class="relative"><Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" /><Input v-model="search" class="border-zinc-800 bg-zinc-900 pl-10" placeholder="Название или артикул..." /></div><Select v-model="storeFilter"><SelectTrigger class="border-zinc-800 bg-zinc-900"><SelectValue placeholder="Все магазины" /></SelectTrigger><SelectContent class="border-zinc-800 bg-zinc-900"><SelectItem value="all">Все магазины</SelectItem><SelectItem v-for="store in manager.stores" :key="store.id" :value="String(store.id)">{{ store.name }}</SelectItem></SelectContent></Select></div>
                        <div class="mb-3 flex items-center justify-between text-xs text-zinc-500"><span>Показано: {{ filteredProducts.length }}</span><span>Выбрано: {{ productForm.product_ids.length }}</span></div>
                        <div class="grid max-h-[620px] gap-2 overflow-y-auto pr-2 sm:grid-cols-2">
                            <label v-for="product in filteredProducts" :key="product.id" class="flex cursor-pointer gap-3 rounded-lg border p-3 transition" :class="productForm.product_ids.includes(product.id) ? 'border-violet-500/30 bg-violet-500/10' : 'border-zinc-800 bg-zinc-900/40 hover:bg-zinc-800/60'">
                                <button type="button" class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded border" :class="productForm.product_ids.includes(product.id) ? 'border-violet-500 bg-violet-500 text-white' : 'border-zinc-600'" @click="toggle(productForm.product_ids, product.id)"><Check v-if="productForm.product_ids.includes(product.id)" class="h-3.5 w-3.5" /></button>
                                <div class="min-w-0"><div class="truncate text-sm font-medium">{{ product.title || 'Без названия' }}</div><div class="mt-1 flex gap-2 text-xs text-zinc-500"><span>{{ product.vendor_code }}</span><span>·</span><span>{{ storeName(product.store_id) }}</span></div></div>
                            </label>
                            <div v-if="!filteredProducts.length" class="col-span-full py-16 text-center text-zinc-500">Товары не найдены</div>
                        </div>
                        <div v-if="productForm.errors.product_ids" class="mt-3 text-xs text-rose-400">{{ productForm.errors.product_ids }}</div>
                    </template>
                </CardContent>
            </Card>
            <Card v-else class="border-zinc-800 text-zinc-100">
                <CardContent class="flex min-h-[260px] flex-col items-center justify-center p-8 text-center"><ShieldCheck class="h-12 w-12 text-amber-400" /><h3 class="mt-4 text-lg font-semibold">Полный доступ</h3><p class="mt-2 max-w-md text-sm leading-relaxed text-zinc-500">Супер-администратор видит все магазины и товары. Отдельные привязки для этой роли не требуются.</p></CardContent>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
