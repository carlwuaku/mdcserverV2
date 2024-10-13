<?php

use App\Controllers\ActivitiesController;
use App\Controllers\AdminController;
use App\Controllers\AssetController;
use App\Controllers\AuthController;
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
$routes->get('/', 'Home::index');
$routes->get('phpinfo', function () {
    phpinfo();
});
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
    $routes->get("", [ActivitiesController::class, "index"]);
});

$routes->group("admin", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {

    $routes->get("profile", [AuthController::class, "profile"]);
    $routes->get("logout", [AuthController::class, "logout"]);
    $routes->post("roles", [AuthController::class, "createRole"]);
    $routes->put("roles/(:num)", [AuthController::class, "updateRole/$1"]);
    $routes->put("roles/(:num)/restore", [AuthController::class, "restoreRole/$1"]);
    $routes->delete("roles/(:num)", [AuthController::class, "deleteRole/$1"]);
    $routes->get("roles/(:num)", [AuthController::class, "getRole/$1"]);
    $routes->get("roles", [AuthController::class, "getRoles"]);
    $routes->post("rolePermissions", [AuthController::class, "addRolePermission"]);
    $routes->delete("rolePermissions/(:num)/(:num)", [AuthController::class, "deleteRolePermission/$1/$2"]);
    $routes->post("users", [AuthController::class, "createUser"]);
    $routes->put("users/(:num)", [AuthController::class, "updateUser/$1"]);
    $routes->put("users/(:num)/deactivate", [AuthController::class, "banUser/$1"]);
    $routes->put("users/(:num)/activate", [AuthController::class, "unbanUser/$1"]);
    $routes->delete("users/(:num)", [AuthController::class, "deleteUser/$1"]);
    $routes->get("users/(:num)", [AuthController::class, "getUser/$1"]);
    $routes->get("users", [AuthController::class, "getUsers"]);
    $routes->post("settings", [AdminController::class, "saveSetting"]);
    $routes->put("settings", [AdminController::class, "saveSetting"]);
    $routes->get("settings/(:segment)", [AdminController::class, "getSetting/$1"]);
    $routes->get("settings", [AdminController::class, "getSettings"]);
    $routes->post("api-user", [AuthController::class, "createApiKey"]);

});

$routes->group("practitioners", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    $routes->put("details/(:segment)", [PractitionerController::class, "updatePractitioner/$1"]);
    $routes->delete("details/(:segment)", [PractitionerController::class, "deletePractitioner/$1"]);
    $routes->get("details/(:segment)", [PractitionerController::class, "getPractitioner/$1"]);
    $routes->get("details", [PractitionerController::class, "getPractitioners"], ["filter" => ["hasPermission:Site.Content.View"]]);
    $routes->post("details", [PractitionerController::class, "createPractitioner"]);
    $routes->put("details/(:segment)/restore", [PractitionerController::class, "restorePractitioner/$1"]);

    $routes->put("qualifications/(:segment)", [PractitionerController::class, "updatePractitionerQualification/$1"]);
    $routes->delete("qualifications/(:segment)", [PractitionerController::class, "deletePractitionerQualification/$1"]);
    $routes->get("qualifications", [PractitionerController::class, "getPractitionerQualifications"], ["filter" => ["hasPermission:Site.Content.View"]], );
    $routes->get("qualifications/(:segment)", [PractitionerController::class, "getPractitionerQualification/$1"], ["filter" => ["hasPermission:Site.Content.View"]], );
    $routes->post("qualifications", [PractitionerController::class, "createPractitionerQualification"]);
    $routes->put("qualifications/(:segment)/restore", [PractitionerController::class, "restorePractitionerQualification/$1"]);


    $routes->put("workhistory/(:segment)", [PractitionerController::class, "updatePractitionerWorkHistory/$1"]);
    $routes->delete("workhistory/(:segment)", [PractitionerController::class, "deletePractitionerWorkHistory/$1"]);
    $routes->get("workhistory", [PractitionerController::class, "getPractitionerWorkHistories"], ["filter" => ["hasPermission:Site.Content.View"]], );
    $routes->get("workhistory/(:segment)", [PractitionerController::class, "getPractitionerWorkHistory/$1"], ["filter" => ["hasPermission:Site.Content.View"]], );
    $routes->post("workhistory", [PractitionerController::class, "createPractitionerWorkHistory"]);
    $routes->put("workhistory/(:segment)/restore", [PractitionerController::class, "restorePractitionerWorkHistory/$1"]);


    $routes->put("renewal/(:segment)", [PractitionerController::class, "updatePractitionerRenewal/$1"]);
    $routes->delete("renewal/(:segment)", [PractitionerController::class, "deletePractitionerRenewal/$1"]);
    $routes->get("renewal", [PractitionerController::class, "getPractitionerRenewals"], ["filter" => ["hasPermission:Site.Content.View"]], );
    $routes->get("renewal-count", [PractitionerController::class, "countRenewals"], ["filter" => ["hasPermission:Site.Content.View"]], );
    $routes->get("renewal/practitioner/(:segment)", [PractitionerController::class, "getPractitionerRenewals/$1"], ["filter" => ["hasPermission:Site.Content.View"]], );
    $routes->get("renewal/(:segment)", [PractitionerController::class, "getPractitionerRenewal/$1"], ["filter" => ["hasPermission:Site.Content.View"]], );
    $routes->post("renewal", [PractitionerController::class, "createPractitionerRenewal"]);

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
    $routes->post("new/(:segment)", [AssetController::class, "upload/$1"], ["filter" => ["hasPermission:Site.Content.View"]]);
    $routes->get('image-render/(:segment)/(:segment)', [AssetController::class, "serveFile/$1/$2"]);
});

