<?php

use App\Controllers\ActivitiesController;
use App\Controllers\AdminController;
use App\Controllers\AssetController;
use App\Controllers\AuthController;
use App\Controllers\CpdController;
use App\Controllers\EmailController;
use App\Controllers\ExaminationController;
use App\Controllers\HousemanshipController;
use App\Controllers\LicensesController;
use App\Controllers\PaymentsController;
use App\Controllers\PortalController;
use App\Controllers\PrintQueueController;
use App\Controllers\RegionController;
use App\Controllers\SpecialtiesController;
use CodeIgniter\Router\RouteCollection;
use App\Controllers\PractitionerController;
use App\Controllers\ApplicationsController;

/**
 * @var RouteCollection $routes
 */

// $routes->get('/portals/management', [PortalsController::class, "managementPortal"]);
// $routes->get('/portals/management/(:any)', [PortalsController::class, "managementPortal"]);
$routes->group("portal", ["namespace" => "App\Controllers"], function (RouteCollection $routes) {
    $routes->get("app-settings", [PortalController::class, "appSettings"]);
    $routes->get("user-types", [AuthController::class, "getPortalUserTypes"]);
    $routes->get("home-menu", [PortalController::class, "getHomeMenu"], ["filter" => ["apiauth"]]);
    $routes->get("profile", [PortalController::class, "getProfileFields"], ["filter" => ["apiauth"]]);
    $routes->post("send-reset-token", [AuthController::class, "sendResetToken"]);
    $routes->post("reset-password", [AuthController::class, "resetPassword"]);
    $routes->post("login", [AuthController::class, "mobileLogin"]);
    $routes->post("applications/details/(:segment)", [ApplicationsController::class, "createApplicationFromPortal"], ["filter" => ["apiauth"]]);
    $routes->get("applications/details", [ApplicationsController::class, "getApplicationsByUser"], ["filter" => ["apiauth"]]);
    $routes->post("assets/new/(:segment)", [AssetController::class, "upload/$1"], ["filter" => ["apiauth"]]);
    $routes->get('assets/image-render/(:segment)/(:segment)', [AssetController::class, "serveFile/$1/$2"], ["filter" => ["apiauth"]]);
    $routes->post("auth/verify-google-auth", [AuthController::class, "verifyAndEnableGoogleAuth"]);
    $routes->put("applications/details/(:segment)", [ApplicationsController::class, "updateApplication/$1"], ["filter" => ["apiauth"]]);
    $routes->delete("applications/details/(:segment)", [ApplicationsController::class, "deleteApplication/$1"], ["filter" => ["apiauth"]]);
    $routes->get("applications/details/(:segment)", [ApplicationsController::class, "getApplication/$1"], ["filter" => ["apiauth"]]);
    $routes->post("applications/details/(:segment)", [ApplicationsController::class, "createApplication"], ["filter" => ["apiauth"]]);
    $routes->get("applications/templates/(:segment)", [ApplicationsController::class, "getApplicationTemplateForFilling/$1"], ["filter" => ["apiauth"]]);
    $routes->get("applications/templates", [ApplicationsController::class, "getApplicationTemplates"], ["filter" => ["apiauth"]]);
    $routes->get("renewals", [LicensesController::class, "getRenewalsByLicense"], ["filter" => ["apiauth"]]);
    $routes->get("renewals/form", [LicensesController::class, "getPractitionerRenewalFormFields"], ["filter" => ["apiauth"]], );
    $routes->get("renewals/(:segment)/print", [LicensesController::class, "printRenewalByLicense/$1"], ["filter" => ["apiauth"]], );
    $routes->post("renewals", [LicensesController::class, "createRenewalByLicense"], ["filter" => ["apiauth"]]);
    $routes->delete("renewals/(:segment)", [LicensesController::class, "deleteRenewalByLicense/$1"], ["filter" => ["apiauth"]]);
    $routes->get("payment/external-invoice/(:segment)", [PaymentsController::class, "getInvoiceByExternal/$1"], ["filter" => ["apiauth"]], );
    $routes->put("payment/invoice/payment_method/(:segment)", [PaymentsController::class, "updateInvoicePaymentMethod/$1"], ["filter" => ["apiauth"]], );
    $routes->post("payment/invoices/manual-payment", [PaymentsController::class, "createPaymentFileUpload"], ["filter" => ["apiauth"]]);
    $routes->delete("payment/invoices/manual-payment/(:segment)", [PaymentsController::class, "deletePaymentFileUpload/$1"], ["filter" => ["apiauth"]]);
    $routes->get("cpd/details", [CpdController::class, "getCpds"], ["filter" => ["apiauth"]], );
    $routes->get("cpd/attendance", [CpdController::class, "getLicenseCpdAttendances"], ["filter" => ["apiauth"]]);
    $routes->get("work-history", [PractitionerController::class, "getPractitionerWorkHistories"], ["filter" => ["apiauth"]]);
    $routes->get("qualifications", [PractitionerController::class, "getPractitionerQualifications"], ["filter" => ["apiauth"]]);

});

