<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatProfil extends Model
{
    protected $table = 'riwayat_profil';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'kolom',
        'nilai_lama',
        'nilai_baru',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
