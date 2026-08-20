<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ui/accordion';
import { computed, ref, defineComponent, onMounted, watch } from 'vue';
import { ArrowLeft, Package, TrendingUp, DollarSign, Megaphone, BarChart3, List, CalendarDays, ExternalLink, Calculator, Target, Save, ShieldCheck } from 'lucide-vue-next';
import { Input } from '@/Components/ui/input';
import VueApexCharts from "vue3-apexcharts";

const ClientOnly = defineComponent({
  setup(_, { slots }) {
    const isMounted = ref(false)
    onMounted(() => {
      isMounted.value = true
    })
    return () => (isMounted.value && slots.default ? slots.default() : null)
  },
})

const props = defineProps({
    product: Object,
    analytics: Array,
    ordersFact: Array,
    salesFact: Array,
    campaigns: Array,
    planFactPeriods: Array,
    canManagePlans: Boolean
});

const currentPlanPeriod = props.planFactPeriods?.[props.planFactPeriods.length - 1] || {};
const planForm = useForm({
    orders_plan: currentPlanPeriod.orders_plan || 0,
    sales_plan: currentPlanPeriod.sales_plan || 0,
});
const planPercent = (fact, plan) => Number(plan) > 0 ? Math.round((Number(fact) / Number(plan)) * 100) : 0;
const progressWidth = (fact, plan) => Math.min(100, planPercent(fact, plan)) + '%';
const savePlan = () => planForm.patch(route('products.plan.update', props.product.id), { preserveScroll: true });

const totalStocks = computed(() => {
    if (!props.product.warehouse_stocks) return 0;
    return props.product.warehouse_stocks.reduce((acc, stock) => acc + stock.quantity, 0);
});

// Funnel Time Range & Charts
const timeRange = ref(14); // 7, 14, 30

const filteredAnalytics = computed(() => {
    // Props analytics are sorted by date ASC from the controller now
    // We want the LAST N days
    const arr = [...(props.analytics || [])];
    return arr.slice(Math.max(arr.length - timeRange.value, 0));
});

const funnelChartOptions = computed(() => ({
    chart: {
        type: 'area',
        height: 350,
        toolbar: { show: false },
        background: 'transparent',
        fontFamily: 'Inter, sans-serif',
        animations: { enabled: true }
    },
    colors: ['#3b82f6', '#8b5cf6', '#10b981'],
    fill: {
        type: 'gradient',
        gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100] }
    },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    xaxis: {
        categories: filteredAnalytics.value.map(d => new Date(d.date).toLocaleDateString('ru-RU', {day: 'numeric', month: 'short'})),
        labels: { style: { colors: '#71717a' } },
        axisBorder: { show: false },
        axisTicks: { show: false }
    },
    yaxis: { labels: { style: { colors: '#71717a' } } },
    grid: { borderColor: '#27272a', strokeDashArray: 4 },
    theme: { mode: 'dark' },
    tooltip: { theme: 'dark' }
}));

const funnelChartSeries = computed(() => [
    { name: 'Просмотры', data: filteredAnalytics.value.map(d => d.open_card_count) },
    { name: 'Заказы', data: filteredAnalytics.value.map(d => d.orders_count) },
    { name: 'Выкупы', data: filteredAnalytics.value.map(d => d.buyouts_count) },
]);

// P&L Data
const plTimeRange = ref(14); // 7, 14, 30

const plData = computed(() => {
    const cutoff = new Date();
    cutoff.setDate(cutoff.getDate() - plTimeRange.value);
    
    const funnelMap = new Map((props.analytics || []).map(a => [a.date.split('T')[0], a]));
    const ordersMap = new Map((props.ordersFact || []).map(a => [a.date, a]));
    const salesMap = new Map((props.salesFact || []).map(a => [a.date, a]));
    
    const allDates = new Set([...funnelMap.keys(), ...ordersMap.keys(), ...salesMap.keys()]);
    let sortedDates = Array.from(allDates).sort();
    const recentDates = sortedDates.filter(d => new Date(d) >= cutoff);
    
    if(recentDates.length === 0) return { totals: {}, chartData: [] };
    
    let totals = { revenue: 0, ordersCount: 0, buyoutsCount: 0, funnelOrders: 0, funnelBuyouts: 0, orderSum: 0, forPay: 0 };
    let chartData = [];
    
    recentDates.forEach(date => {
        const funnel = funnelMap.get(date) || {};
        const order = ordersMap.get(date) || {};
        const sale = salesMap.get(date) || {};
        
        totals.revenue += Number(sale.revenue || 0);
        totals.forPay += Number(sale.for_pay_sum || 0);
        totals.ordersCount += Number(order.orders_count_fact || 0);
        totals.orderSum += Number(order.order_sum || 0);
        totals.buyoutsCount += Number(sale.buyouts_count_fact || 0);
        
        totals.funnelOrders += Number(funnel.orders_count || 0);
        totals.funnelBuyouts += Number(funnel.buyouts_count || 0);
        
        chartData.push({
            date: date,
            buyoutPercent: funnel.conversion_buyouts_percent || 0,
            ordersFact: order.orders_count_fact || 0,
            buyoutsFact: sale.buyouts_count_fact || 0,
            revenue: sale.revenue || 0,
            orderSum: order.order_sum || 0
        });
    });
    
    const cost = totals.buyoutsCount * (props.product.cost_price || 0);
    const grossProfit = totals.forPay - cost;
    totals.margin = totals.forPay > 0 ? ((grossProfit / totals.forPay) * 100).toFixed(1) : 0;
    totals.funnelBuyoutPercent = totals.funnelOrders > 0 ? ((totals.funnelBuyouts / totals.funnelOrders) * 100).toFixed(1) : 0;
    
    return { totals, chartData };
});

