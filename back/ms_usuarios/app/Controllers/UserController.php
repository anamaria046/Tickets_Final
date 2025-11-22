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
    //////////////// Registro de el usuario
    public function register($request, $response)
    {
        $data = $request->getParsedBody();

        // Validación
        if (empty($data['name']) || empty($data['email']) || empty($data['password']) || empty($data['role'])) {
            $response->getBody()->write(json_encode(['error' => 'Campos obligatorios faltantes'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400);
        }

        // Verificar si email ya existe
        if ($this->repo->getUserByEmail($data['email'])) {
            $response->getBody()->write(json_encode(['error' => 'Email ya registrado'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(409); // estado de conflicto, para este caso es porque se duplicaria
        }

        $user = $this->repo->createUser($data);
        $response->getBody()->write(json_encode($user, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
    ///////INICIO DE SESIÓN
    public function login($request, $response)
    {
        $data = $request->getParsedBody();

        if (empty($data['email']) || empty($data['password'])) {
            $response->getBody()->write(json_encode(['error' => 'Email y contraseña requeridos'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $user = $this->repo->getUserByEmail($data['email']);
        if (!$user || $user->password !== $data['password']) {
            $response->getBody()->write(json_encode(['error' => 'Información incorrectas'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $token = bin2hex(random_bytes(16));

        AToken::create([
            'user_id' => $user->id,
            'token' => $token
        ]);

        $response->getBody()->write(json_encode([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ]
        ], JSON_UNESCAPED_UNICODE));

        return $response->withHeader('Content-Type', 'application/json');
    }
    /////////////////CERRAR SESIÓN
    public function logout($request, $response)
    {
        $headers = $request->getHeaders();
        $authHeader = $headers['Authorization'][0] ?? '';

        // Extraer token
        $token = str_replace("Bearer ", "", $authHeader);
        $token = trim($token);

        // Eliminar el token de la base de datos
        $deleted = AToken::where('token', $token)->delete();

        if ($deleted) {
            $response->getBody()->write(json_encode(['message' => 'Sesión cerrada'], JSON_UNESCAPED_UNICODE));
        } else {
            $response->getBody()->write(json_encode(['message' => 'Sesión cerrada (token ya no existía)'], JSON_UNESCAPED_UNICODE));
        }

        return $response->withHeader('Content-Type', 'application/json');
    }
    ///////// LISTAR USUARIO
    public function listUsers($request, $response)
    {
        // Obtener Authorization de todas las fuentes posibles (igual que el middleware)
        $authHeader =
            $request->getHeaderLine('Authorization') ?: ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        // Extraer token
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
        } else {
            $token = trim($authHeader);
        }

        $auth = AToken::where('token', $token)->first();
        $user = $auth ? Users::find($auth->user_id) : null;

        if (!$user || !$user->isAdmin()) {
            $response->getBody()->write(json_encode(['error' => 'Acceso denegado'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $users = $this->repo->getAllUsers();
        $response->getBody()->write(json_encode($users, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
    /////////ACTUALIZAR USUARIOS
    public function updateUser($request, $response, $args)
    {
        $id = $args['id'];
        $data = $request->getParsedBody();

        // Obtener Authorization de todas las fuentes posibles (igual que el middleware)
        $authHeader =
            $request->getHeaderLine('Authorization') ?: ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        // Extraer token
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
        } else {
            $token = trim($authHeader);
        }

        $auth = AToken::where('token', $token)->first();
        $user = $auth ? Users::find($auth->user_id) : null;

        if (!$user || !$user->isAdmin()) {
            $response->getBody()->write(json_encode(['error' => 'Acceso denegado'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(403);
        }

        // Validación adicional: Si se incluye 'role', verificar que sea válido
        if (isset($data['role']) && !in_array($data['role'], ['gestor', 'admin'])) {
            $response->getBody()->write(json_encode(['error' => 'Rol inválido'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400);
        }

        $updated = $this->repo->updateUser($id, $data);
        if ($updated) {
            $response->getBody()->write(json_encode(['message' => 'Usuario actualizado'], JSON_UNESCAPED_UNICODE));
        } else {
            $response->getBody()->write(json_encode(['error' => 'Usuario no encontrado'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(404);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    // Método agregado: Cambiar rol de un usuario
    public function changeUserRole($request, $response, $args)
    {
        $id = $args['id'];
        $data = $request->getParsedBody();

        // Obtener Authorization de todas las fuentes posibles (igual que el middleware)
        $authHeader =
            $request->getHeaderLine('Authorization') ?: ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        // Extraer token
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
        } else {
            $token = trim($authHeader);
        }

        $auth = AToken::where('token', $token)->first();
        $user = $auth ? Users::find($auth->user_id) : null;

        if (!$user || !$user->isAdmin()) {
            $response->getBody()->write(json_encode(['error' => 'Acceso denegado'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(403);
        }

        // Validar que se envíe un rol válido
        if (empty($data['role']) || !in_array($data['role'], ['gestor', 'admin'])) {
            $response->getBody()->write(json_encode(['error' => 'Rol requerido e inválido'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400);
        }

        $updated = $this->repo->updateUser($id, ['role' => $data['role']]);
        if ($updated) {
            $response->getBody()->write(json_encode(['message' => 'Rol de usuario cambiado'], JSON_UNESCAPED_UNICODE));
        } else {
            $response->getBody()->write(json_encode(['error' => 'Usuario no encontrado'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(404);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }
    //////// ELIMINAR USUARIOO
    public function deleteUser($request, $response, $args)
    {
        $id = $args['id'];

        // Obtener Authorization de todas las fuentes posibles 
        $authHeader =
            $request->getHeaderLine('Authorization') ?: ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        // Extraer token
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
        } else {
            $token = trim($authHeader);
        }

        $auth = AToken::where('token', $token)->first();
        $user = $auth ? Users::find($auth->user_id) : null;

        if (!$user || !$user->isAdmin()) {
            $response->getBody()->write(json_encode(['error' => 'Acceso denegado'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(403);
        }

        $deleted = $this->repo->deleteUser($id);
        if ($deleted) {
            $response->getBody()->write(json_encode(['message' => 'Usuario eliminado'], JSON_UNESCAPED_UNICODE));
        } else {
            $response->getBody()->write(json_encode(['error' => 'Usuario no encontrado'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(404);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }
}
