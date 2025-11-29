<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name','email','password','role'];
    protected $hidden = [
        'password'
    ];
    public $timestamps = true;

    ////Relación con tokens
    public function tokens()
    {
        return $this->hasMany(AToken::class, 'user_id');
    }

    ////Verificar si el usuario es administrador
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    ////Verificar si el usuario es gestor
    public function isGestor()
    {
        return $this->role === 'gestor';
    }
}