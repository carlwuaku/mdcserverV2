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
$routes->get('pages', [Pages::class,'index']);
$routes->get('(:segment)', [Pages::class,'view']);
$routes->group("api", ["namespace" => "App\Controllers"], function(RouteCollection $routes){
    $routes->get("invalid-access", [AuthController::class, "accessDenied"]);
    $routes->post("register", [AuthController::class,"register"]);
    $routes->post("login", [AuthController::class,"login"]);
    $routes->get("profile", [AuthController::class,"profile"], ["filter" => "apiauth"]);
    $routes->get("logout", [AuthController::class,"logout"], ["filter" => "apiauth"]);
});

service('auth')->routes($routes);
