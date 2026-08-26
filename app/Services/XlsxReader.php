<?php

namespace App\Services;

/**
 * Sprint 66 — Minimal .xlsx reader.
 *
 * Parses the first worksheet of an xlsx file (a zip of XML parts) into rows
 * of string values, without requiring any external package. Used by the
 * student import feature only; files are never stored permanently.
 */
class XlsxReader
{
    /**
     * @return array<int, array<int, string>> rows of cell values (first row = header)
     */
    public static function read(string $path): array
    {
        $zip = new \ZipArchive();

        if ($zip->open($path) !== true) {
            throw new \RuntimeException('تعذر قراءة ملف Excel.');
        }

        // Resolve the first sheet's target path via workbook relationships.
        $sheetPath = 'xl/worksheets/sheet1.xml';
        $workbook = $zip->getFromName('xl/workbook.xml');
        if ($workbook !== false && preg_match('/<sheet[^>]*r:id="(rId\d+)"/i', $workbook, $m)) {
            $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if ($rels !== false && preg_match('/<Relationship[^>]*Id="' . $m[1] . '"[^>]*Target="([^"]+)"/i', $rels, $t)) {
                $target = ltrim($t[1], '/');
                $sheetPath = str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
            }
        }

        $shared = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $sx = simplexml_load_string($sharedXml);
            foreach ($sx->si as $si) {
                $shared[] = (string) $si;
            }
        }

        $xml = $zip->getFromName($sheetPath);
        $zip->close();

        if ($xml === false) {
            throw new \RuntimeException('ملف Excel لا يحتوي على ورقة عمل صالحة.');
        }

        $sx = simplexml_load_string($xml);
        $rows = [];

        foreach ($sx->sheetData->row as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                $col = self::columnIndex($ref);
                $type = (string) $cell['t'];
                $value = '';

                if ($type === 's') {
                    $idx = (int) $cell->v;
                    $value = $shared[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) ($cell->is->t ?? '');
                } else {
                    $value = (string) ($cell->v ?? '');
                }

                $values[$col] = trim($value);
            }

            // Normalize column gaps into a dense array.
            if ($values !== []) {
                $max = max(array_keys($values));
                $dense = [];
                for ($i = 0; $i <= $max; $i++) {
                    $dense[$i] = $values[$i] ?? '';
                }
                $rows[] = $dense;
            }
        }

        return $rows;
    }

    private static function columnIndex(string $reference): int
    {
        preg_match('/^([A-Z]+)/', strtoupper($reference), $m);
        $letters = $m[1] ?? 'A';
        $index = 0;
        for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }
}