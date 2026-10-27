<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function renderPluginText(string $text): string
{
    $allowed = '<strong><b><em><i><u><ul><ol><li><p><br><h2><h3><blockquote>';
    if (strip_tags($text) === $text) {
        return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
    }

    return strip_tags($text, $allowed);
}

function renderPluginShortText(string $text): string
{
    $plain = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8')));
    if (function_exists('mb_substr')) {
        $plain = mb_strlen($plain) > 180 ? mb_substr($plain, 0, 180) . '...' : $plain;
    } else {
        $plain = strlen($plain) > 180 ? substr($plain, 0, 180) . '...' : $plain;
    }

    return htmlspecialchars($plain, ENT_QUOTES, 'UTF-8');
}

$plugins = [
    ['slug' => 'authme', 'name' => 'AuthMe', 'tag' => 'Авторизація', 'desc' => 'Захист акаунтів, безпечний вхід і перевірка логінів для гравців.', 'long' => 'AuthMe забезпечує захист облікових записів і просту систему входу. Це одна з базових складових безпеки сервера.'],
    ['slug' => 'axapi', 'name' => 'AxAPI', 'tag' => 'API', 'desc' => 'Бібліотека для розробки та інтеграції розширень і плагінів.', 'long' => 'AxAPI дає розробникам зручний інтерфейс для роботи з функціями сервера і сторонніми модулями.'],
    ['slug' => 'axgraves', 'name' => 'AxGraves', 'tag' => 'Відродження', 'desc' => 'Зберігання загублених предметів після смерті в безпечній системі.', 'long' => 'AxGraves дозволяє гравцям повертати втрачений інвентар і мінімізує втрати при смерті.'],
    ['slug' => 'bluemap', 'name' => 'BlueMap', 'tag' => 'Карта', 'desc' => 'Візуальна онлайн-карта світу для перегляду локацій і структури сервера.', 'long' => 'BlueMap створює інтерактивну карту світу, яку можна переглядати в браузері в реальному часі.'],
    ['slug' => 'bookshelf', 'name' => 'Bookshelf', 'tag' => 'Бібліотека', 'desc' => 'Спільна бібліотека для роботи десятків плагінів і модулів.', 'long' => 'Bookshelf забезпечує стабільну роботу інструментів, які використовують спільні API і сервіси.'],
    ['slug' => 'bstats', 'name' => 'bStats', 'tag' => 'Статистика', 'desc' => 'Система збору статистики сервера і активності гравців.', 'long' => 'bStats дозволяє відслідковувати показники активності, стабільність і загальну нагрузку сервера.'],
    ['slug' => 'chunky', 'name' => 'Chunky', 'tag' => 'Генерація', 'desc' => 'Попередня генерація чанків для швидкого старту та стабільної карти.', 'long' => 'Chunky дозволяє готувати світ до гри, щоб уникнути лагів і завантаження нових ділянок.'],
    ['slug' => 'citizens', 'name' => 'Citizens', 'tag' => 'NPC', 'desc' => 'Додавання NPC, квестових персонажів і інтерактивних об’єктів.', 'long' => 'Citizens дає змогу створювати NPC, які можна використовувати для квестів, магазину та розваг.'],
    ['slug' => 'coins', 'name' => 'Coins', 'tag' => 'Економіка', 'desc' => 'Робота з внутрішньою економікою, валютами та балансами.', 'long' => 'Coins дає гравцям зручну систему грошей і швидке керування валютними значеннями.'],
    ['slug' => 'craftbook', 'name' => 'CraftBook', 'tag' => 'Механіка', 'desc' => 'Кастомні механізми, блоки і логіка для сервера.', 'long' => 'CraftBook додає інтерактивні механізми і новий рівень реалізму в геймплей сервера.'],
    ['slug' => 'essentials', 'name' => 'Essentials', 'tag' => 'Утиліти', 'desc' => 'Базові команди, домівки, телепорти та зручна взаємодія з сервером.', 'long' => 'Essentials дає основну функціональність для гравців і адміністраторів — від команди home до warp-систем.'],
    ['slug' => 'fancyanalytics', 'name' => 'FancyAnalytics', 'tag' => 'Аналітика', 'desc' => 'Аналіз активності, статистики і поведінки гравців.', 'long' => 'FancyAnalytics показує, що саме відбувається на сервері, і допомагає керувати активністю.'],
    ['slug' => 'fancyholograms', 'name' => 'FancyHolograms', 'tag' => 'Голограми', 'desc' => 'Динамічні 3D-підписки, локації, інформаційні панелі.', 'long' => 'FancyHolograms допомагає оформляти сервер яскраво і сучасно за допомогою голографічних панелей.'],
    ['slug' => 'fastasyncworldedit', 'name' => 'FastAsyncWorldEdit', 'tag' => 'Мапінг', 'desc' => 'Швидке редагування карт, побудови та великі території.', 'long' => 'FastAsyncWorldEdit дозволяє будувати великі споруди за лічені хвилини без затримок.'],
    ['slug' => 'fishrewards', 'name' => 'FishRewards', 'tag' => 'Нагороди', 'desc' => 'Система нагород за риболовлю, активність і щоденні задачі.', 'long' => 'FishRewards інтегрує події, задачі та винагороди для активних гравців.'],
    ['slug' => 'floodgate', 'name' => 'Floodgate', 'tag' => 'Доступ', 'desc' => 'Підтримка Bedrock-гравців для Java-серверів.', 'long' => 'Floodgate забезпечує сумісність мобільних і Bedrock-гравців із Java-сервером.'],
    ['slug' => 'geyser-spigot', 'name' => 'Geyser-Spigot', 'tag' => 'Крос-платформа', 'desc' => 'Підключення гравців з інших платформ до одного сервера.', 'long' => 'Geyser-Spigot дозволяє об’єднати гравців із різних платформ під одним світом.'],
    ['slug' => 'gsit', 'name' => 'GSit', 'tag' => 'Інтерактивність', 'desc' => 'Сидіння в блоках, стільцях і спеціальних місцях.', 'long' => 'GSit додає зручну систему сидіння, відпочинку та інтерактивних об’єктів.'],
    ['slug' => 'imageframe', 'name' => 'ImageFrame', 'tag' => 'Декор', 'desc' => 'Відображення картинок і інтерфейсних елементів у світі.', 'long' => 'ImageFrame робить карту більш декоративною і привабливою для логотипів, банерів і реакцій.'],
    ['slug' => 'luckperms', 'name' => 'LuckPerms', 'tag' => 'Привілеї', 'desc' => 'Детальна система груп, привілеїв і доступу для гравців і модераторів.', 'long' => 'LuckPerms дає контроль над групами, правами, тимчасовими привілеями й бонусами.'],
    ['slug' => 'multiverse-core', 'name' => 'Multiverse-Core', 'tag' => 'Світ', 'desc' => 'Керування кількома світами і картами на одному сервері.', 'long' => 'Multiverse-Core дозволяє легко створювати окремі світи, мапи і переходи між ними.'],
    ['slug' => 'multiverse-portals', 'name' => 'Multiverse-Portals', 'tag' => 'Телепорти', 'desc' => 'Робота із порталами між різними світами та місцями.', 'long' => 'Multiverse-Portals створює зручну систему переходів між локаціями і картами.'],
    ['slug' => 'protocollib', 'name' => 'ProtocolLib', 'tag' => 'Сумісність', 'desc' => 'Обробка протоколів сервера для стабільної роботи плагінів.', 'long' => 'ProtocolLib дає можливість іншим плагінам працювати із пакетом даних і протоколами без конфліктів.'],
    ['slug' => 'spark', 'name' => 'spark', 'tag' => 'Оптимізація', 'desc' => 'Аналітика лагів, обмеження навантаження і діагностика сервера.', 'long' => 'spark показує, які процеси викликають лаги і де саме сервер переживає навантаження.'],
    ['slug' => 'towny', 'name' => 'Towny', 'tag' => 'Міста', 'desc' => 'Управління містами, захистом територій і економікою гільдій.', 'long' => 'Towny дозволяє будувати міста, захищати території та контролювати розвиток економіки.'],
    ['slug' => 'vault', 'name' => 'Vault', 'tag' => 'Економіка', 'desc' => 'Базова система грошових коштів для плагінів сервера.', 'long' => 'Vault є основою для внутрішньої економіки, магазину і різних інтерфейсів грошових систем.'],
    ['slug' => 'viabackwards', 'name' => 'ViaBackwards', 'tag' => 'Сумісність', 'desc' => 'Підтримка старих клієнтів з новим сервером.', 'long' => 'ViaBackwards забезпечує сумісність між старими версіями клієнта та більш актуальним сервером.'],
    ['slug' => 'viarewind', 'name' => 'ViaRewind', 'tag' => 'Сумісність', 'desc' => 'Підтримка старих версій для стабільного входу гравців.', 'long' => 'ViaRewind дозволяє старим клієнтам залишатися на сервері без конфліктів у протоколах.'],
    ['slug' => 'viaversion', 'name' => 'ViaVersion', 'tag' => 'Сумісність', 'desc' => 'Підтримка різних версій Minecraft на одному сервері.', 'long' => 'ViaVersion допомагає об’єднати клієнтів різних версій у спільну гру.'],
    ['slug' => 'worldedit', 'name' => 'WorldEdit', 'tag' => 'Мапінг', 'desc' => 'Зручне редагування карт, будівництво та швидка генерація ландшафту.', 'long' => 'WorldEdit є незамінним для будівельників і адмінів, які швидко збирають споруди і території.'],
    ['slug' => 'worldeditselectionvisualizer', 'name' => 'WorldEditSelectionVisualizer', 'tag' => 'Мапінг', 'desc' => 'Візуальний показ виділеної області та інструментів редагування.', 'long' => 'Цей плагін робить процес роботи з WorldEdit наочнішим і зручнішим для адмінів.'],
    ['slug' => 'worldguard', 'name' => 'WorldGuard', 'tag' => 'Захист', 'desc' => 'Управління зонами, обмеженнями і захистом локацій.', 'long' => 'WorldGuard контролює доступ до територій, допомагає захищати побудови і управління зонами.'],
    ['slug' => 'worldborder', 'name' => 'WorldBorder', 'tag' => 'Ліміт світу', 'desc' => 'Обмеження межі світу і контроль території сервера.', 'long' => 'WorldBorder допомагає задавати межі карти й обмежувати віддалені польоти або переміщення.'],
    ['slug' => 'coreprotect', 'name' => 'CoreProtect', 'tag' => 'Безпека', 'desc' => 'Логи блоків, відстеження змін і швидке відновлення структури.', 'long' => 'CoreProtect дозволяє дивитися історію змін і оперативно відновлювати втрати на карті.'],
    ['slug' => 'essentialsx', 'name' => 'EssentialsX', 'tag' => 'Утиліти', 'desc' => 'Розширені команди, економіка, дома, варпи і зручна взаємодія.', 'long' => 'EssentialsX додає стабільну основу для серверних комунікацій, навігації та геймплею.'],
    ['slug' => 'advancedteleport', 'name' => 'AdvancedTeleport', 'tag' => 'Телепорти', 'desc' => 'Зручна система телепортів і мітів між локаціями.', 'long' => 'AdvancedTeleport спрощує пересування між точками, домами, варпами і спеціальними зонами.'],
    ['slug' => 'shopgui', 'name' => 'ShopGUI+', 'tag' => 'Магазин', 'desc' => 'Інтерактивний інтерфейс для покупок, продажів і внутрішньої торгівлі.', 'long' => 'ShopGUI+ робить інтерфейс магазину простим, зрозумілим і зручним для гравців.'],
    ['slug' => 'clearlag', 'name' => 'ClearLag', 'tag' => 'Оптимізація', 'desc' => 'Очищення зайвих сутностей для стабільної роботи сервера.', 'long' => 'ClearLag допомагає обходити проблеми з лагами без ручного втручання.'],
    ['slug' => 'geyser', 'name' => 'Geyser', 'tag' => 'Bedrock', 'desc' => 'Підтримка Bedrock-гравців і крос-платформених гравців.', 'long' => 'Geyser дозволяє гравцям із мобільних пристроїв і інших платформ грати разом з Java-гравцями.']
];

