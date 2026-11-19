<x-layouts.admin judul="Koreksi Nilai Isian Singkat">
    <div class="space-y-6">
        <!-- Header Halaman -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-ink tracking-tight">Koreksi Nilai Isian Singkat</h1>
                <p class="text-sm text-muted mt-1">Tinjau kesesuaian jawaban teks siswa dan sesuaikan nilai dengan cepat tanpa beban garis kaku.</p>
            </div>
            @if (request()->hasAny(['cari', 'kuis_id', 'filter_poin']))
                <a href="{{ route('admin.koreksi-isian.index') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white text-xs font-bold text-muted hover:text-ink hover:bg-surface shadow-[0_1px_3px_rgba(0,0,0,0.05)] transition-all self-start sm:self-auto">
                    <x-ikon nama="rotate-ccw" ukuran="14" />
                    <span>Reset Semua Filter</span>
                </a>
            @endif
        </div>

        <!-- Filter & Pencarian (Card Bersih Tanpa Border Kaku) -->
        <div class="bg-white rounded-2xl p-5 shadow-[0_2px_8px_rgba(31,45,42,0.04)]">
            <form method="GET" action="{{ route('admin.koreksi-isian.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3.5">
                <div>
                    <label for="cari" class="block text-xs font-bold text-muted mb-1.5">Cari Siswa / Username</label>
                    <input type="text" id="cari" name="cari" value="{{ request('cari') }}"
                           placeholder="Ketik nama atau username..."
                           class="w-full text-xs rounded-xl bg-[#F8FAF9] border-0 px-3.5 py-2.5 text-ink placeholder:text-muted/50 focus:bg-white focus:ring-2 focus:ring-green-700 transition-all outline-none">
                </div>

                <div>
                    <label for="kuis_id" class="block text-xs font-bold text-muted mb-1.5">Filter Kuis</label>
                    <select id="kuis_id" name="kuis_id"
                            class="w-full text-xs rounded-xl bg-[#F8FAF9] border-0 px-3.5 py-2.5 text-ink focus:bg-white focus:ring-2 focus:ring-green-700 transition-all outline-none">
                        <option value="">Semua Kuis</option>
                        @foreach ($daftarKuis as $k)
                            <option value="{{ $k->id }}" {{ request('kuis_id') == $k->id ? 'selected' : '' }}>
                                {{ $k->judul }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_poin" class="block text-xs font-bold text-muted mb-1.5">Status Nilai</label>
                    <select id="filter_poin" name="filter_poin"
                            class="w-full text-xs rounded-xl bg-[#F8FAF9] border-0 px-3.5 py-2.5 text-ink focus:bg-white focus:ring-2 focus:ring-green-700 transition-all outline-none">
                        <option value="">Semua Jawaban</option>
                        <option value="nol" {{ request('filter_poin') === 'nol' ? 'selected' : '' }}>Nilai 0 Poin (Perlu Ditinjau)</option>
                        <option value="bernilai" {{ request('filter_poin') === 'bernilai' ? 'selected' : '' }}>Sudah Ada Poin (> 0 Poin)</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 bg-green-700 text-white font-bold text-xs hover:bg-[#25664a] transition-all cursor-pointer w-full sm:w-auto h-[40px] shadow-sm">
                        <x-ikon nama="search" ukuran="15" />
                        <span>Filter</span>
                    </button>

                    @if (request()->hasAny(['cari', 'kuis_id', 'filter_poin']))
                        <a href="{{ route('admin.koreksi-isian.index') }}"
                           class="inline-flex items-center justify-center rounded-xl px-3 py-2.5 bg-[#F1F5F3] text-xs font-bold text-muted hover:text-ink hover:bg-[#E8EFEA] transition-colors h-[40px]">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Daftar Jawaban Siswa -->
        @if ($daftarJawaban->isEmpty())
            <div class="bg-white rounded-2xl p-12 text-center space-y-3 shadow-[0_2px_8px_rgba(31,45,42,0.04)]">
                <div class="w-12 h-12 rounded-full bg-[#E8F5EE] text-green-700 flex items-center justify-center mx-auto">
                    <x-ikon nama="check-circle" ukuran="24" />
                </div>
                <h3 class="text-base font-bold text-ink">Tidak Ada Data Jawaban</h3>
                <p class="text-xs text-muted max-w-sm mx-auto">
                    Tidak ditemukan jawaban isian singkat siswa berdasarkan filter yang dipilih, atau belum ada siswa yang mengerjakan kuis.
                </p>
            </div>
        @else
            <div class="space-y-5">
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

                    <!-- Kartu Siswa: Bebas Border Kaku, Mengandalkan Subtle Shadow & Background Tint -->
                    <div class="bg-white rounded-2xl p-6 shadow-[0_2px_12px_rgba(31,45,42,0.04)] hover:shadow-[0_4px_20px_rgba(31,45,42,0.07)] transition-all space-y-4">
                        <!-- 1. Header: Profil Siswa & Info Kuis (Tanpa Border Garis Bawah) -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-[#E8F5EE] text-green-800 font-extrabold text-xs flex items-center justify-center shrink-0 uppercase tracking-wider">
                                    {{ $inisial }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-base text-ink">{{ $user->nama_lengkap ?? 'Siswa' }}</span>
                                        <span class="text-xs text-muted font-mono">({{ $user->username ?? '-' }})</span>
                                        @if ($user && $user->kelas)
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#F1F5F3] text-muted">
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

                            <div class="self-start sm:self-auto">
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-[#EFF8FD] text-sky-700">
                                    {{ $kuis->judul ?? 'Kuis' }}
                                </span>
                            </div>
                        </div>

                        <!-- 2. Blok Soal: Latar Lembut Bebas Border -->
                        <div class="bg-[#F8FAF9] rounded-xl p-4 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-muted flex items-center gap-1.5">
                                    <x-ikon nama="clipboard-list" ukuran="13" class="text-green-700" />
                                    <span>Soal Isian Singkat #{{ $soal->urutan }}</span>
                                </span>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-white text-ink shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                                    Bobot: {{ $bobot }} Poin
                                </span>
                            </div>
                            <p class="text-sm font-semibold text-ink leading-relaxed">
                                {{ $soal->pertanyaan }}
                            </p>
                        </div>

                        <!-- 3. Komparasi 2 Kolom: Kunci Resmi vs Jawaban Siswa (Tanpa Border Menumpuk) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                            <!-- Kolom Kiri: Kunci Jawaban Resmi (Tint Hijau Lembut) -->
                            <div class="bg-[#F0FAF4] rounded-xl p-4 space-y-2.5">
                                <span class="text-xs font-bold text-green-800 flex items-center gap-1.5">
                                    <x-ikon nama="check-circle" ukuran="15" class="text-green-700" />
                                    <span>Kunci Jawaban Resmi:</span>
                                </span>
                                @if (!empty($kunciVariasi))
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($kunciVariasi as $kunci)
                                            <span class="px-3 py-1 rounded-lg text-xs font-bold bg-white text-green-900 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
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

                            <!-- Kolom Kanan: Jawaban Siswa (Tint Netral Lembut) -->
                            <div class="bg-[#F5F8F7] rounded-xl p-4 space-y-2.5">
                                <span class="text-xs font-bold text-ink flex items-center gap-1.5">
                                    <x-ikon nama="user" ukuran="15" class="text-muted" />
                                    <span>Jawaban Teks Siswa:</span>
                                </span>
                                <div class="p-3 rounded-lg bg-white font-mono text-xs font-semibold text-ink shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                                    @if ($jawaban->jawaban_teks !== null && trim($jawaban->jawaban_teks) !== '')
                                        {{ $jawaban->jawaban_teks }}
                                    @else
                                        <span class="text-muted italic font-sans font-normal">(Siswa tidak mengisi jawaban / kosong)</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- 4. Toolbar Penilaian Bawah (Lembut, Lapang, Tanpa Garis Pembatas Menumpuk) -->
                        <div class="bg-[#FAFCFB] rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <!-- Status Poin Saat Ini (Pill Bersih Tanpa Border Menakutkan) -->
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-muted">Nilai:</span>
                                @if ($poinSekarang >= $bobot)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#E6F4EA] text-green-800">
                                        <x-ikon nama="check" ukuran="13" />
                                        <span>{{ $poinSekarang }} / {{ $bobot }} Poin (Penuh)</span>
                                    </span>
                                @elseif ($poinSekarang > 0)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#E8F0FE] text-sky-800">
                                        <x-ikon nama="alert-triangle" ukuran="13" />
                                        <span>{{ $poinSekarang }} / {{ $bobot }} Poin (Parsial)</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#F1F3F4] text-muted">
                                        <x-ikon nama="clock" ukuran="13" />
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
                            <form method="POST" action="{{ route('admin.koreksi-isian.simpan', $jawaban) }}" class="flex flex-wrap items-center gap-2" x-data="{ poinManual: '{{ $poinSekarang }}' }">
                                @csrf
                                <span class="text-xs font-bold text-muted hidden sm:inline mr-1">Beri Nilai:</span>

                                <!-- Tombol Cepat 100% -->
                                <button type="submit" name="poin" value="{{ $bobot }}"
                                        title="Beri nilai penuh ({{ $bobot }} poin)"
                                        class="px-3 py-1.5 text-xs font-bold rounded-xl bg-green-700 text-white hover:bg-green-800 transition-all cursor-pointer shadow-xs">
                                    100% ({{ $bobot }})
                                </button>

                                <!-- Tombol Cepat 80% -->
                                @php $poin80 = round($bobot * 0.8, 2); @endphp
                                <button type="submit" name="poin" value="{{ $poin80 }}"
                                        title="Beri nilai 80% ({{ $poin80 }} poin)"
                                        class="px-3 py-1.5 text-xs font-bold rounded-xl bg-[#E6F4EA] text-green-800 hover:bg-[#D7EEDF] transition-all cursor-pointer">
                                    80% ({{ $poin80 }})
                                </button>

                                <!-- Tombol Cepat 50% -->
                                @php $poin50 = round($bobot * 0.5, 2); @endphp
                                <button type="submit" name="poin" value="{{ $poin50 }}"
                                        title="Beri nilai 50% ({{ $poin50 }} poin)"
                                        class="px-3 py-1.5 text-xs font-bold rounded-xl bg-[#FEF3C7] text-warning-700 hover:bg-[#FDE68A] transition-all cursor-pointer">
                                    50% ({{ $poin50 }})
                                </button>

                                <!-- Tombol Cepat 0% (Warna Netral Tenang, Bebas Border Merah Menakutkan) -->
                                <button type="submit" name="poin" value="0"
                                        title="Beri nilai 0 poin"
                                        class="px-3 py-1.5 text-xs font-bold rounded-xl bg-[#F1F3F4] text-muted hover:bg-[#FCE8E6] hover:text-danger transition-all cursor-pointer">
                                    0%
                                </button>

                                <!-- Input Angka Manual -->
                                <div class="inline-flex items-center gap-1.5 ml-1 pl-2 border-l border-border/60">
                                    <input type="number" name="poin" x-model="poinManual"
                                           min="0" max="{{ $bobot }}" step="0.1"
                                           class="w-16 text-center text-xs font-bold rounded-xl bg-white border border-border/80 px-2 py-1.5 text-ink focus:outline-none focus:ring-2 focus:ring-green-700 shadow-2xs"
                                           aria-label="Poin manual">
                                    <x-tombol tipe="garis" type="submit" class="!min-h-[32px] !px-2.5 !py-1 text-xs">
                                        Simpan
                                    </x-tombol>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach

                <!-- Pagination -->
                <div class="mt-5">
                    {{ $daftarJawaban->links() }}
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
