<?php
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header('Location: /news/');
    exit;
}

if (!empty($_POST['login'])) {
    $loginUsername = $_POST['login_username'] ?? '';
    $loginPassword = $_POST['login_password'] ?? '';

    if ($loginUsername === 'admin' && $loginPassword !== '' && hash_equals((string)(getenv('ATCRAFT_ADMIN_PASSWORD') ?: ''), $loginPassword)) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: /news/');
        exit;
    }

    $_SESSION['login_error'] = 'Невірний логін або пароль';
    header('Location: /news/#admin-login');
    exit;
}

$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

function normalizeNewsImagePath(string $image): string
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

function buildNewsExcerptFromBody(string $text): string
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

$link = atcraft_db_connect();
$newsPosts = [];

if ($link) {
    $result = mysqli_query($link, 'SELECT * FROM news ORDER BY id DESC');
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $content = trim((string)($row['content'] ?? ''));
            $excerpt = trim((string)($row['excerpt'] ?? ''));
            if ($excerpt === '' && $content !== '') {
                $excerpt = buildNewsExcerptFromBody($content);
            }
            $newsPosts[] = [
                'title' => $row['title'] ?? 'Без назви',
                'date' => !empty($row['date_posted']) && strpos((string)$row['date_posted'], '0000-00-00') !== 0 ? $row['date_posted'] : date('d.m.Y'),
                'excerpt' => $excerpt !== '' ? $excerpt : 'Новина додана через адмін-панель.',
                'image' => normalizeNewsImagePath((string)($row['image'] ?? '/img/fon.jpg')),
                'url' => '/news/view-news.php?id=' . (int)($row['id'] ?? 0),
            ];
        }
    }
    mysqli_close($link);
}

if (empty($newsPosts)) {
    $newsPosts = [
        [
            'title' => 'Реліз нового сезону',
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
}

include '../header.php';
?>

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

<main class="content-shell news-page">
    <div class="section-head simple-head">
        <h2>Останні новини сервера</h2>
    </div>

    <div class="news-list">
        <?php foreach ($newsPosts as $post): ?>
            <article class="news-card wide-card card-with-delete">
                <?php if (!empty($_SESSION['admin_logged_in'])): ?>
                    <form method="post" action="/admin.php" class="card-delete-form">
                        <input type="hidden" name="redirect_to" value="/news/">
                        <input type="hidden" name="news_action" value="delete">
                        <input type="hidden" name="news_id" value="<?= htmlspecialchars((string) preg_replace('/[^0-9]/', '', (string)($post['url'] ?? ''))) ?>">
                        <button type="submit" class="delete-card-btn" aria-label="Видалити новину">×</button>
                    </form>
                <?php endif; ?>
                <img src="<?= htmlspecialchars($post['image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy" decoding="async">
                <div class="news-card-body">
                    <span class="news-date"><?= htmlspecialchars($post['date']) ?></span>
                    <h3><?= htmlspecialchars($post['title']) ?></h3>
                    <p><?= htmlspecialchars($post['excerpt']) ?></p>
                    <a href="<?= htmlspecialchars($post['url']) ?>">Детальніше</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</main>

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
    .news-page .news-list {
        grid-template-columns: 1fr !important;
    }
    .news-page .wide-card {
        position: relative;
        display: grid !important;
        grid-template-columns: minmax(260px, 34%) minmax(0, 1fr) !important;
        height: 280px;
        min-height: 280px;
        overflow: hidden;
    }
    .news-page .wide-card .card-delete-form {
        position: absolute;
        top: 12px;
        left: 12px;
        z-index: 3;
    }
    .news-page .wide-card > img {
        width: 100%;
        height: 280px;
        min-height: 280px;
        object-fit: cover;
    }
    .news-page .wide-card .news-card-body {
        min-width: 0;
        padding: 24px 28px;
    }
    @media (max-width: 700px) {
        .news-page .wide-card {
            display: block !important;
            height: auto;
            min-height: 0;
        }
        .news-page .wide-card > img {
            height: 220px;
            min-height: 0;
        }
    }
</style>

<?php include '../footer.php'; ?>
