<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name, email, password, setor FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name, string $email, string $passwordHash, string $setor): int
    {
        $stmt = $this->db->prepare('INSERT INTO users (name, email, password, setor) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, $passwordHash, $setor]);
        return (int) $this->db->lastInsertId();
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return (bool) $stmt->fetchColumn();
    }

    public function sectorTaken(string $setor): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE setor = ? LIMIT 1');
        $stmt->execute([$setor]);
        return (bool) $stmt->fetchColumn();
    }

    public function updatePassword(string $email, string $passwordHash): void
    {
        $stmt = $this->db->prepare('UPDATE users SET password = ? WHERE email = ?');
        $stmt->execute([$passwordHash, $email]);
    }
}
