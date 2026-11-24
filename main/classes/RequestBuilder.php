<?php

/**
 * Отвечает за валидацию и нормализацию входных данных формы.
 * Не отправляет запросы — только готовит данные.
 */
final class RequestBuilder
{
    /**
     * @param array<string, mixed> $input — $_POST
     * @return array{
     *     method: string,
     *     url: string,
     *     params: array<string, string|array<string>>,
     *     headers: array<string, string|array<string>>,
     *     body: string|null
     * }
     * @throws InvalidArgumentException
     */
    public static function buildFromInput(array $input): array
    {
        $method = self::normalizeMethod((string)($input['method'] ?? 'GET'));

        $url = trim((string)($input['url'] ?? ''));
        if ($url === '') {
            throw new InvalidArgumentException('URL обязателен.');
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Некорректный URL.');
        }
        if (!preg_match('#^https?://#i', $url)) {
            throw new InvalidArgumentException('Разрешены только http и https.');
        }

        $params  = self::normalizeKv($input['params']  ?? []);
        $headers = self::normalizeKv($input['headers'] ?? []);

        // Сырые заголовки (построчно "Name: value")
        $rawHeaders = (string)($input['raw_headers'] ?? '');
        if ($rawHeaders !== '') {
            $parsed = self::parseRawHeaders($rawHeaders);
            $headers = self::mergeKv($headers, $parsed);
        }

        // Собираем итоговый URL + тело
        $finalUrl = $url;
        $body = null;

        if (in_array($method, ['GET', 'DELETE', 'HEAD'], true)) {
            $finalUrl = self::appendQuery($url, $params);
        } else {
            // POST / PUT / PATCH — тело JSON
            $body = json_encode(
                self::flattenParams($params),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            if (!self::hasHeader($headers, 'Content-Type')) {
                $headers['Content-Type'] = 'application/json';
            }
            if (!self::hasHeader($headers, 'Accept')) {
                $headers['Accept'] = 'application/json, */*;q=0.5';
            }
        }

        return [
            'method'  => $method,
            'url'     => $finalUrl,
            'params'  => $params,
            'headers' => $headers,
            'body'    => $body,
        ];
    }

    private static function normalizeMethod(string $m): string
    {
        $m = strtoupper(trim($m));
        $allowed = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD'];
        return in_array($m, $allowed, true) ? $m : 'GET';
    }

    /**
     * @param mixed $raw — ожидается массив массивов [['key' => ..., 'value' => ...], ...]
     * @return array<string, string|array<string>>
     */
    private static function normalizeKv(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $result = [];
        $count = 0;
        foreach ($raw as $pair) {
            if (!is_array($pair)) {
                continue;
            }
            $key   = trim((string)($pair['key']   ?? ''));
            $value = (string)($pair['value'] ?? '');

            if ($key === '') {
                continue;
            }
            if (mb_strlen($key) > (int)Config::get('KEY_MAX_LENGTH', 200)) {
                throw new InvalidArgumentException("Слишком длинный ключ: {$key}");
            }
            if (mb_strlen($value) > (int)Config::get('VALUE_MAX_LENGTH', 10000)) {
                throw new InvalidArgumentException("Слишком длинное значение для ключа {$key}");
            }
            if (++$count > (int)Config::get('PARAMS_MAX_COUNT', 100)) {
                throw new InvalidArgumentException('Слишком много параметров.');
            }

            if (array_key_exists($key, $result)) {
                $existing = $result[$key];
                $result[$key] = is_array($existing)
                    ? [...$existing, $value]
                    : [$existing, $value];
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * @return array<string, string|array<string>>
     */
    private static function parseRawHeaders(string $raw): array
    {
        $headers = [];
        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if ($line === '' || !str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $name  = trim($name);
            $value = trim($value);
            if ($name === '') {
                continue;
            }
            $headers[$name] = $value;
        }
        return $headers;
    }

    /**
     * @param array<string, string|array<string>> $a
     * @param array<string, string|array<string>> $b
     * @return array<string, string|array<string>>
     */
    private static function mergeKv(array $a, array $b): array
    {
        foreach ($b as $k => $v) {
            if (array_key_exists($k, $a)) {
                $a[$k] = is_array($a[$k]) ? [...$a[$k], $v] : [$a[$k], $v];
            } else {
                $a[$k] = $v;
            }
        }
        return $a;
    }

    /**
     * @param array<string, string|array<string>> $kv
     * @return array<string, mixed>
     */
    private static function flattenParams(array $kv): array
    {
        // Для JSON-тела оставляем как есть (если значение — массив, оставляем массивом)
        return $kv;
    }

    private static function hasHeader(array $headers, string $name): bool
    {
        foreach ($headers as $key => $_) {
            if (strcasecmp($key, $name) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<string, string|array<string>> $params
     */
    private static function appendQuery(string $url, array $params): string
    {
        if ($params === []) {
            return $url;
        }
        $parts = [];
        foreach ($params as $key => $value) {
            $values = is_array($value) ? $value : [$value];
            foreach ($values as $v) {
                $parts[] = rawurlencode($key) . '=' . rawurlencode($v);
            }
        }
        $sep = str_contains($url, '?') ? '&' : '?';
        return $url . $sep . implode('&', $parts);
    }
}