<?php
namespace App\Middleware;
use App\Models\AToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Token
{
    
    public function __invoke(ServerRequestInterface $request, $handler): ResponseInterface
    {
        $headers = $request->getHeaders();

        if (!isset($headers['Authorization'])) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Token requerido']));
            return $response->withStatus(401);
        }

        $token = str_replace("Bearer ", "", $headers['Authorization'][0]);

        $auth = AToken::where('token', $token)->first();

        if (!$auth) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Token inválido']));
            return $response->withStatus(401);
        }

        return $handler->handle($request);
    }
    
}
