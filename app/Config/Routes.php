<?php

use App\Controllers\AdminController;
use App\Controllers\AssetController;
use App\Controllers\AuthController;
use App\Controllers\EmailController;
use App\Controllers\RegionController;
use App\Controllers\SpecialtiesController;
use CodeIgniter\Router\RouteCollection;
use App\Controllers\PractitionerController;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('phpinfo', function () {
    phpinfo();
});
$routes->group("api", ["namespace" => "App\Controllers"], function (RouteCollection $routes) {
    $routes->get("app-name", [AuthController::class, "appName"]);
    $routes->post("register", [AuthController::class, "register"]);
    $routes->post("login", [AuthController::class, "login"]);
    $routes->post("mobile-login", [AuthController::class, "mobileLogin"]);
    $routes->get("invalid-access", [AuthController::class, "accessDenied"]);
    $routes->get("migrate", [AuthController::class, "migrate"]);
    $routes->get("migrate-cmd", [AuthController::class, "runShieldMigration"]);

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

});

$routes->group("practitioners", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    $routes->put("details/(:segment)", [PractitionerController::class, "updatePractitioner/$1"]);
    $routes->delete("details/(:segment)", [PractitionerController::class, "deletePractitioner/$1"]);
    $routes->get("details/(:segment)", [PractitionerController::class, "getPractitioner/$1"]);
    $routes->get("details", [PractitionerController::class, "getPractitioners"], ["filter" => ["hasPermission:Site.Content.View"]]);
    $routes->post("details", [PractitionerController::class, "createPractitioner"]);
    $routes->put("details/(:segment)/restore", [PractitionerController::class, "restorePractitioner/$1"]);
    $routes->get("pictures", [PractitionerController::class, "filterPictures"]);

    $routes->put("qualifications/(:segment)", [PractitionerController::class, "updatePractitionerQualification/$1"]);
    $routes->delete("qualifications/(:segment)", [PractitionerController::class, "deletePractitionerQualification/$1"]);
    $routes->get("qualifications", [PractitionerController::class, "getPractitionerQualifications"], ["filter" => ["hasPermission:Site.Content.View"]],);
    $routes->get("qualifications/(:segment)", [PractitionerController::class, "getPractitionerQualification/$1"], ["filter" => ["hasPermission:Site.Content.View"]],);
    $routes->post("qualifications", [PractitionerController::class, "createPractitionerQualification"]);
    $routes->put("qualifications/(:segment)/restore", [PractitionerController::class, "restorePractitionerQualification/$1"]);


    $routes->put("workhistory/(:segment)", [PractitionerController::class, "updatePractitionerWorkHistory/$1"]);
    $routes->delete("workhistory/(:segment)", [PractitionerController::class, "deletePractitionerWorkHistory/$1"]);
    $routes->get("workhistory", [PractitionerController::class, "getPractitionerWorkHistories"], ["filter" => ["hasPermission:Site.Content.View"]],);
    $routes->get("workhistory/(:segment)", [PractitionerController::class, "getPractitionerWorkHistory/$1"], ["filter" => ["hasPermission:Site.Content.View"]],);
    $routes->post("workhistory", [PractitionerController::class, "createPractitionerWorkHistory"]);
    $routes->put("workhistory/(:segment)/restore", [PractitionerController::class, "restorePractitionerWorkHistory/$1"]);


    $routes->put("renewal/(:segment)", [PractitionerController::class, "updatePractitionerRenewal/$1"]);
    $routes->delete("renewal/(:segment)", [PractitionerController::class, "deletePractitionerRenewal/$1"]);
    $routes->get("renewal", [PractitionerController::class, "getPractitionerRenewals"], ["filter" => ["hasPermission:Site.Content.View"]],);
    $routes->get("renewal/practitioner/(:segment)", [PractitionerController::class, "getPractitionerRenewals/$1"], ["filter" => ["hasPermission:Site.Content.View"]],);
    $routes->get("renewal/(:segment)", [PractitionerController::class, "getPractitionerRenewal/$1"], ["filter" => ["hasPermission:Site.Content.View"]],);
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
    $routes->get("subspecialties/(:segment)", [SpecialtiesController::class, "getSubspecialties/$1"]);
});

$routes->group("file-server", ["namespace" => "App\Controllers"], function (RouteCollection $routes) {
    $routes->post("new/(:segment)", [AssetController::class, "upload/$1"], ["filter" => ["hasPermission:Site.Content.View"]]);
    $routes->get('image-render/(:segment)/(:segment)', [AssetController::class, "serveFile/$1/$2"]);
});

$routes->group("email", ["namespace" => "App\Controllers"], function (RouteCollection $routes) {
    $routes->post("send", [EmailController::class, "send"] );
});


service('auth')->routes($routes);
