<x-layouts.admin judul="Pemeriksaan Akun Ganda">
    <div class="space-y-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.pengguna.index') }}" class="p-2 rounded-xl border border-border bg-white text-muted hover:text-ink">
                <x-ikon nama="arrow-left" ukuran="18" />
            </a>
            <div>
                <h1 class="text-2xl font-black text-ink">{{ __('pengguna.akun_ganda.judul') }}</h1>
                <p class="text-sm text-muted">{{ __('pengguna.akun_ganda.deskripsi') }}</p>
            </div>
        </div>

        <!-- Catatan Penundaan Fitur Gabung Akun -->
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 text-xs text-blue-900 flex items-start gap-3">
            <div class="p-1.5 bg-blue-100 rounded-lg shrink-0 text-blue-700">
                <x-ikon nama="info" ukuran="16" />
            </div>
            <div>
                <span class="font-bold block">Pemberitahuan:</span>
                {{ __('pengguna.akun_ganda.peringatan_gabung') }}
            </div>
        </div>

        @if ($grupAkunGanda->isEmpty())
            <div class="bg-white rounded-2xl border border-border p-12 text-center shadow-sm">
                <div class="w-12 h-12 rounded-full bg-green-100 text-green-700 flex items-center justify-center mx-auto mb-3">
                    <x-ikon nama="check" ukuran="24" />
                </div>
                <h2 class="text-base font-extrabold text-ink">Semua Nama Siswa Unik</h2>
                <p class="text-xs text-muted mt-1">{{ __('pengguna.akun_ganda.tidak_ditemukan') }}</p>
            </div>
        @else
            <div class="space-y-6">
                @foreach ($grupAkunGanda as $namaLower => $akunList)
                    <div class="bg-white rounded-2xl border border-border shadow-sm p-5 space-y-3">
                        <div class="flex items-center justify-between border-b border-border pb-2">
                            <h2 class="text-base font-black text-ink">Nama: "{{ $akunList->first()->nama_lengkap }}"</h2>
                            <span class="text-xs font-bold text-amber-800 bg-amber-100 px-2.5 py-0.5 rounded-full">
                                {{ $akunList->count() }} Akun Terdeteksi
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-bg text-muted font-black border-b border-border">
                                        <th class="py-2.5 px-3">Username</th>
                                        <th class="py-2.5 px-3">Email</th>
                                        <th class="py-2.5 px-3">Kelas</th>
                                        <th class="py-2.5 px-3">Aktivitas Data</th>
                                        <th class="py-2.5 px-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    @foreach ($akunList as $akun)
                                        @php
                                            $punyaData = ($akun->percobaan_kuis_count > 0 || $akun->progres_materi_count > 0 || $akun->log_akses_count > 0);
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-2.5 px-3 font-bold text-ink">
                                                <a href="{{ route('admin.pengguna.show', $akun) }}" class="hover:text-green-700">
                                                    {{ $akun->username }}
                                                </a>
                                            </td>
                                            <td class="py-2.5 px-3 text-muted">{{ $akun->email }}</td>
                                            <td class="py-2.5 px-3 font-semibold">{{ $akun->kelas ?? '-' }}</td>
                                            <td class="py-2.5 px-3">
                                                @if ($punyaData)
                                                    <span class="inline-flex items-center gap-1 text-ink font-semibold">
                                                        <span>{{ $akun->percobaan_kuis_count }} kuis</span> •
                                                        <span>{{ $akun->log_akses_count }} sesi</span>
                                                    </span>
                                                @else
                                                    <span class="inline-block px-2 py-0.5 bg-gray-100 text-muted rounded text-[11px] font-bold">
                                                        Kosong (Tanpa Data)
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-3 text-right">
                                                @if (! $punyaData)
                                                    <form method="POST" action="{{ route('admin.pengguna.destroy', $akun) }}" onsubmit="return confirm('{{ __('pengguna.akun_ganda.hapus_konfirmasi') }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold bg-red-50 text-red-700 hover:bg-red-100 rounded-lg transition-colors">
                                                            <x-ikon nama="trash-2" ukuran="12" />
                                                            <span>Hapus Akun Kosong</span>
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-xs text-muted italic" title="Akun yang memiliki data tidak dapat dihapus">
                                                        Tidak bisa dihapus
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.admin>
