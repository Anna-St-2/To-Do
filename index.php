<?php
session_start(); // запускаем сессию

require_once 'modules/auth/Auth.php';
require_once 'modules/tasks/Task.php';

// получаем путь без GET-параметров
$uri = strtok($_SERVER["REQUEST_URI"], '?');
$method = $_SERVER['REQUEST_METHOD']; // GET / POST

// функция для JSON-ответа
function jsonResponse($data, $code = 200) {
    http_response_code($code); // HTTP-код
    header('Content-Type: application/json'); // JSON заголовок
    echo json_encode($data);
    exit;
}

// @SECURITY_MODULE

// главная страница
if ($uri === '/' && $method === 'GET') {
    if (Auth::isLoggedIn()) {
        require 'views/dashboard.php';
    } else {
        require 'views/login.php';
    }
}

// страница входа
elseif ($uri === '/login' && $method === 'GET') {
    require 'views/login.php';
}

// обработка входа
elseif ($uri === '/login' && $method === 'POST') {
    $res = Auth::login($_POST['login'], $_POST['password']);
    if ($res === true) header("Location: /dashboard");
    else $error = $res;
    require 'views/login.php';
}

// регистрация
elseif ($uri === '/register' && $method === 'GET') {
    require 'views/register.php';
}

elseif ($uri === '/register' && $method === 'POST') {
    $res = Auth::register($_POST['username'], $_POST['email'], $_POST['password'], $_POST['confirm']);
    if ($res === true) header("Location: /login");
    else $error = $res;
    require 'views/register.php';
}

// выход
elseif ($uri === '/logout') {
    Auth::logout();
}

// дашборд
elseif ($uri === '/dashboard') {
    if (!Auth::isLoggedIn()) header("Location: /login");
    require 'views/dashboard.php';
}

// API
elseif (str_starts_with($uri, '/api/')) {

    // проверка авторизации
    if (!Auth::isLoggedIn()) {
        jsonResponse(["success"=>false,"error"=>"Не авторизован"], 401);
    }

    // @SECURITY_MODULE (Content-Type)

    // читаем JSON тело
    $input = json_decode(file_get_contents('php://input'), true);

    try {

        if ($uri === '/api/task/add') {
            $id = Task::create($_SESSION['user_id'], $input['title']);
            jsonResponse(["success"=>true,"data"=>$id]);
        }

        elseif ($uri === '/api/task/list') {
            $tasks = Task::getAll($_SESSION['user_id'], $_GET['filter'] ?? 'all');
            jsonResponse(["success"=>true,"data"=>$tasks]);
        }

        elseif ($uri === '/api/task/toggle') {
            Task::toggle($input['id'], $_SESSION['user_id']);
            jsonResponse(["success"=>true]);
        }

        elseif ($uri === '/api/task/delete') {
            Task::delete($input['id'], $_SESSION['user_id']);
            jsonResponse(["success"=>true]);
        }

        else {
            jsonResponse(["success"=>false,"error"=>"Не найдено"], 404);
        }

    } catch (Exception $e) {
        jsonResponse(["success"=>false,"error"=>$e->getMessage()], 400);
    }
}

else {
    http_response_code(404);
    echo "404 Not Found"; // если маршрут не найден
}