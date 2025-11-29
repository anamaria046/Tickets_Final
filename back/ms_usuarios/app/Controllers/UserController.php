<?php

namespace App\Controllers;
use App\Models\AToken;
use App\Models\Users;
use Exception;

class UserController
{
    ////////////Registrar un nuevo usuario
    public function register($name, $email, $password, $role)
    {
    ////////////Validación de campos
        if (empty($name) || empty($email) || empty($password) || empty($role)) {
            throw new Exception("Campos obligatorios faltantes", 400);
        }

    ////////////Verificar si email ya existe
        if (Users::where('email', $email)->first()) {
            throw new Exception("Email ya registrado", 409);
        }

     /////Crear usuario
        $user = Users::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role
        ]);
        return $user->toArray();
    }

    /////////Iniciar sesión
    public function login($email, $password)
    {
        if (empty($email) || empty($password)) {
            throw new Exception("Email y contraseña requeridos", 400);
        }
        $user = Users::where('email', $email)->first();
        if (!$user || $user->password !== $password) {
            throw new Exception("Información incorrectas", 401);
        }
        ////// Generar token
        $token = bin2hex(random_bytes(16));
        
        AToken::create([
            'user_id' => $user->id,
            'token' => $token
        ]);

        return [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ]
        ];
    }

    /////////Cerrar sesión
    public function logout($token)
    {
        if (empty($token)) {
            throw new Exception("Token requerido", 400);
        }

        $deleted = AToken::where('token', $token)->delete();
        
        if ($deleted) {
            return ['message' => 'Sesión cerrada'];
        } else {
            return ['message' => 'Sesión cerrada (token ya no existía)'];
        }
    }

    //////////Listar todos los usuarios (solo admin)
   
    public function getUsers($token)
    {
        ////Verificar autenticación y rol
        $user = $this->getUserFromToken($token);
        
        if (!$user || !$user->isAdmin()) {
            throw new Exception("Acceso denegado", 403);
        }

        $users = Users::all();
        return $users->toArray();
    }

    /////////Actualizar usuario (solo admin)
    public function updateUser($token, $userId, $data)
    {
        //Verificar autenticación y rol
        $user = $this->getUserFromToken($token);
        
        if (!$user || !$user->isAdmin()) {
            throw new Exception("Acceso denegado", 403);
        }
        // Validación adicional: Si se incluye 'role', verificar que sea válido
        if (isset($data['role']) && !in_array($data['role'], ['gestor', 'admin'])) {
            throw new Exception("Rol inválido", 400);
        }
        $updated = Users::where('id', $userId)->update($data);
        if (!$updated) {
            throw new Exception("Usuario no encontrado", 404);
        }
        return ['message' => 'Usuario actualizado'];
    }

    /////Cambiar rol de usuario (solo admin)
    public function changeUserRole($token, $userId, $newRole)
    {
        ///7Verificar autenticación y rol
        $user = $this->getUserFromToken($token);
        
        if (!$user || !$user->isAdmin()) {
            throw new Exception("Acceso denegado", 403);
        }

        ////Validar que se envíe un rol válido
        if (empty($newRole) || !in_array($newRole, ['gestor', 'admin'])) {
            throw new Exception("Rol requerido e inválido", 400);
        }

        $updated = Users::where('id', $userId)->update(['role' => $newRole]);
        
        if (!$updated) {
            throw new Exception("Usuario no encontrado", 404);
        }

        return ['message' => 'Rol de usuario cambiado'];
    }

    ///////Eliminar usuario (solo admin)
 
    public function deleteUser($token, $userId)
    {
        /////Verificar autenticación y rol
        $user = $this->getUserFromToken($token);
        
        if (!$user || !$user->isAdmin()) {
            throw new Exception("Acceso denegado", 403);
        }

        try {
            $deleted = Users::destroy($userId);
            
            if (!$deleted) {
                throw new Exception("Usuario no encontrado", 404);
            }

            return ['message' => 'Usuario eliminado'];
        } catch (\Illuminate\Database\QueryException $e) {
            // Capturar error de restricción de clave foránea (Foreign Key Constraint)
            if ($e->getCode() == '23000') {
                throw new Exception("No se puede eliminar este usuario porque tiene tickets asignados. Primero debe reasignar o eliminar los tickets asociados.", 409);
            }
            // Re-lanzar cualquier otra excepción de base de datos
            throw new Exception("Error al eliminar usuario: " . $e->getMessage(), 500);
        }
    }

    ///////////Método auxiliar para obtener el usuario desde el token
    private function getUserFromToken($token)
    {
        if (empty($token)) {
            throw new Exception("Token requerido", 401);
        }
        $auth = AToken::where('token', $token)->first();
        if (!$auth) {
            throw new Exception("Token inválido", 401);
        }
        return Users::find($auth->user_id);
    }
}