<?php

namespace App\Controllers;

use App\Services\LicenseService;
use App\Services\LicenseRenewalService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use OpenApi\Attributes as OA;
use CodeIgniter\Shield\Exceptions\PermissionException;
/**
 * @OA\Info(title="API Name", version="1.0")
 * @OA\Tag(name="Tag Name", description="Tag description")
 * @OA\Tag(
 *     name="Licenses",
 *     description="Operations for managing and viewing licenses"
 * )
 */
class LicensesController extends ResourceController
{
    private LicenseService $licenseService;
    private LicenseRenewalService $renewalService;

    public function __construct()
    {
        $this->licenseService = \Config\Services::licenseService();
        $this->renewalService = \Config\Services::licenseRenewalService();
    }

    // License CRUD Operations

    public function createLicense()
    {
        try {
            $data = $this->request->getVar();
            $result = $this->licenseService->createLicense($data);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateLicense($uuid)
    {
        try {
            $data = (array) $this->request->getVar();
            $result = $this->licenseService->updateLicense($uuid, $data);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteLicense($uuid)
    {
        try {
            $result = $this->licenseService->deleteLicense($uuid);
            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\RuntimeException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restoreLicense($uuid)
    {
        try {
            $result = $this->licenseService->restoreLicense($uuid);
            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\RuntimeException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getLicense($uuid)
    {
        try {
            $result = $this->licenseService->getLicenseDetails($uuid);

            if (!$result) {
                return $this->respond(['message' => "Practitioner not found"], ResponseInterface::HTTP_NOT_FOUND);
            }

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getLicenses()
    {
        try {
            $filters = $this->extractRequestFilters();
            $result = $this->licenseService->getLicenses($filters);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function countLicenses()
    {
        try {
            $filters = $this->extractRequestFilters();
            $total = $this->licenseService->countLicenses($filters);

            return $this->respond(['data' => $total], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getLicenseFormFields($licenseType)
    {
        try {
            $fields = $this->licenseService->getLicenseFormFields($licenseType);
            return $this->respond(['data' => $fields], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getBasicStatistics($licenseType = null)
    {
        try {
            $filters = (array) $this->request->getVar();
            ;
            $results = $this->licenseService->getBasicStatistics($licenseType, $filters);

            return $this->respond(['data' => $results], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // License Renewal Operations

    public function createRenewal()
    {
        try {
            $data = $this->request->getPost();
            $result = $this->renewalService->createRenewal($data);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (PermissionException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_UNAUTHORIZED);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateRenewal($uuid)
    {
        try {
            $data = (array) $this->request->getVar();
            $result = $this->renewalService->updateRenewal($uuid, $data);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateBulkRenewals()
    {
        try {
            $data = $this->request->getVar('data'); // array of renewals
            $status = $this->request->getVar('status') ?? null;

            $result = $this->renewalService->updateBulkRenewals($data, $status);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (PermissionException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_UNAUTHORIZED);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteRenewal($uuid)
    {
        try {
            $result = $this->renewalService->deleteRenewal($uuid);
            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\RuntimeException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getRenewal($uuid)
    {
        try {
            $result = $this->renewalService->getRenewalDetails($uuid);

            if (!$result) {
                return $this->respond(['message' => "License renewal not found"], ResponseInterface::HTTP_NOT_FOUND);
            }

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getRenewals($license_uuid = null)
    {
        try {
            $filters = $this->extractRequestFilters();
            $result = $this->renewalService->getRenewals($license_uuid, $filters);

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function countRenewals()
    {
        try {
            $filters = $this->extractRequestFilters();
            $total = $this->renewalService->countRenewals($filters);

            return $this->respond(['data' => $total], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getLicenseRenewalFormFields($licenseType)
    {
        try {
            $fields = $this->renewalService->getLicenseRenewalFormFields($licenseType);
            return $this->respond(['data' => $fields], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getPrintableRenewalStatuses($licenseType)
    {
        try {
            $data = $this->renewalService->getPrintableRenewalStatuses($licenseType);
            return $this->respond(['data' => $data], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getRenewalBasicStatistics($licenseType)
    {
        try {
            $filters = (array) $this->request->getVar();
            $results = $this->renewalService->getRenewalBasicStatistics($licenseType, $filters);

            return $this->respond(['data' => $results], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getPharmacySuperintendent()
    {
        try {
            $licenseNumber = $this->request->getVar('param');
            $result = $this->renewalService->isEligiblePharmacySuperintendent($licenseNumber);

            if (!$result) {
                return $this->respond(['message' => "License renewal not found"], ResponseInterface::HTTP_NOT_FOUND);
            }

            return $this->respond(["data" => [$result], "message" => "Practitioner is eligible"], ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Private helper methods

    /**
     * Extract request filters from various request methods
     */
    private function extractRequestFilters(): array
    {

        $filters = [];

        // Get common parameters
        // $commonParams = [
        //     'limit',
        //     'page',
        //     'withDeleted',
        //     'param',
        //     'child_param',
        //     'sortBy',
        //     'sortOrder',
        //     'licenseType',
        //     'renewalDate',
        //     'license_type',
        //     'license_number',
        //     'status',
        //     'start_date',
        //     'expiry',
        //     'created_on',
        //     'isGazette',
        //     'in_print_queue',
        //     'fields'
        // ];

        // foreach ($commonParams as $param) {
        //     $value = $this->request->getVar($param);
        //     if ($value !== null) {
        //         $filters[$param] = $value;
        //     }
        // }
        //merge get and post data
        $filters = array_merge($this->request->getGet(), (array) $this->request->getVar());

        // Get all child_ and renewal_ parameters
        // $allParams = (array) $this->request->getVar();
        // foreach ($allParams as $key => $value) {
        //     if (strpos($key, 'child_') === 0 || strpos($key, 'renewal_') === 0) {
        //         $filters[$key] = $value;
        //     }
        // }

        return $filters;
    }

    /**
     * Handle service exceptions and return appropriate HTTP responses
     */
    private function handleServiceException(\Throwable $e): ResponseInterface
    {
        if ($e instanceof \InvalidArgumentException) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        }

        if ($e instanceof \RuntimeException) {
            $statusCode = str_contains($e->getMessage(), 'not found')
                ? ResponseInterface::HTTP_NOT_FOUND
                : ResponseInterface::HTTP_BAD_REQUEST;
            return $this->respond(['message' => $e->getMessage()], $statusCode);
        }

        log_message("error", $e);
        return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    }
}