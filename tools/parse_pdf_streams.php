<?php

$path = __DIR__ . '/frs.pdf';
$data = file_get_contents($path);

// Extract and decompress all FlateDecode streams
preg_match_all('/(\d+) 0 obj\s*<<.*?\/Filter\s*\/FlateDecode.*?stream\r?\n(.*?)\r?\nendstream/s', $data, $matches, PREG_SET_ORDER);

$textParts = [];
foreach ($matches as $match) {
    $objNum = $match[1];
    $stream = $match[2];
    $decoded = @gzuncompress($stream);
    if ($decoded === false) {
        $decoded = @gzdecode($stream);
    }
    if ($decoded === false) {
        continue;
    }

    // BT ... ET text blocks
    if (preg_match_all('/\((?:\\\\.|[^\\\\\)])*\)\s*(?:Tj|TJ)/s', $decoded, $tj)) {
        foreach ($tj[0] as $block) {
            if (preg_match_all('/\((?:\\\\.|[^\\\\\)])*\)/', $block, $parts)) {
                $line = '';
                foreach ($parts[0] as $p) {
                    $line .= stripcslashes(trim($p, '()'));
                }
                if (strlen($line) > 2 && preg_match('/[A-Za-z]{4,}/', $line)) {
                    $textParts[] = $line;
                }
            }
        }
    }

    // TJ array form
    if (preg_match_all('/\[(.*?)\]\s*TJ/s', $decoded, $arrays)) {
        foreach ($arrays[1] as $arr) {
            if (preg_match_all('/\((?:\\\\.|[^\\\\\)])*\)/', $arr, $parts)) {
                $line = '';
                foreach ($parts[0] as $p) {
                    $line .= stripcslashes(trim($p, '()'));
                }
                if (strlen($line) > 2) {
                    $textParts[] = $line;
                }
            }
        }
    }

    // Readable ASCII runs in decoded stream
    if (preg_match_all('/[\x20-\x7E]{20,}/', $decoded, $ascii)) {
        foreach ($ascii[0] as $run) {
            if (preg_match('/[A-Za-z]{5,}/', $run)) {
                $textParts[] = trim($run);
            }
        }
    }
}

$textParts = array_values(array_unique($textParts));
echo "OBJECTS: " . count($matches) . "\n";
echo "LINES: " . count($textParts) . "\n\n";
echo implode("\n", $textParts);
