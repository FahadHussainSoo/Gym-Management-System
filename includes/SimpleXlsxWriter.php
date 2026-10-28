<?php
/**
 * SimpleXlsxWriter — a minimal, dependency-free .xlsx (Office Open XML) writer.
 *
 * Why this exists: PhpSpreadsheet (via Composer) gives richer styling, but it
 * requires `composer install` to have been run. On a fresh XAMPP checkout
 * without Composer/internet access configured, that step is easy to miss —
 * and reports should never hard-fail just because of it. This class produces
 * a genuine, valid .xlsx file using only PHP's built-in ZipArchive extension
 * (enabled by default in XAMPP), with zero external dependencies.
 *
 * It is used automatically by modules/reports/export_excel.php as a fallback
 * when vendor/autoload.php / PhpSpreadsheet isn't available. If Composer
 * dependencies ARE installed, the richer PhpSpreadsheet path is used instead
 * and this class is not touched.
 *
 * Usage:
 *   $writer = new SimpleXlsxWriter('Member Report');
 *   $writer->setTitle('Member Report (2026-01-01 to 2026-01-31)');
 *   $writer->setHeaders(['Code', 'Name', 'Phone']);
 *   $writer->setRows([['M001', 'Ali', '0300...'], ...]);
 *   $writer->output('member_report.xlsx'); // streams the file and exits
 */
class SimpleXlsxWriter
{
    private string $sheetName;
    private string $title = '';
    private array $headers = [];
    private array $rows = [];

    public function __construct(string $sheetName = 'Report')
    {
        // Excel sheet names: max 31 chars, no : \ / ? * [ ]
        $clean = preg_replace('/[:\\\\\/\?\*\[\]]/', ' ', $sheetName);
        $this->sheetName = substr(trim($clean) ?: 'Report', 0, 31);
    }

    public function setTitle(string $title): void { $this->title = $title; }
    public function setHeaders(array $headers): void { $this->headers = $headers; }
    public function setRows(array $rows): void { $this->rows = $rows; }

    /** Builds the .xlsx and returns it as a raw binary string. */
    public function build(): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::OVERWRITE);

        $zip->addEmptyDir('_rels');
        $zip->addEmptyDir('docProps');
        $zip->addEmptyDir('xl');
        $zip->addEmptyDir('xl/_rels');
        $zip->addEmptyDir('xl/worksheets');

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('docProps/core.xml', $this->coreXml());
        $zip->addFromString('docProps/app.xml', $this->appXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml());

        $zip->close();
        $binary = file_get_contents($tmpFile);
        unlink($tmpFile);
        return $binary;
    }

    /** Streams the file as a download and terminates the script. */
    public function output(string $filename): void
    {
        $binary = $this->build();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($binary));
        header('Cache-Control: max-age=0');
        echo $binary;
        exit;
    }

    private function colLetter(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = intdiv($index - $mod, 26);
        }
        return $letter;
    }

    private function escape(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function coreXml(): string
    {
        $now = date('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>Gym Management System</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>'
            . '</cp:coreProperties>';
    }

    private function appXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>SimpleXlsxWriter</Application></Properties>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->escape($this->sheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    /** Style 0 = default, 1 = title (bold 14), 2 = header (bold, white on brand purple fill) */
    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="3">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="14"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF4F46E5"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function cellXml(string $ref, string $value, int $styleIndex): string
    {
        // Everything is written as an inline string cell (t="inlineStr") to avoid
        // needing a separate sharedStrings.xml part - keeps this writer simple.
        $s = $styleIndex > 0 ? ' s="' . $styleIndex . '"' : '';
        if ($value === '') {
            return '<c r="' . $ref . '"' . $s . '/>';
        }
        return '<c r="' . $ref . '" t="inlineStr"' . $s . '><is><t xml:space="preserve">' . $this->escape($value) . '</t></is></c>';
    }

    private function sheetXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        $colCount = max(count($this->headers), 1);
        $cols = '<cols>';
        for ($i = 0; $i < $colCount; $i++) {
            $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="20" customWidth="1"/>';
        }
        $cols .= '</cols>';
        $xml .= $cols;

        $xml .= '<sheetData>';
        $rowNum = 1;

        // Title row (merged across all columns, bold 14pt)
        if ($this->title !== '') {
            $xml .= '<row r="' . $rowNum . '">' . $this->cellXml('A' . $rowNum, $this->title, 1) . '</row>';
            $rowNum += 2; // leave a blank row after the title
        }

        // Header row
        if ($this->headers) {
            $cells = '';
            foreach ($this->headers as $i => $h) {
                $cells .= $this->cellXml($this->colLetter($i) . $rowNum, (string) $h, 2);
            }
            $xml .= '<row r="' . $rowNum . '">' . $cells . '</row>';
            $rowNum++;
        }

        // Data rows
        foreach ($this->rows as $row) {
            $cells = '';
            foreach (array_values($row) as $i => $val) {
                $cells .= $this->cellXml($this->colLetter($i) . $rowNum, (string) $val, 0);
            }
            $xml .= '<row r="' . $rowNum . '">' . $cells . '</row>';
            $rowNum++;
        }

        $xml .= '</sheetData>';

        // Merge the title cell across all columns, if we have a title
        if ($this->title !== '' && $colCount > 1) {
            $lastCol = $this->colLetter($colCount - 1);
            $xml .= '<mergeCells count="1"><mergeCell ref="A1:' . $lastCol . '1"/></mergeCells>';
        }

        $xml .= '</worksheet>';
        return $xml;
    }
}
