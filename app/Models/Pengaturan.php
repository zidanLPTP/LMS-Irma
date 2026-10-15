<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $fillable = [
        'kunci',
        'nilai',
    ];

    /**
     * Cache in-memory lokal per request (menghindari query ganda dalam 1 request).
     *
     * @var array<string, mixed>|null
     */
    protected static ?array $cacheSemuaPengaturan = null;

    /**
     * Ambil nilai pengaturan berdasarkan kunci dengan nilai bawaan.
     */
    public static function ambil(string $kunci, mixed $bawaan = null): mixed
    {
        $baris = static::where('kunci', $kunci)->first();

        return $baris ? $baris->nilai : $bawaan;
    }

    /**
     * Tetapkan atau perbarui nilai pengaturan berdasarkan kunci.
     */
    public static function setel(string $kunci, mixed $nilai): static
    {
        return static::updateOrCreate(
            ['kunci' => $kunci],
            ['nilai' => (string) $nilai]
        );
    }

    /**
     * Bersihkan cache in-memory untuk testing / pergantian sesi.
     */
    public static function bersihkanCache(): void
    {
        static::$cacheSemuaPengaturan = null;
    }
}
