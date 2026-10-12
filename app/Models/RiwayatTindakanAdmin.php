<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatTindakanAdmin extends Model
{
    protected $table = 'riwayat_tindakan_admin';

    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'siswa_id',
        'jenis',
        'keterangan',
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

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }
}
