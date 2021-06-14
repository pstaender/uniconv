<?php

namespace App;

require_once(__DIR__ . '/../vendor/autoload.php');
define('ROOT_PATH', realpath(dirname(__DIR__) . '/'));

global $debug;

function is_debug(): bool
{
    global $debug;
    return (bool) $debug;
}

// load config files
global $config;

$config = \Symfony\Component\Yaml\Yaml::parse(file_get_contents('./config/config.yml'));

$localConfig = "./config/config.local.yml";
$hostConfig = "./config/config.".preg_replace('/\:.+?$/', '', $_SERVER['HTTP_HOST'] ?? 'cli').'.yml';

foreach([$localConfig, $hostConfig] as $configFile) {
    if (file_exists($configFile)) {
        $config = array_merge(
            $config,
            \Symfony\Component\Yaml\Yaml::parse(file_get_contents($configFile)),
        );
    }
}

function config(? string $val = null) {
    global $config;
    if ($val) {
        return $config[$val] ?? null;
    }
    return $config;
}

$debug = config('debug');

if (is_debug()) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $whoops = new \Whoops\Run;
    if (str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')) {
        $whoops->pushHandler(new \Whoops\Handler\JsonResponseHandler);
    } else {
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
    }
    $whoops->register();
}
