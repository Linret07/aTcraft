-/<?php
// 1. СТАРТ СЕСІЇ (має бути на самому початку без жодних пробілів перед <?php)
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/smtp_mail.php';

// HTTP security and cache headers
header_remove('X-Powered-By');
header_remove('Server');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, no-cache');
header('Referrer-Policy: no-referrer-when-downgrade');

// 2. НАЛАШТУВАННЯ БАЗИ ДАНИХ (XAMPP MySQL)
$host = artsasha_env('DB_HOST', 'localhost');
$user = artsasha_env('DB_USER', 'root');
$pass = artsasha_env('DB_PASSWORD');
$db   = artsasha_env('DB_NAME', 'artsasha_db');

$conn = new mysqli($host, $user, $pass);
$conn->query("CREATE DATABASE IF NOT EXISTS $db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db($db);
$conn->query("CREATE TABLE IF NOT EXISTS drawings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    age VARCHAR(50) NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'Малювання',
    subcategory VARCHAR(50) NOT NULL DEFAULT 'Тварини',
    tags TEXT NOT NULL DEFAULT '',
    image_path VARCHAR(500) NOT NULL
)");
// Автоматична перевірка та створення колонки для лайків
$conn->query("ALTER TABLE drawings ADD COLUMN IF NOT EXISTS likes INT DEFAULT 0");

// Міграція: замінити image_data на image_path
$image_path_check = $conn->query("SHOW COLUMNS FROM drawings LIKE 'image_path'");
if ($image_path_check->num_rows == 0) {
    $conn->query("ALTER TABLE drawings ADD COLUMN image_path VARCHAR(500)");
}

$category_check = $conn->query("SHOW COLUMNS FROM drawings LIKE 'category'");
if ($category_check->num_rows == 0) {
    $conn->query("ALTER TABLE drawings ADD COLUMN category VARCHAR(50) NOT NULL DEFAULT 'Малювання'");
}
$subcategory_check = $conn->query("SHOW COLUMNS FROM drawings LIKE 'subcategory'");
if ($subcategory_check->num_rows == 0) {
    $conn->query("ALTER TABLE drawings ADD COLUMN subcategory VARCHAR(50) NOT NULL DEFAULT 'Тварини'");
}
$tags_check = $conn->query("SHOW COLUMNS FROM drawings LIKE 'tags'");
if ($tags_check->num_rows == 0) {
    $conn->query("ALTER TABLE drawings ADD COLUMN tags TEXT NOT NULL DEFAULT ''");
}

// Автоматичне створення таблиці для коментарів, якщо її ще немає
$conn->query("
    CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        drawing_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        text TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (drawing_id) REFERENCES drawings(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$conn->query("CREATE TABLE IF NOT EXISTS subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$correct_password = artsasha_env('ADMIN_PASSWORD');
$auth_error = false;
$subscribe_message = '';
$subscribe_status = '';

if (isset($_SESSION['subscribe_message'])) {
    $subscribe_message = $_SESSION['subscribe_message'];
    $subscribe_status = $_SESSION['subscribe_status'] ?? 'success';
    unset($_SESSION['subscribe_message'], $_SESSION['subscribe_status']);
}

$categories = [
    'Всі' => [],
    'Моделювання' => ['Тварини', 'Люди', 'Посуд', 'Кераміка', 'Повітряний пластилін', 'Поробки','Фантазія', 'Інше'],
    'Малювання' => ['Тварини', 'Рослини', 'Природа', 'Люди', 'Фантазія', 'Традиції та культура', 'Інше'],
    'Комп\'ютерна графіка' => ['Тварини', 'Рослини', 'Природа', 'Люди', 'Фантазія', 'Традиції та культура', 'Інше'],
];

$filter_category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : null;
$filter_subcategory = isset($_GET['subcategory']) ? $conn->real_escape_string($_GET['subcategory']) : null;
$search_query = isset($_GET['search']) ? trim($conn->real_escape_string($_GET['search'])) : null;

// Оброби запит на ВИХІД
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['is_admin']);
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Оброби запит на ВХІД через форму
if (isset($_POST['password'])) {
    if ($_POST['password'] === $correct_password) {
        $_SESSION['is_admin'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $auth_error = true;
    }
}

// Обробка підписки з футера
if (isset($_POST['subscribe_submit'])) {
    $email = trim($_POST['subscribe_email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $subscribe_message = 'Будь ласка, введіть дійсну електронну адресу.';
        $subscribe_status = 'error';
    } else {
        $email_safe = $conn->real_escape_string($email);
        $exists = $conn->query("SELECT id FROM subscribers WHERE email = '$email_safe'")->fetch_assoc();
        if ($exists) {
            $subscribe_message = 'Ця адреса вже підписана на оновлення.';
            $subscribe_status = 'info';
        } else {
            $conn->query("INSERT INTO subscribers (email) VALUES ('$email_safe')");
            $subscribe_message = 'Дякуємо! Ви успішно підписані.';
            $subscribe_status = 'success';
        }
    }

    $_SESSION['subscribe_message'] = $subscribe_message;
    $_SESSION['subscribe_status'] = $subscribe_status;
    header('Location: ' . $_SERVER['PHP_SELF'] . '#subscribe-form');
    exit();
}

// Визначаємо статус адміна СУВОРO з сесії
$is_admin = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true);

// ЛОГІКА ВИДАЛЕННЯ МАЛЮНКА (Працює безвідмовно)
if (isset($_POST['delete_id'])) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        $delete_id = intval($_POST['delete_id']);
        // Отримуємо шлях до файлу перед видаленням з БД
        $file_result = $conn->query("SELECT image_path FROM drawings WHERE id = $delete_id");
        if ($file_result && $file_result->num_rows > 0) {
            $file_row = $file_result->fetch_assoc();
            $file_path = __DIR__ . $file_row['image_path'];
            // Видаляємо файл з диска
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        // Видаляємо коментарі до цього малюнка
        $conn->query("DELETE FROM comments WHERE drawing_id = $delete_id");
        // Видаляємо запис з БД
        $conn->query("DELETE FROM drawings WHERE id = $delete_id");
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// 3. ОБРОБКА ЗАВАНТАЖЕННЯ МАЛЮНКІВ (З НАЛОЖЕННЯМ ВОДЯНОГО ЗНАКУ)
if (isset($_POST['upload']) && $is_admin) {
    $title = $conn->real_escape_string($_POST['title']);
    $age = $conn->real_escape_string($_POST['age']);
    $raw_title = trim($_POST['title'] ?? '');
    
    // Отримуємо категорію з нового поля
    $category = isset($_POST['category_main']) ? $conn->real_escape_string($_POST['category_main']) : 'Малювання';

    $selected_tags = isset($_POST['tags']) ? $_POST['tags'] : [];
    if (!is_array($selected_tags)) {
        $selected_tags = [$selected_tags];
    }

    $subcategory_candidates = [];
    $all_tag_values = [];
    $seen_tags = [];

    $title_generated_tags = [];
    if ($raw_title !== '') {
        $normalized_title = preg_replace('/[^\p{L}\p{N}\s-]+/u', ' ', $raw_title);
        $normalized_title = preg_replace('/[\s-]+/u', ' ', $normalized_title);
        $title_parts = preg_split('/\s+/u', trim($normalized_title));
        if (is_array($title_parts)) {
            foreach ($title_parts as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                $len = function_exists('mb_strlen') ? mb_strlen($part, 'UTF-8') : strlen($part);
                if ($len < 3) {
                    continue;
                }
                $title_generated_tags[] = $part;
                if (count($title_generated_tags) >= 6) {
                    break;
                }
            }
        }
    }

    foreach ($selected_tags as $tag) {
        $tag = trim($tag);
        if ($tag === '') {
            continue;
        }

        $tag_key = function_exists('mb_strtolower') ? mb_strtolower($tag, 'UTF-8') : strtolower($tag);
        if (!isset($seen_tags[$tag_key])) {
            $all_tag_values[] = $conn->real_escape_string($tag);
            $seen_tags[$tag_key] = true;
        }
        $subcategory_candidates[] = $tag;
    }

    foreach ($title_generated_tags as $tag) {
        $tag_key = function_exists('mb_strtolower') ? mb_strtolower($tag, 'UTF-8') : strtolower($tag);
        if (isset($seen_tags[$tag_key])) {
            continue;
        }
        $all_tag_values[] = $conn->real_escape_string($tag);
        $seen_tags[$tag_key] = true;
    }

    $all_tag_values = array_values(array_unique($all_tag_values));
    $subcategory = !empty($subcategory_candidates) ? $subcategory_candidates[0] : 'Тварини';
    $tags_string = implode(',', $all_tag_values);
    
    if ($_FILES['image']['tmp_name']) {
        $source_file = $_FILES['image']['tmp_name'];
        
        // Дебаг
        if (!file_exists($source_file)) {
            echo "<div style='background: #fee; color: #c33; padding: 20px; margin: 20px;'>";
            echo "❌ ОШИБКА: Файл не загружен! Проверь размер и формат.";
            echo "</div>";
            exit;
        }
        
        $img_info = @getimagesize($source_file);
        if (!$img_info) {
            echo "<div style='background: #fee; color: #c33; padding: 20px; margin: 20px;'>";
            echo "❌ ОШИБКА: Это не изображение!";
            echo "</div>";
            exit;
        }
        
        $mime = $img_info['mime'];

        // Створюємо зображення в пам'яті залежно від його типу
        if ($mime == 'image/jpeg' || $mime == 'image/jpg') {
            $image = imagecreatefromjpeg($source_file);
        } elseif ($mime == 'image/png') {
            $image = imagecreatefrompng($source_file);
        } elseif ($mime == 'image/gif') {
            $image = imagecreatefromgif($source_file);
        } else {
            $image = false;
        }

        if ($image) {
            $img_w = imagesx($image);
            $img_h = imagesy($image);

            // Текст водяного знаку
            $text = "ArtSasha"; 
            
            // Шлях до файлу шрифту (переконайтеся, що файл font.ttf лежить поруч з цим скриптом)
            $font_path = __DIR__ . '/font.ttf'; 

            if (file_exists($font_path)) {
                // Великий красивий шрифт для великих фото з телефону (5% від ширини)
                $font_size = max(16, round($img_w * 0.05)); 

                // Розрахунок меж тексту
                $bbox = imageftbbox($font_size, 0, $font_path, $text);
                $text_w = abs($bbox[2] - $bbox[0]);
                $text_h = abs($bbox[5] - $bbox[1]);

                // Позиція в правому нижньому кутку
                $x = $img_w - $text_w - round($img_w * 0.03);
                $y = $img_h - round($img_h * 0.03);

                // Кольори (Білий текст + Чорна тінь для читаємості на будь-якому фоні)
                $shadow_color = imagecolorallocatealpha($image, 0, 0, 0, 70);
                $text_color = imagecolorallocatealpha($image, 255, 255, 255, 40);

                // Нанесення тексту
                imagefttext($image, $font_size, 0, $x + 3, $y + 3, $shadow_color, $font_path, $text);
                imagefttext($image, $font_size, 0, $x, $y, $text_color, $font_path, $text);
            } else {
                // Запасний варіант дрібним стандартним шрифтом GD (якщо font.ttf не знайдено)
                $font = 5;
                $text_w = imagefontwidth($font) * strlen($text);
                $text_h = imagefontheight($font);
                $x = $img_w - $text_w - 15;
                $y = $img_h - $text_h - 15;
                $shadow_color = imagecolorallocatealpha($image, 0, 0, 0, 60);
                $text_color = imagecolorallocatealpha($image, 255, 255, 255, 40);
                imagestring($image, $font, $x + 2, $y + 2, $text, $shadow_color);
                imagestring($image, $font, $x, $y, $text, $text_color);
            }

            // Зберігаємо оброблену картинку на диск
            $filename = 'art_' . time() . '_' . uniqid() . '.jpg';
            $filepath = __DIR__ . '/uploads/' . $filename;
            imagejpeg($image, $filepath, 90);
            imagedestroy($image);
            $image_path = '/uploads/' . $filename;
        } else {
            // Якщо GD не зміг відкрити файл — зберегти оригінал
            $img_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'art_' . time() . '_' . uniqid() . '.' . $img_ext;
            $filepath = __DIR__ . '/uploads/' . $filename;
            copy($source_file, $filepath);
            $image_path = '/uploads/' . $filename;
        }
        
        $image_path_safe = $conn->real_escape_string($image_path);
        $insert_query = "INSERT INTO drawings (title, age, category, subcategory, tags, image_path) VALUES ('$title', '$age', '$category', '$subcategory', '$tags_string', '$image_path_safe')";
        
        if (!$conn->query($insert_query)) {
            echo "<div style='background: #fee; color: #c33; padding: 20px; border-radius: 5px; margin: 20px;'>";
            echo "<strong>❌ ОШИБКА при сохранении в БД:</strong><br>";
            echo htmlspecialchars($conn->error);
            echo "<br><br><strong>Query:</strong><br>";
            echo htmlspecialchars($insert_query);
            echo "</div>";
            exit;
        }

        // Відправка email-сповіщень підписникам
        $subscribers_result = $conn->query("SELECT email FROM subscribers");
        if ($subscribers_result && $subscribers_result->num_rows > 0) {
            $site_url  = 'https://atcraft.com.ua/ArtSasha/';
            $from_name = 'ArtSasha';
            $from_email = 'noreply@atcraft.com.ua';
            $subject = '✨ Нова робота в галереї ArtSasha: ' . htmlspecialchars_decode($title);

            while ($sub = $subscribers_result->fetch_assoc()) {
                $to = $sub['email'];
                $unsubscribe_link = $site_url . 'unsubscribe.php?email=' . rawurlencode($to);
                $body = "Привіт!\r\n\r\n"
                    . "В галереї ArtSasha з'явилась нова робота:\r\n"
                    . "«" . htmlspecialchars_decode($title) . "» (вік: " . htmlspecialchars_decode($age) . ")\r\n\r\n"
                    . "Переглянути: " . $site_url . "\r\n\r\n"
                    . "---\r\n"
                    . "Щоб відписатися: " . $unsubscribe_link . "\r\n";

                $safeTitle = htmlspecialchars(htmlspecialchars_decode($title), ENT_QUOTES, 'UTF-8');
                $safeAge = htmlspecialchars(htmlspecialchars_decode($age), ENT_QUOTES, 'UTF-8');
                $artImage = 'https://atcraft.com.ua/ArtSasha/serve.php?f=' . rawurlencode(basename($image_path));
                $safeArtImage = htmlspecialchars($artImage, ENT_QUOTES, 'UTF-8');
                $safeGallery = htmlspecialchars($site_url . '#gallery', ENT_QUOTES, 'UTF-8');
                $safeUnsubscribe = htmlspecialchars($unsubscribe_link, ENT_QUOTES, 'UTF-8');
                $html = '<div style="margin:0;background:#f2f8fb;padding:28px 12px;font-family:Arial,sans-serif;color:#1e293b;">'
                    . '<div style="max-width:620px;margin:auto;background:#fff;border:1px solid #dce8ef;border-radius:22px;overflow:hidden;">'
                    . '<div style="padding:20px 24px;background:linear-gradient(135deg,#e8fbff,#fff3f1);text-align:center;">'
                    . '<img src="https://atcraft.com.ua/ArtSasha/favicon.ico" alt="aTcraft" width="48" height="48" style="display:block;width:48px;height:48px;margin:0 auto 8px;border:0;">'
                    . '<div style="font-size:24px;font-weight:800;"><span style="color:#38bdf8;">Art</span><span style="color:#f97316;">Sasha</span></div>'
                    . '</div><div style="padding:24px;">'
                    . '<img src="' . $safeArtImage . '" alt="" style="display:block;width:100%;height:auto;max-height:420px;object-fit:cover;border-radius:16px;margin-bottom:20px;">'
                    . '<div style="color:#0ea5a8;font-size:12px;font-weight:800;letter-spacing:1px;text-transform:uppercase;">Нова робота в галереї</div>'
                    . '<h1 style="margin:8px 0 10px;font-size:28px;line-height:1.15;color:#17212b;">' . $safeTitle . '</h1>'
                    . '<p style="margin:0;color:#64748b;font-size:16px;line-height:1.55;">Вік автора: ' . $safeAge . '</p>'
                    . '<a href="' . $safeGallery . '" style="display:inline-block;margin-top:22px;padding:13px 20px;border-radius:10px;background:#5ce1e6;color:#173047;text-decoration:none;font-weight:800;">Переглянути в галереї</a>'
                    . '</div><div style="padding:16px 24px;border-top:1px solid #e8eef2;text-align:center;color:#94a3b8;font-size:12px;">'
                    . '<a href="' . $safeUnsubscribe . '" style="color:#64748b;">Відписатися від оновлень</a></div></div></div>';

                sendSmtpEmail($to, $subject, $html, true);
            }
        }

        echo "<div style='background: #efe; color: #0a0; padding: 20px; margin: 20px; border-radius: 5px;'>";
        echo "✅ <strong>Робота успешно загружена!</strong><br>";
        echo "Перенаправляем на галерею...";
        echo "</div>";
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// 4. ОТРИМАННЯ МАЛЮНКІВ З БД
$conditions = [];
// ВАЖНО: Показуємо тільки роботи що мають image_path (мігровані або нові)
$conditions[] = "(image_path IS NOT NULL AND image_path != '')";

if ($filter_category && $filter_category !== 'Всі') {
    $conditions[] = "category = '$filter_category'";
}
if ($filter_subcategory) {
    $conditions[] = "FIND_IN_SET('$filter_subcategory', tags)";
}
if ($search_query) {
    $conditions[] = "(title LIKE '%$search_query%' OR age LIKE '%$search_query%' OR tags LIKE '%$search_query%')";
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Пагінація: максимум 20 записів на сторінку
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int) $_GET['page'] : 1;
$perPage = isset($_GET['per']) && is_numeric($_GET['per']) && (int)$_GET['per'] > 0 ? (int)$_GET['per'] : 20;
$offset = ($page - 1) * $perPage;

// Загальна кількість робіт для поточного фільтра
$countResult = $conn->query("SELECT COUNT(*) AS total FROM drawings $where");
$totalCount = $countResult ? (int) $countResult->fetch_assoc()['total'] : 0;
$totalPages = (int) ceil($totalCount / $perPage);

$result = $conn->query("SELECT drawings.id, drawings.title, drawings.age, drawings.image_path, COALESCE(comment_counts.comment_count, 0) AS comment_count, drawings.tags, drawings.likes
    FROM drawings
    LEFT JOIN (
        SELECT drawing_id, COUNT(*) AS comment_count
        FROM comments
        GROUP BY drawing_id
    ) AS comment_counts ON drawings.id = comment_counts.drawing_id
    $where ORDER BY drawings.id DESC LIMIT $perPage OFFSET $offset");

// Автоматичний підрахунок статистики для картки "Всі"
$statsQuery = $conn->query("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN category = 'Малювання' THEN 1 ELSE 0 END) AS count_paint,
        SUM(CASE WHEN category = 'Моделювання' THEN 1 ELSE 0 END) AS count_model,
        SUM(CASE WHEN category IN ('Комп\'ютерна графіка', 'Компʼютерна графіка') THEN 1 ELSE 0 END) AS count_digital
    FROM drawings
");

if ($statsQuery) {
    $stats = $statsQuery->fetch_assoc();
} else {
    $stats = ['total' => 0, 'count_paint' => 0, 'count_model' => 0, 'count_digital' => 0];
}

$site_origin = 'https://atcraft.com.ua';
$site_path = '/ArtSasha/';
$base_url = $site_origin . $site_path;

$canonical_params = [];
if ($filter_category && $filter_category !== 'Всі') {
    $canonical_params['category'] = $filter_category;
}
if ($filter_subcategory) {
    $canonical_params['subcategory'] = $filter_subcategory;
}
if ($search_query) {
    $canonical_params['search'] = $search_query;
}
if ($page > 1) {
    $canonical_params['page'] = $page;
}

$canonical_url = $base_url . (empty($canonical_params) ? '' : ('?' . http_build_query($canonical_params)));

$seo_title = 'ArtSasha — Магічний світ творчості Олександри | Галерея робіт';
$seo_description = "Офіційна онлайн-галерея творчих робіт Олександри. Дивіться малюнки, комп'ютерну графіку та унікальні поробки з моделювання в одному місці!";

if ($search_query) {
    $seo_title = 'Пошук «' . $search_query . '» | ArtSasha';
    $seo_description = 'Результати пошуку «' . $search_query . '» у галереї ArtSasha. Знайдено робіт: ' . (int)$totalCount . '.';
} elseif ($filter_category && $filter_category !== 'Всі' && $filter_subcategory) {
    $seo_title = $filter_category . ' — ' . $filter_subcategory . ' | ArtSasha';
    $seo_description = 'Добірка робіт у категорії «' . $filter_category . '» за тегом «' . $filter_subcategory . '». Усього робіт: ' . (int)$totalCount . '.';
} elseif ($filter_category && $filter_category !== 'Всі') {
    $seo_title = $filter_category . ' | ArtSasha — Галерея робіт';
    $seo_description = 'Переглядайте категорію «' . $filter_category . '» у галереї ArtSasha. Усього робіт: ' . (int)$totalCount . '.';
}

$seo_title_esc = htmlspecialchars($seo_title, ENT_QUOTES, 'UTF-8');
$seo_description_esc = htmlspecialchars($seo_description, ENT_QUOTES, 'UTF-8');
$canonical_url_esc = htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8');

$json_ld = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $seo_title,
    'description' => $seo_description,
    'url' => $canonical_url,
    'inLanguage' => 'uk',
    'isPartOf' => [
        '@type' => 'WebSite',
        'name' => 'ArtSasha',
        'url' => $base_url,
    ],
];
?>


<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../favicon.ico?v=20260816" type="image/x-icon">
    <link rel="icon" href="../favicon.ico?v=20260816" sizes="any">
    <link rel="icon" type="image/svg+xml" href="../favicon.svg?v=20260816">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32x32.png?v=20260816">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon-16x16.png?v=20260816">
    <link rel="apple-touch-icon" href="../apple-touch-icon.png?v=20260816">
    <link rel="mask-icon" href="../favicon.svg?v=20260816" color="#39a52a">
    <title><?php echo $seo_title_esc; ?></title>
    <meta name="description" content="<?php echo $seo_description_esc; ?>">
    <link rel="canonical" href="<?php echo $canonical_url_esc; ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="ArtSasha">
    <meta property="og:title" content="<?php echo $seo_title_esc; ?>">
    <meta property="og:description" content="<?php echo $seo_description_esc; ?>">
    <meta property="og:url" content="<?php echo $canonical_url_esc; ?>">
    <meta property="og:image" content="https://atcraft.com.ua/ArtSasha/android-chrome-512x512.png">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?php echo $seo_title_esc; ?>">
    <meta name="twitter:description" content="<?php echo $seo_description_esc; ?>">
    <meta name="twitter:image" content="https://atcraft.com.ua/ArtSasha/android-chrome-512x512.png">
    <script type="application/ld+json"><?php echo json_encode($json_ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <link rel="stylesheet" href="style/assets/header-mobile.css?v=20261027-2">
    <link rel="stylesheet" href="style/st-index.css?v=20261027-11">
    <script>
        (function(){
            var isMobile = window.matchMedia('(max-width:520px)').matches;
            var category = <?php echo json_encode($filter_category); ?>;
            var per = <?php echo isset($_GET['per']) ? (int)$_GET['per'] : 'null'; ?>;
            if (isMobile && category === 'Всі' && per !== 10) {
                var url = new URL(window.location.href);
                url.searchParams.set('per', '10');
                url.searchParams.set('page', '1');
                window.location.replace(url.toString());
            }
        })();
    </script>
</head>
<body>
    <script>
        try {
            var savedTheme = localStorage.getItem('artsashaTheme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-theme');
            } else if (savedTheme === 'halloween') {
                document.body.classList.add('halloween-theme');
            }
        } catch (e) {
            console.warn('Theme init failed', e);
        }
    </script>

    <div class="halloween-decor" aria-hidden="true">
        <span class="halloween-decor-item web web-1">🕸️</span>
        <span class="halloween-decor-item web web-2">🕸️</span>
        <span class="halloween-decor-item spider spider-1">🕷️</span>
        <span class="halloween-decor-item spider spider-2">🕷️</span>
        <span class="halloween-decor-item candle candle-1">🕯️</span>
        <span class="halloween-decor-item candle candle-2">🕯️</span>
        <span class="halloween-decor-item pumpkin pumpkin-1">🎃</span>
        <span class="halloween-decor-item pumpkin pumpkin-2">🎃</span>
        <span class="halloween-decor-item pumpkin pumpkin-3">🎃</span>
    </div>

    <header>
        <div class="header-top">
            <form method="GET" class="search-form">
                <input type="text" name="search" autocomplete="off" placeholder="Пошук за словом..." value="<?php echo htmlspecialchars($search_query); ?>">
                <?php if ($filter_category): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($filter_category); ?>"><?php endif; ?>
                <?php if ($filter_subcategory): ?><input type="hidden" name="subcategory" value="<?php echo htmlspecialchars($filter_subcategory); ?>"><?php endif; ?>
                <button type="submit">Пошук</button>
            </form>
            <div class="logo-wrapper">
                <a href="https://atcraft.com.ua/ArtSasha/index.php" class="logo">
                    <span class="art">Art</span><span class="sasha">Sasha</span>
                    <span class="palette">
                        <img src="style/assets/palette.png" alt="palette" class="palette-img">
                    </span>
                </a>
                <p>Магічний світ творчості Олександри</p>
            </div>
            <div class="header-buttons">
				    
		<a href="donate.php" class="donate-btn">🍦 На морозиво</a>
		<!---->
                <?php if (!$is_admin): ?>
                    <button type="button" class="login-top-btn logout-btn" onclick="openLoginModal()">Увійти</button>
                <?php else: ?>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=logout" class="login-top-btn logout-btn">Вийти 🚪</a>
                <?php endif; ?>
                <div class="theme-picker">
                    <button id="themeMenuToggle" type="button" class="theme-menu-toggle" onclick="toggleThemeMenu()" aria-expanded="false" aria-controls="themeMenu">🎨 Тема ▾</button>
                    <div id="themeMenu" class="theme-menu" role="menu">
                        <button type="button" role="menuitem" data-theme="light" onclick="selectTheme('light')">☀️ Денна</button>
                        <button type="button" role="menuitem" data-theme="dark" onclick="selectTheme('dark')">🌌 Космічна</button>
                        <button type="button" role="menuitem" data-theme="halloween" onclick="selectTheme('halloween')">🎃 Хелловін</button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <?php if ($auth_error): ?><div id="loginModal" class="modal" style="display:flex"><?php else: ?><div id="loginModal" class="modal"><?php endif; ?>
        <div class="modal-content">
            <span class="close-btn" id="closeModalBtn">&times;</span>
            <h2 style="margin-top:0; color:#ff5757;">Увійти</h2>
            <?php if ($auth_error): ?>
                <p style="color:#e53e3e; margin-bottom:16px;">Невірний пароль. Спробуйте ще раз.</p>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="loginPassword">Пароль</label>
                    <input id="loginPassword" type="password" name="password" required placeholder="Введіть пароль" class="form-input-field">
                </div>
                <button type="submit" class="btn">Увійти</button>
            </form>
        </div>
    </div>

    <div class="categories-row">
        <?php foreach ($categories as $cat => $subs): ?>
            <div class="category-card">
                <div class="category-content">
                    <h3><?php echo htmlspecialchars($cat); ?></h3>
                    <p><?php
                        if ($cat === 'Малювання') {
                            echo 'Ручні роботи з фарб, олівців і пастелі.';
                        } elseif ($cat === 'Комп\'ютерна графіка') {
                            echo 'Цифрові ілюстрації';
                        } elseif ($cat === 'Моделювання') {
                            echo 'Поробки з картону, глини, пластиліну';
                        } else {
                            echo 'Усі роботи в одній галереї.';
                        }
                    ?></p>
                    <a class="category-button" href="?category=<?php echo rawurlencode($cat); ?>">Переглянути <?php echo htmlspecialchars($cat); ?></a>
                    <div class="subcategory-list" style="<?php echo $cat === 'Всі' ? 'display:block;' : ''; ?>">
                        <?php if ($cat === 'Всі'): ?>
                            <!-- Автоматична статистика для картки "Всі" -->
                            <div class="site-stats">
                                <div class="stat-row">
                                    <span>🎨 Малюнків:</span>
                                    <strong><?php echo (int)$stats['count_paint']; ?></strong>
                                </div>
                                <div class="stat-row">
                                    <span>🧩 Поробок:</span>
                                    <strong><?php echo (int)$stats['count_model']; ?></strong>
                                </div>
                                <div class="stat-row">
                                    <span>💻 Цифрових робіт:</span>
                                    <strong><?php echo (int)$stats['count_digital']; ?></strong>
                                </div>
                                <div class="stat-row stat-total">
                                    <span>✨ Разом у галереї:</span>
                                    <strong style="color: #ff5757;"><?php echo (int)$stats['total']; ?></strong>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Стандартні підкатегорії для інших карток -->
                            <?php foreach ($subs as $sub): ?>
                                <a class="subcategory-link" href="?category=<?php echo rawurlencode($cat); ?>&subcategory=<?php echo rawurlencode($sub); ?>"><?php echo htmlspecialchars($sub); ?></a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    </div>

<!-- ========================================================================= -->
<!-- ГОЛОВНА ПЕРЕВІРКА: Форма додавання виводиться ТІЛЬКИ якщо Саша авторизована -->
<!-- ========================================================================= -->
<?php if ($is_admin): ?>

    <div class="admin-form-wrap">
        <h3>Привіт, Олександра! Додай свою роботу</h3>
        
        <form method="POST" enctype="multipart/form-data" class="admin-main-form">
            <div class="admin-form-grid">
                <div class="admin-left">
                    <div class="form-group-title">
                        <label class="form-label-text">Назва роботи:</label>
                        <input type="text" name="title" required placeholder="Наприклад: Золота осінь" class="form-input-field">
                    </div>
                    
                    <div class="form-group-age">
                        <label class="form-label-text">Вік малювання:</label>
                        <input type="text" name="age" required placeholder="Наприклад: 7 років" class="form-input-field">
                    </div>
                    
                    <div class="form-group-file">
                        <label class="form-label-text">Оберіть файл малюнка:</label>
                        <input type="file" name="image" required class="form-file-field">
                    </div>
                    
                    <button type="submit"  name="upload" class="form-submit-btn">Зберегти 💾</button>
                </div>

                <div class="admin-right">
                    <div class="tag-category">
                        <span class="admin-side-title">Категорія:</span>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label class="category-radio-label">
                                <input type="radio" name="category_main" value="Моделювання" required onchange="updateSubcategories()"> Моделювання
                            </label>
                            <label class="category-radio-label">
                                <input type="radio" name="category_main" value="Малювання" required onchange="updateSubcategories()"> Малювання
                            </label>
                            <label class="category-radio-label">
                                <input type="radio" name="category_main" value="Комп'ютерна графіка" required onchange="updateSubcategories()"> Комп'ютерна графіка
                            </label>
                        </div>
                    </div>

                    <div class="tag-category" style="margin-top: 14px;">
                        <span class="admin-side-title">Теги (підкатегорії):</span>
                        <div class="tag-checkboxes" id="subcategory-container">
                            <p style="color: #999; font-size: 12px;">Вибери категорію вище</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

<?php endif; ?> 


    <!-- ГАЛЕРЕЯ -->
<h2 class="gallery-title" style="margin-top: 40px;">Галерея робіт</h2>
<div class="gallery" id="gallery">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <?php
                $live_lookup = ($row['title'] ?? '') . ' ' . ($row['tags'] ?? '') . ' ' . ($row['image_path'] ?? '');
                if (function_exists('mb_strtolower')) {
                    $live_lookup = mb_strtolower($live_lookup, 'UTF-8');
                } else {
                    $live_lookup = strtolower($live_lookup);
                }
                $live_classes = 'card';
                if (preg_match('/риб|рыб|fish|fishes|goldfish|koi|акваріум|аквариум/u', $live_lookup)) {
                    $live_classes .= ' hover-fish';
                }
                if (preg_match('/привид|привиди|призрак|привидение|примар|дух|ghost|ghoul|spirit|phantom|specter|spectre/u', $live_lookup)) {
                    $live_classes .= ' hover-ghost';
                }
            ?>
            <div class="<?php echo $live_classes; ?>">
                <!-- Тепер малюнок клікабельний і відкривається повністю -->
                <img src="<?php echo 'serve.php?f=' . basename(htmlspecialchars($row['image_path'])); ?>" 
                     alt="Малюнок Олександри — <?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?>" 
                     onclick="viewFullImage('<?php echo 'serve.php?f=' . basename(htmlspecialchars($row['image_path'], ENT_QUOTES)); ?>', '<?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?>')" 
                     style="cursor: pointer;">
                     
                <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                <p>Вік: <?php echo htmlspecialchars($row['age']); ?></p>
                
                <?php if (!empty($row['tags'])): ?>
                    <!-- Ваш існуючий блок тегів залишається тут без змін -->
                    <div class="tags-block" style="margin: 12px 0 0 0; font-size: 14px; text-align: left; display: flex; flex-wrap: wrap; align-items: center; gap: 8px;">
                        <strong class="tag-label" style="white-space: nowrap;">Теги:</strong>
                        <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                            <?php foreach (explode(',', $row['tags']) as $tag): ?>
                                <a class="tag-item-btn" href="?search=<?php echo urlencode(trim($tag)); ?>">
                                    <?php echo htmlspecialchars(trim($tag)); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Новий оновлений рядок кнопок дій -->
                <div style="display: flex; gap: 10px; align-items: center; justify-content: center; margin-top: 15px; width: 100%;">
                    
                      <!-- Кнопка коментарів з лічильником -->
                    <button class="view-btn" onclick="openComments(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?>')" style="margin-top: 0; width: 200px; background-color: #5ce1e6; color: #333; border-color: #5ce1e6; white-space: nowrap; box-sizing: border-box;">
                        Коментарі 💬 (<span class="btn-comment-count" data-btn-id="<?php echo $row['id']; ?>"><?php echo (int) $row['comment_count']; ?></span>)
                    </button>




                    <!-- Кнопка лайка (залишається вашою ідеальною робочою кнопкою) -->
                    <button class="like-btn" data-id="<?php echo $row['id']; ?>" onclick="sendLike(this, <?php echo $row['id']; ?>)" style="padding: 8px 14px; border-radius: 20px; height: 35px; box-sizing: border-box; display: inline-flex; align-items: center; justify-content: center; margin: 0;">
                        💖&nbsp;<span class="like-count"><?php echo (int)$row['likes']; ?></span>
                    </button>
                    
                </div>
                    <!-- КНОПКА ВИДАЛЕННЯ -->
                    <?php if ($is_admin): ?>
                        <form method="POST" style="margin-top: 10px; display: flex; justify-content: center;" onsubmit="return confirm('Ви впевнені, що хочете видалити малюнок «<?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?>»?');">
                            <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="delete-btn">Видалити ❌</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="grid-column: 1/-1; color: #777;">Тут поки немає жодного малюнка. Олександра, увійди зверху справа та завантаж перший шедевр! ✨</p>
        <?php endif; ?>
    </div>

    <?php if ($totalCount > $perPage): ?>
        <div class="pagination-wrap">
            <div class="page-info">
                Показано <?php echo min($totalCount, $offset + 1); ?> &ndash; <?php echo min($totalCount, $offset + $result->num_rows); ?> з <?php echo $totalCount; ?> робіт
            </div>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-btn">&lt; Назад</a>
                <?php endif; ?>
                <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    if ($startPage > 1) {
                        echo '<a class="page-btn" href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="page-ellipsis">...</span>';
                        }
                    }
                    for ($p = $startPage; $p <= $endPage; $p++) {
                        if ($p == $page) {
                            echo '<span class="page-btn page-active">' . $p . '</span>';
                        } else {
                            echo '<a class="page-btn" href="?' . http_build_query(array_merge($_GET, ['page' => $p])) . '">' . $p . '</a>';
                        }
                    }
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<span class="page-ellipsis">...</span>';
                        }
                        echo '<a class="page-btn" href="?' . http_build_query(array_merge($_GET, ['page' => $totalPages])) . '">' . $totalPages . '</a>';
                    }
                ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-btn">Вперед &gt;</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    <!-- Модальне вікно перегляду зображень (Слайдер) -->
    <div id="imageModal" class="modal" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.85); align-items: center; justify-content: center; -webkit-backdrop-filter: blur(5px); backdrop-filter: blur(5px);">
        
        <!-- Великий хрестик закриття з новим класом -->
        <span class="close-slider" onclick="closeFullImage()">&times;</span>
        
        <!-- Стрілка Вліво (без фіксованого style) -->
        <button class="slider-arrow arrow-left" onclick="changeImage(-1)">&#10094;</button>
        
        <!-- Контейнер для фото та підпису -->
        <div style="max-width: 85%; max-height: 85%; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative;">
            <img id="modalImage" src="" alt="" style="max-width: 100%; max-height: 75vh; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); object-fit: contain;">
            <h3 id="modalImageTitle" style="color: white; margin-top: 15px; font-family: inherit; text-shadow: 0 2px 4px rgba(0,0,0,0.6);"></h3>
        </div>
        
        <!-- Стрілка Вправо (без фіксованого style) -->
        <button class="slider-arrow arrow-right" onclick="changeImage(1)">&#10095;</button>
    </div>

    <div id="commentsModal" class="comments-modal">
        <div class="comments-content">
            <span class="close-comments" id="closeCommentsBtn">&times;</span>
            <h2 id="commentsTitle" style="margin-top:0; color:#5ce1e6;">Коментарі</h2>
            <div id="commentsError" style="color:#e53e3e; display:none; margin-bottom:10px;"></div>
            <div id="commentsList" class="comments-list"></div>
            <form id="commentsForm" class="comment-form">
                <input type="hidden" id="commentsDrawingId" name="drawing_id" value="">
                <input type="text" id="commentName" name="name" autocomplete="name" placeholder="Ваше ім'я" required>
                <textarea id="commentText" name="text" autocomplete="off" placeholder="Ваш коментар" required></textarea>
                <button type="submit" class="btn">Додати коментар</button>
            </form>
        </div>
    </div>

    <footer class="site-footer">
  <div class="footer-container">
    
    <!-- Колонка 1: Про нас -->
    <div class="footer-column">
      <h4>Art Sasha ✨</h4>
      <p>Творча галерея малюнків, поробок та комп'ютерної графіки маленької художниці.</p>
    </div>

    <!-- Колонка 2: Корисні посилання -->
    <div class="footer-column">
      <h4>Корисні посилання</h4>
      <ul class="footer-links">
        <li><a href="about.php">Про авторку</a></li>
                <li><a href="index.php">Всі роботи</a></li>
                <li><a href="The game Road Home/index.html">Пограємо</a></li>
        <li><a href="English%20Board%20Game/src/index.html">English Board Game</a></li>
        <li><a href="donate.php">Підтримати (На морозиво)</a></li>
        <li><a href="mailto:galasirinska@gmail.com">Контакти</a></li>
      </ul>
    </div>

    <!-- Колонка 3: Блок підписки -->
    <div class="footer-column">
      <h4>Підписка на оновлення</h4>
      <p>Отримуйте сповіщення про нові шедеври!</p>
      <?php if ($subscribe_message !== ''): ?>
        <p class="footer-subscribe-message <?php echo $subscribe_status; ?>"><?php echo htmlspecialchars($subscribe_message); ?></p>
      <?php endif; ?>
            <form method="POST" class="footer-subscribe-form" id="subscribe-form">
                <input type="email" name="subscribe_email" autocomplete="email" placeholder="Ваш e-mail..." required>
                <button type="submit" name="subscribe_submit">Підписатись</button>
            </form>
    </div>

  </div>


        <div class="footer-bottom">
            <span>© <?php echo date('Y'); ?> ArtSasha. Всі права захищені.</span>
            <span>Створено з любов'ю для маленької художниці 💖</span>
        </div>
    </footer>

    <button class="btn-to-top" id="scrollToTopBtn" title="Вгору"></button>
    <script>
        var isAdmin = <?php echo $is_admin ? 'true' : 'false'; ?>;
        
        const categorySubcategories = {
            'Моделювання': ['Тварини', 'Люди', 'Посуд', 'Кераміка', 'Повітряний пластилін', 'Поробки', 'Фантазія', 'Інше'],
            'Малювання': ['Тварини', 'Рослини', 'Природа', 'Люди', 'Фантазія', 'Традиції та культура', 'Інше'],
            'Комп\'ютерна графіка': ['Тварини', 'Рослини', 'Природа', 'Люди', 'Фантазія', 'Традиції та культура', 'Інше']
        };

        function updateSubcategories() {
            const selectedCategory = document.querySelector('input[name="category_main"]:checked')?.value;
            const container = document.getElementById('subcategory-container');
            
            if (!selectedCategory || !categorySubcategories[selectedCategory]) {
                container.innerHTML = '<p style="color: #999; font-size: 12px;">Вибери категорію вище</p>';
                return;
            }

            const subcats = categorySubcategories[selectedCategory];
            let html = '<div style="display: flex; flex-wrap: wrap; gap: 8px;">';
            
            subcats.forEach(subcat => {
                html += `
                    <label class="tag-checkbox-label">
                        <input type="checkbox" name="tags[]" value="${subcat}"> ${subcat}
                    </label>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }
    </script>
    <script src="index.js?v=20261022-2"></script>
</body>
</html>