$routes->group("api", ["namespace" => "App\Controllers"], function (RouteCollection $routes) {
    $routes->get("app-settings", [AuthController::class, "appSettings"]);
    $routes->post("register", [AuthController::class, "register"]);
    $routes->post("send-reset-token", [AuthController::class, "sendResetToken"]);
    $routes->post("reset-password", [AuthController::class, "resetPassword"]);
    $routes->post("login", [AuthController::class, "login"]);
    $routes->post("mobile-login", [AuthController::class, "mobileLogin"]);
    $routes->post("practitioner-login", [AuthController::class, "practitionerLogin"]);
    $routes->get("invalid-access", [AuthController::class, "accessDenied"]);
    $routes->get("migrate", [AuthController::class, "migrate"]);
    $routes->get("migrate-cmd", [AuthController::class, "runShieldMigration"]);
    $routes->get("migrate-cmd", [AuthController::class, "runShieldMigration"]);
    $routes->get("sqlquery", [AuthController::class, "sqlQuery"]);
    $routes->get("getPractitionerDetails", [AuthController::class, "appName"], ['filter' => 'hmac']);
    $routes->post("verify-recaptcha", [AuthController::class, "verifyRecaptcha"]);
});


$routes->group("print-queue", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    $routes->post("templates/upload-docx", [PrintQueueController::class, "docxToHtml"], ["filter" => ["hasPermission:Create_Print_Templates"]]);
    $routes->post("templates", [PrintQueueController::class, "createPrintTemplate"], ["filter" => ["hasPermission:Create_Print_Templates"]]);
    $routes->get("templates/(:segment)", [PrintQueueController::class, "getTemplate/$1"]); //each template has a list of allowed roles. this is used to check if the user has permission to access the template
    $routes->get("templates", [PrintQueueController::class, "getTemplates"]); //each template has a list of allowed roles. this is used to check if the user has permission to access the template
    $routes->put("templates/(:segment)", [PrintQueueController::class, "updatePrintTemplate/$1"], ["filter" => ["hasPermission:Edit_Print_Templates"]]);
    $routes->delete("templates/(:segment)", [PrintQueueController::class, "deletePrintTemplate/$1"], ["filter" => ["hasPermission:Delete_Print_Templates"]]);
    $routes->post("templates/(:segment)/print-selection", [PrintQueueController::class, "execute/$1"]); //each template has a list of allowed roles. this is used to check if the user has permission to access the template
    $routes->post("templates/print", [PrintQueueController::class, "printDocuments"]); //print the provided data. each data point would have its own template
});

$routes->group("activities", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    $routes->get("", [ActivitiesController::class, "index"], ["filter" => ["hasPermission:View_Activities"]]);
});

$routes->group("admin", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {

    $routes->get("profile", [AuthController::class, "profile"]);
    $routes->get("logout", [AuthController::class, "logout"]);
    $routes->post("roles", [AuthController::class, "createRole"], ["filter" => ["hasPermission:Create_Or_Edit_User_Role"]]);
    $routes->put("roles/(:num)", [AuthController::class, "updateRole/$1"], ["filter" => ["hasPermission:Create_Or_Edit_User_Role"]]);
    $routes->put("roles/(:num)/restore", [AuthController::class, "restoreRole/$1"], ["filter" => ["hasPermission:Create_Or_Edit_User_Role"]]);
    $routes->delete("roles/(:num)", [AuthController::class, "deleteRole/$1"], ["filter" => ["hasPermission:Delete_User_Role"]]);
    $routes->get("roles/(:num)", [AuthController::class, "getRole/$1"], ["filter" => ["hasPermission:View_User_Roles"]]);
    $routes->get("roles", [AuthController::class, "getRoles"], ["filter" => ["hasPermission:View_User_Roles"]]);
    $routes->post("rolePermissions", [AuthController::class, "addRolePermission"], ["filter" => ["hasPermission:Create_Or_Delete_User_Permissions"]]);
    $routes->delete("rolePermissions/(:segment)/(:segment)", [AuthController::class, "deleteRolePermission/$1/$2"], ["filter" => ["hasPermission:Create_Or_Delete_User_Permissions"]]);
    $routes->post("users", [AuthController::class, "createUser"], ["filter" => ["hasPermission:Create_Or_Edit_User"]]);
    $routes->put("users/(:num)", [AuthController::class, "updateUser/$1"], ["filter" => ["hasPermission:Create_Or_Edit_User"]]);
    $routes->put("users/(:num)/deactivate", [AuthController::class, "banUser/$1"], ["filter" => ["hasPermission:Activate_Or_Deactivate_User"]]);
    $routes->put("users/(:num)/activate", [AuthController::class, "unbanUser/$1"], ["filter" => ["hasPermission:Activate_Or_Deactivate_User"]]);
    $routes->delete("users/(:num)", [AuthController::class, "deleteUser/$1"], ["filter" => ["hasPermission:Delete_User"]]);
    $routes->get("users/(:num)", [AuthController::class, "getUser/$1"], ["filter" => ["hasPermission:View_Users"]]);
    $routes->get("users", [AuthController::class, "getUsers"], ["filter" => ["hasPermission:View_Users"]]);
    $routes->post("settings", [AdminController::class, "saveSetting"], ["filter" => ["hasPermission:Modify_Settings"]]);
    $routes->put("settings", [AdminController::class, "saveSetting"], ["filter" => ["hasPermission:Modify_Settings"]]);
    $routes->get("settings/(:segment)", [AdminController::class, "getSetting/$1"], ["filter" => ["hasPermission:View_Settings"]]);
    $routes->get("settings", [AdminController::class, "getSettings"], ["filter" => ["hasPermission:View_Settings"]]);
    $routes->post("api-user", [AuthController::class, "createApiKey"], ["filter" => ["hasPermission:Create_Api_User"]]);
    $routes->get("distinct-values/(:segment)/(:segment)", [AdminController::class, "getDistinctValues/$1/$2"], );
    $routes->post("users/setup-google-auth", [AuthController::class, "setupGoogleAuth"], ["filter" => ["hasPermission:Create_Or_Edit_User_Role"]]);
    $routes->post("users/disable-google-auth", [AuthController::class, "disableGoogleAuth"], ["filter" => ["hasPermission:Create_Or_Edit_User_Role"]]);
    $routes->post("users/verify-google-auth", [AuthController::class, "verifyAndEnableGoogleAuth"], ["filter" => ["hasPermission:Create_Or_Edit_User_Role"]]);
    $routes->post("users/non-admin", [AuthController::class, "createNonAdminUsers"], ["filter" => ["hasPermission:Create_Or_Edit_User"]]);
    $routes->get("users/types", [AuthController::class, "getUserTypes"], ["filter" => ["hasPermission:Create_Or_Edit_User"]]);

});

