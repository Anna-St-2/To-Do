<?php
session_start(); // старт сессии

require_once 'modules/auth/Auth.php';
require_once 'modules/tasks/Task.php';

/*
========================
 ПРОСТОЙ РОУТЕР ЧЕРЕЗ GET
========================
*/
$route = $_GET['route'] ?? 'home';
$method = $_SERVER['REQUEST_METHOD'];

// JSON ответ
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/*
========================
 ГЛАВНАЯ
========================
*/
if ($route === 'home') {

    if (Auth::isLoggedIn()) {
        require 'views/dashboard.php';
    } else {
        require 'views/login.php';
    }
}

/*
========================
 LOGIN
========================
*/
elseif ($route === 'login' && $method === 'GET') {
    require 'views/login.php';
}

elseif ($route === 'login' && $method === 'POST') {

    $res = Auth::login($_POST['login'], $_POST['password']);

    if ($res === true) {
        header("Location: index.php?route=dashboard");
        exit;
    }

    $error = $res;
    require 'views/login.php';
}


/*
========================
 REGISTER
========================
*/
elseif ($route === 'register' && $method === 'GET') {
    require 'views/register.php';
}

elseif ($route === 'register' && $method === 'POST') {

    $res = Auth::register(
        $_POST['username'],
        $_POST['email'],
        $_POST['password'],
        $_POST['confirm']
    );

    if ($res === true) {
        header("Location: index.php?route=login");
        exit;
    }

    $error = $res;
    require 'views/register.php';
}

/*
========================
 LOGOUT
========================
*/
elseif ($route === 'logout') {
    Auth::logout();
    header("Location: index.php?route=login");
    exit;
}

/*
========================
 DASHBOARD
========================
*/
elseif ($route === 'dashboard') {

    if (!Auth::isLoggedIn()) {
        header("Location: index.php?route=login");
        exit;
    }

    require 'views/dashboard.php';
}

/*
========================
 API
========================
*/
elseif (str_starts_with($route, 'api/')) {

    if (!Auth::isLoggedIn()) {
        jsonResponse(["success"=>false,"error"=>"Не авторизован"], 401);
    }

    $input = json_decode(file_get_contents('php://input'), true);

    try {

        if ($route === 'api/task/add') {
            $id = Task::create($_SESSION['user_id'], $input['title']);
            jsonResponse(["success"=>true,"data"=>$id]);
        }

        elseif ($route === 'api/task/list') {
            $tasks = Task::getAll($_SESSION['user_id'], $_GET['filter'] ?? 'all');
            jsonResponse(["success"=>true,"data"=>$tasks]);
        }

        elseif ($route === 'api/task/toggle') {
            Task::toggle($input['id'], $_SESSION['user_id']);
            jsonResponse(["success"=>true]);
        }

        elseif ($route === 'api/task/delete') {
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

/*
========================
 404
========================
*/
else {
    http_response_code(404);
    echo "404 Not Found";
}