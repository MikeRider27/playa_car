<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    public ?array $user = null;

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly ?string $token,
    ) {
    }

    public static function fromGlobals(): self
    {
        $body = json_decode(file_get_contents('php://input') ?: '[]', true);
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        $token = preg_match('/^Bearer\s+(\S+)$/i', $auth, $m) ? $m[1] : null;

        return new self(
            $_SERVER['REQUEST_METHOD'],
            rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/', '/') ?: '/',
            $_GET,
            is_array($body) ? $body : [],
            $token,
        );
    }

    public function query(string $key, mixed $default = null): mixed
    {
        $value = $this->query[$key] ?? null;
        return ($value === null || $value === '') ? $default : $value;
    }

    public function isAdmin(): bool
    {
        return ($this->user['rol'] ?? null) === 'admin';
    }
}
