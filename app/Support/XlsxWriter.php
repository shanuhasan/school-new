<?php

namespace App\Support;

use DateTimeInterface;
use RuntimeException;
use ZipArchive;

/**
 * Minimal single-sheet .xlsx writer (no external packages).
 *
 * Features: bold frozen header row, auto-filter, column widths, real date cells,
 * numbers as numbers, everything else as text (so phone numbers keep leading zeros).
 */
class XlsxWriter
{
    private array $rows = [];

    /**
     * @param array<int, string> $headers
     * @param array<int, int>    $widths  column widths in characters (optional)
     */
    public function __construct(
        private array $headers,
        private array $widths = [],
        private string $sheetName = 'Sheet1',
    ) {
    }

    /** @param array<int, scalar|DateTimeInterface|null> $row */
    public function addRow(array $row): void
    {
        $this->rows[] = array_values($row);
    }

    /** Builds the workbook and returns the path of a temporary .xlsx file. */
    public function save(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the Excel file.');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '</Relationships>');

        $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>' . $this->esc(config('app.name')) . '</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:created>'
            . '</cp:coreProperties>');

        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->esc(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $this->sheetName), 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '<definedNames><definedName name="_xlnm._FilterDatabase" localSheetId="0" hidden="1">\'' . $this->esc(mb_substr($this->sheetName, 0, 31)) . '\'!$A$1:$' . $this->col(count($this->headers) - 1) . '$' . max(1, count($this->rows) + 1) . '</definedName></definedNames>'
            . '</workbook>');

        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');

        // Style 0 = normal, 1 = bold header with light fill, 2 = date (yyyy-mm-dd)
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="yyyy-mm-dd"/></numFmts>'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE3EDE7"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>');

        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml());
        $zip->close();

        return $path;
    }

    private function sheetXml(): string
    {
        $lastCol = $this->col(count($this->headers) - 1);
        $lastRow = count($this->rows) + 1;

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>';

        if ($this->widths) {
            $xml .= '<cols>';
            foreach ($this->headers as $i => $_) {
                $w = $this->widths[$i] ?? 14;
                $xml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        $xml .= $this->rowXml(1, $this->headers, header: true);
        foreach ($this->rows as $i => $row) {
            $xml .= $this->rowXml($i + 2, $row);
        }
        $xml .= '</sheetData>';
        $xml .= '<autoFilter ref="A1:' . $lastCol . $lastRow . '"/>';

        return $xml . '</worksheet>';
    }

    private function rowXml(int $r, array $cells, bool $header = false): string
    {
        $xml = '<row r="' . $r . '">';
        foreach ($cells as $i => $value) {
            $ref = $this->col($i) . $r;
            if ($value === null || $value === '') {
                continue;
            }
            if ($header) {
                $xml .= '<c r="' . $ref . '" t="inlineStr" s="1"><is><t>' . $this->esc((string) $value) . '</t></is></c>';
            } elseif ($value instanceof DateTimeInterface) {
                $xml .= '<c r="' . $ref . '" s="2"><v>' . $this->excelDate($value) . '</v></c>';
            } elseif (is_int($value) || is_float($value)) {
                $xml .= '<c r="' . $ref . '"><v>' . $value . '</v></c>';
            } else {
                $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . $this->esc((string) $value) . '</t></is></c>';
            }
        }

        return $xml . '</row>';
    }

    private function excelDate(DateTimeInterface $d): int
    {
        // Excel serial day (1899-12-30 is day 0), computed from the calendar date only so time zones don't shift it
        [$y, $m, $day] = array_map('intval', explode('-', $d->format('Y-m-d')));

        return intdiv(gmmktime(0, 0, 0, $m, $day, $y) - gmmktime(0, 0, 0, 12, 30, 1899), 86400);
    }

    private function col(int $index): string
    {
        $s = '';
        for ($n = $index + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $s = chr(65 + (($n - 1) % 26)) . $s;
        }

        return $s;
    }

    private function esc(string $s): string
    {
        // Strip characters that are illegal in XML 1.0 (e.g. stray control chars pasted into addresses)
        $s = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $s) ?? '';

        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
