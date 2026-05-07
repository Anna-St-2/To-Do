<?php
/**
 * Дашборд — основной интерфейс со списком задач
 */

// Настройка безопасных сессионных cookies ДО запуска сессии
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', 0);       // 0 для localhost
ini_set('session.use_strict_mode', 1);

session_start();

// Подключаем зависимости (порядок важен!)
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/modules/auth/Auth.php';
require_once __DIR__ . '/modules/tasks/Task.php';

// Устанавливаем защитные заголовки
setSecurityHeaders();

// Требуем авторизацию
Auth::requireLogin();

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Получаем задачи (изначально все)
$tasksResult = Task::getAll($userId, 'all');
$tasks = $tasksResult['tasks'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои задачи — To-Do List</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <!-- Верхняя панель -->
        <header class="app-header">
            <h1 class="app-logo">To-Do List</h1>
            <div class="user-info">
                <span class="user-greeting">Здравствуйте, <strong><?= h($username) ?></strong>!</span>
                <a href="logout.php" class="btn btn-outline btn-sm">Выйти</a>
            </div>
        </header>

        <main class="app-main">
            <!-- Форма добавления задачи -->
            <div class="add-task-block">
                <form id="addTaskForm" class="add-task-form">
                    <input
                        type="text"
                        id="taskTitle"
                        class="form-input"
                        placeholder="Что нужно сделать?"
                        maxlength="255"
                        required
                    >
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </form>
            </div>

            <!-- Фильтры -->
            <div class="filters">
                <button class="filter-btn active" data-filter="all">Все</button>
                <button class="filter-btn" data-filter="active">Активные</button>
                <button class="filter-btn" data-filter="completed">Выполненные</button>
            </div>

            <!-- Сообщение о загрузке -->
            <div id="loadingIndicator" class="loading" style="display: none;">
                Загрузка...
            </div>

            <!-- Сообщение об ошибке -->
            <div id="errorMessage" class="alert alert-error" style="display: none;"></div>

            <!-- Список задач -->
            <div id="taskList" class="task-list">
                <?php if (empty($tasks)): ?>
                    <div class="empty-state">
                        <p>Нет задач</p>
                        <p class="empty-hint">Добавьте первую задачу выше</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <div class="task-item <?= $task['is_completed'] ? 'completed' : '' ?>" data-id="<?= $task['id'] ?>">
                            <label class="task-checkbox-label">
                                <input
                                    type="checkbox"
                                    class="task-checkbox"
                                    <?= $task['is_completed'] ? 'checked' : '' ?>
                                    onchange="toggleTask(<?= $task['id'] ?>)"
                                >
                                <span class="checkmark"></span>
                            </label>
                            <span class="task-title"><?= h($task['title']) ?></span>
                            <button
                                class="task-delete-btn"
                                onclick="deleteTask(<?= $task['id'] ?>)"
                                title="Удалить задачу"
                            >
                                ×
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Передаём CSRF-токен в JavaScript -->
    <script>
        window.CSRF_TOKEN = '<?= generateCsrfToken() ?>';
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>