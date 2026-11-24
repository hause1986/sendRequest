<?php

/**
 * Публичный эндпоинт для отправки HTTP-запросов.
 * Принимает POST с полями: method, url, params[][key|value], headers[][key|value], raw_headers
 * Возвращает JSON: { ok: true, request: {...}, response: {...} }
 * либо { ok: false, error: '...' }
 */

declare(strict_types=1);

require_once __DIR__ . '/../main/config.cfg';
require_once __DIR__ . '/../main/autoload.php';

// Разрешаем только POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    JsonResponse::error('Метод не разрешён. Используйте POST.', 405);
}

// Проверяем, что запрос пришёл с нашего же сайта (защита от CSRF)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$host   = $_SERVER['HTTP_HOST']   ?? '';
if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== $host) {
    JsonResponse::error('Cross-origin запросы запрещены.', 403);
}

try {
    $controller = new RequestController();
    $result = $controller->handle($_POST);

    JsonResponse::success([
        'request'  => $result['request'],
        'response' => $result['response'],
    ]);
} catch (InvalidArgumentException $e) {
    JsonResponse::error($e->getMessage(), 400);
} catch (RuntimeException $e) {
    JsonResponse::error(
        Config::isDebug() ? $e->getMessage() : 'Ошибка сети при отправке запроса.',
        502
    );
} catch (Throwable $e) {
    JsonResponse::error(
        Config::isDebug() ? $e->getMessage() : 'Внутренняя ошибка сервера.',
        500
    );
}