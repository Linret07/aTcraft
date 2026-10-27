<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ArtSasha/smtp_mail.php';
session_start();

const ADMIN_LOGIN = 'admin';
function atcraft_admin_password(): string
{
    return (string)(getenv('ATCRAFT_ADMIN_PASSWORD') ?: '');
}

function notifyAtcraftSubscribersAboutPlugin(string $name, string $description): void
{
    $link = atcraft_db_connect();
    if (!$link) return;

    $result = @mysqli_query($link, 'SELECT email FROM subscribers');
    if ($result) {
        $text = trim(strip_tags($description));
        $message = "На сайті aTcraft з'явився новий плагін: " . $name . "\n\n";
        if ($text !== '') $message .= $text . "\n\n";
        $message .= "Переглянути плагіни: https://atcraft.com.ua/plugins.php\n\n";
        $message .= "Щоб відписатися, зверніться до адміністрації сайту.";

        while ($subscriber = mysqli_fetch_assoc($result)) {
            $email = filter_var((string)($subscriber['email'] ?? ''), FILTER_VALIDATE_EMAIL);
            if ($email) sendSmtpEmail($email, 'Новий плагін на aTcraft: ' . $name, $message, false, (string)getenv('BREVO_ATCRAFT_FROM_EMAIL'), 'aTcraft');
        }
        mysqli_free_result($result);
    }
    mysqli_close($link);
}

function notifyAtcraftSubscribersAboutNews(string $title, string $description): void
{
    $link = atcraft_db_connect();
    if (!$link) return;

    $result = @mysqli_query($link, 'SELECT email FROM subscribers');
    if ($result) {
        $message = "На сайті aTcraft опубліковано нову новину:\n\n" . $title . "\n\n";
        $text = trim(strip_tags($description));
        if ($text !== '') $message .= $text . "\n\n";
        $message .= "Переглянути новини: https://atcraft.com.ua/news/\n\n";
        $message .= "Щоб відписатися, зверніться до адміністрації сайту.";

        while ($subscriber = mysqli_fetch_assoc($result)) {
            $email = filter_var((string)($subscriber['email'] ?? ''), FILTER_VALIDATE_EMAIL);
            if ($email) sendSmtpEmail($email, 'Нова новина на aTcraft: ' . $title, $message, false, (string)getenv('BREVO_ATCRAFT_FROM_EMAIL'), 'aTcraft');
        }
        mysqli_free_result($result);
    }
    mysqli_close($link);
}

