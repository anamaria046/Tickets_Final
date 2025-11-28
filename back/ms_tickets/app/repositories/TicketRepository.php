<?php

namespace App\Repositories;

use App\Controllers\TicketController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TicketRepository
{
    /**
     * Crear un nuevo ticket (gestores)
     */
    public function createTicket(Request $request, Response $response)
    {
        $controller = new TicketController();
        return $controller->createTicket($request, $response);
    }

    /**
     * Listar mis tickets (gestores)
     */
    public function listMyTickets(Request $request, Response $response)
    {
        $controller = new TicketController();
        return $controller->listMyTickets($request, $response);
    }

    /**
     * Listar todos los tickets (admins)
     */
    public function listAllTickets(Request $request, Response $response)
    {
        $controller = new TicketController();
        return $controller->listAllTickets($request, $response);
    }

    /**
     * Ver detalles de un ticket
     */
    public function getTicketDetails(Request $request, Response $response, $args)
    {
        $controller = new TicketController();
        return $controller->getTicketDetails($request, $response, $args);
    }

    /**
     * Actualizar estado de un ticket (admins)
     */
    public function updateTicketStatus(Request $request, Response $response, $args)
    {
        $controller = new TicketController();
        return $controller->updateTicketStatus($request, $response, $args);
    }

    /**
     * Asignar ticket a un admin (admins)
     */
    public function assignTicket(Request $request, Response $response, $args)
    {
        $controller = new TicketController();
        return $controller->assignTicket($request, $response, $args);
    }

    /**
     * Agregar comentario a un ticket
     */
    public function addComment(Request $request, Response $response, $args)
    {
        $controller = new TicketController();
        return $controller->addComment($request, $response, $args);
    }

    /**
     * Ver historial de actividad de un ticket
     */
    public function getTicketHistory(Request $request, Response $response, $args)
    {
        $controller = new TicketController();
        return $controller->getTicketHistory($request, $response, $args);
    }

    /**
     * Buscar/filtrar tickets (admins)
     */
    public function searchTickets(Request $request, Response $response)
    {
        $controller = new TicketController();
        return $controller->searchTickets($request, $response);
    }
}
