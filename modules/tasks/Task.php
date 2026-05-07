<?php
require_once __DIR__ . '/../../config/database.php';

class Task {

    public static function create($userId, $title) {
        // проверка названия
        if (!$title || strlen($title) > 255) {
            throw new Exception("Некорректное название");
        }

        $pdo = Database::getInstance()->getConnection();

        // @SECURITY_MODULE (логирование)

        // добавляем задачу
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title) VALUES (?, ?)");
        $stmt->execute([$userId, $title]);

        return $pdo->lastInsertId(); // возвращаем id
    }

    public static function getAll($userId, $filter = 'all') {
        $pdo = Database::getInstance()->getConnection();

        // @SECURITY_MODULE (логирование)

        // базовый запрос
        $query = "SELECT * FROM tasks WHERE user_id = ?";

        // фильтрация
        if ($filter === 'active') $query .= " AND is_completed = 0";
        if ($filter === 'completed') $query .= " AND is_completed = 1";

        $query .= " ORDER BY created_at DESC"; // сортировка

        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId]);

        return $stmt->fetchAll(); // массив задач
    }

    public static function toggle($taskId, $userId) {
        $pdo = Database::getInstance()->getConnection();

        // @SECURITY_MODULE

        // переключаем статус
        $stmt = $pdo->prepare("UPDATE tasks SET is_completed = NOT is_completed WHERE id = ? AND user_id = ?");
        $stmt->execute([$taskId, $userId]);

        return true;
    }

    public static function delete($taskId, $userId) {
        $pdo = Database::getInstance()->getConnection();

        // @SECURITY_MODULE

        // удаляем задачу
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$taskId, $userId]);

        return true;
    }
}