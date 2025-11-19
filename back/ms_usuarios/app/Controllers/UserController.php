<?php

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Models\AToken;
use App\Models\Users;
use Slim\Psr7\Response;

class UserController
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new UserRepository();
    }

    public function register($request, $response)
    {
        $data = $request->getParsedBody();

        // Validación básica
        if (empty($data['name']) || empty($data['email']) || empty($data['password']) || empty($data['role'])) {
            $response->getBody()->write(json_encode(['error' => 'Campos obligatorios faltantes']));
            return $response->withStatus(400);
        }

        // Verificar si email ya existe
        if ($this->repo->getUserByEmail($data['email'])) {
            $response->getBody()->write(json_encode(['error' => 'Email ya registrado']));
            return $response->withStatus(409);
        }

        $user = $this->repo->createUser($data);

        $response->getBody()->write(json_encode($user));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function login($request, $response)
    {
        $data = $request->getParsedBody();

        if (empty($data['email']) || empty($data['password'])) {
            $response->getBody()->write(json_encode(['error' => 'Email y contraseña requeridos']));
            return $response->withStatus(400);
        }

        $user = $this->repo->getUserByEmail($data['email']);

        if (!$user || !password_verify($data['password'], $user->password)) {
            $response->getBody()->write(json_encode(['error' => 'Credenciales incorrectas']));
            return $response->withStatus(401);
        }

        $token = bin2hex(random_bytes(32));

        AToken::create([
            'user_id' => $user->id,
            'token' => $token
        ]);

        $response->getBody()->write(json_encode([
            'token' => $token,
            'role' => $user->role
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function logout($request, $response)
    {
        $headers = $request->getHeaders();
        $token = str_replace("Bearer ", "", $headers['Authorization'][0] ?? '');

        AToken::where('token', $token)->delete();

        $response->getBody()->write(json_encode(['message' => 'Sesión cerrada']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function listUsers($request, $response)
    {
        // Obtener usuario desde token para verificar rol
        $headers = $request->getHeaders();
        $token = str_replace("Bearer ", "", $headers['Authorization'][0] ?? '');
        $auth = AToken::where('token', $token)->first();
        $user = $auth ? Users::find($auth->user_id) : null;

        if (!$user || !$user->isAdmin()) {
            $response->getBody()->write(json_encode(['error' => 'Acceso denegado']));
            return $response->withStatus(403);
        }

        $users = $this->repo->getAllUsers();
        $response->getBody()->write(json_encode($users));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function updateUser($request, $response, $args)
    {
        $id = $args['id'];
        $data = $request->getParsedBody();

        // Verificar rol admin
        $headers = $request->getHeaders();
        $token = str_replace("Bearer ", "", $headers['Authorization'][0] ?? '');
        $auth = AToken::where('token', $token)->first();
        $user = $auth ? Users::find($auth->user_id) : null;

        if (!$user || !$user->isAdmin()) {
            $response->getBody()->write(json_encode(['error' => 'Acceso denegado']));
            return $response->withStatus(403);
        }

        // Validación adicional: Si se incluye 'role', verificar que sea válido
        if (isset($data['role']) && !in_array($data['role'], ['gestor', 'admin'])) {
            $response->getBody()->write(json_encode(['error' => 'Rol inválido']));
            return $response->withStatus(400);
        }

        $updated = $this->repo->updateUser($id, $data);
        if ($updated) {
            $response->getBody()->write(json_encode(['message' => 'Usuario actualizado']));
        } else {
            $response->getBody()->write(json_encode(['error' => 'Usuario no encontrado']));
            return $response->withStatus(404);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    // Método agregado: Cambiar rol de un usuario (cumple específicamente con 1.9)
    public function changeUserRole($request, $response, $args)
    {
        $id = $args['id'];
        $data = $request->getParsedBody();

        // Verificar rol admin
        $headers = $request->getHeaders();
        $token = str_replace("Bearer ", "", $headers['Authorization'][0] ?? '');
        $auth = AToken::where('token', $token)->first();
        $user = $auth ? Users::find($auth->user_id) : null;

        if (!$user || !$user->isAdmin()) {
            $response->getBody()->write(json_encode(['error' => 'Acceso denegado']));
            return $response->withStatus(403);
        }

        // Validar que se envíe un rol válido
        if (empty($data['role']) || !in_array($data['role'], ['gestor', 'admin'])) {
            $response->getBody()->write(json_encode(['error' => 'Rol requerido e inválido']));
            return $response->withStatus(400);
        }

        $updated = $this->repo->updateUser($id, ['role' => $data['role']]);
        if ($updated) {
            $response->getBody()->write(json_encode(['message' => 'Rol de usuario cambiado']));
        } else {
            $response->getBody()->write(json_encode(['error' => 'Usuario no encontrado']));
            return $response->withStatus(404);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function deleteUser($request, $response, $args)
    {
        $id = $args['id'];

        // Verificar rol admin
        $headers = $request->getHeaders();
        $token = str_replace("Bearer ", "", $headers['Authorization'][0] ?? '');
        $auth = AToken::where('token', $token)->first();
        $user = $auth ? Users::find($auth->user_id) : null;

        if (!$user || !$user->isAdmin()) {
            $response->getBody()->write(json_encode(['error' => 'Acceso denegado']));
            return $response->withStatus(403);
        }

        $deleted = $this->repo->deleteUser($id);
        if ($deleted) {
            $response->getBody()->write(json_encode(['message' => 'Usuario eliminado']));
        } else {
            $response->getBody()->write(json_encode(['error' => 'Usuario no encontrado']));
            return $response->withStatus(404);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }
}