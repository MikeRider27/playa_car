<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpException;

/** Implementación mínima de JWT HS256. */
final class Jwt
{
    public static function encode(array $payload, string $secret): string
    {
        $segments = [
            self::b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])),
            self::b64(json_encode($payload)),
        ];
        $segments[] = self::b64(hash_hmac('sha256', implode('.', $segments), $secret, true));
        return implode('.', $segments);
    }

    public static function decode(string $token, string $secret): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new HttpException(401, 'Token inválido.');
        }
        [$header, $payload, $signature] = $parts;
        $expected = self::b64(hash_hmac('sha256', "$header.$payload", $secret, true));
        if (!hash_equals($expected, $signature)) {
            throw new HttpException(401, 'Token inválido.');
        }
        $data = json_decode(self::unb64($payload), true);
        if (!is_array($data) || ($data['exp'] ?? 0) < time()) {
            throw new HttpException(401, 'La sesión expiró, ingresá nuevamente.');
        }
        return $data;
    }

    public static function secret(): string
    {
        return getenv('JWT_SECRET') ?: 'cambiar-este-secreto';
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function unb64(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'));
    }
}
