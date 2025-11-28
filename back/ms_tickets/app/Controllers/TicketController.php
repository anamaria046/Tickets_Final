<?php

namespace App\Controllers;

use App\Repositories\TicketDataRepository;
use App\Repositories\TicketActividadRepository;
use App\Models\AToken;
use App\Models\Users;
use App\Models\Tickets;

class TicketController
{
    protected $ticketRepo;
    protected $actividadRepo;

    public function __construct()
    {
        $this->ticketRepo = new TicketDataRepository();
        $this->actividadRepo = new TicketActividadRepository();
    }

    // ==================== MÉTODOS PÚBLICOS ====================

    /**
     * Crear un nuevo ticket (gestores)
     */
    public function createTicket($request, $response)
    {
        $data = $request->getParsedBody();
        $user = $this->getUserFromRequest($request);

        // Verificar que sea un gestor
        $this->requireGestorRole($user);

        // Validación básica
        if (empty($data['titulo']) || empty($data['descripcion'])) {
            return $this->errorResponse($response, 'Título y descripción son obligatorios', 400);
        }

        // Crear ticket
        $ticket = $this->ticketRepo->createTicket([
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'],
            'estado' => 'abierto',
            'gestor_id' => $user->id
        ]);

        // Registrar actividad inicial
        $this->actividadRepo->addActivity($ticket->id, $user->id, 'Ticket creado: ' . $data['titulo']);

        return $this->successResponse($response, $ticket, 201);
    }

    /**
     * Listar mis tickets (gestores)
     */
    public function listMyTickets($request, $response)
    {
        $user = $this->getUserFromRequest($request);
        $this->requireGestorRole($user);

        $tickets = $this->ticketRepo->getTicketsByGestor($user->id);
        return $this->successResponse($response, $tickets);
    }

    /**
     * Listar todos los tickets (admins)
     */
    public function listAllTickets($request, $response)
    {
        $user = $this->getUserFromRequest($request);
        $this->requireAdminRole($user);

        $tickets = $this->ticketRepo->getAllTickets();
        return $this->successResponse($response, $tickets);
    }

    /**
     * Ver detalles de un ticket
     */
    public function getTicketDetails($request, $response, $args)
    {
        $ticketId = $args['id'];
        $user = $this->getUserFromRequest($request);

        $ticket = $this->ticketRepo->getTicketById($ticketId);
        
        if (!$ticket) {
            return $this->errorResponse($response, 'Ticket no encontrado', 404);
        }

        // Si es gestor, solo puede ver sus propios tickets
        if ($user->role === 'gestor' && $ticket->gestor_id != $user->id) {
            return $this->errorResponse($response, 'No tienes permiso para ver este ticket', 403);
        }

        return $this->successResponse($response, $ticket);
    }

    /**
     * Actualizar estado de un ticket (admins)
     */
    public function updateTicketStatus($request, $response, $args)
    {
        $ticketId = $args['id'];
        $data = $request->getParsedBody();
        $user = $this->getUserFromRequest($request);

        $this->requireAdminRole($user);

        // Validar estado
        $estadosValidos = ['abierto', 'en_progreso', 'resuelto', 'cerrado'];
        if (empty($data['estado']) || !in_array($data['estado'], $estadosValidos)) {
            return $this->errorResponse($response, 'Estado inválido. Valores permitidos: abierto, en_progreso, resuelto, cerrado', 400);
        }

        $ticket = $this->ticketRepo->getTicketById($ticketId);
        if (!$ticket) {
            return $this->errorResponse($response, 'Ticket no encontrado', 404);
        }

        $updated = $this->ticketRepo->updateTicketStatus($ticketId, $data['estado']);

        if ($updated) {
            $this->actividadRepo->addActivity($ticketId, $user->id, 'Estado cambiado a: ' . $data['estado']);
            return $this->successResponse($response, ['message' => 'Estado actualizado correctamente']);
        }

        return $this->errorResponse($response, 'Error al actualizar el estado', 500);
    }

