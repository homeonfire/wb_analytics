<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { BarChart3, TrendingUp, Package, ShoppingCart, Percent, Crown } from 'lucide-vue-next';
import VueApexCharts from "vue3-apexcharts";
import { computed, ref, onMounted, defineComponent } from 'vue';

const ClientOnly = defineComponent({
  setup(_, { slots }) {
    const isMounted = ref(false)
    onMounted(() => {
      isMounted.value = true
    })
    return () => (isMounted.value && slots.default ? slots.default() : null)
  },
})

const formatNumber = (value) => new Intl.NumberFormat('ru-RU', {
    maximumFractionDigits: 0,
}).format(Number(value) || 0).replace(/[\u00a0\u202f]/g, ' ');

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({})
    },
    charts: {
        type: Object,
        default: () => ({ daily_revenue: [], top_products: [] })
    }
});

// ApexCharts Options for Revenue Area Chart
const revenueSeries = computed(() => [{
    name: 'Выручка',
    data: props.charts.daily_revenue.map(d => Number(d.revenue))
}]);

const revenueChartOptions = computed(() => ({
    chart: {
        type: 'area',
        height: 350,
        toolbar: { show: false },
        background: 'transparent',
        fontFamily: 'Inter, sans-serif',
        animations: { enabled: true, easing: 'easeinout', speed: 800 }
    },
    colors: ['#3b82f6'],
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.05,
            stops: [0, 100]
        }
    },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    xaxis: {
        categories: props.charts.daily_revenue.map(d => new Date(d.date).toLocaleDateString('ru-RU', {day: 'numeric', month: 'short'})),
        labels: { style: { colors: '#71717a' } },
        axisBorder: { show: false },
        axisTicks: { show: false }
    },
    yaxis: {
        labels: {
            formatter: (value) => {
                if(value >= 1000000) return (value / 1000000).toFixed(1) + 'M ₽';
                if(value >= 1000) return (value / 1000).toFixed(0) + 'K ₽';
                return value + ' ₽';
            },
            style: { colors: '#71717a' }
        }
    },
    grid: { borderColor: '#27272a', strokeDashArray: 4 },
    theme: { mode: 'dark' },
    tooltip: {
        theme: 'dark',
        y: { formatter: (val) => val.toLocaleString('ru-RU') + ' ₽' }
    }
}));

// ApexCharts Options for Top Products Donut Chart
const topProductsSeries = computed(() => 
    props.charts.top_products.map(p => Number(p.total_revenue))
);

const topProductsOptions = computed(() => ({
    chart: {
        type: 'donut',
        background: 'transparent',
        fontFamily: 'Inter, sans-serif',
    },
    labels: props.charts.top_products.map(p => p.title),
    colors: ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#f43f5e'],
    stroke: { show: false },
    dataLabels: { enabled: false },
    legend: { show: false },
    theme: { mode: 'dark' },
    plotOptions: {
        pie: {
            donut: {
                size: '75%',
                labels: {
                    show: true,
                    name: { color: '#71717a' },
                    value: { color: '#ffffff', formatter: (val) => Number(val).toLocaleString('ru-RU') + ' ₽' },
                    total: {
                        show: true,
                        showAlways: true,
                        label: 'Топ 5 товаров',
                        color: '#71717a',
                        formatter: function (w) {
                            return w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString('ru-RU') + ' ₽'
                        }
                    }
                }
            }
        }
    },
    tooltip: {
        theme: 'dark',
        y: { formatter: (val) => val.toLocaleString('ru-RU') + ' ₽' }
    }
}));
</script>

