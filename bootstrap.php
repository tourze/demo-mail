<?php

use tourze\Base\Config;

require_once 'vendor/autoload.php';

if ( ! defined('ROOT_PATH'))
{
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

Config::addPath(ROOT_PATH . 'config' . DIRECTORY_SEPARATOR);
