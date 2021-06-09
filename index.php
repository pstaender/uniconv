<?php
require_once(__DIR__ . '/config/config.php');

$controllers = [
    '/(convert|conversion)' => App\ConversionController::class,
    '/file' => App\FileController::class,
    '/job(s)*' => App\JobController::class,
];

foreach($controllers as $routePattern => $routerClass) {
    $routePattern = '/^'.str_replace('/', '\/', $routePattern) . '(\/.*|\?.*)*$/';
    if (preg_match($routePattern, $_SERVER['REQUEST_URI'])) {
        $controller = new $routerClass(
            request: $_REQUEST,
            requestMethod: $_SERVER['REQUEST_METHOD'],
            server: $_SERVER,
            headers: getallheaders(),
            files: $_FILES
        );
        $controller->run();
    }
}