const plChartOptions = computed(() => ({
    chart: { type: 'line', height: 350, toolbar: { show: false }, background: 'transparent', fontFamily: 'Inter, sans-serif' },
    colors: ['#ec4899', '#8b5cf6', '#f59e0b', '#10b981', '#64748b'],
    stroke: { width: [2, 2, 0, 2, 2], curve: 'smooth' },
    plotOptions: { bar: { columnWidth: '50%', borderRadius: 2 } },
    xaxis: {
        categories: plData.value.chartData.map(d => new Date(d.date).toLocaleDateString('ru-RU', {day: 'numeric', month: 'short'})),
        labels: { style: { colors: '#71717a' } }, axisBorder: { show: false }, axisTicks: { show: false }
    },
    yaxis: [
        { title: { text: 'Рубли (₽)', style: { color: '#10b981' } }, labels: { style: { colors: '#71717a' } } },
        { opposite: true, title: { text: 'Штуки (шт)', style: { color: '#f59e0b' } }, labels: { style: { colors: '#71717a' } } }
    ],
    grid: { borderColor: '#27272a', strokeDashArray: 4 },
    theme: { mode: 'dark' },
    tooltip: { theme: 'dark', shared: true, intersect: false }
}));

const plChartSeries = computed(() => [
    { name: '% Выкупа', type: 'line', data: plData.value.chartData.map(d => d.buyoutPercent) },
    { name: 'Заказы факт', type: 'line', data: plData.value.chartData.map(d => d.ordersFact) },
    { name: 'Выкупы факт', type: 'bar', data: plData.value.chartData.map(d => d.buyoutsFact) },
    { name: 'Выручка (₽)', type: 'area', data: plData.value.chartData.map(d => d.revenue) },
    { name: 'Сумма заказов (₽)', type: 'line', data: plData.value.chartData.map(d => d.orderSum) }
]);

const tableData = computed(() => {
    const data = filteredAnalytics.value;
    const headers = data.map(d => new Date(d.date).toLocaleDateString('ru-RU', {day: '2-digit', month: '2-digit'}));
    
    const formatDiff = (curr, prev) => {
        if (prev === null) return null;
        const diff = (Number(curr) - Number(prev)).toFixed(2);
        const parsed = Number(diff);
        if (parsed > 0) return { val: `+${parsed}`, color: 'text-emerald-500' };
        if (parsed < 0) return { val: `${parsed}`, color: 'text-rose-500' };
        return null;
    };
    
    const buildRow = (label, key, isPercent = false) => {
        const row = { label, values: [], total: 0 };
        let prev = null;
        for (let i = 0; i < data.length; i++) {
            const val = Number(data[i][key]);
            row.total += val;
            
            const currentFormatted = isPercent ? `${val}%` : val;
            
            row.values.push({
                current: currentFormatted,
                diff: formatDiff(val, prev)
            });
            prev = val;
        }
        
        if (isPercent) {
            row.total = data.length > 0 ? (row.total / data.length) : 0;
            row.total = `${row.total.toFixed(2)}%`;
        } else {
            row.total = row.total.toLocaleString('ru-RU');
        }
        
        return row;
    };

    return {
        headers,
        rows: [
            buildRow('Переходы (Просмотры)', 'open_card_count'),
            buildRow('В корзину', 'add_to_cart_count'),
            buildRow('Заказы, шт', 'orders_count'),
            buildRow('Выкупы, шт', 'buyouts_count'),
            buildRow('Конверсия в корзину, %', 'conversion_open_to_cart_percent', true),
            buildRow('Конверсия в заказ, %', 'conversion_cart_to_order_percent', true),
            buildRow('Процент выкупа, %', 'conversion_buyouts_percent', true),
        ]
    };
});

// Unit Economics Calculator
const calcPrice = ref(props.product.skus?.[0]?.price || 0);
const calcCost = ref(props.product.cost_price || 0);
const calcBuyoutPercent = ref(Number(plData.value.totals.funnelBuyoutPercent) || 30);
const calcTax = ref(7);
const calcCommission = ref(25);
const calcLogisticsTo = ref(65);
const calcLogisticsReturn = ref(33);

