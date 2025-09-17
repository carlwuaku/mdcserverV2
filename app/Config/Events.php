<?php

namespace Config;

use App\Helpers\PaymentUtils;
use CodeIgniter\Events\Events;
use App\Helpers\Utils;
use App\Helpers\ApplicationFormActionHelper;
use Codeigniter\Database\Query;

/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create code that can execute by subscribing to events with
 * the 'on()' method. This accepts any form of callable, including
 * Closures, that will be executed when the event is triggered.
 *
 * Example:
 *      Events::on('create', [$myInstance, 'myMethod']);
 */

// Events::on('pre_system', static function () {
//     if (ENVIRONMENT !== 'testing') {
//         if (ini_get('zlib.output_compression')) {
//             throw FrameworkException::forEnabledZlibOutputCompression();
//         }

//         while (ob_get_level() > 0) {
//             ob_end_flush();
//         }

//         ob_start(static fn($buffer) => $buffer);
//     }

//     /*
//      * --------------------------------------------------------------------
//      * Debug Toolbar Listeners.
//      * --------------------------------------------------------------------
//      * If you delete, they will no longer be collected.
//      */
//     if (CI_DEBUG && !is_cli()) {
//         Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
//         Services::toolbar()->respond();
//         // Hot Reload route - for framework use on the hot reloader.
//         if (ENVIRONMENT === 'development') {
//             Services::routes()->get('__hot-reload', static function () {
//                 (new HotReloader())->run();
//             });
//         }
//     }
// });

Events::on(EVENT_INVOICE_CREATED, static function ($invoice, $items) {
    log_message("info", "event - Invoice created: " . $invoice['invoice_number']);
});

Events::on(EVENT_INVOICE_PAYMENT_COMPLETED, static function (string $uuid) {
    //get the invoice details
    $invoiceDetails = PaymentUtils::getInvoiceDetails($uuid);
    //check the purpose of the payment
    $purpose = $invoiceDetails['purpose'];
    //replace the uuid with purpose_table_uuid
    $invoiceDetails['uuid'] = $invoiceDetails['purpose_table_uuid'];
    //get the config for the purpose from app-settings
    /**
     * @var array{defaultInvoiceItems: array {criteria: array {field:string, value:string[]}[], feeServiceCodes: array}[], paymentMethods: array, sourceTableName: string, description: string, onPaymentCompletedActions: array}[]
     */
    $purposes = Utils::getPaymentSettings()["purposes"];
    if (!isset($purposes[$purpose])) {
        throw new \InvalidArgumentException("Invalid payment purpose: $purpose");
    }
    //get the onPaymentCompletedActions for the purpose. these are identical to the actions for application forms. so we can use the ApplicationFormActionHelper methods to run them.
    $onPaymentCompletedActions = $purposes[$purpose]["onPaymentCompletedActions"];
    if (empty($onPaymentCompletedActions)) {
        log_message("info", "No onPaymentCompletedActions found for purpose: $purpose");
        return;
    }

    //run the actions
    $model = new \App\Models\Payments\InvoiceModel();
    $model->db->transException(true)->transStart();
    try {
        foreach ($onPaymentCompletedActions as $action) {
            //the ApplicationFormActionHelper expects an object for the cofig, and some data to process.  
            $result = ApplicationFormActionHelper::runAction((object) $action, $invoiceDetails);
            log_message("info", "action ran for invoice payment" . json_encode($invoiceDetails) . " <br> Results: " . json_encode($result));
        }

        $model->db->transComplete();
    } catch (\Throwable $e) {
        $model->db->transRollback();
        throw $e;
    }
});

Events::on(EVENT_APPLICATION_FORM_ACTION_COMPLETED, static function (object $action, array $data, array $result) {

    //log this to the actions database   
    try {
        //TODO: save the results of the action somewhere

    } catch (\Throwable $e) {

    }
});

Events::on('DBQuery', function (Query $query) {
    if ($query->hasError()) {
        log_message('error', 'Database Query Error: ' . $query->getQuery());
        log_message('error', 'Database Error: ' . $query->getErrorMessage());
        log_message('error', 'Database Error Code: ' . $query->getErrorCode());
    }
});


