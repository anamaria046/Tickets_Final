<?php

use App\Repositories\UserRepository;
use App\Middleware\Token;

return function ($app) {
    /////Endpoint de registro
    $app->post('/register', function ($request, $response) {
        $repository = new UserRepository();
        return $repository->register($request, $response);
    });

    /////Endpoint de login
    $app->post('/login', function ($request, $response) {
        $repository = new UserRepository();
        return $repository->login($request, $response);
    });

    /////Endpoint de logout
    $app->post('/logout', function ($request, $response) {
        $repository = new UserRepository();
        return $repository->logout($request, $response);
    })->add(new Token());

    //////Endpoint para listar usuarios (admin)
    $app->get('/users', function ($request, $response) {
        $repository = new UserRepository();
        return $repository->listUsers($request, $response);
    })->add(new Token());

    /////Endpoint para actualizar usuario (admin)
    $app->put('/users/{id}', function ($request, $response, $args) {
        $repository = new UserRepository();
        return $repository->updateUser($request, $response, $args);
    })->add(new Token());

    //////Endpoint para cambiar rol de usuario (admin)
    $app->patch('/users/{id}/role', function ($request, $response, $args) {
        $repository = new UserRepository();
        return $repository->changeUserRole($request, $response, $args);
    })->add(new Token());

    /////Endpoint para eliminar usuario (admin)
    $app->delete('/users/{id}', function ($request, $response, $args) {
        $repository = new UserRepository();
        return $repository->deleteUser($request, $response, $args);
    })->add(new Token());
};