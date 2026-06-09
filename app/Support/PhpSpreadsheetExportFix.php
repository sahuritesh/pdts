<?php

namespace App\Support;

/**
 * Harden PhpSpreadsheet Xlsx export on hosts where Composer post-scripts did not run:
 * - Load myclabs/php-enum when the package exists but autoload missed it.
 * - Patch PhpSpreadsheet ZipStream0 for ZipStream v3 when vendor files are writable.
 */
class PhpSpreadsheetExportFix
{
    public static function ensureBeforeXlsxExport(): void
    {
        self::loadMyClabsPhpEnumIfPresent();
        self::patchZipStream0IfWritable();
    }

    private static function projectRoot(): string
    {
        if (function_exists('base_path')) {
            return (string) base_path();
        }

        return dirname(__DIR__, 2);
    }

    private static function loadMyClabsPhpEnumIfPresent(): void
    {
        if (class_exists(\MyCLabs\Enum\Enum::class)) {
            return;
        }
        $path = self::projectRoot() . '/vendor/myclabs/php-enum/src/Enum.php';
        if (is_readable($path)) {
            require_once $path;
        }
    }

    private static function patchZipStream0IfWritable(): void
    {
        $path = self::projectRoot() . '/vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Writer/ZipStream0.php';
        if (!is_file($path) || !is_writable($path)) {
            return;
        }
        $content = str_replace("\r\n", "\n", (string) file_get_contents($path));
        if (strpos($content, 'class_exists(\\ZipStream\\OperationMode::class)') !== false) {
            return;
        }

        $find = <<<'SNIP'
    public static function newZipStream($fileHandle): ZipStream
    {
        return class_exists(Archive::class) ? ZipStream2::newZipStream($fileHandle) : ZipStream3::newZipStream($fileHandle);
    }
SNIP;
        $find = str_replace("\r\n", "\n", $find);
        if (strpos($content, $find) === false) {
            return;
        }

        $replace = <<<'SNIP'
    public static function newZipStream($fileHandle): ZipStream
    {
        if (class_exists(\ZipStream\OperationMode::class)) {
            return ZipStream3::newZipStream($fileHandle);
        }

        return class_exists(Archive::class) ? ZipStream2::newZipStream($fileHandle) : ZipStream3::newZipStream($fileHandle);
    }
SNIP;

        @file_put_contents($path, str_replace($find, str_replace("\r\n", "\n", $replace), $content));
    }
}
