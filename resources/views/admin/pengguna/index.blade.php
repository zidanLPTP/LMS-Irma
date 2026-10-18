<x-layouts.admin judul="Kelola Pengguna">
    <div class="space-y-6">
        <!-- Header Halaman -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-ink">{{ __('pengguna.judul_halaman') }}</h1>
                <p class="text-sm text-muted mt-1">{{ __('pengguna.deskripsi_halaman') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.pengguna.ganda') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-bold bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 transition-colors">
                    <x-ikon nama="users" ukuran="16" />
                    <span>{{ __('pengguna.deteksi_ganda') }}</span>
                </a>
                <a href="{{ route('admin.impor-siswa.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-sm font-bold bg-white text-ink border border-border hover:bg-surface hover:border-green-700 transition-colors">
                    <x-ikon nama="upload" ukuran="16" />
                    <span>Impor Excel</span>
                </a>
                <a href="{{ route('admin.pengguna.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold bg-green-700 text-white hover:bg-green-800 shadow-sm transition-colors">
                    <x-ikon nama="plus" ukuran="16" />
                    <span>{{ __('pengguna.tambah_siswa') }}</span>
                </a>
            </div>
        </div>

        <!-- Banner Password Baru Ditambahkan (Sekali Tampil) -->
        @if (session('sandi_sementara_baru'))
            @php $info = session('sandi_sementara_baru'); @endphp
            <div class="bg-amber-50 border-2 border-amber-300 rounded-2xl p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-amber-100 text-amber-800 rounded-xl shrink-0">
                        <x-ikon nama="key-round" ukuran="24" />
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold text-amber-900">Kata Sandi Sementara Berhasil Dibuat</h2>
                        <p class="text-xs text-amber-800 mt-0.5">Catat kata sandi ini sekarang. Demi keamanan, sistem tidak menyimpan teks kata sandi ini di pangkalan data.</p>
                        <div class="mt-3 flex flex-wrap items-center gap-4 bg-white px-4 py-3 rounded-xl border border-amber-200">
                            <div>
                                <span class="text-xs text-muted block">Siswa:</span>
                                <span class="text-sm font-bold text-ink">{{ $info['nama'] }} ({{ $info['username'] }})</span>
                            </div>
                            <div>
                                <span class="text-xs text-muted block">Kata Sandi Sementara:</span>
                                <span class="font-mono text-base font-black text-amber-900 bg-amber-100 px-2.5 py-1 rounded-lg select-all">{{ $info['sandi'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Filter & Pencarian -->
        <div class="bg-white rounded-2xl border border-border p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.pengguna.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="lg:col-span-2">
                    <input type="text" name="cari" value="{{ $cari }}" aria-label="{{ __('pengguna.cari_placeholder') }}" placeholder="{{ __('pengguna.cari_placeholder') }}" class="w-full px-3.5 py-2 text-sm bg-bg border border-border rounded-xl focus:outline-none focus:ring-2 focus:ring-green-700">
                </div>
                <div>
                    <select name="kelas" aria-label="{{ __('pengguna.semua_kelas') }}" class="w-full px-3 py-2 text-sm bg-bg border border-border rounded-xl focus:outline-none focus:ring-2 focus:ring-green-700">
                        <option value="">{{ __('pengguna.semua_kelas') }}</option>
                        @foreach ($daftarKelas as $k)
                            <option value="{{ $k }}" {{ $kelasDipilih === $k ? 'selected' : '' }}>{{ $k }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <select name="status" aria-label="{{ __('pengguna.semua_status') }}" class="w-full px-3 py-2 text-sm bg-bg border border-border rounded-xl focus:outline-none focus:ring-2 focus:ring-green-700">
                        <option value="">{{ __('pengguna.semua_status') }}</option>
                        <option value="aktif" {{ $statusDipilih === 'aktif' ? 'selected' : '' }}>{{ __('pengguna.aktif') }}</option>
                        <option value="nonaktif" {{ $statusDipilih === 'nonaktif' ? 'selected' : '' }}>{{ __('pengguna.nonaktif') }}</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-green-700 text-white rounded-xl text-sm font-bold hover:bg-green-800 shrink-0">
                        Filter
                    </button>
                    @if ($cari || $kelasDipilih || $statusDipilih)
                        <a href="{{ route('admin.pengguna.index') }}" class="px-3 py-2 bg-gray-100 text-muted rounded-xl text-sm font-bold hover:bg-gray-200 shrink-0 flex items-center">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Tabel Daftar Siswa Mobile-first -->
        <div class="bg-white rounded-2xl border border-border shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-bg border-b border-border text-xs font-black text-muted uppercase tracking-wider">
                            <th class="py-3 px-4">{{ __('pengguna.tabel.nama_lengkap') }}</th>
                            <th class="py-3 px-4">{{ __('pengguna.tabel.identitas') }}</th>
                            <th class="py-3 px-4">{{ __('pengguna.tabel.kelas') }}</th>
                            <th class="py-3 px-4">{{ __('pengguna.tabel.status') }}</th>
                            <th class="py-3 px-4">{{ __('pengguna.tabel.progres') }}</th>
                            <th class="py-3 px-4">{{ __('pengguna.tabel.keaktifan') }}</th>
                            <th class="py-3 px-4 text-right">{{ __('pengguna.tabel.aksi') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($daftarSiswa as $siswa)
                            <tr class="hover:bg-green-50/40 transition-colors">
                                <td class="py-3 px-4 font-bold text-ink">
                                    <a href="{{ route('admin.pengguna.show', $siswa) }}" class="hover:text-green-700 transition-colors">
                                        {{ $siswa->nama_lengkap }}
                                    </a>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-mono text-xs font-semibold text-muted">{{ $siswa->username }}</div>
                                    <div class="text-xs text-muted">{{ $siswa->email }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-block px-2.5 py-1 text-xs font-bold rounded-lg bg-gray-100 text-ink">
                                        {{ $siswa->kelas ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    @if ($siswa->aktif)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-600"></span>
                                            {{ __('pengguna.aktif') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                                            {{ __('pengguna.nonaktif') }}
                                        </span>
                                    @endif
                                    @if ($siswa->wajib_ganti_password)
                                        <span class="block mt-1 text-[11px] font-bold text-amber-700">
                                            *{{ __('pengguna.wajib_ganti_password') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <div class="text-xs font-bold text-ink">
                                        {{ __('pengguna.tabel.langkah_selesai', ['selesai' => $siswa->selesai_langkah, 'total' => $siswa->total_langkah]) }}
                                    </div>
                                    <div class="w-24 bg-gray-200 h-1.5 rounded-full mt-1 overflow-hidden">
                                        <div class="bg-green-700 h-1.5 rounded-full" style="width: {{ $siswa->total_langkah > 0 ? ($siswa->selesai_langkah / $siswa->total_langkah) * 100 : 0 }}%"></div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-xs text-muted">
                                    <div class="font-bold text-ink">{{ __('pengguna.tabel.jumlah_sesi', ['jumlah' => $siswa->jumlah_sesi]) }}</div>
                                    @if ($siswa->akses_terakhir)
                                        <div>{{ \Carbon\Carbon::parse($siswa->akses_terakhir)->setTimezone('Asia/Jakarta')->diffForHumans() }}</div>
                                    @else
                                        <div class="italic">{{ __('pengguna.tabel.belum_pernah_masuk') }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('admin.pengguna.show', $siswa) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-green-700 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-sm text-muted">
                                    Tidak ada data siswa yang cocok dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginasi -->
            @if ($daftarSiswa->hasPages())
                <div class="px-4 py-3 border-t border-border bg-bg">
                    {{ $daftarSiswa->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