<template>
    <Head title="Дашборд" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-2xl tracking-tight text-white animate-fade-in">Обзор показателей</h2>
            <p class="text-zinc-400 text-sm mt-1 animate-slide-up">Ключевые метрики за последние 30 дней</p>
        </template>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4 mt-6 animate-slide-up" style="animation-delay: 0.1s">
            <Card class="glass-panel overflow-hidden group">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2 relative z-10">
                    <CardTitle class="text-sm font-medium text-zinc-400">Общая выручка</CardTitle>
                    <div class="p-2 bg-emerald-500/10 rounded-lg group-hover:bg-emerald-500/20 transition-colors">
                        <TrendingUp class="h-4 w-4 text-emerald-500" />
                    </div>
                </CardHeader>
                <CardContent class="relative z-10">
                    <div class="text-3xl font-bold tracking-tight text-white mb-1">
                        {{ formatNumber(stats?.revenue) }} ₽
                    </div>
                    <p class="text-xs text-emerald-500 flex items-center mt-1">
                        +12% <span class="text-zinc-500 ml-1">к прошлому периоду</span>
                    </p>
                </CardContent>
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            </Card>

            <Card class="glass-panel overflow-hidden group">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2 relative z-10">
                    <CardTitle class="text-sm font-medium text-zinc-400">Заказы</CardTitle>
                    <div class="p-2 bg-blue-500/10 rounded-lg group-hover:bg-blue-500/20 transition-colors">
                        <ShoppingCart class="h-4 w-4 text-blue-500" />
                    </div>
                </CardHeader>
                <CardContent class="relative z-10">
                    <div class="text-3xl font-bold tracking-tight text-white mb-1">
                        {{ stats?.orders_count?.toLocaleString() ?? 0 }}
                    </div>
                    <p class="text-xs text-blue-500 flex items-center mt-1">
                        +5.2% <span class="text-zinc-500 ml-1">к прошлому периоду</span>
                    </p>
                </CardContent>
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            </Card>

            <Card class="glass-panel overflow-hidden group">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2 relative z-10">
                    <CardTitle class="text-sm font-medium text-zinc-400">Всего товаров</CardTitle>
                    <div class="p-2 bg-purple-500/10 rounded-lg group-hover:bg-purple-500/20 transition-colors">
                        <Package class="h-4 w-4 text-purple-500" />
                    </div>
                </CardHeader>
                <CardContent class="relative z-10">
                    <div class="text-3xl font-bold tracking-tight text-white mb-1">
                        {{ stats?.products_count?.toLocaleString() ?? 0 }}
                    </div>
                    <p class="text-xs text-zinc-500 mt-1">Активный ассортимент</p>
                </CardContent>
                <div class="absolute inset-0 bg-gradient-to-br from-purple-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            </Card>

            <Card class="glass-panel overflow-hidden group">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2 relative z-10">
                    <CardTitle class="text-sm font-medium text-zinc-400">Средняя конверсия</CardTitle>
                    <div class="p-2 bg-amber-500/10 rounded-lg group-hover:bg-amber-500/20 transition-colors">
                        <Percent class="h-4 w-4 text-amber-500" />
                    </div>
                </CardHeader>
                <CardContent class="relative z-10">
                    <div class="text-3xl font-bold tracking-tight text-white mb-1">
                        {{ stats?.conversion_rate ?? 0 }}%
                    </div>
                    <p class="text-xs text-amber-500 flex items-center mt-1">
                        +1.1% <span class="text-zinc-500 ml-1">за неделю</span>
                    </p>
                </CardContent>
                <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            </Card>
        </div>

        <div class="grid gap-6 md:grid-cols-7 mt-6 animate-slide-up" style="animation-delay: 0.2s">
            <Card class="col-span-4 glass-panel flex flex-col">
                <CardHeader>
                    <CardTitle class="text-lg text-zinc-200">Динамика выручки</CardTitle>
                </CardHeader>
                <CardContent class="flex-1 min-h-[350px]">
                    <ClientOnly>
                        <VueApexCharts v-if="charts.daily_revenue.length" type="area" height="350" :options="revenueChartOptions" :series="revenueSeries"></VueApexCharts>
                        <div v-else class="h-full flex items-center justify-center text-zinc-500">Нет данных для графика</div>
                    </ClientOnly>
                </CardContent>
            </Card>
            
            <Card class="col-span-3 glass-panel flex flex-col">
                <CardHeader>
                    <CardTitle class="text-lg text-zinc-200">Топ 5 товаров по выручке</CardTitle>
                </CardHeader>
                <CardContent class="flex-1 flex flex-col justify-center min-h-[350px]">
                    <ClientOnly>
                        <VueApexCharts v-if="charts.top_products.length" type="donut" height="300" :options="topProductsOptions" :series="topProductsSeries"></VueApexCharts>
                        <div v-else class="h-full flex items-center justify-center text-zinc-500">Нет данных о продажах</div>
                    </ClientOnly>
                    
                    <div v-if="charts.top_products.length" class="mt-4 space-y-3 px-2">
                        <div v-for="(product, idx) in charts.top_products" :key="idx" class="flex items-center justify-between text-sm">
                            <div class="flex items-center truncate mr-4">
                                <span class="w-2 h-2 rounded-full mr-2" :style="{ backgroundColor: topProductsOptions.colors[idx] }"></span>
                                <span class="text-zinc-300 truncate" :title="product.title">{{ product.title }}</span>
                            </div>
                            <span class="font-medium text-white whitespace-nowrap">₽{{ Number(product.total_revenue).toLocaleString('ru-RU') }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
