<?php
// Database configuration and connection helper for NAAQŚĦ.
// Configured to connect to remote Aiven MySQL over SSL.

const DB_HOST = 'mysql-1042b786-naaqsh.d.aivencloud.com';
const DB_PORT = 13730;
const DB_NAME = 'defaultdb';
const DB_USER = 'avnadmin';

/**
 * Create and reuse a PDO connection to the Aiven MySQL database.
 *
 * PDO is used because it supports prepared statements, parameter binding,
 * and proper error handling. The returned object is cached in a static
 * variable so every page reuses the same connection instead of opening a
 * fresh one repeatedly.
 */
function getPDO(): PDO
{
    static $pdo = null;

    // Reuse the existing connection if it has already been created.
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbPass = (string)(getenv('NAAQSH_DB_PASS') ?: ($_ENV['NAAQSH_DB_PASS'] ?? ($_SERVER['NAAQSH_DB_PASS'] ?? '')));
    $caPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'certs' . DIRECTORY_SEPARATOR . 'ca.pem';

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_SSL_CA => $caPath,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
    ];

    try {
        // A PDO instance is created once and stored for later use.
        $pdo = new PDO($dsn, DB_USER, $dbPass, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Do not reveal technical details to end users in production.
        // This message helps developers know the database is unavailable.
        die('Database connection failed. Details: ' . $e->getMessage());
    }
}

/**
 * Simple helper to verify that the database connection is working.
 * This is useful when testing the project after importing SQL.
 */
function testDatabaseConnection(): bool
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->query('SELECT 1');
        return $stmt !== false;
    } catch (PDOException $e) {
        return false;
    }
}
