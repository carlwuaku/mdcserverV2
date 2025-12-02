<?php

namespace App\Models;

use CodeIgniter\Model;

class EmailVerificationTokenModel extends MyBaseModel
{
    protected $table = 'email_verification_tokens';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'guest_uuid',
        'email',
        'token',
        'token_hash',
        'expires_at',
        'verified_at',
        'ip_address',
        'user_agent'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
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
        'guest_uuid' => 'required',
        'email' => 'required|valid_email',
        'token' => 'required',
        'token_hash' => 'required',
        'expires_at' => 'required|valid_date'
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
     * Find valid (non-verified, non-expired) token
     */
    public function findValidToken(string $token): ?array
    {
        return $this->where('token', $token)
            ->where('verified_at', null)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->first();
    }

    /**
     * Mark token as verified
     */
    public function markAsVerified(int $tokenId): bool
    {
        return $this->update($tokenId, [
            'verified_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Delete expired tokens
     */
    public function deleteExpired(): int
    {
        return $this->where('expires_at <', date('Y-m-d H:i:s'))
            ->where('verified_at', null)
            ->delete();
    }

    /**
     * Generate 6-digit token
     */
    public function generateToken(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Hash token using Argon2ID
     */
    public function hashToken(string $token): string
    {
        return password_hash($token, PASSWORD_ARGON2ID);
    }

    /**
     * Verify token against hash
     */
    public function verifyToken(string $token, string $hash): bool
    {
        return password_verify($token, $hash);
    }
}
