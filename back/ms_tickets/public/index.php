<?php

use Slim\Factory\AppFactory;
use App\Middleware\Cors;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Config/database.php';

$endpoints = require __DIR__ . '/../app/Endpoints/endpoints.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Aplicar middleware CORS
$app->add(new Cors());

// Manejar peticiones OPTIONS
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

// Cargar rutas
$endpoints($app);

$app->run();