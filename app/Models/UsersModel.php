<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Shield\Models\UserModel;
use App\Helpers\Interfaces\TableDisplayInterface;

class UsersModel extends UserModel implements TableDisplayInterface
{
    public $tableName = "users";
    public $role_name;
    public $regionId;
    public $position;
    public $picture;
    public $phone;
    public $email;

    public $status;
    public $username;

    public function getDisplayColumns(): array
    {
        return [
            'username',
            'display_name',
            'status',
            'status_message',
            'user_type',
            'active',
            'last_active',
            'deleted_at',
            'role_name',
            'regionId',
            'position',
            'picture',
            'phone',
            'email',
            'google_authenticator_setup'
        ];
    }

    public function getDisplayColumnLabels(): array
    {
        return [];
    }

    protected $allowedFields = [
        'username',
        'display_name',
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
        'two_fa_verification_token',
        'two_fa_setup_token',
        'google_auth_secret',
        'user_type',
        'two_fa_deadline',
        'profile_table',
        'profile_table_uuid',
        'email'
    ];

    protected $defaultProfileSelect = [
        'display_name',
        'role_name',
        'regionId',
        'position',
        'picture',
        'phone',
        'user_type',
        'two_fa_deadline',
        'profile_table',
        'profile_table_uuid',
        'email'
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

    public function getDisplayColumnFilters(): array
    {
        return [];
    }

    public function getNonAdminUserProfile(string $table, string $uuid)
    {
        $this->builder($table)
            ->select($this->defaultProfileSelect)
            ->where('uuid', $uuid);
        return $this->first();
    }
}
