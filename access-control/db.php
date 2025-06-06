<?php
// ------------------------------
// توابع اتصال به پایگاه داده و اجرای کوئری
// ------------------------------

// بارگذاری تنظیمات
if (!isset($db_config)) require_once __DIR__.'/config.php';

/**
 * اتصال Singleton به PDO
 * @return PDO
 */
function ac_get_db() {
    global $db_config;
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
        $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    return $pdo;
}

/**
 * اجرای کوئری PDO
 * @param string $query
 * @param array $params
 * @return PDOStatement
 */
function db_query($query, $params = []) {
    $stmt = ac_get_db()->prepare($query);
    $stmt->execute($params);
    return $stmt;
}