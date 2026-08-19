<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table';
import { Search, ShoppingCart, TrendingUp } from 'lucide-vue-next';

const props = defineProps({
    data: Object,
    type: String,
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
    router.get(route('orders.index'), { search: value, type: props.type }, {
        preserveState: true,
        replace: true
    });
}, 300));

const switchType = (newType) => {
    if (props.type === newType) return;
    router.get(route('orders.index'), { search: search.value, type: newType }, {
        preserveState: true,
        replace: true
    });
};
</script>

<template>
    <Head title="Заказы и Продажи" />

    <AuthenticatedLayout :fullWidth="false">
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between w-full gap-4">
                <div class="flex flex-col md:flex-row md:items-center gap-4">
                    <h2 class="font-semibold text-2xl tracking-tight text-white animate-fade-in">Заказы и Продажи</h2>
                    
                    <!-- Tabs -->
                    <div class="flex items-center bg-zinc-900/80 p-1 rounded-lg border border-zinc-800">
                        <button 
                            @click="switchType('orders')"
                            class="flex items-center px-4 py-2 text-sm font-medium rounded-md transition-all duration-200"
                            :class="type === 'orders' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/20' : 'text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800'"
                        >
                            <ShoppingCart class="w-4 h-4 mr-2" />
                            Сырые заказы
                        </button>
                        <button 
                            @click="switchType('sales')"
                            class="flex items-center px-4 py-2 text-sm font-medium rounded-md transition-all duration-200"
                            :class="type === 'sales' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/20' : 'text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800'"
                        >
                            <TrendingUp class="w-4 h-4 mr-2" />
                            Продажи / Выкупы
                        </button>
                    </div>
                </div>

                <div class="relative w-full md:w-80">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-500" />
                    <Input 
                        v-model="search" 
                        placeholder="Поиск по ID, Баркоду или Артикулу..." 
                        class="pl-9 bg-zinc-900/80 border-zinc-700/50 text-zinc-100 focus-visible:ring-blue-500 w-full shadow-sm" 
                    />
                </div>
            </div>
        </template>

        <Card class="mt-4 glass-panel text-zinc-100 animate-slide-up border-zinc-800">
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="border-zinc-800/50 bg-zinc-900/30 hover:bg-zinc-900/30">
                                <TableHead class="text-zinc-400 pl-6 py-4">Дата</TableHead>
                                <TableHead class="text-zinc-400 py-4">{{ type === 'orders' ? 'ID Заказа (SRID)' : 'ID Продажи (S-ID)' }}</TableHead>
                                <TableHead class="text-zinc-400 py-4">Товар (Арт / Штрихкод)</TableHead>
                                
                                <template v-if="type === 'orders'">
                                    <TableHead class="text-zinc-400 py-4 text-right">Цена со скидкой</TableHead>
                                    <TableHead class="text-zinc-400 pr-6 py-4 text-center">Статус</TableHead>
                                </template>
                                
                                <template v-if="type === 'sales'">
                                    <TableHead class="text-zinc-400 py-4 text-right">Цена СПП</TableHead>
                                    <TableHead class="text-zinc-400 py-4 text-right">К перечислению</TableHead>
                                    <TableHead class="text-zinc-400 pr-6 py-4 text-right">Регион</TableHead>
                                </template>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in data.data" :key="item.id" class="border-zinc-800/50 hover:bg-zinc-800/40 transition-colors">
                                <TableCell class="pl-6 text-zinc-300">
                                    {{ new Date(type === 'orders' ? item.order_date : item.sale_date).toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute:'2-digit' }) }}
                                </TableCell>
                                <TableCell class="font-medium text-xs text-zinc-500 font-mono">
                                    {{ type === 'orders' ? item.srid : item.sale_id }}
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-col">
                                        <span class="text-zinc-200">{{ item.nm_id }}</span>
                                        <span class="text-xs text-zinc-500">{{ item.barcode }}</span>
                                    </div>
                                </TableCell>
                                
                                <template v-if="type === 'orders'">
                                    <TableCell class="text-right font-medium text-white">₽{{ item.finished_price }}</TableCell>
                                    <TableCell class="pr-6 text-center">
                                        <span v-if="item.is_cancel" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20 shadow-[0_0_10px_rgba(244,63,94,0.1)]">Отменен</span>
                                        <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20 shadow-[0_0_10px_rgba(59,130,246,0.1)]">Заказан</span>
                                    </TableCell>
                                </template>
                                
                                <template v-if="type === 'sales'">
                                    <TableCell class="text-right text-zinc-400">₽{{ item.price_with_disc }}</TableCell>
                                    <TableCell class="text-right font-medium text-emerald-400 bg-emerald-950/10">₽{{ item.for_pay }}</TableCell>
                                    <TableCell class="pr-6 text-right text-zinc-500">{{ item.region_name || '—' }}</TableCell>
                                </template>
                            </TableRow>
                            <TableRow v-if="data.data.length === 0" class="hover:bg-transparent">
                                <TableCell colspan="6" class="h-32 text-center text-zinc-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <component :is="type === 'orders' ? ShoppingCart : TrendingUp" class="w-8 h-8 text-zinc-700 mb-2" />
                                        <span>{{ type === 'orders' ? 'Заказы не найдены' : 'Продажи не найдены' }}</span>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                
                <div class="flex items-center justify-between p-4 border-t border-zinc-800/50 bg-zinc-900/30" v-if="data.links && data.links.length > 3">
                    <div class="text-sm text-zinc-400">
                        Показано с <span class="font-medium text-white">{{ data.from }}</span> по <span class="font-medium text-white">{{ data.to }}</span> из <span class="font-medium text-white">{{ data.total }}</span>
                    </div>
                    <div class="flex space-x-1 overflow-x-auto pb-1 max-w-[50%]">
                        <template v-for="(link, key) in data.links" :key="key">
                            <Link 
                                v-if="link.url"
                                :href="link.url" 
                                class="px-3 py-1.5 text-sm font-medium border rounded-md transition-colors whitespace-nowrap"
                                :class="link.active 
                                    ? 'bg-blue-600 text-white border-blue-600' 
                                    : 'bg-zinc-900 border-zinc-700 text-zinc-300 hover:bg-zinc-800'"
                                v-html="link.label.replace('Previous', '&laquo;').replace('Next', '&raquo;')"
                            />
                            <span v-else class="px-3 py-1.5 text-sm font-medium border border-zinc-800 rounded-md text-zinc-600 whitespace-nowrap" v-html="link.label.replace('Previous', '&laquo;').replace('Next', '&raquo;')"></span>
                        </template>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AuthenticatedLayout>
</template>
