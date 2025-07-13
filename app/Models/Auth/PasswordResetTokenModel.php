<?php

namespace App\Models\Auth;

use CodeIgniter\Model;

class PasswordResetTokenModel extends Model
{
    protected $table = 'password_reset_tokens';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'token',
        'token_hash',
        'expires_at',
        'used_at',
        'ip_address',
        'user_agent'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'user_id' => 'required|integer',
        'token' => 'required|string|max_length[255]',
        'token_hash' => 'required|string|max_length[255]',
        'expires_at' => 'required|valid_date',
    ];

    protected $validationMessages = [
        'user_id' => [
            'required' => 'User ID is required',
            'integer' => 'User ID must be an integer'
        ],
        'token' => [
            'required' => 'Token is required',
            'string' => 'Token must be a string'
        ]
    ];
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
     * Find valid token by token string
     */
    public function findValidToken($token)
    {
        return $this->where('token', $token)
            ->where('used_at', null)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->first();
    }

    /**
     * Mark token as used
     */
    public function markAsUsed($tokenId)
    {
        return $this->update($tokenId, [
            'used_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Delete expired tokens
     */
    public function deleteExpired()
    {
        return $this->where('expires_at <', date('Y-m-d H:i:s'))
            ->delete();
    }
}
