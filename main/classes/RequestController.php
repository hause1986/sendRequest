<?php

/**
 * Оркестратор: принимает входные данные, валидирует, отправляет через cURL,
 * возвращает нормализованный результат.
 */
final class RequestController
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function handle(array $input): array
    {
        $prepared = RequestBuilder::buildFromInput($input);

        $response = HttpClient::send(
            $prepared['method'],
            $prepared['url'],
            $prepared['headers'],
            $prepared['body']
        );

        return [
            'request' => [
                'method'  => $prepared['method'],
                'url'     => $prepared['url'],
                'headers' => $prepared['headers'],
                'body'    => $prepared['body'],
            ],
            'response' => [
                'status'      => $response['status'],
                'status_text' => $response['status_text'],
                'headers'     => $response['headers'],
                'body'        => $response['body'],
                'time_ms'     => $response['time_ms'],
                'size'        => $response['size'],
            ],
        ];
    }
}