<?php

use App\Controllers\AdminController;
use App\Controllers\AssetController;
use App\Controllers\AuthController;
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
    $routes->get("details", [PractitionerController::class, "getPractitioners"]);
    $routes->post("details", [PractitionerController::class, "createPractitioner"]);
    $routes->put("details/(:segment)/restore", [AuthController::class, "restorePractitioner/$1"]);

    $routes->put("qualifications/(:segment)", [PractitionerController::class, "updatePractitionerQualification/$1"]);
    $routes->delete("qualifications/(:segment)", [PractitionerController::class, "deletePractitionerQualification/$1"]);
    $routes->get("qualifications/(:segment)", [PractitionerController::class, "getPractitionerQualification/$1"]);
    $routes->get("qualifications", [PractitionerController::class, "getPractitionerQualifications"]);
    $routes->post("qualifications", [PractitionerController::class, "createPractitionerQualification"]);
    $routes->put("qualifications/(:segment)/restore", [PractitionerController::class, "restorePractitionerQualification/$1"]);

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
    $routes->post("new/(:segment)", [AssetController::class, "upload/$1"]);
    $routes->get('image-render/(:segment)/(:segment)', [AssetController::class, "serveFile/$1/$2"]);
});


service('auth')->routes($routes);
