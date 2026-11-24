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

// Защита от CSRF: требуем кастомный заголовок, который обычная HTML-форма
// (и злоумышленник с другого сайта) отправить не сможет без CORS-preflight.
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
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