<?php
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header('Location: /');
    exit;
}

if (!empty($_POST['login'])) {
    $loginUsername = $_POST['login_username'] ?? '';
    $loginPassword = $_POST['login_password'] ?? '';

    if ($loginUsername === 'admin' && $loginPassword !== '' && hash_equals((string)(getenv('ATCRAFT_ADMIN_PASSWORD') ?: ''), $loginPassword)) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: /');
        exit;
    }

    $_SESSION['login_error'] = 'Невірний логін або пароль';
    header('Location: /#admin-login');
    exit;
}

$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

$news = [
    [
        'title' => 'Нова карта та оновлений респавн',
        'date' => '14 серпня 2026',
        'excerpt' => 'Після масштабного апдейту з’явилися нові локації, фінальна зона для крафту та оновлені точки респавну.',
        'image' => '/img/fon.jpg',
        'url' => '/news/view-news.php?id=1'
    ],
    [
        'title' => 'Літній турнір на PvP-арені',
        'date' => '09 серпня 2026',
        'excerpt' => 'Команди змагаються за нагороди, збірки та унікальні предмети. Реєстрація відкривається щодня до 20:00.',
        'image' => '/img/fon.jpg',
        'url' => '/news/view-news.php?id=2'
    ],
    [
        'title' => 'Скоро робочий сезон “Розвиток міст”',
        'date' => '03 серпня 2026',
        'excerpt' => 'Додаємо нові системи економіки, прокачування територій та спеціальні бонуси для активних гравців.',
        'image' => '/img/fon.jpg',
        'url' => '/news/view-news.php?id=3'
    ]
];

function normalizeNewsImage(string $image): string
{
    $trimmed = trim($image);
    if ($trimmed === '') {
        return '/img/fon.jpg';
    }

    if (strpos($trimmed, 'http://') === 0 || strpos($trimmed, 'https://') === 0 || strpos($trimmed, '/uploads/') === 0 || strpos($trimmed, '/img/') === 0) {
        return $trimmed;
    }

    return '/img/' . $trimmed;
}

function buildHomeNewsExcerpt(string $text): string
{
    $plain = trim(strip_tags($text));
    if ($plain === '') {
        return 'Опис новини буде доступний тут.';
    }

    if (function_exists('mb_substr')) {
        return mb_strlen($plain) > 180 ? mb_substr($plain, 0, 180) . '...' : $plain;
    }

    return strlen($plain) > 180 ? substr($plain, 0, 180) . '...' : $plain;
}

function renderHomeRichText(string $text): string
{
    $allowed = '<strong><b><em><i><u><ul><ol><li><p><br><h2><h3><blockquote>';
    if (strip_tags($text) === $text) {
        return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
    }

    return strip_tags($text, $allowed);
}

function renderHomeShortText(string $text): string
{
    $plain = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8')));
    if (function_exists('mb_substr')) {
        $plain = mb_strlen($plain) > 180 ? mb_substr($plain, 0, 180) . '...' : $plain;
    } else {
        $plain = strlen($plain) > 180 ? substr($plain, 0, 180) . '...' : $plain;
    }

    return htmlspecialchars($plain, ENT_QUOTES, 'UTF-8');
}

$link = atcraft_db_connect();
if ($link) {
    $newsRows = mysqli_query($link, 'SELECT * FROM news ORDER BY id DESC LIMIT 6');
    if ($newsRows) {
        $dbNews = [];
        while ($row = mysqli_fetch_assoc($newsRows)) {
            $content = trim((string)($row['content'] ?? ''));
            $excerpt = trim((string)($row['excerpt'] ?? ''));
            if ($excerpt === '' && $content !== '') {
                $excerpt = buildHomeNewsExcerpt($content);
            }
            $dbNews[] = [
                'title' => $row['title'] ?? 'Без назви',
                'date' => !empty($row['date_posted']) && strpos((string)$row['date_posted'], '0000-00-00') !== 0 ? $row['date_posted'] : date('d.m.Y'),
                'excerpt' => $excerpt !== '' ? $excerpt : 'Новина додана через адмін-панель.',
                'image' => normalizeNewsImage((string)($row['image'] ?? '/img/fon.jpg')),
                'url' => '/news/view-news.php?id=' . (int)($row['id'] ?? 0),
            ];
        }

        if (!empty($dbNews)) {
            $news = $dbNews;
        }
    }
    mysqli_close($link);
}

