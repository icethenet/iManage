<?php
/**
 * Centralized path helper to build filesystem and URL paths for uploads.
 */
class Path {
    public static function projectRoot(): string {
        return dirname(dirname(__DIR__));
    }

    public static function config(): array {
        $appConfigPath = self::projectRoot() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';
        return require $appConfigPath;
    }

    public static function uploadsBaseFs(): string {
        $cfg = self::config();
        return self::projectRoot() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim($cfg['upload_dir'], '/\\');
    }

    public static function buildUserFsPath(string $pathSegment, string $subdir = ''): string {
        $base = self::uploadsBaseFs();
        $p = $base . DIRECTORY_SEPARATOR . $pathSegment;
        if ($subdir !== '') $p .= DIRECTORY_SEPARATOR . $subdir;
        return $p;
    }

    public static function buildUrlForFile(string $username, ?string $folder, string $subdir, string $filename): string {
        $cfg = self::config();
        $uploadDir = rtrim($cfg['upload_dir'], '/');
        $encodedFolder = ($folder && $folder !== 'default') ? rawurlencode($folder) : null;
        $segment = $username . ($encodedFolder ? '/' . $encodedFolder : '');
        $base = rtrim($cfg['app_url'] ?? '', '/');
        return $base . $uploadDir . '/' . $segment . '/' . $subdir . '/' . rawurlencode($filename);
    }
}