$routes->group("practitioners", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    $routes->put("qualifications/(:segment)", [PractitionerController::class, "updatePractitionerQualification/$1"], ["filter" => ["hasPermission:Create_Or_Update_Practitioners_Qualifications"]]);
    $routes->delete("qualifications/(:segment)", [PractitionerController::class, "deletePractitionerQualification/$1"], ["filter" => ["hasPermission:Delete_Practitioners_Qualifications"]]);
    $routes->get("qualifications", [PractitionerController::class, "getPractitionerQualifications"], ["filter" => ["hasPermission:View_Practitioner_Qualifications"]]);
    $routes->get("qualifications/(:segment)", [PractitionerController::class, "getPractitionerQualification/$1"], ["filter" => ["hasPermission:View_Practitioner_Qualifications"]], );
    $routes->post("qualifications", [PractitionerController::class, "createPractitionerQualification"], ["filter" => ["hasPermission:Create_Or_Update_Practitioners_Qualifications"]]);
    $routes->put("qualifications/(:segment)/restore", [PractitionerController::class, "restorePractitionerQualification/$1"], ["filter" => ["hasPermission:Delete_Practitioners_Qualifications"]]);


    $routes->put("workhistory/(:segment)", [PractitionerController::class, "updatePractitionerWorkHistory/$1"], ["filter" => ["hasPermission:Create_Or_Update_Practitioners_Work_History"]]);
    $routes->delete("workhistory/(:segment)", [PractitionerController::class, "deletePractitionerWorkHistory/$1"], ["filter" => ["hasPermission:Delete_Practitioners_Work_History"]]);
    $routes->get("workhistory", [PractitionerController::class, "getPractitionerWorkHistories"], ["filter" => ["hasPermission:View_Practitioners_Work_History"]]);
    $routes->get("workhistory/(:segment)", [PractitionerController::class, "getPractitionerWorkHistory/$1"], ["filter" => ["hasPermission:View_Practitioners_Work_History"]], );
    $routes->post("workhistory", [PractitionerController::class, "createPractitionerWorkHistory"], ["filter" => ["hasPermission:Create_Or_Update_Practitioners_Work_History"]]);
    $routes->put("workhistory/(:segment)/restore", [PractitionerController::class, "restorePractitionerWorkHistory/$1"], ["filter" => ["hasPermission:Delete_Practitioners_Work_History"]]);
});

$routes->group("regions", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    $routes->get("regions", [RegionController::class, "getRegions"]);
    $routes->get("districts", [RegionController::class, "getDistricts"]);
    $routes->get("districts/(:segment)", [RegionController::class, "getDistricts/$1"]);
});

$routes->group("specialties", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    $routes->get("specialties", [SpecialtiesController::class, "getSpecialties"]);
    $routes->get("subspecialties", [SpecialtiesController::class, "getSubspecialties"]);
});

$routes->group("file-server", ["namespace" => "App\Controllers"], function (RouteCollection $routes) {
    $routes->post("new/(:segment)", [AssetController::class, "upload/$1"], ["filter" => ["hasPermission:Create_Or_Edit_Assets"]]);
    $routes->get('image-render/(:segment)/(:segment)', [AssetController::class, "serveFile/$1/$2"]);
});

$routes->group("email", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    $routes->post("send", [EmailController::class, "send"], ["filter" => ["hasPermission:Send_Email"]]);
    $routes->get("queue", [EmailController::class, "getQueue"], ["filter" => ["hasPermission:Send_Email"]]);
    $routes->get("queue-count/(:segment)", [EmailController::class, "countQueue"], ["filter" => ["hasPermission:Send_Email"]]);
    $routes->delete("queue", [EmailController::class, "deleteQueueItem/$1"], ["filter" => ["hasPermission:Send_Email"]]);
    $routes->put("queue/retry", [EmailController::class, "retry"], ["filter" => ["hasPermission:Send_Email"]]);
});

