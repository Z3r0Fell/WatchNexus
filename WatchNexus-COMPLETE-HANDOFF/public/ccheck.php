<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';

header('Content-Type: text/plain; charset=utf-8');

global $WNX_CONFIG;

echo isset($WNX_CONFIG['db']) ? "DB config OK\n" : "DB config MISSING\n";
echo isset($WNX_CONFIG['secret_key_b64']) ? "Secret key OK\n" : "Secret key MISSING\n";
