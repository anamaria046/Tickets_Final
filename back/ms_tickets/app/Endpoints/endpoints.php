<?php

use App\Controllers\GestorTicketController;
use App\Controllers\AdminTicketController;
use App\Middleware\Token;

return function ($app) {
    ////Todas las rutas de tickets requieren autenticación
    $app->group('', function ($group) {
        $gestorController = new GestorTicketController();
        $adminController = new AdminTicketController();
        
        ///////rear ticket (gestores)
        $group->post('/tickets', function ($request, $response) use ($gestorController) {
            return $gestorController->createTicket($request, $response);
        });
        
        //////Listar mis tickets (gestores)
        $group->get('/tickets/my', function ($request, $response) use ($gestorController) {
            return $gestorController->listMyTickets($request, $response);
        });
        
        ///////Buscar/filtrar tickets (admins)
        $group->get('/tickets/search', function ($request, $response) use ($adminController) {
            return $adminController->searchTickets($request, $response);
        });
        
        //////////Listar todos los tickets (admins)
        $group->get('/tickets', function ($request, $response) use ($adminController) {
            return $adminController->listAllTickets($request, $response);
        });
        
        ////////Ver detalles de un ticket (gestor o admin según permisos)
        $group->get('/tickets/{id}', function ($request, $response, $args) use ($gestorController, $adminController) {
            /////Intentar con el controlador apropiado según el rol del usuario
            $token = $request->getHeaderLine('Authorization') ?: 
                     ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: 
                     ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
            
            if (preg_match('/Bearer\s+(.*)$/i', $token, $matches)) {
                $token = trim($matches[1]);
            } else {
                $token = trim($token);
            }
            
            $auth = \App\Models\AToken::where('token', $token)->first();
            $user = $auth ? \App\Models\Users::find($auth->user_id) : null;
            
            if ($user && $user->role === 'admin') {
                return $adminController->getTicketDetails($request, $response, $args);
            } else {
                return $gestorController->getTicketDetails($request, $response, $args);
            }
        });
        
        //////Actualizar estado de un ticket (admins)
        $group->put('/tickets/{id}/status', function ($request, $response, $args) use ($adminController) {
            return $adminController->updateTicketStatus($request, $response, $args);
        });
        
        //////Asignar ticket a un admin (admins)
        $group->put('/tickets/{id}/assign', function ($request, $response, $args) use ($adminController) {
            return $adminController->assignTicket($request, $response, $args);
        });
        
        ////////Agregar comentario a un ticket (gestor o admin según permisos)
        $group->post('/tickets/{id}/comments', function ($request, $response, $args) use ($gestorController, $adminController) {
            $token = $request->getHeaderLine('Authorization') ?: 
                     ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: 
                     ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
            
            if (preg_match('/Bearer\s+(.*)$/i', $token, $matches)) {
                $token = trim($matches[1]);
            } else {
                $token = trim($token);
            }
            
            $auth = \App\Models\AToken::where('token', $token)->first();
            $user = $auth ? \App\Models\Users::find($auth->user_id) : null;
            
            if ($user && $user->role === 'admin') {
                return $adminController->addComment($request, $response, $args);
            } else {
                return $gestorController->addComment($request, $response, $args);
            }
        });
        
        //////////Ver historial de actividad de un ticket (gestor o admin según permisos)
        $group->get('/tickets/{id}/history', function ($request, $response, $args) use ($gestorController, $adminController) {
            $token = $request->getHeaderLine('Authorization') ?: 
                     ($request->getServerParams()['HTTP_AUTHORIZATION'] ?? '') ?: 
                     ($request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
            
            if (preg_match('/Bearer\s+(.*)$/i', $token, $matches)) {
                $token = trim($matches[1]);
            } else {
                $token = trim($token);
            }
            
            $auth = \App\Models\AToken::where('token', $token)->first();
            $user = $auth ? \App\Models\Users::find($auth->user_id) : null;
            
            if ($user && $user->role === 'admin') {
                return $adminController->getTicketHistory($request, $response, $args);
            } else {
                return $gestorController->getTicketHistory($request, $response, $args);
            }
        });
    })->add(new Token());
};
