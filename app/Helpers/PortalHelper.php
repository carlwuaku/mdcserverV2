<?php
namespace App\Helpers;
use App\Helpers\Types\Action;
use App\Helpers\Types\Alert;
use App\Helpers\Types\CriteriaType;
use App\Helpers\Types\PortalHomeConfigType;
use App\Helpers\Types\PortalHomeSubtitleType;
use CodeIgniter\Shield\Entities\User;
class PortalHelper
{

    public static function fillPortalHomeMenuForUser(User $user, PortalHomeConfigType $portalHomeConfig)
    {

        //strings may have variables in them like [var_name]. replace them
        try {
            $templateEngine = new TemplateEngineHelper();
            $userData = array_merge([$user->display_name, $user->email_address], (array) $user->profile_data);
            $portalHomeConfig->title = $templateEngine->process($portalHomeConfig->title, $userData);
            $portalHomeConfig->description = $templateEngine->process($portalHomeConfig->description, $userData);
            $portalHomeConfig->image = $templateEngine->process($portalHomeConfig->image, $userData);
            //if the image is not a full url, add the base url to it
            if (strpos($portalHomeConfig->image, "http") === false) {
                $portalHomeConfig->image = base_url($portalHomeConfig->image);
            }
            //for the alerts and actions, include them if the user matches the criteria
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
            $portalHomeConfig->actions = $actions;
            return $portalHomeConfig;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Fills the portal home subtitle for the given user, given the config.
     * The subtitle is filtered based on the criteria in the config.
     * @param User $user
     * @param PortalHomeSubtitleType[] $portalHomeSubtitleConfig
     * @return PortalHomeSubtitleType[]
     */
    public static function fillPortalSubtitleForUser(User $user, array $portalHomeSubtitleConfig)
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
}