// Watch for plData changes to update default buyout percent if it was 0 initially
watch(() => plData.value.totals.funnelBuyoutPercent, (newVal) => {
    if (newVal > 0 && calcBuyoutPercent.value === 30) {
        calcBuyoutPercent.value = Number(newVal);
    }
});

const calcResults = computed(() => {
    const price = Number(calcPrice.value) || 0;
    const cost = Number(calcCost.value) || 0;
    const buyout = Number(calcBuyoutPercent.value) || 1; // avoid division by zero
    const tax = price * ((Number(calcTax.value) || 0) / 100);
    const comm = price * ((Number(calcCommission.value) || 0) / 100);
    const logTo = Number(calcLogisticsTo.value) || 0;
    const logRet = Number(calcLogisticsReturn.value) || 0;
    
    const shipmentsPerBuyout = 100 / buyout;
    const returnsPerBuyout = Math.max(0, shipmentsPerBuyout - 1);
    const avgLogistics = (shipmentsPerBuyout * logTo) + (returnsPerBuyout * logRet);
    
    const totalCosts = cost + tax + comm + avgLogistics;
    const netProfit = price - totalCosts;
    const margin = price > 0 ? (netProfit / price) * 100 : 0;
    const roi = cost > 0 ? (netProfit / cost) * 100 : 0;
    
    return {
        netProfit: netProfit.toFixed(2),
        margin: margin.toFixed(1),
        roi: roi.toFixed(1)
    };
});

</script>

