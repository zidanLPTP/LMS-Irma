<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAkses extends Model
{
    protected $table = 'log_akses';

    protected $fillable = [
        'user_id',
        'waktu_masuk',
        'aktivitas_terakhir',
        'durasi_detik',
    ];

    protected function casts(): array
    {
        return [
            'waktu_masuk' => 'datetime',
            'aktivitas_terakhir' => 'datetime',
            'durasi_detik' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
