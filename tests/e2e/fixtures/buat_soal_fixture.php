<?php

require dirname(__DIR__, 3).'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$sp = new Spreadsheet;
$sheet = $sp->getActiveSheet();
$rows = [
    ['tipe', 'pertanyaan', 'bobot', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d', 'pilihan_e', 'kunci', 'kunci_isian_1', 'kunci_isian_2', 'kunci_isian_3'],
    ['pilihan_ganda', 'Bagian sel yang berfungsi mengatur seluruh kegiatan sel adalah...', 10, 'Inti Sel (Nukleus)', 'Mitokondria', 'Ribosom', 'Retikulum Endoplasma', 'Vakuola', 'A', '', '', ''],
    ['isian', 'Organel sel penghasil energi utama melalui proses respirasi seluler disebut...', 10, '', '', '', '', '', '', 'mitokondria', 'Mitokondria', 'MITOKONDRIA'],
    ['esai', 'Jelaskan perbedaan mendasar antara sel tumbuhan dan sel hewan terkait dinding sel!', 20, '', '', '', '', '', '', '', '', ''],
];
$sheet->fromArray($rows, null, 'A1');

$dir = __DIR__.'/tests/e2e/fixtures';
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$writer = new Xlsx($sp);
$writer->save($dir.'/soal_uji.xlsx');
echo "File fixtures/soal_uji.xlsx berhasil dibuat.\n";
