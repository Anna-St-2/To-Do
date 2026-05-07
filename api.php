<?php
/**
 * API-обработчик для AJAX-запросов
 * Все запросы от JavaScript проходят через этот файл
 */

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/modules/auth/Auth.php';
require_once __DIR__ . '/modules/tasks/Task.php';
require_once __DIR__ . '/includes/helpers.php';

// Определяем метод запроса
$method = $_SERVER['REQUEST_METHOD'];

// Получаем действие из GET-параметра
$action = $_GET['action'] ?? '';

// Для POST-запросов получаем тело запроса
$input = [];
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];
}

// Проверка CSRF-токена для всех POST-запросов (уже после заполнения $input)
if ($method === 'POST') {
    // Берём токен из HTTP-заголовка (отправляет JavaScript)
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        jsonResponse(['success' => false, 'error' => 'CSRF-токен недействителен'], 403);
    }
}

// Все API-запросы требуют авторизации
if (!Auth::isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Необходимо войти в систему'], 401);
}

$userId = (int) $_SESSION['user_id'];

// Маршрутизация API-запросов
switch ($action) {
    // === РАБОТА С ЗАДАЧАМИ ===

    case 'task_add':
        if ($method !== 'POST') {
            jsonResponse(['success' => false, 'error' => 'Метод не поддерживается'], 405);
        }
        $result = Task::create($userId, $input['title'] ?? '');
        if ($result['success']) {
            jsonResponse(['success' => true, 'task_id' => $result['id']], 201);
        } else {
            jsonResponse(['success' => false, 'error' => $result['error']], 400);
        }
        break;

    case 'task_list':
        if ($method !== 'GET') {
            jsonResponse(['success' => false, 'error' => 'Метод не поддерживается'], 405);
        }
        $filter = $_GET['filter'] ?? 'all';
        $allowedFilters = ['all', 'active', 'completed'];
        if (!in_array($filter, $allowedFilters)) {
            $filter = 'all';
        }
        $result = Task::getAll($userId, $filter);
        jsonResponse(['success' => true, 'tasks' => $result['tasks']]);
        break;

    case 'task_toggle':
        if ($method !== 'POST') {
            jsonResponse(['success' => false, 'error' => 'Метод не поддерживается'], 405);
        }
        $taskId = (int) ($input['task_id'] ?? 0);
        if ($taskId <= 0) {
            jsonResponse(['success' => false, 'error' => 'Некорректный ID задачи'], 400);
        }
        $result = Task::toggle($taskId, $userId);
        if ($result['success']) {
            jsonResponse(['success' => true, 'is_completed' => $result['is_completed']]);
        } else {
            jsonResponse(['success' => false, 'error' => $result['error']], 404);
        }
        break;

    case 'task_delete':
        if ($method !== 'POST') {
            jsonResponse(['success' => false, 'error' => 'Метод не поддерживается'], 405);
        }
        $taskId = (int) ($input['task_id'] ?? 0);
        if ($taskId <= 0) {
            jsonResponse(['success' => false, 'error' => 'Некорректный ID задачи'], 400);
        }
        $result = Task::delete($taskId, $userId);
        if ($result['success']) {
            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['success' => false, 'error' => $result['error']], 404);
        }
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Неизвестное действие'], 404);
}