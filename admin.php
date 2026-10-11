<?php
declare(strict_types=1);

session_start();

/*
 * Настройки администратора.
 * Для хеша выполните:
 * php -r "echo password_hash('ваш_пароль', PASSWORD_DEFAULT), PHP_EOL;"
 */
const ADMIN_LOGIN = 'admin';
const ADMIN_PASSWORD_HASH = 'ВСТАВЬТЕ_ХЕШ_СЮДА';

const POSTS_FILE = __DIR__ . '/posts.json';
const UPLOAD_DIR = __DIR__ . '/uploads';
const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5 МБ

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirectToSelf(): never
{
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

function loadPosts(): array
{
    if (!is_file(POSTS_FILE)) {
        return [];
    }

    $json = file_get_contents(POSTS_FILE);
    $posts = json_decode($json ?: '[]', true);

    return is_array($posts) ? $posts : [];
}

function savePosts(array $posts): void
{
    $json = json_encode(
        array_values($posts),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    if ($json === false || file_put_contents(POSTS_FILE, $json, LOCK_EX) === false) {
        throw new RuntimeException('Не удалось сохранить записи. Проверьте права доступа к папке.');
    }
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function checkCsrfToken(): void
{
    $sentToken = $_POST['csrf_token'] ?? '';
    $savedToken = $_SESSION['csrf_token'] ?? '';

    if (!is_string($sentToken) || !is_string($savedToken) ||
        !hash_equals($savedToken, $sentToken)) {
        http_response_code(403);
        exit('Ошибка проверки формы. Обновите страницу и попробуйте снова.');
    }
}

/**
 * Экранирует текст и превращает URL в кликабельные ссылки.
 */
function formatContent(string $content): string
{
    $escaped = escape($content);

    $linked = preg_replace_callback(
        '~https?://[^\s<]+~u',
        static function (array $matches): string {
            $url = $matches[0];

            // Убираем распространённую пунктуацию с конца ссылки.
            $trailing = '';
            while ($url !== '' && preg_match('/[.,!?;:]$/u', $url)) {
                $trailing = substr($url, -1) . $trailing;
                $url = substr($url, 0, -1);
            }

            if ($url === '') {
                return $matches[0];
            }

            return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">'
                . $url . '</a>' . $trailing;
        },
        $escaped
    );

    return nl2br($linked ?? $escaped, false);
}

function handleImageUpload(): ?string
{
    if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES['image'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Не удалось загрузить изображение.');
    }

    if ($file['size'] > MAX_IMAGE_SIZE) {
        throw new RuntimeException('Размер изображения не должен превышать 5 МБ.');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Некорректный загруженный файл.');
    }

    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new RuntimeException('Загруженный файл не является изображением.');
    }

    $allowedTypes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_GIF  => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];

    $imageType = $imageInfo[2];
    if (!isset($allowedTypes[$imageType])) {
        throw new RuntimeException('Разрешены только JPG, PNG, GIF и WEBP.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$imageType];
    $destination = UPLOAD_DIR . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Не удалось сохранить изображение.');
    }

    return $filename;
}

$error = '';
$loginError = '';

// Вход и выход.
if (isset($_GET['logout'])) {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
    redirectToSelf();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $login = (string)($_POST['login'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (
        hash_equals(ADMIN_LOGIN, $login) &&
        password_verify($password, ADMIN_PASSWORD_HASH)
    ) {
        session_regenerate_id(true);
        $_SESSION['authorized'] = true;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        redirectToSelf();
    }

    $loginError = 'Неверный логин или пароль.';
}

// До входа показываем только форму авторизации.
if (empty($_SESSION['authorized'])):
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вход</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="page">
        <h2 class="pt">Вход в панель управления</h2>

        <?php if ($loginError !== ''): ?>
            <p><?= escape($loginError) ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="action" value="login">
            <input type="text" name="login" placeholder="Логин"
                   autocomplete="username" required>
            <br>
            <input type="password" name="password" placeholder="Пароль"
                   autocomplete="current-password" required>
            <br>
            <button type="submit" class="bt">Войти</button>
        </form>
    </div>
</body>
</html>
<?php
exit;
endif;

// Добавление и удаление записей доступны только после авторизации.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrfToken();

    try {
        $posts = loadPosts();
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $title = trim((string)($_POST['title'] ?? ''));
            $content = trim((string)($_POST['content'] ?? ''));

            if ($title === '' || $content === '') {
                throw new RuntimeException('Заполните название и текст записи.');
            }

            $image = handleImageUpload();

            array_unshift($posts, [
                'id' => bin2hex(random_bytes(16)),
                'title' => $title,
                'content' => $content,
                'image' => $image,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            savePosts($posts);
            redirectToSelf();
        }

        if ($action === 'delete') {
            $id = (string)($_POST['id'] ?? '');
            $found = false;

            foreach ($posts as $index => $post) {
                if (($post['id'] ?? '') === $id) {
                    $image = $post['image'] ?? null;

                    if (is_string($image) && preg_match('/\A[a-f0-9]{32}\.(jpg|png|gif|webp)\z/', $image)) {
                        $imagePath = UPLOAD_DIR . '/' . $image;
                        if (is_file($imagePath)) {
                            unlink($imagePath);
                        }
                    }

                    unset($posts[$index]);
                    $found = true;
                    break;
                }
            }

            if ($found) {
                savePosts($posts);
            }

            redirectToSelf();
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$posts = loadPosts();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Standalone blog</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="page">
    <p><a href="?logout=1">Выйти</a></p>

    <?php if ($error !== ''): ?>
        <p><?= escape($error) ?></p>
    <?php endif; ?>

    <h2 class="pt">Добавить новую запись</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
        <input type="hidden" name="action" value="add">

        <input type="text" name="title" placeholder="Название записи" required>
        <br>
        <textarea name="content" placeholder="Текст записи" required></textarea>
        <br><br>
        <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
        <button type="submit" class="bt">Опубликовать</button>
    </form>

    <br><br><hr><br><br>
    <h2 class="pt">Существующие записи</h2>

    <?php foreach ($posts as $post): ?>
        <div class="p">
            <h2 class="pt"><?= escape((string)($post['title'] ?? '')) ?></h2>

            <?php
            $image = $post['image'] ?? null;
            if (is_string($image) && preg_match('/\A[a-f0-9]{32}\.(jpg|png|gif|webp)\z/', $image)):
            ?>
                <img
                    src="<?= 'uploads/' . escape($image) ?>"
                    alt="<?= escape((string)($post['title'] ?? '')) ?>"
                    style="max-width: 100%;"
                    class="centered-image"
                >
            <?php endif; ?>

            <p class="pg"><?= formatContent((string)($post['content'] ?? '')) ?></p>

            <form method="post" onsubmit="return confirm('Удалить эту запись?');">
                <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= escape((string)($post['id'] ?? '')) ?>">
                <button type="submit" class="bt">Удалить</button>
            </form>
        </div>
        <hr>
    <?php endforeach; ?>

    <p><a href="archive.php">Архивировать записи</a></p>
</div>
</body>
</html>
