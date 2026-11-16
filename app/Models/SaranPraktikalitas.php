<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaranPraktikalitas extends Model
{
    protected $table = 'saran_praktikalitas';

    protected $fillable = [
        'user_id',
        'saran',
    ];

    /**
     * Relasi ke siswa yang memberikan saran.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
