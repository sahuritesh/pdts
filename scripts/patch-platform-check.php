<?php

/**
 * Relaxes Composer platform_check.php PHP constraint for local XAMPP (8.2 vs 8.3).
 * Invoked from composer.json post-update-cmd — avoids fragile inline @php -r on Windows.
 */
$path = dirname(__DIR__) . '/vendor/composer/platform_check.php';
if (!is_file($path)) {
    exit(0);
}

$content = file_get_contents($path);
if ($content === false) {
    exit(0);
}

$patched = preg_replace('/>= 8\\.3\\.0/', '>= 8.2.0', $content);
if ($patched !== null && $patched !== $content) {
    file_put_contents($path, $patched);
}

exit(0);
