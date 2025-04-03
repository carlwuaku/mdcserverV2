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
        'is_revoked',
        'unique_id',
        'table_name',
        'table_row_uuid'
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
     * @param array{type:string, department:string, unique_id?:string, table_name?:string, table_row_uuid?:string} $documentData
     * @return array{document_id:string, verification_url:string, qr_path:string}
     */
    public function generateSecureDocument($documentData)
    {
        // Create digital signature
        $signature = Utils::signDocument($documentData);
        $documentId = bin2hex(random_bytes(16));
        // Generate verification token
        $token = Utils::generateVerificationToken($documentId);
        $verificationUrl = site_url("verify/{$token}");
        $qrCode = Utils::generateQRCode($verificationUrl, false, "");
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
            'is_revoked' => false,
            'unique_id' => $documentData['unique_id'] ?? null,
            'table_name' => $documentData['table_name'] ?? null,
            'table_row_uuid' => $documentData['table_row_uuid'] ?? null,
            'qr_code' => $qrCode
        ];
        $this->insert((object) $documentRecord);



        return [
            'document_id' => $documentId,
            'verification_url' => $verificationUrl,
            'qr_path' => $qrCode
        ];
    }
}