function notifyAtcraftSubscribersAboutNewsStyled(string $title, string $description, ?int $newsId = null, string $image = ''): void
{
    $link = atcraft_db_connect();
    if (!$link) return;
    $result = @mysqli_query($link, 'SELECT email FROM subscribers');
    if (!$result) { mysqli_close($link); return; }

    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $shortText = trim(strip_tags($description));
    $shortText = function_exists('mb_substr') ? mb_substr($shortText, 0, 420) : substr($shortText, 0, 420);
    $safeText = htmlspecialchars($shortText, ENT_QUOTES, 'UTF-8');
    $postUrl = 'https://atcraft.com.ua/news/' . ($newsId ? 'view-news.php?id=' . $newsId : '');
    $imageHtml = '';
    if ($image !== '') {
        $imageUrl = 'https://atcraft.com.ua' . (strpos($image, '/') === 0 ? $image : '/' . $image);
        $imageHtml = '<img src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" alt="" style="display:block;width:100%;height:auto;border-radius:14px;margin-bottom:22px;">';
    }
    $html = '<div style="margin:0;background:#eef5f7;padding:28px 12px;font-family:Arial,sans-serif;color:#17232d;"><div style="max-width:620px;margin:auto;background:#fff;border:1px solid #d9e6e9;border-radius:20px;overflow:hidden;"><div style="padding:18px 26px;background:linear-gradient(135deg,#102f3c,#2b9d4a);color:#fff;font-size:22px;font-weight:700;"><img src="https://atcraft.com.ua/ArtSasha/favicon.ico" alt="aTcraft" width="42" height="42" style="display:inline-block;vertical-align:middle;margin-right:10px;border:0;"><span style="vertical-align:middle;">aTcraft</span> <span style="font-size:13px;font-weight:400;">НОВИНИ</span></div><div style="padding:26px;">' . $imageHtml . '<div style="color:#2b9d4a;font-size:12px;font-weight:700;letter-spacing:1px;">НОВЕ ОНОВЛЕННЯ</div><h1 style="margin:8px 0 14px;font-size:28px;line-height:1.15;color:#17232d;">' . $safeTitle . '</h1><p style="font-size:16px;line-height:1.6;color:#52626d;">' . nl2br($safeText) . '</p><a href="' . htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;margin-top:12px;padding:13px 20px;border-radius:9px;background:#2b9d4a;color:#fff;text-decoration:none;font-weight:700;">Читати новину</a></div><div style="padding:16px 26px;border-top:1px solid #e8eff1;color:#81909a;font-size:12px;">Ви отримали цей лист, бо підписалися на оновлення aTcraft.</div></div></div>';

    while ($subscriber = mysqli_fetch_assoc($result)) {
        $email = filter_var((string)($subscriber['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        if ($email) sendSmtpEmail($email, 'Нова новина на aTcraft: ' . $title, $html, true, (string)getenv('BREVO_ATCRAFT_FROM_EMAIL'), 'aTcraft');
    }
    mysqli_free_result($result);
    mysqli_close($link);
}

function getAdminReturnUrl(): string
{
    $returnTo = trim((string)($_GET['return_to'] ?? $_POST['redirect_to'] ?? '/'));
    if ($returnTo === '' || $returnTo[0] !== '/' || strpos($returnTo, '//') === 0) {
        return '/';
    }

    return $returnTo;
}

function readJsonFile(string $path, array $fallback): array
{
    if (!file_exists($path)) {
        return $fallback;
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        return $fallback;
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $fallback;
}

function writeJsonFile(string $path, array $data): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($encoded === false || file_put_contents($path, $encoded, LOCK_EX) === false) {
        error_log('aTcraft: failed to save JSON file ' . $path);
    }
}

function saveUploadedImage(string $field): string
{
    if (empty($_FILES[$field]['name']) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return '';
    }

    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $extension = strtolower(pathinfo((string)$_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'], true)) {
        return '';
    }

    $fileName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $targetPath = $uploadDir . $fileName;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
        return '';
    }

    return '/uploads/' . $fileName;
}

function buildPluginSlug(string $name, array $plugins = []): string
{
    $slug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/', '-', $name), '-'));
    if ($slug === '') {
        $slug = 'plugin-' . time();
    }

    $baseSlug = $slug;
    $counter = 2;
    while (in_array($slug, array_column($plugins, 'slug'), true)) {
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }

    return $slug;
}

function fetchNewsRows(): array
{
    $link = atcraft_db_connect();
    if (!$link) {
        return [];
    }

    $rows = [];
    $result = mysqli_query($link, 'SELECT * FROM news ORDER BY id DESC');
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }

    mysqli_close($link);
    return $rows;
}

function newsColumnExists(mysqli $link, string $column): bool
{
    $result = mysqli_query($link, "SHOW COLUMNS FROM news LIKE '" . mysqli_real_escape_string($link, $column) . "'");
    if (!$result) {
        return false;
    }

    $exists = mysqli_num_rows($result) > 0;
    mysqli_free_result($result);
    return $exists;
}

function buildNewsExcerpt(string $text): string
{
    $plain = trim(strip_tags($text));
    if ($plain === '') {
        return 'Новина додана через адмін-панель.';
    }

    if (function_exists('mb_substr')) {
        return mb_strlen($plain) > 180 ? mb_substr($plain, 0, 180) . '...' : $plain;
    }

    return strlen($plain) > 180 ? substr($plain, 0, 180) . '...' : $plain;
}

function normalizeNewsDate(string $date): string
{
    $value = trim($date);
    if ($value === '' || strpos($value, '0000-00-00') === 0) {
        return date('d.m.Y');
    }

    return $value;
}

function sanitizeAdminRichText(string $text): string
{
    $allowed = '<strong><b><em><i><u><ul><ol><li><p><br><h2><h3><blockquote>';
    $clean = strip_tags($text, $allowed);
    $clean = preg_replace('/\s+(class|style|id|data-[a-z0-9_-]+|aria-[a-z0-9_-]+|role)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);
    return trim((string)$clean);
}

function buildPluginShortDescription(string $text): string
{
    $plain = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8')));
    if (function_exists('mb_substr')) {
        return mb_strlen($plain) > 180 ? mb_substr($plain, 0, 180) . '...' : $plain;
    }

    return strlen($plain) > 180 ? substr($plain, 0, 180) . '...' : $plain;
}

function renderAdminEditor(string $id, string $name, string $value, bool $required = false): void
{
    $allowedTags = '<strong><b><em><i><u><ul><ol><li><p><br><h2><h3><blockquote>';
    $editorValue = strip_tags($value, $allowedTags);
    $requiredAttribute = $required ? ' required' : '';
    ?>
    <div class="admin-rich-editor-field">
        <div class="admin-editor-toolbar" role="toolbar" aria-label="Форматування опису">
            <button type="button" data-editor-command="bold"><strong>B</strong></button>
            <button type="button" data-editor-command="italic"><em>I</em></button>
            <button type="button" data-editor-command="underline"><u>U</u></button>
            <button type="button" data-editor-command="formatBlock" data-editor-value="h3">H3</button>
            <button type="button" data-editor-command="insertUnorderedList">• Список</button>
            <button type="button" data-editor-command="insertOrderedList">1. Список</button>
        </div>
        <div id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" class="admin-rich-editor" contenteditable="true" data-placeholder="Введіть опис..."><?= $editorValue ?></div>
        <textarea name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" data-editor-input="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" hidden<?= $requiredAttribute ?>><?= htmlspecialchars($editorValue, ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>
    <?php
}

function buildSeoTags(string $title): string
{
    $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower(trim($title)), -1, PREG_SPLIT_NO_EMPTY);
    $stopWords = ['і', 'й', 'та', 'на', 'у', 'в', 'до', 'з', 'для', 'про', 'це'];
    $tags = array_values(array_filter($words, function ($word) use ($stopWords) {
        return mb_strlen($word) >= 3 && !in_array($word, $stopWords, true);
    }));

    return implode(', ', array_slice(array_unique($tags), 0, 8));
}

function insertNewsItem(array $data): ?int
{
    $link = atcraft_db_connect();
    if (!$link) {
        return null;
    }

    $title = trim((string)($data['title'] ?? ''));
    $content = trim((string)($data['content'] ?? ''));
    $image = trim((string)($data['image'] ?? ''));
    $datePosted = trim((string)($data['date_posted'] ?? date('d.m.Y')));

    if ($title === '') {
        mysqli_close($link);
        return null;
    }

    $excerpt = trim((string)($data['excerpt'] ?? ''));
    if ($excerpt === '') {
        $excerpt = buildNewsExcerpt($content);
    }

    $columns = ['title'];
    $values = ["'" . mysqli_real_escape_string($link, $title) . "'"];

    if (newsColumnExists($link, 'excerpt')) {
        $columns[] = 'excerpt';
        $values[] = "'" . mysqli_real_escape_string($link, $excerpt) . "'";
    }

    if (newsColumnExists($link, 'content')) {
        $columns[] = 'content';
        $values[] = "'" . mysqli_real_escape_string($link, $content) . "'";
    }

    if (newsColumnExists($link, 'image')) {
        $columns[] = 'image';
        $values[] = "'" . mysqli_real_escape_string($link, $image) . "'";
    }

    if (newsColumnExists($link, 'date_posted')) {
        $columns[] = 'date_posted';
        $values[] = "'" . mysqli_real_escape_string($link, $datePosted) . "'";
    }

    if (newsColumnExists($link, 'seo_tags')) {
        $columns[] = 'seo_tags';
        $values[] = "'" . mysqli_real_escape_string($link, trim((string)($data['seo_tags'] ?? ''))) . "'";
    }

    $sql = 'INSERT INTO news (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')';
    $created = mysqli_query($link, $sql);
    $id = $created ? (int)mysqli_insert_id($link) : null;
    mysqli_close($link);
    return $id;
}

function deleteNewsItem(int $id): void
{
    $link = atcraft_db_connect();
    if (!$link) {
        return;
    }

    $safeId = (int) $id;
    mysqli_query($link, 'DELETE FROM news WHERE id = ' . $safeId);
    mysqli_close($link);
}

function updateNewsItem(int $id, array $data): void
{
    $link = atcraft_db_connect();
    if (!$link) {
        return;
    }

    $safeId = (int) $id;
    $fields = [];

    $title = trim((string)($data['title'] ?? ''));
    if ($title !== '' && newsColumnExists($link, 'title')) {
        $fields[] = "title = '" . mysqli_real_escape_string($link, $title) . "'";
    }

    $content = trim((string)($data['content'] ?? ''));
    if ($content !== '' && newsColumnExists($link, 'content')) {
        $fields[] = "content = '" . mysqli_real_escape_string($link, $content) . "'";
    }

    $excerpt = trim((string)($data['excerpt'] ?? ''));
    if ($excerpt !== '' && newsColumnExists($link, 'excerpt')) {
        $fields[] = "excerpt = '" . mysqli_real_escape_string($link, $excerpt) . "'";
    }

    $image = trim((string)($data['image'] ?? ''));
    if ($image !== '' && newsColumnExists($link, 'image')) {
        $fields[] = "image = '" . mysqli_real_escape_string($link, $image) . "'";
    }

    $datePosted = trim((string)($data['date_posted'] ?? ''));
    if ($datePosted !== '' && newsColumnExists($link, 'date_posted')) {
        $fields[] = "date_posted = '" . mysqli_real_escape_string($link, $datePosted) . "'";
    }

    if (!empty($fields)) {
        mysqli_query($link, 'UPDATE news SET ' . implode(', ', $fields) . ' WHERE id = ' . $safeId);
    }

    mysqli_close($link);
}

function updatePluginItem(string $slug, array $data, array $plugins): array
{
    $target = trim($slug);
    if ($target === '') {
        return $plugins;
    }

    foreach ($plugins as $index => $plugin) {
        if (($plugin['slug'] ?? '') === $target) {
            $plugins[$index] = array_merge($plugin, $data);
            break;
        }
    }

    return $plugins;
}

function updatePrivilegeItem(string $name, array $data, array $privileges): array
{
    $target = trim($name);
    if ($target === '') {
        return $privileges;
    }

    foreach ($privileges as $index => $item) {
        if (($item['name'] ?? '') === $target) {
            $privileges[$index] = array_merge($item, $data);
            break;
        }
    }

    return $privileges;
}

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header('Location: /');
    exit;
}