$storedPluginsPath = __DIR__ . '/data/plugins.json';
if (is_file($storedPluginsPath)) {
    $storedPlugins = json_decode((string)file_get_contents($storedPluginsPath), true);
    if (is_array($storedPlugins)) {
        $plugins = $storedPlugins;
    }
}

$pluginBySlug = [];
foreach ($plugins as $plugin) {
    $pluginBySlug[$plugin['slug']] = $plugin;
}

$pluginCommands = [
    'authme' => [
        ['command' => '/login', 'description' => 'Вхід до облікового запису та перевірка пароля.'],
        ['command' => '/register', 'description' => 'Створення нового облікового запису.'],
        ['command' => '/logout', 'description' => 'Вихід із сервера або облікового запису.'],
        ['command' => '/changepassword', 'description' => 'Зміна пароля для профілю.'],
        ['command' => '/unregister', 'description' => 'Видалення облікового запису.'],
        ['command' => '/email', 'description' => 'Прив’язка або перевірка електронної пошти.'],
    ],
    'axapi' => [
        ['command' => '/axapi', 'description' => 'Основне меню API-плагіна.'],
        ['command' => '/axapi reload', 'description' => 'Перезавантаження підключених модулів і налаштувань.'],
        ['command' => '/axapi version', 'description' => 'Перевірка поточної версії API.'],
        ['command' => '/axapi help', 'description' => 'Список доступних команд.'],
        ['command' => '/axapi info', 'description' => 'Інформація про стан підключень.'],
    ],
    'axgraves' => [
        ['command' => '/grave', 'description' => 'Перевірка останньої могили або втраченої інвентарної сесії.'],
        ['command' => '/graves', 'description' => 'Відображення всіх могил гравця.'],
        ['command' => '/grave list', 'description' => 'Показує список доступних могил.'],
        ['command' => '/grave clear', 'description' => 'Видалення записів про загублені предмети.'],
        ['command' => '/grave restore', 'description' => 'Повернення втраченої речі з могили.'],
    ],
    'bluemap' => [
        ['command' => '/bluemap', 'description' => 'Основна команда інформації про карту сервера.'],
        ['command' => '/bluemap update', 'description' => 'Оновлення картографічних даних.'],
        ['command' => '/bluemap reload', 'description' => 'Перезавантаження карти без рестарту сервера.'],
        ['command' => '/bluemap hide', 'description' => 'Приховує карту для обраного гравця.'],
    ],
    'bookshelf' => [
        ['command' => '/bookshelf', 'description' => 'Інформація про основний стан бібліотеки.'],
        ['command' => '/bookshelf reload', 'description' => 'Перезавантажує плагін і конфігурацію.'],
        ['command' => '/bookshelf info', 'description' => 'Показує поточний стан підключення модулів.'],
        ['command' => '/bookshelf version', 'description' => 'Версія бібліотеки та її модулів.'],
    ],
    'bstats' => [
        ['command' => '/bstats', 'description' => 'Показує основну статистику сервера.'],
        ['command' => '/bstats reload', 'description' => 'Перезавантаження налаштувань статистики.'],
        ['command' => '/bstats metrics', 'description' => 'Показує детальні показники активності.'],
        ['command' => '/bstats help', 'description' => 'Відображає доступні команди підсистеми.'],
    ],
    'chunky' => [
        ['command' => '/chunky start', 'description' => 'Запускає генерацію чанків для світу.'],
        ['command' => '/chunky pause', 'description' => 'Призупиняє генерацію.'],
        ['command' => '/chunky cancel', 'description' => 'Скасовує поточний процес.'],
        ['command' => '/chunky world', 'description' => 'Вибирає світ для генерації.'],
        ['command' => '/chunky center', 'description' => 'Задає центр генерації.'],
    ],
    'citizens' => [
        ['command' => '/npc create', 'description' => 'Створення нового NPC.'],
        ['command' => '/npc remove', 'description' => 'Видалення персонажа.'],
        ['command' => '/npc spawn', 'description' => 'Поява NPC у певній точці.'],
        ['command' => '/npc list', 'description' => 'Перелік усіх NPC на сервері.'],
        ['command' => '/npc sell', 'description' => 'Встановлення продажу або параметрів NPC.'],
    ],
    'coins' => [
        ['command' => '/bal', 'description' => 'Перевірка поточного балансу.'],
        ['command' => '/pay', 'description' => 'Переказ коштів іншому гравцю.'],
        ['command' => '/eco', 'description' => 'Основне керування економікою.'],
        ['command' => '/coins', 'description' => 'Показує інформацію про внутрішню валюту.'],
        ['command' => '/setbal', 'description' => 'Встановлення балансу для гравця.'],
    ],
    'craftbook' => [
        ['command' => '/cb', 'description' => 'Основна команда для роботи з CraftBook.'],
        ['command' => '/cb reload', 'description' => 'Перезавантаження налаштувань механік.'],
        ['command' => '/cb info', 'description' => 'Інформація про модуль і стан роботи.'],
        ['command' => '/craftbook', 'description' => 'Показує доступні налаштування та об’єкти.'],
    ],
    'essentials' => [
        ['command' => '/home', 'description' => 'Телепортує до власного дому.'],
        ['command' => '/sethome', 'description' => 'Зберігає точку дому.'],
        ['command' => '/warp', 'description' => 'Телепортує до точки варпа.'],
        ['command' => '/spawn', 'description' => 'Повертає до точки появи.'],
        ['command' => '/kit', 'description' => 'Видає набори предметів.'],
        ['command' => '/nick', 'description' => 'Зміна ніку гравця.'],
        ['command' => '/mail', 'description' => 'Пошта або внутрішні повідомлення.'],
    ],
    'fancyanalytics' => [
        ['command' => '/fa', 'description' => 'Головна команда аналітики.'],
        ['command' => '/fa stats', 'description' => 'Показує поточну статистику сервера.'],
        ['command' => '/fa reset', 'description' => 'Очищення або скидання даних аналітики.'],
        ['command' => '/fa top', 'description' => 'Рейтинг активності гравців.'],
    ],
    'fancyholograms' => [
        ['command' => '/holo create', 'description' => 'Створення нової голограми.'],
        ['command' => '/holo remove', 'description' => 'Видалення голограми.'],
        ['command' => '/holo list', 'description' => 'Перелік активних голограм.'],
        ['command' => '/holo edit', 'description' => 'Редагування тексту та стилю.'],
        ['command' => '/holo move', 'description' => 'Переміщення голограми.'],
    ],
    'fastasyncworldedit' => [
        ['command' => '/wand', 'description' => 'Видає інструмент для виділення області.'],
        ['command' => '/pos1', 'description' => 'Встановлює першу точку виділення.'],
        ['command' => '/pos2', 'description' => 'Встановлює другу точку виділення.'],
        ['command' => '/brush', 'description' => 'Дозволяє працювати кистями для редагування.'],
        ['command' => '/set', 'description' => 'Заповнює область обраним блоком.'],
        ['command' => '/undo', 'description' => 'Відміняє останню дію.'],
        ['command' => '/replace', 'description' => 'Заміняє один блок на інший.'],
    ],
    'fishrewards' => [
        ['command' => '/fish', 'description' => 'Основна команда риболовлі.'],
        ['command' => '/fish rewards', 'description' => 'Показує доступні нагороди.'],
        ['command' => '/fish top', 'description' => 'Рейтинг найкращих рибалок.'],
        ['command' => '/fish claim', 'description' => 'Отримання нагороди.'],
        ['command' => '/fish reset', 'description' => 'Скидання першості або прогресу.'],
    ],
    'floodgate' => [
        ['command' => '/floodgate', 'description' => 'Основна інформація про Bedrock-підтримку.'],
        ['command' => '/floodgate link', 'description' => 'Зв’язування облікового запису Bedrock.'],
        ['command' => '/floodgate info', 'description' => 'Показує статус підключення.'],
        ['command' => '/floodgate reload', 'description' => 'Перезавантаження налаштувань.'],
    ],
    'geyser-spigot' => [
        ['command' => '/geyser', 'description' => 'Основна інформація про Geyser.'],
        ['command' => '/geyser version', 'description' => 'Перевірка поточної версії.'],
        ['command' => '/geyser reload', 'description' => 'Перезавантаження модуля.'],
        ['command' => '/geyser help', 'description' => 'Список доступних підкоманд.'],
    ],
    'gsit' => [
        ['command' => '/sit', 'description' => 'Сидіння на землі або блоках.'],
        ['command' => '/lay', 'description' => 'Лежання для відпочинку.'],
        ['command' => '/crawl', 'description' => 'Перехід у стан повзання.'],
        ['command' => '/stand', 'description' => 'Підйом із сидячого або лежачого стану.'],
        ['command' => '/gsit', 'description' => 'Основне меню системи сидіння.'],
    ],
    'imageframe' => [
        ['command' => '/imageframe', 'description' => 'Основна команда для роботи з рамками.'],
        ['command' => '/imageframe set', 'description' => 'Встановлення зображення у рамку.'],
        ['command' => '/imageframe remove', 'description' => 'Видалення зображення.'],
        ['command' => '/imageframe list', 'description' => 'Перелік зображень на сервері.'],
    ],
    'luckperms' => [
        ['command' => '/lp', 'description' => 'Основна команда для налаштування прав.'],
        ['command' => '/lp user', 'description' => 'Керування правами конкретного гравця.'],
        ['command' => '/lp group', 'description' => 'Керування групами привілеїв.'],
        ['command' => '/perm', 'description' => 'Швидка перевірка доступу.'],
        ['command' => '/luckperms verbose', 'description' => 'Показує дані перевірки прав.'],
        ['command' => '/lp editor', 'description' => 'Відкриває веб-редактор привілеїв.'],
    ],
    'multiverse-core' => [
        ['command' => '/mv', 'description' => 'Основне меню мультисвітів.'],
        ['command' => '/mv create', 'description' => 'Створення нового світу.'],
        ['command' => '/mv list', 'description' => 'Перелік доступних світів.'],
        ['command' => '/mv tp', 'description' => 'Телепортація між світами.'],
        ['command' => '/mv delete', 'description' => 'Видалення світу.'],
        ['command' => '/mv import', 'description' => 'Імпорт карти як світу.'],
    ],
    'multiverse-portals' => [
        ['command' => '/mvp', 'description' => 'Основне меню порталів.'],
        ['command' => '/mvp create', 'description' => 'Створення нового порталу.'],
        ['command' => '/mvp list', 'description' => 'Показує всі існуючі портали.'],
        ['command' => '/mvp tp', 'description' => 'Телепортація через портал.'],
        ['command' => '/mvp delete', 'description' => 'Видалення порталу.'],
    ],
    'protocollib' => [
        ['command' => '/protocollib', 'description' => 'Показує стан системи протоколів.'],
        ['command' => '/pl reload', 'description' => 'Перезавантажує плагін і підключення.'],
        ['command' => '/pl version', 'description' => 'Перевіряє версію ProtocolLib.'],
    ],
    'spark' => [
        ['command' => '/spark', 'description' => 'Основна команда Spark.'],
        ['command' => '/spark profiler', 'description' => 'Профілювання продуктивності сервера.'],
        ['command' => '/spark tps', 'description' => 'Показує поточний TPS.'],
        ['command' => '/spark heap', 'description' => 'Показує використання оперативної пам’яті.'],
        ['command' => '/spark health', 'description' => 'Показує стан сервера і навантаження.'],
    ],
    'towny' => [
        ['command' => '/town', 'description' => 'Керування містом і його територією.'],
        ['command' => '/nation', 'description' => 'Керування нацією або союзом міст.'],
        ['command' => '/res', 'description' => 'Показує ресурси та власність території.'],
        ['command' => '/towny', 'description' => 'Основне меню Towny.'],
        ['command' => '/town claim', 'description' => 'Захоплення нової території для міста.'],
        ['command' => '/town delete', 'description' => 'Видалення міста.'],
        ['command' => '/town invite', 'description' => 'Запрошення гравця в місто.'],
        ['command' => '/nation create', 'description' => 'Створення нової нації.'],
    ],
    'vault' => [
        ['command' => '/eco', 'description' => 'Основна економічна команда сервера.'],
        ['command' => '/eco give', 'description' => 'Додає гроші гравцю.'],
        ['command' => '/eco take', 'description' => 'Віднімає гроші у гравця.'],
        ['command' => '/bal', 'description' => 'Перевірка поточного балансу.'],
        ['command' => '/pay', 'description' => 'Передача грошей іншим гравцям.'],
        ['command' => '/setbalance', 'description' => 'Встановлення зарплати або балансу вручну.'],
    ],
    'viabackwards' => [
        ['command' => '/viabackwards', 'description' => 'Показує статус підтримки старих клієнтів.'],
        ['command' => '/vb', 'description' => 'Швидкий доступ до інформації про сумісність.'],
        ['command' => '/version', 'description' => 'Перевіряє версію клієнта і сервера.'],
    ],
    'viarewind' => [
        ['command' => '/viarewind', 'description' => 'Інформація про підримку старих версій.'],
        ['command' => '/rewind', 'description' => 'Коротка перевірка сумісності.'],
        ['command' => '/version', 'description' => 'Показує поточну версію.'],
    ],
    'viaversion' => [
        ['command' => '/viaversion', 'description' => 'Основна інформація про версійну сумісність.'],
        ['command' => '/version', 'description' => 'Показує поточну версію сервера й клієнта.'],
        ['command' => '/viaversion reload', 'description' => 'Перезавантажує конфігурацію сумісності.'],
    ],
    'worldedit' => [
        ['command' => '/wand', 'description' => 'Видає інструмент для виділення області.'],
        ['command' => '/pos1', 'description' => 'Встановлює першу межу регіону.'],
        ['command' => '/pos2', 'description' => 'Встановлює другу межу регіону.'],
        ['command' => '/set', 'description' => 'Заповнює обрану область блоками.'],
        ['command' => '/undo', 'description' => 'Відміняє останню дію.'],
        ['command' => '/replace', 'description' => 'Заміняє блоки в області.'],
        ['command' => '/copy', 'description' => 'Копіює обрану область.'],
    ],
    'worldeditselectionvisualizer' => [
        ['command' => '/wes', 'description' => 'Основна команда візуалізації виділеної області.'],
        ['command' => '/we vis', 'description' => 'Показує вибрану область в графічному режимі.'],
        ['command' => '/we visualize', 'description' => 'Включає підсвічування region-області.'],
        ['command' => '/we toggle', 'description' => 'Перемикає режим візуалізації.'],
    ],
    'worldguard' => [
        ['command' => '/region', 'description' => 'Основна команда роботи з регіонами.'],
        ['command' => '/region define', 'description' => 'Створення нового захищеного регіону.'],
        ['command' => '/region protect', 'description' => 'Захист території від змін.'],
        ['command' => '/region list', 'description' => 'Показує всі регіони сервера.'],
        ['command' => '/rg', 'description' => 'Швидкий доступ до керування регіонами.'],
        ['command' => '/region remove', 'description' => 'Видалення регіону.'],
    ],
    'worldborder' => [
        ['command' => '/wb', 'description' => 'Основна команда роботи з межею світу.'],
        ['command' => '/wb shape', 'description' => 'Задає форму межі світу.'],
        ['command' => '/wb set', 'description' => 'Встановлює нову межу карти.'],
        ['command' => '/wb fill', 'description' => 'Заповнює область або межу.'],
        ['command' => '/wb help', 'description' => 'Показує доступні параметри межі.'],
    ],
    'coreprotect' => [
        ['command' => '/co', 'description' => 'Основна команда CoreProtect.'],
        ['command' => '/co lookup', 'description' => 'Шукає історію змін на території або біля блока.'],
        ['command' => '/co rollback', 'description' => 'Відновлює попередній стан світу.'],
        ['command' => '/co inspect', 'description' => 'Детальна перевірка змін та подій.'],
        ['command' => '/co near', 'description' => 'Показує останні зміни навколо точки.'],
    ],
    'essentialsx' => [
        ['command' => '/home', 'description' => 'Телепортує до особистого дому.'],
        ['command' => '/sethome', 'description' => 'Зберігає точку дому.'],
        ['command' => '/warp', 'description' => 'Телепортує до варпа.'],
        ['command' => '/spawn', 'description' => 'Повертає до точки появи.'],
        ['command' => '/eco', 'description' => 'Керування внутрішньою економікою.'],
        ['command' => '/money', 'description' => 'Перевірка або зміна балансу.'],
        ['command' => '/kits', 'description' => 'Список і видача наборів предметів.'],
    ],
    'advancedteleport' => [
        ['command' => '/tpa', 'description' => 'Запрос відправити гравця до іншого гравця.'],
        ['command' => '/tpahere', 'description' => 'Запрос гравця прийти до себе.'],
        ['command' => '/home', 'description' => 'Телепортування до дому.'],
        ['command' => '/spawn', 'description' => 'Телепортація до точки появи.'],
        ['command' => '/tpaccept', 'description' => 'Прийняття запиту на телепортацію.'],
        ['command' => '/tpdeny', 'description' => 'Відхилення запиту на телепортацію.'],
    ],
    'shopgui' => [
        ['command' => '/shop', 'description' => 'Відкриває ігровий магазин.'],
        ['command' => '/buy', 'description' => 'Покупка предметів або привілеїв.'],
        ['command' => '/sell', 'description' => 'Продаж предметів.'],
        ['command' => '/shop reload', 'description' => 'Перезавантаження магазинів і розділів.'],
    ],
    'clearlag' => [
        ['command' => '/clearlag', 'description' => 'Основна інформація про очистку світу.'],
        ['command' => '/lag', 'description' => 'Перевірка навантаження сервера.'],
        ['command' => '/clearlag check', 'description' => 'Показує стан системи очищення.'],
        ['command' => '/clearlag reload', 'description' => 'Перезавантажує параметри очищення.'],
    ],
    'geyser' => [
        ['command' => '/geyser', 'description' => 'Основна інформація про Bedrock-підтримку.'],
        ['command' => '/geyser version', 'description' => 'Показує поточну версію.'],
        ['command' => '/geyser reload', 'description' => 'Перезавантажує модуль Bedrock.'],
        ['command' => '/geyser help', 'description' => 'Список доступних команд.'],
        ['command' => '/geyser stats', 'description' => 'Показує статистику підключень.'],
    ],
];

