<?php

namespace App\Repositories;

use App\Controllers\UserController;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserRepository
{
    private $codesError = [
        400 => 400,
        401 => 401,
        403 => 403,
        404 => 404,
        409 => 409,
        'default' => 500
    ];

    /**
     * Registrar nuevo usuario
     */
    public function register(Request $request, Response $response)
    {
        try {
            $data = $request->getParsedBody();
            
            $controller = new UserController();
            $user = $controller->register(
                $data['name'] ?? null,
                $data['email'] ?? null,
                $data['password'] ?? null,
                $data['role'] ?? null
            );
            
            $response->getBody()->write(json_encode($user, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            $status = $this->codesError[$ex->getCode()] ?? $this->codesError['default'];
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()], JSON_UNESCAPED_UNICODE));
            return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Iniciar sesión
     */
    public function login(Request $request, Response $response)
    {
        try {
            $data = $request->getParsedBody();
            
            $controller = new UserController();
            $result = $controller->login(
                $data['email'] ?? null,
                $data['password'] ?? null
            );
            
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            $status = $this->codesError[$ex->getCode()] ?? $this->codesError['default'];
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()], JSON_UNESCAPED_UNICODE));
            return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request, Response $response)
    {
        try {
            $token = $this->extractToken($request);
            
            $controller = new UserController();
            $result = $controller->logout($token);
            
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            $status = $this->codesError[$ex->getCode()] ?? $this->codesError['default'];
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()], JSON_UNESCAPED_UNICODE));
            return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Listar todos los usuarios (admin)
     */
    public function listUsers(Request $request, Response $response)
    {
        try {
            $token = $this->extractToken($request);
            
            $controller = new UserController();
            $users = $controller->getUsers($token);
            
            $response->getBody()->write(json_encode($users, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            $status = $this->codesError[$ex->getCode()] ?? $this->codesError['default'];
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()], JSON_UNESCAPED_UNICODE));
            return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Actualizar usuario (admin)
     */
    public function updateUser(Request $request, Response $response, $args)
    {
        try {
            $token = $this->extractToken($request);
            $userId = $args['id'];
            $data = $request->getParsedBody();
            
            $controller = new UserController();
            $result = $controller->updateUser($token, $userId, $data);
            
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            $status = $this->codesError[$ex->getCode()] ?? $this->codesError['default'];
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()], JSON_UNESCAPED_UNICODE));
            return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Cambiar rol de usuario (admin)
     */
    public function changeUserRole(Request $request, Response $response, $args)
    {
        try {
            $token = $this->extractToken($request);
            $userId = $args['id'];
            $data = $request->getParsedBody();
            
            $controller = new UserController();
            $result = $controller->changeUserRole($token, $userId, $data['role'] ?? null);
            
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            $status = $this->codesError[$ex->getCode()] ?? $this->codesError['default'];
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()], JSON_UNESCAPED_UNICODE));
            return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Eliminar usuario (admin)
     */
    public function deleteUser(Request $request, Response $response, $args)
    {
        try {
            $token = $this->extractToken($request);
            $userId = $args['id'];
            
            $controller = new UserController();
            $result = $controller->deleteUser($token, $userId);
            
            $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $ex) {
            $status = $this->codesError[$ex->getCode()] ?? $this->codesError['default'];
            $response->getBody()->write(json_encode(['error' => $ex->getMessage()], JSON_UNESCAPED_UNICODE));
            return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Método auxiliar para extraer el token del request
     */
    private function extractToken(Request $request)
    {
        // Obtener Authorization de todas las fuentes posibles
        $authHeader = 
            $request->getHeaderLine('Authorization') ?: 
            ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: 
            ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        // Extraer token
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }
        
        return trim($authHeader);
    }
}