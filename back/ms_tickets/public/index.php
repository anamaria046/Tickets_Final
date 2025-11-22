<?php

require __DIR__ . '/../vendor/autoload.php';

// Configurar base de datos
require __DIR__ . '/../app/Config/database.php';

// Cargar rutas
$app = require __DIR__ . '/../app/Config/routers.php';

// Middleware de CORS - DEBE IR ANTES DE LAS RUTAS
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
// Ejecutar aplicación
$app->run();