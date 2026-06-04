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

    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT id, name, email, setor, created_at FROM users ORDER BY name ASC'
        );
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, setor FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function sectorTakenByOther(string $setor, int $excludeId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM users WHERE setor = ? AND id != ? LIMIT 1'
        );
        $stmt->execute([$setor, $excludeId]);
        return (bool) $stmt->fetchColumn();
    }

    public function updateSector(int $id, string $setor): void
    {
        $stmt = $this->db->prepare('UPDATE users SET setor = ? WHERE id = ?');
        $stmt->execute([$setor, $id]);
    }
}
