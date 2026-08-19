<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table';
import { Search } from 'lucide-vue-next';

const props = defineProps({
    products: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

watch(search, debounce((value) => {
    router.get(route('products.index'), { ...props.filters, search: value }, {
        preserveState: true,
        replace: true
    });
}, 300));

const sortBy = (column) => {
    const currentDir = props.filters.dir || 'desc';
    const currentSort = props.filters.sort || 'orders_30d';
    
    let dir = 'desc';
    if (currentSort === column) {
        dir = currentDir === 'desc' ? 'asc' : 'desc';
    }
    
    router.get(route('products.index'), { ...props.filters, sort: column, dir }, {
        preserveState: true,
        replace: true
    });
};
</script>

<template>
    <Head title="Товары" />

    <AuthenticatedLayout :fullWidth="true">
        <template #header>
            <h2 class="font-semibold text-2xl tracking-tight text-white animate-fade-in">Каталог товаров</h2>
        </template>

        <Card class="mt-6 glass-panel text-zinc-100 animate-slide-up">
            <CardHeader class="flex flex-row items-center justify-between pb-6 border-b border-zinc-800/50">
                <CardTitle class="text-lg text-zinc-200">Список товаров</CardTitle>
                <div class="relative w-80">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-500" />
                    <Input 
                        v-model="search" 
                        placeholder="Поиск по названию или артикулу..." 
                        class="pl-9 bg-zinc-900/50 border-zinc-700/50 text-zinc-100 focus-visible:ring-blue-500" 
                    />
                </div>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="border-zinc-800/50 bg-zinc-900/30 hover:bg-zinc-900/30">
                                <TableHead class="text-zinc-400 w-16 pl-6 py-4">Фото</TableHead>
                                <TableHead class="text-zinc-400 py-4">Артикул WB / Поставщика</TableHead>
                                <TableHead class="text-zinc-400 py-4">Название</TableHead>
                                <TableHead class="text-zinc-400 py-4">Бренд</TableHead>
                                <TableHead class="text-zinc-400 py-4 text-center">ABC-класс</TableHead>
                                <TableHead class="text-zinc-400 py-4 text-right cursor-pointer hover:text-white transition-colors" @click="sortBy('orders_30d')">
                                    <div class="flex items-center justify-end">
                                        Заказы (30 дн)
                                        <span v-if="filters.sort === 'orders_30d' || !filters.sort" class="ml-1">{{ filters.dir === 'asc' ? '↑' : '↓' }}</span>
                                    </div>
                                </TableHead>
                                <TableHead class="text-zinc-400 py-4 text-right cursor-pointer hover:text-white transition-colors" @click="sortBy('revenue_30d')">
                                    <div class="flex items-center justify-end">
                                        Выручка (30 дн)
                                        <span v-if="filters.sort === 'revenue_30d'" class="ml-1">{{ filters.dir === 'asc' ? '↑' : '↓' }}</span>
                                    </div>
                                </TableHead>
                                <TableHead class="text-zinc-400 py-4 pr-6 text-right">Действия</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="product in products.data" :key="product.id" class="border-zinc-800/50 hover:bg-zinc-800/40 transition-colors">
                                <TableCell class="pl-6">
                                    <div class="relative w-12 h-12 rounded-lg overflow-hidden border border-zinc-700/50 shadow-sm">
                                        <img v-if="product.main_image_url" :src="product.main_image_url" class="w-full h-full object-cover" />
                                        <div v-else class="w-full h-full bg-zinc-800 flex items-center justify-center text-[10px] text-zinc-500">Нет фото</div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="font-medium text-blue-400">{{ product.nm_id }}</div>
                                    <div class="text-xs text-zinc-500 mt-0.5">{{ product.vendor_code }}</div>
                                </TableCell>
                                <TableCell class="font-medium text-zinc-200">
                                    <div class="truncate max-w-[250px]" :title="product.title">{{ product.title }}</div>
                                </TableCell>
                                <TableCell class="text-zinc-300">{{ product.brand || 'Без бренда' }}</TableCell>
                                <TableCell class="text-center">
                                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                        :class="{
                                            'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20': product.abc_class === 'A',
                                            'bg-amber-500/10 text-amber-400 border border-amber-500/20': product.abc_class === 'B',
                                            'bg-rose-500/10 text-rose-400 border border-rose-500/20': product.abc_class === 'C',
                                            'bg-zinc-800 text-zinc-400 border border-zinc-700': !product.abc_class
                                        }">
                                        {{ product.abc_class || '—' }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right text-zinc-300">
                                    {{ Number(product.orders_30d || 0).toLocaleString('ru-RU') }} шт.
                                </TableCell>
                                <TableCell class="text-right text-emerald-400 font-medium whitespace-nowrap">
                                    {{ Number(product.revenue_30d || 0).toLocaleString('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }) }}
                                </TableCell>
                                <TableCell class="text-right pr-6">
                                    <Link :href="route('products.show', product.id)">
                                        <Button variant="outline" size="sm" class="bg-zinc-900 border-zinc-700 text-zinc-300 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-all">
                                            Смотреть
                                        </Button>
                                    </Link>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="products.data.length === 0" class="hover:bg-transparent">
                                <TableCell colspan="8" class="h-32 text-center text-zinc-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <Package class="w-8 h-8 text-zinc-700 mb-2" />
                                        <span>Товары не найдены</span>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                
                <div class="flex items-center justify-between p-4 border-t border-zinc-800/50 bg-zinc-900/30" v-if="products.links.length > 3">
                    <div class="text-sm text-zinc-400">
                        Показано с <span class="font-medium text-white">{{ products.from }}</span> по <span class="font-medium text-white">{{ products.to }}</span> из <span class="font-medium text-white">{{ products.total }}</span>
                    </div>
                    <div class="flex space-x-1">
                        <template v-for="(link, key) in products.links" :key="key">
                            <Link 
                                v-if="link.url"
                                :href="link.url" 
                                class="px-3 py-1.5 text-sm font-medium border rounded-md transition-colors"
                                :class="link.active 
                                    ? 'bg-blue-600 text-white border-blue-600' 
                                    : 'bg-zinc-900 border-zinc-700 text-zinc-300 hover:bg-zinc-800'"
                                v-html="link.label.replace('Previous', 'Назад').replace('Next', 'Вперед')"
                            />
                            <span v-else class="px-3 py-1.5 text-sm font-medium border border-zinc-800 rounded-md text-zinc-600" v-html="link.label.replace('Previous', 'Назад').replace('Next', 'Вперед')"></span>
                        </template>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AuthenticatedLayout>
</template>
