<?php

namespace App\Controllers;

use App\Helpers\PortalHelper;
use App\Helpers\Types\PortalHomeSubtitleType;
use App\Helpers\Utils;
use App\Services\PortalService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Helpers\Types\PortalHomeConfigType;
use App\Helpers\AuthHelper;
use App\Helpers\Types\Alert;

class PortalController extends ResourceController
{
    private PortalService $portalService;
    public function __construct()
    {
        $this->portalService = \Config\Services::portalService();
    }
    /**
     * Gets the home menu for the logged in user. the settings in app-settings are used together with the user data to create the menu
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function getHomeMenu()
    {
        try {
            $userId = auth("tokens")->id();
            $user = AuthHelper::getAuthUser($userId);

            $portalHomeMenuItems = Utils::getMultipleAppSettings(['portalHomeMenu', 'portalHomeSubTitleFields', 'portalAlerts']);
            /**
             * @var PortalHomeConfigType[]
             */
            $dashboardMenu = [];
            foreach ($portalHomeMenuItems['portalHomeMenu'] as $config) {
                $configObject = PortalHomeConfigType::fromArray($config);

                //fill the configs with user data
                try {
                    $dashboardMenu[] = PortalHelper::fillPortalHomeMenuForUser($user, $configObject);
                } catch (\Throwable $th) {
                    //do nothing. user does not meet criteria for this config
                }

            }
            /**
             * @var PortalHomeSubtitleType[]
             */
            $subtitles = [];
            /**
             * @var PortalHomeSubtitleType[]
             */
            $subtitleObjects = [];

            foreach ($portalHomeMenuItems['portalHomeSubTitleFields'] as $config) {
                $subtitleObjects[] = PortalHomeSubtitleType::fromArray($config);
            }
            //fill the configs with user data
            $subtitles = PortalHelper::fillPortalSubtitleForUser($user, $subtitleObjects);
            $portalAlerts = array_map(function ($alert) {
                return Alert::fromArray($alert);
            }, $portalHomeMenuItems['portalAlerts']);
            $alerts = PortalHelper::fillPortalAlertsForuser($user, $portalAlerts);



            return $this->respond(["data" => ["dashboardMenu" => $dashboardMenu, "subtitles" => $subtitles, "alerts" => $alerts]], ResponseInterface::HTTP_OK);

        } catch (\Throwable $th) {
            log_message("error", $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);

        }
    }


    public function appSettings()
    {
        // return CacheHelper::remember('app_settings', function() {
        //read the data from app-settings.json at the root of the project
        try {
            $settings = ['appName', 'appVersion', 'appLongName', 'logo', 'whiteLogo', 'loginBackground', 'portalContactUsTitle', 'portalContactUsSubTitle', 'institutionEmail', 'portalFooterBackground', 'institutionPhone', 'institutionWebsite', 'institutionAddress', 'institutionWhatsapp'];
            //if the user is logged in, add more settings
            // if (auth("tokens")->loggedIn()) {
            //     $settings = array_merge($settings, ['portalHomeMenu']);
            // }
            $data = Utils::getMultipleAppSettings($settings);

            //if logo or other images are set append the base url to it
            $imageProperties = ['logo', 'whiteLogo', 'institutionLogo', 'loginBackground', 'portalFooterBackground'];
            foreach ($imageProperties as $imageProperty) {
                if (isset($data[$imageProperty])) {
                    $data[$imageProperty] = base_url() . $data[$imageProperty];
                }
            }
            $data['recaptchaSiteKey'] = getenv('RECAPTCHA_PUBLIC_KEY');
            if (isset($data['portalHomeMenu'])) {
                //set each image url relative to the base url
                foreach ($data['portalHomeMenu'] as $key => $menu) {
                    if (isset($menu['image'])) {
                        $data['portalHomeMenu'][$key]['image'] = base_url() . $menu['image'];
                    }
                }
            }
            return $this->respond($data, ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => 'System error'], ResponseInterface::HTTP_NOT_FOUND);
        }

        // }, 3600); // Cache for 1 hour
    }

    /**
     * Get the fields for the user profile form
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function getProfileFields()
    {
        try {

            $data = $this->portalService->getUserProfileFields();
            return $this->respond(['data' => $data], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => 'Unable to get your profile. Please try again'], ResponseInterface::HTTP_NOT_FOUND);
        }
    }

    public function getSystemSetting(string $name)
    {
        try {
            $data = $this->portalService->getSystemSetting($name);
            return $this->respond(['data' => $data], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => 'Unable to get your profile. Please try again'], ResponseInterface::HTTP_NOT_FOUND);
        }
    }
}
