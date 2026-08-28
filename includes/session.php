<?php
// Centralized session bootstrap for NAAQŚĦ.
// Ensures sessions are started safely exactly once before any output occurs.

if (session_status() === PHP_SESSION_NONE) {
    if (!ob_get_level()) {
        ob_start();
    }
    session_start();
}
