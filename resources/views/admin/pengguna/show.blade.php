<x-layouts.admin judul="Detail Siswa: {{ $pengguna->nama_lengkap }}">
    <div class="space-y-6">
        <!-- Header & Navigasi Balik -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.pengguna.index') }}" class="p-2 rounded-xl border border-border bg-white text-muted hover:text-ink">
                    <x-ikon nama="arrow-left" ukuran="18" />
                </a>
                <div>
                    <h1 class="text-2xl font-black text-ink">{{ $pengguna->nama_lengkap }}</h1>
                    <p class="text-sm text-muted">{{ $pengguna->username }} • {{ $pengguna->email }}</p>
                </div>
            </div>

            <!-- Tombol Aksi Cepat Akun -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.pengguna.edit', $pengguna) }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-ink bg-white border border-border hover:bg-gray-50 rounded-xl transition-colors">
                    <x-ikon nama="pencil" ukuran="14" />
                    <span>Ubah Profil</span>
                </a>

                <!-- Reset Password Admin Button -->
                <form method="POST" action="{{ route('admin.pengguna.reset-password', $pengguna) }}" data-konfirmasi="Apakah Anda yakin ingin mereset kata sandi siswa ini ke kata sandi acak sementara?" data-konfirmasi-bahaya="false" data-konfirmasi-tombol="Ya, Reset Sandi">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-xl transition-colors">
                        <x-ikon nama="key-round" ukuran="14" />
                        <span>Reset Sandi</span>
                    </button>
                </form>

                <!-- Toggle Aktif / Nonaktif -->
                <form method="POST" action="{{ route('admin.pengguna.toggle-aktif', $pengguna) }}">
                    @csrf
                    @method('PATCH')
                    @if ($pengguna->aktif)
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-red-800 bg-red-50 hover:bg-red-100 border border-red-200 rounded-xl transition-colors" data-konfirmasi="Nonaktifkan akun siswa ini? Sesi yang sedang aktif akan langsung dikeluarkan." data-konfirmasi-bahaya="true" data-konfirmasi-tombol="Ya, Nonaktifkan">
                            <x-ikon nama="user-x" ukuran="14" />
                            <span>Nonaktifkan</span>
                        </button>
                    @else
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-green-800 bg-green-50 hover:bg-green-100 border border-green-200 rounded-xl transition-colors">
                            <x-ikon nama="user-check" ukuran="14" />
                            <span>Aktifkan</span>
                        </button>
                    @endif
                </form>
            </div>
        </div>

        <!-- Banner Password Hasil Reset (Tampil 1x) -->
        @if (session('sandi_sementara'))
            <div class="bg-amber-50 border-2 border-amber-300 rounded-2xl p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-amber-100 text-amber-800 rounded-xl shrink-0">
                        <x-ikon nama="key-round" ukuran="24" />
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold text-amber-900">Kata Sandi Sementara Berhasil Dibuat</h2>
                        <p class="text-xs text-amber-800 mt-0.5">Berikan kata sandi ini kepada siswa. Kata sandi ini hanya ditampilkan SATU KALI ini saja dan siswa diwajibkan membuat kata sandi baru saat masuk.</p>
                        <div class="mt-3 flex items-center gap-3">
                            <span class="font-mono text-lg font-black text-amber-900 bg-white border border-amber-300 px-3 py-1.5 rounded-xl select-all">
                                {{ session('sandi_sementara') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Kolom Kiri: Informasi Akun & Stepper Progres -->
            <div class="space-y-6">
                <!-- Kartu Status Akun -->
                <div class="bg-white rounded-2xl border border-border p-5 shadow-sm space-y-3">
                    <h2 class="text-sm font-black text-ink uppercase tracking-wider border-b border-border pb-2">Informasi Akun</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted">Status:</span>
                            @if ($pengguna->aktif)
                                <span class="font-bold text-green-700">Aktif</span>
                            @else
                                <span class="font-bold text-red-600">Nonaktif</span>
                            @endif
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Kelas:</span>
                            <span class="font-bold text-ink">{{ $pengguna->kelas ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Wajib Ganti Sandi:</span>
                            <span class="font-bold {{ $pengguna->wajib_ganti_password ? 'text-amber-700' : 'text-muted' }}">
                                {{ $pengguna->wajib_ganti_password ? 'Ya' : 'Tidak' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Terdaftar Pada:</span>
                            <span class="font-bold text-ink">{{ $pengguna->created_at?->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Stepper Alur Belajar Siswa -->
                <div class="bg-white rounded-2xl border border-border p-5 shadow-sm space-y-4">
                    <h2 class="text-sm font-black text-ink uppercase tracking-wider border-b border-border pb-2">Tahapan Pembelajaran</h2>
                    <div class="space-y-3">
                        @foreach ($langkahBelajar as $idx => $step)
                            <div class="flex items-start gap-3 p-2.5 rounded-xl border {{ $step['status'] === 'selesai' ? 'bg-green-50/50 border-green-200' : ($step['status'] === 'terbuka' ? 'bg-amber-50/40 border-amber-200' : 'bg-gray-50 border-gray-200 opacity-60') }}">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-black shrink-0 {{ $step['status'] === 'selesai' ? 'bg-green-700 text-white' : ($step['status'] === 'terbuka' ? 'bg-amber-500 text-white' : 'bg-gray-300 text-muted') }}">
                                    @if ($step['status'] === 'selesai')
                                        ✓
                                    @else
                                        {{ $idx + 1 }}
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-bold text-ink truncate">{{ $step['judul'] }}</div>
                                    <div class="text-[11px] capitalize font-semibold {{ $step['status'] === 'selesai' ? 'text-green-700' : ($step['status'] === 'terbuka' ? 'text-amber-800' : 'text-muted') }}">
                                        {{ $step['status'] }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Formulir Modal Reset Kesempatan Kuis -->
                <div class="bg-white rounded-2xl border border-border p-5 shadow-sm space-y-3">
                    <h2 class="text-sm font-black text-ink uppercase tracking-wider border-b border-border pb-2">Reset Kesempatan Kuis</h2>
                    <p class="text-xs text-muted">Admin dapat membatalkan percobaan sebelumnya agar siswa bisa mengulang kuis. Data percobaan lama tidak dihapus.</p>
                    <form method="POST" action="{{ route('admin.pengguna.reset-kesempatan', $pengguna) }}" class="space-y-3" data-konfirmasi="Batalkan seluruh percobaan siswa pada kuis ini untuk mereset kesempatan?" data-konfirmasi-bahaya="false" data-konfirmasi-tombol="Ya, Reset Kesempatan">
                        @csrf
                        <div>
                            <label for="kuis_id" class="block text-xs font-bold text-muted mb-1">Pilih Kuis</label>
                            <select id="kuis_id" name="kuis_id" required class="w-full px-3 py-2 text-xs bg-bg border border-border rounded-xl focus:outline-none focus:ring-2 focus:ring-green-700">
                                <option value="">-- Pilih Kuis --</option>
                                @foreach ($daftarKuis as $k)
                                    <option value="{{ $k->id }}">{{ $k->judul }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="alasan" class="block text-xs font-bold text-muted mb-1">Alasan Reset (Wajib)</label>
                            <textarea id="alasan" name="alasan" rows="2" required placeholder="Contoh: Terjadi pemadaman listrik saat siswa mengerjakan." class="w-full px-3 py-2 text-xs bg-bg border border-border rounded-xl focus:outline-none focus:ring-2 focus:ring-green-700"></textarea>
                        </div>
                        <button type="submit" class="w-full py-2 bg-amber-600 text-white rounded-xl text-xs font-bold hover:bg-amber-700 transition-colors shadow-sm">
                            Batalkan Percobaan & Reset
                        </button>
                    </form>
                </div>
            </div>

            <!-- Kolom Kanan: Riwayat Percobaan, Sesi Akses, & Jejak Audit -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Riwayat Percobaan Kuis -->
                <div class="bg-white rounded-2xl border border-border p-5 shadow-sm space-y-3">
                    <h2 class="text-sm font-black text-ink uppercase tracking-wider border-b border-border pb-2">Riwayat Percobaan Kuis</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-bg text-muted font-black border-b border-border">
                                    <th class="py-2.5 px-3">Kuis</th>
                                    <th class="py-2.5 px-3">Ke-</th>
                                    <th class="py-2.5 px-3">Status</th>
                                    <th class="py-2.5 px-3">Nilai</th>
                                    <th class="py-2.5 px-3">Dibatalkan</th>
                                    <th class="py-2.5 px-3">Waktu</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse ($riwayatPercobaan as $p)
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2.5 px-3 font-bold text-ink">{{ $p->kuis?->judul }}</td>
                                        <td class="py-2.5 px-3 font-semibold">{{ $p->nomor_percobaan }}</td>
                                        <td class="py-2.5 px-3">
                                            @if ($p->esai_menunggu_nilai)
                                                <span class="text-amber-800 font-bold">menunggu esai</span>
                                            @else
                                                <span class="font-bold">{{ $p->status_kelulusan ?? $p->status_pengerjaan }}</span>
                                            @endif
                                        </td>
                                        <td class="py-2.5 px-3 font-bold text-ink">{{ $p->nilai_akhir ?? $p->nilai_otomatis ?? '-' }}</td>
                                        <td class="py-2.5 px-3">
                                            @if ($p->dibatalkan)
                                                <span class="inline-block px-2 py-0.5 rounded text-[11px] font-black bg-red-100 text-red-800" title="{{ $p->alasan_pembatalan }}">
                                                    Ya
                                                </span>
                                            @else
                                                <span class="text-muted">Tidak</span>
                                            @endif
                                        </td>
                                        <td class="py-2.5 px-3 text-muted">
                                            {{ $p->waktu_mulai?->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-4 text-center text-muted">Belum ada riwayat percobaan kuis.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 10 Sesi Akses Terakhir -->
                <div class="bg-white rounded-2xl border border-border p-5 shadow-sm space-y-3">
                    <h2 class="text-sm font-black text-ink uppercase tracking-wider border-b border-border pb-2">10 Sesi Akses Terakhir</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-bg text-muted font-black border-b border-border">
                                    <th class="py-2.5 px-3">Waktu Masuk</th>
                                    <th class="py-2.5 px-3">Aktivitas Terakhir</th>
                                    <th class="py-2.5 px-3">Durasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse ($sesiAkses as $log)
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2 px-3 font-semibold text-ink">{{ $log->waktu_masuk?->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s') }}</td>
                                        <td class="py-2 px-3 text-muted">{{ $log->aktivitas_terakhir?->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s') }}</td>
                                        <td class="py-2 px-3 font-bold text-ink">
                                            @php
                                                $jam = floor($log->durasi_detik / 3600);
                                                $menit = floor(($log->durasi_detik % 3600) / 60);
                                                $detik = $log->durasi_detik % 60;
                                            @endphp
                                            {{ sprintf('%02d:%02d:%02d', $jam, $menit, $detik) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-4 text-center text-muted">Belum pernah mengakses aplikasi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Jejak Tindakan Administrator -->
                <div class="bg-white rounded-2xl border border-border p-5 shadow-sm space-y-3">
                    <h2 class="text-sm font-black text-ink uppercase tracking-wider border-b border-border pb-2">Jejak Tindakan Administrator</h2>
                    <div class="space-y-3">
                        @forelse ($jejakAudit as $audit)
                            <div class="p-3 bg-bg rounded-xl border border-border text-xs">
                                <div class="flex items-center justify-between font-bold text-ink mb-1">
                                    <span class="inline-flex items-center gap-1.5 text-green-800">
                                        <x-ikon nama="shield-alert" ukuran="14" />
                                        {{ ucwords(str_replace('_', ' ', $audit->jenis)) }}
                                    </span>
                                    <span class="text-muted font-normal">{{ $audit->created_at?->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }}</span>
                                </div>
                                <p class="text-ink text-xs">{{ $audit->keterangan }}</p>
                                <span class="block text-[11px] text-muted mt-1">Oleh: {{ $audit->admin?->nama_lengkap ?? 'Admin' }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-muted py-2">Belum ada jejak tindakan administratif pada siswa ini.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