$routes->group("email", ["namespace" => "App\Controllers"], function (RouteCollection $routes) {
    $routes->post("send", [EmailController::class, "send"]);
});

$routes->group("applications", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    $routes->put("details/(:segment)", [ApplicationsController::class, "updateApplication/$1"]);
    $routes->delete("details/(:segment)", [ApplicationsController::class, "deleteApplication/$1"]);
    $routes->get("details/(:segment)", [ApplicationsController::class, "getApplication/$1"]);
    $routes->get("details", [ApplicationsController::class, "getApplications"], ["filter" => ["hasPermission:Site.Content.View"]]);
    $routes->post("details/(:segment)", [ApplicationsController::class, "createApplication"]);
    $routes->put("details/(:segment)/restore", [ApplicationsController::class, "restoreApplication/$1"]);
    $routes->get("count", [ApplicationsController::class, "countApplications"], ["filter" => ["hasPermission:Site.Content.View"]], );
    $routes->get("types/(:segment)", [ApplicationsController::class, "getApplicationFormTypes"], ["filter" => ["hasPermission:Site.Content.View"]]);

    $routes->get("templates", [ApplicationsController::class, "getApplicationTemplates"], ["filter" => ["hasPermission:Site.Content.View"]], );
    $routes->put("templates/(:segment)", [ApplicationsController::class, "updateApplicationTemplate/$1"]);
    $routes->delete("templates/(:segment)", [ApplicationsController::class, "deleteApplicationTemplate/$1"]);
    $routes->get("templates/(:segment)", [ApplicationsController::class, "getApplicationTemplate/$1"]);
    $routes->post("templates", [ApplicationsController::class, "createApplicationTemplate"]);
    $routes->put("details/(:segment)/(:segment)", [ApplicationsController::class, "finishApplication/$1/$2"]);
    $routes->get("config/(:segment)/(:segment)", [ApplicationsController::class, "getApplicationConfig/$1/$2"]);
    $routes->get("config", [ApplicationsController::class, "getApplicationConfig"]);
});

$routes->group("licenses", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    $routes->put("details/(:segment)", [LicensesController::class, "updateLicense/$1"]);
    $routes->delete("details/(:segment)", [LicensesController::class, "deleteLicense/$1"]);
    $routes->get("details/(:segment)", [LicensesController::class, "getLicense/$1"]);
    $routes->get("details", [LicensesController::class, "getLicenses"], ["filter" => ["hasPermission:Site.Content.View"]]);
    $routes->post("details", [LicensesController::class, "createLicense"]);
    $routes->put("details/(:segment)/restore", [LicensesController::class, "restoreLicense/$1"]);
    $routes->get("count", [LicensesController::class, "countLicenses"], ["filter" => ["hasPermission:Site.Content.View"]], );

    $routes->get("config/(:segment)", [LicensesController::class, "getLicenseFormFields/$1"]);

    $routes->put("renewal/(:segment)", [PractitionerController::class, "updatePractitionerRenewal/$1"]);
    $routes->delete("renewal/(:segment)", [PractitionerController::class, "deletePractitionerRenewal/$1"]);
    $routes->get("renewal", [PractitionerController::class, "getPractitionerRenewals"], ["filter" => ["hasPermission:Site.Content.View"]], );
    $routes->get("renewal-count", [PractitionerController::class, "countRenewals"], ["filter" => ["hasPermission:Site.Content.View"]], );
    $routes->get("renewal/practitioner/(:segment)", [PractitionerController::class, "getPractitionerRenewals/$1"], ["filter" => ["hasPermission:Site.Content.View"]], );
    $routes->get("renewal/(:segment)", [PractitionerController::class, "getPractitionerRenewal/$1"], ["filter" => ["hasPermission:Site.Content.View"]], );
    $routes->post("renewal", [PractitionerController::class, "createPractitionerRenewal"]);

});


service('auth')->routes($routes);
