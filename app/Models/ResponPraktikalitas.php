<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResponPraktikalitas extends Model
{
    protected $table = 'respon_praktikalitas';

    protected $fillable = [
        'user_id',
        'instrumen_id',
        'skor',
    ];

    protected function casts(): array
    {
        return [
            'skor' => 'integer',
        ];
    }

    /**
     * Relasi ke siswa yang memberikan respon.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke instrumen / butir pernyataan.
     */
    public function instrumen(): BelongsTo
    {
        return $this->belongsTo(InstrumenPraktikalitas::class, 'instrumen_id');
    }
}