$plugins = [
    [
        'slug' => 'coreprotect',
        'name' => 'CoreProtect',
        'tag' => 'Безпека',
        'price' => 'Безкоштовно',
        'desc' => 'Система відстеження змін, логів і швидкого відновлення блоків. Ідеально підходить для мінімізації втрат на сервері.',
        'long' => 'CoreProtect дає повний контроль над змінами на карті: відстеження руйнувань, редагувань, обмінів, крафтів і більшого. Завдяки цьому адміністрація може оперативно відновлювати втрати і зберігати стабільність гри.'
    ],
    [
        'slug' => 'essentialsx',
        'name' => 'EssentialsX',
        'tag' => 'Утиліти',
        'price' => 'Безкоштовно',
        'desc' => 'Команди для економіки, дому, телепортів і зручної взаємодії між гравцями.',
        'long' => 'EssentialsX додає основні серверні інструменти: команди home, sethome, warp, money, kits, nicknames та систему приватних інвентарів. Плагін робить життя гравців простішим без втрати інтеграції сервера.'
    ],
    [
        'slug' => 'worldedit',
        'name' => 'WorldEdit',
        'tag' => 'Мапінг',
        'price' => 'Преміум',
        'desc' => 'Зручне редагування великих територій, будівництво, заміни блоків та швидка генерація конструкцій.',
        'long' => 'WorldEdit стане незамінним для будівельників, адмінів та організаторів подій. Завдяки швидким інструментам можна створювати великі архітектурні проєкти за хвилини і легко масштабувати карти.'
    ]
];

$storedPluginsPath = __DIR__ . '/data/plugins.json';
if (is_file($storedPluginsPath)) {
    $storedPlugins = json_decode((string)file_get_contents($storedPluginsPath), true);
    if (is_array($storedPlugins)) {
        $plugins = $storedPlugins;
    }
}

$shopItems = [
    [
        'name' => 'Fly',
        'price' => '120 грн',
        'tag' => 'Полети',
        'desc' => 'Літати по карті, економити час і легко пересуватися між локаціями.'
    ],
    [
        'name' => 'Creative',
        'price' => '180 грн',
        'tag' => 'Творчий режим',
        'desc' => 'Необмежений доступ до творчого режиму, безліч блоків і швидка побудова.'
    ],
    [
        'name' => 'Mod',
        'price' => '260 грн',
        'tag' => 'Преміум пакети',
        'desc' => 'Комплект можливостей, бонуси, привілеї та розширений набір функцій для сервера.'
    ]
];

$pageTitle = 'aTcraft — Minecraft сервер 26.1.2 | Виживання та PvP';
$pageDescription = 'aTcraft — Minecraft сервер версії 26.1.2 з виживанням, PvP, містами, економікою та активною спільнотою гравців.';
$pageKeywords = 'Minecraft 26.1.2, сервер Minecraft, Minecraft сервер Україна, виживання Minecraft, PvP, Towny, aTcraft';

include 'header.php';
?>

