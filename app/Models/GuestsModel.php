<?php

namespace App\Models;

use CodeIgniter\Model;

class GuestsModel extends MyBaseModel
{
    protected $table = 'guests';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'uuid',
        'unique_id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'id_type',
        'id_number',
        'postal_address',
        'sex',
        'picture',
        'date_of_birth',
        'country',
        'email_verified',
        'email_verified_at',
        'id_image',
        'verified'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'email_verified' => 'boolean'
    ];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'first_name' => 'required|min_length[2]|max_length[255]',
        'last_name' => 'required|min_length[2]|max_length[255]',
        'email' => 'required|valid_email|max_length[255]',
        'phone_number' => 'required|min_length[7]|max_length[50]',
        'id_type' => 'required|max_length[50]',
        'id_number' => 'required|max_length[100]',
        'sex' => 'required|in_list[Male,Female,Other]',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Find guest by UUID
     */
    public function findByUuid(string $uuid): ?array
    {
        return $this->where('uuid', $uuid)->first();
    }

    /**
     * Find guest by email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Mark email as verified
     */
    public function markEmailAsVerified(string $uuid): bool
    {
        return $this->builder()->where("uuid", $uuid)->update([
            'email_verified' => true,
            'email_verified_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get display columns for the data table
     */
    public function getDisplayColumns(): array
    {
        return [
            'unique_id',
            'first_name',
            'last_name',
            'sex',
            'email',
            'phone_number',
            'id_type',
            'id_number',
            'id_image',
            'country',
            'email_verified',
            'postal_address',
            'verified',
            'created_at'
        ];
    }

    /**
     * Get display column filters for the data table
     */
    public function getDisplayColumnFilters(): array
    {
        return [
            [
                "label" => "Search",
                "name" => "param",
                "type" => "text",
                "hint" => "Search names, emails, ID numbers",
                "options" => [],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Email Verified",
                "name" => "email_verified",
                "type" => "select",
                "hint" => "",
                "options" => [
                    ["key" => "Yes", "value" => "1"],
                    ["key" => "No", "value" => "0"]
                ],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "Verified",
                "name" => "verified",
                "type" => "select",
                "hint" => "",
                "options" => [
                    ["key" => "Yes", "value" => "1"],
                    ["key" => "No", "value" => "0"]
                ],
                "value" => "",
                "required" => false
            ],
            [
                "label" => "ID Type",
                "name" => "id_type",
                "type" => "select",
                "hint" => "",
                "options" => $this->getDistinctValuesAsKeyValuePairs('id_type'),
                "value" => "",
                "required" => false
            ]
        ];
    }
}