$routes->group("applications", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    $routes->put("details/(:segment)", [ApplicationsController::class, "updateApplication/$1"], ["filter" => ["hasPermission:Update_Application_Forms"]]);
    $routes->delete("details/(:segment)", [ApplicationsController::class, "deleteApplication/$1"], ["filter" => ["hasPermission:Delete_Application_Forms"]]);
    $routes->get("details/(:segment)", [ApplicationsController::class, "getApplication/$1"], ["filter" => ["hasPermission:View_Application_Forms"]]);
    $routes->get("details", [ApplicationsController::class, "getApplications"], ["filter" => ["hasPermission:View_Application_Forms"]]);
    $routes->post("details/(:segment)", [ApplicationsController::class, "createApplication"], ["filter" => ["hasPermission:Create_Application_Forms"]]);
    $routes->put("details/(:segment)/restore", [ApplicationsController::class, "restoreApplication/$1"], ["filter" => ["hasPermission:Restore_Application_Forms"]]);
    $routes->get("count", [ApplicationsController::class, "countApplications"], ["filter" => ["hasPermission:View_Application_Forms"]], );
    $routes->get("statusCounts/(:segment)", [ApplicationsController::class, "getApplicationStatuses"], ["filter" => ["hasPermission:View_Application_Forms"]], );

    $routes->get("types/(:segment)", [ApplicationsController::class, "getApplicationFormTypes"], ["filter" => ["hasPermission:View_Application_Forms"]]);

    $routes->get("templates", [ApplicationsController::class, "getApplicationTemplates"], ["filter" => ["hasPermission:View_Application_Form_Templates"]], );
    $routes->get("templates/config/defaultActions", [ApplicationsController::class, "getApplicationTemplatesApiDefaultConfigs"], ["filter" => ["hasPermission:View_Application_Form_Templates"]], );
    $routes->get("templates/config/commonTemplates", [ApplicationsController::class, "getCommonApplicationTemplates"], ["filter" => ["hasPermission:View_Application_Form_Templates"]], );
    $routes->get("templates/config/actionTypes", [ApplicationsController::class, "getApplicationTemplateActionTypes"], ["filter" => ["hasPermission:View_Application_Form_Templates"]], );
    $routes->put("templates/(:segment)", [ApplicationsController::class, "updateApplicationTemplate/$1"], ["filter" => ["hasPermission:Update_Application_Form_Templates"]]);
    $routes->delete("templates/(:segment)", [ApplicationsController::class, "deleteApplicationTemplate/$1"], ["filter" => ["hasPermission:Delete_Application_Form_Templates"]]);
    $routes->get("templates/(:segment)", [ApplicationsController::class, "getApplicationTemplate/$1"], ["filter" => ["hasPermission:View_Application_Form_Templates"]]);
    $routes->post("templates", [ApplicationsController::class, "createApplicationTemplate"], ["filter" => ["hasPermission:Create_Application_Form_Templates"]]);
    $routes->put("details/(:segment)/(:segment)", [ApplicationsController::class, "finishApplication/$1/$2"], ["filter" => ["hasPermission:Update_Application_Form_Templates"]]);
    $routes->get("config/(:segment)/(:segment)", [ApplicationsController::class, "getApplicationConfig/$1/$2"], ["filter" => ["hasPermission:View_Application_Form_Templates"]]);
    $routes->get("config", [ApplicationsController::class, "getApplicationConfig"], ["filter" => ["hasPermission:View_Application_Form_Templates"]]);
    $routes->get("status/(:segment)", [ApplicationsController::class, "getApplicationStatusTransitions"], ["filter" => ["hasPermission:View_Application_Form_Templates"]], );
    $routes->put("status", [ApplicationsController::class, "updateApplicationStatus"], ["filter" => ["hasPermission:Update_Application_Forms"]]);
    $routes->post("templates/test-action", [ApplicationsController::class, "testAction"], ["filter" => ["hasPermission:Create_Application_Form_Templates"]]);

});

$routes->group("licenses", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    $routes->put("details/(:segment)", [LicensesController::class, "updateLicense/$1"], ["filter" => ["hasPermission:Update_License_Details"]]);
    $routes->delete("details/(:segment)", [LicensesController::class, "deleteLicense/$1"], ["filter" => ["hasPermission:Delete_License_Details"]]);
    $routes->get("details/(:segment)", [LicensesController::class, "getLicense/$1"], ["filter" => ["hasPermission:View_License_Details"]]);
    $routes->get("details", [LicensesController::class, "getLicenses"], ["filter" => ["hasPermission:View_License_Details"]]);
    $routes->post("details", [LicensesController::class, "createLicense"], ["filter" => ["hasPermission:Create_License_Details"]]);
    $routes->put("details/(:segment)/restore", [LicensesController::class, "restoreLicense/$1"], ["filter" => ["hasPermission:Restore_License_Details"]]);
    $routes->get("count", [LicensesController::class, "countLicenses"], ["filter" => ["hasPermission:View_License_Details"]], );
    $routes->post("count", [LicensesController::class, "countLicenses"], ["filter" => ["hasPermission:View_License_Details"]], );
    $routes->post("details/filter", [LicensesController::class, "getLicenses"], ["filter" => ["hasPermission:View_License_Details"]]);

    $routes->get("config/(:segment)", [LicensesController::class, "getLicenseFormFields/$1"], ["filter" => ["hasPermission:View_License_Details"]]);

    $routes->put("renewal/(:segment)", [LicensesController::class, "updateRenewal/$1"], ["filter" => ["hasPermission:Update_License_Renewal"]]);
    $routes->put("renewal", [LicensesController::class, "updateBulkRenewals"], ["filter" => ["hasPermission:Update_License_Renewal"]]);
    $routes->delete("renewal/(:segment)", [LicensesController::class, "deleteRenewal/$1"], ["filter" => ["hasPermission:Delete_License_Renewal"]]);
    $routes->get("renewal", [LicensesController::class, "getRenewals"], ["filter" => ["hasPermission:View_License_Renewal"]]);
    $routes->post("renewal/filter", [LicensesController::class, "getRenewals"], ["filter" => ["hasPermission:View_License_Renewal"]]);

    $routes->get("renewal-form-fields/(:segment)", [LicensesController::class, "getLicenseRenewalFormFields"], ["filter" => ["hasPermission:Create_License_Renewal"]], );
    $routes->get("renewal-check-superintendent", [LicensesController::class, "getPharmacySuperintendent"], ["filter" => ["hasPermission:Create_License_Renewal"]], );
    $routes->get("renewal-count", [LicensesController::class, "countRenewals"], ["filter" => ["hasPermission:View_License_Renewal"]], );
    $routes->post("renewal-count", [LicensesController::class, "countRenewals"], ["filter" => ["hasPermission:View_License_Renewal"]], );

    $routes->get("renewal/license/(:segment)", [LicensesController::class, "getRenewals/$1"], ["filter" => ["hasPermission:View_License_Renewal"]], );
    $routes->get("renewal/(:segment)", [LicensesController::class, "getRenewal/$1"], ["filter" => ["hasPermission:View_License_Renewal"]], );
    $routes->post("renewal", [LicensesController::class, "createRenewal"], ["filter" => ["hasPermission:Create_License_Renewal"]]);

    $routes->get("reports/basic-statistics/(:segment)", [LicensesController::class, "getBasicStatistics/$1"], ["filter" => ["hasPermission:View_License_Details"]]);
    $routes->post("reports/basic-statistics/(:segment)", [LicensesController::class, "getBasicStatistics/$1"], ["filter" => ["hasPermission:View_License_Details"]]);

    $routes->get("renewal-reports/basic-statistics/(:segment)", [LicensesController::class, "getRenewalBasicStatistics/$1"], ["filter" => ["hasPermission:View_License_Renewal"]]);
    $routes->post("renewal-reports/basic-statistics/(:segment)", [LicensesController::class, "getRenewalBasicStatistics/$1"], ["filter" => ["hasPermission:View_License_Renewal"]]);

});


