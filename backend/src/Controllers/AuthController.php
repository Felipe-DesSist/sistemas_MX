<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

class AuthController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function sectors(Request $_request): void
    {
        Response::json($this->auth->getAvailableSectors());
    }

    public function login(Request $request): void
    {
        $body  = $request->body();
        $email = trim($body['email'] ?? '');
        $pass  = $body['password'] ?? '';

        if (!$email || !$pass) {
            Response::json(['error' => 'E-mail e senha são obrigatórios.'], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json(['error' => 'E-mail inválido.'], 422);
        }

        $result = $this->auth->login($email, $pass);

        if (!$result['ok']) {
            Response::json(['error' => $result['message']], 401);
        }

        Response::json($result);
    }

    public function register(Request $request): void
    {
        $body  = $request->body();
        $name  = trim($body['name'] ?? '');
        $email = trim($body['email'] ?? '');
        $pass  = $body['password'] ?? '';
        $setor = trim($body['setor'] ?? '');

        if (!$name || !$email || !$pass || !$setor) {
            Response::json(['error' => 'Todos os campos são obrigatórios.'], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json(['error' => 'E-mail inválido.'], 422);
        }

        if (strlen($pass) < 8) {
            Response::json(['error' => 'Senha deve ter no mínimo 8 caracteres.'], 422);
        }

        $result = $this->auth->register($name, $email, $pass, $setor);

        if (!$result['ok']) {
            $status = str_contains($result['message'], 'E-mail') ? 409 : 422;
            Response::json(['error' => $result['message']], $status);
        }

        Response::json($result, 201);
    }

    public function forgotPassword(Request $request): void
    {
        $email = trim($request->body()['email'] ?? '');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json(['error' => 'E-mail inválido.'], 422);
        }

        $this->auth->forgotPassword($email);

        // Sempre retorna 200 — não revela se o e-mail existe
        Response::json(['ok' => true]);
    }

    public function resetPassword(Request $request): void
    {
        $body     = $request->body();
        $token    = trim($body['token'] ?? '');
        $password = $body['password'] ?? '';

        if (!$token || !$password) {
            Response::json(['error' => 'Dados inválidos.'], 422);
        }

        if (strlen($password) < 8) {
            Response::json(['error' => 'Senha deve ter no mínimo 8 caracteres.'], 422);
        }

        $result = $this->auth->resetPassword($token, $password);

        if (!$result['ok']) {
            Response::json(['error' => $result['message']], 400);
        }

        Response::json(['ok' => true]);
    }
}
