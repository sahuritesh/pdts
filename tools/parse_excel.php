<?php

$xlsx = dirname(__DIR__) . '/documentation/Project_Delay_Framework_Renovation_Enhanced.xlsx';
if (!file_exists($xlsx)) {
    fwrite(STDERR, "File not found: $xlsx\n");
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($xlsx) !== true) {
    fwrite(STDERR, "Cannot open xlsx\n");
    exit(1);
}

$shared = [];
$sharedXml = $zip->getFromName('xl/sharedStrings.xml');
if ($sharedXml !== false) {
    $sroot = simplexml_load_string($sharedXml);
    foreach ($sroot->si as $si) {
        $text = '';
        if (isset($si->t)) {
            $text = (string) $si->t;
        }
        if ($text === '' && isset($si->r)) {
            foreach ($si->r as $r) {
                if (isset($r->t)) {
                    $text .= (string) $r->t;
                }
            }
        }
        $shared[] = $text;
    }
}

$wbXml = $zip->getFromName('xl/workbook.xml');
$relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
$wb = simplexml_load_string($wbXml);
$rels = simplexml_load_string($relsXml);

$ridMap = [];
foreach ($rels->Relationship as $rel) {
    $attrs = $rel->attributes();
    $ridMap[(string) $attrs['Id']] = (string) $attrs['Target'];
}

$ns = $wb->getNamespaces(true);
$rIdNs = $ns['r'] ?? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
$sheetNodes = $wb->sheets->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main');

$out = [];
foreach ($sheetNodes as $sheet) {
    $attrs = $sheet->attributes();
    $rAttrs = $sheet->attributes($rIdNs);
    $name = (string) $attrs['name'];
    $rid = (string) $rAttrs['id'];
    $target = $ridMap[$rid] ?? '';
    if ($target === '') {
        fwrite(STDERR, "No target for sheet: $name (rid=$rid)\n");
        continue;
    }
    $path = (strpos($target, 'xl/') === 0) ? $target : 'xl/' . $target;
    $sheetXml = $zip->getFromName($path);
    if ($sheetXml === false) {
        fwrite(STDERR, "Missing sheet file: $path\n");
        continue;
    }
    $sxml = simplexml_load_string($sheetXml);
    $mainNs = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    $rows = [];
    foreach ($sxml->children($mainNs)->sheetData->children($mainNs) as $row) {
        $r = [];
        foreach ($row->children($mainNs) as $c) {
            $cAttrs = $c->attributes();
            $ref = (string) $cAttrs['r'];
            preg_match('/^([A-Z]+)/', $ref, $m);
            $col = $m[1] ?? $ref;
            $t = (string) $cAttrs['t'];
            $v = isset($c->children($mainNs)->v) ? (string) $c->children($mainNs)->v : '';
            if ($t === 's') {
                $v = $shared[(int) $v] ?? $v;
            }
            $r[$col] = $v;
        }
        if (array_filter($r, static fn($v) => trim((string) $v) !== '')) {
            $rows[] = $r;
        }
    }

    $out[$name] = [
        'count' => count($rows),
        'rows' => $rows,
    ];
}

$zip->close();

$outputFile = __DIR__ . '/excel_dump.json';
file_put_contents($outputFile, json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Written to $outputFile\n";
echo 'Sheets: ' . implode(', ', array_keys($out)) . "\n";
