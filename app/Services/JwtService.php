<?php

namespace App\Services;

use App\Models\User;
use RuntimeException;

class JwtService
{
    public function create(User $user): string
    {
        $now = time();

        return $this->encode([
            'iss' => config('jwt.issuer'),
            'sub' => (string) $user->getKey(),
            'ver' => $user->jwt_version,
            'iat' => $now,
            'exp' => $now + (config('jwt.ttl') * 60),
        ]);
    }

    public function decode(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid JWT structure.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $header = $this->decodePart($encodedHeader);
        $payload = $this->decodePart($encodedPayload);
        $signature = $this->base64UrlDecode($encodedSignature);
        $expected = hash_hmac('sha256', "{$encodedHeader}.{$encodedPayload}", $this->secret(), true);

        if (($header['typ'] ?? null) !== 'JWT' || ($header['alg'] ?? null) !== 'HS256') {
            throw new RuntimeException('Invalid JWT header.');
        }

        if (! hash_equals($expected, $signature)) {
            throw new RuntimeException('Invalid JWT signature.');
        }

        if (
            ! isset($payload['sub'], $payload['ver'], $payload['iat'], $payload['exp'])
            || ! ctype_digit((string) $payload['sub'])
            || ! is_int($payload['ver'])
            || ! is_int($payload['iat'])
            || ! is_int($payload['exp'])
        ) {
            throw new RuntimeException('Invalid JWT claims.');
        }

        if (($payload['iss'] ?? null) !== config('jwt.issuer') || $payload['exp'] <= time() || $payload['iat'] > time() + 30) {
            throw new RuntimeException('Expired or invalid JWT.');
        }

        return $payload;
    }

    public function cookie(string $token)
    {
        return cookie(
            config('jwt.cookie'),
            $token,
            config('jwt.ttl'),
            '/admin',
            null,
            config('jwt.secure_cookie'),
            true,
            false,
            'strict',
        );
    }

    public function forgetCookie()
    {
        return cookie()->forget(config('jwt.cookie'), '/admin');
    }

    private function encode(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], JSON_THROW_ON_ERROR));
        $body = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', "{$header}.{$body}", $this->secret(), true);

        return "{$header}.{$body}.{$this->base64UrlEncode($signature)}";
    }

    private function decodePart(string $part): array
    {
        $decoded = json_decode($this->base64UrlDecode($part), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new RuntimeException('Invalid JWT content.');
        }

        return $decoded;
    }

    private function secret(): string
    {
        $secret = config('jwt.secret');

        if (! is_string($secret) || strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET must contain at least 32 characters.');
        }

        return $secret;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $value = strtr($value, '-_', '+/');
        $value .= str_repeat('=', (4 - strlen($value) % 4) % 4);
        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            throw new RuntimeException('Invalid base64url content.');
        }

        return $decoded;
    }
}
