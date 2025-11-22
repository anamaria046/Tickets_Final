<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketActividad extends Model
{
    protected $table = 'ticket_actividad';
    
    protected $fillable = [
        'ticket_id',
        'user_id',
        'mensaje'
    ];

    public $timestamps = true;

    /////// Relación: La actividad pertenece a un ticket
     
    public function ticket()
    {
        return $this->belongsTo(Tickets::class, 'ticket_id');
    }

    /////////////////Relación: La actividad pertenece a un usuario
    
    public function usuario()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
