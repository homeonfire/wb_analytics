<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table';
import { 
  Dialog, 
  DialogContent, 
  DialogHeader, 
  DialogTitle, 
  DialogTrigger,
  DialogFooter
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { 
  Select, 
  SelectContent, 
  SelectItem, 
  SelectTrigger, 
  SelectValue 
} from '@/Components/ui/select';
import { Megaphone, ExternalLink, Activity, Plus } from 'lucide-vue-next';

const props = defineProps({
    campaigns: Object,
    externalAdverts: Object,
    products: Array,
});

const activeTab = ref('wb'); // 'wb' or 'external'
const isDialogOpen = ref(false);

const form = useForm({
    product_id: '',
    blogger_link: '',
    ad_cost: '',
    platform: 'tg',
    release_date: '',
    status: 'published',
});

const submitForm = () => {
    form.post(route('adverts.external.store'), {
        onSuccess: () => {
            isDialogOpen.value = false;
            form.reset();
        }
    });
};
</script>

<template>
    <Head title="Реклама" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-2xl tracking-tight text-white animate-fade-in">Рекламные кампании</h2>
        </template>

        <div class="mt-6 flex space-x-2 animate-slide-up">
            <button 
                @click="activeTab = 'wb'"
                class="px-4 py-2 text-sm font-medium rounded-lg transition-all"
                :class="activeTab === 'wb' ? 'bg-blue-600 text-white shadow-[0_0_15px_rgba(37,99,235,0.4)]' : 'bg-zinc-900/50 text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800'"
            >
                Внутренняя реклама (WB)
            </button>
            <button 
                @click="activeTab = 'external'"
                class="px-4 py-2 text-sm font-medium rounded-lg transition-all"
                :class="activeTab === 'external' ? 'bg-purple-600 text-white shadow-[0_0_15px_rgba(147,51,234,0.4)]' : 'bg-zinc-900/50 text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800'"
            >
                Внешняя реклама
            </button>
        </div>

        <!-- WB Campaigns -->
        <Card v-if="activeTab === 'wb'" class="mt-6 glass-panel text-zinc-100 animate-slide-up" style="animation-delay: 0.1s">
            <CardHeader class="flex flex-row items-center justify-between pb-6 border-b border-zinc-800/50">
                <CardTitle class="text-lg text-zinc-200 flex items-center">
                    <Activity class="w-5 h-5 mr-2 text-blue-400" />
                    Кампании Wildberries
                </CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="border-zinc-800/50 bg-zinc-900/30 hover:bg-zinc-900/30">
                                <TableHead class="text-zinc-400 pl-6 py-4">Название кампании</TableHead>
                                <TableHead class="text-zinc-400 py-4">Тип</TableHead>
                                <TableHead class="text-zinc-400 py-4">Артикул WB</TableHead>
                                <TableHead class="text-zinc-400 py-4 text-right">Бюджет (₽)</TableHead>
                                <TableHead class="text-zinc-400 pr-6 py-4 text-center">Статус</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="campaign in campaigns.data" :key="campaign.id" class="border-zinc-800/50 hover:bg-zinc-800/40">
                                <TableCell class="pl-6 font-medium text-white">{{ campaign.name || `Кампания #${campaign.advert_id}` }}</TableCell>
                                <TableCell class="text-zinc-400">Тип {{ campaign.type }}</TableCell>
                                <TableCell>
                                    <div class="flex items-center text-blue-400">
                                        <span class="font-mono">{{ campaign.nm_id }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right text-zinc-200">{{ campaign.daily_budget || '0' }}</TableCell>
                                <TableCell class="pr-6 text-center">
                                    <span v-if="campaign.status == 9" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Активна</span>
                                    <span v-else-if="campaign.status == 11" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">На паузе</span>
                                    <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-zinc-800 text-zinc-400 border border-zinc-700">Статус {{ campaign.status }}</span>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="campaigns.data.length === 0" class="hover:bg-transparent">
                                <TableCell colspan="5" class="h-32 text-center text-zinc-500">
                                    Нет активных кампаний WB.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>

        <!-- External Adverts -->
        <Card v-if="activeTab === 'external'" class="mt-6 glass-panel text-zinc-100 animate-slide-up" style="animation-delay: 0.1s">
            <CardHeader class="flex flex-row items-center justify-between pb-6 border-b border-zinc-800/50">
                <CardTitle class="text-lg text-zinc-200 flex items-center">
                    <ExternalLink class="w-5 h-5 mr-2 text-purple-400" />
                    Внешняя реклама (Интеграции)
                </CardTitle>
                
                <Dialog v-model:open="isDialogOpen">
                    <DialogTrigger asChild>
                        <Button class="bg-purple-600 hover:bg-purple-500 text-white shadow-[0_0_15px_rgba(147,51,234,0.3)]">
                            <Plus class="w-4 h-4 mr-2" /> Добавить интеграцию
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[425px] bg-zinc-950 border-zinc-800 text-zinc-100">
                        <DialogHeader>
                            <DialogTitle class="text-xl font-semibold">Добавить внешнюю рекламу</DialogTitle>
                        </DialogHeader>
                        
                        <form @submit.prevent="submitForm" class="space-y-4 mt-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-300">Платформа</label>
                                <Select v-model="form.platform">
                                    <SelectTrigger class="w-full bg-zinc-900 border-zinc-700 text-zinc-100">
                                        <SelectValue placeholder="Выберите платформу" />
                                    </SelectTrigger>
                                    <SelectContent class="bg-zinc-900 border-zinc-800">
                                        <SelectItem value="tg" class="focus:bg-zinc-800 focus:text-white">Telegram</SelectItem>
                                        <SelectItem value="vk" class="focus:bg-zinc-800 focus:text-white">ВКонтакте</SelectItem>
                                        <SelectItem value="inst" class="focus:bg-zinc-800 focus:text-white">Instagram</SelectItem>
                                        <SelectItem value="youtube" class="focus:bg-zinc-800 focus:text-white">YouTube</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-300">Товар</label>
                                <Select v-model="form.product_id">
                                    <SelectTrigger class="w-full bg-zinc-900 border-zinc-700 text-zinc-100">
                                        <SelectValue placeholder="Выберите товар" />
                                    </SelectTrigger>
                                    <SelectContent class="bg-zinc-900 border-zinc-800 max-h-48">
                                        <SelectItem v-for="product in products" :key="product.id" :value="product.id.toString()" class="focus:bg-zinc-800 focus:text-white">
                                            {{ product.title }} (Арт: {{ product.nm_id }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-300">Ссылка на блогера / пост</label>
                                <Input v-model="form.blogger_link" type="url" placeholder="https://t.me/example" class="bg-zinc-900 border-zinc-700 text-zinc-100 placeholder:text-zinc-600" required />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-300">Стоимость (₽)</label>
                                <Input v-model="form.ad_cost" type="number" min="0" step="0.01" placeholder="15000" class="bg-zinc-900 border-zinc-700 text-zinc-100 placeholder:text-zinc-600" required />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-300">Дата выхода</label>
                                <Input v-model="form.release_date" type="date" class="bg-zinc-900 border-zinc-700 text-zinc-100 [color-scheme:dark]" required />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-300">Статус</label>
                                <Select v-model="form.status">
                                    <SelectTrigger class="w-full bg-zinc-900 border-zinc-700 text-zinc-100">
                                        <SelectValue placeholder="Статус" />
                                    </SelectTrigger>
                                    <SelectContent class="bg-zinc-900 border-zinc-800">
                                        <SelectItem value="planned" class="focus:bg-zinc-800 focus:text-white">Запланировано</SelectItem>
                                        <SelectItem value="published" class="focus:bg-zinc-800 focus:text-white">Опубликовано</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <DialogFooter class="pt-4">
                                <Button type="submit" :disabled="form.processing" class="w-full bg-purple-600 hover:bg-purple-500 text-white">
                                    {{ form.processing ? 'Сохранение...' : 'Сохранить' }}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="border-zinc-800/50 bg-zinc-900/30 hover:bg-zinc-900/30">
                                <TableHead class="text-zinc-400 pl-6 py-4">Дата выхода</TableHead>
                                <TableHead class="text-zinc-400 py-4">Платформа / Ссылка</TableHead>
                                <TableHead class="text-zinc-400 py-4">Товар (Артикул)</TableHead>
                                <TableHead class="text-zinc-400 py-4 text-right">Стоимость (₽)</TableHead>
                                <TableHead class="text-zinc-400 py-4 text-center">Форматы</TableHead>
                                <TableHead class="text-zinc-400 pr-6 py-4 text-center">Статус</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="ext in externalAdverts.data" :key="ext.id" class="border-zinc-800/50 hover:bg-zinc-800/40 transition-colors">
                                <TableCell class="pl-6 text-zinc-300">
                                    {{ ext.release_date ? new Date(ext.release_date).toLocaleDateString('ru-RU') : '—' }}
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-col">
                                        <span class="text-white font-medium uppercase text-xs">{{ ext.platform }}</span>
                                        <a :href="ext.blogger_link" target="_blank" class="text-blue-400 hover:underline text-xs truncate max-w-[200px]" :title="ext.blogger_link">{{ ext.blogger_link }}</a>
                                    </div>
                                </TableCell>
                                <TableCell class="text-zinc-300">
                                    <div class="flex flex-col">
                                        <span class="truncate max-w-[150px] text-xs" :title="ext.product?.title">{{ ext.product?.title || 'Товар удален' }}</span>
                                        <span class="text-blue-400 font-mono text-xs">{{ ext.product?.nm_id || '' }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right text-zinc-200">₽{{ ext.ad_cost }}</TableCell>
                                <TableCell class="text-center text-zinc-400 text-xs">
                                    {{ ext.formats && ext.formats.length ? ext.formats.join(', ') : '—' }}
                                </TableCell>
                                <TableCell class="pr-6 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize"
                                        :class="ext.status === 'published' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20'">
                                        {{ ext.status === 'published' ? 'Опубликовано' : 'План' }}
                                    </span>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="externalAdverts.data.length === 0" class="hover:bg-transparent">
                                <TableCell colspan="6" class="h-32 text-center text-zinc-500">
                                    Нет данных о внешних интеграциях.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>

    </AuthenticatedLayout>
</template>
