<?php

$path = __DIR__ . '/frs.pdf';
$data = file_get_contents($path);

function decodeStream(string $stream): ?string
{
    $decoded = @gzuncompress($stream);
    if ($decoded === false) {
        $decoded = @gzdecode($stream);
    }

    return $decoded === false ? null : $decoded;
}

function decodeHexTitle(string $hex): string
{
    $hex = preg_replace('/\s+/', '', $hex);
    if (str_starts_with(strtolower($hex), 'feff')) {
        $hex = substr($hex, 4);
    }
    $out = '';
    for ($i = 0; $i + 3 < strlen($hex); $i += 4) {
        $out .= mb_chr(hexdec(substr($hex, $i, 4)), 'UTF-8');
    }

    return $out;
}

// Outline titles
preg_match_all('/\/Title (\((?:\\\\.|[^\\\\\)])*\)|<([0-9A-Fa-f\s]+)>)/', $data, $titles);
echo "=== DOCUMENT OUTLINE ===\n";
foreach ($titles[0] as $idx => $raw) {
    if (!empty($titles[1][$idx])) {
        echo stripcslashes(trim($titles[1][$idx], '()')) . "\n";
    } elseif (!empty($titles[2][$idx])) {
        echo decodeHexTitle($titles[2][$idx]) . "\n";
    }
}

echo "\n=== PAGE TEXT (best effort) ===\n";
preg_match_all('/\/Contents (\d+) 0 R/', $data, $contentRefs);
$seen = [];
foreach ($contentRefs[1] as $ref) {
    if (isset($seen[$ref])) {
        continue;
    }
    $seen[$ref] = true;
    if (!preg_match('/' . preg_quote($ref, '/') . ' 0 obj\s*<<.*?stream\r?\n(.*?)\r?\nendstream/s', $data, $m)) {
        continue;
    }
    $decoded = decodeStream($m[1]);
    if ($decoded === null) {
        continue;
    }

    $pageText = [];
    if (preg_match_all('/\((?:\\\\.|[^\\\\\)])*\)/', $decoded, $parts)) {
        foreach ($parts[0] as $p) {
            $t = stripcslashes(trim($p, '()'));
            if (strlen($t) > 1 && preg_match('/[\x20-\x7E]/', $t)) {
                $pageText[] = $t;
            }
        }
    }
    if (preg_match_all('/\[(.*?)\]\s*TJ/s', $decoded, $arrays)) {
        foreach ($arrays[1] as $arr) {
            if (preg_match_all('/\((?:\\\\.|[^\\\\\)])*\)/', $arr, $parts)) {
                $line = '';
                foreach ($parts[0] as $p) {
                    $line .= stripcslashes(trim($p, '()'));
                }
                if ($line !== '') {
                    $pageText[] = $line;
                }
            }
        }
    }

    if ($pageText !== []) {
        echo "\n--- content obj {$ref} ---\n";
        echo implode("\n", array_unique($pageText));
    }
}
