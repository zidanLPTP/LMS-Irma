<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';

    protected $fillable = [
        'user_id',
        'jenis',
        'referensi',
        'judul',
        'isi',
        'tautan',
        'dibaca_pada',
    ];

    protected $casts = [
        'dibaca_pada' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeBelumDibaca(Builder $query): Builder
    {
        return $query->whereNull('dibaca_pada');
    }

    public function tandaiDibaca(): void
    {
        if (is_null($this->dibaca_pada)) {
            $this->update(['dibaca_pada' => now()]);
        }
    }
}
