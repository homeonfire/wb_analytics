<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Save, AlertCircle, Filter, Package } from 'lucide-vue-next';

const props = defineProps({
    products: Array,
    selectedMonth: Number,
    selectedYear: Number
});

const monthNames = [
    'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
    'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
];

const selectedMonth = ref(props.selectedMonth);
const selectedYear = ref(props.selectedYear);

const handlePeriodChange = () => {
    router.get(route('plans.index'), {
        month: selectedMonth.value,
        year: selectedYear.value
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const form = useForm({
    year: props.selectedYear,
    month: props.selectedMonth,
    plans: props.products.map(p => ({
        product_id: p.id,
        orders_plan: p.orders_plan || 0,
        sales_plan: p.sales_plan || 0
    }))
});

// Sync form when props change after period change
computed(() => props.products).value; // triggers reactivity
router.on('success', (event) => {
    if (event.detail.page.url.startsWith('/plans')) {
        form.year = props.selectedYear;
        form.month = props.selectedMonth;
        form.plans = props.products.map(p => ({
            product_id: p.id,
            orders_plan: p.orders_plan || 0,
            sales_plan: p.sales_plan || 0
        }));
    }
});

const submit = () => {
    form.post(route('plans.store'), {
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="План-Факт" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-2xl tracking-tight text-white animate-fade-in">План-Факт</h2>
                    <p class="text-sm text-zinc-400 mt-1">Установка плановых показателей для товаров</p>
                </div>
            </div>
        </template>

        <div class="grid gap-6 mt-6 animate-slide-up pb-10">
            <Card class="glass-panel border-zinc-800/50">
                <CardHeader class="border-b border-zinc-800/50 flex flex-row items-center justify-between pb-4">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-zinc-400">Месяц:</span>
                            <select v-model="selectedMonth" @change="handlePeriodChange" class="bg-zinc-900 border border-zinc-700 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2">
                                <option v-for="(name, index) in monthNames" :key="index" :value="index + 1">{{ name }}</option>
                            </select>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-zinc-400">Год:</span>
                            <select v-model="selectedYear" @change="handlePeriodChange" class="bg-zinc-900 border border-zinc-700 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2">
                                <option :value="2025">2025</option>
                                <option :value="2026">2026</option>
                                <option :value="2027">2027</option>
                            </select>
                        </div>
                    </div>
                    <Button @click="submit" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-500 text-white">
                        <Save class="w-4 h-4 mr-2" />
                        Сохранить планы
                    </Button>
                </CardHeader>
                <div class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-zinc-400 bg-zinc-900/50 uppercase border-b border-zinc-800/50">
                                <tr>
                                    <th class="px-6 py-4 font-medium">Товар</th>
                                    <th class="px-6 py-4 font-medium text-center w-48">План Заказов (шт)</th>
                                    <th class="px-6 py-4 font-medium text-center w-48">План Выкупов (шт)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-800/30">
                                <tr v-for="(product, index) in products" :key="product.id" class="hover:bg-zinc-800/30 transition-colors">
                                    <td class="px-6 py-4 flex items-center space-x-4">
                                        <div class="w-10 h-14 rounded-md overflow-hidden bg-zinc-800 shrink-0">
                                            <img :src="product.main_image_url" :alt="product.title" class="w-full h-full object-cover" />
                                        </div>
                                        <div>
                                            <div class="font-medium text-zinc-200 line-clamp-1" :title="product.title">{{ product.title }}</div>
                                            <div class="text-xs text-zinc-500 mt-1">{{ product.vendor_code }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <input 
                                            type="number" 
                                            v-model="form.plans[index].orders_plan" 
                                            class="bg-zinc-900/50 border border-zinc-700 text-white text-center text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                            min="0"
                                        />
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <input 
                                            type="number" 
                                            v-model="form.plans[index].sales_plan" 
                                            class="bg-zinc-900/50 border border-zinc-700 text-white text-center text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5"
                                            min="0"
                                        />
                                    </td>
                                </tr>
                                <tr v-if="products.length === 0">
                                    <td colspan="3" class="px-6 py-8 text-center text-zinc-500">
                                        Нет товаров для установки планов
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
