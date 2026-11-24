<?php

/**
 * Use this file ONLY if cPanel will not let you point the subdomain at accountant-app/public.
 * Copy the contents of accountant-app/public/ into the subdomain folder, replace its index.php
 * with this file, and set $app below to the folder where you uploaded the app.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$app = dirname(__DIR__).'/accountant-app';

if (file_exists($maintenance = $app.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $app.'/vendor/autoload.php';

/** @var Application $laravel */
$laravel = require_once $app.'/bootstrap/app.php';

$laravel->usePublicPath(__DIR__);

$laravel->handleRequest(Request::capture());
