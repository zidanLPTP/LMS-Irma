<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class AmanValueBinder extends DefaultValueBinder implements WithCustomValueBinder
{
    /**
     * Netralkan formula injection pada sel string.
     * Karakter =, +, -, @ di awal teks dipaksa menjadi string eksplisit.
     */
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value)) {
            $trimmed = ltrim($value, ' ');
            if ($trimmed !== '') {
                $karakterAwal = $trimmed[0];
                if (in_array($karakterAwal, ['=', '+', '-', '@', "\t", "\r"], true)) {
                    $cell->setValueExplicit($value, DataType::TYPE_STRING);

                    return true;
                }
            }
        }

        return parent::bindValue($cell, $value);
    }
}
