<x-layouts.admin judul="Antrean Penilaian Esai">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-ink">Antrean Penilaian Esai</h1>
                <p class="text-sm text-muted mt-1">Daftar pengerjaan kuis siswa yang menunggu penilaian uraian manual.</p>
            </div>
        </div>

        <!-- Filter & Pencarian -->
        <x-kartu class="p-4 sm:p-5">
            <form method="GET" action="{{ route('admin.penilaian-esai.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="cari" class="block text-xs font-bold text-muted mb-1">Cari Nama Siswa / Username</label>
                    <input type="text" id="cari" name="cari" value="{{ request('cari') }}"
                           placeholder="Ketik nama siswa..."
                           class="w-full text-sm rounded-xl border border-border px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-green-700">
                </div>

                <div>
                    <label for="kuis_id" class="block text-xs font-bold text-muted mb-1">Filter Kuis</label>
                    <select id="kuis_id" name="kuis_id"
                            class="w-full text-sm rounded-xl border border-border px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-green-700">
                        <option value="">Semua Kuis</option>
                        @foreach ($daftarKuis as $k)
                            <option value="{{ $k->id }}" {{ request('kuis_id') == $k->id ? 'selected' : '' }}>
                                {{ $k->judul }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <x-tombol tipe="utama" type="submit" class="!min-h-[40px] text-xs font-bold w-full sm:w-auto">
                        <x-ikon nama="search" ukuran="16" />
                        <span>Filter</span>
                    </x-tombol>

                    @if (request()->hasAny(['cari', 'kuis_id']))
                        <x-tombol tipe="garis" href="{{ route('admin.penilaian-esai.index') }}" class="!min-h-[40px] text-xs font-bold">
                            Reset
                        </x-tombol>
                    @endif
                </div>
            </form>
        </x-kartu>

        <!-- Tabel Antrean (Mobile-First Card / Table) -->
        @if ($daftarPercobaan->isEmpty())
            <x-kartu class="p-10 text-center space-y-3">
                <div class="inline-flex p-3 rounded-full bg-green-50 text-green-700">
                    <x-ikon nama="check-circle" ukuran="36" />
                </div>
                <h3 class="text-base font-extrabold text-ink">Semua Esai Telah Dinilai</h3>
                <p class="text-xs text-muted max-w-sm mx-auto">
                    {{ trans('kuis.antrean_kosong') }}
                </p>
            </x-kartu>
        @else
            <!-- Tampilan Desktop (Tabel) -->
            <div class="hidden md:block bg-white rounded-2xl border border-border overflow-hidden shadow-[0_1px_3px_rgba(31,45,42,0.08)]">
                <table class="w-full text-left text-sm">
                    <thead class="bg-surface border-b border-border text-xs font-extrabold text-muted uppercase">
                        <tr>
                            <th class="px-5 py-3.5">Siswa</th>
                            <th class="px-5 py-3.5">Kelas</th>
                            <th class="px-5 py-3.5">Kuis</th>
                            <th class="px-5 py-3.5">Waktu Selesai</th>
                            <th class="px-5 py-3.5 text-center">Esai Belum Dinilai</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($daftarPercobaan as $p)
                            @php
                                $belumDinilai = $p->jawabanSiswa->filter(fn ($j) => $j->soal?->tipe_soal === 'esai' && is_null($j->dinilai_pada))->count();
                            @endphp
                            <tr class="hover:bg-green-50/30 transition-colors">
                                <td class="px-5 py-4">
                                    <span class="font-extrabold text-ink block">{{ $p->user->nama_lengkap }}</span>
                                    <span class="text-xs text-muted font-mono">{{ $p->user->username }}</span>
                                </td>
                                <td class="px-5 py-4 text-xs font-bold text-muted">
                                    {{ $p->user->kelas ?? '-' }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-bold text-ink block">{{ $p->kuis->judul }}</span>
                                    <span class="text-xs text-muted">Percobaan ke-{{ $p->nomor_percobaan }}</span>
                                </td>
                                <td class="px-5 py-4 text-xs text-muted">
                                    {{ $p->waktu_selesai ? $p->waktu_selesai->translatedFormat('d M Y, H:i') : '-' }}
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-amber-50 text-amber-700 border border-amber-200">
                                        {{ $belumDinilai }} Soal
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <x-tombol tipe="utama" href="{{ route('admin.penilaian-esai.show', $p) }}" class="!min-h-[36px] !px-4 text-xs font-bold">
                                        <span>Beri Nilai</span>
                                        <x-ikon nama="arrow-right" ukuran="14" />
                                    </x-tombol>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Tampilan Mobile (Card) -->
            <div class="md:hidden space-y-3">
                @foreach ($daftarPercobaan as $p)
                    @php
                        $belumDinilai = $p->jawabanSiswa->filter(fn ($j) => $j->soal?->tipe_soal === 'esai' && is_null($j->dinilai_pada))->count();
                    @endphp
                    <x-kartu class="p-4 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-extrabold text-ink">{{ $p->user->nama_lengkap }}</h3>
                                <p class="text-xs text-muted font-bold mt-0.5">{{ $p->user->kelas ?? 'Kelas tidak disetel' }}</p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-black bg-amber-50 text-amber-700 border border-amber-200">
                                {{ $belumDinilai }} Esai
                            </span>
                        </div>

                        <div class="pt-2 border-t border-border text-xs space-y-1">
                            <p class="font-bold text-ink">{{ $p->kuis->judul }}</p>
                            <p class="text-muted">Diserahkan: {{ $p->waktu_selesai ? $p->waktu_selesai->translatedFormat('d M Y, H:i') : '-' }}</p>
                        </div>

                        <div class="pt-3 border-t border-border">
                            <x-tombol tipe="utama" href="{{ route('admin.penilaian-esai.show', $p) }}" class="w-full justify-center !min-h-[44px] text-xs font-bold">
                                <span>Beri Nilai Esai</span>
                                <x-ikon nama="arrow-right" ukuran="16" />
                            </x-tombol>
                        </div>
                    </x-kartu>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $daftarPercobaan->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
