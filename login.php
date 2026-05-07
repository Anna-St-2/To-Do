<?php
/**
 * Страница входа
 */

// Настройка безопасных сессионных cookies ДО запуска сессии
ini_set('session.cookie_httponly', 1);     // Запрет доступа к cookie из JavaScript
ini_set('session.cookie_samesite', 'Lax'); // Защита от CSRF
ini_set('session.cookie_secure', 0);       // 0 для localhost (на продакшене 1)
ini_set('session.use_strict_mode', 1);     // Защита от session fixation

session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/modules/auth/Auth.php';
require_once __DIR__ . '/includes/helpers.php';

setSecurityHeaders();
forceHttps();

// Если уже авторизован — на дашборд
Auth::redirectIfLoggedIn();

$error = '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу.';
    } else {
        $result = Auth::login($_POST['login'] ?? '', $_POST['password'] ?? '');
        if ($result['success']) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — To-Do List</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">Вход</h1>
            <p class="auth-subtitle">Войдите в свой аккаунт</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= h($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="auth-form">
                <?= csrfField() ?>
                <div class="form-group">
                    <label for="login">Логин или Email</label>
                    <input
                        type="text"
                        id="login"
                        name="login"
                        class="form-input"
                        value="<?= h($_POST['login'] ?? '') ?>"
                        required
                        autocomplete="username"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-full">Войти</button>
            </form>

            <p class="auth-link">
                Нет аккаунта? <a href="register.php">Зарегистрироваться</a>
            </p>
        </div>
    </div>
</body>
</html>