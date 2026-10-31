<?php

namespace App\Core;

class Response
{
    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public static function view(string $template, array $data = [], bool $withLayout = true): void
    {
        extract($data);
        $contentFile = __DIR__ . '/../Views/' . $template . '.php';
        if ($withLayout) {
            require __DIR__ . '/../Views/layout.php';
        } else {
            require $contentFile;
        }
    }
}
