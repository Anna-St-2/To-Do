<?php
require_once __DIR__ . '/../../config/database.php';

class Auth {

    public static function register($username, $email, $password, $confirmPassword) {
        $pdo = Database::getInstance()->getConnection(); // получаем PDO

        // простая валидация
        if (strlen($username) < 3 || strlen($username) > 50) {
            return "Логин должен быть от 3 до 50 символов";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "Некорректный email";
        }

        if (strlen($password) < 6) {
            return "Пароль должен быть минимум 6 символов";
        }

        if ($password !== $confirmPassword) {
            return "Пароли не совпадают";
        }

        // проверка уникальности логина
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) return "Логин уже занят";

        // проверка уникальности email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) return "Email уже используется";

        // @SECURITY_MODULE (капча)

        // хешируем пароль
        $hash = password_hash($password, PASSWORD_BCRYPT);

        // сохраняем пользователя
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hash]);

        return true; // успех
    }

    public static function login($login, $password) {
        $pdo = Database::getInstance()->getConnection();

        // ищем по логину ИЛИ email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if (!$user) return "Пользователь не найден";

        // @SECURITY_MODULE (ограничение попыток)

        // проверка пароля
        if (!password_verify($password, $user['password_hash'])) {
            return "Неверный пароль";
        }

        session_regenerate_id(true); // защита от фиксации сессии

        // сохраняем данные в сессию
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        return true;
    }

    public static function logout() {
        session_unset(); // очищаем сессию
        session_destroy(); // уничтожаем
        header("Location: /login"); // редирект
        exit;
    }

    public static function isLoggedIn() {
        return isset($_SESSION['user_id']); // проверка авторизации
    }
}