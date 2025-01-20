<?php

namespace App\Controllers;

use App\Helpers\Utils;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use \App\Models\DocumentVerification\DocumentVerificationModel;

class DocumentVerificationController extends ResourceController
{
    protected $db;
    protected $privateKeyPath;
    protected $publicKeyPath;

    protected $documentVerificationModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->documentVerificationModel = new DocumentVerificationModel();

    }

    /**
     * Generate secure document with QR code and digital signature
     */
    public function generateSecureDocument()
    {
        // Validate request
        if (!$this->request->is('post')) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Method not allowed'
            ], ResponseInterface::HTTP_METHOD_NOT_ALLOWED);
        }



        try {
            // Get document data from request
            $documentData = $this->request->getPost();

            $result = $this->documentVerificationModel->generateSecureDocument($documentData);

            return $this->respond($result, ResponseInterface::HTTP_CREATED);

        } catch (\Exception $e) {
            log_message('error', 'Document generation failed: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Failed to generate document'
            ]);
        }
    }

    /**
     * Verify a document using its token
     */
    public function verifyDocument($token)
    {
        try {
            // Check rate limiting
            if ($this->isRateLimitExceeded($token)) {
                return $this->response->setStatusCode(429)->setJSON([
                    'status' => 'error',
                    'message' => 'Too many verification attempts. Please try again later.'
                ]);
            }

            // Get document details
            $document = $this->db->table('documents')
                ->where('verification_token', $token)
                ->where('expires_at >=', date('Y-m-d H:i:s'))
                ->where('is_revoked', false)
                ->get()
                ->getRow();

            if (!$document) {
                $this->logVerificationAttempt($token, false);
                return $this->response->setJSON([
                    'status' => 'invalid',
                    'message' => 'Document not found or has expired'
                ]);
            }

            // Log successful verification
            $this->logVerificationAttempt($token, true);

            // Return minimal necessary information
            return $this->response->setJSON([
                'status' => 'valid',
                'document_type' => $document->document_type,
                'issuing_department' => $document->issuing_department,
                'issue_date' => $document->created_at,
                'last_verified' => date('Y-m-d H:i:s'),
                'verification_count' => $this->getVerificationCount($token)
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Document verification failed: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Verification process failed'
            ]);
        }
    }

    /**
     * Revoke a document
     */
    public function revokeDocument()
    {
        if (!$this->validateAuth()) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'Unauthorized'
            ]);
        }

        $documentId = $this->request->getPost('document_id');

        try {
            $this->db->table('documents')
                ->where('document_id', $documentId)
                ->update(['is_revoked' => true]);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Document revoked successfully'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Document revocation failed: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Failed to revoke document'
            ]);
        }
    }



    /**
     * Check if rate limit is exceeded
     */
    private function isRateLimitExceeded($token): bool
    {
        $oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $attempts = $this->db->table('verification_logs')
            ->where('verification_token', $token)
            ->where('created_at >', $oneHourAgo)
            ->countAllResults();

        return $attempts >= 10; // Limit to 10 verifications per hour
    }

    /**
     * Log verification attempt
     */
    private function logVerificationAttempt($token, $success)
    {
        $this->db->table('verification_logs')->insert([
            'verification_token' => $token,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString(),
            'is_success' => $success,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get verification count for a document
     */
    private function getVerificationCount($token): int
    {
        return $this->db->table('verification_logs')
            ->where('verification_token', $token)
            ->where('is_success', true)
            ->countAllResults();
    }

    /**
     * Validate authentication
     */
    private function validateAuth(): bool
    {
        // Implement your authentication logic here
        // Example: check for valid API key or user session
        return true; // Replace with actual authentication
    }
}
