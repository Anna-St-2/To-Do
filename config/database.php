<?php

class Database {
    private static $instance = null; // хранит единственный экземпляр класса
    private $pdo; // объект подключения PDO

    // параметры подключения к БД
    private const HOST = 'localhost';
    private const DB   = 'todo_app';
    private const USER = 'root';
    private const PASS = '';

    private function __construct() {
        // строка подключения (DSN)
        $dsn = "mysql:host=" . self::HOST . ";dbname=" . self::DB . ";charset=utf8mb4";

        // создаём PDO с настройками
        $this->pdo = new PDO($dsn, self::USER, self::PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // ошибки как исключения
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // ассоциативные массивы
            PDO::ATTR_EMULATE_PREPARES => false // настоящие prepared statements
        ]);
    }

    public static function getInstance() {
        // создаём объект только один раз
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo; // возвращаем подключение
    }
}