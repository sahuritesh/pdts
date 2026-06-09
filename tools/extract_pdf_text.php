<?php

$path = dirname(__DIR__) . '/tools/frs.pdf';
if (!is_file($path)) {
    $dir = dirname(__DIR__) . '/documentation';
    foreach (glob($dir . '/*.pdf') ?: [] as $candidate) {
        $path = $candidate;
        break;
    }
}

if (!is_file($path)) {
    fwrite(STDERR, "PDF not found\n");
    exit(1);
}

$data = file_get_contents($path);
echo "FILE: {$path}\n";
echo "SIZE: " . strlen($data) . "\n\n";

$lines = [];

if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $data, $streams)) {
    foreach ($streams[1] as $stream) {
        $decoded = @gzuncompress($stream);
        if ($decoded === false) {
            $decoded = @gzdecode($stream);
        }
        if ($decoded === false) {
            $decoded = $stream;
        }

        extractPdfStrings($decoded, $lines);
    }
}

extractPdfStrings($data, $lines);

$lines = array_values(array_unique(array_filter($lines, static function ($line) {
    $line = trim($line);
    if ($line === '') {
        return false;
    }
    return (bool) preg_match('/[A-Za-z]{3,}/', $line);
})));

echo implode("\n", $lines);

function extractPdfStrings(string $decoded, array &$lines): void
{
    if (preg_match_all('/\((?:\\\\.|[^\\\\\)])*\)/s', $decoded, $m)) {
        foreach ($m[0] as $raw) {
            $t = pdfUnescape(trim($raw, '()'));
            if ($t !== '') {
                $lines[] = $t;
            }
        }
    }

    if (preg_match_all('/\[(.*?)\]/s', $decoded, $m2)) {
        foreach ($m2[1] as $block) {
            if (preg_match_all('/\((?:\\\\.|[^\\\\\)])*\)/s', $block, $parts)) {
                $line = '';
                foreach ($parts[0] as $raw) {
                    $line .= pdfUnescape(trim($raw, '()'));
                }
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }
    }

    if (preg_match_all('/<([0-9A-Fa-f\s]+)>/', $decoded, $hexMatches)) {
        foreach ($hexMatches[1] as $hex) {
            $hex = preg_replace('/\s+/', '', $hex);
            if ($hex === '' || strlen($hex) % 2 !== 0) {
                continue;
            }
            $bytes = hex2bin($hex);
            if ($bytes !== false && preg_match('/[A-Za-z]{3,}/', $bytes)) {
                $lines[] = $bytes;
            }
        }
    }
}

function pdfUnescape(string $value): string
{
    $value = preg_replace_callback('/\\\\([0-7]{1,3}|n|r|t|b|f|\(|\\)|\\\\)/', static function ($m) {
        switch ($m[1]) {
            case 'n': return "\n";
            case 'r': return "\r";
            case 't': return "\t";
            case 'b': return "\x08";
            case 'f': return "\x0C";
            case '(': return '(';
            case ')': return ')';
            case '\\': return '\\';
            default:
                return chr(octdec($m[1]));
        }
    }, $value);

    return trim($value);
}
