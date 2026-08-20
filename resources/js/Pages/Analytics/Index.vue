<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { 
    BarChart3, 
    TrendingUp, 
    ShoppingCart, 
    CreditCard, 
    Percent, 
    Trophy,
    Eye,
    MapPin,
    AlertTriangle,
    ArrowUpRight,
    ArrowDownRight,
    Minus
} from 'lucide-vue-next';

// Chart.js imports
import { Line, Pie } from 'vue-chartjs';
import { 
    Chart as ChartJS, 
    CategoryScale, 
    LinearScale, 
    PointElement, 
    LineElement, 
    Title, 
    Tooltip, 
    Legend, 
    Filler,
    ArcElement
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler, ArcElement);

const props = defineProps({
    kpis: Object,
    trend: Array,
    topProducts: Array,
    warehouses: Array,
    antiTop: Array,
    days: Number
});

// Period Selector
const handlePeriodChange = (event) => {
    router.get(route('analytics.index'), { days: event.target.value }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Formatters
const formatMoney = (value) => {
    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(value || 0);
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('ru-RU').format(value || 0);
};

const formatDate = (dateString) => {
    const d = new Date(dateString);
    return d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit' });
};

// Chart Data Setup
const trendChartData = computed(() => {
    const labels = props.trend.map(d => formatDate(d.date));
    return {
        labels,
        datasets: [
            {
                label: 'Заказы (шт)',
                data: props.trend.map(d => d.orders_count),
                borderColor: '#3b82f6', // blue-500
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#3b82f6',
                tension: 0.4,
                fill: true,
            },
            {
                label: 'Выкупы (шт)',
                data: props.trend.map(d => d.buyouts_count),
                borderColor: '#10b981', // emerald-500
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#10b981',
                tension: 0.4,
                fill: true,
            }
        ]
    };
});

const pieChartData = computed(() => {
    return {
        labels: props.warehouses.map(w => w.warehouse_name),
        datasets: [
            {
                data: props.warehouses.map(w => w.count),
                backgroundColor: [
                    '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444'
                ],
                borderWidth: 0,
            }
        ]
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: { display: true, labels: { color: '#a1a1aa' } },
        tooltip: {
            backgroundColor: 'rgba(24, 24, 27, 0.9)', titleColor: '#fff', bodyColor: '#e4e4e7',
            borderColor: 'rgba(63, 63, 70, 0.5)', borderWidth: 1, padding: 10,
        }
    },
    scales: {
        x: { grid: { color: 'rgba(63, 63, 70, 0.2)' }, ticks: { color: '#71717a' } },
        y: { grid: { color: 'rgba(63, 63, 70, 0.2)' }, ticks: { color: '#71717a' } }
    }
};

const pieOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'right', labels: { color: '#a1a1aa', padding: 20 } }
    }
};

const funnelMax = Math.max(props.kpis.views, props.kpis.carts, props.kpis.funnel_orders, props.kpis.funnel_buyouts) || 1;
const getFunnelWidth = (val) => `${(val / funnelMax) * 100}%`;
</script>

