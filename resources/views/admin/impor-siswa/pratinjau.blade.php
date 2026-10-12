<x-layouts.admin judul="Pratinjau Impor Siswa">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <a href="{{ route('admin.impor-siswa.create') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-green-700 hover:underline mb-1">
                    <x-ikon nama="chevron-left" ukuran="16" />
                    <span>Kembali ke Unggah Berkas</span>
                </a>
                <h1 class="text-2xl font-extrabold text-ink">Pratinjau Validasi Impor Siswa</h1>
                <p class="text-sm text-muted mt-0.5">
                    Berkas: <strong>{{ $hasil['nama_berkas'] }}</strong> &bull; Waktu proses: <strong>{{ $hasil['durasi_ms'] }} ms</strong>
                </p>
            </div>

            <div class="flex items-center gap-2">
                @if (! empty($hasil['baris_valid']))
                    <form method="POST" action="{{ route('admin.impor-siswa.konfirmasi') }}">
                        @csrf
                        <x-tombol tipe="utama" type="submit" class="!px-6">
                            <x-ikon nama="check" ukuran="16" />
                            <span>Konfirmasi Impor ({{ count($hasil['baris_valid']) }} Siswa Valid)</span>
                        </x-tombol>
                    </form>
                @endif
            </div>
        </div>

        <!-- Kartu Ringkasan Validasi -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-kartu class="p-4 border border-border">
                <span class="text-xs text-muted font-bold">Total Baris Terbaca</span>
                <span class="block text-xl font-extrabold text-ink mt-1">{{ $hasil['total_baris'] }}</span>
            </x-kartu>
            <x-kartu class="p-4 border border-green-200 bg-green-50/50">
                <span class="text-xs text-green-700 font-bold">Baris Valid (Siap Disimpan)</span>
                <span class="block text-xl font-extrabold text-green-800 mt-1">{{ count($hasil['baris_valid']) }}</span>
            </x-kartu>
            <x-kartu class="p-4 border border-rose-200 bg-rose-50/50">
                <span class="text-xs text-rose-700 font-bold">Baris Bermasalah (Akan Dilewati)</span>
                <span class="block text-xl font-extrabold text-rose-800 mt-1">{{ count($hasil['baris_galat']) }}</span>
            </x-kartu>
        </div>

        @if (! empty($hasil['baris_galat']))
            <x-kartu class="p-6 border border-rose-200 bg-rose-50/20 space-y-4">
                <div class="flex items-center gap-2 text-rose-800 font-extrabold text-sm">
                    <x-ikon nama="alert-circle" ukuran="20" class="text-rose-600" />
                    <span>Daftar Baris yang Mengalami Galat (Tidak Akan Diimpor)</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-rose-200 text-rose-900 font-bold">
                                <th class="py-2 px-3">Baris</th>
                                <th class="py-2 px-3">Nama Lengkap</th>
                                <th class="py-2 px-3">Username</th>
                                <th class="py-2 px-3">Email</th>
                                <th class="py-2 px-3">Alasan Galat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-rose-100">
                            @foreach ($hasil['baris_galat'] as $g)
                                <tr>
                                    <td class="py-2 px-3 font-bold">{{ $g['nomor_baris'] }}</td>
                                    <td class="py-2 px-3">{{ $g['nama_lengkap'] ?: '-' }}</td>
                                    <td class="py-2 px-3 font-mono">{{ $g['username'] ?: '-' }}</td>
                                    <td class="py-2 px-3">{{ $g['email'] ?: '-' }}</td>
                                    <td class="py-2 px-3 text-rose-700 font-bold">{{ $g['alasan'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-kartu>
        @endif

        @if (! empty($hasil['baris_valid']))
            <x-kartu class="p-6 space-y-4">
                <h2 class="text-base font-extrabold text-ink">Daftar Baris Valid (Akan Dibuatkan Akun)</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-border text-muted font-bold uppercase">
                                <th class="py-2 px-3">Baris</th>
                                <th class="py-2 px-3">Nama Lengkap</th>
                                <th class="py-2 px-3">Username</th>
                                <th class="py-2 px-3">Email</th>
                                <th class="py-2 px-3">Kelas</th>
                                <th class="py-2 px-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($hasil['baris_valid'] as $v)
                                <tr>
                                    <td class="py-2 px-3 font-bold">{{ $v['nomor_baris'] }}</td>
                                    <td class="py-2 px-3 font-bold text-ink">{{ $v['nama_lengkap'] }}</td>
                                    <td class="py-2 px-3 font-mono">{{ $v['username'] }}</td>
                                    <td class="py-2 px-3">{{ $v['email'] }}</td>
                                    <td class="py-2 px-3">{{ $v['kelas'] ?? '-' }}</td>
                                    <td class="py-2 px-3">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-black bg-green-100 text-green-800">
                                            Valid
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-kartu>
        @endif
    </div>
</x-layouts.admin>
