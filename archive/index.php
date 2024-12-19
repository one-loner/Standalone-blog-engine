<?php
// Получаем список файлов в текущей директории
$files = scandir(__DIR__);
$htmlFiles = [];

// Фильтруем файлы, оставляя только .html и исключая index.php
foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'html' && $file !== 'index.php') {
        $htmlFiles[] = $file;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Архив</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        a {
            display: block;
            margin: 5px 0;
            text-decoration: none;
            color: blue;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Архив</h1>
    <?php if (empty($htmlFiles)): ?>
        <p>Нет доступных HTML файлов.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($htmlFiles as $htmlFile): ?>
                <li>
                    <a href="<?php echo htmlspecialchars($htmlFile); ?>">
                        <?php echo htmlspecialchars(pathinfo($htmlFile, PATHINFO_FILENAME)); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>
</html>
