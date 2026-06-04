<?php

use App\Controllers\AuthController;
use App\Controllers\AdminController;

$router->get('/api/auth/sectors',         [AuthController::class, 'sectors']);
$router->post('/api/auth/login',          [AuthController::class, 'login']);
$router->post('/api/auth/register',       [AuthController::class, 'register']);
$router->post('/api/auth/forgot-password',[AuthController::class, 'forgotPassword']);
$router->post('/api/auth/reset-password', [AuthController::class, 'resetPassword']);

$router->get('/api/admin/users',          [AdminController::class, 'listUsers']);
$router->post('/api/admin/users/sector',  [AdminController::class, 'updateUserSector']);
