<?php

namespace App\Traits;

trait SendJSON
{
    public function sendJSON(null|string|array $data, int $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        exit(json_encode(is_string($data) ? ['message' => $data] : $data, JSON_PRETTY_PRINT));
    }

    public function sendErrorMessage(string $message, int $statusCode = 500, array $data = [])
    {
        $data = array_merge(
            ['error' => $message, 'response_code' => $statusCode],
            $data
        );

        return $this->sendJSON($data, $statusCode);
    }
}