<main class="minecraft-page">
    <?php if (empty($_SESSION['admin_logged_in'])): ?>
        <div id="admin-login-modal" class="admin-login-modal" aria-hidden="true">
            <div class="admin-login-window" role="dialog" aria-modal="true" aria-labelledby="admin-login-title">
                <button type="button" class="admin-login-close" data-close-admin-modal="1" aria-label="Закрити">×</button>
                <h2 id="admin-login-title">Вхід в адмін-панель</h2>

                <?php if ($loginError): ?>
                    <div class="admin-login-error"><?= htmlspecialchars($loginError) ?></div>
                <?php endif; ?>

                <form method="post" class="admin-login-form">
                    <input type="text" name="login_username" placeholder="Логін" required>
                    <input type="password" name="login_password" placeholder="Пароль" required>
                    <button type="submit" name="login" value="1">Увійти</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <section class="hero-panel">
        <div class="hero-content">
            <span class="eyebrow">aTcraft • Minecraft survival</span>
            <h1>Будуй, виживай і бери верх над світом</h1>
            <p>
                Приєднуйся до нашого сервера, де є економіка, PvP, міста, крафт, розклади подій і нові оновлення кожного тижня.
            </p>
            <div class="hero-actions">
                <a class="primary-btn" href="/shop.php">Купити привілею</a>
            </div>
        </div>
        <div class="hero-stats">
            <div class="stat-card">
                <strong>aTcraft.com.ua</strong>
                <span>Адреса сервера</span>
                <button type="button" class="copy-ip-button" data-copy-ip="aTcraft.com.ua">Скопіювати IP</button>
            </div>
            <div class="stat-card">
                <strong>24/7</strong>
                <span>Робочий сервер</span>
            </div>
            <div class="stat-card">
                <strong>26.1.2</strong>
                <span>Версія сервера</span>
            </div>
        </div>
    </section>

    <?php if (!empty($_SESSION['admin_logged_in'])): ?>
        <section class="content-section admin-panel-open" style="margin-top: 10px;">
            <div class="section-head">
                <h2>Адмін-панель</h2>
            </div>

            <div class="admin-create-shell">
                <form method="post" action="/admin.php" class="admin-create-form" enctype="multipart/form-data">
                    <input type="hidden" name="admin_create" value="1">

                    <div class="admin-form-row admin-form-top">
                        <label for="admin_target">Розділ</label>
                        <select id="admin_target" name="admin_target">
                            <option value="news">Новини</option>
                            <option value="plugins">Плагіни</option>
                            <option value="shop">Магазин</option>
                        </select>
                    </div>

                    <div class="admin-form-row">
                        <label for="admin_title">Назва</label>
                        <input id="admin_title" type="text" name="admin_title" placeholder="Наприклад: Нова карта" required>
                    </div>

                    <div class="admin-form-row">
                        <label for="admin_tag">Тег / категорія</label>
                        <input id="admin_tag" type="text" name="admin_tag" placeholder="Автоматично з назви">
                    </div>

                    <div class="admin-form-row admin-price-row">
                        <label for="admin_price">Ціна</label>
                        <input id="admin_price" type="text" name="admin_price" placeholder="Наприклад: 120 грн">
                    </div>

                    <div class="admin-form-row">
                        <label for="admin_seo_tags">SEO-теги</label>
                        <input id="admin_seo_tags" type="text" name="admin_seo_tags" placeholder="Автоматично з назви">
                    </div>

                    <div class="admin-form-row">
                        <label for="admin_date">Дата</label>
                        <input id="admin_date" type="text" name="admin_date" value="<?= date('d.m.Y') ?>" readonly>
                    </div>

                    <div class="admin-form-row">
                        <label for="admin_desc">Повний опис новини</label>
                        <div class="admin-editor-field">
                            <div class="admin-editor-toolbar" role="toolbar" aria-label="Форматування опису">
                                <button type="button" data-editor-command="bold"><strong>B</strong></button>
                                <button type="button" data-editor-command="italic"><em>I</em></button>
                                <button type="button" data-editor-command="underline"><u>U</u></button>
                                <button type="button" data-editor-command="formatBlock" data-editor-value="h3">H3</button>
                                <button type="button" data-editor-command="insertUnorderedList">• Список</button>
                                <button type="button" data-editor-command="insertOrderedList">1. Список</button>
                            </div>
                            <div id="admin_desc_editor" class="admin-rich-editor" contenteditable="true" data-placeholder="Введіть опис новини..."></div>
                            <textarea id="admin_desc" name="admin_content" hidden></textarea>
                        </div>
                    </div>

                    <div class="admin-form-row">
                        <label for="admin_image">Зображення</label>
                        <input id="admin_image" type="file" name="admin_image" accept="image/*">
                    </div>

                    <button type="submit" class="admin-create-btn">Додати запис</button>
                </form>
            </div>
        </section>
    <?php endif; ?>

    <section class="content-section">
        <div class="section-head">
            <h2>Новини</h2>
            <a href="/news/">Всі новини</a>
        </div>
        <div class="news-grid">
            <?php foreach ($news as $item): ?>
                <article class="news-card card-with-delete">
                    <?php if (!empty($_SESSION['admin_logged_in'])): ?>
                        <div class="card-admin-actions">
                            <a class="edit-card-btn" href="/admin.php?edit_news_id=<?= urlencode((string) (parse_url($item['url'], PHP_URL_QUERY) ? str_replace('id=', '', parse_url($item['url'], PHP_URL_QUERY)) : '')) ?>&return_to=%2F" aria-label="Редагувати новину">Редагувати</a>
                            <form method="post" action="/admin.php" class="card-delete-form">
                                <input type="hidden" name="redirect_to" value="/">
                                <input type="hidden" name="news_action" value="delete">
                                <input type="hidden" name="news_id" value="<?= htmlspecialchars(parse_url($item['url'], PHP_URL_QUERY) ? str_replace('id=', '', parse_url($item['url'], PHP_URL_QUERY)) : '') ?>">
                                <button type="submit" class="delete-card-btn" aria-label="Видалити новину">×</button>
                            </form>
                        </div>
                    <?php endif; ?>
                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy" decoding="async">
                    <div class="news-card-body">
                        <span class="news-date"><?= htmlspecialchars($item['date']) ?></span>
                        <h3><?= htmlspecialchars($item['title']) ?></h3>
                        <p><?= htmlspecialchars($item['excerpt']) ?></p>
                        <a href="<?= htmlspecialchars($item['url']) ?>">Читати</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="content-section alt-bg">
        <div class="section-head">
            <h2>Плагіни</h2>
            <a href="/plugins.php">Подивитися всі</a>
        </div>
        <div class="plugin-grid">
            <?php foreach ($plugins as $plugin): ?>
                <article class="plugin-card card-with-delete">
                    <?php if (!empty($_SESSION['admin_logged_in'])): ?>
                        <div class="card-admin-actions">
                            <a class="edit-card-btn" href="/admin.php?edit_plugin_slug=<?= urlencode((string)($plugin['slug'] ?? '')) ?>&return_to=%2F" aria-label="Редагувати плагін">Редагувати</a>
                            <form method="post" action="/admin.php" class="card-delete-form">
                                <input type="hidden" name="redirect_to" value="/">
                                <input type="hidden" name="plugin_action" value="delete">
                                <input type="hidden" name="plugin_slug" value="<?= htmlspecialchars($plugin['slug'] ?? '') ?>">
                                <button type="submit" class="delete-card-btn" aria-label="Видалити плагін">×</button>
                            </form>
                        </div>
                    <?php endif; ?>
                    <div class="plugin-badge">Plugin</div>
                    <h3><?= htmlspecialchars($plugin['name']) ?></h3>
                    <div><?= renderHomeShortText((string)$plugin['desc']) ?></div>
                    <div class="plugin-meta">
                        <a class="mini-btn plugin-button" href="/plugins.php?plugin=<?= htmlspecialchars($plugin['slug']) ?>">Детальніше</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="content-section">
        <div class="section-head">
            <h2>Магазин</h2>
            <a href="/shop.php">Відкрити каталог</a>
        </div>
        <div class="shop-grid">
            <?php foreach ($shopItems as $item): ?>
                <article class="shop-card card-with-delete">
                    <?php if (!empty($_SESSION['admin_logged_in'])): ?>
                        <div class="card-admin-actions">
                            <a class="edit-card-btn" href="/admin.php?edit_privilege_name=<?= urlencode((string)($item['name'] ?? '')) ?>&return_to=%2F" aria-label="Редагувати привілей">Редагувати</a>
                            <form method="post" action="/admin.php" class="card-delete-form">
                                <input type="hidden" name="redirect_to" value="/">
                                <input type="hidden" name="privilege_action" value="delete">
                                <input type="hidden" name="privilege_name" value="<?= htmlspecialchars($item['name'] ?? '') ?>">
                                <button type="submit" class="delete-card-btn" aria-label="Видалити привілей">×</button>
                            </form>
                        </div>
                    <?php endif; ?>
                    <span class="shop-tag"><?= htmlspecialchars($item['tag']) ?></span>
                    <h3><?= htmlspecialchars($item['name']) ?></h3>
                    <div><?= renderHomeRichText((string)$item['desc']) ?></div>
                    <div class="shop-bottom">
                        <strong><?= htmlspecialchars($item['price']) ?></strong>
                        <a href="/shop.php" class="mini-btn">Купити</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<script>
