<?php

namespace App\Models\Auth;

use CodeIgniter\Model;

class PasswordResetAttemptModel extends Model
{
    protected $table = 'password_reset_attempts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'email',
        'ip_address',
        'user_agent',
        'success'
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
        'email' => 'required|valid_email|max_length[255]',
        'ip_address' => 'required|string|max_length[45]',
        'success' => 'in_list[0,1]'
    ];

    protected $validationMessages = [
        'email' => [
            'required' => 'Email is required',
            'valid_email' => 'Email must be valid'
        ],
        'ip_address' => [
            'required' => 'IP address is required'
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
     * Get attempts count for email in given timeframe
     */
    public function getAttemptsCount($email, $hours = 1)
    {
        $timeAgo = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

        return $this->where('email', $email)
            ->where('created_at >', $timeAgo)
            ->countAllResults();
    }

    /**
     * Get attempts count for IP in given timeframe
     */
    public function getIpAttemptsCount($ipAddress, $hours = 1)
    {
        $timeAgo = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

        return $this->where('ip_address', $ipAddress)
            ->where('created_at >', $timeAgo)
            ->countAllResults();
    }

    /**
     * Clean old attempts
     */
    public function cleanOldAttempts($days = 30)
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $this->where('created_at <', $cutoffDate)
            ->delete();
    }
}
