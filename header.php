<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$adminLoggedIn = !empty($_SESSION['admin_logged_in']);
?>
<!doctype html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'aTcraft', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="canonical" href="https://atcraft.com.ua<?= htmlspecialchars(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:site_name" content="aTcraft">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle ?? 'aTcraft — Minecraft сервер', ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription ?? 'Minecraft сервер aTcraft для виживання, PvP, міст та економіки.', ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://atcraft.com.ua<?= htmlspecialchars(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', ENT_QUOTES, 'UTF-8') ?>">
    <meta name="theme-color" content="#102f3c">
    <?php if (!empty($pageDescription)): ?>
        <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <?php if (!empty($pageKeywords)): ?>
        <meta name="keywords" content="<?= htmlspecialchars($pageKeywords, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="alternate icon" href="/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/style-main.css?v=20260816-18">
</head>
<body data-theme="dark">
<header class="site-header">
    <div class="header">
        <div class="logo">
            <span class="logo-mark" aria-hidden="true"><svg class="logo-star" viewBox="0 0 64 64" focusable="false"><path d="M32 5 39 25 59 32 39 39 32 59 25 39 5 32 25 25Z"/><path d="m49 9 2 6 6 2-6 2-2 6-2-6-6-2 6-2Z"/></svg></span>
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

        <form class="site-search" action="/plugins.php" method="get" role="search">
            <label for="site-search-input">Пошук</label>
            <input id="site-search-input" type="search" name="q" placeholder="Пошук..." autocomplete="off">
            <button type="submit" aria-label="Знайти">⌕</button>
        </form>

        <div class="header-actions">
            <button class="theme-toggle" type="button" aria-label="Переключити тему">☀️</button>
            <?php if ($adminLoggedIn): ?>
                <a class="header-button admin-logout-button" href="/?logout=1">Вийти</a>
            <?php else: ?>
                <button type="button" class="header-button key-button" data-open-admin-modal="1" aria-label="Вхід в адмін-панель" title="Вхід">🔑</button>
            <?php endif; ?>
        </div>
    </div>
</header>

<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const body = document.body;
        const toggle = document.querySelector('.theme-toggle');
        const menuToggle = document.querySelector('.mobile-menu-toggle');
        const nav = document.querySelector('.main-nav');
        const modal = document.getElementById('admin-login-modal');
        const openButtons = document.querySelectorAll('[data-open-admin-modal]');
        const closeButtons = document.querySelectorAll('[data-close-admin-modal]');

        function applyTheme(theme) {
            body.setAttribute('data-theme', theme);
            localStorage.setItem('atcraft-theme', theme);
            if (!toggle) return;
            toggle.textContent = theme === 'dark' ? '☀️' : '🌙';
            toggle.setAttribute('aria-label', theme === 'dark' ? 'Увімкнути світлу тему' : 'Увімкнути темну тему');
        }

        const savedTheme = localStorage.getItem('atcraft-theme') || 'dark';
        applyTheme(savedTheme);

        if (toggle) {
            toggle.addEventListener('click', function () {
                const nextTheme = body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                applyTheme(nextTheme);
            });
        }

        if (menuToggle && nav) {
            menuToggle.addEventListener('click', function () {
                const isOpen = nav.classList.toggle('open');
                menuToggle.setAttribute('aria-expanded', String(isOpen));
            });
            nav.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    nav.classList.remove('open');
                    menuToggle.setAttribute('aria-expanded', 'false');
                });
            });
        }

        document.querySelectorAll('[data-copy-ip]').forEach(function (button) {
            button.addEventListener('click', function () {
                const value = button.getAttribute('data-copy-ip') || '';
                const done = function () {
                    const original = button.textContent;
                    button.textContent = 'Скопійовано ✓';
                    setTimeout(function () { button.textContent = original; }, 1600);
                };
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(value).then(done);
                } else {
                    const field = document.createElement('textarea');
                    field.value = value;
                    document.body.appendChild(field);
                    field.select();
                    document.execCommand('copy');
                    field.remove();
                    done();
                }
            });
        });

        if (modal) {
            openButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    modal.classList.add('open');
                    modal.setAttribute('aria-hidden', 'false');
                });
            });

            closeButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    modal.classList.remove('open');
                    modal.setAttribute('aria-hidden', 'true');
                });
            });

            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    modal.classList.remove('open');
                    modal.setAttribute('aria-hidden', 'true');
                }
            });
        }

            document.querySelectorAll('form[action="/admin.php"]').forEach(function (form) {
            const deleteField = form.querySelector('input[name="news_action"][value="delete"], input[name="plugin_action"][value="delete"], input[name="privilege_action"][value="delete"]');
            if (!form.querySelector('input[name="redirect_to"]') && deleteField) {
                const redirectInput = document.createElement('input');
                redirectInput.type = 'hidden';
                redirectInput.name = 'redirect_to';
                redirectInput.value = '/';
                form.appendChild(redirectInput);
            }
        });

        const listToggle = document.querySelector('[data-admin-list-toggle]');
        const fullList = document.querySelector('[data-admin-full-list]');
        if (listToggle && fullList) {
            listToggle.addEventListener('click', function () {
                const isOpen = !fullList.hidden;
                fullList.hidden = isOpen;
                listToggle.textContent = isOpen ? 'Повний перелік' : 'Сховати перелік';
            });
        }
    });
})();
</script>
