<?php

/**
 * Обёртка над cURL. Отправляет HTTP-запрос и нормализует ответ.
 * Никакой логики сборки входных данных — только транспорт.
 */
final class HttpClient
{
    /**
     * @param array<string, string|array<string>> $headers
     * @param string|null $body
     * @return array{
     *     status: int,
     *     status_text: string,
     *     headers: array<string, string>,
     *     body: string,
     *     time_ms: int,
     *     size: int
     * }
     * @throws RuntimeException
     */
    public static function send(
        string $method,
        string $url,
        array $headers = [],
        ?string $body = null
    ): array {
        $ch = curl_init();

        $responseHeaders = [];

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => (int) Config::get('HTTP_TIMEOUT', 30),
            CURLOPT_CONNECTTIMEOUT => (int) Config::get('HTTP_CONNECT_TIMEOUT', 10),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_ENCODING       => '',
            CURLOPT_USERAGENT      => 'RequestTester/1.0',
            CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$responseHeaders): int {
                $len = strlen($line);
                $trimmed = trim($line);
                if ($trimmed !== '' && str_contains($trimmed, ':')) {
                    [$name, $value] = explode(':', $trimmed, 2);
                    $responseHeaders[strtolower(trim($name))] = trim($value);
                }
                return $len;
            },
        ]);

        if ($body !== null && $body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        // Приводим заголовки к формату "Name: value"
        $curlHeaders = [];
        foreach ($headers as $name => $value) {
            $values = is_array($value) ? $value : [$value];
            foreach ($values as $v) {
                $curlHeaders[] = $name . ': ' . $v;
            }
        }
        // Явно отключаем Expect: 100-continue (частая проблема при POST)
        $curlHeaders[] = 'Expect:';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

        $start = microtime(true);
        $responseBody = curl_exec($ch);
        $elapsed = (int) round((microtime(true) - $start) * 1000);

        if ($responseBody === false) {
            $err = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new RuntimeException("cURL error [$errno]: $err");
        }

        $status     = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $size       = (int) curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        $statusText = self::statusText($status);

        curl_close($ch);

        return [
            'status'      => $status,
            'status_text' => $statusText,
            'headers'     => $responseHeaders,
            'body'        => (string) $responseBody,
            'time_ms'     => $elapsed,
            'size'        => $size,
        ];
    }

    private static function statusText(int $code): string
    {
        $map = [
            200 => 'OK', 201 => 'Created', 202 => 'Accepted', 204 => 'No Content',
            301 => 'Moved Permanently', 302 => 'Found', 304 => 'Not Modified',
            400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden',
            404 => 'Not Found', 405 => 'Method Not Allowed', 409 => 'Conflict',
            422 => 'Unprocessable Entity', 429 => 'Too Many Requests',
            500 => 'Internal Server Error', 502 => 'Bad Gateway',
            503 => 'Service Unavailable', 504 => 'Gateway Timeout',
        ];
        return $map[$code] ?? '';
    }
}