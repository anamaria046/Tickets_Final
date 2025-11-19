<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AToken extends Model
{
    protected $table = 'auth_tokens';  
    protected $fillable = ['user_id', 'token'];
    public $timestamps = true;

    // Relación con usuario
    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}