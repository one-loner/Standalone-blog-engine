<?php
declare(strict_types=1);

$postsFile = __DIR__ . '/posts.json';
$posts = [];

if (is_file($postsFile)) {
    $json = file_get_contents($postsFile);
    $data = json_decode($json ?: '[]', true);

    if (is_array($data)) {
        $posts = $data;
    }
}

function escapeHtml(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function formatPostContent(string $text): string
{
    // Сначала экранируем текст, чтобы не допустить выполнения HTML.
    $text = escapeHtml($text);

    // Превращаем URL в ссылки.
    $text = preg_replace_callback(
        '~https?://[^\s<]+~u',
        static function (array $matches): string {
            $url = $matches[0];

            return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">'
                . $url
                . '</a>';
        },
        $text
    ) ?? $text;

    return nl2br($text, false);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Записи</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="page">
            <div class="container">
        <h1>Standalone blog</h1>
        <h4>Description</h4>


        <?php if (!$posts): ?>
            <p>Пока нет опубликованных записей.</p>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <article class="p">
                    <h2 class="pt">
                        <?= escapeHtml((string)($post['title'] ?? 'Без названия')) ?>
                    </h2>

                    <?php
                    $image = $post['image'] ?? null;

                    if (
                        is_string($image) &&
                        preg_match('/\A[a-f0-9]{32}\.(jpg|png|gif|webp)\z/', $image) &&
                        is_file(__DIR__ . '/uploads/' . $image)
                    ):
                    ?>
                        <img
                            src="<?= 'uploads/' . escapeHtml($image) ?>"
                            alt="<?= escapeHtml((string)($post['title'] ?? '')) ?>"
                            class="centered-image"
                            style="max-width: 100%;"
                        >
                    <?php endif; ?>

                    <p class="pg">
                        <?= formatPostContent((string)($post['content'] ?? '')) ?>
                    </p>
                </article>
                <hr>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
