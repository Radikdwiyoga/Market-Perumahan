<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Setup temporary writable directories for serverless environments (Vercel)
$tmpDirs = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/storage/app/public',
    '/tmp/bootstrap/cache',
    '/tmp/database',
];

foreach ($tmpDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Ensure SQLite database exists in writable /tmp directory
$tmpSqlite = '/tmp/database/database.sqlite';
$sourceSqlite = __DIR__ . '/../database/database.sqlite';

if (!file_exists($tmpSqlite)) {
    if (file_exists($sourceSqlite)) {
        @copy($sourceSqlite, $tmpSqlite);
    } else {
        @touch($tmpSqlite);
    }
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Overwrite storage and cache paths to writable /tmp directory if in serverless
if (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL'])) {
    $app->useStoragePath('/tmp/storage');
}

$app->handleRequest(Request::capture());
