<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
$router->group(['prefix' => 'auth'], function () use ($router) {
    $router->post('login', 'AuthController@login');
    $router->post('register', 'AuthController@register');
    $router->post('forgot-password', 'AuthController@forgotPassword');
    $router->get('reset-password', 'AuthController@showResetPasswordForm');
    $router->post('reset-password', 'AuthController@resetPassword');
    $router->get('verify-email', 'AuthController@verifyEmail');
});

$router->group(['middleware' => 'auth'], function () use ($router) {
    $router->post('/auth/logout', 'AuthController@logout');

    $router->get('check-auth', function () {
        return 'check';
    });
});
