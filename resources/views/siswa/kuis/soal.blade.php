<x-layouts.siswa judul="Soal {{ $nomorSoalAktif }} dari {{ $totalSoal }} — {{ $percobaan->kuis->judul }}">
    <div class="max-w-2xl mx-auto space-y-6"
         x-data="{
            sisaDetik: {{ $sisaDetik !== null ? $sisaDetik : 'null' }},
            batasTotal: {{ $percobaan->kuis->batasWaktuEfektif() }},
            terkirim: false,
            kunciDraf: 'draf_esai_{{ $percobaan->id }}_{{ $soalAktif->id }}',
            inisialisasiDrafEsai() {
                @if ($soalAktif->tipe_soal === 'esai')
                try {
                    const textarea = document.getElementById('jawaban_teks');
                    if (textarea) {
                        const drafTersimpan = localStorage.getItem(this.kunciDraf);
                        if (drafTersimpan && !textarea.value) {
                            textarea.value = drafTersimpan;
                        }
                    }
                } catch (e) {
                    // Abaikan jika localStorage tidak diizinkan atau kuota penuh
                }
                @endif
            },
            simpanDrafEsai(nilai) {
                @if ($soalAktif->tipe_soal === 'esai')
                try {
                    localStorage.setItem(this.kunciDraf, nilai);
                } catch (e) {}
                @endif
            },
            hapusDrafEsai() {
                @if ($soalAktif->tipe_soal === 'esai')
                try {
                    localStorage.removeItem(this.kunciDraf);
                } catch (e) {}
                @endif
            },
            mulaiTimer() {
                if (this.sisaDetik === null) return;
                const interval = setInterval(() => {
                    if (this.sisaDetik > 0) {
                        this.sisaDetik--;
                    } else {
                        clearInterval(interval);
                        if (!this.terkirim) {
                            this.terkirim = true;
                            // Submit form otomatis saat timer habis
                            document.getElementById('form-jawab-soal').submit();
                        }
                    }
                }, 1000);
            }
         }"
         x-init="mulaiTimer(); inisialisasiDrafEsai()">

        <!-- Header Kuis: Indikator Soal & Timer -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold text-green-700 uppercase tracking-wider">
                    {{ $percobaan->kuis->judul }} &bull; Percobaan ke-{{ $percobaan->nomor_percobaan }}
                </span>
                <span class="text-xs font-bold text-muted">
                    Soal {{ $nomorSoalAktif }} dari {{ $totalSoal }}
                </span>
            </div>

            <!-- Bilah Progres Butir Soal -->
            @php
                $persenSoal = $totalSoal > 0 ? (int) round(($nomorSoalAktif / $totalSoal) * 100) : 0;
            @endphp
            <div class="w-full bg-border rounded-full h-2 overflow-hidden">
                <div class="bg-green-700 h-full rounded-full transition-all duration-300" style="width: {{ $persenSoal }}%"></div>
            </div>

            <!-- Timer Menurun (Khusus PG dan Isian) -->
            @if ($sisaDetik !== null)
                <div class="p-3 rounded-2xl border transition-colors flex items-center justify-between gap-4"
                     :class="sisaDetik <= 10 ? 'bg-red-50 border-danger text-danger' : 'bg-white border-border text-ink'">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-extrabold uppercase">Sisa Waktu:</span>
                        <span class="text-base font-black font-mono" x-text="sisaDetik + ' detik'"></span>
                    </div>

                    <!-- Bilah Timer Menyusut -->
                    <div class="flex-1 max-w-[200px] bg-border rounded-full h-2 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-1000"
                             :class="sisaDetik <= 10 ? 'bg-danger' : 'bg-green-700'"
                             :style="'width: ' + ((sisaDetik / batasTotal) * 100) + '%'"></div>
                    </div>

                    <!-- Pembaca Layar: aria-live hanya aktif pada 10 detik terakhir agar tidak berisik -->
                    <div class="sr-only" :aria-live="sisaDetik <= 10 ? 'assertive' : 'off'" x-text="sisaDetik <= 10 ? ('Peringatan, sisa waktu tinggal ' + sisaDetik + ' detik') : ''"></div>
                </div>
            @else
                <div class="p-2.5 rounded-xl bg-surface border border-border text-xs text-muted flex items-center gap-2">
                    <span class="font-bold text-ink">Catatan:</span>
                    <span>Soal esai tidak dibatasi waktu. Tuliskan jawaban Anda dengan lengkap dan teliti.</span>
                </div>
            @endif
        </div>

        <!-- Kartu Soal & Form Jawaban -->
        <x-kartu class="p-6 sm:p-8 bg-white border border-border space-y-6">
            <!-- Teks Pertanyaan (Tanpa Petunjuk Kunci Jawaban di HTML) -->
            <div class="space-y-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-bold bg-surface text-muted">
                    {{ ucfirst(str_replace('_', ' ', $soalAktif->tipe_soal)) }} &bull; Bobot: {{ $soalAktif->bobot_poin }} Poin
                </span>
                <p class="text-lg font-extrabold text-ink leading-relaxed whitespace-pre-line">
                    {{ $soalAktif->pertanyaan }}
                </p>
            </div>

            <form id="form-jawab-soal" method="POST" action="{{ route('siswa.percobaan.jawab', [$percobaan, $soalAktif]) }}"
                  @submit="terkirim = true; hapusDrafEsai()" class="space-y-6">
                @csrf

                <!-- 1. Pilihan Ganda: Radio Card Besar Mudah Diketuk -->
                @if ($soalAktif->tipe_soal === 'pilihan_ganda')
                    <div class="space-y-3">
                        @foreach ($pilihanOpsi as $idx => $opsi)
                            <label class="group flex items-center gap-4 p-4 rounded-2xl border border-border hover:border-green-700 hover:bg-green-50/50 cursor-pointer transition-all duration-150 has-[:checked]:border-green-700 has-[:checked]:bg-green-50">
                                <input type="radio" name="pilihan_jawaban_id" value="{{ $opsi->id }}"
                                       class="w-5 h-5 text-green-700 focus:ring-green-700 border-border cursor-pointer"
                                       {{ old('pilihan_jawaban_id', $jawaban->pilihan_jawaban_id) == $opsi->id ? 'checked' : '' }}>
                                <span class="text-sm font-bold text-ink group-hover:text-green-900 flex-1">
                                    {{ $opsi->teks_pilihan }}
                                </span>
                            </label>
                        @endforeach
                    </div>

                <!-- 2. Isian Singkat: Input Teks Besar Tanpa Autocomplete -->
                @elseif ($soalAktif->tipe_soal === 'isian')
                    <div class="space-y-2">
                        <label for="jawaban_teks" class="block text-xs font-bold text-muted uppercase">
                            Tulis Jawaban Singkat Anda
                        </label>
                        <input type="text" id="jawaban_teks" name="jawaban_teks"
                               value="{{ old('jawaban_teks', $jawaban->jawaban_teks) }}"
                               autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                               placeholder="Ketik jawaban di sini..."
                               class="w-full text-base font-extrabold text-ink rounded-2xl border border-border px-4 py-3.5 bg-white focus:outline-none focus:ring-2 focus:ring-green-700">
                    </div>

                <!-- 3. Esai: Textarea Besar Tanpa Batas Waktu -->
                @elseif ($soalAktif->tipe_soal === 'esai')
                    <div class="space-y-2">
                        <label for="jawaban_teks" class="block text-xs font-bold text-muted uppercase">
                            Tulis Uraian / Penjelasan Anda
                        </label>
                        <textarea id="jawaban_teks" name="jawaban_teks" rows="7"
                                  @input="simpanDrafEsai($event.target.value)"
                                  placeholder="Ketik penjelasan mendalam Anda..."
                                  class="w-full text-sm text-ink rounded-2xl border border-border p-4 bg-white focus:outline-none focus:ring-2 focus:ring-green-700 leading-relaxed">{{ old('jawaban_teks', $jawaban->jawaban_teks) }}</textarea>
                    </div>
                @endif

                <!-- Tombol Kirim Dinonaktifkan Setelah Diklik (Mencegah Klik Ganda) -->
                <div class="pt-4 border-t border-border flex items-center justify-between">
                    <span class="text-xs text-muted font-bold">
                        {{ $nomorSoalAktif === $totalSoal ? 'Soal Terakhir' : 'Soal Selanjutnya' }}
                    </span>

                    <button type="submit" :disabled="terkirim"
                            class="inline-flex items-center justify-center gap-2 rounded-xl px-6 py-3 font-extrabold text-sm text-white bg-green-700 hover:bg-green-800 transition-colors focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2 disabled:bg-locked disabled:cursor-not-allowed">
                        <span x-text="terkirim ? 'Menyimpan...' : ('{{ $nomorSoalAktif === $totalSoal ? 'Simpan & Selesai' : 'Simpan & Lanjut' }}')"></span>
                        <x-ikon nama="arrow-right" ukuran="16" />
                    </button>
                </div>
            </form>
        </x-kartu>
    </div>
</x-layouts.siswa>
