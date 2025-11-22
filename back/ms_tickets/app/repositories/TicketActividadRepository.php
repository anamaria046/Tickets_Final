<?php

namespace App\Repositories;

use App\Models\TicketActividad;

class TicketActividadRepository
{
    ///////// Agregar una actividad/comentario a un ticket
    public function addActivity($ticketId, $userId, $mensaje)
    {
        return TicketActividad::create([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'mensaje' => $mensaje
        ]);
    } 
    /////////Obtener el historial de actividades de un ticket
    public function getTicketHistory($ticketId)
    {
        return TicketActividad::where('ticket_id', $ticketId)
            ->with('usuario')
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
