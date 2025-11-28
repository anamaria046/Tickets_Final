<?php

use App\Repositories\TicketRepository;
use App\Middleware\Token;

return function ($app) {
    // Todas las rutas de tickets requieren autenticación
    $app->group('', function ($group) {
        $repository = new TicketRepository();
        
        // Crear ticket (gestores)
        $group->post('/tickets', function ($request, $response) use ($repository) {
            return $repository->createTicket($request, $response);
        });
        
        // Listar mis tickets (gestores)
        $group->get('/tickets/my', function ($request, $response) use ($repository) {
            return $repository->listMyTickets($request, $response);
        });
        
        // Buscar/filtrar tickets (admins)
        $group->get('/tickets/search', function ($request, $response) use ($repository) {
            return $repository->searchTickets($request, $response);
        });
        
        // Listar todos los tickets (admins)
        $group->get('/tickets', function ($request, $response) use ($repository) {
            return $repository->listAllTickets($request, $response);
        });
        
        // Ver detalles de un ticket
        $group->get('/tickets/{id}', function ($request, $response, $args) use ($repository) {
            return $repository->getTicketDetails($request, $response, $args);
        });
        
        // Actualizar estado de un ticket (admins)
        $group->put('/tickets/{id}/status', function ($request, $response, $args) use ($repository) {
            return $repository->updateTicketStatus($request, $response, $args);
        });
        
        // Asignar ticket a un admin (admins)
        $group->put('/tickets/{id}/assign', function ($request, $response, $args) use ($repository) {
            return $repository->assignTicket($request, $response, $args);
        });
        
        // Agregar comentario a un ticket
        $group->post('/tickets/{id}/comments', function ($request, $response, $args) use ($repository) {
            return $repository->addComment($request, $response, $args);
        });
        
        // Ver historial de actividad de un ticket
        $group->get('/tickets/{id}/history', function ($request, $response, $args) use ($repository) {
            return $repository->getTicketHistory($request, $response, $args);
        });
    })->add(new Token());
};
