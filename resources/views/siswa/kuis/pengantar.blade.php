<x-layouts.siswa judul="Pengantar: {{ $kuis->judul }}">
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <a href="{{ route('siswa.beranda') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-green-700 hover:underline mb-2">
                <x-ikon nama="chevron-left" ukuran="16" />
                <span>Kembali ke Beranda</span>
            </a>
            <span class="block text-xs font-extrabold text-green-700 uppercase tracking-wider">
                Evaluasi Pembelajaran &bull; {{ ucfirst(str_replace('_', ' ', $kuis->tipe_kuis)) }}
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-ink tracking-tight mt-1">
                {{ $kuis->judul }}
            </h1>
        </div>

        @if (! $terbuka)
            <x-pita-info tipe="peringatan">
                <strong>Kuis Terkunci:</strong> Anda belum dapat memulai kuis ini. Silakan selesaikan tahapan materi pembelajaran atau kuis sebelumnya terlebih dahulu.
            </x-pita-info>
        @endif

        <!-- Kartu Detail Aturan Kuis -->
        <x-kartu class="p-6 bg-white border border-border space-y-5">
            <h2 class="text-base font-extrabold text-ink border-b border-border pb-3">
                Informasi & Petunjuk Pengerjaan
            </h2>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <div class="p-3 rounded-xl bg-surface border border-border">
                    <span class="block text-xs font-bold text-muted">Jumlah Soal</span>
                    <span class="text-lg font-extrabold text-ink">{{ $totalSoal }} butir</span>
                </div>

                <div class="p-3 rounded-xl bg-surface border border-border">
                    <span class="block text-xs font-bold text-muted">Batas Waktu per Soal</span>
                    @if ($batasWaktuPerSoal)
                        <span class="text-lg font-extrabold text-ink">{{ $batasWaktuPerSoal }} detik</span>
                    @else
                        <span class="text-sm font-extrabold text-ink mt-1 block">Tanpa Batas</span>
                    @endif
                </div>

                @if ($nilaiLulus)
                    <div class="p-3 rounded-xl bg-surface border border-border col-span-2 sm:col-span-1">
                        <span class="block text-xs font-bold text-muted">Standar Kelulusan</span>
                        <span class="text-lg font-extrabold text-green-700">&ge; {{ number_format($nilaiLulus, 0) }}</span>
                    </div>
                @endif

                <div class="p-3 rounded-xl bg-surface border border-border">
                    <span class="block text-xs font-bold text-muted">Maksimal Kesempatan</span>
                    <span class="text-lg font-extrabold text-ink">{{ $maksimalKesempatan }} kali</span>
                </div>

                <div class="p-3 rounded-xl bg-surface border border-border">
                    <span class="block text-xs font-bold text-muted">Kesempatan Sisa</span>
                    <span class="text-lg font-extrabold {{ $kesempatanSisa > 0 ? 'text-green-700' : 'text-danger' }}">
                        {{ $kesempatanSisa }} kali
                    </span>
                </div>
            </div>

            <div class="space-y-2 pt-2 text-xs text-muted leading-relaxed">
                <p>&bull; <strong>Waktu per soal:</strong> Soal pilihan ganda dan isian dibatasi waktu (60 detik per butir). Timer berjalan otomatis di server saat soal dibuka.</p>
                <p>&bull; <strong>Sistem waktu:</strong> Refresh halaman melanjutkan sisa waktu dan tidak mengulang dari awal.</p>
                <p>&bull; <strong>Urutan soal:</strong> Anda harus menjawab atau melewati soal satu per satu. Soal yang sudah terlewat tidak dapat dibuka kembali.</p>
                <p>&bull; <strong>Soal esai:</strong> Terletak di akhir dan tidak dibatasi oleh timer.</p>
            </div>

            <!-- Tombol Mulai / Lanjutkan -->
            <div class="pt-4 border-t border-border flex items-center justify-between">
                <div>
                    @if ($percobaanAktif)
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-sky-700 bg-sky-50 border border-sky-200 px-3 py-1 rounded-full">
                            Ada pengerjaan yang belum diselesaikan
                        </span>
                    @endif
                </div>

                <div>
                    @if ($percobaanAktif)
                        <x-tombol tipe="utama" href="{{ route('siswa.percobaan.soal', $percobaanAktif) }}" class="!min-h-[44px] !px-5 text-sm font-bold">
                            <span>Lanjutkan Pengerjaan</span>
                            <x-ikon nama="arrow-right" ukuran="16" />
                        </x-tombol>
                    @elseif ($terbuka && $bisaMulai)
                        <form method="POST" action="{{ route('siswa.kuis.mulai', $kuis) }}">
                            @csrf
                            <x-tombol tipe="utama" type="submit" class="!min-h-[44px] !px-6 text-sm font-bold">
                                <span>{{ $kesempatanTerpakai > 0 ? 'Mulai Percobaan Baru' : 'Mulai Kerjakan Kuis' }}</span>
                                <x-ikon nama="arrow-right" ukuran="16" />
                            </x-tombol>
                        </form>
                    @else
                        <button type="button" disabled class="px-5 py-2.5 rounded-xl text-xs font-bold bg-surface text-muted border border-border cursor-not-allowed">
                            {{ ! $terbuka ? 'Kuis Terkunci' : 'Kesempatan Habis' }}
                        </button>
                    @endif
                </div>
            </div>
        </x-kartu>

        <!-- Riwayat Percobaan Sebelumnya -->
        @if ($riwayatPercobaan->isNotEmpty())
            <div class="space-y-3">
                <h3 class="text-sm font-extrabold text-ink">Riwayat Percobaan Anda</h3>
                <div class="space-y-2">
                    @foreach ($riwayatPercobaan as $p)
                        <div class="p-4 rounded-xl border border-border bg-white flex items-center justify-between">
                            <div class="space-y-0.5">
                                <span class="text-xs font-bold text-muted">Percobaan ke-{{ $p->nomor_percobaan }}</span>
                                <div class="text-sm font-extrabold text-ink">
                                    @if ($p->status_pengerjaan === 'sedang_mengerjakan')
                                        <span class="text-sky-700">Sedang Dikerjakan</span>
                                    @else
                                        Nilai: {{ $p->nilai_otomatis ?? 0 }}%
                                        @if ($p->esai_menunggu_nilai)
                                            <span class="text-xs font-bold text-warning">(Esai menunggu nilai)</span>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            <div>
                                @if ($p->status_pengerjaan === 'selesai')
                                    <x-tombol tipe="garis" href="{{ route('siswa.percobaan.hasil', $p) }}" class="!min-h-[36px] !px-3 text-xs">
                                        Lihat Hasil
                                    </x-tombol>
                                @else
                                    <x-tombol tipe="utama" href="{{ route('siswa.percobaan.soal', $p) }}" class="!min-h-[36px] !px-3 text-xs">
                                        Lanjutkan
                                    </x-tombol>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts.siswa>
