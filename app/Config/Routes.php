<?php

use App\Controllers\ActivitiesController;
use App\Controllers\AdminController;
use App\Controllers\AssetController;
use App\Controllers\AuthController;
use App\Controllers\CpdController;
use App\Controllers\EmailController;
use App\Controllers\LicensesController;
use App\Controllers\RegionController;
use App\Controllers\SpecialtiesController;
use CodeIgniter\Router\RouteCollection;
use App\Controllers\PractitionerController;
use App\Controllers\ApplicationsController;
use App\Controllers\PortalsController;

/**
 * @var RouteCollection $routes
 */

$routes->get('/portals/management', [PortalsController::class, "managementPortal"]);
$routes->get('/portals/management/(:any)', [PortalsController::class, "managementPortal"]);

$routes->group("api", ["namespace" => "App\Controllers"], function (RouteCollection $routes) {
    $routes->get("app-settings", [AuthController::class, "appSettings"]);
    $routes->post("register", [AuthController::class, "register"]);
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

$routes->group("activities", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    $routes->get("", [ActivitiesController::class, "index"], ["filter" => ["hasPermission:View Activities"]]);
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

});

$routes->group("practitioners", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    // $routes->put("details/(:segment)", [PractitionerController::class, "updatePractitioner/$1"], ["filter" => ["hasPermission:Create_Or_Edit_Practitioners"]]);
    // $routes->delete("details/(:segment)", [PractitionerController::class, "deletePractitioner/$1"], ["filter" => ["hasPermission:Delete_Practitioners"]]);
    // $routes->get("details/(:segment)", [PractitionerController::class, "getPractitioner/$1"], ["filter" => ["hasPermission:View_Practitioners"]]);
    // $routes->get("details", [PractitionerController::class, "getPractitioners"], ["filter" => ["hasPermission:View_Practitioners"]]);
    // $routes->post("details", [PractitionerController::class, "createPractitioner"], ["filter" => ["hasPermission:Create_Or_Edit_Practitioners"]]);
    // $routes->put("details/(:segment)/restore", [PractitionerController::class, "restorePractitioner/$1"], ["filter" => ["hasPermission:Delete_Practitioners"]]);

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


    // $routes->put("renewal/(:segment)", [PractitionerController::class, "updatePractitionerRenewal/$1"]);
    // $routes->delete("renewal/(:segment)", [PractitionerController::class, "deletePractitionerRenewal/$1"]);
    // $routes->get("renewal", [PractitionerController::class, "getPractitionerRenewals"], ["filter" => ["hasPermission:Site.Content.View"]], );
    // $routes->get("renewal-count", [PractitionerController::class, "countRenewals"], ["filter" => ["hasPermission:Site.Content.View"]], );
    // $routes->get("renewal/practitioner/(:segment)", [PractitionerController::class, "getPractitionerRenewals/$1"], ["filter" => ["hasPermission:Site.Content.View"]], );
    // $routes->get("renewal/(:segment)", [PractitionerController::class, "getPractitionerRenewal/$1"], ["filter" => ["hasPermission:Site.Content.View"]], );
    // $routes->post("renewal", [PractitionerController::class, "createPractitionerRenewal"]);

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
    $routes->put("templates/(:segment)", [ApplicationsController::class, "updateApplicationTemplate/$1"], ["filter" => ["hasPermission:Update_Application_Form_Templates"]]);
    $routes->delete("templates/(:segment)", [ApplicationsController::class, "deleteApplicationTemplate/$1"], ["filter" => ["hasPermission:Delete_Application_Form_Templates"]]);
    $routes->get("templates/(:segment)", [ApplicationsController::class, "getApplicationTemplate/$1"], ["filter" => ["hasPermission:View_Application_Form_Templates"]]);
    $routes->post("templates", [ApplicationsController::class, "createApplicationTemplate"], ["filter" => ["hasPermission:Create_Application_Form_Templates"]]);
    $routes->put("details/(:segment)/(:segment)", [ApplicationsController::class, "finishApplication/$1/$2"], ["filter" => ["hasPermission:Update_Application_Form_Templates"]]);
    $routes->get("config/(:segment)/(:segment)", [ApplicationsController::class, "getApplicationConfig/$1/$2"], ["filter" => ["hasPermission:View_Application_Form_Templates"]]);
    $routes->get("config", [ApplicationsController::class, "getApplicationConfig"], ["filter" => ["hasPermission:View_Application_Form_Templates"]]);
    $routes->get("status/(:segment)", [ApplicationsController::class, "getApplicationStatusTransitions"], ["filter" => ["hasPermission:View_Application_Form_Templates"]], );
    $routes->put("status", [ApplicationsController::class, "updateApplicationStatus"], ["filter" => ["hasPermission:Update_Application_Forms"]]);

});

$routes->group("licenses", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    $routes->put("details/(:segment)", [LicensesController::class, "updateLicense/$1"], ["filter" => ["hasPermission:Update_License_Details"]]);
    $routes->delete("details/(:segment)", [LicensesController::class, "deleteLicense/$1"], ["filter" => ["hasPermission:Delete_License_Details"]]);
    $routes->get("details/(:segment)", [LicensesController::class, "getLicense/$1"], ["filter" => ["hasPermission:View_License_Details"]]);
    $routes->get("details", [LicensesController::class, "getLicenses"], ["filter" => ["hasPermission:View_License_Details"]]);
    $routes->post("details", [LicensesController::class, "createLicense"], ["filter" => ["hasPermission:Create_License_Details"]]);
    $routes->put("details/(:segment)/restore", [LicensesController::class, "restoreLicense/$1"], ["filter" => ["hasPermission:Restore_License_Details"]]);
    $routes->get("count", [LicensesController::class, "countLicenses"], ["filter" => ["hasPermission:View_License_Details"]], );

    $routes->get("config/(:segment)", [LicensesController::class, "getLicenseFormFields/$1"], ["filter" => ["hasPermission:View_License_Details"]]);

    $routes->put("renewal/(:segment)", [LicensesController::class, "updateRenewal/$1"], ["filter" => ["hasPermission:Update_License_Renewal"]]);
    $routes->put("renewalStage", [LicensesController::class, "updateBulkRenewals"], ["filter" => ["hasPermission:Update_License_Renewal"]]);
    $routes->delete("renewal/(:segment)", [LicensesController::class, "deleteRenewal/$1"], ["filter" => ["hasPermission:Delete_License_Renewal"]]);
    $routes->get("renewal", [LicensesController::class, "getRenewals"], ["filter" => ["hasPermission:View_License_Renewal"]]);
    $routes->get("renewal-form-fields/(:segment)", [LicensesController::class, "getLicenseRenewalFormFields"], ["filter" => ["hasPermission:Create_License_Renewal"]], );
    $routes->get("renewal-count", [LicensesController::class, "countRenewals"], ["filter" => ["hasPermission:View_License_Renewal"]], );
    $routes->get("renewal/license/(:segment)", [LicensesController::class, "getRenewals/$1"], ["filter" => ["hasPermission:View_License_Renewal"]], );
    $routes->get("renewal/(:segment)", [LicensesController::class, "getRenewal/$1"], ["filter" => ["hasPermission:View_License_Renewal"]], );
    $routes->post("renewal", [LicensesController::class, "createRenewal"], ["filter" => ["hasPermission:Create_License_Renewal"]]);

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

service('auth')->routes($routes);


