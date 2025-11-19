<?php

require __DIR__ . '/../vendor/autoload.php';

// Cargar configuración de BD
require __DIR__ . '/../app/Config/database.php';

// Crear app Slim con contenedor por defecto
$app = Slim\Factory\AppFactory::create();

// Agregar middleware para parsear JSON/XML
$app->addBodyParsingMiddleware();

// Definir rutas directamente aquí
use App\Controllers\UserController;
use App\Middleware\Token;

// Rutas públicas
$app->post('/register', [new UserController(), 'register']);
$app->post('/login', [new UserController(), 'login']);

// Rutas protegidas
$app->group('', function ($group) {
    $group->post('/logout', [new UserController(), 'logout']);
    $group->get('/users', [new UserController(), 'listUsers']);
    $group->put('/users/{id}', [new UserController(), 'updateUser']);
    $group->delete('/users/{id}', [new UserController(), 'deleteUser']);
})->add(new Token());

// Ejecutar
$app->run();