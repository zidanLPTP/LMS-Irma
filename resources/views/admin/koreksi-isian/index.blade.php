<x-layouts.admin judul="Koreksi Nilai Isian Singkat">
    <div class="space-y-6">
        <!-- Header Halaman -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-ink tracking-tight">Koreksi Nilai Isian Singkat</h1>
                <p class="text-sm text-muted mt-0.5">Tinjau kesesuaian jawaban teks siswa dan berikan penyesuaian nilai secara instan.</p>
            </div>
            @if (request()->hasAny(['cari', 'kuis_id', 'filter_poin']))
                <a href="{{ route('admin.koreksi-isian.index') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-border text-xs font-bold text-muted hover:text-ink hover:bg-surface transition-colors self-start sm:self-auto">
                    <x-ikon nama="rotate-ccw" ukuran="14" />
                    <span>Reset Semua Filter</span>
                </a>
            @endif
        </div>

        <!-- Filter & Pencarian -->
        <x-kartu class="p-4 sm:p-5">
            <form method="GET" action="{{ route('admin.koreksi-isian.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div>
                    <label for="cari" class="block text-xs font-bold text-muted mb-1">Cari Siswa / Username</label>
                    <input type="text" id="cari" name="cari" value="{{ request('cari') }}"
                           placeholder="Ketik nama atau username..."
                           class="w-full text-xs rounded-xl border border-border px-3 py-2 bg-white text-ink placeholder:text-muted/60 focus:outline-none focus:ring-2 focus:ring-green-700">
                </div>

                <div>
                    <label for="kuis_id" class="block text-xs font-bold text-muted mb-1">Filter Kuis</label>
                    <select id="kuis_id" name="kuis_id"
                            class="w-full text-xs rounded-xl border border-border px-3 py-2 bg-white text-ink focus:outline-none focus:ring-2 focus:ring-green-700">
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
                            class="w-full text-xs rounded-xl border border-border px-3 py-2 bg-white text-ink focus:outline-none focus:ring-2 focus:ring-green-700">
                        <option value="">Semua Jawaban</option>
                        <option value="nol" {{ request('filter_poin') === 'nol' ? 'selected' : '' }}>Nilai 0 Poin (Perlu Ditinjau)</option>
                        <option value="bernilai" {{ request('filter_poin') === 'bernilai' ? 'selected' : '' }}>Sudah Ada Poin (> 0 Poin)</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2 bg-green-700 text-white font-bold text-xs hover:bg-[#25664a] transition-colors cursor-pointer w-full sm:w-auto h-[38px] shadow-xs">
                        <x-ikon nama="search" ukuran="15" />
                        <span>Filter</span>
                    </button>

                    @if (request()->hasAny(['cari', 'kuis_id', 'filter_poin']))
                        <a href="{{ route('admin.koreksi-isian.index') }}"
                           class="inline-flex items-center justify-center rounded-xl px-3 py-2 border border-border text-xs font-bold text-muted hover:text-ink hover:bg-surface transition-colors h-[38px]">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </x-kartu>

        <!-- Daftar Jawaban Siswa -->
        @if ($daftarJawaban->isEmpty())
            <x-kartu class="p-12 text-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-green-50 text-green-700 flex items-center justify-center mx-auto">
                    <x-ikon nama="check-circle" ukuran="24" />
                </div>
                <h3 class="text-base font-bold text-ink">Tidak Ada Data Jawaban</h3>
                <p class="text-xs text-muted max-w-sm mx-auto">
                    Tidak ditemukan jawaban isian singkat siswa berdasarkan filter yang dipilih, atau belum ada siswa yang mengerjakan kuis.
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
                        $inisial = $user ? mb_substr($user->nama_lengkap ?? 'S', 0, 2) : 'S';
                    @endphp

                    <x-kartu class="p-5 sm:p-6 transition-all hover:border-green-300">
                        <!-- 1. Header Kartu: Profil Siswa & Info Kuis -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3.5 border-b border-border/70 gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-green-100 text-green-800 font-extrabold text-xs flex items-center justify-center shrink-0 uppercase tracking-wider">
                                    {{ $inisial }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-sm text-ink">{{ $user->nama_lengkap ?? 'Siswa' }}</span>
                                        <span class="text-xs text-muted font-mono">({{ $user->username ?? '-' }})</span>
                                        @if ($user && $user->kelas)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-surface text-muted border border-border">
                                                Kelas {{ $user->kelas }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 text-xs text-muted mt-0.5">
                                        <span>Percobaan ke-{{ $percobaan->nomor_percobaan ?? 1 }}</span>
                                        <span>•</span>
                                        <span>{{ $percobaan->waktu_selesai ? $percobaan->waktu_selesai->format('d M Y, H:i') : '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 self-start sm:self-auto">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                                    {{ $kuis->judul ?? 'Kuis' }}
                                </span>
                            </div>
                        </div>

                        <!-- 2. Teks Pertanyaan & Bobot Nilai -->
                        <div class="py-3.5">
                            <div class="bg-surface/50 rounded-xl p-3.5 border border-border/70 space-y-1.5">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-muted flex items-center gap-1.5">
                                        <x-ikon nama="clipboard-list" ukuran="13" class="text-green-700" />
                                        <span>Soal Isian Singkat #{{ $soal->urutan }}</span>
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-white text-ink border border-border shadow-2xs">
                                        Bobot: {{ $bobot }} Poin
                                    </span>
                                </div>
                                <p class="text-sm font-semibold text-ink leading-relaxed">
                                    {{ $soal->pertanyaan }}
                                </p>
                            </div>
                        </div>

                        <!-- 3. Komparasi 2 Kolom: Kunci Resmi vs Jawaban Siswa -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pb-3.5">
                            <!-- Kolom Kiri: Kunci Resmi -->
                            <div class="bg-green-50/60 rounded-xl p-3.5 border border-green-200/90 space-y-2">
                                <span class="text-xs font-bold text-green-800 flex items-center gap-1.5">
                                    <x-ikon nama="check-circle" ukuran="14" class="text-green-700" />
                                    <span>Kunci Jawaban Resmi:</span>
                                </span>
                                @if (!empty($kunciVariasi))
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($kunciVariasi as $kunci)
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-white text-green-900 border border-green-300 shadow-2xs">
                                                {{ $kunci }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-xs text-amber-800 italic">
                                        (Belum ada variasi kunci jawaban terdaftar pada bank soal)
                                    </p>
                                @endif
                            </div>

                            <!-- Kolom Kanan: Jawaban Siswa -->
                            <div class="bg-white rounded-xl p-3.5 border border-border space-y-2">
                                <span class="text-xs font-bold text-ink flex items-center gap-1.5">
                                    <x-ikon nama="user" ukuran="14" class="text-muted" />
                                    <span>Jawaban Teks Siswa:</span>
                                </span>
                                <div class="p-2.5 rounded-lg bg-surface/50 border border-border/80 font-mono text-xs font-semibold text-ink">
                                    @if ($jawaban->jawaban_teks !== null && trim($jawaban->jawaban_teks) !== '')
                                        {{ $jawaban->jawaban_teks }}
                                    @else
                                        <span class="text-muted italic font-sans font-normal">(Siswa tidak mengisi jawaban / kosong)</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- 4. Toolbar Penilaian Cepat (Rapi & Minimalis) -->
                        <div class="pt-3 border-t border-border/70 bg-[#F7FBF9] -mx-5 -mb-5 sm:-mx-6 sm:-mb-6 p-4 sm:px-6 rounded-b-2xl flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <!-- Status Poin Saat Ini -->
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-muted">Nilai:</span>
                                @if ($poinSekarang >= $bobot)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-300">
                                        <x-ikon nama="check" ukuran="12" />
                                        <span>{{ $poinSekarang }} / {{ $bobot }} Poin (Penuh)</span>
                                    </span>
                                @elseif ($poinSekarang > 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-100 text-sky-800 border border-sky-300">
                                        <x-ikon nama="alert-triangle" ukuran="12" />
                                        <span>{{ $poinSekarang }} / {{ $bobot }} Poin (Parsial)</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-50 text-danger border border-red-200">
                                        <x-ikon nama="x" ukuran="12" />
                                        <span>0 / {{ $bobot }} Poin</span>
                                    </span>
                                @endif

                                @if ($jawaban->dinilai_pada)
                                    <span class="text-[11px] text-muted hidden lg:inline">
                                        (diperbarui {{ $jawaban->dinilai_pada->diffForHumans() }})
                                    </span>
                                @endif
                            </div>

                            <!-- Tombol Cepat Nilai & Form Input -->
                            <form method="POST" action="{{ route('admin.koreksi-isian.simpan', $jawaban) }}" class="flex flex-wrap items-center gap-1.5" x-data="{ poinManual: '{{ $poinSekarang }}' }">
                                @csrf
                                <span class="text-xs font-bold text-muted mr-1 hidden sm:inline">Set Nilai:</span>

                                <!-- Tombol Cepat 100% -->
                                <button type="submit" name="poin" value="{{ $bobot }}"
                                        title="Beri nilai penuh ({{ $bobot }} poin)"
                                        class="px-2.5 py-1 text-xs font-bold rounded-lg border border-green-700 bg-green-700 text-white hover:bg-[#25664a] transition-colors cursor-pointer shadow-2xs">
                                    100% ({{ $bobot }})
                                </button>

                                <!-- Tombol Cepat 80% -->
                                @php $poin80 = round($bobot * 0.8, 2); @endphp
                                <button type="submit" name="poin" value="{{ $poin80 }}"
                                        title="Beri nilai 80% ({{ $poin80 }} poin)"
                                        class="px-2.5 py-1 text-xs font-bold rounded-lg border border-green-300 bg-white text-green-800 hover:bg-green-50 transition-colors cursor-pointer shadow-2xs">
                                    80% ({{ $poin80 }})
                                </button>

                                <!-- Tombol Cepat 50% -->
                                @php $poin50 = round($bobot * 0.5, 2); @endphp
                                <button type="submit" name="poin" value="{{ $poin50 }}"
                                        title="Beri nilai 50% ({{ $poin50 }} poin)"
                                        class="px-2.5 py-1 text-xs font-bold rounded-lg border border-amber-300 bg-white text-amber-900 hover:bg-amber-50 transition-colors cursor-pointer shadow-2xs">
                                    50% ({{ $poin50 }})
                                </button>

                                <!-- Tombol Cepat 0% -->
                                <button type="submit" name="poin" value="0"
                                        title="Beri nilai 0 poin"
                                        class="px-2.5 py-1 text-xs font-bold rounded-lg border border-red-200 bg-white text-danger hover:bg-red-50 transition-colors cursor-pointer shadow-2xs">
                                    0%
                                </button>

                                <!-- Input Angka Manual -->
                                <div class="inline-flex items-center gap-1 ml-1 pl-2 border-l border-border/80">
                                    <input type="number" name="poin" x-model="poinManual"
                                           min="0" max="{{ $bobot }}" step="0.1"
                                           class="w-16 text-center text-xs font-bold rounded-lg border border-border px-1.5 py-1 bg-white text-ink focus:outline-none focus:ring-1 focus:ring-green-700"
                                           aria-label="Poin manual">
                                    <button type="submit"
                                            class="px-2.5 py-1 text-xs font-bold rounded-lg bg-surface border border-border text-ink hover:bg-green-100 hover:text-green-800 hover:border-green-300 transition-colors cursor-pointer">
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
