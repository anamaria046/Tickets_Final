<?php

namespace App\Controllers;
use App\Models\Users;

class AdminTicketController extends BaseController
{
     ///////Listar todos los tickets 
    public function listAllTickets($request, $response)
    {
        $user = $this->getUserFromRequest($request);
        $this->requireAdminRole($user);
        $tickets = $this->ticketRepo->getAllTickets();
        return $this->successResponse($response, $tickets);
    }

    ///////Buscar y filtrar tickets 
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

    ///////Ver detalles de cualquier ticket
    public function getTicketDetails($request, $response, $args)
    {
        $ticketId = $args['id'];
        $user = $this->getUserFromRequest($request);
        $this->requireAdminRole($user);
        $ticket = $this->ticketRepo->getTicketById($ticketId);
        if (!$ticket) {
            return $this->errorResponse($response, 'Ticket no encontrado', 404);
        }
        return $this->successResponse($response, $ticket);
    }

    //////////Actualizar estado de un ticket
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

     ///////Asignar ticket a un admin (solo admins)
    public function assignTicket($request, $response, $args)
    {
        $ticketId = $args['id'];
        $data = $request->getParsedBody();
        $user = $this->getUserFromRequest($request);
        $this->requireAdminRole($user);
        if (empty($data['admin_id'])) {
            return $this->errorResponse($response, 'admin_id es obligatorio', 400);
        }
        ////Verificar que el admin existe y es admin
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

    //////////Agregar comentario a cualquier ticket
    public function addComment($request, $response, $args)
    {
        $ticketId = $args['id'];
        $data = $request->getParsedBody();
        $user = $this->getUserFromRequest($request);
        $this->requireAdminRole($user);
        $ticket = $this->ticketRepo->getTicketById($ticketId);
        if (!$ticket) {
            return $this->errorResponse($response, 'Ticket no encontrado', 404);
        }
        if (empty($data['mensaje'])) {
            return $this->errorResponse($response, 'El mensaje es obligatorio', 400);
        }
        $actividad = $this->actividadRepo->addActivity($ticketId, $user->id, $data['mensaje']);
        return $this->successResponse($response, $actividad, 201);
    }

     /////Ver historial de actividad de cualquier ticket
    public function getTicketHistory($request, $response, $args)
    {
        $ticketId = $args['id'];
        $user = $this->getUserFromRequest($request);

        $this->requireAdminRole($user);

        $ticket = $this->ticketRepo->getTicketById($ticketId);
        if (!$ticket) {
            return $this->errorResponse($response, 'Ticket no encontrado', 404);
        }
        $history = $this->actividadRepo->getTicketHistory($ticketId);
        return $this->successResponse($response, $history);
    }
}
