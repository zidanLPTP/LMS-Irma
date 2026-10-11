<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgresMateri extends Model
{
    protected $table = 'progres_materi';

    protected $fillable = [
        'user_id',
        'materi_id',
        'sudah_dibuka',
        'waktu_buka',
    ];

    protected function casts(): array
    {
        return [
            'sudah_dibuka' => 'boolean',
            'waktu_buka' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function materi(): BelongsTo
    {
        return $this->belongsTo(Materi::class, 'materi_id');
    }
}
