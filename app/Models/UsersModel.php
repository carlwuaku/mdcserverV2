<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Shield\Models\UserModel;
use App\Helpers\Interfaces\TableDisplayInterface;

class UsersModel extends UserModel implements TableDisplayInterface
{
    public $tableName = "users";

    public function getDisplayColumns(): array
    {
        return [
            'username',
            'status',
            'status_message',
            'active',
            'last_active',
            'deleted_at',
            'role_name',
            'regionId',
            'position',
            'picture',
            'phone',
            'email'
        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
    }

    protected $allowedFields = [
        'username',
        'status',
        'status_message',
        'active',
        'last_active',
        'deleted_at',
        'role_id',
        'regionId',
        'position',
        'picture',
        'phone'
    ];





    public function getUserProfile(string $userId)
    {

    }

    public $validationRules = [
        // "username" => "required|is_unique[users.username, id, {id}]",
        // "password" => "required",
        // "email" => "required|valid_email|is_unique[auth_identities.secret]",
        // "id" => "is_unique[users.id]"
    ];
    public function getPagination(?int $perPage = null): array
    {
        $this->builder()
            ->select('news.*, category.name')
            ->join('category', 'news.category_id = category.id');

        return [
            'news' => $this->paginate($perPage),
            'pager' => $this->pager,
        ];
    }

}
