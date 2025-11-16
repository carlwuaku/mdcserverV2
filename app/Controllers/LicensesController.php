<?php

namespace App\Controllers;

use App\Services\LicenseService;
use App\Services\LicenseRenewalService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use OpenApi\Attributes as OA;
use CodeIgniter\Shield\Exceptions\PermissionException;
use App\Helpers\AuthHelper;
use App\Helpers\LicenseUtils;
use App\Traits\CacheInvalidatorTrait;
use App\Helpers\Utils;
use App\Helpers\CacheHelper;
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
    use CacheInvalidatorTrait;
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

            // Invalidate cache
            $this->invalidateCache('app_licenses_');
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
            $this->invalidateCache('app_licenses_');
            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return $this->respond(['message' => Utils::parseMysqlExceptions($e->getMessage())], ResponseInterface::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteLicense($uuid)
    {
        try {
            $result = $this->licenseService->deleteLicense($uuid);
            $this->invalidateCache('app_licenses_');
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
            $this->invalidateCache('app_licenses_');
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
            $cacheKey = Utils::generateHashedCacheKey('app_licenses_', ['uuid' => $uuid]);
            return CacheHelper::remember($cacheKey, function () use ($uuid) {
                $result = $this->licenseService->getLicenseDetails($uuid);

                if (!$result) {
                    return $this->respond(['message' => "Practitioner not found"], ResponseInterface::HTTP_NOT_FOUND);
                }

                return $this->respond($result, ResponseInterface::HTTP_OK);
            });

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getLicenses()
    {
        try {
            $filters = $this->extractRequestFilters();
            $cacheKey = Utils::generateHashedCacheKey('app_licenses_', $filters);
            return CacheHelper::remember($cacheKey, function () use ($filters) {
                $result = $this->licenseService->getLicenses($filters);

                return $this->respond($result, ResponseInterface::HTTP_OK);
            });

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function countLicenses()
    {
        try {
            $filters = $this->extractRequestFilters();
            $cacheKey = Utils::generateHashedCacheKey('app_licenses_count_', $filters);
            return CacheHelper::remember($cacheKey, function () use ($filters) {
                $total = $this->licenseService->countLicenses($filters);

                return $this->respond(['data' => $total], ResponseInterface::HTTP_OK);
            });

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getLicenseFormFields($licenseType)
    {
        try {
            $cacheKey = Utils::generateHashedCacheKey('app_licenses_form_fields_', ['licenseType' => $licenseType]);
            return CacheHelper::remember($cacheKey, function () use ($licenseType) {
                $fields = $this->licenseService->getLicenseFormFields($licenseType);
                return $this->respond(['data' => $fields], ResponseInterface::HTTP_OK);
            }, 3600); // Cache for 1 hour since form fields rarely change

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getBasicStatistics($licenseType = null)
    {
        try {
            $filters = (array) $this->request->getVar();
            $filters['licenseType'] = $licenseType;
            $cacheKey = Utils::generateHashedCacheKey('app_licenses_stats_', $filters);
            return CacheHelper::remember($cacheKey, function () use ($licenseType, $filters) {
                $results = $this->licenseService->getBasicStatistics($licenseType, $filters);

                return $this->respond(['data' => $results], ResponseInterface::HTTP_OK);
            }, 600); // Cache for 10 minutes

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
            $this->invalidateCache(CACHE_KEY_PREFIX_RENEWALS);
            $this->invalidateCache('app_licenses_'); // Renewals affect license data
            $this->invalidateCache('app_licenses_count_');
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

    public function createRenewalByLicense()
    {
        try {
            $userId = auth("tokens")->id();
            $user = AuthHelper::getAuthUser($userId);
            $data = $this->request->getPost();
            $data['license_type'] = $user->profile_data['type'];
            $data['license_uuid'] = $user->profile_data['uuid'];
            $data['license_number'] = $user->profile_data['license_number'];
            //TODO:check if applications open
            $result = $this->renewalService->createRenewal($data);
            $this->invalidateCache(CACHE_KEY_PREFIX_RENEWALS);
            $this->invalidateCache('app_licenses_'); // Renewals affect license data
            $this->invalidateCache('app_licenses_count_');
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
            $this->invalidateCache(CACHE_KEY_PREFIX_RENEWALS);
            $this->invalidateCache('app_licenses_'); // Renewals affect license data
            $this->invalidateCache('app_licenses_count_');
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
            $this->invalidateCache(CACHE_KEY_PREFIX_RENEWALS);
            $this->invalidateCache('app_licenses_'); // Renewals affect license data
            $this->invalidateCache('app_licenses_count_');

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
            $this->invalidateCache(CACHE_KEY_PREFIX_RENEWALS);
            $this->invalidateCache('app_licenses_'); // Renewals affect license data
            $this->invalidateCache('app_licenses_count_');

            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (\RuntimeException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteRenewalByLicense($uuid)
    {
        try {
            $userId = auth("tokens")->id();
            $user = AuthHelper::getAuthUser($userId);
            //make sure the uuid belongs to the user
            $result = $this->renewalService->deleteRenewal($uuid, $user->profile_data['uuid']);
            $this->invalidateCache(CACHE_KEY_PREFIX_RENEWALS);
            $this->invalidateCache('app_licenses_'); // Renewals affect license data
            $this->invalidateCache('app_licenses_count_');

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
            $cacheKey = Utils::generateHashedCacheKey(CACHE_KEY_PREFIX_RENEWALS, ['uuid' => $uuid]);
            return CacheHelper::remember($cacheKey, function () use ($uuid) {
                $result = $this->renewalService->getRenewalDetails($uuid);

                if (!$result) {
                    return $this->respond(['message' => "License renewal not found"], ResponseInterface::HTTP_NOT_FOUND);
                }

                return $this->respond($result, ResponseInterface::HTTP_OK);
            }, 900);

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getRenewals($license_uuid = null)
    {
        try {
            $filters = $this->extractRequestFilters();
            $userId = auth("tokens")->id();
            $user = AuthHelper::getAuthUser($userId);
            $filters['user_id'] = $user->id;
            //if the user has a region set, only return renewals for that region
            if ($user->region) {
                $filters['region'] = $user->region;
            }
            $cacheKey = Utils::generateHashedCacheKey(CACHE_KEY_PREFIX_RENEWALS, $filters);
            return CacheHelper::remember($cacheKey, function () use ($license_uuid, $filters) {

                $result = $this->renewalService->getRenewals($license_uuid, $filters);

                return $this->respond($result, ResponseInterface::HTTP_OK);
            }, 900);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getRenewalsByLicense()
    {
        try {
            //use the uuid of the currently logged in user. this is for use by portal users
            $userId = auth("tokens")->id();
            $user = AuthHelper::getAuthUser($userId);
            $filters = $this->extractRequestFilters();
            $filters['user_uuid'] = $user->profile_table_uuid;
            $cacheKey = Utils::generateHashedCacheKey(CACHE_KEY_PREFIX_RENEWALS, $filters);
            return CacheHelper::remember($cacheKey, function () use ($user, $filters) {
                $result = $this->renewalService->getRenewals($user->profile_table_uuid, $filters);
                //remove unnecessary data fields and add the printable and deletable fields
                $allowedFields = ['first_name', 'last_name', 'middle_name', 'license_number', 'start_date', 'expiry', 'status', 'printable', 'deletable', 'uuid'];
                foreach ($result['data'] as $renewal) {
                    $renewal->deletable = LicenseUtils::isRenewalStageDeletable($renewal->license_type, $renewal->status);
                    $renewal->printable = LicenseUtils::isRenewalStagePrintable($renewal->license_type, $renewal->status);
                }
                foreach ($result['data'] as $key => $renewal) {
                    $result['data'][$key] = array_intersect_key((array) $renewal, array_flip($allowedFields));
                    //status is  a printable one

                }
                //set the display fields
                $result['displayColumns'] = ['license_number', 'start_date', 'expiry', 'status'];
                return $this->respond($result, ResponseInterface::HTTP_OK);
            }, 900);

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
            $userId = auth("tokens")->id();
            $user = AuthHelper::getAuthUser($userId);
            $filters['user_id'] = $user->id;
            //if the user has a region set, only return renewals for that region
            if ($user->region) {
                $filters['region'] = $user->region;
            }

            $cacheKey = Utils::generateHashedCacheKey(CACHE_KEY_PREFIX_RENEWALS_COUNT, $filters);
            return CacheHelper::remember($cacheKey, function () use ($filters) {

                $total = $this->renewalService->countRenewals($filters);

                return $this->respond(['data' => $total], ResponseInterface::HTTP_OK);
            }, 900);


        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getLicenseRenewalFormFields($licenseType)
    {
        try {
            $cacheKey = Utils::generateHashedCacheKey(CACHE_KEY_PREFIX_RENEWALS . '_form_fields_', ['licenseType' => $licenseType]);
            return CacheHelper::remember($cacheKey, function () use ($licenseType) {
                $fields = $this->renewalService->getLicenseRenewalFormFields($licenseType);
                return $this->respond(['data' => $fields], ResponseInterface::HTTP_OK);
            }, 3600); // Cache for 1 hour

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getLicenseRenewalFilters($licenseType)
    {
        try {
            $userId = auth("tokens")->id();
            $user = AuthHelper::getAuthUser($userId);
            $cacheKey = Utils::generateHashedCacheKey(CACHE_KEY_PREFIX_RENEWALS . '_filters_', ['licenseType' => $licenseType, 'user_id' => $user->id]);
            return CacheHelper::remember($cacheKey, function () use ($user, $licenseType) {
                $fields = $this->renewalService->getRenewalFilters($user, $licenseType);
                return $this->respond(['data' => $fields], ResponseInterface::HTTP_OK);
            }, 3600); // Cache for 1 hour

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getPractitionerRenewalFormFields()
    {
        try {
            $userId = auth("tokens")->id();
            $cacheKey = Utils::generateHashedCacheKey(CACHE_KEY_PREFIX_RENEWALS . '_practitioner_form_', ['user_id' => $userId]);
            return CacheHelper::remember($cacheKey, function () use ($userId) {
                $state = $this->renewalService->getPractitionerPortalRenewal($userId);

                return $this->respond(["data" => $state], ResponseInterface::HTTP_OK);
            }, 1800); // Cache for 30 minutes

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getPrintableRenewalStatuses($licenseType)
    {
        try {
            $cacheKey = Utils::generateHashedCacheKey(CACHE_KEY_PREFIX_RENEWALS . '_printable_', ['licenseType' => $licenseType]);
            return CacheHelper::remember($cacheKey, function () use ($licenseType) {
                $data = $this->renewalService->getPrintableRenewalStatuses($licenseType);
                return $this->respond(['data' => $data], ResponseInterface::HTTP_OK);
            }, 3600); // Cache for 1 hour

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getRenewalBasicStatistics($licenseType)
    {
        try {
            $filters = (array) $this->request->getVar();
            $filters['licenseType'] = $licenseType;
            $cacheKey = Utils::generateHashedCacheKey(CACHE_KEY_PREFIX_RENEWALS . '_stats_', $filters);
            return CacheHelper::remember($cacheKey, function () use ($licenseType, $filters) {
                $results = $this->renewalService->getRenewalBasicStatistics($licenseType, $filters);

                return $this->respond(['data' => $results], ResponseInterface::HTTP_OK);
            }, 600); // Cache for 10 minutes

        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getPharmacySuperintendent()
    {
        try {
            $licenseNumber = $this->request->getVar('param');
            $cacheKey = Utils::generateHashedCacheKey(CACHE_KEY_PREFIX_RENEWALS . '_superintendent_', ['licenseNumber' => $licenseNumber]);
            return CacheHelper::remember($cacheKey, function () use ($licenseNumber) {
                $result = $this->renewalService->isEligiblePharmacySuperintendent($licenseNumber);

                if (!$result) {
                    return $this->respond(['message' => "License renewal not found"], ResponseInterface::HTTP_NOT_FOUND);
                }

                return $this->respond(["data" => [$result], "message" => "Practitioner is eligible"], ResponseInterface::HTTP_OK);
            }, 1800); // Cache for 30 minutes

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function printRenewalByLicense(string $renewalUuid)
    {
        try {
            $userId = auth("tokens")->id();
            $user = AuthHelper::getAuthUser($userId);
            $cacheKey = Utils::generateHashedCacheKey(CACHE_KEY_PREFIX_RENEWALS . '_print_', ['renewalUuid' => $renewalUuid, 'licenseUuid' => $user->profile_data['uuid']]);
            return CacheHelper::remember($cacheKey, function () use ($renewalUuid, $user) {
                $result = $this->renewalService->getRenewalOnlinePrintTemplateForLicense($renewalUuid, $user->profile_data['uuid']);

                return $this->respond(['data' => $result, 'message' => 'Success'], ResponseInterface::HTTP_OK);
            }, 1800); // Cache for 30 minutes

        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
            return $this->respond(['message' => "Server error", 'data' => null], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    // Private helper methods

    /**
     * Extract request filters from various request methods
     */
    private function extractRequestFilters(): array
    {

        $filters = [];
        //merge get and post data
        $filters = array_merge($this->request->getGet(), (array) $this->request->getVar());


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