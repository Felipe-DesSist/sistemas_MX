<?php

use App\Controllers\AuthController;

$router->get('/api/auth/sectors',  [AuthController::class, 'sectors']);
$router->post('/api/auth/login',   [AuthController::class, 'login']);
$router->post('/api/auth/register',[AuthController::class, 'register']);
