<?php
require_once 'config/db.php';

try {
    $pdo = getPDO();
    $db = $pdo->query('SELECT DATABASE() AS dbname')->fetchColumn();
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo 'DB_OK|' . $db . '|' . count($tables) . PHP_EOL;
} catch (Throwable $e) {
    echo 'FAIL|' . $e->getMessage() . PHP_EOL;
}
?>
