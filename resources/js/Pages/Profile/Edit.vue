<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Check, KeyRound, Package, Save, Search, ShieldCheck, Store, UserRound } from 'lucide-vue-next';

const props = defineProps({ status: String, products: Array, selectedProductIds: Array, stores: Array });
const user = usePage().props.auth.user;
const profileForm = useForm({ name: user.name });
const productForm = useForm({ product_ids: [...props.selectedProductIds] });
const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });
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

const toggleProduct = id => {
    const index = productForm.product_ids.indexOf(id);
    index === -1 ? productForm.product_ids.push(id) : productForm.product_ids.splice(index, 1);
};
const updateProfile = () => profileForm.patch(route('profile.update'), { preserveScroll: true });
const updateProducts = () => productForm.patch(route('profile.products.update'), { preserveScroll: true });
const updatePassword = () => passwordForm.put(route('password.update'), { preserveScroll: true, onSuccess: () => passwordForm.reset() });
</script>

<template>
    <Head title="Профиль" />
    <AuthenticatedLayout>
        <template #header>
            <div><h2 class="flex items-center text-2xl font-semibold tracking-tight text-white"><UserRound class="mr-3 h-6 w-6 text-blue-400" />Мой профиль</h2><p class="mt-1 text-sm text-zinc-500">Личные настройки и товары для отслеживания</p></div>
        </template>

        <Tabs default-value="profile" class="mt-6">
            <TabsList class="border border-zinc-800 bg-zinc-900/80">
                <TabsTrigger value="profile">Профиль</TabsTrigger>
                <TabsTrigger value="products">Мои товары</TabsTrigger>
                <TabsTrigger value="security">Безопасность</TabsTrigger>
            </TabsList>

            <TabsContent value="profile" class="mt-5">
                <div class="grid gap-5 lg:grid-cols-[1fr_340px]">
                    <Card class="glass-panel border-zinc-800 text-zinc-100">
                        <CardHeader><CardTitle>Основная информация</CardTitle><CardDescription>Вы можете изменить отображаемое имя. Email используется для входа и меняется только администратором.</CardDescription></CardHeader>
                        <CardContent><form class="space-y-5" @submit.prevent="updateProfile"><div class="space-y-2"><Label for="profile-name">Имя</Label><Input id="profile-name" v-model="profileForm.name" class="border-zinc-800 bg-zinc-900" autocomplete="name" /><div v-if="profileForm.errors.name" class="text-xs text-rose-400">{{ profileForm.errors.name }}</div></div><div class="space-y-2"><Label for="profile-email">Email</Label><Input id="profile-email" :model-value="user.email" class="cursor-not-allowed border-zinc-800 bg-zinc-900/50 text-zinc-500" disabled /></div><Button type="submit" :disabled="profileForm.processing" class="bg-blue-600 text-white hover:bg-blue-500"><Save class="mr-2 h-4 w-4" />Сохранить имя</Button><span v-if="profileForm.recentlySuccessful" class="ml-3 text-sm text-emerald-400">Сохранено</span></form></CardContent>
                    </Card>
                    <Card class="border-zinc-800 bg-zinc-900/50 text-zinc-100"><CardHeader><CardTitle class="flex items-center gap-2 text-base"><Store class="h-4 w-4 text-violet-400" />Доступные магазины</CardTitle><CardDescription>Назначаются только администратором.</CardDescription></CardHeader><CardContent class="space-y-2"><div v-for="store in stores" :key="store.id" class="flex items-center gap-2 rounded-lg border border-zinc-800 bg-zinc-950/50 px-3 py-2 text-sm"><ShieldCheck class="h-4 w-4 text-emerald-400" />{{ store.name }}</div><div v-if="!stores.length" class="rounded-lg border border-dashed border-zinc-700 p-5 text-center text-sm text-zinc-500">Магазины пока не назначены</div><p class="pt-2 text-xs text-zinc-600">Самостоятельно добавить или удалить магазин нельзя.</p></CardContent></Card>
                </div>
            </TabsContent>

            <TabsContent value="products" class="mt-5">
                <Card class="border-zinc-800 text-zinc-100"><CardHeader><div class="flex items-start justify-between gap-4"><div><CardTitle class="flex items-center gap-2"><Package class="h-5 w-5 text-violet-400" />Товары для отслеживания</CardTitle><CardDescription class="mt-1">Выбирайте товары только из магазинов, назначенных вам администратором.</CardDescription></div><Button :disabled="productForm.processing || !stores.length" class="bg-violet-600 text-white hover:bg-violet-500" @click="updateProducts"><Save class="mr-2 h-4 w-4" />Сохранить</Button></div></CardHeader><CardContent>
                    <div v-if="!stores.length" class="rounded-xl border border-dashed border-zinc-700 px-6 py-14 text-center"><Store class="mx-auto h-8 w-8 text-zinc-600" /><p class="mt-3 text-zinc-400">Администратор ещё не назначил вам магазин</p></div>
                    <template v-else><div class="mb-4 grid gap-3 sm:grid-cols-[1fr_220px]"><div class="relative"><Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" /><Input v-model="search" class="border-zinc-800 bg-zinc-900 pl-10" placeholder="Название или артикул..." /></div><Select v-model="storeFilter"><SelectTrigger class="border-zinc-800 bg-zinc-900"><SelectValue /></SelectTrigger><SelectContent class="border-zinc-800 bg-zinc-900"><SelectItem value="all">Все магазины</SelectItem><SelectItem v-for="store in stores" :key="store.id" :value="String(store.id)">{{ store.name }}</SelectItem></SelectContent></Select></div><div class="mb-3 flex justify-between text-xs text-zinc-500"><span>Доступно: {{ filteredProducts.length }}</span><span>Выбрано: {{ productForm.product_ids.length }}</span></div><div class="grid max-h-[620px] gap-2 overflow-y-auto pr-2 sm:grid-cols-2 lg:grid-cols-3"><label v-for="product in filteredProducts" :key="product.id" class="flex cursor-pointer gap-3 rounded-lg border p-3 transition" :class="productForm.product_ids.includes(product.id) ? 'border-violet-500/30 bg-violet-500/10' : 'border-zinc-800 bg-zinc-900/40 hover:bg-zinc-800/60'"><button type="button" class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded border" :class="productForm.product_ids.includes(product.id) ? 'border-violet-500 bg-violet-500 text-white' : 'border-zinc-600'" @click="toggleProduct(product.id)"><Check v-if="productForm.product_ids.includes(product.id)" class="h-3.5 w-3.5" /></button><div class="min-w-0"><div class="truncate text-sm font-medium">{{ product.title || 'Без названия' }}</div><div class="mt-1 truncate text-xs text-zinc-500">{{ product.vendor_code }} · {{ storeName(product.store_id) }}</div></div></label><div v-if="!filteredProducts.length" class="col-span-full py-16 text-center text-zinc-500">Товары не найдены</div></div><div v-if="productForm.errors.product_ids" class="mt-3 text-xs text-rose-400">{{ productForm.errors.product_ids }}</div><div v-if="productForm.recentlySuccessful" class="mt-3 text-sm text-emerald-400">Список товаров сохранён</div></template>
                </CardContent></Card>
            </TabsContent>

            <TabsContent value="security" class="mt-5"><Card class="max-w-2xl border-zinc-800 text-zinc-100"><CardHeader><CardTitle class="flex items-center gap-2"><KeyRound class="h-5 w-5 text-amber-400" />Смена пароля</CardTitle><CardDescription>Введите текущий пароль и задайте новый.</CardDescription></CardHeader><CardContent><form class="space-y-4" @submit.prevent="updatePassword"><div class="space-y-2"><Label>Текущий пароль</Label><Input v-model="passwordForm.current_password" type="password" class="border-zinc-800 bg-zinc-900" /><div v-if="passwordForm.errors.current_password" class="text-xs text-rose-400">{{ passwordForm.errors.current_password }}</div></div><div class="space-y-2"><Label>Новый пароль</Label><Input v-model="passwordForm.password" type="password" class="border-zinc-800 bg-zinc-900" /><div v-if="passwordForm.errors.password" class="text-xs text-rose-400">{{ passwordForm.errors.password }}</div></div><div class="space-y-2"><Label>Повторите новый пароль</Label><Input v-model="passwordForm.password_confirmation" type="password" class="border-zinc-800 bg-zinc-900" /></div><Button type="submit" :disabled="passwordForm.processing" class="bg-amber-600 text-white hover:bg-amber-500"><KeyRound class="mr-2 h-4 w-4" />Обновить пароль</Button><span v-if="passwordForm.recentlySuccessful" class="ml-3 text-sm text-emerald-400">Пароль обновлён</span></form></CardContent></Card></TabsContent>
        </Tabs>
    </AuthenticatedLayout>
</template>
