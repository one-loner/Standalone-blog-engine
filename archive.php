<?php
function archivePosts() {
    $archiveDir = 'archive/';
    $timestamp = date('Y-m-d_H-i-s'); // Формат даты и времени
    $archiveFile = $archiveDir . "posts_$timestamp.html";

    // Проверяем, существует ли директория для архива
    if (!is_dir($archiveDir)) {
        if (!mkdir($archiveDir, 0755, true)) {
            return "Не удалось создать директорию для архива.";
        }
    }

    // Перемещаем содержимое posts.html в архив
    if (file_exists('posts.html')) {
        if (rename('posts.html', $archiveFile)) {
            // Создаем новый пустой posts.html
            file_put_contents('posts.html', '');
            return "Посты успешно архивированы.";
        } else {
            return "Ошибка при перемещении файла posts.html в архив.";
        }
    } else {
        return "Файл posts.html не найден.";
    }
}

// Выполняем архивирование при загрузке страницы
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $message = archivePosts();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Архивирование постов</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class='page'>
        <h2 class='pt'>Результат архивирования</h2>
        <p><?php echo $message; ?></p>
        <a href="index.php">Вернуться на главную страницу</a>
    </div>
</body>
</html>
