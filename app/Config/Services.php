<?php

namespace Config;

use App\Services\GhanaGovPaymentService;
use App\Services\PortalService;
use CodeIgniter\Config\BaseService;
use App\Services\LicenseService;
use App\Services\LicenseRenewalService;
use App\Services\ApplicationService;
use App\Services\ApplicationTemplateService;
use App\Services\ExaminationService;
use App\Services\PaymentsService;
use App\Services\SettingsService;
use App\Services\HousemanshipService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /*
     * public static function example($getShared = true)
     * {
     *     if ($getShared) {
     *         return static::getSharedInstance('example');
     *     }
     *
     *     return new \CodeIgniter\Example();
     * }
     */

    /**
     * License Service
     */
    public static function licenseService(bool $getShared = true): LicenseService
    {
        if ($getShared) {
            return static::getSharedInstance('licenseService');
        }

        return new LicenseService();
    }

    /**
     * License Renewal Service
     */
    public static function licenseRenewalService(bool $getShared = true): LicenseRenewalService
    {
        if ($getShared) {
            return static::getSharedInstance('licenseRenewalService');
        }

        return new LicenseRenewalService();
    }

    /**
     * Application Service
     */
    public static function applicationService(bool $getShared = true): ApplicationService
    {
        if ($getShared) {
            return static::getSharedInstance('applicationService');
        }
        return new ApplicationService();
    }

    /**
     * Application Template Service
     */
    public static function applicationTemplateService(bool $getShared = true): ApplicationTemplateService
    {
        if ($getShared) {
            return static::getSharedInstance('applicationTemplateService');
        }
        return new ApplicationTemplateService();
    }

    /**
     * Examination Service
     */
    public static function examinationService(bool $getShared = true): ExaminationService
    {
        if ($getShared) {
            return static::getSharedInstance('examinationService');
        }
        return new ExaminationService();
    }

    /**Payments Service */
    public static function paymentsService(bool $getShared = true): PaymentsService
    {
        if ($getShared) {
            return static::getSharedInstance('paymentsService');
        }
        return new PaymentsService();
    }

    public static function ghanaGovPaymentService(bool $getShared = true): GhanaGovPaymentService
    {
        if ($getShared) {
            return static::getSharedInstance('ghanaGovPaymentService');
        }
        return new GhanaGovPaymentService();
    }

    public static function portalService(bool $getShared = true): PortalService
    {
        if ($getShared) {
            return static::getSharedInstance('portalService');
        }
        return new PortalService();
    }

    public static function settingsService(bool $getShared = true): SettingsService
    {
        if ($getShared) {
            return static::getSharedInstance('serviceService');
        }
        return new SettingsService();
    }

    public static function housemanshipService(bool $getShared = true): HousemanshipService
    {
        if ($getShared) {
            return static::getSharedInstance('housemanshipService');
        }
        return new HousemanshipService();
    }
}