(function () {
    const target = document.getElementById('admin_target');
    const title = document.getElementById('admin_title');
    const tag = document.getElementById('admin_tag');
    const seoTags = document.getElementById('admin_seo_tags');
    const form = document.querySelector('.admin-create-form');
    const editor = document.getElementById('admin_desc_editor');
    const content = document.getElementById('admin_desc');
    const stopWords = new Set(['і', 'й', 'та', 'на', 'у', 'в', 'до', 'з', 'для', 'про', 'це']);

    if (!title || !tag || !seoTags || !form || !editor || !content) return;

    function makeTags(value) {
        return [...new Set(value.toLowerCase().split(/[^\p{L}\p{N}]+/u).filter(word => word.length >= 3 && !stopWords.has(word)))].slice(0, 8).join(', ');
    }

    title.addEventListener('input', function () {
        const generated = makeTags(title.value);
        if (!tag.dataset.edited) tag.value = generated;
        if (!seoTags.dataset.edited) seoTags.value = generated;
    });
    tag.addEventListener('input', () => { tag.dataset.edited = '1'; });
    seoTags.addEventListener('input', () => { seoTags.dataset.edited = '1'; });
    document.querySelectorAll('[data-editor-command]').forEach(function (button) {
        button.addEventListener('click', function () {
            editor.focus();
            document.execCommand(button.dataset.editorCommand, false, button.dataset.editorValue || null);
        });
    });
    form.addEventListener('submit', function () {
        content.value = editor.innerHTML;
    });
})();
</script>

