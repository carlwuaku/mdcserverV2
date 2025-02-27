<?php
/**
 * this helper class contains methods to help with the creation of submitted application forms
 */
namespace App\Helpers;
class ApplicationFormActionHelper extends Utils
{
    /**
     * this method runs a provided action on the application form
     * @param object{type:string, config:object} $action
     * @param array $data
     * @return array
     */
    public static function runAction($action, $data)
    {
        switch ($action->type) {
            case 'email':
                return self::sendEmailToApplicant($action, $data);
            case 'admin_email':
                return self::sendEmailToAdmin($action, $data);
            case 'api_call':
                return self::callApi($action, $data);
            default:
                return $data;
        }
    }

    /**
     * this method sends an email to the applicant
     * @param object{type:string, config:object {template:string, subject:string}} $action
     * @param array $data
     * @return array
     */
    private static function sendEmailToApplicant($action, $data)
    {
        log_message('info', 'Sending email to applicant');
        $templateModel = new TemplateEngineHelper();
        $content = $templateModel->process($action->config['template'], $data);
        $subject = $templateModel->process($action->config['subject'], $data);
        $emailConfig = new EmailConfig($content, $subject, $data['email']);

        EmailHelper::sendEmail($emailConfig);
        return $data;
    }

    /**
     * this method sends an email to the admin
     * @param object{type:string, config:object {template:string, subject:string, admin_email:string}} $action
     * @param array $data
     * @return array
     */
    private static function sendEmailToAdmin($action, $data)
    {
        log_message('info', 'Sending email to admin');
        $templateModel = new TemplateEngineHelper();
        $content = $templateModel->process($action->config['template'], $data);
        $subject = $templateModel->process($action->config['subject'], $data);
        $emailConfig = new EmailConfig($content, $subject, $action->config['admin_email']);

        EmailHelper::sendEmail($emailConfig);
        return $data;
    }

    /**
     * this method makes an api call
     * @param object{type:string, config:object {url:string, method:string, headers:array, body:array}} $action
     * @param array $data
     * @return array
     */
    private static function callApi($action, $data)
    {
        //update the values of the body with the matching keys from $data
        foreach ($action->config->body as $key => $value) {
            if (array_key_exists($value, $data)) {
                $action->config->body[$key] = $data[$value];
            }
        }
        /**
         * @var \GuzzleHttp\RequestOptions[] $requestOptions
         */
        $requestOptions = [];
        $requestOptions['headers'] = $action->config->headers;
        //TODO: update the header values with dynamic values
        switch ($action->config->method) {
            case 'GET':
                return NetworkUtils::makeGetRequest($action->config->url, $requestOptions);
            case 'POST':
                $requestOptions['json'] = $action->config->body;
                return NetworkUtils::makePostRequest($action->config->url, $requestOptions);
            default:
                return $data;
        }
    }//TODO: check body and headers




}

