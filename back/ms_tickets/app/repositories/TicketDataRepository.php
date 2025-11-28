<?php

namespace App\Repositories;

use App\Models\Tickets;

class TicketDataRepository
{
    
    /////////Crear un nuevo ticket
    public function createTicket($data)
    {
        return Tickets::create($data);
    }

    /////////Obtener tickets de un gestor específico

    public function getTicketsByGestor($gestorId)
    {
        return Tickets::where('gestor_id', $gestorId)
            ->with(['gestor', 'admin'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /////////Obtener todos los tickets
    
    public function getAllTickets()
    {
        return Tickets::with(['gestor', 'admin'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    
    /////////Obtener un ticket por ID con relaciones
    
    public function getTicketById($id)
    {
        return Tickets::with(['gestor', 'admin', 'actividades.usuario'])
            ->find($id);
    }

    /////////Actualizar el estado de un ticket
    
    public function updateTicketStatus($id, $estado)
    {
        return Tickets::where('id', $id)->update(['estado' => $estado]);
    }

    
    /////////Asignar un ticket a un admin
    
    public function assignTicket($id, $adminId)
    {
        return Tickets::where('id', $id)->update(['admin_id' => $adminId]);
    }

    /////////Buscar tickets con filtros
   
    public function searchTickets($filters)
    {
        $query = Tickets::with(['gestor', 'admin']);

        if (isset($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (isset($filters['gestor_id'])) {
            $query->where('gestor_id', $filters['gestor_id']);
        }

        if (isset($filters['admin_id'])) {
            $query->where('admin_id', $filters['admin_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
