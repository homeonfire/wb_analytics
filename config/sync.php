<?php

return [
    'tasks' => [
        'products' => ['label' => 'Товары и размеры', 'command' => 'wb:sync-products', 'description' => 'Карточки товаров и SKU'],
        'prices' => ['label' => 'Цены и скидки', 'command' => 'wb:sync-data', 'description' => 'Текущие цены и скидки'],
        'orders' => ['label' => 'Заказы', 'command' => 'wb:sync-orders', 'description' => 'Новые и изменённые заказы', 'days' => true],
        'sales' => ['label' => 'Продажи', 'command' => 'wb:sync-sales', 'description' => 'Продажи и возвраты', 'days' => true],
        'analytics' => ['label' => 'Воронка продаж', 'command' => 'wb:sync-analytics', 'description' => 'Аналитика карточек (до 7 дней)', 'days' => true, 'max_days' => 7],
        'stocks' => ['label' => 'Остатки', 'command' => 'wb:sync-stocks', 'description' => 'Остатки по складам'],
        'adverts' => ['label' => 'Рекламные кампании', 'command' => 'wb:sync-adverts', 'description' => 'Список и состояние кампаний'],
        'advert_stats' => ['label' => 'Статистика рекламы', 'command' => 'wb:sync-advert-stats', 'description' => 'Статистика рекламных кампаний', 'days' => true],
        'abc' => ['label' => 'ABC-анализ', 'command' => 'wb:calculate-abc', 'description' => 'Пересчёт классов по выручке'],
        'full' => ['label' => 'Полная синхронизация', 'description' => 'Все операции в безопасной последовательности'],
    ],
    'full_sequence' => ['products', 'prices', 'orders', 'sales', 'analytics', 'stocks', 'adverts', 'advert_stats', 'abc'],
];
