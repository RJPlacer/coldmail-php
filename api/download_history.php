<?php
require_once __DIR__ . '/../config.php';
require_login(true);

$username = current_username();

// Same filters as the History page UI, applied here so the export
// contains exactly what's currently visible in the table.
$subjectFilter = strtolower(trim($_GET['subject'] ?? ''));
$modeFilter    = $_GET['mode'] ?? '';   // '', 'dry', or 'live'
$statusFilter  = $_GET['status'] ?? ''; // '', 'done', 'stopped', 'sending', 'queued', 'error'
$fromDate      = $_GET['from'] ?? '';   // 'YYYY-MM-DD'
$toDate        = $_GET['to'] ?? '';     // 'YYYY-MM-DD'

$export = [];

foreach (glob(JOBS_DIR . '/*.json') as $file) {
    $job = json_decode(file_get_contents($file), true);
    if (!is_array($job) || ($job['owner'] ?? null) !== $username) {
        continue;
    }

    $subject   = $job['subject'] ?? '';
    $createdAt = $job['created_at'] ?? '';
    $isDry     = !empty($job['dry_run']);
    $status    = $job['status'] ?? 'unknown';

    if ($subjectFilter !== '' && strpos(strtolower($subject), $subjectFilter) === false) {
        continue;
    }
    if ($modeFilter === 'dry' && !$isDry) {
        continue;
    }
    if ($modeFilter === 'live' && $isDry) {
        continue;
    }
    if ($statusFilter !== '' && $status !== $statusFilter) {
        continue;
    }
    if ($createdAt !== '') {
        $day = substr($createdAt, 0, 10);
        if ($fromDate !== '' && $day < $fromDate) {
            continue;
        }
        if ($toDate !== '' && $day > $toDate) {
            continue;
        }
    }

    $export[] = [
        'job_id'     => basename($file, '.json'),
        'subject'    => $subject,
        'created_at' => $createdAt,
        'dry_run'    => $isDry,
        'status'     => $status,
        'total'      => $job['total'] ?? 0,
        'sent'       => $job['sent'] ?? 0,
        'failed'     => $job['failed'] ?? 0,
        // Matches list_history.php's formula exactly, so this number always
        // agrees with what's shown in the History table for the same job.
        'suppressed' => $job['suppressed'] ?? 0,
        'results'    => $job['results'] ?? [],
    ];
}