if (!empty($_POST['login'])) {
    $login = $_POST['login_username'] ?? '';
    $password = $_POST['login_password'] ?? '';

    if ($login === ADMIN_LOGIN && $password !== '' && hash_equals(atcraft_admin_password(), $password)) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: /admin.php');
        exit;
    }

    $loginError = 'Невірний логін або пароль';
}

if (!empty($_SESSION['admin_logged_in'])) {
    $pluginsPath = __DIR__ . '/data/plugins.json';
    $privilegesPath = __DIR__ . '/data/privileges.json';

    $editorType = null;
    $editorItem = null;
    $returnTo = getAdminReturnUrl();

    $pluginDefault = [
        ['slug' => 'coreprotect', 'name' => 'CoreProtect', 'tag' => 'Безпека', 'desc' => 'Система відстеження змін.', 'long' => 'CoreProtect дозволяє відстежувати зміни на карті.'],
        ['slug' => 'essentialsx', 'name' => 'EssentialsX', 'tag' => 'Утиліти', 'desc' => 'Команди для дому і економіки.', 'long' => 'EssentialsX дає базові утиліти сервера.'],
    ];

    $privilegeDefault = [
        ['name' => 'Fly', 'price' => '120 грн', 'tag' => 'Полети', 'desc' => 'Літати по карті.', 'features' => ['Постійний доступ до польоту']]
    ];

    $plugins = readJsonFile($pluginsPath, $pluginDefault);
    $privileges = readJsonFile($privilegesPath, $privilegeDefault);
    $newsRows = fetchNewsRows();

    if (!empty($_GET['edit_news_id'])) {
        $editorType = 'news';
        foreach ($newsRows as $row) {
            if ((int)($row['id'] ?? 0) === (int)$_GET['edit_news_id']) {
                $editorItem = $row;
                break;
            }
        }
    } elseif (!empty($_GET['edit_plugin_slug'])) {
        $editorType = 'plugin';
        foreach ($plugins as $plugin) {
            if (($plugin['slug'] ?? '') === trim((string)$_GET['edit_plugin_slug'])) {
                $editorItem = $plugin;
                break;
            }
        }
    } elseif (!empty($_GET['edit_privilege_name'])) {
        $editorType = 'privilege';
        foreach ($privileges as $privilege) {
            if (($privilege['name'] ?? '') === trim((string)$_GET['edit_privilege_name'])) {
                $editorItem = $privilege;
                break;
            }
        }
    }

    if (!empty($_POST['admin_create'])) {
        $target = trim((string)($_POST['admin_target'] ?? 'news'));
        $title = trim((string)($_POST['admin_title'] ?? ''));
        $tag = trim((string)($_POST['admin_tag'] ?? ''));
        $price = trim((string)($_POST['admin_price'] ?? ''));
        $fullText = trim((string)($_POST['admin_content'] ?? $_POST['admin_desc'] ?? ''));
        $dateValue = date('d.m.Y');
        $seoTags = trim((string)($_POST['admin_seo_tags'] ?? ''));
        $uploadedPath = '';

        if (!empty($_FILES['admin_image']['name'])) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($_FILES['admin_image']['name']));
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['admin_image']['tmp_name'], $targetPath)) {
                $uploadedPath = '/uploads/' . $fileName;
            }
        }

        if ($title !== '') {
            $tag = $tag !== '' ? $tag : buildSeoTags($title);
            $seoTags = $seoTags !== '' ? $seoTags : buildSeoTags($title);
            if ($target === 'plugins') {
                $slug = buildPluginSlug($title, $plugins);
                $plugins[] = [
                    'slug' => $slug !== '' ? $slug : 'plugin-' . time(),
                    'name' => $title,
                    'tag' => $tag !== '' ? $tag : 'Плагін',
                    'image' => $uploadedPath,
                    'desc' => $fullText !== '' ? $fullText : 'Додано через адмін-панель.',
                    'long' => $fullText !== '' ? $fullText : 'Додано через адмін-панель.'
                ];
                writeJsonFile($pluginsPath, $plugins);
            } elseif ($target === 'shop') {
                $privileges[] = [
                    'name' => $title,
                    'price' => $price !== '' ? $price : '120 грн',
                    'tag' => $tag !== '' ? $tag : 'Привілей',
                    'desc' => $fullText !== '' ? $fullText : 'Додано через адмін-панель.',
                    'features' => []
                ];
                writeJsonFile($privilegesPath, $privileges);
            } else {
                $newsContent = $fullText !== '' ? $fullText : 'Додано через адмін-панель.';
                $createdNewsId = insertNewsItem([
                    'title' => $title,
                    'excerpt' => buildNewsExcerpt($newsContent),
                    'content' => $newsContent,
                    'image' => $uploadedPath,
                    'date_posted' => $dateValue !== '' ? $dateValue : date('d.m.Y'),
                    'seo_tags' => $seoTags,
                ]);
                notifyAtcraftSubscribersAboutNewsStyled($title, $newsContent, $createdNewsId, $uploadedPath);
            }
        }

        header('Location: /');
        exit;
    }

    if (!empty($_POST['news_action'])) {
        if ($_POST['news_action'] === 'create') {
            $newsTitle = trim((string)($_POST['news_title'] ?? ''));
            $newsContent = (string)($_POST['news_content'] ?? '');
            $createdNewsId = insertNewsItem([
                'title' => $newsTitle,
                'excerpt' => $_POST['news_excerpt'] ?? '',
                'content' => $newsContent,
                'image' => $_POST['news_image'] ?? '',
                'date_posted' => normalizeNewsDate((string)($_POST['news_date'] ?? '')),
            ]);
            notifyAtcraftSubscribersAboutNewsStyled($newsTitle, $newsContent, $createdNewsId, (string)($_POST['news_image'] ?? ''));
        }

        if ($_POST['news_action'] === 'delete' && !empty($_POST['news_id'])) {
            deleteNewsItem((int) $_POST['news_id']);
        }

        if ($_POST['news_action'] === 'edit' && !empty($_POST['news_id'])) {
            updateNewsItem((int) $_POST['news_id'], [
                'title' => $_POST['news_title'] ?? '',
                'excerpt' => $_POST['news_excerpt'] ?? '',
                'content' => $_POST['news_content'] ?? '',
                'image' => $_POST['news_image'] ?? '',
                'date_posted' => normalizeNewsDate((string)($_POST['news_date'] ?? '')),
            ]);
        }

        $redirectTo = trim((string)($_POST['redirect_to'] ?? ''));
        if ($redirectTo === '' && !empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], '/admin.php') === false) {
            $redirectTo = '/';
        }
        $redirectTo = $redirectTo ?: '/admin.php';
        header('Location: ' . $redirectTo);
        exit;
    }

    if (!empty($_POST['plugin_action'])) {
        if ($_POST['plugin_action'] === 'create') {
            $name = trim((string)($_POST['plugin_name'] ?? ''));
            $slug = trim((string)($_POST['plugin_slug'] ?? ''));
            $tag = trim((string)($_POST['plugin_tag'] ?? ''));
            $desc = sanitizeAdminRichText((string)($_POST['plugin_desc'] ?? ''));
            $long = sanitizeAdminRichText((string)($_POST['plugin_long'] ?? ''));
            $descriptionLength = function_exists('mb_strlen') ? mb_strlen(strip_tags($desc)) : strlen(strip_tags($desc));
            if ($descriptionLength > 240) {
                $desc = buildPluginShortDescription($desc);
            }

            if ($name !== '') {
                $slug = $slug !== '' ? buildPluginSlug($slug, $plugins) : buildPluginSlug($name, $plugins);
                $image = saveUploadedImage('plugin_image');
                $plugins[] = ['slug' => $slug, 'name' => $name, 'tag' => $tag, 'image' => $image, 'desc' => $desc, 'long' => $long];
                writeJsonFile($pluginsPath, $plugins);
            }
        }

        if ($_POST['plugin_action'] === 'edit' && !empty($_POST['plugin_slug'])) {
            $slug = trim((string)$_POST['plugin_slug']);
            $image = saveUploadedImage('plugin_image');
            $pluginData = [
                'slug' => strtolower(trim((string)($_POST['plugin_slug'] ?? ''))),
                'name' => trim((string)($_POST['plugin_name'] ?? '')),
                'tag' => trim((string)($_POST['plugin_tag'] ?? '')),
                'desc' => sanitizeAdminRichText((string)($_POST['plugin_desc'] ?? '')),
                'long' => sanitizeAdminRichText((string)($_POST['plugin_long'] ?? '')),
            ];
            if ($image !== '') {
                $pluginData['image'] = $image;
            }
            $plugins = updatePluginItem($slug, $pluginData, $plugins);
            writeJsonFile($pluginsPath, $plugins);
        }

        if ($_POST['plugin_action'] === 'delete' && !empty($_POST['plugin_slug'])) {
            $target = trim((string)$_POST['plugin_slug']);
            $plugins = array_values(array_filter($plugins, function ($item) use ($target) {
                return ($item['slug'] ?? '') !== $target;
            }));
            writeJsonFile($pluginsPath, $plugins);
        }

        $redirectTo = trim((string)($_POST['redirect_to'] ?? ''));
        if ($redirectTo === '' && !empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], '/admin.php') === false) {
            $redirectTo = '/';
        }
        $redirectTo = $redirectTo ?: '/admin.php';
        header('Location: ' . $redirectTo);
        exit;
    }

    if (!empty($_POST['privilege_action'])) {
        if ($_POST['privilege_action'] === 'create') {
            $name = trim((string)($_POST['privilege_name'] ?? ''));
            $price = trim((string)($_POST['privilege_price'] ?? ''));
            $tag = trim((string)($_POST['privilege_tag'] ?? ''));
            $desc = trim((string)($_POST['privilege_desc'] ?? ''));
            $features = array_values(array_filter(array_map('trim', preg_split('/\r\n|\n|,/', (string)($_POST['privilege_features'] ?? ''))), function ($item) {
                return $item !== '';
            }));

            if ($name !== '') {
                $privileges[] = ['name' => $name, 'price' => $price, 'tag' => $tag, 'desc' => $desc, 'features' => $features];
                writeJsonFile($privilegesPath, $privileges);
            }
        }

        if ($_POST['privilege_action'] === 'edit' && !empty($_POST['privilege_name'])) {
            $target = trim((string)$_POST['privilege_name']);
            $features = array_values(array_filter(array_map('trim', preg_split('/\r\n|\n|,/', (string)($_POST['privilege_features'] ?? ''))), function ($item) {
                return $item !== '';
            }));
            $privileges = updatePrivilegeItem($target, [
                'name' => trim((string)($_POST['privilege_name'] ?? '')),
                'price' => trim((string)($_POST['privilege_price'] ?? '')),
                'tag' => trim((string)($_POST['privilege_tag'] ?? '')),
                'desc' => trim((string)($_POST['privilege_desc'] ?? '')),
                'features' => $features,
            ], $privileges);
            writeJsonFile($privilegesPath, $privileges);
        }

        if ($_POST['privilege_action'] === 'delete' && !empty($_POST['privilege_name'])) {
            $target = trim((string)$_POST['privilege_name']);
            $privileges = array_values(array_filter($privileges, function ($item) use ($target) {
                return ($item['name'] ?? '') !== $target;
            }));
            writeJsonFile($privilegesPath, $privileges);
        }

        $redirectTo = trim((string)($_POST['redirect_to'] ?? ''));
        if ($redirectTo === '' && !empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], '/admin.php') === false) {
            $redirectTo = '/';
        }
        $redirectTo = $redirectTo ?: '/admin.php';
        header('Location: ' . $redirectTo);
        exit;
    }
}
?>