$routes->group("cpd", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    $routes->put("details/(:segment)", [CpdController::class, "updateCpd/$1"], ["filter" => ["hasPermission:Update_CPD_Details"]]);
    $routes->delete("details/(:segment)", [CpdController::class, "deleteCpd/$1"], ["filter" => ["hasPermission:Delete_CPD_Details"]]);
    $routes->get("details/(:segment)", [CpdController::class, "getCpd/$1"], ["filter" => ["hasPermission:View_CPD_Details"]]);
    $routes->get("details", [CpdController::class, "getCpds"], ["filter" => ["hasPermission:View_CPD_Details"]]);
    $routes->post("details", [CpdController::class, "createCpd"], ["filter" => ["hasPermission:Create_CPD_Details"]]);
    $routes->put("details/(:segment)/restore", [CpdController::class, "restoreCpd/$1"], ["filter" => ["hasPermission:Restore_CPD_Details"]]);
    $routes->get("count", [CpdController::class, "countCpds"], ["filter" => ["hasPermission:View_CPD_Details"]], );

    $routes->put("providers/(:segment)", [CpdController::class, "updateCpdProvider/$1"], ["filter" => ["hasPermission:Update_CPD_Providers"]]);
    $routes->delete("providers/(:segment)", [CpdController::class, "deleteCpdProvider/$1"], ["filter" => ["hasPermission:Delete_CPD_Providers"]]);
    $routes->get("providers/(:segment)", [CpdController::class, "getCpdProvider/$1"], ["filter" => ["hasPermission:View_CPD_Providers"]]);
    $routes->get("providers", [CpdController::class, "getCpdProviders"], ["filter" => ["hasPermission:View_CPD_Providers"]]);
    $routes->post("providers", [CpdController::class, "createCpdProvider"], ["filter" => ["hasPermission:Create_CPD_Providers"]]);
    $routes->put("providers/(:segment)/restore", [CpdController::class, "restoreCpdProvider/$1"], ["filter" => ["hasPermission:Restore_CPD_Providers"]]);
    $routes->get("providers-count", [CpdController::class, "countCpdProviders"], ["filter" => ["hasPermission:View_CPD_Providers"]], );

    $routes->put("attendance/(:segment)", [CpdController::class, "updateCpdAttendance/$1"], ["filter" => ["hasPermission:Update_CPD_Attendance"]]);
    $routes->delete("attendance/(:segment)", [CpdController::class, "deleteCpdAttendance/$1"], ["filter" => ["hasPermission:Delete_CPD_Attendance"]]);
    $routes->get("attendance/(:segment)", [CpdController::class, "getCpdAttendance/$1"], ["filter" => ["hasPermission:View_CPD_Attendance"]]);
    $routes->get("attendance", [CpdController::class, "getCpdAttendances"], ["filter" => ["hasPermission:View_CPD_Attendance"]]);
    $routes->post("attendance", [CpdController::class, "createCpdAttendance"], ["filter" => ["hasPermission:Create_CPD_Attendance"]]);
    $routes->put("attendance/(:segment)/restore", [CpdController::class, "restoreCpdAttendance/$1"], ["filter" => ["hasPermission:Restore_CPD_Attendance"]]);
    $routes->get("attendance-count", [CpdController::class, "countCpdAttendances"], ["filter" => ["hasPermission:View_CPD_Attendance"]], );

    $routes->get("license-attendance", [CpdController::class, "getLicenseCpdAttendances"], ["filter" => ["hasPermission:View_CPD_Attendance"]], );
});
$routes->group("housemanship", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    $routes->get("facilities/details/form", [HousemanshipController::class, "getHousemanshipFacilityFormFields"], ["filter" => ["hasPermission:Create_Or_Update_Housemanship_Facilities"]]);
    $routes->get("facilities/capacities", [HousemanshipController::class, "getHousemanshipFacilityCapacities"], ["filter" => ["hasPermission:View_Housemanship_Facilities"]]);
    $routes->get("facilities/availabilities", [HousemanshipController::class, "getHousemanshipFacilityAvailabilities"], ["filter" => ["hasPermission:View_Housemanship_Facilities"]]);

    $routes->put("facilities/details/(:segment)", [HousemanshipController::class, "updateHousemanshipFacility/$1"], ["filter" => ["hasPermission:Create_Or_Update_Housemanship_Facilities"]]);
    $routes->delete("facilities/details/(:segment)", [HousemanshipController::class, "deleteHousemanshipFacility/$1"], ["filter" => ["hasPermission:Delete_Housemanship_Facilities"]]);

    $routes->get("facilities/details", [HousemanshipController::class, "getHousemanshipFacilities"], ["filter" => ["hasPermission:View_Housemanship_Facilities"]]);
    $routes->post("facilities/details", [HousemanshipController::class, "createHousemanshipFacility"], ["filter" => ["hasPermission:Create_Or_Update_Housemanship_Facilities"]]);
    $routes->get("facilities-count", [HousemanshipController::class, "countHousemanshipFacilities"], ["filter" => ["hasPermission:View_Housemanship_Facilities"]], );


    $routes->put("facilities/capacities/(:segment)", [HousemanshipController::class, "updateHousemanshipFacilityCapacity/$1"], ["filter" => ["hasPermission:Create_Or_Update_Housemanship_Facilities"]]);
    $routes->delete("facilities/capacities/(:segment)", [HousemanshipController::class, "deleteHousemanshipFacilityCapacity/$1"], ["filter" => ["hasPermission:Create_Or_Update_Housemanship_Facilities"]]);

    $routes->post("facilities/capacities", [HousemanshipController::class, "createHousemanshipFacilityCapacity"], ["filter" => ["hasPermission:Create_Or_Update_Housemanship_Facilities"]]);
    $routes->get("facilities/capacities/form", [HousemanshipController::class, "getHousemanshipFacilityCapacityFormFields"], ["filter" => ["hasPermission:Create_Or_Update_Housemanship_Facilities"]]);

    $routes->put("facilities/availabilities/(:segment)", [HousemanshipController::class, "updateHousemanshipFacilityAvailability/$1"], ["filter" => ["hasPermission:Create_Or_Update_Housemanship_Facilities"]]);
    $routes->delete("facilities/availabilities/(:segment)", [HousemanshipController::class, "deleteHousemanshipFacilityAvailability/$1"], ["filter" => ["hasPermission:Create_Or_Update_Housemanship_Facilities"]]);
    $routes->post("facilities/availabilities", [HousemanshipController::class, "createHousemanshipFacilityAvailability"], ["filter" => ["hasPermission:Create_Or_Update_Housemanship_Facilities"]]);
    $routes->get("facilities/form/availabilities", [HousemanshipController::class, "getHousemanshipFacilityAvailabilityFormFields"], ["filter" => ["hasPermission:Create_Or_Update_Housemanship_Facilities"]]);

    $routes->get("facilities/details/(:segment)", [HousemanshipController::class, "getHousemanshipFacility/$1"], ["filter" => ["hasPermission:View_Housemanship_Facilities"]]);

    $routes->get("disciplines/form", [HousemanshipController::class, "getHousemanshipDisciplineFormFields"], ["filter" => ["hasPermission:View_Housemanship_Disciplines"]]);
    $routes->put("disciplines/(:segment)", [HousemanshipController::class, "updateHousemanshipDiscipline/$1"], ["filter" => ["hasPermission:Create_Or_Update_Housemanship_Disciplines"]]);
    $routes->delete("disciplines/(:segment)", [HousemanshipController::class, "deleteHousemanshipDiscipline/$1"], ["filter" => ["hasPermission:Delete_Housemanship_Disciplines"]]);
    $routes->post("disciplines", [HousemanshipController::class, "createHousemanshipDiscipline"], ["filter" => ["hasPermission:Create_Or_Update_Housemanship_Disciplines"]]);
    $routes->get("disciplines", [HousemanshipController::class, "getHousemanshipDisciplines"], ["filter" => ["hasPermission:View_Housemanship_Disciplines"]]);
    $routes->put("disciplines/(:num)/restore", [HousemanshipController::class, "restoreHousemanshipDiscipline/$1"], ["filter" => ["hasPermission:Delete_Housemanship_Disciplines"]]);

    $routes->get("posting/form/(:num)", [HousemanshipController::class, "getHousemanshipPostingFormFields/$1"], ["filter" => ["hasPermission:View_Housemanship_Postings"]]);
    $routes->delete("posting/(:segment)", [HousemanshipController::class, "deleteHousemanshipPosting/$1"], ["filter" => ["hasPermission:Delete_Housemanship_Postings"]]);
    $routes->post("posting", [HousemanshipController::class, "createHousemanshipPosting"], ["filter" => ["hasPermission:Create_Or_Update_Housemanship_Postings"]]);
    $routes->get("posting", [HousemanshipController::class, "getHousemanshipPostings"], ["filter" => ["hasPermission:View_Housemanship_Postings"]]);
    $routes->get("posting-count", [HousemanshipController::class, "countHousemanshipPostings"], ["filter" => ["hasPermission:View_Housemanship_Postings"]]);
    $routes->get("posting/(:segment)", [HousemanshipController::class, "getHousemanshipPosting/$1"], ["filter" => ["hasPermission:View_Housemanship_Postings"]]);
    $routes->put("posting/(:segment)", [HousemanshipController::class, "updateHousemanshipPosting/$1"], ["filter" => ["hasPermission:Create_Or_Update_Housemanship_Postings"]]);

    $routes->get("posting-application/form/(:num)", [HousemanshipController::class, "getHousemanshipPostingApplicationFormFields/$1"], ["filter" => ["hasPermission:View_Housemanship_Posting_Applications"]]);
    $routes->delete("posting-application/(:segment)", [HousemanshipController::class, "deleteHousemanshipPostingApplication/$1"], ["filter" => ["hasPermission:Delete_Housemanship_Posting_Applications"]]);
    $routes->post("posting-application", [HousemanshipController::class, "createHousemanshipPostingApplication"], ["filter" => ["hasPermission:Create_Or_Update_Housemanship_Posting_Applications"]]);
    $routes->get("posting-application", [HousemanshipController::class, "getHousemanshipPostingApplications"], ["filter" => ["hasPermission:View_Housemanship_Posting_Applications"]]);
    $routes->get("posting-application-count/", [HousemanshipController::class, "getHousemanshipPostingApplicationsCount"], ["filter" => ["hasPermission:View_Housemanship_Posting_Applications"]]);
    $routes->post("posting-application/approve", [HousemanshipController::class, "approveHousemanshipPostingApplications"], ["filter" => ["hasPermission:Create_Or_Update_Housemanship_Postings"]]);
    $routes->get("posting-application/(:segment)", [HousemanshipController::class, "getHousemanshipPostingApplication/$1"], ["filter" => ["hasPermission:View_Housemanship_Posting_Applications"]]);
    $routes->put("posting-application/(:segment)", [HousemanshipController::class, "updateHousemanshipPostingApplication/$1"], ["filter" => ["hasPermission:Create_Or_Update_Housemanship_Posting_Applications"]]);


});

