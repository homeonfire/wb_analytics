<script setup>
import { computed, onBeforeUnmount, onMounted } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { CalendarClock, CheckCircle2, CircleOff, Clock3, Play, RefreshCw, Trash2, XCircle } from 'lucide-vue-next';

const props = defineProps({ tasks: Array, runs: Array, schedules: Array });
const taskMap = computed(() => Object.fromEntries(props.tasks.map(task => [task.key, task])));
const runForm = useForm({ task: 'orders', days: 7 });
const scheduleForm = useForm({ task: 'orders', frequency: 'hourly', run_at: '03:00', days: 7 });
const selectedRunTask = computed(() => taskMap.value[runForm.task]);
const selectedScheduleTask = computed(() => taskMap.value[scheduleForm.task]);
const hasActiveRuns = computed(() => props.runs.some(run => ['queued', 'running'].includes(run.status)));
let refreshTimer;

const submitRun = () => runForm.post(route('sync.run'), { preserveScroll: true });
const submitSchedule = () => scheduleForm.post(route('sync.schedules.store'), { preserveScroll: true });
const toggleSchedule = id => router.patch(route('sync.schedules.toggle', id), {}, { preserveScroll: true });
const deleteSchedule = id => {
    if (confirm('Удалить это расписание?')) router.delete(route('sync.schedules.destroy', id), { preserveScroll: true });
};
const labelFor = key => taskMap.value[key]?.label ?? key;
const dateTime = value => value ? new Intl.DateTimeFormat('ru-RU', { dateStyle: 'short', timeStyle: 'medium' }).format(new Date(value)) : '—';
const duration = run => {
    if (!run.started_at) return '—';
    const end = run.finished_at ? new Date(run.finished_at) : new Date();
    return `${Math.max(0, Math.round((end - new Date(run.started_at)) / 1000))} сек.`;
};
const frequencyLabels = { '15_minutes': 'Каждые 15 минут', '30_minutes': 'Каждые 30 минут', hourly: 'Каждый час', daily: 'Ежедневно' };
const statusLabels = { queued: 'В очереди', running: 'Выполняется', completed: 'Готово', failed: 'Ошибка' };
const statusClass = status => ({ queued: 'bg-amber-500/10 text-amber-300 border-amber-500/20', running: 'bg-blue-500/10 text-blue-300 border-blue-500/20', completed: 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20', failed: 'bg-rose-500/10 text-rose-300 border-rose-500/20' }[status]);

onMounted(() => {
    refreshTimer = window.setInterval(() => {
        if (hasActiveRuns.value) router.reload({ only: ['runs'], preserveScroll: true, preserveState: true });
    }, 5000);
});
onBeforeUnmount(() => window.clearInterval(refreshTimer));
</script>

<template>
    <Head title="Синхронизация" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="flex items-center text-2xl font-semibold tracking-tight text-white"><RefreshCw class="mr-3 h-6 w-6 text-blue-400" />Синхронизация</h2>
                    <p class="mt-1 text-sm text-zinc-500">Запуск и расписание для выбранного магазина</p>
                </div>
                <div v-if="hasActiveRuns" class="flex items-center gap-2 rounded-full border border-blue-500/20 bg-blue-500/10 px-3 py-1.5 text-xs text-blue-300">
                    <RefreshCw class="h-3.5 w-3.5 animate-spin" /> Обновление выполняется
                </div>
            </div>
        </template>

        <Tabs default-value="run" class="mt-6">
            <TabsList class="bg-zinc-900/80 border border-zinc-800">
                <TabsTrigger value="run">Ручной запуск</TabsTrigger>
                <TabsTrigger value="schedules">Расписание</TabsTrigger>
                <TabsTrigger value="history">История</TabsTrigger>
            </TabsList>

            <TabsContent value="run" class="mt-5">
                <div class="grid gap-5 lg:grid-cols-[1fr_360px]">
                    <Card class="glass-panel border-zinc-800 text-zinc-100">
                        <CardHeader><CardTitle>Запустить обновление</CardTitle><CardDescription>Задача выполнится в фоне только для текущего магазина.</CardDescription></CardHeader>
                        <CardContent>
                            <form class="space-y-5" @submit.prevent="submitRun">
                                <div class="space-y-2"><Label>Операция</Label><Select v-model="runForm.task"><SelectTrigger class="border-zinc-700 bg-zinc-900"><SelectValue /></SelectTrigger><SelectContent class="border-zinc-800 bg-zinc-900"><SelectItem v-for="task in tasks" :key="task.key" :value="task.key">{{ task.label }}</SelectItem></SelectContent></Select><p class="text-xs text-zinc-500">{{ selectedRunTask?.description }}</p></div>
                                <div v-if="selectedRunTask?.days" class="space-y-2"><Label for="run-days">Период, дней</Label><Input id="run-days" v-model="runForm.days" type="number" min="1" :max="selectedRunTask.max_days || 90" class="border-zinc-700 bg-zinc-900" /></div>
                                <Button type="submit" :disabled="runForm.processing" class="bg-blue-600 text-white hover:bg-blue-500"><Play class="mr-2 h-4 w-4" />Поставить в очередь</Button>
                            </form>
                        </CardContent>
                    </Card>
                    <Card class="border-zinc-800 bg-zinc-900/50 text-zinc-100"><CardHeader><CardTitle class="text-base">Как это работает</CardTitle></CardHeader><CardContent class="space-y-4 text-sm text-zinc-400"><div class="flex gap-3"><Clock3 class="h-5 w-5 shrink-0 text-blue-400" /><span>Страница не ждёт ответа WB: выполнение идёт в очереди.</span></div><div class="flex gap-3"><CircleOff class="h-5 w-5 shrink-0 text-violet-400" /><span>Для одного магазина одновременно выполняется только одна синхронизация.</span></div><div class="flex gap-3"><CheckCircle2 class="h-5 w-5 shrink-0 text-emerald-400" /><span>Результат и ошибка сохраняются в истории запусков.</span></div></CardContent></Card>
                </div>
            </TabsContent>

            <TabsContent value="schedules" class="mt-5 space-y-5">
                <Card class="glass-panel border-zinc-800 text-zinc-100"><CardHeader><CardTitle>Новое расписание</CardTitle><CardDescription>Время отображается в часовом поясе приложения.</CardDescription></CardHeader><CardContent><form class="grid gap-4 md:grid-cols-4 md:items-end" @submit.prevent="submitSchedule"><div class="space-y-2"><Label>Операция</Label><Select v-model="scheduleForm.task"><SelectTrigger class="border-zinc-700 bg-zinc-900"><SelectValue /></SelectTrigger><SelectContent class="border-zinc-800 bg-zinc-900"><SelectItem v-for="task in tasks" :key="task.key" :value="task.key">{{ task.label }}</SelectItem></SelectContent></Select></div><div class="space-y-2"><Label>Периодичность</Label><Select v-model="scheduleForm.frequency"><SelectTrigger class="border-zinc-700 bg-zinc-900"><SelectValue /></SelectTrigger><SelectContent class="border-zinc-800 bg-zinc-900"><SelectItem value="15_minutes">Каждые 15 минут</SelectItem><SelectItem value="30_minutes">Каждые 30 минут</SelectItem><SelectItem value="hourly">Каждый час</SelectItem><SelectItem value="daily">Ежедневно</SelectItem></SelectContent></Select></div><div v-if="scheduleForm.frequency === 'daily'" class="space-y-2"><Label>Время запуска</Label><Input v-model="scheduleForm.run_at" type="time" class="border-zinc-700 bg-zinc-900" /></div><div v-else-if="selectedScheduleTask?.days" class="space-y-2"><Label>Период, дней</Label><Input v-model="scheduleForm.days" type="number" min="1" :max="selectedScheduleTask.max_days || 90" class="border-zinc-700 bg-zinc-900" /></div><Button type="submit" :disabled="scheduleForm.processing" class="bg-violet-600 text-white hover:bg-violet-500"><CalendarClock class="mr-2 h-4 w-4" />Добавить</Button></form></CardContent></Card>
                <Card class="border-zinc-800 text-zinc-100"><CardContent class="p-0"><Table><TableHeader><TableRow class="border-zinc-800"><TableHead>Операция</TableHead><TableHead>Периодичность</TableHead><TableHead>Следующий запуск</TableHead><TableHead>Состояние</TableHead><TableHead class="text-right">Действия</TableHead></TableRow></TableHeader><TableBody><TableRow v-for="schedule in schedules" :key="schedule.id" class="border-zinc-800/60"><TableCell class="font-medium text-zinc-200">{{ labelFor(schedule.task) }}</TableCell><TableCell>{{ frequencyLabels[schedule.frequency] }}<span v-if="schedule.frequency === 'daily'">, {{ schedule.run_at?.slice(0,5) }}</span></TableCell><TableCell>{{ dateTime(schedule.next_run_at) }}</TableCell><TableCell><span :class="schedule.is_enabled ? 'text-emerald-400' : 'text-zinc-500'">{{ schedule.is_enabled ? 'Включено' : 'Остановлено' }}</span></TableCell><TableCell class="text-right"><Button variant="ghost" size="sm" @click="toggleSchedule(schedule.id)">{{ schedule.is_enabled ? 'Остановить' : 'Включить' }}</Button><Button variant="ghost" size="sm" class="text-rose-400" @click="deleteSchedule(schedule.id)"><Trash2 class="h-4 w-4" /></Button></TableCell></TableRow><TableRow v-if="!schedules.length"><TableCell colspan="5" class="py-10 text-center text-zinc-500">Расписаний пока нет</TableCell></TableRow></TableBody></Table></CardContent></Card>
            </TabsContent>

            <TabsContent value="history" class="mt-5"><Card class="border-zinc-800 text-zinc-100"><CardContent class="p-0"><Table><TableHeader><TableRow class="border-zinc-800"><TableHead>Запуск</TableHead><TableHead>Операция</TableHead><TableHead>Статус</TableHead><TableHead>Начало</TableHead><TableHead>Длительность</TableHead><TableHead>Сообщение</TableHead></TableRow></TableHeader><TableBody><TableRow v-for="run in runs" :key="run.id" class="border-zinc-800/60"><TableCell class="text-zinc-500">#{{ run.id }}</TableCell><TableCell class="font-medium text-zinc-200">{{ labelFor(run.task) }}</TableCell><TableCell><span class="inline-flex rounded-full border px-2 py-1 text-xs" :class="statusClass(run.status)">{{ statusLabels[run.status] }}</span></TableCell><TableCell>{{ dateTime(run.started_at || run.created_at) }}</TableCell><TableCell>{{ duration(run) }}</TableCell><TableCell class="max-w-xs truncate text-xs" :class="run.error ? 'text-rose-400' : 'text-zinc-500'" :title="run.error || run.output">{{ run.error || run.output || '—' }}</TableCell></TableRow><TableRow v-if="!runs.length"><TableCell colspan="6" class="py-10 text-center text-zinc-500">Запусков пока нет</TableCell></TableRow></TableBody></Table></CardContent></Card></TabsContent>
        </Tabs>
    </AuthenticatedLayout>
</template>
