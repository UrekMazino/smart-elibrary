<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
// Dev-server router only (`php artisan serve`): a request for an existing file under public/ is
// served as-is, exactly as Apache would. Not a user-controlled file read. Like public/.htaccess,
// only index.php runs: any other .php file goes to the app (a 404).
// nosemgrep: php.lang.security.injection.tainted-filename.tainted-filename
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri) && ! preg_match('/\.(php\d*|phtml|phar)$/i', $uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