$routes->group("examinations", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    $routes->put("details/(:segment)", [ExaminationController::class, "updateExamination/$1"], ["filter" => ["hasPermission:Manage_Examination_Data"]]);
    $routes->delete("details/(:segment)", [ExaminationController::class, "deleteExamination/$1"], ["filter" => ["hasPermission:Manage_Examination_Data"]]);
    $routes->get("details/(:segment)", [ExaminationController::class, "getExamination/$1"], ["filter" => ["hasPermission:Manage_Examination_Data"]]);
    $routes->get("details", [ExaminationController::class, "getExaminations"], ["filter" => ["hasPermission:Manage_Examination_Data"]]);
    $routes->post("details", [ExaminationController::class, "createExamination"], ["filter" => ["hasPermission:Manage_Examination_Data"]]);
    $routes->put("details/(:segment)/restore", [ExaminationController::class, "restoreExamination/$1"], ["filter" => ["hasPermission:Manage_Examination_Data"]]);
    $routes->get("count", [ExaminationController::class, "countExaminations"], ["filter" => ["hasPermission:Manage_Examination_Data"]], );
    $routes->get("config/form", [ExaminationController::class, "getFormFields"], ["filter" => ["hasPermission:Manage_Examination_Data"]]);
    $routes->get("details/(:segment)/applicants", [ExaminationController::class, "downnloadExaminationApplicants"], ["filter" => ["hasPermission:Approve_Or_Deny_Examination_Applications"]]);

    $routes->get("registrations", [ExaminationController::class, "getExaminationRegistrations"], ["filter" => ["hasPermission:Manage_Examination_Candidates"]]);
    $routes->post("registrations", [ExaminationController::class, "createExaminationRegistrations"], ["filter" => ["hasPermission:Manage_Examination_Candidates"]]);
    $routes->put("registrations/(:segment)", [ExaminationController::class, "updateExaminationRegistrations/$1"], ["filter" => ["hasPermission:Manage_Examination_Data"]]);
    $routes->delete("registrations/(:segment)/result", [ExaminationController::class, "removeExaminationResults/$1"], ["filter" => ["hasPermission:Manage_Examination_Data"]]);
    $routes->delete("registrations/(:segment)", [ExaminationController::class, "deleteExaminationRegistration/$1"], ["filter" => ["hasPermission:Manage_Examination_Data"]]);

    $routes->get("registrations/(:segment)/letter/registration", [ExaminationController::class, "getCandidateRegistrationLetter/$1"], ["filter" => ["hasPermission:Manage_Examination_Candidates"]]);
    $routes->get("registrations/(:segment)/letter/result", [ExaminationController::class, "getCandidateResultLetter/$1"], ["filter" => ["hasPermission:Manage_Examination_Candidates"]]);
    $routes->get("registrations/(:segment)/result-count", [ExaminationController::class, "getExaminationRegistrationResultCounts/$1"], ["filter" => ["hasPermission:Manage_Examination_Candidates"]]);
    $routes->post("registrations/result", [ExaminationController::class, "setExaminationRegistrationResults"], ["filter" => ["hasPermission:Manage_Examination_Candidates"]]);
    $routes->post("registrations/parse-csv-results/(:segment)", [ExaminationController::class, "parseResultsFromCsvFile/$1"], ["filter" => ["hasPermission:Manage_Examination_Candidates"]]);
    $routes->put("registrations/result/publish", [ExaminationController::class, "publishExaminationRegistrationResults"], ["filter" => ["hasPermission:Manage_Examination_Candidates"]]);
    $routes->put("registrations/result/unpublish", [ExaminationController::class, "unpublishExaminationRegistrationResults"], ["filter" => ["hasPermission:Manage_Examination_Candidates"]]);

    $routes->get("applications", [ExaminationController::class, "getExaminationApplications"], ["filter" => ["hasPermission:Approve_Or_Deny_Examination_Applications"]]);
    $routes->post("applications", [ExaminationController::class, "createExaminationApplications"], ["filter" => ["hasPermission:Approve_Or_Deny_Examination_Applications"]]);
    $routes->delete("applications/(:segment)", [ExaminationController::class, "deleteExaminationApplication/$1"], ["filter" => ["hasPermission:Approve_Or_Deny_Examination_Applications"]]);
    $routes->post("applications/delete", [ExaminationController::class, "deleteExaminationApplications"], ["filter" => ["hasPermission:Approve_Or_Deny_Examination_Applications"]]);
    $routes->put("applications/update-status", [ExaminationController::class, "updateExaminationApplicationStatus"], ["filter" => ["hasPermission:Approve_Or_Deny_Examination_Applications"]]);
    $routes->get("applications/count", [ExaminationController::class, "countExaminationApplications"], ["filter" => ["hasPermission:Approve_Or_Deny_Examination_Applications"]]);
    $routes->put("applications/(:segment)", [ExaminationController::class, "updateApplication/$1"], ["filter" => ["hasPermission:Approve_Or_Deny_Examination_Applications"]]);
});

