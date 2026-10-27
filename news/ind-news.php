<?php
require_once __DIR__ . '/../db.php';
$link = atcraft_db_connect();

$sql = "SELECT * FROM news ORDER BY date_posted DESC";
$result = mysqli_query($link, $sql);

while($row = mysqli_fetch_assoc($result)) {
    echo "<div class='news-card'>"; // Змінив назву класу на card для стилю

    // 1. Додаємо картинку. Припустимо, ви назвали колонку в базі 'image'
    if (!empty($row['image'])) {
        echo "<div class='card-img-container'>";
        echo "<img src='/img/" . htmlspecialchars($row['image']) . "' alt='" . htmlspecialchars($row['title']) . "' class='news-image'>";
        echo "</div>";
    }

    echo "<div class='card-body'>";
    echo "<h2>" . htmlspecialchars($row['title']) . "</h2>";
    echo "<p class='date'>Опубліковано: " . $row['date_posted'] . "</p>";

    // excerpt
    $plain = strip_tags($row['content']);
    if(function_exists('mb_substr')){
        $excerpt = mb_strlen($plain) > 220 ? mb_substr($plain,0,220) . '...' : $plain;
    } else {
        $excerpt = strlen($plain) > 220 ? substr($plain,0,220) . '...' : $plain;
    }
    echo "<p class='excerpt'>" . htmlspecialchars($excerpt) . "</p>";

    // actions button
    echo "<div class='card-actions'><a class='btn-detail' href='view-news.php?id=" . (int)$row['id'] . "'>Детальніше</a></div>";
    echo "</div>";

    echo "</div>";
}
?>