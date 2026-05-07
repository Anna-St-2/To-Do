<?php
/**
 * Подключение к базе данных (паттерн Singleton)
 * Обеспечивает единственное PDO-соединение на всё приложение
 */

class Database
{
    // Параметры подключения — измени под свои данные
    private const DB_HOST = 'localhost';
    private const DB_NAME = 'todo_app';
    private const DB_USER = 'root';         // замени на своего пользователя
    private const DB_PASS = '';             // замени на свой пароль
    private const DB_CHARSET = 'utf8mb4';

    private static ?PDO $instance = null;

    /**
     * Возвращает единственный экземпляр PDO
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::DB_HOST,
                self::DB_NAME,
                self::DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // Реальные подготовленные выражения
            ];

            self::$instance = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
        }

        return self::$instance;
    }

    // Запрещаем создание экземпляров класса
    private function __construct() {}
    private function __clone() {}
}