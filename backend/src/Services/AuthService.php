<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PasswordReset;
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

    private const EXCLUSIVE = [
        'ADMINISTRAÇÃO',
        'DESENVOLVEDOR',
    ];

    private User          $user;
    private PasswordReset $passwordReset;

    public function __construct()
    {
        $this->user          = new User();
        $this->passwordReset = new PasswordReset();
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

    public function forgotPassword(string $email): array
    {
        // Sempre retorna ok — não revela se o e-mail existe ou não
        if (!$this->user->emailExists($email)) {
            return ['ok' => true];
        }

        $this->passwordReset->deleteByEmail($email);

        $token     = bin2hex(random_bytes(32));
        $hashed    = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        $this->passwordReset->create($email, $hashed, $expiresAt);

        $appUrl   = rtrim($_ENV['APP_URL'] ?? '', '/');
        $resetUrl = $appUrl . '/reset-password.html?token=' . $token;

        MailService::send(
            $email,
            'Redefinição de senha — Sistema MX',
            $this->resetEmailTemplate($resetUrl)
        );

        return ['ok' => true];
    }

    public function resetPassword(string $token, string $newPassword): array
    {
        $hashed = hash('sha256', $token);
        $reset  = $this->passwordReset->findValidToken($hashed);

        if ($reset === null) {
            return ['ok' => false, 'message' => 'Link inválido ou expirado.'];
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->user->updatePassword($reset['email'], $hash);
        $this->passwordReset->deleteByEmail($reset['email']);

        return ['ok' => true];
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

    private function resetEmailTemplate(string $url): string
    {
        return "
        <div style='font-family:system-ui,sans-serif;max-width:400px;margin:0 auto;padding:32px'>
            <h2 style='font-size:17px;font-weight:600;margin:0 0 16px;color:#111'>Redefinição de senha</h2>
            <p style='color:#444;font-size:14px;line-height:1.6;margin:0 0 24px'>
                Recebemos uma solicitação para redefinir a senha da sua conta.<br>
                Clique no botão abaixo. O link expira em <strong>1 hora</strong>.
            </p>
            <a href='{$url}'
               style='display:inline-block;background:#111;color:#fff;text-decoration:none;
                      padding:11px 22px;border-radius:6px;font-size:14px;font-weight:600'>
                Redefinir senha
            </a>
            <p style='color:#999;font-size:12px;margin:24px 0 0;line-height:1.5'>
                Se você não solicitou isso, ignore este e-mail.<br>
                Sua senha não será alterada.
            </p>
        </div>";
    }
}
