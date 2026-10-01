<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\HttpException;
use PDO;

abstract class Controller
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function execute(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    protected function findOrFail(string $table, int $id, string $label = 'Registro'): array
    {
        return $this->fetchOne("SELECT * FROM $table WHERE id = ?", [$id])
            ?? throw new HttpException(404, "$label no encontrado.");
    }

    protected function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn ($c) => ":$c", array_keys($data)));
        $stmt = $this->db->prepare("INSERT INTO $table ($columns) VALUES ($placeholders) RETURNING id");
        $stmt->execute($data);
        return (int) $stmt->fetchColumn();
    }

    protected function updateRow(string $table, int $id, array $data): void
    {
        $sets = implode(', ', array_map(fn ($c) => "$c = :$c", array_keys($data)));
        $this->execute("UPDATE $table SET $sets WHERE id = :id", [...$data, 'id' => $id]);
    }
}
