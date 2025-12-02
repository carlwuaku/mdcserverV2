<?php

namespace App\Controllers;

use App\Exceptions\LicenseNotFoundException;
use App\Services\LicenseService;
use App\Services\LicenseRenewalService;
use App\Services\PractitionerIsSuperintendent;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\Shield\Exceptions\ValidationException;
use OpenApi\Attributes as OA;
use CodeIgniter\Shield\Exceptions\PermissionException;
use App\Helpers\AuthHelper;
use App\Helpers\LicenseUtils;
use App\Traits\CacheInvalidatorTrait;
use App\Helpers\Utils;
use App\Helpers\CacheHelper;
use App\Exceptions\PractitionerNotEligibleForSuperintendingException;

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
            $state = $this->renewalService->getPractitionerPortalRenewal($userId);

            if ($state->action != 'fill_form') {
                return $this->respond(['message' => $state->message], ResponseInterface::HTTP_BAD_REQUEST);
            }
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

    public function createSuperintendingRenewalByLicense()
    {
        try {
            $userId = auth("tokens")->id();
            $userData = AuthHelper::getAuthUser($userId);
            $practitionerDetails = property_exists($userData, 'profile_data') ? $userData->profile_data : null;
            if (empty($practitionerDetails)) {
                return $this->respond(['message' => "Practitioner license not found"], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $practitionerLicenseNumber = array_key_exists('license_number', $practitionerDetails) ? $practitionerDetails['license_number'] : null;
            if (empty($practitionerLicenseNumber)) {
                return $this->respond(['message' => "Practitioner license number not found"], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $data = (array) $this->request->getVar();
            $rules = [
                "facility_license_number" => "required",
                "support_staff" => "required",
            ];
            $supportStaffRules = [
                "email" => "required|valid_email",
                "phone_number" => "required",
                "pin_number" => "required|is_not_unique[licenses.license_number]",
                "type" => "required",
                "last_name" => "required",
                "dob" => "if_exist|valid_date",
            ];

            // Validate data
            $validation = \Config\Services::validation();
            if (!$validation->setRules($rules)->run($data)) {
                throw new \InvalidArgumentException('Validation failed: ' . json_encode($validation->getErrors()));
            }
            //validate the support staff
            $supportStaff = $data['support_staff'];
            if (empty($supportStaff) || !is_array($supportStaff) || count($supportStaff) == 0) {
                throw new \InvalidArgumentException('Validation failed: at least one support staff is required');
            }
            $supportStaffValidation = \Config\Services::validation();
            for ($i = 0; $i < count($supportStaff); $i++) {
                $detail = (array) $supportStaff[$i];
                if (!$supportStaffValidation->setRules($supportStaffRules)->run($detail)) {
                    $message = implode(" ", array_values($validation->getErrors()));
                    log_message("error", $message);
                    throw new ValidationException($message);
                }
            }
            $facilityDetails = LicenseUtils::getLicenseDetails($data['facility_license_number']);

            $data['license_type'] = $facilityDetails['type'];
            $data['license_uuid'] = $facilityDetails['uuid'];
            $data['license_number'] = $facilityDetails['license_number'];
            $data['practitioner_in_charge'] = $practitionerLicenseNumber;
            $data['practitioner_in_charge_details'] = $practitionerDetails;
            $data['business_type'] = $facilityDetails['business_type'];
            $canApply = $this->renewalService->getPractitionerPortalSuperintendingRenewal($userId, $data['facility_license_number']);
            if ($canApply->action != 'fill_form') {
                return $this->respond(['message' => $canApply->message], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $data['support_staff'] = json_encode($supportStaff);
            $result = $this->renewalService->createRenewal($data);
            $this->invalidateCache(CACHE_KEY_PREFIX_RENEWALS);
            $this->invalidateCache('app_licenses_'); // Renewals affect license data
            $this->invalidateCache('app_licenses_count_');
            return $this->respond($result, ResponseInterface::HTTP_OK);

        } catch (ValidationException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
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
            //in some cases we don't want to filter by the region. particularly when getting renewals for a specific license
            $regionExceptions = ["renewal_practitioner_in_charge"];
            $skipRegionFilter = false;
            foreach ($filters as $key => $value) {
                if (!in_array($key, $regionExceptions)) {
                    $skipRegionFilter = true;
                    break;
                }
            }
            if ($license_uuid) {
                $skipRegionFilter = true;
            }
            //if the user has a region set, only return renewals for that region
            if (!$skipRegionFilter && $user->region) {
                $filters['region'] = $user->region;
            }
            if ($license_uuid) {
                $filters['license_uuid'] = $license_uuid;
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

    public function getPractitionerSuperintendingRenewalFormFields()
    {
        try {
            $userId = auth("tokens")->id();
            $userData = AuthHelper::getAuthUser($userId);
            $practitionerLicenseNumber = property_exists($userData, 'profile_data') && array_key_exists('license_number', $userData->profile_data) ? $userData->profile_data['license_number'] : null;
            if (empty($practitionerLicenseNumber)) {
                return $this->respond(['message' => "Practitioner license number is required"], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $facilityLicenseNumber = $this->request->getGet('facility_license_number') ?? null;
            if (empty($facilityLicenseNumber)) {
                // return $this->respond(['message' => "Facility license number is required"], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $state = $this->renewalService->getPractitionerPortalSuperintendingRenewal($userId, $facilityLicenseNumber);

            return $this->respond(["data" => $state], ResponseInterface::HTTP_OK);

        } catch (LicenseNotFoundException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_NOT_FOUND);
        } catch (PractitionerNotEligibleForSuperintendingException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
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
            $result = $this->renewalService->isEligiblePharmacySuperintendent($licenseNumber);
            /**
             * @var PractitionerIsSuperintendent
             */
            $isFaclitySuperintendent = $this->renewalService->isFacilitySuperintendent($licenseNumber);
            log_message('info', 'isFaclitySuperintendent: ' . print_r($isFaclitySuperintendent, true));
            if ($isFaclitySuperintendent->isSuperintendent) {
                //if already a superintendent, use the license number of the existing
                $facilityLicenseNumber = $isFaclitySuperintendent->renewalDetails['license_number'];
            }
            if (!$result) {
                return $this->respond(['message' => "Practitioner is not eligible"], ResponseInterface::HTTP_NOT_FOUND);
            }

            return $this->respond(["data" => [$result], "message" => "Practitioner is eligible"], ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_NOT_FOUND);
        } catch (PractitionerNotEligibleForSuperintendingException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getSuperintendingHistory()
    {
        try {
            //use the registration number of the currently logged in user. this is for use by portal users
            $userId = auth("tokens")->id();
            $user = AuthHelper::getAuthUser($userId);
            $registrationNumber = array_key_exists('license_number', $user->profile_data) ? $user->profile_data['license_number'] : null;
            if (!$registrationNumber) {
                return $this->respond(['message' => "Registration number not found"], ResponseInterface::HTTP_NOT_FOUND);
            }
            $filters = $this->extractRequestFilters();
            $filters['renewal_practitioner_in_charge'] = $registrationNumber;
            $filters['license_type'] = "facilities";
            $cacheKey = Utils::generateHashedCacheKey(CACHE_KEY_PREFIX_RENEWALS . "_superintending_history_{$userId}", $filters);
            return CacheHelper::remember($cacheKey, function () use ($user, $filters) {
                $result = $this->renewalService->getRenewals(null, $filters);
                //remove unnecessary data fields and add the printable and deletable fields
                $allowedFields = [
                    'first_name',
                    'last_name',
                    'middle_name',
                    'license_number',
                    'start_date',
                    'expiry',
                    'status',
                    'printable',
                    'deletable',
                    'uuid',
                    'license_type',
                    'practitioner_in_charge',
                    'practitioner_in_charge_name',
                    'name',
                    'business_type'
                ];
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

    public function isPharmacistPharmacySuperintendent()
    {
        try {
            //use the registration number of the currently logged in user. this is for use by portal users
            $userId = auth("tokens")->id();
            $user = AuthHelper::getAuthUser($userId);
            $registrationNumber = array_key_exists('license_number', $user->profile_data) ? $user->profile_data['license_number'] : null;
            if (!$registrationNumber) {
                return $this->respond(['message' => "Registration number not found"], ResponseInterface::HTTP_NOT_FOUND);
            }
            $isSuperintendent = $this->renewalService->isFacilitySuperintendent($registrationNumber);
            $message = $isSuperintendent->isSuperintendent ? "Superintendent for {$isSuperintendent->renewalDetails['name']}" : "Not a superintendent";
            $isSuperintendent->message = $message;
            return $this->respond(["data" => $isSuperintendent, "message" => $message], ResponseInterface::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return $this->respond(['message' => $e->getMessage()], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            log_message("error", $e);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function printRenewalByLicense(string $renewalUuid)
    {
        try {
            $userId = auth(alias: "tokens")->id();
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