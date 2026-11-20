<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use ZipArchive;

/**
 * Reads the first sheet of a .xlsx file or a .csv file into a list of rows (arrays of cell strings).
 * No external packages: CSV via fgetcsv, XLSX via ZipArchive + SimpleXML.
 */
class SpreadsheetReader
{
    /** @return array<int, array<int, string>> zero-based list of rows, row 0 is the header */
    public function read(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        return strtolower($file->getClientOriginalExtension()) === 'xlsx'
            ? $this->readXlsx($path)
            : $this->readCsv($path);
    }

    /** "Admission Number*" -> "admission_number", then applies aliases. */
    public static function normaliseHeader(string $h, array $aliases = []): string
    {
        $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
        $h = mb_strtolower(trim(str_replace(['*', '(', ')'], '', $h)));
        $h = trim(preg_replace('/[\s\-\.\/]+/', '_', $h), '_');

        return $aliases[$h] ?? $h;
    }

    private function readCsv(string $path): array
    {
        $content = file_get_contents($path);
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252'); // older Excel "CSV" exports
        }
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        $firstLine = strtok($content, "\n");
        $delimiter = substr_count((string) $firstLine, ';') > substr_count((string) $firstLine, ',') ? ';' : ',';

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $rows = [];
        while (($cells = fgetcsv($stream, 0, $delimiter)) !== false) {
            $rows[] = $cells;
        }
        fclose($stream);

        return $rows;
    }

    /**
     * First worksheet only; shared + inline strings and numbers. Formulas are not evaluated
     * (Excel stores the last calculated value, which is what we read).
     */
    private function readXlsx(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Could not open the Excel file. Is it a valid .xlsx?');
        }

        $shared = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $sst = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET);
            foreach ($sst->si as $si) {
                $text = '';
                if (isset($si->t)) {
                    $text = (string) $si->t;
                } else {
                    foreach ($si->r as $r) {
                        $text .= (string) $r->t;
                    }
                }
                $shared[] = $text;
            }
        }

        $sheetPath = 'xl/worksheets/sheet1.xml';
        if (($wb = $zip->getFromName('xl/workbook.xml')) !== false && ($rels = $zip->getFromName('xl/_rels/workbook.xml.rels')) !== false) {
            $wbXml = simplexml_load_string($wb, 'SimpleXMLElement', LIBXML_NONET);
            $relXml = simplexml_load_string($rels, 'SimpleXMLElement', LIBXML_NONET);
            $firstSheet = $wbXml->sheets->sheet[0] ?? null;
            if ($firstSheet) {
                $rid = (string) $firstSheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')->id;
                foreach ($relXml->Relationship as $rel) {
                    if ((string) $rel['Id'] === $rid) {
                        $sheetPath = 'xl/' . ltrim(str_replace('/xl/', '', (string) $rel['Target']), '/');
                    }
                }
            }
        }

        $sheet = $zip->getFromName($sheetPath);
        $zip->close();
        if ($sheet === false) {
            throw new RuntimeException('The Excel file has no worksheet.');
        }

        $xml = simplexml_load_string($sheet, 'SimpleXMLElement', LIBXML_NONET);
        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $col = $this->columnIndex(preg_replace('/\d+/', '', (string) $c['r']));
                $cells[$col] = match ((string) $c['t']) {
                    's' => $shared[(int) $c->v] ?? '',
                    'inlineStr' => (string) $c->is->t,
                    'b' => ((string) $c->v) === '1' ? 'TRUE' : 'FALSE',
                    default => (string) $c->v,
                };
            }
            if ($cells) {
                $max = max(array_keys($cells));
                $rows[(int) $row['r'] - 1] = array_replace(array_fill(0, $max + 1, ''), $cells);
            }
        }
        ksort($rows);

        // Keep spreadsheet row numbers stable: fill skipped rows with empty arrays
        $out = [];
        if ($rows) {
            for ($i = 0; $i <= max(array_keys($rows)); $i++) {
                $out[] = $rows[$i] ?? [];
            }
        }

        return $out;
    }

    private function columnIndex(string $letters): int
    {
        $n = 0;
        foreach (str_split(strtoupper($letters)) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return $n - 1;
    }
}
