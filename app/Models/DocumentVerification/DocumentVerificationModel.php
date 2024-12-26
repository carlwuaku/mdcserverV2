<?php

namespace App\Models\DocumentVerification;

use App\Helpers\Utils;
use App\Models\MyBaseModel;

class DocumentVerificationModel extends MyBaseModel
{
    protected $table = 'documents';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'document_id',
        'verification_token',
        'document_type',
        'content_hash',
        'digital_signature',
        'issuing_department',
        'created_at',
        'expires_at',
        'is_revoked'
    ];

    protected bool $allowEmptyInserts = false;

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [];
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
     * Generate secure document with QR code and digital signature
     * 
     * @param array $documentData
     * @return array
     */
    public function generateSecureDocument($documentData)
    {
        // Create digital signature
        $signature = Utils::signDocument($documentData);
        $documentId = bin2hex(random_bytes(16));
        // Generate verification token
        $token = Utils::generateVerificationToken($documentId);

        // Store document data
        $documentRecord = [
            'document_id' => $documentId,
            'verification_token' => $token,
            'document_type' => $documentData['type'],
            'content_hash' => hash('sha256', json_encode($documentData)),
            'digital_signature' => $signature,
            'issuing_department' => $documentData['department'],
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'is_revoked' => false
        ];
        $this->insert((object) $documentRecord);

        $verificationUrl = site_url("verify/{$token}");
        $qrPath = Utils::generateQRCode($verificationUrl, true, "");

        return [
            'document_id' => $documentId,
            'verification_url' => $verificationUrl,
            'qr_path' => $qrPath
        ];
    }
}