<style>
    .admin-login-modal {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 20px;
    }
    .admin-login-modal.open {
        display: flex;
    }
    .admin-login-window {
        position: relative;
        width: min(440px, 100%);
        background: #edf3f5;
        border-radius: 18px;
        padding: 28px 24px 20px;
        box-shadow: 0 18px 45px rgba(0,0,0,0.25);
        color: #17212b;
    }
    .admin-login-window h2 {
        margin: 0 0 18px;
        font-size: 32px;
        text-align: center;
    }
    .admin-login-close {
        position: absolute;
        top: 12px;
        right: 12px;
        border: none;
        background: transparent;
        font-size: 30px;
        line-height: 1;
        cursor: pointer;
        color: #17212b;
    }
    .admin-login-form {
        display: grid;
        gap: 12px;
    }
    .admin-login-form input,
    .admin-login-form button {
        width: 100%;
        box-sizing: border-box;
        border-radius: 12px;
        padding: 14px 16px;
        font-size: 15px;
    }
    .admin-login-form input {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        color: #0f172a;
    }
    .admin-login-form button {
        border: none;
        background: #24c86b;
        color: #fff;
        font-weight: 700;
        cursor: pointer;
    }
    .admin-login-error {
        margin-bottom: 12px;
        color: #b91c1c;
        text-align: center;
        font-weight: 600;
    }
    .admin-panel-open {
        background: rgba(239, 244, 245, 0.92);
        border: 1px solid #d5dfe4;
        border-radius: 18px;
        box-shadow: none;
        padding: 20px 18px 18px;
        margin-top: 18px;
        width: min(1200px, 92%);
        margin-left: auto;
        margin-right: auto;
    }
    .admin-create-shell {
        background: transparent;
        border: none;
        border-radius: 0;
        padding: 0;
        box-shadow: none;
    }
    .admin-create-form {
        display: grid;
        gap: 8px;
        max-width: 560px;
        width: 100%;
        margin: 0 auto;
    }
    .admin-form-row {
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 34px;
    }
    .admin-form-row label {
        width: 120px;
        min-width: 120px;
        font-size: 15px;
        font-weight: 700;
        color: #0d151b;
        line-height: 1.4;
        text-align: left;
    }
    .admin-create-form input,
    .admin-create-form textarea,
    .admin-create-form select {
        flex: 1;
        width: 100%;
        box-sizing: border-box;
        border-radius: 4px;
        border: 1px solid #b9c7ce;
        background: rgba(255,255,255,0.15);
        color: #0d151b;
        padding: 7px 10px;
        font-size: 15px;
        line-height: 1.5;
        font-family: "Plus Jakarta Sans", sans-serif;
    }
    .admin-create-form textarea {
        resize: vertical;
        min-height: 110px;
    }
    .admin-editor-field {
        flex: 1;
        min-width: 0;
    }
    .admin-editor-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 6px;
        border: 1px solid #b9c7ce;
        border-bottom: 0;
        border-radius: 4px 4px 0 0;
        background: rgba(255,255,255,0.35);
    }
    .admin-editor-toolbar button {
        border: 1px solid #b9c7ce;
        border-radius: 4px;
        background: #f5f7f8;
        color: #0d151b;
        cursor: pointer;
        padding: 4px 8px;
    }
    .admin-rich-editor {
        min-height: 150px;
        padding: 10px;
        border: 1px solid #b9c7ce;
        border-radius: 0 0 4px 4px;
        background: rgba(255,255,255,0.15);
        color: #0d151b;
        line-height: 1.5;
        outline: none;
    }
    .admin-rich-editor:empty::before {
        content: attr(data-placeholder);
        color: #64748b;
    }
    .admin-form-top {
        max-width: 320px;
    }
    .admin-create-btn {
        width: fit-content;
        border: 1px solid #b7c4ca;
        border-radius: 8px;
        background: #f5f7f8;
        color: #0d151b;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        padding: 8px 16px;
        margin-top: 8px;
        box-shadow: none;
    }
    .admin-list-toggle-wrap {
        display: flex;
        justify-content: flex-end;
    }
    .admin-list-toggle {
        border: 1px solid var(--line);
        background: var(--panel-alt);
        color: var(--text-strong);
        border-radius: 10px;
        padding: 10px 14px;
        cursor: pointer;
        font-weight: 700;
    }
    .admin-full-list {
        display: grid;
        gap: 18px;
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid var(--line);
    }
    .admin-list-group {
        background: var(--panel-alt);
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 16px;
    }
    .admin-list-group h3 {
        margin: 0 0 12px;
        color: var(--text-strong);
    }
    .admin-list-items {
        display: grid;
        gap: 10px;
    }
    .admin-item-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        background: rgba(255,255,255,0.04);
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: 8px 10px;
    }
    .admin-item-row span {
        color: var(--text-strong);
        flex: 1;
        min-width: 0;
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
        cursor: pointer;
    }
    .card-edit-form {
        position: relative;
        z-index: 2;
        display: grid;
        gap: 8px;
        margin-top: 42px;
        padding: 10px;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: rgba(255,255,255,0.04);
    }
    .card-edit-form input,
    .card-edit-form textarea,
    .card-edit-form button {
        width: 100%;
        box-sizing: border-box;
        border-radius: 8px;
        border: 1px solid var(--line);
        padding: 8px 10px;
        font-size: 13px;
    }
    .card-edit-form button {
        background: #22c55e;
        color: #052e16;
        font-weight: 800;
        cursor: pointer;
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
    @media (max-width: 900px) {
        .admin-quick-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php include 'footer.php'; ?>
