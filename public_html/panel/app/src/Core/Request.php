<?php

namespace App\Core;

class Request
{
    /**
     * Cuerpo JSON de la petición. Las escrituras solo se aceptan como
     * application/json: un formulario de otro sitio no puede enviar ese
     * tipo sin preflight CORS, así que esto también frena CSRF.
     */
    public static function json(): array
    {
        $type = $_SERVER['CONTENT_TYPE'] ?? '';
        if (!str_starts_with(strtolower($type), 'application/json')) {
            Response::json(['error' => 'Se esperaba application/json'], 415);
            exit;
        }

        $raw = file_get_contents('php://input');
        if (trim($raw) === '') {
            return [];
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            Response::json(['error' => 'JSON inválido (revisa que el texto esté en UTF-8)'], 400);
            exit;
        }

        return $data;
    }

    /** Convierte '', null o texto no numérico en null; lo demás en float. */
    public static function num(mixed $value): ?float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    public static function str(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    /** UUID que genera la app para cada registro (evita duplicados al reintentar); null si no es válido. */
    public static function uuid(mixed $value): ?string
    {
        $value = strtolower(trim((string) ($value ?? '')));
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $value) ? $value : null;
    }
}
