<?php
declare(strict_types=1);

session_start();

$debug = (getenv("WNX_DEBUG") === "1");
ini_set("log_errors","1");
ini_set("error_log", __DIR__ . "/data/php_errors.log");
error_reporting(E_ALL);
ini_set("display_errors", $debug ? "1" : "0");


// Security headers (safe defaults)
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// IMPORTANT: no stray output before this file ends.
// If you see anything printed above the page (like SQL), it means some file has text outside PHP tags.

define('WNX_DEMO_MODE', false);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/schema.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/rbac.php';
require_once __DIR__ . '/lib/crypto.php';
require_once __DIR__ . '/lib/modules.php';
require_once __DIR__ . '/lib/demo_store.php';
require_once __DIR__ . '/lib/helpers.php';
