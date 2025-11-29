<?php

namespace App\Controllers;

use App\Repositories\TicketDataRepository;
use App\Repositories\TicketActividadRepository;
use App\Models\AToken;
use App\Models\Users;


////////////////Clase base abstracta con utilidades compartidas para todos los controladores de tickets
 
abstract class BaseController
{
    protected $ticketRepo;
    protected $actividadRepo;

    public function __construct()
    {
        $this->ticketRepo = new TicketDataRepository();
        $this->actividadRepo = new TicketActividadRepository();
    }

    
    ////////Extraer token del request
    protected function extractTokenFromRequest($request)
    {
        $authHeader = 
            $request->getHeaderLine('Authorization') ?: 
            ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: 
            ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }
        
        return trim($authHeader);
    }

    ////////////////Obtener usuario desde el request
    protected function getUserFromRequest($request)
    {
        $token = $this->extractTokenFromRequest($request);
        $auth = AToken::where('token', $token)->first();
        return $auth ? Users::find($auth->user_id) : null;
    }

    /////////MÉTODOS DE AUTORIZACIÓN 

    //////////////Verificar que el usuario sea gestor
    protected function requireGestorRole($user)
    {
        if (!$user || $user->role !== 'gestor') {
            throw new \Exception('Solo los gestores pueden realizar esta acción');
        }
    }
    ///Verificar que el usuario sea admina
     
    protected function requireAdminRole($user)
    {
        if (!$user || $user->role !== 'admin') {
            throw new \Exception('Solo los administradores pueden realizar esta acción');
        }
    }

    ////mÉTODOS DE RESPUESTA 
     //////Respuesta de éxito
    protected function successResponse($response, $data, $statusCode = 200)
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withStatus($statusCode)->withHeader('Content-Type', 'application/json');
    }
    ////Respuesta de error
    protected function errorResponse($response, $message, $statusCode = 400)
    {
        $response->getBody()->write(json_encode(['error' => $message], JSON_UNESCAPED_UNICODE));
        return $response->withStatus($statusCode)->withHeader('Content-Type', 'application/json');
    }
}
