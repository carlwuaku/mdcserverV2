<?php

namespace App\Models\Auth;

use CodeIgniter\Model;

class PasswordResetHistoryModel extends Model
{
    protected $table = 'password_reset_histories';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'password_hash'
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
        'password_hash' => 'required|string|max_length[255]'
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
     * Check if password was recently used
     */
    public function isPasswordRecentlyUsed($userId, $passwordHash, $months = 6)
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$months} months"));

        $recentPasswords = $this->where('user_id', $userId)
            ->where('created_at >', $cutoffDate)
            ->findAll();

        foreach ($recentPasswords as $record) {
            if (password_verify($passwordHash, $record->password_hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add password to history
     */
    public function addToHistory($userId, $passwordHash)
    {
        // Keep only last 10 passwords
        $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit(1000, 10) // Skip first 10, delete rest
            ->delete();

        return $this->insert([
            'user_id' => $userId,
            'password_hash' => $passwordHash
        ]);
    }
}