<template>
    <Head title="Аналитика" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-2xl tracking-tight text-white animate-fade-in">Аналитика</h2>
                <div class="flex items-center space-x-3">
                    <span class="text-sm text-zinc-400">Период:</span>
                    <select 
                        class="bg-zinc-900 border border-zinc-700 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2"
                        @change="handlePeriodChange"
                        :value="days"
                    >
                        <option :value="7">За 7 дней</option>
                        <option :value="14">За 14 дней</option>
                        <option :value="30">За 30 дней</option>
                        <option :value="90">За 90 дней</option>
                    </select>
                </div>
            </div>
        </template>

        <div class="grid gap-6 mt-6 animate-slide-up pb-10">
            
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Orders -->
                <Card class="glass-panel text-white overflow-hidden relative group">
                    <div class="absolute inset-0 bg-blue-500/5 group-hover:bg-blue-500/10 transition-colors"></div>
                    <CardContent class="p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-zinc-400 mb-1">Заказы</p>
                                <h3 class="text-2xl font-bold text-white">{{ formatMoney(kpis.orders_sum) }}</h3>
                                <div class="flex items-center mt-2 space-x-3">
                                    <p class="text-sm text-blue-400 flex items-center">
                                        {{ formatNumber(kpis.orders_count) }} шт
                                    </p>
                                    <div class="flex items-center text-xs" :class="kpis.lfl_orders_sum > 0 ? 'text-emerald-400' : (kpis.lfl_orders_sum < 0 ? 'text-red-400' : 'text-zinc-500')">
                                        <ArrowUpRight v-if="kpis.lfl_orders_sum > 0" class="w-3 h-3 mr-1" />
                                        <ArrowDownRight v-else-if="kpis.lfl_orders_sum < 0" class="w-3 h-3 mr-1" />
                                        <Minus v-else class="w-3 h-3 mr-1" />
                                        {{ Math.abs(kpis.lfl_orders_sum) }}%
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 bg-blue-500/20 rounded-xl text-blue-400">
                                <ShoppingCart class="w-6 h-6" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Buyouts -->
                <Card class="glass-panel text-white overflow-hidden relative group">
                    <div class="absolute inset-0 bg-emerald-500/5 group-hover:bg-emerald-500/10 transition-colors"></div>
                    <CardContent class="p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-zinc-400 mb-1">Выкупы (Продажи)</p>
                                <h3 class="text-2xl font-bold text-white">{{ formatMoney(kpis.buyouts_sum) }}</h3>
                                <div class="flex items-center mt-2 space-x-3">
                                    <p class="text-sm text-emerald-400 flex items-center">
                                        {{ formatNumber(kpis.buyouts_count) }} шт
                                    </p>
                                    <div class="flex items-center text-xs" :class="kpis.lfl_buyouts_sum > 0 ? 'text-emerald-400' : (kpis.lfl_buyouts_sum < 0 ? 'text-red-400' : 'text-zinc-500')">
                                        <ArrowUpRight v-if="kpis.lfl_buyouts_sum > 0" class="w-3 h-3 mr-1" />
                                        <ArrowDownRight v-else-if="kpis.lfl_buyouts_sum < 0" class="w-3 h-3 mr-1" />
                                        <Minus v-else class="w-3 h-3 mr-1" />
                                        {{ Math.abs(kpis.lfl_buyouts_sum) }}%
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 bg-emerald-500/20 rounded-xl text-emerald-400">
                                <CreditCard class="w-6 h-6" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Buyout Rate -->
                <Card class="glass-panel text-white overflow-hidden relative group">
                    <div class="absolute inset-0 bg-purple-500/5 group-hover:bg-purple-500/10 transition-colors"></div>
                    <CardContent class="p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-zinc-400 mb-1">Процент выкупа</p>
                                <h3 class="text-2xl font-bold text-white">{{ kpis.buyout_rate }}%</h3>
                                <div class="flex items-center mt-2 space-x-3">
                                    <p class="text-sm text-zinc-500">К заказам</p>
                                    <div class="flex items-center text-xs" :class="kpis.lfl_buyout_rate > 0 ? 'text-emerald-400' : (kpis.lfl_buyout_rate < 0 ? 'text-red-400' : 'text-zinc-500')">
                                        <ArrowUpRight v-if="kpis.lfl_buyout_rate > 0" class="w-3 h-3 mr-1" />
                                        <ArrowDownRight v-else-if="kpis.lfl_buyout_rate < 0" class="w-3 h-3 mr-1" />
                                        <Minus v-else class="w-3 h-3 mr-1" />
                                        {{ Math.abs(kpis.lfl_buyout_rate) }} п.п.
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 bg-purple-500/20 rounded-xl text-purple-400">
                                <Percent class="w-6 h-6" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Conversions -->
                <Card class="glass-panel text-white overflow-hidden relative group">
                    <div class="absolute inset-0 bg-amber-500/5 group-hover:bg-amber-500/10 transition-colors"></div>
                    <CardContent class="p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-zinc-400 mb-1">В корзину</p>
                                <h3 class="text-2xl font-bold text-white">{{ kpis.conversion_cart_rate }}%</h3>
                                <p class="text-sm text-amber-400 mt-2 flex items-center">
                                    В заказ: {{ kpis.conversion_order_rate }}%
                                </p>
                            </div>
                            <div class="p-3 bg-amber-500/20 rounded-xl text-amber-400">
                                <TrendingUp class="w-6 h-6" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Charts Row 1 -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Trend Chart -->
                <Card class="xl:col-span-2 glass-panel border-zinc-800/50">
                    <CardHeader class="border-b border-zinc-800/50">
                        <CardTitle class="text-lg flex items-center text-zinc-200">
                            <BarChart3 class="w-5 h-5 mr-2 text-blue-400" />
                            Динамика заказов и выкупов
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="pt-6 h-[350px]">
                        <Line :data="trendChartData" :options="chartOptions" v-if="trend.length > 0" />
                        <div v-else class="h-full flex items-center justify-center text-zinc-500">
                            Нет данных за выбранный период
                        </div>
                    </CardContent>
                </Card>

                <!-- Warehouse Pie Chart -->
                <Card class="glass-panel border-zinc-800/50">
                    <CardHeader class="border-b border-zinc-800/50">
                        <CardTitle class="text-lg flex items-center text-zinc-200">
                            <MapPin class="w-5 h-5 mr-2 text-emerald-400" />
                            Топ-5 складов отгрузки
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="pt-6 h-[350px]">
                        <Pie :data="pieChartData" :options="pieOptions" v-if="warehouses.length > 0" />
                        <div v-else class="h-full flex items-center justify-center text-zinc-500">
                            Нет данных о складах
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Charts Row 2 -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Top Products Table -->
                <Card class="xl:col-span-2 glass-panel border-zinc-800/50">
                    <CardHeader class="border-b border-zinc-800/50">
                        <CardTitle class="text-lg flex items-center text-zinc-200">
                            <Trophy class="w-5 h-5 mr-2 text-yellow-500" />
                            Лидеры продаж (Топ-10)
                        </CardTitle>
                    </CardHeader>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-zinc-400 bg-zinc-900/50 uppercase border-b border-zinc-800/50">
                                <tr>
                                    <th class="px-6 py-4 font-medium">Товар</th>
                                    <th class="px-6 py-4 font-medium text-center">Выручка</th>
                                    <th class="px-6 py-4 font-medium text-center">Остаток</th>
                                    <th class="min-w-[110px] whitespace-nowrap px-6 py-4 text-center font-medium">Хватит на</th>
                                    <th class="px-6 py-4 font-medium">План / Факт (Текущий месяц)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-800/30">
                                <tr v-for="(product, index) in topProducts" :key="product.id" class="hover:bg-zinc-800/30 transition-colors">
                                    <td class="px-6 py-4 flex items-center space-x-4">
                                        <div class="w-6 text-center text-zinc-500 font-medium">{{ index + 1 }}</div>
                                        <div class="w-10 h-14 rounded-md overflow-hidden bg-zinc-800 shrink-0">
                                            <img :src="product.main_image_url" :alt="product.title" class="w-full h-full object-cover" />
                                        </div>
                                        <div>
                                            <div class="font-medium text-zinc-200 line-clamp-1" :title="product.title">{{ product.title }}</div>
                                            <div class="text-xs text-zinc-500 mt-1">{{ product.vendor_code }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center font-medium text-emerald-400">
                                        {{ formatMoney(product.revenue_30d) }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-zinc-300">
                                        {{ formatNumber(product.total_stock) }} шт
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-center">
                                        <span v-if="product.days_of_supply <= 7" class="inline-flex whitespace-nowrap px-2.5 py-1 rounded-full text-xs font-semibold bg-red-500/20 text-red-400">
                                            {{ product.days_of_supply }} дн.
                                        </span>
                                        <span v-else-if="product.days_of_supply <= 30" class="inline-flex whitespace-nowrap px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-400">
                                            {{ product.days_of_supply }} дн.
                                        </span>
                                        <span v-else-if="product.days_of_supply < 999" class="inline-flex whitespace-nowrap px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-400">
                                            {{ product.days_of_supply }} дн.
                                        </span>
                                        <span v-else class="inline-flex whitespace-nowrap text-zinc-500 text-xs">Много</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="product.plan_orders > 0 || product.plan_sales > 0" class="space-y-3 min-w-[200px]">
                                            <div>
                                                <div class="flex justify-between text-[10px] mb-1">
                                                    <span class="text-zinc-400">Заказы ({{ product.orders_count }} / {{ product.plan_orders }})</span>
                                                    <span class="text-blue-400 font-medium">{{ product.fact_orders_percent }}%</span>
                                                </div>
                                                <div class="h-1.5 w-full bg-zinc-800 rounded-full overflow-hidden">
                                                    <div class="h-full bg-blue-500 rounded-full" :style="{ width: `${product.fact_orders_percent}%` }"></div>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="flex justify-between text-[10px] mb-1">
                                                    <span class="text-zinc-400">Выкупы ({{ product.sales_count }} / {{ product.plan_sales }})</span>
                                                    <span class="text-emerald-400 font-medium">{{ product.fact_sales_percent }}%</span>
                                                </div>
                                                <div class="h-1.5 w-full bg-zinc-800 rounded-full overflow-hidden">
                                                    <div class="h-full bg-emerald-500 rounded-full" :style="{ width: `${product.fact_sales_percent}%` }"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="text-xs text-zinc-600 italic">
                                            План не установлен
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="topProducts.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-zinc-500">Нет данных</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>

                <div class="space-y-6">
                    <!-- Funnel Visualization -->
                    <Card class="glass-panel border-zinc-800/50">
                        <CardHeader class="border-b border-zinc-800/50 pb-4">
                            <CardTitle class="text-lg flex items-center text-zinc-200">
                                <Eye class="w-5 h-5 mr-2 text-purple-400" />
                                Воронка
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="pt-6 space-y-5">
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-zinc-300">Просмотры</span>
                                    <span class="text-white">{{ formatNumber(kpis.views) }}</span>
                                </div>
                                <div class="h-2 bg-zinc-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-zinc-600 transition-all duration-1000" :style="{ width: getFunnelWidth(kpis.views) }"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-zinc-300">В корзину</span>
                                    <span class="text-white">{{ formatNumber(kpis.carts) }}</span>
                                </div>
                                <div class="h-2 bg-zinc-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 transition-all duration-1000 shadow-[0_0_10px_rgba(59,130,246,0.5)]" :style="{ width: getFunnelWidth(kpis.carts) }"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-zinc-300">Заказы</span>
                                    <span class="text-white">{{ formatNumber(kpis.funnel_orders) }}</span>
                                </div>
                                <div class="h-2 bg-zinc-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-purple-500 transition-all duration-1000 shadow-[0_0_10px_rgba(168,85,247,0.5)]" :style="{ width: getFunnelWidth(kpis.funnel_orders) }"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-zinc-300">Выкупы</span>
                                    <span class="text-white">{{ formatNumber(kpis.funnel_buyouts) }}</span>
                                </div>
                                <div class="h-2 bg-zinc-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 transition-all duration-1000 shadow-[0_0_10px_rgba(16,185,129,0.5)]" :style="{ width: getFunnelWidth(kpis.funnel_buyouts) }"></div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Anti-Top Cancellations -->
                    <Card class="glass-panel border-zinc-800/50">
                        <CardHeader class="border-b border-zinc-800/50 pb-4">
                            <CardTitle class="text-lg flex items-center text-zinc-200">
                                <AlertTriangle class="w-5 h-5 mr-2 text-red-500" />
                                Топ по отменам
                            </CardTitle>
                        </CardHeader>
                        <div class="pt-2 px-4 pb-4">
                            <div v-for="item in antiTop" :key="item.id" class="flex items-center justify-between py-3 border-b border-zinc-800/50 last:border-0">
                                <div class="flex items-center space-x-3 w-[70%]">
                                    <img :src="item.main_image_url" class="w-8 h-10 rounded object-cover bg-zinc-800" />
                                    <div class="text-xs text-zinc-300 line-clamp-2" :title="item.title">{{ item.title }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-red-400 font-medium text-sm">{{ item.cancel_rate }}%</div>
                                    <div class="text-zinc-600 text-[10px]">{{ item.cancels_count }} из {{ item.total_orders }}</div>
                                </div>
                            </div>
                            <div v-if="antiTop.length === 0" class="py-6 text-center text-sm text-zinc-500">
                                Нет данных об отменах
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
