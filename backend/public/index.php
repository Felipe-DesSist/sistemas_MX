<?php

declare(strict_types=1);

// Aponta para a pasta raiz do backend (fora de public_html/api/)
// No HostGator ficará em: /home/USUARIO/app
define('ROOT', dirname(__DIR__));

require ROOT . '/vendor/autoload.php';

// Carrega o .env
$dotenv = Dotenv\Dotenv::createImmutable(ROOT);
$dotenv->load();

$dotenv->required(['APP_SECRET', 'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);

use App\Core\Router;
use App\Core\Request;

// CORS — ajuste o domínio em produção
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$router = new Router();

require ROOT . '/routes/api.php';

$router->dispatch(new Request());
