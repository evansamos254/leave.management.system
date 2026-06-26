<?php
// PHP built-in server router — not for production use
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false; // serve static files directly
}
require __DIR__ . '/index.php';
