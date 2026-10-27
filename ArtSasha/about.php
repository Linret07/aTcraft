<?php
// about.php
require_once __DIR__ . '/config.php';
$stats = [
    'total' => 0,
    'count_paint' => 0,
    'count_model' => 0,
    'count_digital' => 0,
];

$host = artsasha_env('DB_HOST', 'localhost');
$user = artsasha_env('DB_USER', 'root');
$pass = artsasha_env('DB_PASSWORD');
$db   = artsasha_env('DB_NAME', 'artsasha_db');

$conn = @new mysqli($host, $user, $pass, $db);
if (!$conn->connect_error) {
    $statsQuery = $conn->query("\n        SELECT\n            COUNT(*) AS total,\n            SUM(CASE WHEN category = 'Малювання' THEN 1 ELSE 0 END) AS count_paint,\n            SUM(CASE WHEN category = 'Моделювання' THEN 1 ELSE 0 END) AS count_model,\n            SUM(CASE WHEN category IN ('Комп\\'ютерна графіка', 'Компʼютерна графіка') THEN 1 ELSE 0 END) AS count_digital\n        FROM drawings\n    ");

    if ($statsQuery) {
        $row = $statsQuery->fetch_assoc();
        $stats['total'] = (int)($row['total'] ?? 0);
        $stats['count_paint'] = (int)($row['count_paint'] ?? 0);
        $stats['count_model'] = (int)($row['count_model'] ?? 0);
        $stats['count_digital'] = (int)($row['count_digital'] ?? 0);
    }

    $conn->close();
}
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
    <meta name="description" content="Про авторку ArtSasha: історія творчості Олександри, напрямки робіт і статистика галереї.">
    <link rel="canonical" href="https://atcraft.com.ua/ArtSasha/about.php">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="ArtSasha">
    <meta property="og:title" content="Про авторку | ArtSasha">
    <meta property="og:description" content="Дізнайтесь більше про Олександру, її творчий шлях та галерею ArtSasha.">
    <meta property="og:url" content="https://atcraft.com.ua/ArtSasha/about.php">
    <meta property="og:image" content="https://atcraft.com.ua/ArtSasha/android-chrome-512x512.png">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Про авторку | ArtSasha">
    <meta name="twitter:description" content="Історія творчості Олександри та напрямки галереї ArtSasha.">
    <meta name="twitter:image" content="https://atcraft.com.ua/ArtSasha/android-chrome-512x512.png">
    <script type="application/ld+json">{"@context":"https://schema.org","@type":"AboutPage","name":"Про авторку | ArtSasha","url":"https://atcraft.com.ua/ArtSasha/about.php","inLanguage":"uk","isPartOf":{"@type":"WebSite","name":"ArtSasha","url":"https://atcraft.com.ua/ArtSasha/"}}</script>
    <title>Про авторку | ArtSasha</title>
    <style>
        :root {
            --accent: #ff5757;
            --accent-soft: #ffe4e4;
            --blue: #5ce1e6;
            --ink: #2f2f2f;
            --muted: #6b7280;
            --bg: #fffaf3;
            --card: #ffffff;
            --border: #f3d7c7;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--ink);
            background: linear-gradient(180deg, #fffef8 0%, var(--bg) 100%);
            line-height: 1.7;
        }

        .page-shell {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px 18px 48px;
        }

        header.site-header {
            width: 100%;
            background: #ffffff;
            box-shadow: 0 8px 24px rgba(255,87,87,0.06);
            padding: 14px 0;
            margin-bottom: 24px;
        }

        .topbar-inner {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 0 18px;
            border-radius: 999px;
            border: 1px solid var(--border);
        }

        @media (min-width: 1700px) {
            .topbar-inner {
                max-width: none;
                width: 100%;
                border-radius: 0;
                border: none;
                padding: 0 32px;
                box-shadow: none;
            }
        }

        body.dark-theme header.site-header {
            background: rgba(15,23,42,0.95);
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
        }

        body.dark-theme .topbar-inner {
            border-color: rgba(148,163,184,0.12);
            background: rgba(17,24,39,0.9);
            color: #f8fafc;
        }

        .brand {
            font-weight: 800;
            color: var(--accent);
            text-decoration: none;
            font-size: 1.05rem;
        }

        .topnav {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .topnav a {
            text-decoration: none;
            color: var(--ink);
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 999px;
        }

        .topnav a:hover {
            background: var(--accent-soft);
            color: var(--accent);
        }

        .hero-card {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 24px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 28px;
            box-shadow: 0 18px 45px rgba(0,0,0,0.06);
            margin-bottom: 24px;
        }

        .hero-text h1 {
            margin: 0 0 10px;
            font-size: clamp(1.8rem, 3vw, 2.7rem);
            color: var(--accent);
        }

        .hero-text p {
            margin: 0 0 16px;
            color: var(--muted);
            font-size: 1.02rem;
        }

        .hero-image {
            width: 100%;
            border-radius: 22px;
            object-fit: cover;
            min-height: 240px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 18px;
        }

        .hero-stats {
            margin-top: 50px;
            margin-left: auto;
            margin-right: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 66.67%;
            text-align: left;
            background: #f7fdf9;
            padding: 14px;
            border-radius: 14px;
            border: 1px dashed #5ce1e6;
            box-sizing: border-box;
        }

        .hero-stat-item {
            display: flex;
            justify-content: space-between;
            font-size: 15px;
            color: #4a5568;
        }

        .hero-stat-item strong {
            color: #2d3748;
        }

        .hero-stat-total {
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            margin-top: 6px;
            font-weight: 700;
        }

        body.dark-theme .hero-stats {
            background: #0f172a;
            border-color: #334155;
        }

        body.dark-theme .hero-stat-item {
            color: #cbd5e1;
        }

        body.dark-theme .hero-stat-item strong {
            color: #f8fafc;
        }

        body.dark-theme .hero-stat-total {
            border-top-color: #334155;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 16px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
        }

        .btn-secondary {
            background: #f8f8f8;
            color: var(--ink);
            border-color: #e5e7eb;
        }

        .info-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 16px;
        }

        .info-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.04);
        }

        .info-card h3 {
            margin-top: 0;
            color: var(--accent);
        }

        .info-card ul {
            margin: 0;
            padding-left: 18px;
            color: var(--muted);
        }

        @media (max-width: 800px) {
            .hero-card { grid-template-columns: 1fr; }
            .hero-stats { width: 100%; }
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="topbar-inner">
            <a class="brand" href="index.php">ArtSasha</a>
            <nav class="topnav">
                <a href="index.php">Головна</a>
                <a href="donate.php">На морозиво</a>
            </nav>
        </div>
    </header>
    <div class="page-shell">

        <section class="hero-card">
            <div class="hero-text">
                <h1>Про авторку</h1>
                <p>Привіт! Я Саша — маленька художниця, яка любить створювати яскраві світи, малювати, фантазувати й ділитися своїми роботами з вами.</p>
                <p>Цей сайт — про творчість, дружню атмосферу та добрі моменти, які народжуються у процесі малювання.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="index.php">Переглянути роботи</a>
                    <a class="btn btn-secondary" href="donate.php">Підтримати</a>
                </div>
                <div class="hero-stats" aria-label="Статистика категорій">
                    <div class="hero-stat-item">
                        <span>🎨 Малюнків:</span>
                        <strong><?php echo (int)$stats['count_paint']; ?></strong>
                    </div>
                    <div class="hero-stat-item">
                        <span>🧩 Поробок:</span>
                        <strong><?php echo (int)$stats['count_model']; ?></strong>
                    </div>
                    <div class="hero-stat-item">
                        <span>💻 Цифрових робіт:</span>
                        <strong><?php echo (int)$stats['count_digital']; ?></strong>
                    </div>
                    <div class="hero-stat-item hero-stat-total">
                        <span>✨ Разом у галереї:</span>
                        <strong style="color: #ff5757;"><?php echo (int)$stats['total']; ?></strong>
                    </div>
                </div>
            </div>
            <img class="hero-image" src="img/Paliienko.jpg" alt="Фото Paliienko">
        </section>

        <div class="info-grid">
            <div class="info-card">
                <h3>Що мене надихає</h3>
                <ul>
                    <li>мальовничі історії та кольори;</li>
                    <li>природа, тварини та казкові образи;</li>
                    <li>ідеї, які народжуються прямо під час творчості.</li>
                </ul>
            </div>
            <div class="info-card">
                <h3>Що тут є</h3>
                <ul>
                    <li>галерея робіт;</li>
                    <li>коментарі та спілкування;</li>
                    <li>можливість підтримати творчий процес.</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
