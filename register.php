<?php
/**
 * Страница регистрации
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

// Если уже авторизован — на дашборд
Auth::redirectIfLoggedIn();

$error = '';

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Ошибка безопасности. Обновите страницу.';
    } else {
        $result = Auth::register(
            $_POST['username'] ?? '',
            $_POST['email'] ?? '',
            $_POST['password'] ?? '',
            $_POST['confirm_password'] ?? ''
        );
        if ($result['success']) {
            header('Location: login.php?registered=1');
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
    <title>Регистрация — To-Do List</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">Регистрация</h1>
            <p class="auth-subtitle">Создайте новый аккаунт</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= h($error) ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">Регистрация успешна! Теперь войдите.</div>
            <?php endif; ?>

            <form method="POST" action="register.php" class="auth-form">
                <?= csrfField() ?>
                <div class="form-group">
                    <label for="username">Логин</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-input"
                        value="<?= h($_POST['username'] ?? '') ?>"
                        required
                        minlength="3"
                        maxlength="50"
                        autocomplete="username"
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input"
                        value="<?= h($_POST['email'] ?? '') ?>"
                        required
                        autocomplete="email"
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
                        minlength="6"
                        autocomplete="new-password"
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">Подтверждение пароля</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-input"
                        required
                        minlength="6"
                        autocomplete="new-password"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-full">Зарегистрироваться</button>
            </form>

            <p class="auth-link">
                Уже есть аккаунт? <a href="login.php">Войти</a>
            </p>
        </div>
    </div>
</body>
</html>