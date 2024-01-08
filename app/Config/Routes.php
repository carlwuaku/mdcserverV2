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
    $routes->get("app-name", [AuthController::class, "appName"]);
    $routes->post("register", [AuthController::class, "register"]);
    $routes->post("login", [AuthController::class, "login"]);
    $routes->post("mobile-login", [AuthController::class, "mobileLogin"]);

});

$routes->group("admin", ["namespace" => "App\Controllers", "filter" => "apiauth"], function (RouteCollection $routes) {
    
    $routes->get("profile", [AuthController::class, "profile"]);
    $routes->get("logout", [AuthController::class, "logout"]);
    $routes->post("roles", [AuthController::class, "createRole"]);
    $routes->put("roles/(:num)", [AuthController::class, "updateRole/$1"]);
    $routes->delete("roles/(:num)", [AuthController::class, "deleteRole/$1"]);
    $routes->get("roles/(:num)", [AuthController::class, "getRole/$1"]);
    $routes->get("roles", [AuthController::class, "getRoles"]);
    $routes->post("rolePermissions", [AuthController::class, "addRolePermission"]);
    $routes->delete("rolePermissions/(:num)", [AuthController::class, "deleteRolePermission/$1"]);
    $routes->post("users", [AuthController::class, "createUser"]);
    $routes->put("users/(:num)", [AuthController::class, "updateUser/$1"]);
    $routes->put("users/(:num)/deactivate", [AuthController::class, "banUser/$1"]);
    $routes->put("users/(:num)/activate", [AuthController::class, "unbanUser/$1"]);
    $routes->delete("users/(:num)", [AuthController::class, "deleteUser/$1"]);
    $routes->get("users/(:num)", [AuthController::class, "getUser/$1"]);
    $routes->get("users", [AuthController::class, "getUsers"]);
});


service('auth')->routes($routes);