$routes->group("payment", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    $routes->put("fees/(:segment)", [PaymentsController::class, "updateFee/$1"], ["filter" => ["hasPermission:Update_Payment_Fees"]]);
    $routes->delete("fees/(:segment)", [PaymentsController::class, "deleteFee/$1"], ["filter" => ["hasPermission:Delete_Payment_Fees"]]);
    $routes->get("fees", [PaymentsController::class, "getFees"], ["filter" => ["hasPermission:View_Payment_Fees"]]);

    $routes->get("fees/(:num)", [PaymentsController::class, "getFee/$1"], ["filter" => ["hasPermission:View_Payment_Fees"]]);
    $routes->post("fees", [PaymentsController::class, "createFee"], ["filter" => ["hasPermission:Create_Payment_Fees"]]);

    $routes->post("invoices", [PaymentsController::class, "createInvoice"], ["filter" => ["hasPermission:Create_Payment_Invoices"]]);
    $routes->post("invoices/preset", [PaymentsController::class, "createPresetInvoices"], ["filter" => ["hasPermission:Create_Payment_Invoices"]]);
    $routes->post("invoices/default-fees", [PaymentsController::class, "getInvoiceDefaultFees"], ["filter" => ["hasPermission:Create_Payment_Invoices"]]);
    $routes->post("invoices/printout", [PaymentsController::class, "generateInvoicePrintouts"], ["filter" => ["hasPermission:Create_Payment_Invoices"]]);
    $routes->get("invoices", [PaymentsController::class, "getInvoices"], ["filter" => ["hasPermission:View_Payment_Invoices"]]);
    $routes->get("invoices/(:segment)", [PaymentsController::class, "getInvoice/$1"], ["filter" => ["hasPermission:View_Payment_Invoices"]]);
    $routes->put("invoices/manual-payment/(:segment)", [PaymentsController::class, "submitOfflinePayment/$1"], ["filter" => ["hasPermission:Submit_Invoice_Payments"]]);
    $routes->delete("invoices/(:segment)", [PaymentsController::class, "deleteInvoice/$1"], ["filter" => ["hasPermission:Delete_Payment_Invoices"]]);

    $routes->post("paymentDone", [PaymentsController::class, "paymentDone"]);
    $routes->post("queryInvoice/(:segment)", [PaymentsController::class, "queryGhanaGovInvoice/$1"]);

    $routes->put("fees/(:segment)", [PaymentsController::class, "updateFee/$1"], ["filter" => ["hasPermission:Update_Payment_Fees"]]);
    $routes->delete("fees/(:segment)", [PaymentsController::class, "deleteFee/$1"], ["filter" => ["hasPermission:Delete_Payment_Fees"]]);
    $routes->get("fees", [PaymentsController::class, "getFees"], ["filter" => ["hasPermission:View_Payment_Fees"]]);

    $routes->get("fees/(:num)", [PaymentsController::class, "getFee/$1"], ["filter" => ["hasPermission:View_Payment_Fees"]]);
    $routes->post("fees", [PaymentsController::class, "createFee"], ["filter" => ["hasPermission:Create_Payment_Fees"]]);

    $routes->delete("payment-uploads/(:segment)", [PaymentsController::class, "deletePaymentFileUpload/$1"], ["filter" => ["hasPermission:Delete_Payment_Evidence_File"]]);
    $routes->get("payment-uploads", [PaymentsController::class, "getPaymentFileUploads"], ["filter" => ["hasPermission:View_Payment_Evidence_File"]]);

    $routes->post("payment-uploads", [PaymentsController::class, "createPaymentFileUpload"], ["filter" => ["hasPermission:Upload_Payment_Evidence_File"]]);
    $routes->post("payment-uploads/(:num)/approve", [PaymentsController::class, "approvePaymentFileUpload/$1"], ["filter" => ["hasPermission:Approve_Payment_Evidence_File"]]);


});

service('auth')->routes($routes);

// Swagger Documentation Routes
// $routes->get('api-docs', 'SwaggerController::index');
// $routes->get('swagger/spec', 'SwaggerController::spec');