<template>
    <Head :title="product.title" />

    <AuthenticatedLayout :fullWidth="true">
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="route('products.index')">
                    <Button variant="ghost" size="icon" class="text-zinc-400 hover:text-white bg-zinc-900 border-zinc-800 transition-colors">
                        <ArrowLeft class="w-5 h-5" />
                    </Button>
                </Link>
                <div>
                    <h2 class="font-semibold text-2xl leading-tight truncate text-white animate-fade-in">{{ product.title }}</h2>
                    <p class="text-sm text-zinc-400 mt-0.5">Артикул WB: <span class="text-blue-400 font-medium">{{ product.nm_id }}</span></p>
                </div>
            </div>
        </template>

        <!-- Top Overview Section -->
        <div class="grid gap-6 grid-cols-1 lg:grid-cols-12 mt-6 animate-slide-up items-start">
            
            <!-- Left Column: Product Image & Info (3/12) -->
            <div class="lg:col-span-3">
                <Card class="glass-panel text-zinc-100 flex flex-col">
                    <CardContent class="p-4 flex-1 flex flex-col">
                        <div class="relative rounded-xl overflow-hidden shadow-lg border border-zinc-700/50 mb-4 bg-zinc-900 w-full aspect-square max-h-[300px] mx-auto">
                            <img v-if="product.main_image_url" :src="product.main_image_url" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 hover:scale-105" />
                            <div v-else class="absolute inset-0 flex items-center justify-center text-zinc-500">Нет фото</div>
                        </div>
                        
                        <div class="space-y-2 mt-auto">
                            <div class="flex justify-between items-center bg-zinc-900/30 p-2.5 rounded-lg border border-zinc-800/50">
                                <span class="text-zinc-500 text-[10px] uppercase tracking-wider font-semibold">Поставщик</span>
                                <span class="font-bold text-white text-xs truncate max-w-[120px]">{{ product.vendor_code }}</span>
                            </div>
                            <div class="flex justify-between items-center bg-zinc-900/30 p-2.5 rounded-lg border border-zinc-800/50">
                                <span class="text-zinc-500 text-[10px] uppercase tracking-wider font-semibold">Бренд</span>
                                <span class="font-bold text-white text-xs truncate max-w-[120px]">{{ product.brand || '—' }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Right Column: Metrics & Lists (9/12) -->
            <div class="lg:col-span-9 flex flex-col space-y-6">
                
                <!-- Unit Economics Mini Widgets -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card class="glass-panel p-4 relative overflow-hidden group">
                        <div class="text-zinc-400 text-xs font-medium mb-1">Себестоимость</div>
                        <div class="text-xl font-bold text-white">₽{{ product.cost_price || 0 }}</div>
                        <div class="absolute -right-3 -bottom-3 opacity-5"><DollarSign class="w-12 h-12 text-white" /></div>
                    </Card>
                    <Card class="glass-panel p-4 relative overflow-hidden group">
                        <div class="text-zinc-400 text-xs font-medium mb-1">Цена (WB)</div>
                        <div class="text-xl font-bold text-white">₽{{ product.skus?.[0]?.price || 0 }}</div>
                        <div class="absolute -right-3 -bottom-3 opacity-5"><TrendingUp class="w-12 h-12 text-white" /></div>
                    </Card>
                    <Card class="glass-panel p-4 relative overflow-hidden group">
                        <div class="text-zinc-400 text-xs font-medium mb-1">Скидка</div>
                        <div class="text-xl font-bold text-rose-400">{{ product.skus?.[0]?.discount || 0 }}%</div>
                        <div class="absolute -right-3 -bottom-3 opacity-5"><TrendingUp class="w-12 h-12 text-rose-500" /></div>
                    </Card>
                    <Card class="glass-panel p-4 relative overflow-hidden group border-emerald-500/20 shadow-[0_0_15px_rgba(16,185,129,0.05)]">
                        <div class="text-zinc-400 text-xs font-medium mb-1">Расчетная маржа</div>
                        <div class="text-xl font-bold text-emerald-400">₽{{ product.margin_30d || 0 }}</div>
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-100 pointer-events-none"></div>
                        <div class="absolute -right-3 -bottom-3 opacity-[0.03]"><DollarSign class="w-12 h-12 text-emerald-500" /></div>
                    </Card>

                </div>

                <Card class="glass-panel overflow-hidden border-violet-500/20 text-zinc-100">
                    <CardHeader class="border-b border-zinc-800/60 bg-gradient-to-r from-violet-500/10 to-transparent">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div><CardTitle class="flex items-center gap-2"><Target class="h-5 w-5 text-violet-400" />План-факт товара</CardTitle><p class="mt-1 text-sm text-zinc-500">Выполнение целей за текущий и предыдущий месяц</p></div>
                            <span class="inline-flex w-fit items-center gap-1.5 rounded-full border px-3 py-1 text-xs" :class="canManagePlans ? 'border-violet-500/30 bg-violet-500/10 text-violet-300' : 'border-zinc-700 bg-zinc-900 text-zinc-400'"><ShieldCheck class="h-3.5 w-3.5" />{{ canManagePlans ? 'Можно редактировать' : 'Только просмотр' }}</span>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-5 p-5">
                        <form v-if="canManagePlans" class="grid gap-3 rounded-xl border border-violet-500/20 bg-violet-500/5 p-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end" @submit.prevent="savePlan">
                            <div class="space-y-1.5"><label class="text-xs font-medium text-zinc-400">План заказов на текущий месяц</label><Input v-model="planForm.orders_plan" type="number" min="0" class="border-zinc-700 bg-zinc-950 text-white focus-visible:ring-violet-500" /></div>
                            <div class="space-y-1.5"><label class="text-xs font-medium text-zinc-400">План выкупов на текущий месяц</label><Input v-model="planForm.sales_plan" type="number" min="0" class="border-zinc-700 bg-zinc-950 text-white focus-visible:ring-violet-500" /></div>
                            <Button type="submit" :disabled="planForm.processing" class="bg-violet-600 text-white hover:bg-violet-500"><Save class="mr-2 h-4 w-4" />Сохранить</Button>
                        </form>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div v-for="(period, index) in planFactPeriods" :key="period.year + '-' + period.month" class="rounded-xl border p-4" :class="index === planFactPeriods.length - 1 ? 'border-violet-500/25 bg-violet-500/5' : 'border-zinc-800 bg-zinc-900/40'">
                                <div class="mb-4 flex items-center justify-between"><div><div class="text-sm font-semibold capitalize text-zinc-200">{{ period.label }}</div><div class="mt-0.5 text-xs text-zinc-500">{{ index === planFactPeriods.length - 1 ? 'Текущий месяц' : 'Предыдущий месяц' }}</div></div><span v-if="!period.has_plan" class="rounded-full border border-zinc-700 px-2 py-1 text-[10px] text-zinc-500">План не назначен</span></div>
                                <div class="space-y-4">
                                    <div><div class="mb-1.5 flex items-end justify-between gap-3"><div><div class="text-xs text-zinc-500">Заказы</div><div class="mt-0.5 text-lg font-semibold text-white">{{ period.orders_fact.toLocaleString('ru-RU') }} <span class="text-xs font-normal text-zinc-500">из {{ period.orders_plan.toLocaleString('ru-RU') }}</span></div></div><span class="text-sm font-semibold text-blue-400">{{ planPercent(period.orders_fact, period.orders_plan) }}%</span></div><div class="h-2 overflow-hidden rounded-full bg-zinc-800"><div class="h-full rounded-full bg-blue-500 transition-all" :style="{ width: progressWidth(period.orders_fact, period.orders_plan) }"></div></div></div>
                                    <div><div class="mb-1.5 flex items-end justify-between gap-3"><div><div class="text-xs text-zinc-500">Выкупы</div><div class="mt-0.5 text-lg font-semibold text-white">{{ period.sales_fact.toLocaleString('ru-RU') }} <span class="text-xs font-normal text-zinc-500">из {{ period.sales_plan.toLocaleString('ru-RU') }}</span></div></div><span class="text-sm font-semibold text-emerald-400">{{ planPercent(period.sales_fact, period.sales_plan) }}%</span></div><div class="h-2 overflow-hidden rounded-full bg-zinc-800"><div class="h-full rounded-full bg-emerald-500 transition-all" :style="{ width: progressWidth(period.sales_fact, period.sales_plan) }"></div></div></div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Stocks & Campaigns -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Warehouse Stocks (Accordion) -->
                    <Card class="glass-panel text-zinc-100">
                        <CardHeader class="border-b border-zinc-800/50 py-3 px-4">
                            <CardTitle class="flex items-center text-sm">
                                <Package class="w-4 h-4 mr-2 text-amber-400" />
                                Склады
                                <span class="ml-auto text-[11px] font-normal text-zinc-400 bg-zinc-900 px-2 py-0.5 rounded border border-zinc-800">
                                    Всего: <span class="text-white font-bold">{{ totalStocks.toLocaleString() }}</span>
                                </span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div class="px-2 overflow-y-auto max-h-[250px]">
                                <Accordion type="single" collapsible class="w-full">
                                    <AccordionItem value="stocks" class="border-none">
                                        <AccordionTrigger class="text-xs hover:no-underline hover:text-zinc-300 text-zinc-400 py-3 px-2 font-medium transition-colors">
                                            Показать список ({{ product.warehouse_stocks?.length || 0 }})
                                        </AccordionTrigger>
                                        <AccordionContent>
                                            <Table class="mt-0 border-t border-zinc-800/50">
                                                <TableHeader>
                                                    <TableRow class="border-none hover:bg-transparent">
                                                        <TableHead class="text-zinc-500 pl-3 py-1.5 h-7 text-[10px] uppercase tracking-wider">Склад</TableHead>
                                                        <TableHead class="text-zinc-500 py-1.5 h-7 text-[10px] uppercase tracking-wider text-right pr-3">Шт.</TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    <TableRow v-for="ws in product.warehouse_stocks" :key="ws.id" class="border-zinc-800/30 hover:bg-zinc-800/40">
                                                        <TableCell class="font-medium text-zinc-300 pl-3 py-1.5 text-xs">{{ ws.warehouse_name }}</TableCell>
                                                        <TableCell class="text-right text-emerald-400 font-medium py-1.5 pr-3 text-xs">{{ ws.quantity }}</TableCell>
                                                    </TableRow>
                                                    <TableRow v-if="!product.warehouse_stocks || product.warehouse_stocks.length === 0">
                                                        <TableCell colspan="2" class="text-center text-zinc-500 py-4 text-xs">Нет данных</TableCell>
                                                    </TableRow>
                                                </TableBody>
                                            </Table>
                                        </AccordionContent>
                                    </AccordionItem>
                                </Accordion>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <!-- Advertising Campaigns -->
                    <Card class="glass-panel text-zinc-100">
                        <CardHeader class="border-b border-zinc-800/50 py-3 px-4">
                            <CardTitle class="flex items-center text-sm">
                                <Megaphone class="w-4 h-4 mr-2 text-purple-400" />
                                Реклама
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-0 overflow-y-auto max-h-[250px]">
                            <Table>
                                <TableHeader>
                                    <TableRow class="border-none bg-zinc-900/30 hover:bg-transparent">
                                        <TableHead class="text-zinc-500 pl-4 py-1.5 h-7 text-[10px] uppercase tracking-wider">Название</TableHead>
                                        <TableHead class="text-zinc-500 pr-4 py-1.5 h-7 text-[10px] uppercase tracking-wider text-right">Бюджет</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="campaign in campaigns" :key="campaign.id" class="border-zinc-800/30 hover:bg-zinc-800/40 cursor-pointer" @click="$inertia.visit(route('adverts.index'))">
                                        <TableCell class="pl-4 py-1.5">
                                            <div class="font-medium text-zinc-200 text-xs truncate max-w-[120px]">{{ campaign.name || `РК #${campaign.advert_id}` }}</div>
                                            <div class="mt-0.5">
                                                <span v-if="campaign.status == 9" class="inline-flex items-center px-1 py-0 rounded text-[8px] uppercase font-bold text-emerald-400">Активна</span>
                                                <span v-else-if="campaign.status == 11" class="inline-flex items-center px-1 py-0 rounded text-[8px] uppercase font-bold text-amber-400">Пауза</span>
                                                <span v-else class="inline-flex items-center px-1 py-0 rounded text-[8px] uppercase font-bold text-zinc-500">Ст. {{ campaign.status }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-right pr-4 py-1.5">
                                            <div class="text-zinc-300 font-medium text-xs inline-flex items-center">
                                                ₽{{ campaign.daily_budget || 0 }}
                                                <ExternalLink class="w-3 h-3 ml-1 text-zinc-600" />
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="!campaigns || campaigns.length === 0" class="hover:bg-transparent">
                                        <TableCell colspan="2" class="text-center text-zinc-500 py-6 text-xs">Нет кампаний</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                </div>
            </div>
        </div>

        <!-- P&L Analytics Section -->
        <div class="mt-6 animate-slide-up">
            <Card class="glass-panel text-zinc-100 group border border-zinc-700/50 shadow-2xl">
                <CardHeader class="pb-4 border-b border-zinc-800/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <CardTitle class="flex items-center text-xl text-white">
                        <BarChart3 class="w-6 h-6 mr-3 text-purple-400" />
                        P&L Аналитика
                    </CardTitle>
                    <div class="flex items-center space-x-2 bg-zinc-900 p-1 rounded-lg border border-zinc-800">
                        <Button variant="ghost" size="sm" class="rounded-md h-8 px-4 text-xs font-medium" :class="plTimeRange === 7 ? 'bg-zinc-800 text-white shadow-sm' : 'text-zinc-400 hover:text-zinc-200'" @click="plTimeRange = 7">7 дней</Button>
                        <Button variant="ghost" size="sm" class="rounded-md h-8 px-4 text-xs font-medium" :class="plTimeRange === 14 ? 'bg-zinc-800 text-white shadow-sm' : 'text-zinc-400 hover:text-zinc-200'" @click="plTimeRange = 14">14 дней</Button>
                        <Button variant="ghost" size="sm" class="rounded-md h-8 px-4 text-xs font-medium" :class="plTimeRange === 30 ? 'bg-zinc-800 text-white shadow-sm' : 'text-zinc-400 hover:text-zinc-200'" @click="plTimeRange = 30">30 дней</Button>
                    </div>
                </CardHeader>
                <CardContent class="pt-6">
                    <!-- 6 Metric Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <div class="bg-zinc-900/50 border border-zinc-800/80 rounded-xl p-5 shadow-inner">
                            <div class="text-zinc-400 text-xs uppercase tracking-wider font-semibold mb-1.5">Выручка</div>
                            <div class="text-3xl font-bold text-white mb-2">₽{{ plData.totals.revenue?.toLocaleString('ru-RU') || 0 }}</div>
                        </div>
                        <div class="bg-zinc-900/50 border border-zinc-800/80 rounded-xl p-5 shadow-inner">
                            <div class="text-zinc-400 text-xs uppercase tracking-wider font-semibold mb-1.5">Количество заказов</div>
                            <div class="text-3xl font-bold text-white mb-2">{{ plData.totals.ordersCount || 0 }} шт.</div>
                            <div class="text-amber-400 text-xs flex items-center font-medium">Оформлено заказов 🔒</div>
                        </div>
                        <div class="bg-zinc-900/50 border border-zinc-800/80 rounded-xl p-5 shadow-inner">
                            <div class="text-zinc-400 text-xs uppercase tracking-wider font-semibold mb-1.5">Количество выкупов</div>
                            <div class="text-3xl font-bold text-white mb-2">{{ plData.totals.buyoutsCount || 0 }} шт.</div>
                            <div class="text-emerald-400 text-xs flex items-center font-medium">Фактические продажи ✓</div>
                        </div>
                        
                        <div class="bg-zinc-900/50 border border-zinc-800/80 rounded-xl p-5 shadow-inner">
                            <div class="text-zinc-400 text-xs uppercase tracking-wider font-semibold mb-1.5">% Выкупа (Воронка)</div>
                            <div class="text-3xl font-bold text-white mb-2">{{ plData.totals.funnelBuyoutPercent || 0 }}%</div>
                            <div class="text-amber-400 text-xs font-medium">Из {{ plData.totals.funnelOrders || 0 }} зак. выкуплено {{ plData.totals.funnelBuyouts || 0 }} 🔄</div>
                        </div>
                        <div class="bg-zinc-900/50 border border-zinc-800/80 rounded-xl p-5 shadow-inner">
                            <div class="text-zinc-400 text-xs uppercase tracking-wider font-semibold mb-1.5">Общий остаток</div>
                            <div class="text-3xl font-bold text-white mb-2">{{ totalStocks.toLocaleString() }} шт.</div>
                            <div class="text-zinc-500 text-xs font-medium">По всем складам и размерам 📦</div>
                        </div>
                        <div class="bg-zinc-900/50 border border-zinc-800/80 rounded-xl p-5 shadow-inner relative overflow-hidden">
                            <div class="text-zinc-400 text-xs uppercase tracking-wider font-semibold mb-1.5 z-10 relative">Маржинальность</div>
                            <div class="text-3xl font-bold mb-2 z-10 relative" :class="Number(plData.totals.margin) < 0 ? 'text-rose-400' : 'text-emerald-400'">{{ plData.totals.margin || 0 }}%</div>
                            <div class="text-zinc-500 text-xs font-medium z-10 relative">Рентабельность (ROI)</div>
                            <div v-if="Number(plData.totals.margin) > 0" class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent z-0 pointer-events-none"></div>
                            <div v-if="Number(plData.totals.margin) < 0" class="absolute inset-0 bg-gradient-to-br from-rose-500/5 to-transparent z-0 pointer-events-none"></div>
                        </div>
                    </div>

                    <!-- Chart -->
                    <div class="pt-6 border-t border-zinc-800/50">
                        <h3 class="text-sm font-semibold text-zinc-300 mb-4 uppercase tracking-wider">Динамика продаж и заказов</h3>
                        <ClientOnly>
                            <VueApexCharts v-if="plData.chartData.length" type="line" height="350" :options="plChartOptions" :series="plChartSeries"></VueApexCharts>
                            <div v-else class="h-64 flex items-center justify-center text-zinc-500">Нет данных для графика</div>
                        </ClientOnly>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Full Width Funnel Table & Chart -->
        <div class="mt-6 animate-slide-up pb-10">
            <Card class="glass-panel text-zinc-100 group border border-zinc-700/50 shadow-2xl">
                <CardHeader class="pb-4 border-b border-zinc-800/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <CardTitle class="flex items-center text-xl text-white">
                        <TrendingUp class="w-6 h-6 mr-3 text-blue-400" />
                        Динамика показателей
                    </CardTitle>
                    <div class="flex items-center space-x-2 bg-zinc-900 p-1 rounded-lg border border-zinc-800">
                        <Button variant="ghost" size="sm" class="rounded-md h-8 px-4 text-xs font-medium" :class="timeRange === 7 ? 'bg-zinc-800 text-white shadow-sm' : 'text-zinc-400 hover:text-zinc-200'" @click="timeRange = 7">7 дней</Button>
                        <Button variant="ghost" size="sm" class="rounded-md h-8 px-4 text-xs font-medium" :class="timeRange === 14 ? 'bg-zinc-800 text-white shadow-sm' : 'text-zinc-400 hover:text-zinc-200'" @click="timeRange = 14">14 дней</Button>
                        <Button variant="ghost" size="sm" class="rounded-md h-8 px-4 text-xs font-medium" :class="timeRange === 30 ? 'bg-zinc-800 text-white shadow-sm' : 'text-zinc-400 hover:text-zinc-200'" @click="timeRange = 30">30 дней</Button>
                    </div>
                </CardHeader>
                <CardContent class="pt-0 px-0">
                    
                    <!-- The Transposed Table -->
                    <div class="w-full overflow-x-auto">
                        <Table class="w-full text-sm">
                            <TableHeader>
                                <TableRow class="border-b border-zinc-800 bg-zinc-900/40 hover:bg-zinc-900/40">
                                    <TableHead class="text-zinc-300 font-medium pl-6 py-4 sticky left-0 bg-zinc-900/90 backdrop-blur z-10 w-48 shadow-[2px_0_10px_-3px_rgba(0,0,0,0.5)]">Метрика</TableHead>
                                    <TableHead class="text-amber-400 font-bold py-4 text-center border-r border-zinc-800/50 min-w-[100px]">ИТОГО</TableHead>
                                    <TableHead v-for="(date, i) in tableData.headers" :key="i" class="text-zinc-400 py-4 text-center font-medium min-w-[100px]">
                                        {{ date }}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(row, i) in tableData.rows" :key="i" class="border-b border-zinc-800/30 hover:bg-zinc-800/20 transition-colors">
                                    <!-- Metric Name -->
                                    <TableCell class="font-medium text-zinc-200 pl-6 sticky left-0 bg-zinc-900/90 backdrop-blur z-10 shadow-[2px_0_10px_-3px_rgba(0,0,0,0.5)] py-3">
                                        {{ row.label }}
                                    </TableCell>
                                    
                                    <!-- Total -->
                                    <TableCell class="text-center font-bold text-white border-r border-zinc-800/50 bg-zinc-900/20 py-3">
                                        {{ row.total }}
                                    </TableCell>
                                    
                                    <!-- Daily Values -->
                                    <TableCell v-for="(val, j) in row.values" :key="j" class="text-center py-3">
                                        <div class="font-semibold text-zinc-100">{{ val.current }}</div>
                                        <div v-if="val.diff" :class="['text-[10px] font-bold mt-0.5', val.diff.color]">
                                            {{ val.diff.val }}
                                        </div>
                                    </TableCell>
                                </TableRow>
                                
                                <TableRow v-if="tableData.headers.length === 0">
                                    <TableCell :colspan="tableData.headers.length + 2" class="h-32 text-center text-zinc-500">
                                        Нет данных за выбранный период
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- The Area Chart Below -->
                    <div class="px-6 pb-6 pt-10 border-t border-zinc-800/50 mt-4">
                        <ClientOnly>
                            <VueApexCharts v-if="filteredAnalytics.length" type="area" height="300" :options="funnelChartOptions" :series="funnelChartSeries"></VueApexCharts>
                            <div v-else class="h-64 flex items-center justify-center text-zinc-500">Нет данных для графика</div>
                        </ClientOnly>
                    </div>

                </CardContent>
            </Card>
        </div>
        <!-- Unit Economics Calculator -->
        <div class="mt-6 animate-slide-up pb-10">
            <Card class="glass-panel text-zinc-100 group border border-zinc-700/50 shadow-2xl">
                <CardHeader class="pb-4 border-b border-zinc-800/50">
                    <CardTitle class="flex items-center text-xl text-white">
                        <Calculator class="w-6 h-6 mr-3 text-emerald-400" />
                        Моделирование цены (Юнит-экономика)
                    </CardTitle>
                </CardHeader>
                <CardContent class="pt-6">
                    <div class="bg-zinc-900/40 rounded-xl p-4 sm:p-6 border border-zinc-800/80">
                        <div class="flex items-center mb-6">
                            <Calculator class="w-5 h-5 text-zinc-400 mr-2" />
                            <div>
                                <div class="text-sm font-semibold text-zinc-200">Интерактивный калькулятор Юнит-Экономики</div>
                                <div class="text-xs text-zinc-500">Изменяйте значения ниже, чтобы моментально увидеть, как изменится маржа и ROI. Данные по умолчанию берутся из статистики товара.</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                            <!-- Input Fields -->
                            <div class="space-y-1.5">
                                <label class="text-xs font-medium text-zinc-400">Цена на сайте (₽)</label>
                                <Input v-model="calcPrice" type="number" class="bg-zinc-900/80 border-zinc-700 text-white focus-visible:ring-emerald-500 font-semibold" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-medium text-zinc-400">Себестоимость (₽)</label>
                                <Input v-model="calcCost" type="number" class="bg-zinc-900/80 border-zinc-700 text-white focus-visible:ring-emerald-500" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-medium text-zinc-400">Процент выкупа (%)</label>
                                <Input v-model="calcBuyoutPercent" type="number" class="bg-zinc-900/80 border-zinc-700 text-white focus-visible:ring-emerald-500" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-medium text-zinc-400">Налог (%)</label>
                                <Input v-model="calcTax" type="number" class="bg-zinc-900/80 border-zinc-700 text-white focus-visible:ring-emerald-500" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-medium text-zinc-400">Комиссия WB (%)</label>
                                <Input v-model="calcCommission" type="number" class="bg-zinc-900/80 border-zinc-700 text-white focus-visible:ring-emerald-500" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-medium text-zinc-400">Логистика к клиенту (₽)</label>
                                <Input v-model="calcLogisticsTo" type="number" class="bg-zinc-900/80 border-zinc-700 text-white focus-visible:ring-emerald-500" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-medium text-zinc-400">Обратная логистика (₽)</label>
                                <Input v-model="calcLogisticsReturn" type="number" class="bg-zinc-900/80 border-zinc-700 text-white focus-visible:ring-emerald-500" />
                            </div>
                        </div>

                        <!-- Results Block -->
                        <div class="bg-zinc-900/80 rounded-xl p-6 border border-zinc-800/80 flex flex-col md:flex-row justify-between items-center gap-6">
                            <div class="flex-1 text-center md:text-left">
                                <div class="text-xs font-semibold text-zinc-400 mb-1">Чистая прибыль с 1 шт.</div>
                                <div class="text-2xl lg:text-3xl font-bold" :class="Number(calcResults.netProfit) >= 0 ? 'text-emerald-400' : 'text-rose-500'">
                                    {{ Number(calcResults.netProfit) > 0 ? '+' : '' }}{{ calcResults.netProfit }} ₽
                                </div>
                            </div>
                            <div class="hidden md:block w-px h-16 bg-zinc-800/80"></div>
                            <div class="flex-1 text-center">
                                <div class="text-xs font-semibold text-zinc-400 mb-1">Маржинальность</div>
                                <div class="text-2xl lg:text-3xl font-bold" :class="Number(calcResults.margin) >= 0 ? 'text-emerald-400' : 'text-rose-500'">
                                    {{ Number(calcResults.margin) > 0 ? '+' : '' }}{{ calcResults.margin }} %
                                </div>
                            </div>
                            <div class="hidden md:block w-px h-16 bg-zinc-800/80"></div>
                            <div class="flex-1 text-center md:text-right">
                                <div class="text-xs font-semibold text-zinc-400 mb-1">ROI (Окупаемость)</div>
                                <div class="text-2xl lg:text-3xl font-bold" :class="Number(calcResults.roi) >= 0 ? 'text-emerald-400' : 'text-rose-500'">
                                    {{ Number(calcResults.roi) > 0 ? '+' : '' }}{{ calcResults.roi }} %
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Ensure custom scrollbars for the table area to make it look premium */
::-webkit-scrollbar {
  height: 8px;
  width: 8px;
}
::-webkit-scrollbar-track {
  background: rgba(39, 39, 42, 0.5);
  border-radius: 4px;
}
::-webkit-scrollbar-thumb {
  background: rgba(82, 82, 91, 0.8);
  border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
  background: rgba(113, 113, 122, 1);
}
</style>
