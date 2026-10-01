<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    public function __construct(public readonly mixed $data, public readonly int $status = 200)
    {
    }

    public function send(): void
    {
        http_response_code($this->status);
        if ($this->status === 204) {
            return;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
