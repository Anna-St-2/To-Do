<?php
/**
 * Выход из системы
 */

session_start();
require_once __DIR__ . '/modules/auth/Auth.php';

Auth::logout();
header('Location: login.php?logged_out=1');
exit;