$selectedSlug = isset($_GET['plugin']) ? $_GET['plugin'] : null;
$selectedPlugin = $selectedSlug && isset($pluginBySlug[$selectedSlug]) ? $pluginBySlug[$selectedSlug] : null;

$pluginQuery = trim((string)($_GET['q'] ?? ''));
$filteredPlugins = array_values(array_filter($plugins, static function ($plugin) use ($pluginQuery) {
    $haystack = strtolower($plugin['name'] . ' ' . $plugin['tag'] . ' ' . strip_tags($plugin['desc']));
    $matchesQuery = $pluginQuery === '' || strpos($haystack, strtolower($pluginQuery)) !== false;
    return $matchesQuery;
}));

$perPage = 12;
$totalPlugins = count($filteredPlugins);
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageCount = max(1, (int)ceil($totalPlugins / $perPage));
if ($currentPage > $pageCount) {
    $currentPage = $pageCount;
}
$offset = ($currentPage - 1) * $perPage;
$visiblePlugins = array_slice($filteredPlugins, $offset, $perPage);


include 'header.php';
echo "<link rel='stylesheet' href='/news/style-news.css'>";
?>

<?php if ($selectedPlugin): ?>
    <?php $commands = $pluginCommands[$selectedPlugin['slug']] ?? []; ?>
    <main class="plugin-detail-page">
        <a class="plugin-detail-back" href="/plugins.php">← Повернутися до всіх плагінів</a>
        <article class="plugin-detail-card">
            <header class="plugin-detail-hero">
                <div class="plugin-detail-icon" aria-hidden="true">✦</div>
                <div class="plugin-detail-heading">
                    <span class="plugin-detail-kicker">Плагіни</span>
                    <h1><?= htmlspecialchars($selectedPlugin['name']) ?></h1>
                    <p>Плагін для Minecraft-сервера · офіційна сторінка aTcraft</p>
                </div>
                <div class="plugin-detail-status"><span></span> Підтримується</div>
            </header>
            <div class="plugin-detail-layout">
                <section class="plugin-detail-main">
                    <h2>Про плагін</h2>
                    <div class="plugin-detail-content">
                        <?= renderPluginText((string)$selectedPlugin['long']) ?>
                    </div>
                    <?php if ($commands): ?>
                        <h2 class="plugin-commands-title">Основні команди</h2>
                        <div class="plugin-commands">
                            <?php foreach ($commands as $command): ?>
                                <div class="plugin-command"><code><?= htmlspecialchars($command['command']) ?></code><span><?= htmlspecialchars($command['description']) ?></span></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
                <aside class="plugin-detail-sidebar">
                    <div class="plugin-fact"><span>Категорія</span><strong>Плагіни</strong></div>
                    <div class="plugin-fact"><span>Платформа</span><strong>Paper / Spigot</strong></div>
                    <div class="plugin-fact"><span>Сумісність</span><strong>Minecraft Java</strong></div>
                    <div class="plugin-tip">Потрібна допомога з налаштуванням? Зверніться до адміністрації сервера.</div>
                </aside>
            </div>
        </article>
    </main>
