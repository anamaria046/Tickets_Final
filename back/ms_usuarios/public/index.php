<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Config/database.php';

$app = Slim\Factory\AppFactory::create();
$app->addBodyParsingMiddleware();

// Middleware de CORS 
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Manejar peticiones OPTIONS
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

// Cargar rutas desde endpoints.php
$routes = require __DIR__ . '/../app/Endpoints/endpoints.php';
$routes($app);

$app->run();