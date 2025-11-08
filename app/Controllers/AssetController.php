<?php

namespace App\Controllers;

use App\Models\UsersModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Helpers\Constants;
use App\Helpers\AuthHelper;

/**
 * @OA\Info(title="API Name", version="1.0")
 * @OA\Tag(name="Tag Name", description="Tag description")
 * @OA\Tag(
 *     name="Asset",
 *     description="Operations for managing and viewing system assets"
 * )
 */
class AssetController extends ResourceController
{

    /**
     * Generate a signed URL for secure file access without requiring authentication header
     *
     * @param string $type File type category
     * @param string $filename The file name
     * @return mixed JSON response with signed URL
     */
    public function generateSignedUrl(string $type, string $filename)
    {
        // Verify user has permission to access this file
        if (!$this->canAccessFile($type, $filename)) {
            return $this->failForbidden('Access denied');
        }

        $expiration = time() + 3600; // 1 hour expiry
        $signature = $this->createSignature($type, $filename, $expiration);

        $signedUrl = base_url("file-server/secure/$type/$filename") .
            "?expires=$expiration&signature=$signature";

        return $this->respond([
            'url' => $signedUrl,
            'expires_at' => date('Y-m-d H:i:s', $expiration)
        ]);
    }

    /**
     * Serve file with signature verification (no auth required if signature is valid)
     */
    public function serveSecureFile($type, $imageName)
    {
        $expires = $this->request->getGet('expires');
        $signature = $this->request->getGet('signature');

        // Validate signature
        if (!$expires || !$signature) {
            return $this->failUnauthorized('Missing signature parameters');
        }

        if (time() > $expires) {
            return $this->failUnauthorized('URL has expired');
        }

        $expectedSignature = $this->createSignature($type, $imageName, $expires);
        if (!hash_equals($expectedSignature, $signature)) {
            log_message('warning', 'Invalid signature attempt for file: ' . $imageName);
            return $this->failUnauthorized('Invalid signature');
        }

        // Signature is valid, serve the file (reuse existing logic)
        return $this->serveFileInternal($type, $imageName, true);
    }

    /**
     * Create HMAC signature for URL signing
     */
    private function createSignature(string $type, string $filename, int $expires): string
    {
        $secret = getenv('encryption.key') ?: 'your-secret-key-change-this';
        $data = "$type|$filename|$expires";
        return hash_hmac('sha256', $data, $secret);
    }

    /**
     * upload a new file. the type specifies which subfolder it should be placed in
     *
     * @return mixed
     */
    public function upload(string $type)
    {
        $allowedTypes = [
            'practitioners_images',
            'documents',
            'applications',
            'payments',
            'users'
        ];
        if (!in_array($type, $allowedTypes)) {
            return $this->respond(['message' => "Invalid file type"], ResponseInterface::HTTP_BAD_REQUEST);
        }
        $mimes = "image/jpg,image/jpeg,image/gif,image/png,image/webp,image/svg+xml,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
        if ($type === "practitioners_images" || $type === "users") {
            $mimes = "image/jpg,image/jpeg,image/png";
        }
        if ($type === "payments") {
            $mimes = "application/pdf,image/jpg,image/jpeg,image/png";
        }
        $validationRule = [
            'uploadFile' => [
                'label' => 'Uploaded File',
                'rules' => [
                    // 'uploaded[uploadFile]',
                    "mime_in[uploadFile,$mimes]",
                    "max_size[uploadFile,5000]",
                    // 'max_dims[uploadFile,1024,768]',
                ],
            ],
        ];
        if (!$this->validate($validationRule)) {
            $message = implode(" ", array_values($this->validator->getErrors()));
            return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);

        }

        $img = $this->request->getFile('uploadFile');
        $destination = $this->getImageDirectory($type);

