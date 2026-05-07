<?php
/**
 * Вспомогательные функции
 */

/**
 * Безопасный вывод строки в HTML (защита от XSS)
 * @param string $value Исходная строка
 * @return string Экранированная строка
 */
function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Отправка JSON-ответа и завершение скрипта
 * @param array $data Данные для отправки
 * @param int $statusCode HTTP-код ответа
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Установка защитных HTTP-заголовков
 * Вызывать в начале каждого файла, который отдаёт HTML
 */
function setSecurityHeaders(): void
{
    // Запрет на встраивание страницы в iframe (защита от clickjacking)
    header('X-Frame-Options: DENY');

    // Запрет браузеру угадывать MIME-тип (защита от MIME-sniffing)
    header('X-Content-Type-Options: nosniff');

    // Content Security Policy (защита от XSS)
    // Разрешаем скрипты и стили только с нашего домена
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    
    // Отключаем отображение PHP в заголовках
    header_remove('X-Powered-By');

    // Запрещаем браузеру сохранять страницу в кэш (для страниц с конфиденциальными данными)
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}


/**
 * Генерация CSRF-токена и сохранение в сессии
 * @return string Токен
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Скрытое поле с CSRF-токеном для вставки в HTML-формы
 * @return string HTML-код скрытого поля
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(generateCsrfToken()) . '">';
}

/**
 * Проверка CSRF-токена из запроса
 * @param string|null $token Токен из запроса
 * @return bool Валиден ли токен
 */
function verifyCsrfToken(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}




/**
 * Логирование событий безопасности в файл
 * @param string $type Тип события
 * @param string $message Сообщение
 * @param array $context Дополнительные данные
 */
function securityLog(string $type, string $message, array $context = []): void
{
    $logFile = __DIR__ . '/../logs/security.log';
    $logDir = dirname($logFile);
    
    // Создаём папку для логов, если её нет
    if (!is_dir($logDir)) {
        mkdir($logDir, 0750, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $userId = $_SESSION['user_id'] ?? 'anonymous';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $logEntry = sprintf(
        "[%s] [%s] [user:%s] [ip:%s] %s %s\n",
        $timestamp,
        strtoupper($type),
        $userId,
        $ip,
        $message,
        !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
    );
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}


?>