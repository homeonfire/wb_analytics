<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { 
    Search, 
    PackageOpen, 
    Download, 
    Upload, 
    ExternalLink, 
    List,
    ChevronDown,
    ChevronUp
} from 'lucide-vue-next';

const props = defineProps({
    products: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const expandedRows = ref([]);

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
    router.get(route('logistics.index'), { search: value }, {
        preserveState: true,
        replace: true
    });
}, 300));

const toggleRow = (id) => {
    if (expandedRows.value.includes(id)) {
        expandedRows.value = expandedRows.value.filter(rowId => rowId !== id);
    } else {
        expandedRows.value.push(id);
    }
};

// Aggregation functions for new flat table
const getStocksAggregated = (product) => {
    let at_factory = 0;
    let in_transit_general = 0;
    let stock_own = 0;
    let in_transit_to_wb = 0;

    if (product.skus && product.skus.length > 0) {
        product.skus.forEach(sku => {
            if (sku.stock) {
                at_factory += (sku.stock.at_factory || 0);
                in_transit_general += (sku.stock.in_transit_general || 0);
                stock_own += (sku.stock.stock_own || 0);
                in_transit_to_wb += (sku.stock.in_transit_to_wb || 0);
            }
        });
    }

    return {
        at_factory,
        in_transit_general,
        stock_own,
        in_transit_to_wb
    };
};

const getWbStocksAggregated = (product) => {
    let quantity = 0;
    let in_way_to_client = 0;

    if (product.warehouse_stocks && product.warehouse_stocks.length > 0) {
        product.warehouse_stocks.forEach(stock => {
            quantity += (stock.quantity || 0);
            in_way_to_client += (stock.in_way_to_client || 0);
        });
    }

    return {
        quantity,
        in_way_to_client
    };
};

const getMergedSizes = (product) => {
    const skus = product.skus || [];
    
    const wbSizesMap = {};
    if (product.warehouse_stocks) {
        product.warehouse_stocks.forEach(ws => {
            if (!wbSizesMap[ws.chrt_id]) {
                wbSizesMap[ws.chrt_id] = {
                    quantity: 0,
                    in_way_to_client: 0,
                    chrt_id: ws.chrt_id
                };
            }
            wbSizesMap[ws.chrt_id].quantity += (ws.quantity || 0);
            wbSizesMap[ws.chrt_id].in_way_to_client += (ws.in_way_to_client || 0);
        });
    }
    
    // Sort wbSizes by chrt_id just to be deterministic
    const wbSizes = Object.values(wbSizesMap).sort((a, b) => a.chrt_id - b.chrt_id);
    
    const merged = [];
    const maxLen = Math.max(skus.length, wbSizes.length);
    for (let i = 0; i < maxLen; i++) {
        const sku = skus[i] || {};
        const wb = wbSizes[i] || { quantity: 0, in_way_to_client: 0 };
        merged.push({
            id: sku.id || `wb-${wb.chrt_id}`,
            tech_size: sku.tech_size || (wb.chrt_id ? `Size ID: ${wb.chrt_id}` : 'Без размера'),
            barcode: sku.barcode || '-',
            stock: sku.stock || null,
            quantity: wb.quantity,
            in_way_to_client: wb.in_way_to_client
        });
    }
    return merged;
};

const getAvgSales = (product) => {
    // Временно возвращаем 0.5 для демонстрации оборачиваемости (если нет реальных данных)
    return 0.5;
};

const getTurnover = (product, wbStocks, internalStocks) => {
    const totalStock = wbStocks.quantity + internalStocks.stock_own + internalStocks.in_transit_to_wb;
    const avg = getAvgSales(product);
    if (avg <= 0) return '∞';
    return Math.round(totalStock / avg);
};
</script>

