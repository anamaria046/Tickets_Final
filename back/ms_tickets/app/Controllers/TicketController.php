<?php

namespace App\Controllers;

use App\Repositories\TicketRepository;
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
        $this->ticketRepo = new TicketRepository();
        $this->actividadRepo = new TicketActividadRepository();
    }

    ///////////Crear un nuevo ticket (gestores)
    public function createTicket($request, $response)
    {
        $data = $request->getParsedBody();

        //Obtener usuario desde token
        $authHeader =
            $request->getHeaderLine('Authorization') ?: ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
        } else {
            $token = trim($authHeader);
        }

        $auth = AToken::where('token', $token)->first();
        $user = $auth ? Users::find($auth->user_id) : null;

        //Verificar que sea un gestor
        if (!$user || $user->role !== 'gestor') {
            $response->getBody()->write(json_encode(['error' => 'Solo los gestores pueden crear tickets'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        //Validación básica
        if (empty($data['titulo']) || empty($data['descripcion'])) {
            $response->getBody()->write(json_encode(['error' => 'Título y descripción son obligatorios'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        //Crear ticket
        $ticketData = [
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'],
            'estado' => 'abierto',
            'gestor_id' => $user->id
        ];

        $ticket = $this->ticketRepo->createTicket($ticketData);

        //Registrar actividad inicial
        $this->actividadRepo->addActivity(
            $ticket->id,
            $user->id,
            'Ticket creado: ' . $data['titulo']
        );

        $response->getBody()->write(json_encode($ticket, JSON_UNESCAPED_UNICODE));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }
    ////////////// Listar mis tickets (gestores)

    public function listMyTickets($request, $response)
    {
        //Obtener usuario desde token
        $authHeader =
            $request->getHeaderLine('Authorization') ?: ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
        } else {
            $token = trim($authHeader);
        }

        $auth = AToken::where('token', $token)->first();
        $user = $auth ? Users::find($auth->user_id) : null;

        if (!$user || $user->role !== 'gestor') {
            $response->getBody()->write(json_encode(['error' => 'Solo los gestores pueden ver sus tickets'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $tickets = $this->ticketRepo->getTicketsByGestor($user->id);
        $response->getBody()->write(json_encode($tickets, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }

     //////////Listar todos los tickets (admins)
  
    public function listAllTickets($request, $response)
    {
        // Obtener usuario desde token
        $authHeader =
            $request->getHeaderLine('Authorization') ?: ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
        } else {
            $token = trim($authHeader);
        }

        $auth = AToken::where('token', $token)->first();
        $user = $auth ? Users::find($auth->user_id) : null;

        if (!$user || $user->role !== 'admin') {
            $response->getBody()->write(json_encode(['error' => 'Solo los administradores pueden ver todos los tickets'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $tickets = $this->ticketRepo->getAllTickets();
        $response->getBody()->write(json_encode($tickets, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }

    ////////////////////Ver detalles de un ticket
    public function getTicketDetails($request, $response, $args)
    {
        $ticketId = $args['id'];

        //Obtener usuario desde token
        $authHeader =
            $request->getHeaderLine('Authorization') ?: ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
        } else {
            $token = trim($authHeader);
        }

        $auth = AToken::where('token', $token)->first();
        $user = $auth ? Users::find($auth->user_id) : null;

        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Usuario no autenticado'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $ticket = $this->ticketRepo->getTicketById($ticketId);

        if (!$ticket) {
            $response->getBody()->write(json_encode(['error' => 'Ticket no encontrado'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        //Si es gestor, solo puede ver sus propios tickets
        if ($user->role === 'gestor' && $ticket->gestor_id != $user->id) {
            $response->getBody()->write(json_encode(['error' => 'No tienes permiso para ver este ticket'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($ticket, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }

    ////////////////////// Actualizar estado de un ticket (admins)

    public function updateTicketStatus($request, $response, $args)
    {
        $ticketId = $args['id'];
        $data = $request->getParsedBody();

        //Obtener usuario desde token
        $authHeader =
            $request->getHeaderLine('Authorization') ?: ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
        } else {
            $token = trim($authHeader);
        }

        $auth = AToken::where('token', $token)->first();
        $user = $auth ? Users::find($auth->user_id) : null;

        if (!$user || $user->role !== 'admin') {
            $response->getBody()->write(json_encode(['error' => 'Solo los administradores pueden cambiar el estado'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        //Validar estado
        $estadosValidos = ['abierto', 'en_progreso', 'resuelto', 'cerrado'];
        if (empty($data['estado']) || !in_array($data['estado'], $estadosValidos)) {
            $response->getBody()->write(json_encode(['error' => 'Estado inválido. Valores permitidos: abierto, en_progreso, resuelto, cerrado'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $ticket = $this->ticketRepo->getTicketById($ticketId);
        if (!$ticket) {
            $response->getBody()->write(json_encode(['error' => 'Ticket no encontrado'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $updated = $this->ticketRepo->updateTicketStatus($ticketId, $data['estado']);

        if ($updated) {
            //Registrar actividad
            $this->actividadRepo->addActivity(
                $ticketId,
                $user->id,
                'Estado cambiado a: ' . $data['estado']
            );

            $response->getBody()->write(json_encode(['message' => 'Estado actualizado correctamente'], JSON_UNESCAPED_UNICODE));
        } else {
            $response->getBody()->write(json_encode(['error' => 'Error al actualizar el estado'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    //////////////////Asignar ticket a un admin (admins)
    public function assignTicket($request, $response, $args)
    {
        $ticketId = $args['id'];
        $data = $request->getParsedBody();

        //Obtener usuario desde token
        $authHeader =
            $request->getHeaderLine('Authorization') ?: ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
        } else {
            $token = trim($authHeader);
        }

        $auth = AToken::where('token', $token)->first();
        $user = $auth ? Users::find($auth->user_id) : null;

        if (!$user || $user->role !== 'admin') {
            $response->getBody()->write(json_encode(['error' => 'Solo los administradores pueden asignar tickets'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        if (empty($data['admin_id'])) {
            $response->getBody()->write(json_encode(['error' => 'admin_id es obligatorio'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        //Verificar que el admin existe y es admin
        $admin = Users::find($data['admin_id']);
        if (!$admin || $admin->role !== 'admin') {
            $response->getBody()->write(json_encode(['error' => 'El usuario especificado no es un administrador'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $ticket = $this->ticketRepo->getTicketById($ticketId);
        if (!$ticket) {
            $response->getBody()->write(json_encode(['error' => 'Ticket no encontrado'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $updated = $this->ticketRepo->assignTicket($ticketId, $data['admin_id']);

        if ($updated) {
            //Registrar actividad
            $this->actividadRepo->addActivity(
                $ticketId,
                $user->id,
                'Ticket asignado a: ' . $admin->name
            );

            $response->getBody()->write(json_encode(['message' => 'Ticket asignado correctamente'], JSON_UNESCAPED_UNICODE));
        } else {
            $response->getBody()->write(json_encode(['error' => 'Error al asignar el ticket'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    /////////////Agregar comentario a un ticket
    public function addComment($request, $response, $args)
    {
        $ticketId = $args['id'];
        $data = $request->getParsedBody();

        //Obtener usuario desde token
        $authHeader =
            $request->getHeaderLine('Authorization') ?: ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
        } else {
            $token = trim($authHeader);
        }

        $auth = AToken::where('token', $token)->first();
        $user = $auth ? Users::find($auth->user_id) : null;

        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Usuario no autenticado'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $ticket = $this->ticketRepo->getTicketById($ticketId);
        if (!$ticket) {
            $response->getBody()->write(json_encode(['error' => 'Ticket no encontrado'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        //Si es gestor, solo puede comentar en sus propios tickets
        if ($user->role === 'gestor' && $ticket->gestor_id != $user->id) {
            $response->getBody()->write(json_encode(['error' => 'No tienes permiso para comentar en este ticket'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        if (empty($data['mensaje'])) {
            $response->getBody()->write(json_encode(['error' => 'El mensaje es obligatorio'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $actividad = $this->actividadRepo->addActivity(
            $ticketId,
            $user->id,
            $data['mensaje']
        );

        $response->getBody()->write(json_encode($actividad, JSON_UNESCAPED_UNICODE));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

//////////// Ver historial de actividad de un ticket
    public function getTicketHistory($request, $response, $args)
    {
        $ticketId = $args['id'];

        //Obtener usuario desde token
        $authHeader =
            $request->getHeaderLine('Authorization') ?: ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
        } else {
            $token = trim($authHeader);
        }

        $auth = AToken::where('token', $token)->first();
        $user = $auth ? Users::find($auth->user_id) : null;

        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Usuario no autenticado'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $ticket = $this->ticketRepo->getTicketById($ticketId);
        if (!$ticket) {
            $response->getBody()->write(json_encode(['error' => 'Ticket no encontrado'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        //Si es gestor, solo puede ver el historial de sus propios tickets
        if ($user->role === 'gestor' && $ticket->gestor_id != $user->id) {
            $response->getBody()->write(json_encode(['error' => 'No tienes permiso para ver el historial de este ticket'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $history = $this->actividadRepo->getTicketHistory($ticketId);
        $response->getBody()->write(json_encode($history, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
     ///////////////////Busca y filtrar tickets (admins)
  
    public function searchTickets($request, $response)
    {
        //Obtener usuario desde token
        $authHeader =
            $request->getHeaderLine('Authorization') ?: ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
        } else {
            $token = trim($authHeader);
        }

        $auth = AToken::where('token', $token)->first();
        $user = $auth ? Users::find($auth->user_id) : null;

        if (!$user || $user->role !== 'admin') {
            $response->getBody()->write(json_encode(['error' => 'Solo los administradores pueden buscar tickets'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $params = $request->getQueryParams();
        $filters = [];

        if (isset($params['estado'])) {
            $filters['estado'] = $params['estado'];
        }
        if (isset($params['gestor_id'])) {
            $filters['gestor_id'] = $params['gestor_id'];
        }
        if (isset($params['admin_id'])) {
            $filters['admin_id'] = $params['admin_id'];
        }

        $tickets = $this->ticketRepo->searchTickets($filters);
        $response->getBody()->write(json_encode($tickets, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
