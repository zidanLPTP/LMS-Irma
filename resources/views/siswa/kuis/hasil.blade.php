<x-layouts.siswa judul="Hasil: {{ $kuis->judul }}">
    <div class="max-w-2xl mx-auto space-y-6">
        <!-- Kartu Utama Hasil -->
        <x-kartu class="p-6 sm:p-8 bg-white border border-border text-center space-y-6">
            <div class="space-y-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-surface text-muted">
                    Hasil Pengerjaan Kuis
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-ink tracking-tight">
                    {{ $kuis->judul }}
                </h1>
                <p class="text-xs text-muted">
                    Percobaan ke-{{ $percobaan->nomor_percobaan }} dari {{ $maksimalKesempatan }} kesempatan
                </p>
            </div>

            <!-- Tampilan Lencana Status & Nilai -->
            <div class="p-6 rounded-2xl bg-surface border border-border max-w-md mx-auto space-y-4">
                @if ($kuis->tipe_kuis === 'pretest')
                    <!-- Pretest: Tanpa Lencana Kelulusan -->
                    <div class="space-y-2">
                        <span class="inline-block text-xs font-bold text-sky-700 bg-sky-50 border border-sky-200 px-3 py-1 rounded-full">
                            Diagnosis Awal Selesai
                        </span>
                        <div class="text-3xl sm:text-4xl font-black text-ink">
                            {{ number_format($percobaan->nilai_otomatis ?? 0, 0) }}%
                        </div>
                        <p class="text-xs text-muted leading-relaxed">
                            Pretest selesai, ini hanya untuk mengetahui pengetahuan awalmu sebelum mempelajari bab materi biologi.
                        </p>
                    </div>

                @elseif ($kuis->tipe_kuis === 'posttest')
                    <!-- Posttest: Tanpa Lencana Kelulusan -->
                    <div class="space-y-2">
                        <span class="inline-block text-xs font-bold text-green-700 bg-green-50 border border-green-200 px-3 py-1 rounded-full">
                            Evaluasi Akhir Selesai
                        </span>
                        <div class="text-3xl sm:text-4xl font-black text-ink">
                            {{ number_format($percobaan->nilai_otomatis ?? 0, 0) }}%
                        </div>
                        <p class="text-xs text-muted">
                            Terima kasih telah menuntaskan seluruh rangkaian pembelajaran dan evaluasi akhir biologi.
                        </p>
                    </div>

                @else
                    <!-- Kuis Bab: Dengan Lencana Status Kelulusan Berdasarkan Soal Otomatis -->
                    <div class="space-y-3">
                        @if ($percobaan->status_kelulusan === 'lulus')
                            <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-black bg-green-100 text-green-700 border border-green-200">
                                <x-ikon nama="check" ukuran="16" />
                                <span>LULUS</span>
                            </span>
                        @elseif ($percobaan->status_kelulusan === 'belum_lulus_lanjut')
                            <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-black bg-yellow-100 text-yellow-800 border border-yellow-200">
                                <span>BELUM LULUS (DAPAT MELANJUTKAN)</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-black bg-red-100 text-red-700 border border-red-200">
                                <span>BELUM LULUS</span>
                            </span>
                        @endif

                        <div>
                            <span class="block text-xs font-bold text-muted">Nilai Soal Pilihan Ganda & Isian</span>
                            <div class="text-3xl sm:text-4xl font-black {{ $percobaan->status_kelulusan === 'lulus' ? 'text-green-700' : 'text-ink' }}">
                                {{ number_format($percobaan->nilai_otomatis ?? 0, 0) }}%
                            </div>
                            <span class="text-xs text-muted font-bold">
                                Standar Kelulusan: &ge; {{ number_format($kuis->nilaiLulusEfektif(), 0) }}%
                            </span>
                        </div>
                    </div>
                @endif

                <!-- Status Nilai Akhir / Menunggu Esai -->
                <div class="pt-3 border-t border-border text-xs">
                    @if ($percobaan->esai_menunggu_nilai)
                        <span class="font-bold text-amber-700">
                            Nilai akhir: menunggu penilaian esai
                        </span>
                    @elseif (! is_null($percobaan->nilai_akhir))
                        <span class="font-bold text-ink">
                            Nilai akhir: {{ number_format($percobaan->nilai_akhir, 0) }}%
                        </span>
                    @endif
                </div>
            </div>

            <!-- Pesan Pengingat Kerahasiaan Kunci Penelitian -->
            <p class="text-xs text-muted max-w-md mx-auto">
                Kunci jawaban dan rincian benar/salah tidak ditampilkan untuk menjaga objektivitas instrumen penelitian sekolah.
            </p>

            <!-- Tombol Navigasi Lanjutan -->
            <div class="pt-4 border-t border-border flex flex-wrap items-center justify-center gap-3">
                <x-tombol tipe="garis" href="{{ route('siswa.beranda') }}">
                    Kembali ke Beranda
                </x-tombol>

                @if ($bisaCobaLagi)
                    <form method="POST" action="{{ route('siswa.kuis.mulai', $kuis) }}">
                        @csrf
                        <x-tombol tipe="utama" type="submit">
                            <span>Coba Lagi (Sisa {{ $kesempatanSisa }})</span>
                            <x-ikon nama="arrow-right" ukuran="16" />
                        </x-tombol>
                    </form>
                @endif

                @if ($babBerikutnya)
                    <x-tombol tipe="utama" href="{{ route('siswa.bab.show', $babBerikutnya) }}">
                        <span>Lanjut ke {{ $babBerikutnya->judul }}</span>
                        <x-ikon nama="arrow-right" ukuran="16" />
                    </x-tombol>
                @endif
            </div>
        </x-kartu>
    </div>
</x-layouts.siswa>
