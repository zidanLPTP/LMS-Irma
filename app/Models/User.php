<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama_lengkap',
        'username',
        'email',
        'password',
        'peran',
        'kelas',
        'aktif',
        'wajib_ganti_password',
    ];

    /**
     * Kolom yang disembunyikan dalam serialisasi.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'aktif' => 'boolean',
            'wajib_ganti_password' => 'boolean',
        ];
    }

    /**
     * Relasi ke percobaan kuis siswa.
     */
    public function percobaanKuis(): HasMany
    {
        return $this->hasMany(PercobaanKuis::class, 'user_id');
    }

    /**
     * Relasi ke progres pembukaan materi.
     */
    public function progresMateri(): HasMany
    {
        return $this->hasMany(ProgresMateri::class, 'user_id');
    }

    /**
     * Relasi ke log sesi akses siswa.
     */
    public function logAkses(): HasMany
    {
        return $this->hasMany(LogAkses::class, 'user_id');
    }

    /**
     * Relasi ke riwayat tindakan admin yang diterima siswa ini.
     */
    public function riwayatTindakan(): HasMany
    {
        return $this->hasMany(RiwayatTindakanAdmin::class, 'siswa_id');
    }

    /**
     * Relasi ke pengumuman yang sudah dibaca oleh siswa ini.
     */
    public function pengumumanDibaca(): BelongsToMany
    {
        return $this->belongsToMany(Pengumuman::class, 'pengumuman_dibaca', 'user_id', 'pengumuman_id')
            ->withPivot('dibaca_pada')
            ->withTimestamps();
    }

    /**
     * Relasi ke notifikasi siswa ini.
     */
    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'user_id');
    }

    /**
     * Cek apakah pengguna adalah admin.
     */
    public function isAdmin(): bool
    {
        return $this->peran === 'admin';
    }

    /**
     * Cek apakah pengguna adalah siswa.
     */
    public function isSiswa(): bool
    {
        return $this->peran === 'siswa';
    }
}
