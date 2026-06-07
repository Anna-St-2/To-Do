<?php
/**
 * Модуль управления задачами
 * CRUD-операции с привязкой к пользователю
 */

require_once __DIR__ . '/../../config/database.php';

class Task
{
    /**
     * Создание новой задачи
     * 
     * @param int    $userId ID пользователя
     * @param string $title  Текст задачи
     * @return array         ['success' => bool, 'id' => int, 'error' => string]
     */
    public static function create(int $userId, string $title): array
    {
        $title = trim($title);

        // Валидация
        if (empty($title)) {
            return ['success' => false, 'error' => 'Текст задачи не может быть пустым'];
        }

        if (mb_strlen($title) > 255) {
            return ['success' => false, 'error' => 'Текст задачи слишком длинный (максимум 255 символов)'];
        }

        // Сначала получаем соединение
        $db = Database::getConnection();
        
        // Затем выполняем запрос
        $stmt = $db->prepare('INSERT INTO tasks (user_id, title) VALUES (?, ?)');
        $stmt->execute([$userId, $title]);

        // Логирование — после того, как задача создана
        securityLog('info', 'Задача создана', [
            'task_id' => (int) $db->lastInsertId(),
            'title_length' => mb_strlen($title)
        ]);

        return [
            'success' => true,
            'id'      => (int) $db->lastInsertId(),
            'error'   => ''
        ];
    }

    /**
     * Получение списка задач пользователя с фильтром
     * 
     * @param int    $userId ID пользователя
     * @param string $filter 'all' | 'active' | 'completed'
     * @return array         ['success' => bool, 'tasks' => array]
     */
    public static function getAll(int $userId, string $filter = 'all'): array
    {
        $sql = 'SELECT id, title, is_completed, created_at FROM tasks WHERE user_id = ?';

        // Добавление условия фильтра
        if ($filter === 'active') {
            $sql .= ' AND is_completed = 0';
        } elseif ($filter === 'completed') {
            $sql .= ' AND is_completed = 1';
        }

        $sql .= ' ORDER BY created_at DESC';

        // @SECURITY_MODULE: место для логирования запросов
        $db = Database::getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);

        return [
            'success' => true,
            'tasks'   => $stmt->fetchAll()
        ];
    }

    /**
     * Переключение статуса задачи (выполнена/не выполнена)
     * 
     * @param int $taskId ID задачи
     * @param int $userId ID пользователя
     * @return array      ['success' => bool, 'is_completed' => bool, 'error' => string]
     */
    public static function toggle(int $taskId, int $userId): array
    {
        $db = Database::getConnection();

        // Проверка, что задача существует и принадлежит пользователю
        $stmt = $db->prepare('SELECT id, is_completed FROM tasks WHERE id = ? AND user_id = ?');
        $stmt->execute([$taskId, $userId]);
        $task = $stmt->fetch();

        if (!$task) {
            return ['success' => false, 'error' => 'Задача не найдена'];
        }

        // Инвертирование статуса
        // @SECURITY_MODULE: место для логирования изменений
        // Логирование
        securityLog('info', 'Статус задачи изменён', [
            'task_id' => $taskId,
            'new_status' => !$task['is_completed']
        ]);
        
        
        $stmt = $db->prepare('UPDATE tasks SET is_completed = NOT is_completed WHERE id = ? AND user_id = ?');
        $stmt->execute([$taskId, $userId]);

        return [
            'success'      => true,
            'is_completed' => !$task['is_completed'],
            'error'        => ''
        ];
    }

    /**
     * Удаление задачи
     * 
     * @param int $taskId ID задачи
     * @param int $userId ID пользователя
     * @return array      ['success' => bool, 'error' => string]
     */
    public static function delete(int $taskId, int $userId): array
    {
        $db = Database::getConnection();

        // Проверка принадлежности задачи пользователю
        $stmt = $db->prepare('SELECT id FROM tasks WHERE id = ? AND user_id = ?');
        $stmt->execute([$taskId, $userId]);

        if (!$stmt->fetch()) {
            return ['success' => false, 'error' => 'Задача не найдена'];
        }

        // @SECURITY_MODULE: место для логирования удаления
       // Логирование
        securityLog('warning', 'Задача удалена', ['task_id' => $taskId]);
       
        $stmt = $db->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
        $stmt->execute([$taskId, $userId]);

        return ['success' => true, 'error' => ''];
    }
}