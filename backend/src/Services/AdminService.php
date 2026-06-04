<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class AdminService
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

    private User $user;

    public function __construct()
    {
        $this->user = new User();
    }

    public function getAllUsers(): array
    {
        return $this->user->findAll();
    }

    public function updateUserSector(int $userId, string $setor): array
    {
        if (!in_array($setor, self::SECTORS, true)) {
            return ['ok' => false, 'message' => 'Setor inválido.'];
        }

        if (in_array($setor, self::EXCLUSIVE, true)) {
            if ($this->user->sectorTakenByOther($setor, $userId)) {
                return ['ok' => false, 'message' => 'Setor já ocupado por outro usuário.'];
            }
        }

        $this->user->updateSector($userId, $setor);

        return ['ok' => true];
    }
}
