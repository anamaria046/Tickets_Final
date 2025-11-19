<?php

use App\Controllers\UserController;
use App\Middleware\Token;
use Slim\App;

return function (App $app) {
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
};