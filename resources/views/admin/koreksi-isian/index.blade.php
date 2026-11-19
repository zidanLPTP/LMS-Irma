<x-layouts.admin judul="Koreksi Nilai Isian Singkat">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-ink">Koreksi Nilai Isian Singkat</h1>
                <p class="text-sm text-muted mt-1">Periksa jawaban isian singkat siswa dan sesuaikan nilai dengan tombol persentase cepat atau input manual.</p>
            </div>
            @if (request()->hasAny(['cari', 'kuis_id', 'filter_poin']))
                <div>
                    <x-tombol tipe="garis" href="{{ route('admin.koreksi-isian.index') }}" class="!min-h-[38px] text-xs font-bold">
                        <x-ikon nama="rotate-ccw" ukuran="14" />
                        <span>Reset Semua Filter</span>
                    </x-tombol>
                </div>
            @endif
        </div>

        <!-- Filter & Pencarian -->
        <x-kartu class="p-4 sm:p-5">
            <form method="GET" action="{{ route('admin.koreksi-isian.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div>
                    <label for="cari" class="block text-xs font-bold text-muted mb-1">Cari Nama / Username</label>
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

                <div>
                    <label for="filter_poin" class="block text-xs font-bold text-muted mb-1">Status Nilai</label>
                    <select id="filter_poin" name="filter_poin"
                            class="w-full text-sm rounded-xl border border-border px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-green-700">
                        <option value="">Semua Jawaban</option>
                        <option value="nol" {{ request('filter_poin') === 'nol' ? 'selected' : '' }}>Nilai 0 Poin (Perlu Ditinjau)</option>
                        <option value="bernilai" {{ request('filter_poin') === 'bernilai' ? 'selected' : '' }}>Sudah Bernilai (> 0 Poin)</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <x-tombol tipe="utama" type="submit" class="!min-h-[40px] text-xs font-bold w-full sm:w-auto">
                        <x-ikon nama="search" ukuran="16" />
                        <span>Filter</span>
                    </x-tombol>

                    @if (request()->hasAny(['cari', 'kuis_id', 'filter_poin']))
                        <x-tombol tipe="garis" href="{{ route('admin.koreksi-isian.index') }}" class="!min-h-[40px] text-xs font-bold">
                            Reset
                        </x-tombol>
                    @endif
                </div>
            </form>
        </x-kartu>

        <!-- Daftar Jawaban Siswa -->
        @if ($daftarJawaban->isEmpty())
            <x-kartu class="p-10 text-center space-y-3">
                <div class="inline-flex p-3 rounded-full bg-green-50 text-green-700">
                    <x-ikon nama="check-circle" ukuran="36" />
                </div>
                <h3 class="text-base font-extrabold text-ink">Tidak Ada Jawaban Isian</h3>
                <p class="text-xs text-muted max-w-sm mx-auto">
                    Tidak ditemukan data jawaban isian singkat berdasarkan filter yang dipilih atau siswa belum mengerjakan kuis terkait.
                </p>
            </x-kartu>
        @else
            <div class="space-y-4">
                @foreach ($daftarJawaban as $jawaban)
                    @php
                        $soal = $jawaban->soal;
                        $bobot = (float) ($soal->bobot_poin ?? 1);
                        $poinSekarang = (float) ($jawaban->poin_diperoleh ?? 0);
                        $percobaan = $jawaban->percobaanKuis;
                        $user = $percobaan?->user;
                        $kuis = $percobaan?->kuis;
                        $kunciVariasi = $soal->pilihanJawaban->where('apakah_benar', true)->pluck('teks_pilihan')->all();
                    @endphp

                    <x-kartu class="p-4 sm:p-5 transition-shadow hover:shadow-md border border-border">
                        <!-- Header Kartu: Siswa & Kuis -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-3 border-b border-border/60 gap-2">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-sm text-ink">{{ $user->nama_lengkap ?? 'Siswa' }}</span>
                                <span class="text-xs text-muted font-mono">({{ $user->username ?? '-' }})</span>
                                @if ($user && $user->kelas)
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold bg-surface text-ink border border-border">
                                        Kelas {{ $user->kelas }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 text-xs text-muted">
                                <span class="font-medium text-green-800 bg-green-50 px-2 py-0.5 rounded-full border border-green-200">
                                    {{ $kuis->judul ?? 'Kuis' }} (Percobaan ke-{{ $percobaan->nomor_percobaan ?? 1 }})
                                </span>
                                <span>•</span>
                                <span>{{ $percobaan->waktu_selesai ? $percobaan->waktu_selesai->format('d M Y, H:i') : '-' }}</span>
                            </div>
                        </div>

                        <!-- Pertanyaan & Bobot -->
                        <div class="py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="text-xs font-bold text-muted uppercase tracking-wider mb-1">
                                    Soal Isian Singkat
                                </div>
                                <span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-amber-50 text-amber-900 border border-amber-200">
                                    Bobot Soal: {{ $bobot }} Poin
                                </span>
                            </div>
                            <div class="text-sm font-semibold text-ink bg-surface/50 p-3 rounded-xl border border-border/40">
                                {{ $soal->pertanyaan }}
                            </div>
                        </div>

                        <!-- Perbandingan Kunci vs Jawaban Siswa -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 py-2">
                            <!-- Kunci Jawaban Resmi -->
                            <div class="p-3 rounded-xl bg-green-50/60 border border-green-200">
                                <div class="text-xs font-bold text-green-800 mb-1 flex items-center gap-1.5">
                                    <x-ikon nama="check-circle" ukuran="14" class="text-green-700" />
                                    <span>Kunci Jawaban Resmi:</span>
                                </div>
                                @if (!empty($kunciVariasi))
                                    <div class="flex flex-wrap gap-1.5 mt-1">
                                        @foreach ($kunciVariasi as $kunci)
                                            <span class="px-2 py-0.5 rounded-md text-xs font-medium bg-white text-green-900 border border-green-300 shadow-xs">
                                                "{{ $kunci }}"
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-xs text-amber-800 italic">
                                        (Belum ada variasi kunci terdaftar di database untuk soal ini)
                                    </p>
                                @endif
                            </div>

                            <!-- Jawaban Siswa -->
                            <div class="p-3 rounded-xl bg-white border border-border">
                                <div class="text-xs font-bold text-ink mb-1 flex items-center gap-1.5">
                                    <x-ikon nama="user" ukuran="14" class="text-muted" />
                                    <span>Jawaban yang Diisi Siswa:</span>
                                </div>
                                <div class="mt-1 font-mono text-sm px-2.5 py-1.5 rounded-lg bg-surface border border-border text-ink font-semibold">
                                    {{ $jawaban->jawaban_teks !== null && trim($jawaban->jawaban_teks) !== '' ? $jawaban->jawaban_teks : '(Tidak Menjawab / Kosong)' }}
                                </div>
                            </div>
                        </div>

                        <!-- Baris Penilaian & Tombol Cepat -->
                        <div class="mt-3 pt-3 border-t border-border/60 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                            <!-- Status Poin Saat Ini -->
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-muted">Nilai Saat Ini:</span>
                                @if ($poinSekarang >= $bobot)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-300">
                                        <x-ikon nama="check" ukuran="12" />
                                        <span>{{ $poinSekarang }} / {{ $bobot }} Poin (100%)</span>
                                    </span>
                                @elseif ($poinSekarang > 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                        <x-ikon nama="alert-circle" ukuran="12" />
                                        <span>{{ $poinSekarang }} / {{ $bobot }} Poin (Parsial)</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-300">
                                        <x-ikon nama="x" ukuran="12" />
                                        <span>0 / {{ $bobot }} Poin</span>
                                    </span>
                                @endif

                                @if ($jawaban->dinilai_pada)
                                    <span class="text-[11px] text-muted hidden sm:inline">
                                        (diperbarui {{ $jawaban->dinilai_pada->diffForHumans() }})
                                    </span>
                                @endif
                            </div>

                            <!-- Tombol Cepat & Input Manual -->
                            <form method="POST" action="{{ route('admin.koreksi-isian.simpan', $jawaban) }}" class="flex flex-wrap items-center gap-1.5" x-data="{ poinManual: '{{ $poinSekarang }}' }">
                                @csrf
                                <span class="text-xs font-bold text-muted mr-1">Beri Nilai Cepat:</span>

                                <!-- Tombol Cepat 100% -->
                                <button type="submit" name="poin" value="{{ $bobot }}"
                                        title="Beri nilai penuh ({{ $bobot }} poin)"
                                        class="px-2.5 py-1 text-xs font-bold rounded-lg border border-green-600 bg-green-50 text-green-800 hover:bg-green-600 hover:text-white transition-colors cursor-pointer">
                                    100% ({{ $bobot }})
                                </button>

                                <!-- Tombol Cepat 80% -->
                                @php $poin80 = round($bobot * 0.8, 2); @endphp
                                <button type="submit" name="poin" value="{{ $poin80 }}"
                                        title="Beri nilai 80% ({{ $poin80 }} poin)"
                                        class="px-2.5 py-1 text-xs font-bold rounded-lg border border-teal-600 bg-teal-50 text-teal-800 hover:bg-teal-600 hover:text-white transition-colors cursor-pointer">
                                    80% ({{ $poin80 }})
                                </button>

                                <!-- Tombol Cepat 50% -->
                                @php $poin50 = round($bobot * 0.5, 2); @endphp
                                <button type="submit" name="poin" value="{{ $poin50 }}"
                                        title="Beri nilai 50% ({{ $poin50 }} poin)"
                                        class="px-2.5 py-1 text-xs font-bold rounded-lg border border-amber-600 bg-amber-50 text-amber-800 hover:bg-amber-600 hover:text-white transition-colors cursor-pointer">
                                    50% ({{ $poin50 }})
                                </button>

                                <!-- Tombol Cepat 0% -->
                                <button type="submit" name="poin" value="0"
                                        title="Beri nilai 0 poin"
                                        class="px-2.5 py-1 text-xs font-bold rounded-lg border border-red-300 bg-red-50 text-red-700 hover:bg-red-600 hover:text-white transition-colors cursor-pointer">
                                    0% (0)
                                </button>

                                <!-- Input Angka Manual -->
                                <div class="inline-flex items-center gap-1 ml-1 pl-2 border-l border-border">
                                    <input type="number" name="poin" x-model="poinManual"
                                           min="0" max="{{ $bobot }}" step="0.1"
                                           class="w-16 text-center text-xs font-bold rounded-lg border border-border px-1.5 py-1 bg-white focus:outline-none focus:ring-1 focus:ring-green-700"
                                           aria-label="Poin manual">
                                    <button type="submit"
                                            class="px-2.5 py-1 text-xs font-bold rounded-lg bg-green-700 text-white hover:bg-green-800 transition-colors cursor-pointer">
                                        Simpan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </x-kartu>
                @endforeach

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $daftarJawaban->links() }}
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