        if (!$img->hasMoved()) {
            $filepath = $img->store($destination);
            $filepathParts = explode("/", $filepath);
            $fileName = array_pop($filepathParts);
            $data = [
                'filePath' => $fileName,
                'fullPath' => base_url("file-server/image-render/$type/$fileName")
            ];
            return $this->respond($data, ResponseInterface::HTTP_OK);
        }

    }

    private function getImageDirectory(string $type): string
    {
        $baseFolder = '';
        $destination = $baseFolder;
        switch ($type) {
            case 'practitioners_images':
                $destination = PRACTITIONERS_ASSETS_FOLDER . "/";
                break;
            case "documents":
                $destination = "documents/";
                break;
            case "applications":
                $destination = APPLICATIONS_ASSETS_FOLDER . "/";
                break;
            case "payments":
                $destination = PAYMENTS_ASSETS_FOLDER . "/";
                break;
            case "qr_codes":
                $destination = QRCODES_ASSETS_FOLDER . "/";
                break;
            case "users":
                $destination = USERS_ASSETS_FOLDER . "/";
                break;
            default:
                throw new \Exception("Invalid file type");
        }
        return $destination;
    }

    public function serveFile($type, $imageName)
    {
        // Require authentication and permission check
        return $this->serveFileInternal($type, $imageName, false);
    }

    /**
     * Internal method to serve files with optional permission bypass for signed URLs
     */
    private function serveFileInternal($type, $imageName, $bypassPermissionCheck = false)
    {
        // 1. VALIDATE TYPE - prevent directory traversal
        $allowedTypes = [
            'practitioners_images',
            'documents',
            'applications',
            'payments',
            'qr_codes',
            'users'
        ];

        if (!in_array($type, $allowedTypes)) {
            return $this->failNotFound('Invalid file type');
        }

        // 2. SANITIZE FILENAME - prevent path traversal
        $imageName = basename($imageName); // Remove any directory components
        if (preg_match('/[^a-zA-Z0-9_\-\.]/', $imageName)) {
            return $this->failNotFound('Invalid filename');
        }

        // 3. CONSTRUCT SAFE PATH
        $directory = $this->getImageDirectory($type);
        if ($type !== "qr_codes") {
            $directory = UPLOADS_FOLDER . "/" . $directory;
        }

        $filePath = realpath(WRITEPATH . $directory . $imageName);
        $baseDirectory = realpath(WRITEPATH . $directory);

        // 4. VERIFY FILE IS WITHIN ALLOWED DIRECTORY
        if (!$filePath || !$baseDirectory || strpos($filePath, $baseDirectory) !== 0) {
            log_message("warning", "Path traversal attempt detected: type=$type, file=$imageName");
            return $this->failNotFound('File not found');
        }

        // 5. CHECK FILE EXISTS AND IS A FILE
        if (!is_file($filePath)) {
            return $this->failNotFound('File not found');
        }

        // 6. CHECK FILE PERMISSIONS (based on file type) - skip for signed URLs
        if (!$bypassPermissionCheck && !$this->canAccessFile($type, $imageName)) {
            return $this->failForbidden('Access denied');
        }

        // 7. VALIDATE MIME TYPE
        $mimeType = $this->getMimeType($filePath);
        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        if (!in_array($mimeType, $allowedMimeTypes)) {
            log_message("warning", "Unsafe file type requested: $mimeType for file=$imageName");
            return $this->failForbidden('File type not allowed');
        }

        // 8. GET FILE INFO
        $fileSize = filesize($filePath);
        $lastModified = filemtime($filePath);
        $etag = md5($filePath . $lastModified);

        // 9. HANDLE CONDITIONAL REQUESTS (caching)
        if ($this->isNotModified($etag, $lastModified)) {
            return $this->response->setStatusCode(304);
        }

        // 10. SET SECURITY HEADERS
        $this->response
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('X-Frame-Options', 'DENY')
            ->setHeader('Content-Security-Policy', "default-src 'none'");

        // 11. SET CACHING HEADERS (1 hour cache for private use)
        $this->response
            ->setHeader('Cache-Control', 'private, max-age=3600')
            ->setHeader('ETag', $etag)
            ->setHeader('Last-Modified', gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');

        // 12. SET CONTENT DISPOSITION
        $isDownload = $this->request->getGet('download') === '1';
        $disposition = $isDownload ? 'attachment' : 'inline';
        $this->response->setHeader('Content-Disposition', "$disposition; filename=\"" . addslashes($imageName) . "\"");

        // 13. STREAM FILE EFFICIENTLY (don't load entire file into memory)
        try {
            $this->response
                ->setStatusCode(200)
                ->setContentType($mimeType)
                ->setHeader('Content-Length', (string) $fileSize);

            // Stream the file
            $handle = fopen($filePath, 'rb');
            if ($handle) {
                // Send headers first
                $this->response->sendHeaders();

                // Stream file in 8KB chunks
                while (!feof($handle)) {
                    echo fread($handle, 8192);
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
                fclose($handle);
                exit; // Prevent CI4 from sending additional output
            }

            return $this->failServerError('Unable to read file');
        } catch (\Throwable $th) {
            log_message("error", "Error serving file: " . $th->getMessage());
            return $this->failServerError('Unable to serve file');
        }
    }

    public function getMimeType($filename)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filename);
        finfo_close($finfo);
        return $mimeType;
    }

    /**
     * Check if user has permission to access this file
     */
    private function canAccessFile(string $type, string $filename): bool
    {
        $userId = auth("tokens")->id();
        $user = AuthHelper::getAuthUser($userId);

        if (!$user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }
        // QR codes are generally accessible to authenticated users
        if ($type === 'qr_codes' || $type === 'applications') {
            return true;
        }

        // Check permissions based on file type
        switch ($type) {
            case 'practitioners_images':
                // Allow if user can view practitioner details or is viewing their own image
                return $this->canViewPractitionerFile($user, $filename);

            case 'documents':
                return $this->canViewDocumentFile($user, $filename);

            case 'payments':
                return $this->canViewPaymentFile($user, $filename);
            case 'users':
                return $user->isAdmin();

            default:
                return false;
        }
    }

    /**
     * Check if content hasn't been modified (for caching)
     */
    private function isNotModified(string $etag, int $lastModified): bool
    {
        $ifNoneMatch = $this->request->getHeaderLine('If-None-Match');
        $ifModifiedSince = $this->request->getHeaderLine('If-Modified-Since');

        if ($ifNoneMatch && $ifNoneMatch === $etag) {
            return true;
        }

        if ($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModified) {
            return true;
        }

        return false;
    }

    /**
     * Implement authorization logic for practitioner files
     */
    private function canViewPractitionerFile(UsersModel $user, string $filename): bool
    {
        //for now, return true once the user is authenticated. the rules need to be defined properly.
        //practitioners can only view their own files, but facilities may be able to view the files of their practitioners.
        return true;
        // Check if user has View_License_Details permission
        // if ($user->can('View_License_Details')) {
        //     return true;
        // }

        // // Check if the file belongs to the current user
        // return $this->isUsersOwnFile($user, $filename, 'practitioners_images');
    }

    private function canViewDocumentFile(UsersModel $user, string $filename): bool
    {
        //for now, return true once the user is authenticated. the rules need to be defined properly.
        //practitioners can only view their own files, but facilities may be able to view the files of their practitioners.
        return true;
        // Check if user has View_License_Details permission
        // if ($user->can('View_License_Details')) {
        //     return true;
        // }

        // // Check if the file belongs to the current user
        // return $this->isUsersOwnFile($user, $filename, 'practitioners_images');
    }

    /**
     * Implement authorization logic for payment files
     */
    private function canViewPaymentFile(UsersModel $user, string $filename): bool
    {
        //for now, return true once the user is authenticated. the rules need to be defined properly.
        //practitioners can only view their own files, but facilities may be able to view the files of their practitioners.
        return true;
        // Check if user has View_Payment_Details permission
        // if ($user->can('View_Payment_Details')) {
        //     return true;
        // }

        // // Check if the file belongs to the current user
        // return $this->isUsersOwnFile($user, $filename, 'payments');
    }

    /**
     * Check if file belongs to current user
     *
     * TODO: Implement file tracking system to properly verify file ownership.
     * For now, we rely on permission-based access only.
     * Consider creating a file_uploads table to track:
     * - filename, file_type, uploaded_by, related_entity_id, created_at
     */
    private function isUsersOwnFile($user, string $filename, string $type): bool
    {
        // Placeholder for file ownership verification
        // This should query a database table that tracks file uploads
        // and verify if the file belongs to the current user

        // Example implementation:
        // $fileModel = new FileUploadModel();
        // $file = $fileModel->where('filename', $filename)
        //                   ->where('file_type', $type)
        //                   ->first();
        // return $file && $file->uploaded_by === $user->id;

        return false;
    }

}
