<?php
namespace App\Helpers;
use App\Helpers\Types\Action;
use App\Helpers\Types\Alert;
use App\Helpers\Types\CriteriaType;
use App\Helpers\Types\PortalHomeConfigType;
use App\Helpers\Types\PortalHomeSubtitleType;
use App\Models\UsersModel;
use CodeIgniter\Shield\Entities\User;
class PortalHelper
{

    /**
     * Fills the portal home menu for the given user, given the config.
     * The title, description, image, alerts and actions are filtered based on the criteria in the config.
     * The title, description and image are also processed by the template engine to replace any variables.
     * @param UsersModel $user
     * @param PortalHomeConfigType $portalHomeConfig
     * @return PortalHomeConfigType
     */
    public static function fillPortalHomeMenuForUser(UsersModel $user, PortalHomeConfigType $portalHomeConfig)
    {

        //strings may have variables in them like [var_name]. replace them
        try {
            $templateEngine = new TemplateEngineHelper();
            $userData = array_merge(["display_name" => $user->display_name, "email_address" => $user->email_address, "user_type" => $user->user_type], (array) $user->profile_data);
            if (!CriteriaType::matchesCriteria($userData, $portalHomeConfig->criteria)) {
                throw new \Exception("User does not match criteria");
            }
            $portalHomeConfig->title = $templateEngine->process($portalHomeConfig->title, $userData);
            $portalHomeConfig->description = $templateEngine->process($portalHomeConfig->description, $userData);
            $portalHomeConfig->image = $templateEngine->process($portalHomeConfig->image, $userData);
            //if the image is not a full url, add the base url to it
            if (strpos($portalHomeConfig->image, "http") === false) {
                $portalHomeConfig->image = base_url($portalHomeConfig->image);
            }
            //for the dataPoints, alerts and actions, include them if the user matches the criteria
            $alerts = [];
            foreach ($portalHomeConfig->alerts as $alert) {
                if (CriteriaType::matchesCriteria($userData, $alert->criteria)) {
                    $alerts[] = new Alert(
                        $templateEngine->process($alert->message, $userData),
                        $alert->type
                    );
                }
            }
            $portalHomeConfig->alerts = $alerts;

            $actions = [];
            foreach ($portalHomeConfig->actions as $action) {
                if (CriteriaType::matchesCriteria($userData, $action->criteria)) {
                    unset($action->criteria);
                    $actions[] = $action;
                }
            }
            $dataPoints = [];
            foreach ($portalHomeConfig->dataPoints as $dataPoint) {
                if (CriteriaType::matchesCriteria($userData, $dataPoint->criteria)) {
                    //the dataPoint url may contain placeholders. look for anything in the form of [placeholder] and replace it with data from the user/preset data
                    $templateEngine->addCustomDateFormat("current_year", "Y");
                    $dataPoint->apiUrl = $templateEngine->process($dataPoint->apiUrl, $userData);
                    // log_message('info', 'Template data: ' . json_encode($dataPoint->apiUrl));

                    unset($dataPoint->criteria);
                    $dataPoints[] = $dataPoint;
                }
            }
            $portalHomeConfig->dataPoints = $dataPoints;
            $portalHomeConfig->actions = $actions;

            return $portalHomeConfig;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Fills the portal home subtitle for the given user, given the config.
     * The subtitle is filtered based on the criteria in the config.
     * @param UsersModel $user
     * @param PortalHomeSubtitleType[] $portalHomeSubtitleConfig
     * @return PortalHomeSubtitleType[]
     */
    public static function fillPortalSubtitleForUser(UsersModel $user, array $portalHomeSubtitleConfig)
    {
        try {
            $userData = array_merge([$user->display_name, $user->email_address], (array) $user->profile_data);
            $templateEngine = new TemplateEngineHelper();
            /**
             * @var PortalHomeSubtitleType[]
             */
            $subtitles = [];
            foreach ($portalHomeSubtitleConfig as $subtitle) {
                if (CriteriaType::matchesCriteria($userData, $subtitle->criteria)) {
                    if (isset($subtitle->template) && !empty(trim($subtitle->template))) {
                        $subtitles[] = new PortalHomeSubtitleType("", $subtitle->label, $templateEngine->process($subtitle->template, $userData));
                    } else {
                        $subtitles[] = new PortalHomeSubtitleType("", $subtitle->label, $userData[$subtitle->field]);
                    }
                }
            }

            return $subtitles;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Fills the portal home alerts for the given user, given the config.
     * The alerts are filtered based on the criteria in the config.
     * The message of each alert is processed by the template engine to replace any variables.
     * @param UsersModel $user
     * @param Alert[] $alerts
     * @return Alert[]
     */
    public static function fillPortalAlertsForuser(UsersModel $user, array $alerts)
    {
        $results = [];
        $templateEngine = new TemplateEngineHelper();
        $userData = array_merge(["display_name" => $user->display_name, "email_address" => $user->email_address, "user_type" => $user->user_type], (array) $user->profile_data);
        foreach ($alerts as $alert) {
            if (CriteriaType::matchesCriteria($userData, $alert->criteria)) {
                $results[] = new Alert(
                    $templateEngine->process($alert->message, $userData),
                    $alert->type
                );
            }
        }

        return $results;
    }
}