    /**
     * Asignar ticket a un admin (admins)
     */
    public function assignTicket($request, $response, $args)
    {
        $ticketId = $args['id'];
        $data = $request->getParsedBody();
        $user = $this->getUserFromRequest($request);

        $this->requireAdminRole($user);

        if (empty($data['admin_id'])) {
            return $this->errorResponse($response, 'admin_id es obligatorio', 400);
        }

        // Verificar que el admin existe y es admin
        $admin = Users::find($data['admin_id']);
        if (!$admin || $admin->role !== 'admin') {
            return $this->errorResponse($response, 'El usuario especificado no es un administrador', 400);
        }

        $ticket = $this->ticketRepo->getTicketById($ticketId);
        if (!$ticket) {
            return $this->errorResponse($response, 'Ticket no encontrado', 404);
        }

        $updated = $this->ticketRepo->assignTicket($ticketId, $data['admin_id']);

        if ($updated) {
            $this->actividadRepo->addActivity($ticketId, $user->id, 'Ticket asignado a: ' . $admin->name);
            return $this->successResponse($response, ['message' => 'Ticket asignado correctamente']);
        }

        return $this->errorResponse($response, 'Error al asignar el ticket', 500);
    }

    /**
     * Agregar comentario a un ticket
     */
    public function addComment($request, $response, $args)
    {
        $ticketId = $args['id'];
        $data = $request->getParsedBody();
        $user = $this->getUserFromRequest($request);

        $ticket = $this->ticketRepo->getTicketById($ticketId);
        if (!$ticket) {
            return $this->errorResponse($response, 'Ticket no encontrado', 404);
        }

        // Si es gestor, solo puede comentar en sus propios tickets
        if ($user->role === 'gestor' && $ticket->gestor_id != $user->id) {
            return $this->errorResponse($response, 'No tienes permiso para comentar en este ticket', 403);
        }

        if (empty($data['mensaje'])) {
            return $this->errorResponse($response, 'El mensaje es obligatorio', 400);
        }

        $actividad = $this->actividadRepo->addActivity($ticketId, $user->id, $data['mensaje']);

        return $this->successResponse($response, $actividad, 201);
    }

    /**
     * Ver historial de actividad de un ticket
     */
    public function getTicketHistory($request, $response, $args)
    {
        $ticketId = $args['id'];
        $user = $this->getUserFromRequest($request);

        $ticket = $this->ticketRepo->getTicketById($ticketId);
        if (!$ticket) {
            return $this->errorResponse($response, 'Ticket no encontrado', 404);
        }

        // Si es gestor, solo puede ver el historial de sus propios tickets
        if ($user->role === 'gestor' && $ticket->gestor_id != $user->id) {
            return $this->errorResponse($response, 'No tienes permiso para ver el historial de este ticket', 403);
        }

        $history = $this->actividadRepo->getTicketHistory($ticketId);
        return $this->successResponse($response, $history);
    }

    /**
     * Buscar y filtrar tickets (admins)
     */
    public function searchTickets($request, $response)
    {
        $user = $this->getUserFromRequest($request);
        $this->requireAdminRole($user);

        $params = $request->getQueryParams();
        $filters = [];

        if (isset($params['estado'])) $filters['estado'] = $params['estado'];
        if (isset($params['gestor_id'])) $filters['gestor_id'] = $params['gestor_id'];
        if (isset($params['admin_id'])) $filters['admin_id'] = $params['admin_id'];

        $tickets = $this->ticketRepo->searchTickets($filters);
        return $this->successResponse($response, $tickets);
    }

    // ==================== MÉTODOS AUXILIARES PRIVADOS ====================

    /**
     * Extraer token del request
     */
    private function extractTokenFromRequest($request)
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

    /**
     * Obtener usuario desde el request
     */
    private function getUserFromRequest($request)
    {
        $token = $this->extractTokenFromRequest($request);
        $auth = AToken::where('token', $token)->first();
        return $auth ? Users::find($auth->user_id) : null;
    }

    /**
     * Verificar que el usuario sea gestor
     */
    private function requireGestorRole($user)
    {
        if (!$user || $user->role !== 'gestor') {
            throw new \Exception('Solo los gestores pueden realizar esta acción');
        }
    }

    /**
     * Verificar que el usuario sea admin
     */
    private function requireAdminRole($user)
    {
        if (!$user || $user->role !== 'admin') {
            throw new \Exception('Solo los administradores pueden realizar esta acción');
        }
    }

    /**
     * Respuesta de éxito
     */
    private function successResponse($response, $data, $statusCode = 200)
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withStatus($statusCode)->withHeader('Content-Type', 'application/json');
    }

    //Respuesta de error
    
    private function errorResponse($response, $message, $statusCode = 400)
    {
        $response->getBody()->write(json_encode(['error' => $message], JSON_UNESCAPED_UNICODE));
        return $response->withStatus($statusCode)->withHeader('Content-Type', 'application/json');
    }
}
