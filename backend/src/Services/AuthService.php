<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class AuthService
{
    private const SECTORS = [
        'ARTES',
        'PRODUÇÃO',
        'EXPEDIÇÃO',
        'ADMINISTRAÇÃO',
        'DESENVOLVEDOR',
    ];

    // Setores que só podem ter um único usuário cadastrado
    private const EXCLUSIVE = [
        'ADMINISTRAÇÃO',
        'DESENVOLVEDOR',
    ];

    private User $user;

    public function __construct()
    {
        $this->user = new User();
    }

    public function getAvailableSectors(): array
    {
        $available = [];

        foreach (self::SECTORS as $sector) {
            if (in_array($sector, self::EXCLUSIVE, true)) {
                if (!$this->user->sectorTaken($sector)) {
                    $available[] = $sector;
                }
            } else {
                $available[] = $sector;
            }
        }

        return ['sectors' => $available];
    }

    public function register(string $name, string $email, string $password, string $setor): array
    {
        if (!in_array($setor, self::SECTORS, true)) {
            return ['ok' => false, 'message' => 'Setor inválido.'];
        }

        // Revalida exclusividade no momento do cadastro (race-condition safe via DB unique check)
        if (in_array($setor, self::EXCLUSIVE, true) && $this->user->sectorTaken($setor)) {
            return ['ok' => false, 'message' => 'Este setor já está preenchido.'];
        }

        if ($this->user->emailExists($email)) {
            return ['ok' => false, 'message' => 'E-mail já cadastrado.'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $id   = $this->user->create($name, $email, $hash, $setor);

        return ['ok' => true, 'user_id' => $id];
    }

    public function login(string $email, string $password): array
    {
        $user = $this->user->findByEmail($email);

        if ($user === null || !password_verify($password, $user['password'])) {
            return ['ok' => false, 'message' => 'Credenciais inválidas.'];
        }

        $token = $this->generateToken($user['id']);

        return [
            'ok'    => true,
            'token' => $token,
            'user'  => [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'setor' => $user['setor'],
            ],
        ];
    }

    private function generateToken(int $userId): string
    {
        $payload = base64_encode(json_encode([
            'uid' => $userId,
            'exp' => time() + 3600,
            'rnd' => bin2hex(random_bytes(8)),
        ]));
        $sig = hash_hmac('sha256', $payload, $_ENV['APP_SECRET']);
        return $payload . '.' . $sig;
    }
}
