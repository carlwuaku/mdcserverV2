<?php

use App\Controllers\AuthController;
use CodeIgniter\Router\RouteCollection;
use App\Controllers\Pages;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('phpinfo', function () {
    phpinfo();
});
$routes->get('pages', [Pages::class, 'index']);
$routes->get('(:segment)', [Pages::class, 'view']);
$routes->group("api", ["namespace" => "App\Controllers"], function (RouteCollection $routes) {
    $routes->get("invalid-access", [AuthController::class, "accessDenied"]);
    $routes->post("register", [AuthController::class, "register"]);
    $routes->post("login", [AuthController::class, "login"]);
    $routes->post("mobile_login", [AuthController::class, "mobileLogin"]);
    $routes->get("profile", [AuthController::class, "profile"], ["filter" => "apiauth"]);
    $routes->get("logout", [AuthController::class, "logout"], ["filter" => "apiauth"]);
    $routes->post("roles", [AuthController::class, "createRole"], ["filter" => "apiauth"]);
    $routes->put("roles/(:num)", [AuthController::class, "updateRole/$1"], ["filter" => "apiauth"]);
    $routes->delete("roles/(:num)", [AuthController::class, "deleteRole/$1"], ["filter" => "apiauth"]);
    $routes->get("roles/(:num)", [AuthController::class, "getRole/$1"], ["filter" => "apiauth"]);
    $routes->get("roles", [AuthController::class, "getRoles"], ["filter" => "apiauth"]);
    $routes->post("rolePermissions", [AuthController::class, "addRolePermission"], ["filter" => "apiauth"]);
    $routes->delete("rolePermissions/(:num)", [AuthController::class, "deleteRolePermission/$1"], ["filter" => "apiauth"]);

});

service('auth')->routes($routes);
