<?php

namespace App\Repositories;

use App\Models\Users;

class UserRepository
{
    public function createUser($data)
    {
        return Users::create($data);
    }

    public function getUserByEmail($email)
    {
        return Users::where('email', $email)->first();
    }

    public function getAllUsers()
    {
        return Users::all();
    }

    public function updateUser($id, $data)
    {
        // Si se actualiza la contraseña, ya no la hasheamos
        return Users::where('id', $id)->update($data);
    }

    public function deleteUser($id)
    {
        return Users::destroy($id);
    }
}