<?php
// Database configuration and connection helper for NAAQŚĦ.
// The project is designed for a local XAMPP/MySQL setup using the default
// root user with no password. This file centralizes all connection details.

const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'naaqsh';
const DB_USER = 'root';
const DB_PASS = '';

/**
 * Create and reuse a PDO connection to the `naaqsh` database.
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

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        // A PDO instance is created once and stored for later use.
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Do not reveal technical details to end users in production.
        // This message helps developers know the database is unavailable.
        die('Database connection failed. Please make sure MySQL is running and the database exists. Details: ' . $e->getMessage());
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
