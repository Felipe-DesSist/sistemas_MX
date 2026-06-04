<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class AuthMiddleware
{
    public static function handle(Request $request): void
    {
        $token = $request->bearerToken();

        if (!$token || !self::verify($token)) {
            Response::json(['error' => 'Unauthorized.'], 401);
        }
    }

    private static function verify(string $token): bool
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) return false;

        [$payload, $sig] = $parts;
        $expected = hash_hmac('sha256', $payload, $_ENV['APP_SECRET']);

        if (!hash_equals($expected, $sig)) return false;

        $data = json_decode(base64_decode($payload), true);
        return isset($data['exp']) && $data['exp'] > time();
    }
}
