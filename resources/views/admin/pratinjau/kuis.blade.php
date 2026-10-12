<x-layouts.siswa :judul="'[Pratinjau] ' . $kuis->judul">
    <div class="space-y-6">
        <!-- Banner Mode Pratinjau -->
        <div class="p-3 bg-amber-500 text-white rounded-2xl flex items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center gap-2">
                <x-ikon nama="eye" ukuran="20" />
                <span class="text-xs font-bold">Mode Pratinjau Kuis — Kunci jawaban dan bobot ditampilkan di bawah khusus untuk Guru/Admin.</span>
            </div>
            <a href="{{ route('admin.kuis.index') }}" class="text-xs font-extrabold underline hover:text-amber-100">
                Kembali ke Kelola Kuis
            </a>
        </div>

        <div>
            <span class="text-xs font-extrabold text-green-700 uppercase tracking-wider">
                Pratinjau Kuis &bull; {{ $kuis->tipe_kuis }}
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-ink tracking-tight mt-1">
                {{ $kuis->judul }}
            </h1>
            <p class="text-xs text-muted mt-1">Total {{ $kuis->soal->count() }} soal terdaftar.</p>
        </div>

        <div class="space-y-6">
            @forelse ($kuis->soal as $index => $soal)
                <x-kartu class="p-6 border border-border space-y-4">
                    <div class="flex items-center justify-between border-b border-border pb-3">
                        <span class="text-xs font-extrabold text-green-700">Soal Nomor {{ $index + 1 }}</span>
                        <span class="text-xs font-bold text-muted bg-surface px-2.5 py-1 rounded-md border border-border">
                            Bobot: {{ $soal->bobot_poin }} poin &bull; Tipe: {{ $soal->tipe_soal }}
                        </span>
                    </div>

                    <!-- Tampilan Pertanyaan (Seperti yang Dilihat Siswa) -->
                    <div class="text-sm font-bold text-ink whitespace-pre-line">
                        {{ $soal->pertanyaan }}
                    </div>

                    @if ($soal->tipe_soal === 'pilihan_ganda')
                        <div class="space-y-2 pt-2">
                            @foreach ($soal->pilihanJawaban as $pilihan)
                                <div class="p-3 rounded-xl border border-border text-xs flex items-center justify-between {{ $pilihan->apakah_benar ? 'bg-green-50 border-green-300' : 'bg-surface' }}">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[11px] {{ $pilihan->apakah_benar ? 'bg-green-700 text-white' : 'bg-white border border-border text-muted' }}">
                                            {{ chr(64 + $pilihan->urutan) }}
                                        </span>
                                        <span class="text-ink font-semibold">{{ $pilihan->teks_pilihan }}</span>
                                    </div>

                                    @if ($pilihan->apakah_benar)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-black text-green-800 bg-green-200/60 px-2 py-0.5 rounded-md">
                                            <x-ikon nama="check" ukuran="12" />
                                            <span>KUNCI JAWABAN</span>
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @elseif ($soal->tipe_soal === 'isian')
                        <div class="p-3 rounded-xl bg-surface border border-border text-xs text-muted italic">
                            [Kolom input isian siswa]
                        </div>

                        <!-- Khusus Admin: Kunci Isian -->
                        <div class="p-3 rounded-xl bg-green-50 border border-green-200 text-xs space-y-1">
                            <span class="font-extrabold text-green-900">Kunci Jawaban Isian yang Diterima (Khusus Guru):</span>
                            <div class="flex flex-wrap gap-1.5 mt-1">
                                @foreach ((array) $soal->kunci_jawaban as $kj)
                                    <span class="px-2.5 py-0.5 rounded-md bg-white border border-green-300 font-mono font-bold text-green-800">
                                        "{{ $kj }}"
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @elseif ($soal->tipe_soal === 'esai')
                        <div class="p-3 rounded-xl bg-surface border border-border text-xs text-muted italic">
                            [Area pengetikan esai siswa]
                        </div>

                        <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 font-bold">
                            Soal esai ini akan dinilai manual oleh Guru/Admin melalui menu Penilaian Esai.
                        </div>
                    @endif
                </x-kartu>
            @empty
                <x-kartu class="p-8 text-center text-muted">
                    <p class="text-sm">Kuis ini belum memiliki soal.</p>
                </x-kartu>
            @endforelse
        </div>
    </div>
</x-layouts.siswa>
