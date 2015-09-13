<?php

use tourze\Base\Config;

if (is_file(__DIR__ . '/vendor/autoload.php'))
{
    require_once __DIR__ . '/vendor/autoload.php';
}

if ( ! defined('ROOT_PATH'))
{
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

Config::addPath(ROOT_PATH . 'config' . DIRECTORY_SEPARATOR);
