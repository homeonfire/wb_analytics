<template>
  <aside class="w-64 min-h-screen bg-zinc-950/80 backdrop-blur-xl border-r border-zinc-800/50 flex flex-col transition-all duration-300">
    <div class="p-6 border-b border-zinc-800/50 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg shadow-blue-500/20">
          <span class="font-bold text-white text-sm">WB</span>
        </div>
        <span class="font-bold text-lg text-zinc-100 tracking-tight">Analytics</span>
      </div>
    </div>
    
    <div class="p-4 border-b border-zinc-800/50">
      <div class="text-[10px] text-zinc-500 mb-2 uppercase font-bold tracking-wider">Текущий магазин</div>
      <Select :model-value="currentStoreId" @update:model-value="switchStore">
        <SelectTrigger class="w-full bg-zinc-900/50 border-zinc-700/50 hover:bg-zinc-800 transition-colors">
          <SelectValue placeholder="Выберите магазин" />
        </SelectTrigger>
        <SelectContent class="bg-zinc-900 border-zinc-800">
          <SelectItem v-for="store in stores" :key="store.id" :value="store.id.toString()" class="focus:bg-zinc-800 focus:text-white cursor-pointer">
            {{ store.name }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>

    <nav class="flex-1 p-4 overflow-y-auto space-y-6">
      <div v-for="group in navGroups" :key="group.title">
        <div class="text-[10px] text-zinc-500 mb-2 uppercase font-bold tracking-wider px-3">{{ group.title }}</div>
        <div class="space-y-1.5">
          <Link 
            v-for="item in group.items" 
            :key="item.name" 
            :href="route(item.route)"
            class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-all duration-200 group"
            :class="route().current(item.route) 
              ? 'bg-blue-500/10 text-blue-400' 
              : 'text-zinc-400 hover:bg-zinc-800/50 hover:text-zinc-200'"
          >
            <component 
              :is="item.icon" 
              class="w-5 h-5 transition-transform duration-200"
              :class="route().current(item.route) ? 'scale-110' : 'group-hover:scale-110'" 
            />
            <span class="font-medium text-sm">{{ item.name }}</span>
          </Link>
        </div>
      </div>
    </nav>
    
    <div class="p-4 border-t border-zinc-800/50 bg-zinc-950/50">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-9 h-9 rounded-full bg-zinc-800 flex items-center justify-center border border-zinc-700">
          <span class="text-xs text-zinc-400">{{ $page.props.auth.user.name.charAt(0) }}</span>
        </div>
        <div class="flex flex-col">
          <span class="text-sm font-medium text-zinc-200">{{ $page.props.auth.user.name }}</span>
          <span class="text-xs text-zinc-500 truncate w-32">{{ $page.props.auth.user.email }}</span>
        </div>
      </div>
      <div class="flex space-x-2">
        <Link :href="route('profile.edit')" class="flex-1 text-center py-2 text-xs font-medium text-zinc-400 hover:text-white hover:bg-zinc-800 rounded-md transition-colors">
          Профиль
        </Link>
        <Link :href="route('logout')" method="post" as="button" class="flex-1 text-center py-2 text-xs font-medium text-red-400 hover:text-red-300 hover:bg-red-500/10 rounded-md transition-colors">
          Выйти
        </Link>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import { 
  Select, 
  SelectContent, 
  SelectItem, 
  SelectTrigger, 
  SelectValue 
} from '@/Components/ui/select';
import { LayoutDashboard, Package, ShoppingCart, BarChart3, TrendingUp, Settings, Users, Store, MapPin, RefreshCw } from 'lucide-vue-next';

const page = usePage();
const stores = computed(() => page.props.auth.stores || []);
const currentStore = computed(() => page.props.auth.current_store || {});
const currentStoreId = computed(() => currentStore.value.id ? currentStore.value.id.toString() : '');

const navGroups = computed(() => {
  const groups = [
    {
      title: 'Основное',
      items: [
        { name: 'Дашборд', route: 'dashboard', icon: LayoutDashboard },
      ]
    },
    {
      title: 'Аналитика WB',
      items: [
        { name: 'Товары', route: 'products.index', icon: Package },
        { name: 'Заказы', route: 'orders.index', icon: ShoppingCart },
        { name: 'Логистика', route: 'logistics.index', icon: MapPin },
        { name: 'Аналитика', route: 'analytics.index', icon: BarChart3 },
        { name: 'Реклама', route: 'adverts.index', icon: TrendingUp },
      ]
    },
    {
      title: 'Управление',
      items: [
        { name: 'Магазины', route: 'stores.index', icon: Store },
        { name: 'Менеджеры', route: 'managers.index', icon: Users },
      ]
    }
  ];

  if (!page.props.auth.user.is_super_admin) {
    groups.splice(2, 1);
    const tools = [];
    if (page.props.auth.user.can_manage_plans) tools.push({ name: 'План-Факт', route: 'plans.index', icon: TrendingUp });
    if (page.props.auth.user.can_run_sync) tools.push({ name: 'Синхронизация', route: 'sync.index', icon: RefreshCw });
    if (tools.length) groups.push({ title: 'Инструменты', items: tools });
  }

  if (page.props.auth.user.is_super_admin) {
    groups.push({
      title: 'Админ',
      items: [
        { name: 'План-Факт', route: 'plans.index', icon: TrendingUp },
        { name: 'Синхронизация', route: 'sync.index', icon: RefreshCw }
      ]
    });
  }

  return groups;
});

const switchStore = (storeId) => {
  router.post(route('stores.switch'), { store_id: storeId }, {
    preserveState: false,
  });
};
</script>
