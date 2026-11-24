<?php

/**
 * Единый формат JSON-ответа API.
 * Все эндпоинты возвращают одинаковую структуру.
 */
final class JsonResponse
{
    /**
     * @param array<string, mixed> $data
     */
    public static function success(array $data, int $httpCode = 200): void
    {
        self::send(['ok' => true] + $data, $httpCode);
    }

    /**
     * @param array<string, mixed> $extra
     */
    public static function error(string $message, int $httpCode = 400, array $extra = []): void
    {
        self::send(['ok' => false, 'error' => $message] + $extra, $httpCode);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function send(array $payload, int $httpCode): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}