<?php
session_start();

// Замените на свой логин и хеш пароля.
// Хеш можно получить командой: php -r "echo password_hash('ваш_пароль', PASSWORD_DEFAULT), PHP_EOL;"
const ADMIN_LOGIN = 'admin';
const ADMIN_PASSWORD_HASH = '$2y$10$ВСТАВЬТЕ_СЮДА_ХЕШ_ПАРОЛЯ';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';

    if (
        hash_equals(ADMIN_LOGIN, $login) &&
        password_verify($password, ADMIN_PASSWORD_HASH)
    ) {
        session_regenerate_id(true);
        $_SESSION['authorized'] = true;

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $loginError = 'Неверный логин или пароль.';
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (empty($_SESSION['authorized'])) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Вход</title>
    </head>
    <body>
        <h2>Вход в панель управления</h2>

        <?php if (!empty($loginError)): ?>
            <p><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="action" value="login">
            <input type="text" name="login" placeholder="Логин" required autocomplete="username">
            <input type="password" name="password" placeholder="Пароль" required autocomplete="current-password">
            <button type="submit">Войти</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// Ниже оставьте обработку записей и HTML из вашего скрипта.
