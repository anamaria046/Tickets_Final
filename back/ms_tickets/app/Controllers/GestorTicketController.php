<?php

namespace App\Controllers;

class GestorTicketController extends BaseController
{
     //////////////Crear un nuevo ticket 
    public function createTicket($request, $response)
    {
        $data = $request->getParsedBody();
        $user = $this->getUserFromRequest($request);

        ////Verificar que sea un gestor
        $this->requireGestorRole($user);

        ////Validación básica
        if (empty($data['titulo']) || empty($data['descripcion'])) {
            return $this->errorResponse($response, 'Título y descripción son obligatorios', 400);
        }

        //////Crear ticket
        $ticket = $this->ticketRepo->createTicket([
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'],
            'estado' => 'abierto',
            'gestor_id' => $user->id
        ]);

        ///////Registrar actividad inicial
        $this->actividadRepo->addActivity($ticket->id, $user->id, 'Ticket creado: ' . $data['titulo']);

        return $this->successResponse($response, $ticket, 201);
    }

    ///////////Listar mis ticketsd
    public function listMyTickets($request, $response)
    {
        $user = $this->getUserFromRequest($request);
        $this->requireGestorRole($user);

        $tickets = $this->ticketRepo->getTicketsByGestor($user->id);
        return $this->successResponse($response, $tickets);
    }

    /////////////Ver detalles de un ticket (solo sus propios tickets)
    public function getTicketDetails($request, $response, $args)
    {
        $ticketId = $args['id'];
        $user = $this->getUserFromRequest($request);

        $this->requireGestorRole($user);

        $ticket = $this->ticketRepo->getTicketById($ticketId);
        
        if (!$ticket) {
            return $this->errorResponse($response, 'Ticket no encontrado', 404);
        }

        //7///Solo puede ver sus propios tickets
        if ($ticket->gestor_id != $user->id) {
            return $this->errorResponse($response, 'No tienes permiso para ver este ticket', 403);
        }

        return $this->successResponse($response, $ticket);
    }

    ////////////Agregar comentario a un ticket (solo a sus propios tickets)
    public function addComment($request, $response, $args)
    {
        $ticketId = $args['id'];
        $data = $request->getParsedBody();
        $user = $this->getUserFromRequest($request);
        $this->requireGestorRole($user);
        $ticket = $this->ticketRepo->getTicketById($ticketId);
        if (!$ticket) {
            return $this->errorResponse($response, 'Ticket no encontrado', 404);
        }

        ///////Solo puede comentar en sus propios tickets
        if ($ticket->gestor_id != $user->id) {
            return $this->errorResponse($response, 'No tienes permiso para comentar en este ticket', 403);
        }
        if (empty($data['mensaje'])) {
            return $this->errorResponse($response, 'El mensaje es obligatorio', 400);
        }
        $actividad = $this->actividadRepo->addActivity($ticketId, $user->id, $data['mensaje']);
        return $this->successResponse($response, $actividad, 201);
    }

    
     /////////////Ver historial de actividad de un ticket (solo de sus propios tickets)
    public function getTicketHistory($request, $response, $args)
    {
        $ticketId = $args['id'];
        $user = $this->getUserFromRequest($request);
        $this->requireGestorRole($user);
        $ticket = $this->ticketRepo->getTicketById($ticketId);
        if (!$ticket) {
            return $this->errorResponse($response, 'Ticket no encontrado', 404);
        }
        ////////Solo puede ver el historial de sus propios tickets
        if ($ticket->gestor_id != $user->id) {
            return $this->errorResponse($response, 'No tienes permiso para ver el historial de este ticket', 403);
        }
        $history = $this->actividadRepo->getTicketHistory($ticketId);
        return $this->successResponse($response, $history);
    }
}
