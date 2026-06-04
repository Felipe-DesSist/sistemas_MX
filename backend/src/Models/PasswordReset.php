<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class PasswordReset
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(string $email, string $tokenHash, string $expiresAt): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)'
        );
        $stmt->execute([$email, $tokenHash, $expiresAt]);
    }

    public function findValidToken(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        return $stmt->fetch() ?: null;
    }

    public function deleteByEmail(string $email): void
    {
        $stmt = $this->db->prepare('DELETE FROM password_resets WHERE email = ?');
        $stmt->execute([$email]);
    }
}
