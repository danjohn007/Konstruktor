<?php

namespace App\Services;

/**
 * Fotos y comprobantes subidos (tickets, facturas, fotos de avance).
 * Se guardan en app/storage/uploads, que no es accesible por HTTP (app/.htaccess);
 * se sirven solo a usuarios autenticados por /archivos/{tipo}/{id}.
 */
class Archivos
{
    public const TIPOS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];
    private const MAX_BYTES = 10 * 1024 * 1024;

    private static function base(): string
    {
        return dirname(__DIR__, 2) . '/storage/uploads';
    }

    /**
     * Valida y guarda el archivo del campo "archivo" de un multipart.
     * Devuelve [ruta relativa, null] o [null, mensaje de error].
     */
    public static function guardar(string $carpeta): array
    {
        $f = $_FILES['archivo'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [null, 'No se recibió ningún archivo'];
        }
        if (in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $f['size'] > self::MAX_BYTES) {
            return [null, 'El archivo es demasiado grande (máximo 10 MB; revisa upload_max_filesize en el hosting)'];
        }
        if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            return [null, 'No se pudo recibir el archivo'];
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!isset(self::TIPOS[$mime])) {
            return [null, 'Solo se aceptan fotos (JPG, PNG, WEBP) o PDF'];
        }

        $relativa = $carpeta . '/' . date('Y/m') . '/' . bin2hex(random_bytes(16)) . '.' . self::TIPOS[$mime];
        $destino = self::base() . '/' . $relativa;
        if (!is_dir(dirname($destino)) && !mkdir(dirname($destino), 0775, true)) {
            return [null, 'No se pudo crear la carpeta de archivos en el servidor'];
        }
        if (!move_uploaded_file($f['tmp_name'], $destino)) {
            return [null, 'No se pudo guardar el archivo en el servidor'];
        }

        return [$relativa, null];
    }

    public static function ruta(?string $relativa): ?string
    {
        if (!$relativa || str_contains($relativa, '..')) {
            return null;
        }
        $ruta = self::base() . '/' . $relativa;

        return is_file($ruta) ? $ruta : null;
    }

    public static function borrar(?string $relativa): void
    {
        $ruta = self::ruta($relativa);
        if ($ruta) {
            @unlink($ruta);
        }
    }

    /** Envía el archivo al navegador o a la app. */
    public static function enviar(string $ruta): void
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($ruta);
        header('Content-Type: ' . (isset(self::TIPOS[$mime]) ? $mime : 'application/octet-stream'));
        header('Content-Length: ' . filesize($ruta));
        header('Content-Disposition: inline; filename="' . basename($ruta) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=86400');
        readfile($ruta);
    }
}
