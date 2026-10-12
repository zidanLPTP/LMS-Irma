<x-layouts.siswa :judul="__('nilai.judul_halaman')">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-extrabold text-ink">{{ __('nilai.judul_halaman') }}</h1>
            <p class="text-sm text-muted mt-1">{{ __('nilai.deskripsi_halaman') }}</p>
        </div>

        <div class="space-y-6">
            @forelse ($daftarLangkah as $item)
                @php
                    $kuis = $item['kuis'];
                    $langkah = $item['langkah'];
                    $nilaiTertinggi = $item['nilai_tertinggi'];
                    $statusLulus = $item['status_lulus'];
                    $riwayatPercobaan = $item['riwayat_percobaan'];
                    $isPretestPosttest = in_array($kuis->tipe_kuis, ['pretest', 'posttest']);
                @endphp

                <x-kartu class="p-6 border border-border">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-border">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-green-700">
                                    {{ $kuis->tipe_kuis === 'pretest' ? 'Tes Awal' : ($kuis->tipe_kuis === 'posttest' ? 'Tes Akhir' : 'Kuis Bab') }}
                                </span>
                            </div>
                            <h2 class="text-lg font-extrabold text-ink">
                                {{ $kuis->judul }}
                            </h2>
                        </div>

                        <!-- Nilai Tertinggi & Lencana Status -->
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <span class="block text-xs text-muted font-bold">Nilai Tertinggi</span>
                                <span class="text-xl font-extrabold text-ink">
                                    {{ $nilaiTertinggi !== null ? $nilaiTertinggi : '-' }}
                                </span>
                            </div>

                            @if (! $isPretestPosttest && $statusLulus !== null)
                                @if ($statusLulus)
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">
                                        <x-ikon nama="check" ukuran="14" />
                                        {{ __('nilai.lencana.lulus') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                        <x-ikon nama="x" ukuran="14" />
                                        {{ __('nilai.lencana.belum_lulus') }}
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>

                    <!-- Riwayat Percobaan -->
                    <div class="mt-4 space-y-3">
                        <h3 class="text-xs font-extrabold text-muted uppercase tracking-wider">
                            {{ __('nilai.riwayat_percobaan') }}
                        </h3>

                        @if ($riwayatPercobaan->isEmpty())
                            <p class="text-xs text-muted py-2">Belum ada riwayat pengerjaan.</p>
                        @else
                            <div class="space-y-2">
                                @foreach ($riwayatPercobaan as $percobaan)
                                    <div class="p-3 bg-surface rounded-xl border border-border flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-ink">Percobaan ke-{{ $percobaan['nomor_percobaan'] }}</span>
                                                <span class="text-muted">&bull;</span>
                                                <span class="text-muted">{{ \Carbon\Carbon::parse($percobaan['waktu_mulai'])->translatedFormat('d M Y, H:i') }}</span>
                                            </div>
                                            <div class="text-muted">
                                                Status: <span class="font-bold text-ink">{{ ucfirst($percobaan['status']) }}</span>
                                                @if ($percobaan['nilai_otomatis'] !== null)
                                                    &bull; Otomatis: <span class="font-bold text-ink">{{ $percobaan['nilai_otomatis'] }}</span>
                                                @endif
                                                @if ($percobaan['nilai_esai'] !== null)
                                                    &bull; Esai: <span class="font-bold text-ink">{{ $percobaan['nilai_esai'] }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-4">
                                            <div class="text-right">
                                                <span class="block text-[11px] text-muted">{{ __('nilai.nilai_akhir') }}</span>
                                                @if ($percobaan['nilai_akhir'] !== null)
                                                    <span class="text-sm font-extrabold text-ink">{{ $percobaan['nilai_akhir'] }}</span>
                                                @elseif ($percobaan['status'] === 'selesai')
                                                    <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200">
                                                        {{ __('nilai.lencana.menunggu_esai') }}
                                                    </span>
                                                @else
                                                    <span class="text-xs text-muted">-</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Catatan Guru dari Jawaban Esai -->
                                    @if (! empty($percobaan['catatan_guru']))
                                        <div class="ml-4 pl-3 border-l-2 border-green-600 space-y-1 py-1">
                                            <span class="text-[11px] font-bold text-green-800">{{ __('nilai.catatan_guru') }}:</span>
                                            @foreach ($percobaan['catatan_guru'] as $catatan)
                                                <p class="text-xs text-ink bg-green-50 p-2 rounded-lg border border-green-100">
                                                    "{{ $catatan }}"
                                                </p>
                                            @endforeach
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </x-kartu>
            @empty
                <p class="text-sm text-muted text-center py-8">Belum ada langkah pembelajaran aktif.</p>
            @endforelse
        </div>
    </div>
</x-layouts.siswa>
