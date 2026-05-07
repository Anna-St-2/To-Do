<?php
/**
 * Главная страница
 * Если пользователь авторизован — редирект на dashboard.php
 * Если нет — редирект на login.php
 */

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/modules/auth/Auth.php';

if (Auth::isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;