usort($export, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

if (!class_exists('ZipArchive')) {
    json_response(['error' => 'The PHP zip extension is not enabled on this server, so Word export is unavailable. Ask your host to enable ext-zip.'], 500);
}

// ---- Minimal Office Open WordprocessingML (.docx) builder ----
// No third-party library — .docx is just a zip of a few XML parts, so we
// build those parts by hand and zip them with PHP's built-in ZipArchive.

function docx_xml_escape(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function docx_run(string $text, bool $bold = false, ?int $sizeHalfPoints = null): string {
    $rpr = '';
    if ($bold || $sizeHalfPoints !== null) {
        $rpr .= '<w:rPr>';
        if ($bold) {
            $rpr .= '<w:b/>';
        }
        if ($sizeHalfPoints !== null) {
            $rpr .= '<w:sz w:val="' . $sizeHalfPoints . '"/>';
        }
        $rpr .= '</w:rPr>';
    }
    return '<w:r>' . $rpr . '<w:t xml:space="preserve">' . docx_xml_escape($text) . '</w:t></w:r>';
}

function docx_paragraph(string $text, bool $bold = false, ?int $sizeHalfPoints = null): string {
    return '<w:p>' . docx_run($text, $bold, $sizeHalfPoints) . '</w:p>';
}

function docx_table_cell(string $text, bool $header = false): string {
    $shading = $header ? '<w:shd w:val="clear" w:color="auto" w:fill="EAF5FF"/>' : '';
    return '<w:tc><w:tcPr><w:tcW w:w="0" w:type="auto"/>' . $shading . '</w:tcPr>'
        . '<w:p>' . docx_run($text, $header, $header ? 18 : null) . '</w:p></w:tc>';
}

function docx_table(array $headers, array $rows): string {
    $xml = '<w:tbl><w:tblPr><w:tblStyle w:val="TableGrid"/><w:tblW w:w="0" w:type="auto"/></w:tblPr>';
    $xml .= '<w:tblGrid>' . str_repeat('<w:gridCol/>', count($headers)) . '</w:tblGrid>';

    $xml .= '<w:tr>';
    foreach ($headers as $h) {
        $xml .= docx_table_cell($h, true);
    }
    $xml .= '</w:tr>';

    foreach ($rows as $row) {
        $xml .= '<w:tr>';
        foreach ($row as $cell) {
            $xml .= docx_table_cell((string) $cell, false);
        }
        $xml .= '</w:tr>';
    }

    $xml .= '</w:tbl>';
    return $xml;
}

$body = '';
$body .= docx_paragraph('Dispatch — Campaign History Export', true, 44);
$body .= docx_paragraph('Exported for ' . $username . ' on ' . date('Y-m-d H:i'), false, 18);

$filterBits = [];
if ($subjectFilter !== '') $filterBits[] = 'subject contains "' . $subjectFilter . '"';
if ($modeFilter !== '')    $filterBits[] = 'mode = ' . $modeFilter;
if ($statusFilter !== '')  $filterBits[] = 'status = ' . $statusFilter;
if ($fromDate !== '')      $filterBits[] = 'from ' . $fromDate;
if ($toDate !== '')        $filterBits[] = 'to ' . $toDate;
$body .= docx_paragraph('Filters applied: ' . (empty($filterBits) ? 'none' : implode(' · ', $filterBits)), false, 18);
$body .= '<w:p/>';

if (empty($export)) {
    $body .= docx_paragraph('No campaigns matched these filters.');
} else {
    foreach ($export as $job) {
        $modeLabel = $job['dry_run'] ? 'Dry run' : 'Live send';

        $body .= docx_paragraph($job['subject'] !== '' ? $job['subject'] : '(no subject)', true, 28);
        $body .= docx_paragraph('Sent: ' . ($job['created_at'] ?: '—') . '   ·   Mode: ' . $modeLabel . '   ·   Status: ' . $job['status']);
        $body .= docx_paragraph('Total: ' . $job['total'] . '   ·   Sent: ' . $job['sent'] . '   ·   Failed: ' . $job['failed'] . '   ·   Suppressed: ' . $job['suppressed']);
        $body .= '<w:p/>';

        if (!empty($job['results'])) {
            $rows = [];
            foreach ($job['results'] as $r) {
                $rows[] = [
                    $r['email'] ?? '',
                    $r['status'] ?? '',
                    $r['subject'] ?? '',
                    $r['error'] ?? '',
                    $r['timestamp'] ?? '',
                ];
            }
            $body .= docx_table(['Email', 'Status', 'Subject', 'Error', 'Timestamp'], $rows);
        } else {
            $body .= docx_paragraph('No per-recipient results recorded for this job.');
        }

        $body .= '<w:p/><w:p/>';
    }
}

$documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    . '<w:body>' . $body
    . '<w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440"/></w:sectPr>'
    . '</w:body></w:document>';

$contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
    . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
    . '<Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>'
    . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
    . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
    . '</Types>';

$relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
    . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
    . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
    . '</Relationships>';

$documentRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
    . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/>'
    . '</Relationships>';

$stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    . '<w:style w:type="table" w:default="1" w:styleId="TableNormal"><w:name w:val="Normal Table"/></w:style>'
    . '<w:style w:type="table" w:styleId="TableGrid">'
    . '<w:name w:val="Table Grid"/><w:basedOn w:val="TableNormal"/>'
    . '<w:tblPr><w:tblBorders>'
    . '<w:top w:val="single" w:sz="4" w:color="DBE8F6"/>'
    . '<w:left w:val="single" w:sz="4" w:color="DBE8F6"/>'
    . '<w:bottom w:val="single" w:sz="4" w:color="DBE8F6"/>'
    . '<w:right w:val="single" w:sz="4" w:color="DBE8F6"/>'
    . '<w:insideH w:val="single" w:sz="4" w:color="DBE8F6"/>'
    . '<w:insideV w:val="single" w:sz="4" w:color="DBE8F6"/>'
    . '</w:tblBorders></w:tblPr>'
    . '</w:style>'
    . '</w:styles>';

$coreXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
    . 'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
    . '<dc:title>Dispatch Campaign History Export</dc:title>'
    . '<dc:creator>' . docx_xml_escape($username) . '</dc:creator>'
    . '<dcterms:created xsi:type="dcterms:W3CDTF">' . date('c') . '</dcterms:created>'
    . '</cp:coreProperties>';

$appXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">'
    . '<Application>AlfaDevs Dispatch</Application>'
    . '</Properties>';

// Tells Word this is a modern (2013+) document so it opens normally
// instead of flagging "Compatibility Mode" in the title bar.
$settingsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    . '<w:compat><w:compatSetting w:name="compatibilityMode" w:uri="http://schemas.microsoft.com/office/word" w:val="15"/></w:compat>'
    . '</w:settings>';

$tmpFile = tempnam(sys_get_temp_dir(), 'dispatch_export_');
rename($tmpFile, $tmpFile . '.docx');
$tmpFile .= '.docx';

$zip = new ZipArchive();
$zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addFromString('[Content_Types].xml', $contentTypesXml);
$zip->addFromString('_rels/.rels', $relsXml);
$zip->addFromString('word/document.xml', $documentXml);
$zip->addFromString('word/styles.xml', $stylesXml);
$zip->addFromString('word/settings.xml', $settingsXml);
$zip->addFromString('word/_rels/document.xml.rels', $documentRelsXml);
$zip->addFromString('docProps/core.xml', $coreXml);
$zip->addFromString('docProps/app.xml', $appXml);
$zip->close();

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="dispatch_history_export.docx"');
header('Content-Length: ' . filesize($tmpFile));
readfile($tmpFile);
unlink($tmpFile);
