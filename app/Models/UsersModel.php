<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Shield\Models\UserModel;

class UsersModel extends UserModel
{
   
    protected $allowedFields = [
        'username',
        'status',
        'status_message',
        'active',
        'last_active',
        'deleted_at', 'role', 'regionId', 'position', 'picture', 'phone'];

    
}
