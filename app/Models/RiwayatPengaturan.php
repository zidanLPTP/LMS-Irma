<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatPengaturan extends Model
{
    protected $table = 'riwayat_pengaturan';

    public $timestamps = false;

    protected $fillable = [
        'kunci',
        'nilai_lama',
        'nilai_baru',
        'admin_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