<?php else: ?>
    <main class="content-shell">
        <div class="section-head simple-head">
            <h2>Плагіни сервера</h2>
        </div>

        <section class="plugin-showcase" aria-label="Плагіни сервера">
            <div class="plugin-showcase-glow"></div>
            <div class="plugin-showcase-copy">
                <span class="plugin-showcase-kicker">aTcraft • серверна система</span>
                <h1>Все необхідне для живого світу</h1>
                <p>Стабільна економіка, захист територій, міста, зручні команди та інструменти для комфортної гри.</p>
                <div class="plugin-showcase-tags">
                    <span>Економіка</span><span>Міста</span><span>Захист</span><span>Команди</span>
                </div>
                <a class="primary-btn" href="/">Повернутися на сервер</a>
            </div>
            <div class="plugin-showcase-art" aria-hidden="true">
                <div class="plugin-orbit plugin-orbit-one"></div>
                <div class="plugin-orbit plugin-orbit-two"></div>
                <div class="plugin-core">✦</div>
                <span class="plugin-node plugin-node-one">⚙</span>
                <span class="plugin-node plugin-node-two">◆</span>
                <span class="plugin-node plugin-node-three">⌘</span>
            </div>
        </section>

        <div class="plugin-grid">
            <?php foreach ($visiblePlugins as $plugin): ?>
                <article class="plugin-card card-with-delete">
                    <?php if (!empty($_SESSION['admin_logged_in'])): ?>
                        <div class="card-admin-actions">
                            <a href="/admin.php?edit_plugin_slug=<?= urlencode((string)($plugin['slug'] ?? '')) ?>&return_to=%2Fplugins.php" class="edit-card-btn" aria-label="Редагувати плагін">Редагувати</a>
                            <form method="post" action="/admin.php" class="card-delete-form">
                                <input type="hidden" name="redirect_to" value="/plugins.php">
                                <input type="hidden" name="plugin_action" value="delete">
                                <input type="hidden" name="plugin_slug" value="<?= htmlspecialchars($plugin['slug'] ?? '') ?>">
                                <button type="submit" class="delete-card-btn" aria-label="Видалити плагін">×</button>
                            </form>
                        </div>
                    <?php endif; ?>
                    <div class="plugin-badge">Plugin</div>
                    <h3><?= htmlspecialchars($plugin['name']) ?></h3>
                    <div><?= renderPluginShortText((string)$plugin['desc']) ?></div>
                    <div class="plugin-meta">
                        <a class="mini-btn plugin-button" href="/plugins.php?plugin=<?= htmlspecialchars($plugin['slug']) ?>">Детальніше</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPlugins > $perPage): ?>
            <div class="plugin-pagination" aria-label="Пагінація плагінів">
                <div class="pagination-label">
                    Показано <?= min($offset + 1, $totalPlugins) ?> – <?= min($offset + $perPage, $totalPlugins) ?> з <?= $totalPlugins ?> робіт
                </div>
                <div class="pagination-controls">
                    <?php for ($i = 1; $i <= $pageCount; $i++): ?>
                        <a class="page-btn <?= $i === $currentPage ? 'active' : '' ?>" href="?page=<?= $i ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($currentPage < $pageCount): ?>
                        <a class="page-btn page-next" href="?page=<?= $currentPage + 1 ?>">Вперед →</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
