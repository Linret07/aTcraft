<?php
require_once __DIR__ . '/../db.php';
echo "<style>
body { padding-top: 88px; background: linear-gradient(180deg, #edf7ef 0%, #f4f5f7 100%); font-family: 'Segoe UI', Tahoma, sans-serif; color: #111827; }
.news-detail-page { width: min(1100px, 92%); margin: 26px auto 72px; box-sizing: border-box; }
.news-detail-card { background: rgba(255,255,255,0.94); border: 1px solid rgba(17,24,39,0.08); border-radius: 26px; overflow: hidden; box-shadow: 0 22px 42px rgba(15,23,42,0.08); }
.news-detail-image { width: 100%; height: 540px; overflow: hidden; background: linear-gradient(135deg, #dfeadc, #cfe1d3); border-bottom: 1px solid rgba(17,24,39,0.06); }
.news-detail-image img { width: 100%; height: 100%; object-fit: cover; display: block; }
.news-detail-body { padding: 30px 32px 28px; }
.news-detail-kicker { display: inline-block; margin-bottom: 16px; padding: 8px 14px; border-radius: 999px; background: rgba(36,200,107,0.12); color: #168a4c; font-size: 0.72rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; }
.news-detail-body h1 { margin: 0 0 12px; font-size: clamp(2.4rem, 3vw, 3.8rem); line-height: 1.06; color: #111827; font-weight: 900; letter-spacing: -0.05em; }
.news-detail-meta { margin: 0 0 22px; color: rgba(17,24,39,0.7); font-size: 0.98rem; line-height: 1.6; font-weight: 700; border-bottom: 1px solid rgba(17,24,39,0.08); padding-bottom: 14px; }
.news-detail-content { color: #1f2937; line-height: 1.9; font-size: 1.07rem; max-width: 920px; }
.news-detail-content p { margin: 0 0 18px; }
.news-detail-content br { content: ''; display: block; margin-bottom: 8px; }
.news-detail-content ul, .news-detail-content ol { margin: 0 0 18px 22px; padding-left: 18px; }
.news-detail-content li { margin-bottom: 8px; line-height: 1.75; }
.news-detail-content strong { color: #111827; }
.news-detail-content a { color: #168a4c; font-weight: 700; text-decoration: underline; text-underline-offset: 3px; }
.news-detail-content a:hover { color: #0d6335; }
.news-detail-content img { max-width: 100%; border-radius: 18px; margin: 10px 0 22px; border: 1px solid rgba(17,24,39,0.08); }
.news-detail-content blockquote { margin: 0 0 18px; padding: 16px 18px; border-left: 4px solid #24c86b; background: rgba(36,200,107,0.06); border-radius: 0 14px 14px 0; color: #1b2b20; }
.news-back-link { display: inline-flex; align-items: center; gap: 8px; margin-top: 14px; color: #1c8d4f; text-decoration: none; font-weight: 800; }
@media (max-width: 700px) { body { padding-top: 76px; } .news-detail-page { width: min(100%, 92%); margin-top: 14px; } .news-detail-image { height: 280px; } .news-detail-body { padding: 20px 18px 18px; } .news-detail-body h1 { font-size: 2.1rem; } .news-detail-content { font-size: 1rem; } }
</style>";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$fallbackPosts = [
    1 => [
        'title' => 'Реліз нового сезону',
        'date' => '14 серпня 2026',
        'image' => '/img/fon.jpg',
        'content' => "Після масштабного апдейту з’явилися нові локації, фінальна зона для крафту та оновлені точки респавну.\n\nУ цьому сезоні ми додали нові будівельні зони, покращили генерацію світів, а також зробили велику переробку економіки. Gравцям стало комфортніше пересуватися й організовувати своє життя в світі сервера."
    ],
    2 => [
        'title' => 'Літній турнір на PvP-арені',
        'date' => '09 серпня 2026',
        'image' => '/img/fon.jpg',
        'content' => "Команди змагаються за нагороди, збірки та унікальні предмети. Реєстрація відкривається щодня до 20:00.\n\nЦього разу ми покращили систему балів, додали нові нагороди й спростили процес участі для новачків, щоб кожен міг швидко приєднатися до події."
    ],
    3 => [
        'title' => 'Скоро робочий сезон “Розвиток міст”',
        'date' => '03 серпня 2026',
        'image' => '/img/fon.jpg',
        'content' => "Додаємо нові системи економіки, прокачування територій та спеціальні бонуси для активних гравців.\n\nУ новому сезоні ми робимо ставку на розвиток міст, економічну взаємодію між гравцями та розширення можливостей для будівельників, фермерів і кланів."
    ],
];

$link = atcraft_db_connect();

if ($id <= 0) {
    $row = null;
} elseif ($link) {
    $sql = "SELECT * FROM news WHERE id = " . $id . " LIMIT 1";
    $result = mysqli_query($link, $sql);
    $row = $result && mysqli_num_rows($result) > 0 ? mysqli_fetch_assoc($result) : null;
    mysqli_close($link);
} else {
    $row = $fallbackPosts[$id] ?? null;
}

if (!$row) {
    $row = $fallbackPosts[$id] ?? [
        'title' => 'Новину не знайдено',
        'date' => 'Невідома дата',
        'image' => '/img/fon.jpg',
        'content' => 'Ця новина тимчасово недоступна, але ви можете повернутися до списку новин.'
    ];
}

$rawImage = trim((string)($row['image'] ?? ''));
if ($rawImage === '') {
    $img = '/img/fon.jpg';
} elseif (strpos($rawImage, 'http://') === 0 || strpos($rawImage, 'https://') === 0 || strpos($rawImage, '/uploads/') === 0 || strpos($rawImage, '/img/') === 0) {
    $img = $rawImage;
} else {
    $img = '/img/' . $rawImage;
}

$title = htmlspecialchars($row['title'] ?? 'Новина');
$rawDate = trim((string)($row['date_posted'] ?? $row['date'] ?? ''));
$dateValue = $rawDate === '' || strpos($rawDate, '0000-00-00') === 0 ? date('d.m.Y') : $rawDate;
$date = htmlspecialchars($dateValue, ENT_QUOTES, 'UTF-8');
$content = trim((string)($row['content'] ?? ''));
if ($content === '') {
    $content = "Опис цієї новини поки що не додано.\n\nПоверніться до списку новин, щоб переглянути інші публікації.";
}

if (strip_tags($content) === $content) {
    $paragraphs = preg_split('/\R\s*\R/', $content);
    $contentHtml = '';
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);
        if ($paragraph === '') {
            continue;
        }
        $safeParagraph = nl2br(htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8'));
        $safeParagraph = preg_replace(
            '~(?<!["\'=])(https?://[^\s<]+)~i',
            '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
            $safeParagraph
        );
        $contentHtml .= '<p>' . $safeParagraph . '</p>';
    }
} else {
    $contentHtml = preg_replace('/<(strong|b|em|i|u|ul|ol|li|p|br|h2|h3|blockquote)\b[^>]*>/i', '<$1>', $content);
    $contentHtml = strip_tags($contentHtml, '<strong><b><em><i><u><ul><ol><li><p><br><h2><h3><blockquote>');
    $contentHtml = preg_replace(
        '~(?<!["\'=])(https?://[^\s<]+)~i',
        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
        $contentHtml
    );
}

$seoPlainContent = trim(preg_replace('/\s+/', ' ', strip_tags($content)));
$pageTitle = ($row['title'] ?? 'Новина') . ' | aTcraft';
$pageDescription = function_exists('mb_substr') ? mb_substr($seoPlainContent, 0, 155) : substr($seoPlainContent, 0, 155);
$pageKeywords = trim((string)($row['seo_tags'] ?? ''));
if ($pageKeywords === '') {
    $keywordTitle = function_exists('mb_strtolower') ? mb_strtolower((string)($row['title'] ?? '')) : strtolower((string)($row['title'] ?? ''));
    $pageKeywords = preg_replace('/[^\p{L}\p{N}, ]/u', '', $keywordTitle);
}

require_once __DIR__ . '/../header.php';
echo "<link rel='stylesheet' href='/news/style-news.css'>";

 echo "<link rel='stylesheet' href='style-news.css'>";

 echo "<main class='news-detail-page'>";
 echo "  <article class='news-detail-card'>";
 echo "    <div class='news-detail-image'><img src='" . $img . "' alt='" . $title . "' decoding='async'></div>";
 echo "    <div class='news-detail-body'>";
 echo "      <div class='news-detail-kicker'>Новина</div>";
 echo "      <h1>" . $title . "</h1>";
 echo "      <div class='news-detail-meta'>Опубліковано: " . $date . "</div>";
 echo "      <div class='news-detail-content'>" . $contentHtml . "</div>";
 echo "      <a href='index.php' class='news-back-link'>← Повернутися до списку</a>";
 echo "    </div>";
 echo "  </article>";
 echo "</main>";

echo "</body></html>";
?>
