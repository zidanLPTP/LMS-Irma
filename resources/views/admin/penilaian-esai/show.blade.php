<x-layouts.admin judul="Penilaian Esai — {{ $percobaan->user->nama_lengkap }}">
    <div class="max-w-3xl mx-auto space-y-6">
        <!-- Header & Navigasi Balik -->
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.penilaian-esai.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-green-700 hover:text-green-800">
                <x-ikon nama="arrow-left" ukuran="16" />
                <span>Kembali ke Antrean</span>
            </a>
            <span class="text-xs font-bold text-muted">
                Diserahkan: {{ $percobaan->waktu_selesai ? $percobaan->waktu_selesai->translatedFormat('d M Y, H:i') : '-' }}
            </span>
        </div>

        <!-- Info Siswa & Kuis -->
        <x-kartu class="p-5 sm:p-6 bg-white border border-border">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-xs font-extrabold text-green-700 uppercase tracking-wider">
                        {{ $percobaan->kuis->judul }} &bull; Percobaan ke-{{ $percobaan->nomor_percobaan }}
                    </span>
                    <h1 class="text-xl font-extrabold text-ink mt-1">
                        {{ $percobaan->user->nama_lengkap }}
                    </h1>
                    <p class="text-xs text-muted mt-0.5">
                        Kelas: {{ $percobaan->user->kelas ?? '-' }} &bull; Username: {{ $percobaan->user->username }}
                    </p>
                </div>

                <div class="text-right">
                    @if (! is_null($percobaan->nilai_otomatis))
                        <div class="text-xs text-muted">Nilai Otomatis (PG/Isian):</div>
                        <div class="text-lg font-black text-ink font-mono">{{ $percobaan->nilai_otomatis }}%</div>
                    @else
                        <span class="text-xs text-muted">Hanya memuat soal esai</span>
                    @endif
                </div>
            </div>
        </x-kartu>

        <!-- Formulir Penilaian Esai -->
        <form method="POST" action="{{ route('admin.penilaian-esai.simpan', $percobaan) }}" class="space-y-6">
            @csrf

            @php
                $jawabanEsai = $percobaan->jawabanSiswa->filter(fn ($j) => $j->soal?->tipe_soal === 'esai');
            @endphp

            @foreach ($jawabanEsai as $index => $jawaban)
                @php
                    $soal = $jawaban->soal;
                    $errorKey = "penilaian.{$jawaban->id}.poin";
                @endphp
                <x-kartu class="p-5 sm:p-6 space-y-4 border {{ $errors->has($errorKey) ? 'border-danger bg-red-50/20' : 'border-border bg-white' }}">
                    <!-- Header Soal -->
                    <div class="flex items-center justify-between pb-3 border-b border-border">
                        <span class="text-xs font-extrabold text-muted uppercase">
                            Soal Esai ke-{{ $index + 1 }}
                        </span>
                        <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-full bg-surface text-ink border border-border">
                            Bobot Maksimal: <strong class="font-black text-green-700">{{ $soal->bobot_poin }} Poin</strong>
                        </span>
                    </div>

                    <!-- Pertanyaan Soal -->
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-muted uppercase">Pertanyaan:</label>
                        <p class="text-base font-extrabold text-ink leading-relaxed whitespace-pre-line">
                            {{ $soal->pertanyaan }}
                        </p>
                    </div>

                    <!-- Jawaban Siswa (Wajib di-escape terhadap XSS, baris baru tetap terbaca) -->
                    <div class="space-y-1 pt-2">
                        <label class="block text-xs font-bold text-muted uppercase">Jawaban Siswa:</label>
                        <div class="p-4 rounded-xl bg-surface border border-border text-sm text-ink leading-relaxed whitespace-pre-line font-mono break-words">
                            {{ $jawaban->jawaban_teks ?: '(Siswa tidak mengisi jawaban / kosong)' }}
                        </div>
                    </div>

                    <!-- Input Poin & Catatan Admin -->
                    <div class="pt-3 border-t border-border grid grid-cols-1 sm:grid-cols-3 gap-4 items-start">
                        <div class="sm:col-span-1 space-y-1">
                            <label for="poin_{{ $jawaban->id }}" class="block text-xs font-bold text-ink uppercase">
                                Nilai Poin (0 - {{ $soal->bobot_poin }}) <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" max="{{ $soal->bobot_poin }}"
                                   id="poin_{{ $jawaban->id }}"
                                   name="penilaian[{{ $jawaban->id }}][poin]"
                                   value="{{ old("penilaian.{$jawaban->id}.poin", $jawaban->poin_diperoleh) }}"
                                   required
                                   placeholder="0.00"
                                   class="w-full text-base font-black text-ink rounded-xl border {{ $errors->has($errorKey) ? 'border-danger focus:ring-danger' : 'border-border focus:ring-green-700' }} px-3 py-2 bg-white focus:outline-none focus:ring-2">
                            @error($errorKey)
                                <p class="text-xs text-danger font-bold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2 space-y-1">
                            <label for="catatan_{{ $jawaban->id }}" class="block text-xs font-bold text-ink uppercase">
                                Catatan untuk Siswa (Opsional)
                            </label>
                            <input type="text"
                                   id="catatan_{{ $jawaban->id }}"
                                   name="penilaian[{{ $jawaban->id }}][catatan]"
                                   value="{{ old("penilaian.{$jawaban->id}.catatan", $jawaban->catatan_guru) }}"
                                   placeholder="Umpan balik atau evaluasi..."
                                   class="w-full text-sm text-ink rounded-xl border border-border px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-green-700">
                        </div>
                    </div>
                </x-kartu>
            @endforeach

            <!-- Tombol Aksi Simpan & Lanjut -->
            <div class="pt-4 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-3">
                <x-tombol tipe="garis" href="{{ route('admin.penilaian-esai.index') }}" class="w-full sm:w-auto text-xs font-bold">
                    Batal
                </x-tombol>

                <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                    <x-tombol tipe="garis" type="submit" name="lanjut" value="0" class="w-full sm:w-auto text-xs font-bold !min-h-[44px]">
                        <span>Simpan Nilai</span>
                    </x-tombol>

                    <x-tombol tipe="utama" type="submit" name="lanjut" value="1" class="w-full sm:w-auto text-xs font-bold !min-h-[44px]">
                        <span>Simpan dan Lanjut ke Percobaan Berikutnya</span>
                        <x-ikon nama="arrow-right" ukuran="16" />
                    </x-tombol>
                </div>
            </div>
        </form>
    </div>
</x-layouts.admin>
