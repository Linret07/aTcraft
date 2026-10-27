<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function renderShopText(string $text): string
{
    $allowed = '<strong><b><em><i><u><ul><ol><li><p><br><h2><h3><blockquote>';
    if (strip_tags($text) === $text) {
        return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
    }

    return strip_tags($text, $allowed);
}

if (!empty($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header('Location: /shop.php');
    exit;
}

if (!empty($_POST['login'])) {
    $loginUsername = $_POST['login_username'] ?? '';
    $loginPassword = $_POST['login_password'] ?? '';

    if ($loginUsername === 'admin' && $loginPassword !== '' && hash_equals((string)(getenv('ATCRAFT_ADMIN_PASSWORD') ?: ''), $loginPassword)) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: /shop.php');
        exit;
    }

    $_SESSION['login_error'] = 'Невірний логін або пароль';
    header('Location: /shop.php#admin-login');
    exit;
}

$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

$shopFile = __DIR__ . '/data/privileges.json';
$shopItems = [];

if (file_exists($shopFile)) {
    $decoded = json_decode(file_get_contents($shopFile), true);
    if (is_array($decoded) && !empty($decoded)) {
        $shopItems = $decoded;
    }
}

if (empty($shopItems)) {
    $shopItems = [
        [
            'name' => 'Fly',
            'price' => '120 грн',
            'tag' => 'Полети',
            'desc' => 'Літати по карті, економити час і легко пересуватися між локаціями.',
            'features' => ['Постійний доступ до польоту', 'Режим авіації у світі', 'Підтримка на всіх локаціях']
        ],
        [
            'name' => 'Creative',
            'price' => '180 грн',
            'tag' => 'Творчий режим',
            'desc' => 'Необмежений доступ до творчого режиму, безліч блоків і швидка побудова.',
            'features' => ['Відкритий інвентар', 'Швидкий будівельний доступ', 'Статус творця сервера']
        ],
        [
            'name' => 'Mod',
            'price' => '260 грн',
            'tag' => 'Преміум пакети',
            'desc' => 'Комплект можливостей, бонуси, привілеї та розширений набір функцій для сервера.',
            'features' => ['Всі бонуси привілею', 'Розширені команди', 'Ексклюзивні нагороди']
        ]
    ];
}

include 'header.php';
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

<main class="content-shell shop-page">
    <div class="section-head simple-head">
        <h2>Магазин привілеїв</h2>
    </div>

    <div class="shop-grid full-shop-grid">
        <?php foreach ($shopItems as $item): ?>
            <article class="shop-card purchase-card card-with-delete">
                <?php if (!empty($_SESSION['admin_logged_in'])): ?>
                    <div class="card-admin-actions">
                        <a href="/admin.php?edit_privilege_name=<?= urlencode((string)($item['name'] ?? '')) ?>&return_to=%2Fshop.php" class="edit-card-btn" aria-label="Редагувати привілей">Редагувати</a>
                        <form method="post" action="/admin.php" class="card-delete-form">
                            <input type="hidden" name="redirect_to" value="/shop.php">
                            <input type="hidden" name="privilege_action" value="delete">
                            <input type="hidden" name="privilege_name" value="<?= htmlspecialchars($item['name'] ?? '') ?>">
                            <button type="submit" class="delete-card-btn" aria-label="Видалити привілей">×</button>
                        </form>
                    </div>
                <?php endif; ?>
                <span class="shop-tag"><?= htmlspecialchars($item['tag']) ?></span>
                <h3><?= htmlspecialchars($item['name']) ?></h3>
                <div class="shop-description"><?= renderShopText((string)$item['desc']) ?></div>
                <ul class="feature-list">
                    <?php foreach (($item['features'] ?? []) as $feature): ?>
                        <li><?= htmlspecialchars($feature) ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="shop-bottom">
                    <strong><?= htmlspecialchars($item['price']) ?></strong>
                    <a href="mailto:support@atcraft.com.ua?subject=Купівля <?= urlencode($item['name']) ?>" class="mini-btn">Купити</a>
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
</style>

<?php include 'footer.php'; ?>