<!doctype html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Адмін-панель | aTcraft</title>
    <link rel="stylesheet" href="/style-main.css">
    <style>
        body {
            margin: 0;
            font-family: "Plus Jakarta Sans", Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        .admin-shell {
            max-width: 1200px;
            margin: 112px auto 40px;
            padding: 24px;
        }
        .admin-card {
            background: var(--panel-strong);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow);
            margin-bottom: 24px;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }
        .admin-header h1 {
            margin: 0;
            font-size: 32px;
            color: var(--text-strong);
        }
        .admin-logout,
        .admin-back-link {
            color: #fff;
            background: #ef4444;
            padding: 10px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
        }
        .admin-editor {
            margin-bottom: 24px;
        }
        .editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }
        .editor-header h2 {
            margin: 0;
            font-size: 28px;
        }
        .admin-login {
            max-width: 420px;
            margin: 60px auto;
        }
        .admin-form {
            display: grid;
            gap: 14px;
        }
        .admin-form input,
        .admin-form textarea,
        .admin-form button {
            width: 100%;
            box-sizing: border-box;
            border-radius: 10px;
            padding: 12px 14px;
            border: 1px solid #475569;
            font-size: 15px;
        }
        .admin-form input,
        .admin-form textarea {
            background: var(--panel-alt);
            color: var(--text-strong);
            border-color: var(--line);
        }
        .admin-form button,
        .mini-btn {
            background: #22c55e;
            color: #082f1b;
            border: none;
            cursor: pointer;
            font-weight: 700;
        }
        .mini-btn.danger {
            background: #ef4444;
            color: white;
        }
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
        }
        .list-box {
            margin-top: 18px;
            display: grid;
            gap: 12px;
        }
        .list-item {
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 12px 14px;
            background: rgba(15, 23, 42, 0.7);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .list-item strong {
            display: block;
            margin-bottom: 3px;
        }
        .list-item form {
            margin: 0;
        }
        .error {
            color: #fca5a5;
            margin-bottom: 12px;
        }
        .admin-rich-editor-field {
            display: grid;
            gap: 0;
        }
        .admin-editor-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 6px;
            border: 1px solid var(--line);
            border-bottom: 0;
            border-radius: 10px 10px 0 0;
            background: var(--panel-alt);
        }
        .admin-editor-toolbar button {
            width: auto;
            min-width: 0;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--line);
            border-radius: 6px;
            background: var(--panel-strong);
            color: var(--text-strong);
            cursor: pointer;
            padding: 5px 9px;
            font-size: 14px;
            line-height: 1;
        }
        .admin-rich-editor {
            min-height: 150px;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 0 0 10px 10px;
            background: var(--panel-alt);
            color: var(--text-strong);
            line-height: 1.6;
            outline: none;
        }
        .admin-rich-editor:empty::before {
            content: attr(data-placeholder);
            color: #94a3b8;
        }
        @media (max-width: 640px) {
            .admin-shell {
                width: min(94%, 560px);
                margin-top: 104px;
                padding: 12px 0 28px;
            }
            .admin-card {
                padding: 18px 14px;
                border-radius: 14px;
            }
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .admin-grid {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .admin-form input,
            .admin-form textarea,
            .admin-form button {
                font-size: 16px;
            }
        }
    </style>
</head>
<body data-theme="dark">
    <header class="site-header">
        <div class="header">
            <div class="logo">
                <a class="logo-first" href="/"><b>aTcraft</b></a>
                <a class="logo-second" href="/"><b>.com.ua</b></a>
            </div>

            <button class="mobile-menu-toggle" type="button" aria-label="Відкрити меню" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>

            <nav class="main-nav" aria-label="Головна навігація">
                <ul>
                    <li><a href="/">Головна</a></li>
                    <li><a href="/news/">Новини</a></li>
                    <li><a href="/map/">Мапа</a></li>
                    <li><a href="/plugins.php">Плагіни</a></li>
                    <li><a href="/shop.php">Магазин</a></li>
                </ul>
            </nav>

            <div class="header-actions">
                <button class="theme-toggle" type="button" aria-label="Переключити тему">☀️</button>
                <a class="header-button" href="/admin.php?logout=1">Вийти</a>
            </div>
        </div>
    </header>

    <script>
    (function () {
        const toggle = document.querySelector('.mobile-menu-toggle');
        const nav = document.querySelector('.main-nav');
        if (toggle && nav) {
            toggle.addEventListener('click', function () {
                const isOpen = nav.classList.toggle('open');
                toggle.setAttribute('aria-expanded', String(isOpen));
            });
        }
    })();
    </script>

    <script>
    (function () {
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.admin-rich-editor').forEach(function (editor) {
                const input = document.querySelector('[data-editor-input="' + editor.id + '"]');
                const form = editor.closest('form');
                if (!input || !form) return;

                editor.parentElement.querySelectorAll('[data-editor-command]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        editor.focus();
                        document.execCommand(button.dataset.editorCommand, false, button.dataset.editorValue || null);
                    });
                });

                form.addEventListener('submit', function () {
                    input.value = editor.innerHTML;
                });
            });
        });
    })();
    </script>

    <div class="admin-shell">
        <?php if (empty($_SESSION['admin_logged_in'])): ?>
            <div class="admin-card admin-login">
                <h1>Вхід в адмін-панель</h1>
                <?php if (!empty($loginError)): ?>
                    <div class="error"><?= htmlspecialchars($loginError) ?></div>
                <?php endif; ?>
                <form method="post" class="admin-form">
                    <input type="text" name="login_username" placeholder="Логін" required>
                    <input type="password" name="login_password" placeholder="Пароль" required>
                    <button type="submit" name="login" value="1">Увійти</button>
                </form>
            </div>
        <?php else: ?>
            <div class="admin-header">
                <h1>Адмін-панель</h1>
            </div>

            <?php if (!empty($editorType) && $editorItem !== null): ?>
                <div class="admin-card admin-editor">
                    <div class="editor-header">
                        <h2><?= $editorType === 'news' ? 'Редагування новини' : ($editorType === 'plugin' ? 'Редагування плагіна' : 'Редагування привілею') ?></h2>
                        <a class="admin-back-link" href="<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8') ?>">Назад</a>
                    </div>

                    <?php if ($editorType === 'news'): ?>
                        <?php $editorNewsDate = normalizeNewsDate((string)($editorItem['date_posted'] ?? $editorItem['date'] ?? '')); ?>
                        <form method="post" class="admin-form" enctype="multipart/form-data">
                            <input type="hidden" name="news_action" value="edit">
                            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="news_id" value="<?= (int)($editorItem['id'] ?? 0) ?>">
                            <input type="text" name="news_title" value="<?= htmlspecialchars((string)($editorItem['title'] ?? '')) ?>" required>
                            <input type="text" name="news_date" value="<?= htmlspecialchars($editorNewsDate, ENT_QUOTES, 'UTF-8') ?>" readonly>
                            <textarea name="news_excerpt" rows="3" placeholder="Короткий опис"><?= htmlspecialchars((string)($editorItem['excerpt'] ?? '')) ?></textarea>
                            <?php renderAdminEditor('edit-news-content', 'news_content', (string)($editorItem['content'] ?? ''), true); ?>
                            <input type="text" name="news_image" value="<?= htmlspecialchars((string)($editorItem['image'] ?? '')) ?>" placeholder="URL зображення">
                            <button type="submit">Зберегти зміни</button>
                        </form>
                    <?php elseif ($editorType === 'plugin'): ?>
                        <form method="post" class="admin-form" enctype="multipart/form-data">
                            <input type="hidden" name="plugin_action" value="edit">
                            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="plugin_slug" value="<?= htmlspecialchars((string)($editorItem['slug'] ?? '')) ?>">
                            <input type="text" name="plugin_name" value="<?= htmlspecialchars((string)($editorItem['name'] ?? '')) ?>" required>
                            <input type="text" name="plugin_tag" value="<?= htmlspecialchars((string)($editorItem['tag'] ?? '')) ?>">
                                <label class="admin-file-label" for="edit_plugin_image">Зображення плагіна</label>
                                <input id="edit_plugin_image" type="file" name="plugin_image" accept="image/*">
                            <?php
                            $pluginEditorDescription = (string)($editorItem['desc'] ?? '');
                            $pluginDescriptionLength = function_exists('mb_strlen') ? mb_strlen(strip_tags($pluginEditorDescription)) : strlen(strip_tags($pluginEditorDescription));
                            if ($pluginDescriptionLength > 240) {
                                $pluginEditorDescription = buildPluginShortDescription($pluginEditorDescription);
                            }
                            renderAdminEditor('edit-plugin-desc', 'plugin_desc', $pluginEditorDescription, true);
                            ?>
                            <?php renderAdminEditor('edit-plugin-long', 'plugin_long', (string)($editorItem['long'] ?? ''), true); ?>
                            <button type="submit">Зберегти зміни</button>
                        </form>
                    <?php else: ?>
                        <form method="post" class="admin-form">
                            <input type="hidden" name="privilege_action" value="edit">
                            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="privilege_name" value="<?= htmlspecialchars((string)($editorItem['name'] ?? '')) ?>">
                            <input type="text" name="privilege_name" value="<?= htmlspecialchars((string)($editorItem['name'] ?? '')) ?>" required>
                            <input type="text" name="privilege_price" value="<?= htmlspecialchars((string)($editorItem['price'] ?? '')) ?>">
                            <input type="text" name="privilege_tag" value="<?= htmlspecialchars((string)($editorItem['tag'] ?? '')) ?>">
                            <?php renderAdminEditor('edit-privilege-desc', 'privilege_desc', (string)($editorItem['desc'] ?? '')); ?>
                            <textarea name="privilege_features" rows="3"><?= htmlspecialchars(is_array($editorItem['features'] ?? null) ? implode("\n", $editorItem['features']) : ((string)($editorItem['features'] ?? ''))) ?></textarea>
                            <button type="submit">Зберегти зміни</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="admin-grid">
                <section class="admin-card">
                    <h2>Новини</h2>
                    <form method="post" class="admin-form" enctype="multipart/form-data">
                        <input type="hidden" name="news_action" value="create">
                        <input type="text" name="news_title" placeholder="Назва новини" required>
                        <input type="text" name="news_date" placeholder="Дата (наприклад: 14.08.2026)">
                        <textarea name="news_excerpt" rows="3" placeholder="Короткий опис"></textarea>
                        <textarea name="news_content" rows="5" placeholder="Повний текст новини" required></textarea>
                        <input type="text" name="news_image" placeholder="Назва зображення (не обов'язково)">
                        <button type="submit">Створити новину</button>
                    </form>

                    <div class="list-box">
                        <?php foreach ($newsRows as $newsItem): ?>
                            <div class="list-item">
                                <div>
                                    <strong><?= htmlspecialchars($newsItem['title'] ?? 'Без назви') ?></strong>
                                    <span><?= htmlspecialchars($newsItem['date_posted'] ?? '') ?></span>
                                </div>
                                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                    <form method="post" style="margin:0;">
                                        <input type="hidden" name="news_action" value="delete">
                                        <input type="hidden" name="news_id" value="<?= (int)($newsItem['id'] ?? 0) ?>">
                                        <button class="mini-btn danger" type="submit">Видалити</button>
                                    </form>
                                    <button class="mini-btn" type="button" onclick="document.getElementById('edit-news-<?= (int)($newsItem['id'] ?? 0) ?>').style.display = document.getElementById('edit-news-<?= (int)($newsItem['id'] ?? 0) ?>').style.display === 'none' ? 'grid' : 'none';">Редагувати</button>
                                </div>
                            </div>
                            <form id="edit-news-<?= (int)($newsItem['id'] ?? 0) ?>" method="post" class="admin-form" style="display:none; margin-top:10px; background:rgba(15,23,42,0.55); border:1px solid #334155; border-radius:12px; padding:12px;">
                                <input type="hidden" name="news_action" value="edit">
                                <input type="hidden" name="news_id" value="<?= (int)($newsItem['id'] ?? 0) ?>">
                                <input type="text" name="news_title" value="<?= htmlspecialchars((string)($newsItem['title'] ?? '')) ?>" required>
                                <input type="text" name="news_date" value="<?= htmlspecialchars((string)($newsItem['date_posted'] ?? '')) ?>" placeholder="Дата">
                                <textarea name="news_excerpt" rows="3" placeholder="Короткий опис"><?= htmlspecialchars((string)($newsItem['excerpt'] ?? '')) ?></textarea>
                                <textarea name="news_content" rows="5" placeholder="Повний текст новини" required><?= htmlspecialchars((string)($newsItem['content'] ?? '')) ?></textarea>
                                <input type="text" name="news_image" value="<?= htmlspecialchars((string)($newsItem['image'] ?? '')) ?>" placeholder="Назва зображення">
                                <button type="submit">Зберегти зміни</button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="admin-card">
                    <h2>Плагіни</h2>
                    <form method="post" class="admin-form" enctype="multipart/form-data">
                        <input type="hidden" name="plugin_action" value="create">
                        <input type="text" name="plugin_slug" placeholder="Slug (необов'язково, наприклад: worldedit)">
                        <input type="text" name="plugin_name" placeholder="Назва плагіна" required>
                        <input type="text" name="plugin_tag" placeholder="Тег (наприклад: Мапінг)">
                        <label class="admin-file-label" for="plugin_image">Зображення плагіна</label>
                        <input id="plugin_image" type="file" name="plugin_image" accept="image/*">
                        <textarea name="plugin_desc" rows="3" placeholder="Короткий опис" required></textarea>
                        <textarea name="plugin_long" rows="4" placeholder="Детальний опис" required></textarea>
                        <button type="submit">Додати плагін</button>
                    </form>

                    <div class="list-box">
                        <?php foreach ($plugins as $plugin): ?>
                            <div class="list-item">
                                <div>
                                    <strong><?= htmlspecialchars($plugin['name'] ?? 'Без назви') ?></strong>
                                    <span><?= htmlspecialchars($plugin['tag'] ?? '') ?></span>
                                </div>
                                <form method="post">
                                    <input type="hidden" name="plugin_action" value="delete">
                                    <input type="hidden" name="plugin_slug" value="<?= htmlspecialchars($plugin['slug'] ?? '') ?>">
                                    <button class="mini-btn danger" type="submit">Видалити</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="admin-card">
                    <h2>Привілеї</h2>
                    <form method="post" class="admin-form">
                        <input type="hidden" name="privilege_action" value="create">
                        <input type="text" name="privilege_name" placeholder="Назва привілею" required>
                        <input type="text" name="privilege_price" placeholder="Ціна (наприклад: 120 грн)">
                        <input type="text" name="privilege_tag" placeholder="Тег (наприклад: Полети)">
                        <textarea name="privilege_desc" rows="3" placeholder="Опис"></textarea>
                        <textarea name="privilege_features" rows="3" placeholder="Переваги через кому або з нового рядка"></textarea>
                        <button type="submit">Додати привілей</button>
                    </form>

                    <div class="list-box">
                        <?php foreach ($privileges as $privilege): ?>
                            <div class="list-item">
                                <div>
                                    <strong><?= htmlspecialchars($privilege['name'] ?? 'Без назви') ?></strong>
                                    <span><?= htmlspecialchars($privilege['price'] ?? '') ?></span>
                                </div>
                                <form method="post">
                                    <input type="hidden" name="privilege_action" value="delete">
                                    <input type="hidden" name="privilege_name" value="<?= htmlspecialchars($privilege['name'] ?? '') ?>">
                                    <button class="mini-btn danger" type="submit">Видалити</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        (function () {
            const body = document.body;
            const toggle = document.querySelector('.theme-toggle');
            const savedTheme = localStorage.getItem('atcraft-theme') || 'dark';

            function applyTheme(theme) {
                body.setAttribute('data-theme', theme);
                localStorage.setItem('atcraft-theme', theme);
                if (toggle) {
                    toggle.textContent = theme === 'dark' ? '☀️' : '🌙';
                    toggle.setAttribute('aria-label', theme === 'dark' ? 'Увімкнути світлу тему' : 'Увімкнути темну тему');
                }
            }

            applyTheme(savedTheme);

            if (toggle) {
                toggle.addEventListener('click', function () {
                    const next = body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                    applyTheme(next);
                });
            }
        })();
    </script>
</body>
</html>