<template>
    <Head title="Логистика и Склады" />

    <AuthenticatedLayout :fullWidth="true">
        <template #header>
            <div class="flex flex-col xl:flex-row xl:items-center justify-between w-full gap-4">
                <h2 class="font-semibold text-2xl tracking-tight text-white animate-fade-in shrink-0">Логистика (Сводная)</h2>
                
                <div class="flex flex-wrap items-center gap-2">
                    <button class="flex items-center px-3 py-1.5 text-xs font-medium bg-emerald-600/90 hover:bg-emerald-500 text-white rounded-md transition-colors border border-emerald-500/50 shadow-[0_0_15px_rgba(16,185,129,0.2)]">
                        <Download class="w-3.5 h-3.5 mr-1.5" />
                        Экспорт в Excel
                    </button>
                    <button class="flex items-center px-3 py-1.5 text-xs font-medium bg-amber-600/90 hover:bg-amber-500 text-white rounded-md transition-colors border border-amber-500/50 shadow-[0_0_15px_rgba(217,119,6,0.2)]">
                        <Upload class="w-3.5 h-3.5 mr-1.5" />
                        Импорт "Фабрика"
                    </button>
                    <button class="flex items-center px-3 py-1.5 text-xs font-medium bg-blue-600/90 hover:bg-blue-500 text-white rounded-md transition-colors border border-blue-500/50 shadow-[0_0_15px_rgba(37,99,235,0.2)]">
                        <Upload class="w-3.5 h-3.5 mr-1.5" />
                        Импорт "В пути с фабрики"
                    </button>
                    <button class="flex items-center px-3 py-1.5 text-xs font-medium bg-emerald-600/90 hover:bg-emerald-500 text-white rounded-md transition-colors border border-emerald-500/50 shadow-[0_0_15px_rgba(16,185,129,0.2)]">
                        <Upload class="w-3.5 h-3.5 mr-1.5" />
                        Импорт "Склад"
                    </button>
                    <button class="flex items-center px-3 py-1.5 text-xs font-medium bg-amber-600/90 hover:bg-amber-500 text-white rounded-md transition-colors border border-amber-500/50 shadow-[0_0_15px_rgba(217,119,6,0.2)]">
                        <Upload class="w-3.5 h-3.5 mr-1.5" />
                        Импорт "Путь WB"
                    </button>
                </div>

                <div class="relative w-full xl:w-64 shrink-0">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-500" />
                    <Input 
                        v-model="search" 
                        placeholder="Поиск..." 
                        class="pl-9 bg-zinc-900/80 border-zinc-700/50 text-zinc-100 focus-visible:ring-blue-500 w-full shadow-sm h-9" 
                    />
                </div>
            </div>
        </template>

        <Card class="glass-panel text-zinc-100 animate-slide-up border-zinc-800">
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left whitespace-nowrap">
                        <thead class="text-xs text-zinc-400 bg-zinc-900/50 border-b border-zinc-800 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4 font-medium min-w-[300px]">Товар</th>
                                <th class="px-4 py-4 font-medium text-center">Фабрика</th>
                                <th class="px-4 py-4 font-medium text-center text-blue-400">В пути с фабрики</th>
                                <th class="px-4 py-4 font-medium text-center text-emerald-400">Склад</th>
                                <th class="px-4 py-4 font-medium text-center text-amber-500">В пути WB</th>
                                <th class="px-4 py-4 font-medium text-center text-zinc-100">На WB</th>
                                <th class="px-4 py-4 font-medium text-center text-blue-400">К клиенту</th>
                                <th class="px-4 py-4 font-medium text-center">Ср.прод</th>
                                <th class="px-4 py-4 font-medium text-center text-rose-400">Обор.</th>
                                <th class="px-6 py-4 font-medium text-right">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="products.data.length === 0">
                                <td colspan="10" class="px-6 py-12 text-center text-zinc-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <PackageOpen class="w-12 h-12 mb-4 opacity-30" />
                                        <h3 class="text-lg font-medium text-zinc-300">Товары не найдены</h3>
                                    </div>
                                </td>
                            </tr>
                            <template v-for="product in products.data" :key="product.id">
                                <tr class="border-b border-zinc-800/30 hover:bg-zinc-800/20 transition-colors group">
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-md bg-zinc-800 flex items-center justify-center shrink-0 overflow-hidden border border-zinc-700/50">
                                                <img v-if="product.main_image_url" :src="product.main_image_url" class="w-full h-full object-cover" />
                                                <PackageOpen v-else class="w-5 h-5 text-zinc-500" />
                                            </div>
                                            <div class="flex flex-col justify-center">
                                                <div class="text-sm font-medium text-zinc-200 line-clamp-1 max-w-[250px]" :title="product.title">
                                                    {{ product.title || 'Без названия' }}
                                                </div>
                                                <div class="text-[11px] text-zinc-500 mt-0.5">
                                                    {{ product.brand || 'No Brand' }} / {{ product.vendor_code }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- Фабрика -->
                                    <td class="px-4 py-3 text-center text-amber-600 font-semibold">
                                        {{ getStocksAggregated(product).at_factory }}
                                    </td>
                                    
                                    <!-- В пути с фабрики -->
                                    <td class="px-4 py-3 text-center text-blue-500 font-semibold">
                                        {{ getStocksAggregated(product).in_transit_general }}
                                    </td>
                                    
                                    <!-- Склад -->
                                    <td class="px-4 py-3 text-center text-emerald-500 font-semibold">
                                        {{ getStocksAggregated(product).stock_own }}
                                    </td>
                                    
                                    <!-- В пути WB -->
                                    <td class="px-4 py-3 text-center text-amber-500 font-semibold">
                                        {{ getStocksAggregated(product).in_transit_to_wb }}
                                    </td>
                                    
                                    <!-- На WB -->
                                    <td class="px-4 py-3 text-center text-zinc-200 font-semibold">
                                        {{ getWbStocksAggregated(product).quantity }}
                                    </td>
                                    
                                    <!-- К клиенту -->
                                    <td class="px-4 py-3 text-center text-blue-400 font-semibold">
                                        {{ getWbStocksAggregated(product).in_way_to_client }}
                                    </td>
                                    
                                    <!-- Ср.прод -->
                                    <td class="px-4 py-3 text-center text-zinc-400">
                                        {{ getAvgSales(product) }}
                                    </td>
                                    
                                    <!-- Обор. -->
                                    <td class="px-4 py-3 text-center text-rose-400 font-bold">
                                        {{ getTurnover(product, getWbStocksAggregated(product), getStocksAggregated(product)) }}
                                    </td>
                                    
                                    <!-- Actions -->
                                    <td class="px-6 py-3 text-right">
                                        <div class="flex items-center justify-end space-x-3 opacity-50 group-hover:opacity-100 transition-opacity">
                                            <Link :href="route('products.show', product.id)" class="text-xs text-blue-400 hover:text-blue-300 flex items-center transition-colors">
                                                <ExternalLink class="w-3 h-3 mr-1" />
                                                К товару
                                            </Link>
                                            <button @click="toggleRow(product.id)" class="text-xs text-amber-500 hover:text-amber-400 flex items-center transition-colors focus:outline-none">
                                                <List class="w-3 h-3 mr-1" />
                                                Размеры
                                                <ChevronUp v-if="expandedRows.includes(product.id)" class="w-3 h-3 ml-1" />
                                                <ChevronDown v-else class="w-3 h-3 ml-1" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Sub-row for SKUs (Sizes) -->
                                <template v-if="expandedRows.includes(product.id)">
                                    <tr v-for="sku in getMergedSizes(product)" :key="sku.id" class="bg-zinc-900/40 border-b border-zinc-800/20 last:border-zinc-800/50 hover:bg-zinc-800/30 transition-colors">
                                        <td class="px-6 py-2 pl-16 relative">
                                            <div class="absolute left-6 top-0 bottom-0 w-px bg-zinc-800"></div>
                                            <div class="absolute left-6 top-1/2 w-6 h-px bg-zinc-800"></div>
                                            <div class="flex flex-col">
                                                <span class="text-xs text-zinc-300 font-medium">Размер: {{ sku.tech_size }}</span>
                                                <span class="text-[10px] text-zinc-500">Баркод: {{ sku.barcode }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-center text-xs text-amber-600/80 font-medium">{{ sku.stock ? sku.stock.at_factory : 0 }}</td>
                                        <td class="px-4 py-2 text-center text-xs text-blue-500/80 font-medium">{{ sku.stock ? sku.stock.in_transit_general : 0 }}</td>
                                        <td class="px-4 py-2 text-center text-xs text-emerald-500/80 font-medium">{{ sku.stock ? sku.stock.stock_own : 0 }}</td>
                                        <td class="px-4 py-2 text-center text-xs text-amber-500/80 font-medium">{{ sku.stock ? sku.stock.in_transit_to_wb : 0 }}</td>
                                        <td class="px-4 py-2 text-center text-xs text-zinc-400 font-medium">{{ sku.quantity }}</td>
                                        <td class="px-4 py-2 text-center text-xs text-blue-400/80 font-medium">{{ sku.in_way_to_client }}</td>
                                        <td class="px-4 py-2 text-center text-xs text-zinc-600">-</td>
                                        <td class="px-4 py-2 text-center text-xs text-zinc-600">-</td>
                                        <td class="px-6 py-2"></td>
                                    </tr>
                                </template>
                            </template>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- Pagination -->
        <div class="mt-4 flex items-center justify-between p-4 border border-zinc-800/50 bg-zinc-900/30 rounded-xl" v-if="products.links && products.links.length > 3">
            <div class="text-sm text-zinc-400 hidden md:block">
                Показано с <span class="font-medium text-white">{{ products.from }}</span> по <span class="font-medium text-white">{{ products.to }}</span> из <span class="font-medium text-white">{{ products.total }}</span>
            </div>
            <div class="flex space-x-1 overflow-x-auto pb-1 w-full md:w-auto md:max-w-[60%]">
                <template v-for="(link, key) in products.links" :key="key">
                    <Link 
                        v-if="link.url"
                        :href="link.url" 
                        class="px-3 py-1.5 text-sm font-medium border rounded-md transition-colors whitespace-nowrap"
                        :class="link.active 
                            ? 'bg-blue-600 text-white border-blue-600 shadow-lg shadow-blue-900/20' 
                            : 'bg-zinc-900 border-zinc-700 text-zinc-300 hover:bg-zinc-800'"
                        v-html="link.label.replace('Previous', '&laquo;').replace('Next', '&raquo;')"
                    />
                    <span v-else class="px-3 py-1.5 text-sm font-medium border border-zinc-800 rounded-md text-zinc-600 whitespace-nowrap" v-html="link.label.replace('Previous', '&laquo;').replace('Next', '&raquo;')"></span>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
