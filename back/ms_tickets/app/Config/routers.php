<?php

use Slim\Factory\AppFactory;
use App\Controllers\TicketController;
use App\Middleware\Token;

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Crear instancia del controlador
$ticketController = new TicketController();

$app->group('', function ($group) use ($ticketController) {

    //Crear ticket (gestores)
    $group->post('/tickets', [$ticketController, 'createTicket']);

    //Listar mis tickets (gestores)
    $group->get('/tickets/my', [$ticketController, 'listMyTickets']);

    //Buscar/filtrar tickets (admin)
    $group->get('/tickets/search', [$ticketController, 'searchTickets']);

    //Listar todos los tickets (admin)
    $group->get('/tickets', [$ticketController, 'listAllTickets']);

    //Ver detalles de un ticket
    $group->get('/tickets/{id}', [$ticketController, 'getTicketDetails']);

    //Actualizar estado de un ticket (admin)
    $group->put('/tickets/{id}/status', [$ticketController, 'updateTicketStatus']);

    // Asignar ticket a un admin (admin)
    $group->put('/tickets/{id}/assign', [$ticketController, 'assignTicket']);

    //Agregar comentario a un ticket
    $group->post('/tickets/{id}/comments', [$ticketController, 'addComment']);

    //Ver historial de actividad de un ticket
    $group->get('/tickets/{id}/history', [$ticketController, 'getTicketHistory']);
})->add(new Token());

return $app;
