<?php
function archivePosts() {
    $archiveDir = 'archive/';
    $uploadedDir = 'uploads/';
    $archiveUploadedDir = $archiveDir . 'uploads/';
    $timestamp = date('Y-m-d_H-i-s'); // Формат даты и времени
    $archiveFile = $archiveDir . "$timestamp.html";

    // Проверяем, существует ли директория для архива
    if (!is_dir($archiveDir)) {
        if (!mkdir($archiveDir, 0755, true)) {
            return "Не удалось создать директорию для архива.";
        }
    }

    // Проверяем, существует ли директория для загруженных файлов
    if (!is_dir($archiveUploadedDir)) {
        if (!mkdir($archiveUploadedDir, 0755, true)) {
            return "Не удалось создать директорию для архива загруженных файлов.";
        }
    }

    // Перемещаем содержимое posts.html в архив
    if (file_exists('posts.html')) {
        // Читаем содержимое posts.html
        $content = file_get_contents('posts.html');
        
        // Добавляем HTML-разметку в начало содержимого
        $htmlHeader = "<html><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"><link rel=\"stylesheet\" href=\"styles.css\"></head><body>";
        $content = $htmlHeader . $content . "</body></html>"; // Закрываем body и html теги

        // Записываем содержимое в архивный файл
        if (file_put_contents($archiveFile, $content) !== false) {
            // Создаем новый пустой posts.html
            file_put_contents('posts.html', '');

            // Перемещаем содержимое папки uploads в archive/uploads
            if (is_dir($uploadedDir)) {
                $files = scandir($uploadedDir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $sourceFile = $uploadedDir . $file;
                        $destinationFile = $archiveUploadedDir . $file;
                        if (!copy($sourceFile, $destinationFile)) {
                            return "Ошибка при копировании файла $file.";
                        }
                    }
                }
            }

            return "Посты успешно архивированы.";
        } else {
            return "Ошибка при записи в архивный файл.";
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
        <a href="index.php">Вернуться на главную страницу</a><br>
        <a href="admin.php">Вернуться на страницу администрирования</a><br>
        <a href="archive/">Просмотреть архив</a>
    </div>
</body>
</html>
