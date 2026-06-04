<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Services\AdminService;

class AdminController
{
    private AdminService $service;

    public function __construct()
    {
        $this->service = new AdminService();
    }

    public function listUsers(Request $request): void
    {
        $this->authorize($request);
        Response::json(['users' => $this->service->getAllUsers()]);
    }

    public function updateUserSector(Request $request): void
    {
        $this->authorize($request);

        $body   = $request->body();
        $userId = (int) ($body['user_id'] ?? 0);
        $setor  = trim($body['setor'] ?? '');

        if (!$userId || !$setor) {
            Response::json(['error' => 'Dados inválidos.'], 422);
        }

        $result = $this->service->updateUserSector($userId, $setor);

        if (!$result['ok']) {
            Response::json(['error' => $result['message']], 422);
        }

        Response::json(['ok' => true]);
    }

    private function authorize(Request $request): void
    {
        $token = $request->bearerToken();

        if (!$token) {
            Response::json(['error' => 'Não autorizado.'], 401);
        }

        $uid = $this->decodeToken($token);

        if ($uid === null) {
            Response::json(['error' => 'Token inválido ou expirado.'], 401);
        }

        $user = (new User())->findById($uid);

        if ($user === null || !in_array($user['setor'], ['ADMINISTRAÇÃO', 'DESENVOLVEDOR'], true)) {
            Response::json(['error' => 'Acesso negado.'], 403);
        }
    }

    private function decodeToken(string $token): ?int
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) return null;

        [$payload, $sig] = $parts;
        $expected = hash_hmac('sha256', $payload, $_ENV['APP_SECRET']);

        if (!hash_equals($expected, $sig)) return null;

        $data = json_decode(base64_decode($payload), true);

        if (!isset($data['uid'], $data['exp']) || $data['exp'] < time()) return null;

        return (int) $data['uid'];
    }
}
