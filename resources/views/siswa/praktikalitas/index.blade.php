<x-layouts.siswa judul="Lembar Praktikalitas Media">
    <div class="space-y-6">
        @if ($sudahMengisi)
            <!-- Tampilan Jika Sudah Mengisi -->
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl border border-green-200 p-6 sm:p-8 text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-green-700 text-white flex items-center justify-center mx-auto shadow-md">
                    <x-ikon nama="check-circle" ukuran="36" />
                </div>
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-white text-green-700 border border-green-200">
                        <x-ikon nama="sparkles" ukuran="14" />
                        Terima Kasih Banyak!
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-ink tracking-tight pt-2">
                        Respon Anda Telah Tersimpan
                    </h1>
                    <p class="text-sm text-muted max-w-xl mx-auto leading-relaxed">
                        Terima kasih telah meluangkan waktu untuk mengisi lembar kepraktisan website ini. Penilaian dan masukan Anda sangat berharga untuk kesuksesan penelitian dan peningkatan kualitas media pembelajaran BioSTEM-X.
                    </p>
                </div>
                <div class="pt-2">
                    <x-tombol tipe="utama" href="{{ route('siswa.beranda') }}" class="text-xs">
                        Kembali ke Beranda Belajar
                    </x-tombol>
                </div>
            </div>

            <!-- Ringkasan Jawaban Siswa -->
            <div class="bg-white rounded-2xl border border-border p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-border">
                    <h2 class="text-base font-extrabold text-ink flex items-center gap-2">
                        <x-ikon nama="clipboard-check" ukuran="18" class="text-green-700" />
                        <span>Ringkasan Penilaian yang Anda Berikan</span>
                    </h2>
                    <span class="text-xs text-muted font-bold">Skala 1 s.d. 4</span>
                </div>

                <div class="divide-y divide-border text-xs">
                    @foreach ($responList as $item)
                        @php
                            $labelSkor = match ($item->skor) {
                                4 => 'Sangat Setuju',
                                3 => 'Setuju',
                                2 => 'Tidak Setuju',
                                1 => 'Sangat Tidak Setuju',
                                default => '-',
                            };
                            $warnaSkor = match ($item->skor) {
                                4 => 'bg-green-100 text-green-800 border-green-300',
                                3 => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                2 => 'bg-amber-50 text-amber-800 border-amber-200',
                                default => 'bg-red-50 text-red-800 border-red-200',
                            };
                        @endphp
                        <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="space-y-0.5 max-w-2xl">
                                <span class="text-[10px] font-extrabold text-muted uppercase tracking-wider">
                                    {{ $item->instrumen->aspek }} &bull; Butir {{ $item->instrumen->urutan }}
                                </span>
                                <p class="text-ink font-semibold">{{ $item->instrumen->pernyataan }}</p>
                            </div>
                            <div class="shrink-0 flex items-center gap-2">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-extrabold border {{ $warnaSkor }}">
                                    <span>Skor {{ $item->skor }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $labelSkor }}</span>
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($saran && $saran->saran)
                    <div class="pt-4 mt-2 border-t border-border space-y-1.5 bg-surface p-4 rounded-xl">
                        <span class="text-xs font-extrabold text-muted uppercase tracking-wider block">Saran & Masukan Anda:</span>
                        <p class="text-xs text-ink italic leading-relaxed">"{{ $saran->saran }}"</p>
                    </div>
                @endif
            </div>
        @else
            <!-- Formulir Pengisian Lembar Praktikalitas -->
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl border border-green-200/80 p-6 sm:p-8 space-y-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-white text-green-700 border border-green-200 shadow-xs">
                    <x-ikon nama="clipboard-list" ukuran="15" />
                    Instrumen Penelitian Praktikalitas Media
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-ink tracking-tight">
                    Lembar Praktikalitas Penggunaan Website
                </h1>
                <p class="text-sm text-muted max-w-2xl leading-relaxed">
                    Mohon berikan penilaian sejujurnya mengenai kemudahan, kenyamanan, dan kebermanfaatan website BioSTEM-X ini selama Anda belajar. Penilaian Anda tidak mempengaruhi nilai akademik.
                </p>

                <!-- Keterangan Skala -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2 text-xs">
                    <div class="bg-white/80 backdrop-blur rounded-xl p-2.5 border border-green-200 text-center">
                        <span class="block font-black text-green-800 text-sm">4</span>
                        <span class="text-[11px] text-muted font-bold">Sangat Setuju (SS)</span>
                    </div>
                    <div class="bg-white/80 backdrop-blur rounded-xl p-2.5 border border-green-200 text-center">
                        <span class="block font-black text-green-800 text-sm">3</span>
                        <span class="text-[11px] text-muted font-bold">Setuju (S)</span>
                    </div>
                    <div class="bg-white/80 backdrop-blur rounded-xl p-2.5 border border-green-200 text-center">
                        <span class="block font-black text-green-800 text-sm">2</span>
                        <span class="text-[11px] text-muted font-bold">Tidak Setuju (TS)</span>
                    </div>
                    <div class="bg-white/80 backdrop-blur rounded-xl p-2.5 border border-green-200 text-center">
                        <span class="block font-black text-green-800 text-sm">1</span>
                        <span class="text-[11px] text-muted font-bold">Sangat Tidak Setuju (STS)</span>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <x-pita-info tipe="galat">
                    Mohon lengkapi semua butir pernyataan sebelum mengirimkan formulir.
                </x-pita-info>
            @endif

            <form method="POST" action="{{ route('siswa.praktikalitas.store') }}" class="space-y-6">
                @csrf

                @php
                    $kelompokAspek = $instrumen->groupBy('aspek');
                @endphp

                @foreach ($kelompokAspek as $namaAspek => $daftarButir)
                    <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
                        <div class="bg-surface px-6 py-3.5 border-b border-border flex items-center justify-between">
                            <h2 class="text-sm font-extrabold text-ink uppercase tracking-wider flex items-center gap-2">
                                <x-ikon nama="layers" ukuran="16" class="text-green-700" />
                                <span>Aspek: {{ $namaAspek }}</span>
                            </h2>
                            <span class="text-xs text-muted font-bold">{{ $daftarButir->count() }} Pernyataan</span>
                        </div>

                        <div class="divide-y divide-border p-4 sm:p-6 space-y-6 sm:space-y-0 sm:divide-y">
                            @foreach ($daftarButir as $item)
                                <div class="py-4 first:pt-0 last:pb-0 space-y-3">
                                    <div class="flex items-start gap-3">
                                        <span class="w-6 h-6 rounded-full bg-green-100 text-green-800 text-xs font-black flex items-center justify-center shrink-0 mt-0.5">
                                            {{ $item->urutan }}
                                        </span>
                                        <p class="text-sm font-bold text-ink leading-snug">
                                            {{ $item->pernyataan }}
                                        </p>
                                    </div>

                                    <!-- Opsi Skala Likert 1-4 -->
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:pl-9">
                                        @foreach ([
                                            4 => '4 - Sangat Setuju',
                                            3 => '3 - Setuju',
                                            2 => '2 - Tidak Setuju',
                                            1 => '1 - Sangat Tidak Setuju',
                                        ] as $nilai => $label)
                                            <label class="relative flex items-center gap-2.5 p-3 rounded-xl border border-border cursor-pointer hover:bg-green-50/70 hover:border-green-600 transition-all text-xs has-checked:bg-green-100 has-checked:border-green-700 has-checked:text-green-900 font-bold">
                                                <input type="radio" name="skor[{{ $item->id }}]" value="{{ $nilai }}"
                                                    {{ old("skor.{$item->id}") == $nilai ? 'checked' : '' }}
                                                    required
                                                    class="w-4 h-4 text-green-700 focus:ring-green-700">
                                                <span>{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>

                                    @error("skor.{$item->id}")
                                        <p class="text-xs text-red-500 sm:pl-9">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <!-- Kolom Saran / Masukan Bebas -->
                <div class="bg-white rounded-2xl border border-border p-6 shadow-xs space-y-3">
                    <label for="saran" class="block text-sm font-extrabold text-ink flex items-center gap-2">
                        <x-ikon nama="message-square" ukuran="16" class="text-green-700" />
                        <span>Saran, Kritik, atau Masukan Tambahan (Opsional)</span>
                    </label>
                    <p class="text-xs text-muted">
                        Apakah ada tampilan yang membingungkan, fitur yang diinginkan, atau kendala yang Anda alami saat menggunakan website ini?
                    </p>
                    <textarea id="saran" name="saran" rows="3" placeholder="Tuliskan saran atau pengalaman Anda belajar dengan website ini..."
                        class="w-full rounded-xl border border-border p-3.5 text-xs text-ink focus:outline-none focus:ring-2 focus:ring-green-700">{{ old('saran') }}</textarea>
                </div>

                <!-- Tombol Submit -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <x-tombol tipe="garis" href="{{ route('siswa.beranda') }}">
                        Batal
                    </x-tombol>
                    <x-tombol tipe="utama" class="gap-2 !px-6 !py-3 text-sm">
                        <span>Kirim Penilaian Kepraktisan</span>
                        <x-ikon nama="send" ukuran="16" />
                    </x-tombol>
                </div>
            </form>
        @endif
    </div>
</x-layouts.siswa>
