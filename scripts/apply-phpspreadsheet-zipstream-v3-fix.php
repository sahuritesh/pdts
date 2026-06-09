<?php

/**
 * PhpSpreadsheet / ZipStream compatibility (Composer post-autoload-dump).
 * Delegates to App\Support\PhpSpreadsheetExportFix (same logic as runtime Xlsx export).
 */
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

if (class_exists(\App\Support\PhpSpreadsheetExportFix::class)) {
    \App\Support\PhpSpreadsheetExportFix::ensureBeforeXlsxExport();
}

exit(0);
