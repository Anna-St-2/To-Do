<?php
/**
 * Модуль аутентификации
 * Отвечает за регистрацию, вход и выход пользователей
 */

require_once __DIR__ . '/../../config/database.php';

class Auth
{
    /**
     * Регистрация нового пользователя
     * 
     * @param string $username        Логин
     * @param string $email           Email
     * @param string $password        Пароль
     * @param string $confirmPassword Подтверждение пароля
     * @return array                  ['success' => bool, 'error' => string]
     */
    public static function register(string $username, string $email, string $password, string $confirmPassword): array
    {
        // Валидация
        $username = trim($username);
        $email = trim($email);

        if (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
            return ['success' => false, 'error' => 'Логин должен быть от 3 до 50 символов'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Некорректный email'];
        }

        if (mb_strlen($password) < 6) {
            return ['success' => false, 'error' => 'Пароль должен содержать минимум 6 символов'];
        }

        if ($password !== $confirmPassword) {
            return ['success' => false, 'error' => 'Пароли не совпадают'];
        }

        $db = Database::getConnection();

        // @SECURITY_MODULE: место для проверки капчи перед регистрацией

        // Проверка уникальности логина
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Пользователь с таким логином уже существует'];
        }

        // Проверка уникальности email
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Пользователь с таким email уже существует'];
        }

        // Хеширование пароля и сохранение
        // @SECURITY_MODULE: место для логирования регистрации
        // Логирование регистрации
        securityLog('info', 'Новый пользователь зарегистрирован', ['username' => $username]);
        
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $db->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$username, $email, $passwordHash]);

        return ['success' => true, 'error' => ''];
    }

    /**
     * Вход пользователя
     * 
     * @param string $login    Логин или email
     * @param string $password Пароль
     * @return array           ['success' => bool, 'error' => string]
     */
    public static function login(string $login, string $password): array
    {
        $login = trim($login);
    
        if (empty($login) || empty($password)) {
            return ['success' => false, 'error' => 'Заполните все поля'];
        }
    
        $db = Database::getConnection();
    
        // Поиск пользователя по логину ИЛИ email
        $stmt = $db->prepare('SELECT id, username, password_hash FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();
    
        if (!$user) {
            // Пользователь не найден — логируем и возвращаем ошибку
            securityLog('warning', 'Неудачная попытка входа: пользователь не найден', ['login' => $login]);
            return ['success' => false, 'error' => 'Неверный логин или пароль'];
        }
    
        if (!password_verify($password, $user['password_hash'])) {
            // Пароль неверный — логируем и возвращаем ошибку
            securityLog('warning', 'Неудачная попытка входа: неверный пароль', ['login' => $login]);
            return ['success' => false, 'error' => 'Неверный логин или пароль'];
        }
    
        // Успешный вход
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        session_regenerate_id(true);
    
        // Логирование успешного входа — ТОЛЬКО ОДИН РАЗ, ТОЛЬКО ЗДЕСЬ
        securityLog('info', 'Успешный вход в систему');
    
        return ['success' => true, 'error' => ''];
    }

    /**
     * Выход пользователя
     */
    public static function logout(): void
    {
        // Очистка сессии
        $_SESSION = [];
        
        // Удаление сессионной cookie
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
    }

    /**
     * Проверка авторизации
     * 
     * @return bool
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Требовать авторизацию (если нет — редирект на login.php)
     */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Если уже авторизован — редирект на дашборд
     */
    public static function redirectIfLoggedIn(): void
    {
        if (self::isLoggedIn()) {
            header('Location: dashboard.php');
            exit;
        }
    }
}