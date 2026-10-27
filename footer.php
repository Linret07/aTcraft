<footer id="footer" class="new-footer">
    <div class="new-footer-inner">
        <div class="new-footer-brand">
            <div class="logo footer-logo">
                <span class="logo-mark" aria-hidden="true"><svg class="logo-star" viewBox="0 0 64 64" focusable="false"><path d="M32 5 39 25 59 32 39 39 32 59 25 39 5 32 25 25Z"/><path d="m49 9 2 6 6 2-6 2-2 6-2-6-6-2 6-2Z"/></svg></span>
                <a class="logo-first" href="/"><b>aTcraft</b></a>
                <a class="logo-second" href="/"><b>.com.ua</b></a>
            </div>
            <p>Майнкрафт-сервер для виживання, PvP, будівництва та активного життя в світі блоків.</p>
            <div class="footer-server-badge"><span></span> Сервер онлайн · 26.1.2</div>
        </div>

        <div class="footer-promos">
            <a class="footer-promo" href="/ArtSasha/">
                <strong>ArtSasha</strong>
                <span>Творчі проєкти</span>
            </a>
            <div class="footer-promo footer-subscribe" aria-hidden="true">
                <strong>Підписка на оновлення</strong>
                <span>Отримуй новини сайту на email</span>
                <form action="/subscribe.php" method="post">
                    <input type="email" name="email" placeholder="Ваш email" autocomplete="email" required>
                    <button type="submit">Підписатися</button>
                </form>
                <?php if (($_GET['subscribe'] ?? '') === 'ok'): ?>
                    <small class="subscribe-message">Дякуємо! Email додано.</small>
                <?php elseif (($_GET['subscribe'] ?? '') === 'exists'): ?>
                    <small class="subscribe-message">Цей email вже підписаний.</small>
                <?php elseif (($_GET['subscribe'] ?? '') === 'error'): ?>
                    <small class="subscribe-message subscribe-error">Перевірте email і спробуйте ще раз.</small>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer-promo footer-subscribe footer-subscribe-featured">
            <div class="subscribe-icon">✉</div>
            <span class="subscribe-kicker">БУДЬ У КУРСІ</span>
            <h3>Підписка на оновлення</h3>
            <p>Новини та важливі оновлення — прямо на вашу пошту.</p>
            <form action="/subscribe.php" method="post">
                <input type="email" name="email" placeholder="Ваш email" autocomplete="email" required>
                <button type="submit">Підписатися</button>
            </form>
            <?php if (($_GET['subscribe'] ?? '') === 'ok'): ?>
                <small class="subscribe-message">Дякуємо! Email додано.</small>
            <?php elseif (($_GET['subscribe'] ?? '') === 'exists'): ?>
                <small class="subscribe-message">Цей email вже підписаний.</small>
            <?php elseif (($_GET['subscribe'] ?? '') === 'error'): ?>
                <small class="subscribe-message subscribe-error">Перевірте email і спробуйте ще раз.</small>
            <?php endif; ?>
        </div>

        <div class="new-footer-col">
            <a class="footer-nav-artsasha" href="/ArtSasha/"><strong>ArtSasha</strong><span>Творчі проєкти</span></a>
            <h3>НАВІГАЦІЯ</h3>
            <a href="/">Головна</a>
            <a href="/news/">Новини</a>
            <a href="/plugins.php">Плагіни</a>
            <a href="/shop.php">Магазин</a>
        </div>

        <div class="new-footer-col">
            <h3>ПРО СЕРВЕР</h3>
            <a href="#">Виживання</a>
            <a href="#">PvP</a>
            <a href="#">Міста</a>
            <a href="#">Економіка</a>
        </div>
    </div>
    <div class="new-footer-bottom">
        <span>© <?= date('Y') ?> aTcraft.com.ua</span>
        <span>Створено для гравців Minecraft</span>
    </div>
</footer>

