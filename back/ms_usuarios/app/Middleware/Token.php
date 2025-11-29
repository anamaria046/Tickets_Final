<?php

namespace App\Middleware;

use App\Models\AToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class Token
{
    public function __invoke(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        /////Obtener Authorization de todas las fuentes posibles
        $authHeader =
            $request->getHeaderLine('Authorization') ?: 
            ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: 
            ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (!$authHeader) {
            return $this->errorResponse('Token requerido', 401);
        }

        /////Extraer token
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) { ///se busca coincidencia con el match 0 y 1
            $token = trim($matches[1]);
        } else {
            $token = trim($authHeader);
        }

        if (empty($token)) {
            return $this->errorResponse('Token requerido', 401); // El 401 es para el no autorizado
        }

        ////Validar token existente en la base de datos
        $exists = AToken::where('token', $token)->first();

        if (!$exists) {
            return $this->errorResponse('Token inválido', 401);
        }

        ////Token válido, continuar con la petición
        return $handler->handle($request);
    }
    /////////Crea una respuesta de error JSON
    private function errorResponse(string $message, int $status): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode(['error' => $message]));
        
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}