<?php endif; ?>

<style>
    .plugin-detail-page {
        width: min(1120px, calc(100% - 40px));
        margin: 34px auto 90px;
    }
    .plugin-detail-back {
        display: inline-flex;
        margin-top: 30px;
        margin-bottom: 18px;
        color: #5c7896;
        font-weight: 700;
        text-decoration: none;
    }
    .plugin-detail-back:hover { color: #159447; }
    .plugin-detail-card {
        overflow: hidden;
        border: 1px solid #dce5ec;
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 20px 50px rgba(42, 69, 91, .10);
    }
    .plugin-detail-hero {
        position: relative;
        width: auto;
        min-height: 152px;
        box-sizing: border-box;
        top: auto;
        right: auto;
        left: auto;
        z-index: auto;
        display: flex;
        align-items: center;
        gap: 22px;
        padding: 38px 40px;
        color: #fff;
        background: radial-gradient(circle at 85% 10%, rgba(62, 220, 126, .25), transparent 30%), linear-gradient(135deg, #102b49, #1b5161);
    }
    .plugin-detail-icon {
        display: grid;
        flex: 0 0 74px;
        height: 74px;
        place-items: center;
        border: 1px solid rgba(255,255,255,.25);
        border-radius: 20px;
        background: rgba(255,255,255,.12);
        color: #7ef0a2;
        font-size: 36px;
    }
    .plugin-detail-kicker { color: #7ef0a2; font-size: 12px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .plugin-detail-heading h1 { margin: 5px 0 4px; color: #fff; font-size: clamp(2rem, 4vw, 3.4rem); line-height: 1; }
    .plugin-detail-heading p { margin: 0; color: rgba(255,255,255,.72); }
    .plugin-detail-status { margin-left: auto; padding: 9px 13px; border: 1px solid rgba(126,240,162,.35); border-radius: 999px; color: #b9ffd0; font-size: 13px; white-space: nowrap; }
    .plugin-detail-status span { display: inline-block; width: 7px; height: 7px; margin-right: 5px; border-radius: 50%; background: #63e58b; }
    .plugin-detail-layout { display: grid; grid-template-columns: minmax(0, 1fr) 280px; gap: 42px; padding: 38px 40px 44px; }
    .plugin-detail-main h2 { margin: 0 0 16px; color: #17334c; font-size: 24px; }
    .plugin-detail-content { color: #46596b; font-size: 17px; line-height: 1.8; }
    .plugin-detail-content p { margin: 0 0 18px; }
    .plugin-detail-sidebar { align-self: start; padding: 20px; border: 1px solid #e1e9ee; border-radius: 16px; background: #f6fafc; }
    .plugin-fact { display: flex; flex-direction: column; gap: 4px; padding: 0 0 15px; margin-bottom: 15px; border-bottom: 1px solid #e2eaf0; }
    .plugin-fact span { color: #7890a3; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
    .plugin-fact strong { color: #17334c; }
    .plugin-tip { color: #63798b; font-size: 13px; line-height: 1.6; }
    .plugin-commands-title { margin-top: 34px !important; }
    .plugin-commands { display: grid; gap: 9px; }
    .plugin-command { display: flex; align-items: center; gap: 16px; padding: 12px 14px; border: 1px solid #e4ebef; border-radius: 10px; background: #fbfdfe; }
    .plugin-command code { min-width: 145px; color: #168b4a; font-weight: 800; }
    .plugin-command span { color: #718494; font-size: 14px; }
    @media (max-width: 720px) {
        .plugin-detail-page { width: min(100% - 24px, 1120px); margin-top: 20px; }
        .plugin-detail-hero { align-items: flex-start; flex-wrap: wrap; padding: 26px 22px; }
        .plugin-detail-status { margin-left: 0; }
        .plugin-detail-layout { grid-template-columns: 1fr; gap: 26px; padding: 26px 22px 30px; }
        .plugin-command { align-items: flex-start; flex-direction: column; gap: 5px; }
        .plugin-command code { min-width: 0; }
    }
    .card-with-delete {
        position: relative;
    }
    .card-admin-actions {
        position: absolute;
        top: 12px;
        right: 12px;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .edit-card-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 28px;
        padding: 0 10px;
        border-radius: 999px;
        border: none;
        background: #22c55e;
        color: #052e16;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
        box-shadow: 0 4px 10px rgba(34, 197, 94, 0.25);
    }
    .card-delete-form {
        position: static;
        margin: 0;
    }
    .delete-card-btn {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: none;
        background: #ef4444;
        color: #fff;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }
    .delete-card-btn:hover,
    .edit-card-btn:hover {
        transform: scale(1.04);
    }
</style>

<?php include 'footer.php'; ?>
