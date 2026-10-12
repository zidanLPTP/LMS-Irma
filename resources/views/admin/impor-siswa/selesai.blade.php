<x-layouts.admin judul="Hasil Impor Siswa">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-ink">Impor Siswa Berhasil Disimpan</h1>
                <p class="text-sm text-muted mt-1">
                    Sebanyak <strong>{{ count($hasilAkun) }}</strong> akun siswa telah berhasil dibuat.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.impor-siswa.unduh-akun') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-green-700 text-white hover:bg-green-800 shadow-sm transition-colors">
                    <x-ikon nama="download" ukuran="16" />
                    <span>Unduh Berkas Excel (.xlsx)</span>
                </a>
                <a href="{{ route('admin.pengguna.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-white text-ink border border-border hover:bg-surface">
                    <span>Kembali ke Daftar Pengguna</span>
                </a>
            </div>
        </div>

        <div class="p-4 bg-amber-50 rounded-2xl border border-amber-300 text-xs space-y-1.5 text-amber-900">
            <div class="flex items-center gap-2 font-black text-sm">
                <x-ikon nama="alert-triangle" ukuran="18" class="text-amber-700" />
                <span>Penting: Simpan Kata Sandi Sekarang!</span>
            </div>
            <p>
                Daftar kata sandi sementara berikut ini <strong>HANYA DITAMPILKAN SATU KALI</strong> pada sesi ini. Demi keamanan penelitian, kata sandi sementara ini tidak disimpan dalam bentuk teks biasa di basis data maupun berkas log, sehingga tidak bisa dilihat lagi setelah Anda meninggalkan halaman ini.
            </p>
        </div>

        <x-kartu class="p-6 space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-border text-muted font-bold uppercase">
                            <th class="py-2.5 px-3">No</th>
                            <th class="py-2.5 px-3">Nama Lengkap</th>
                            <th class="py-2.5 px-3">Username</th>
                            <th class="py-2.5 px-3">Email</th>
                            <th class="py-2.5 px-3">Kelas</th>
                            <th class="py-2.5 px-3">Kata Sandi Sementara</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($hasilAkun as $index => $akun)
                            <tr>
                                <td class="py-2.5 px-3 font-bold">{{ $index + 1 }}</td>
                                <td class="py-2.5 px-3 font-bold text-ink">{{ $akun['nama_lengkap'] }}</td>
                                <td class="py-2.5 px-3 font-mono">{{ $akun['username'] }}</td>
                                <td class="py-2.5 px-3">{{ $akun['email'] }}</td>
                                <td class="py-2.5 px-3">{{ $akun['kelas'] ?? '-' }}</td>
                                <td class="py-2.5 px-3">
                                    <span class="px-2 py-1 bg-amber-100 font-mono font-black text-amber-900 rounded-md border border-amber-200">
                                        {{ $akun['kata_sandi_sementara'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-kartu>
    </div>
</x-layouts.admin>
