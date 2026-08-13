<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * XLSX writer ringan tanpa dependency tambahan.
 * Export dibuat putih polos, bersih, dengan border pada seluruh tabel,
 * tanpa filter/dropdown dan tanpa pewarnaan area sheet yang kosong.
 */
class SimpleXlsx
{
    public static function download(string $filename, string $title, array $headers, array $rows, array $widths = []): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP ZipArchive diperlukan untuk membuat file Excel (.xlsx).');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak dapat membuat file Excel sementara.');
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::rootRels());
        $zip->addFromString('xl/workbook.xml', self::workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels());
        $zip->addFromString('xl/styles.xml', self::styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheet($title, $headers, $rows, $widths));
        $zip->close();

        return response()->streamDownload(function () use ($tmp) {
            readfile($tmp);
            @unlink($tmp);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private static function esc(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? '-'), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function col(int $n): string
    {
        $out = '';
        while ($n > 0) {
            $n--;
            $out = chr(65 + ($n % 26)) . $out;
            $n = intdiv($n, 26);
        }
        return $out;
    }

    private static function inlineCell(int $column, int $row, mixed $value, int $style = 1): string
    {
        $ref = self::col($column) . $row;
        $value = self::esc($value === '' ? '-' : $value);
        return '<c r="'.$ref.'" t="inlineStr" s="'.$style.'"><is><t xml:space="preserve">'.$value.'</t></is></c>';
    }

    private static function rowHeightFor(array $row, array $widths): float
    {
        $maxLines = 1;
        foreach ($row as $i => $value) {
            $text = (string) ($value ?? '-');
            $width = max(8, (float) ($widths[$i] ?? 22));
            $lines = max(1, (int) ceil(mb_strlen($text) / max(8, $width * 1.25)));
            $maxLines = max($maxLines, min($lines, 6));
        }

        return min(108, max(22, 18 * $maxLines));
    }

    private static function sheet(string $title, array $headers, array $rows, array $widths): string
    {
        $count = count($headers);
        $lastCol = self::col($count);
        $title = self::esc($title);
        $generated = self::esc(now()->format('d/m/Y H:i:s'));
        $lastRow = max(3, count($rows) + 3);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<dimension ref="A1:'.$lastCol.$lastRow.'"/>';
        $xml .= '<sheetViews><sheetView workbookViewId="0" showGridLines="1"><pane ySplit="3" topLeftCell="A4" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A4" sqref="A4"/></sheetView></sheetViews>';
        $xml .= '<sheetFormatPr defaultRowHeight="20"/><cols>';

        for ($i = 0; $i < $count; $i++) {
            $width = (float) ($widths[$i] ?? 22);
            $xml .= '<col min="'.($i + 1).'" max="'.($i + 1).'" width="'.$width.'" customWidth="1"/>';
        }

        $xml .= '</cols><sheetData>';

        // Informasi laporan di atas tabel: putih polos, tanpa border/fill tambahan.
        $xml .= '<row r="1" ht="28" customHeight="1">'.self::inlineCell(1, 1, $title, 0).'</row>';
        $xml .= '<row r="2" ht="22" customHeight="1">'.self::inlineCell(1, 2, 'Diekspor otomatis oleh SIBERAD · '.$generated, 4).'</row>';

        // Header tabel: putih, tebal, border penuh, tanpa filter/dropdown.
        $xml .= '<row r="3" ht="30" customHeight="1">';
        foreach ($headers as $i => $header) {
            $xml .= self::inlineCell($i + 1, 3, $header, 2);
        }
        $xml .= '</row>';

        // Semua baris data memakai style yang sama: putih + all borders + wrap.
        // Jumlah baris sepenuhnya mengikuti jumlah data; tidak ada pewarnaan
        // atau format berbeda pada baris setelah batas tertentu.
        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 4;
            $height = self::rowHeightFor($row, $widths);
            $xml .= '<row r="'.$excelRow.'" ht="'.$height.'" customHeight="1">';
            foreach ($headers as $i => $_) {
                $xml .= self::inlineCell($i + 1, $excelRow, $row[$i] ?? '-', 1);
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData>';
        $xml .= '<mergeCells count="2"><mergeCell ref="A1:'.$lastCol.'1"/><mergeCell ref="A2:'.$lastCol.'2"/></mergeCells>';
        $xml .= '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>';
        $xml .= '<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/><pageSetUpPr fitToPage="1"/>';
        $xml .= '</worksheet>';

        return $xml;
    }

    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<numFmts count="0"/>
<fonts count="3"><font><sz val="11"/><name val="Aptos"/></font><font><b/><sz val="14"/><name val="Aptos"/></font><font><b/><sz val="11"/><name val="Aptos"/></font></fonts>
<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>
<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFB7B7B7"/></left><right style="thin"><color rgb="FFB7B7B7"/></right><top style="thin"><color rgb="FFB7B7B7"/></top><bottom style="thin"><color rgb="FFB7B7B7"/></bottom><diagonal/></border></borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="5"><xf numFmtId="0" fontId="1" fillId="0" borderId="0" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="2" fillId="0" borderId="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="2" fillId="0" borderId="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" applyAlignment="1"><alignment vertical="center"/></xf></cellXfs>
<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>';
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private static function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Laporan" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }
}
