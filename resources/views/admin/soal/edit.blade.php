<x-layouts.admin judul="Ubah Soal: {{ $kuis->judul }}">
    <div class="max-w-3xl mx-auto space-y-6" x-data="{ tipeSoal: '{{ old('tipe_soal', $soal->tipe_soal) }}' }">
        <div>
            <a href="{{ route('admin.kuis.soal.index', $kuis) }}" class="text-xs font-bold text-green-700 hover:underline mb-1 inline-block">
                &larr; Kembali ke Daftar Soal
            </a>
            <h1 class="text-2xl font-extrabold text-ink">Ubah Butir Soal</h1>
            <p class="text-sm text-muted mt-1">Perbarui butir soal {{ $kuis->judul }}.</p>
        </div>

        @if ($punyaJawaban)
            <x-pita-info tipe="peringatan">
                <strong>Peringatan Integritas Data Penelitian:</strong> Soal ini sudah dikerjakan oleh siswa. Untuk menjaga keabsahan data hasil penelitian, tipe soal, bobot poin, dan kunci jawaban <strong>terkunci dan tidak dapat diubah</strong>. Anda hanya dapat memperbaiki redaksi teks pertanyaan.
            </x-pita-info>
        @endif

        @if ($errors->any())
            <x-pita-info tipe="galat">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-pita-info>
        @endif

        <x-kartu class="p-6">
            <form method="POST" action="{{ route('admin.kuis.soal.update', [$kuis, $soal]) }}" class="space-y-4">
                @csrf
                @method('PUT')

                @if ($punyaJawaban)
                    <input type="hidden" name="tipe_soal" value="{{ $soal->tipe_soal }}">
                    <input type="hidden" name="bobot_poin" value="{{ $soal->bobot_poin }}">
                @endif

                <div x-show="!{{ $punyaJawaban ? 'true' : 'false' }}">
                    <label class="block text-sm font-bold text-ink mb-1">Tipe Soal</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="flex flex-col items-center justify-center p-3 rounded-xl border border-border cursor-pointer hover:bg-green-50 text-center" :class="{ 'border-green-700 bg-green-50': tipeSoal === 'pilihan_ganda' }">
                            <input type="radio" name="tipe_soal" value="pilihan_ganda" x-model="tipeSoal" class="text-green-700 focus:ring-green-700 mb-1" {{ $punyaJawaban ? 'disabled' : '' }}>
                            <span class="text-xs font-bold text-ink">Pilihan Ganda</span>
                        </label>
                        <label class="flex flex-col items-center justify-center p-3 rounded-xl border border-border cursor-pointer hover:bg-sky-50 text-center" :class="{ 'border-sky-700 bg-sky-50': tipeSoal === 'isian' }">
                            <input type="radio" name="tipe_soal" value="isian" x-model="tipeSoal" class="text-sky-700 focus:ring-sky-700 mb-1" {{ $punyaJawaban ? 'disabled' : '' }}>
                            <span class="text-xs font-bold text-ink">Isian Singkat</span>
                        </label>
                        <label class="flex flex-col items-center justify-center p-3 rounded-xl border border-border cursor-pointer hover:bg-yellow-50 text-center" :class="{ 'border-warning bg-[#FEF3C7]': tipeSoal === 'esai' }">
                            <input type="radio" name="tipe_soal" value="esai" x-model="tipeSoal" class="text-warning focus:ring-warning mb-1" {{ $punyaJawaban ? 'disabled' : '' }}>
                            <span class="text-xs font-bold text-ink">Esai (Di Akhir)</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label for="pertanyaan" class="block text-sm font-bold text-ink mb-1">Teks Pertanyaan</label>
                    <textarea id="pertanyaan" name="pertanyaan" rows="3" required
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">{{ old('pertanyaan', $soal->pertanyaan) }}</textarea>
                </div>

                <div x-show="!{{ $punyaJawaban ? 'true' : 'false' }}">
                    <label for="bobot_poin" class="block text-sm font-bold text-ink mb-1">Bobot Poin</label>
                    <input type="number" id="bobot_poin" name="bobot_poin" value="{{ old('bobot_poin', $soal->bobot_poin) }}" min="1" {{ $punyaJawaban ? 'disabled' : 'required' }}
                        class="w-32 rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                </div>

                @if ($punyaJawaban)
                    @if ($soal->tipe_soal === 'pilihan_ganda')
                        <div class="space-y-3 pt-2 border-t border-border">
                            <span class="block text-sm font-bold text-ink">Perbaikan Redaksi Opsi Pilihan Ganda (Kunci Jawaban Benar Terkunci)</span>
                            @foreach ($soal->pilihanJawaban as $idx => $pilihan)
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs {{ $pilihan->apakah_benar ? 'bg-green-700 text-white' : 'bg-surface text-muted' }}">
                                        {{ chr(65 + $idx) }}
                                    </span>
                                    <input type="text" name="pilihan_terkunci[{{ $pilihan->id }}]" value="{{ old("pilihan_terkunci.{$pilihan->id}", $pilihan->teks_pilihan) }}" required
                                        class="flex-1 rounded-xl border border-border px-3.5 py-2 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                                    @if ($pilihan->apakah_benar)
                                        <span class="text-xs font-bold text-green-700">Kunci Benar</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    <!-- Bagian Opsi Pilihan Ganda -->
                    <div x-show="tipeSoal === 'pilihan_ganda'" class="space-y-3 pt-2 border-t border-border">
                        <span class="block text-sm font-bold text-ink">Opsi Pilihan Jawaban (Minimal 2 opsi, pilih TEPAT SATU yang benar)</span>
                        @php
                            $opsiTersimpan = $soal->pilihanJawaban;
                        @endphp
                        @for ($i = 0; $i < 4; $i++)
                            @php
                                $item = $opsiTersimpan[$i] ?? null;
                            @endphp
                            <div class="flex items-center gap-3">
                                <input type="radio" name="pilihan[{{ $i }}][apakah_benar]" value="1" {{ old("pilihan.{$i}.apakah_benar", $item ? $item->apakah_benar : false) ? 'checked' : ($i === 0 && ! $item ? 'checked' : '') }}
                                    class="text-green-700 focus:ring-green-700 cursor-pointer">
                                <input type="text" name="pilihan[{{ $i }}][teks]" value="{{ old("pilihan.{$i}.teks", $item ? $item->teks_pilihan : '') }}" placeholder="Teks opsi {{ chr(65 + $i) }}"
                                    class="flex-1 rounded-xl border border-border px-3.5 py-2 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                            </div>
                        @endfor
                    </div>

                    <!-- Bagian Kunci Isian Singkat -->
                    <div x-show="tipeSoal === 'isian'" class="space-y-3 pt-2 border-t border-border">
                        <span class="block text-sm font-bold text-ink">Kunci Jawaban Isian Singkat</span>
                        @php
                            $kunciTersimpan = $soal->pilihanJawaban->pluck('teks_pilihan')->all();
                        @endphp
                        @for ($i = 0; $i < 3; $i++)
                            <div>
                                <input type="text" name="kunci_isian[{{ $i }}]" value="{{ old("kunci_isian.{$i}", $kunciTersimpan[$i] ?? '') }}" placeholder="Variasi kunci jawaban yang benar {{ $i + 1 }}"
                                    class="w-full rounded-xl border border-border px-3.5 py-2 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-sky-700 text-sm">
                            </div>
                        @endfor
                    </div>
                @endif

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-border">
                    <x-tombol tipe="garis" href="{{ route('admin.kuis.soal.index', $kuis) }}">Batal</x-tombol>
                    <x-tombol tipe="utama" type="submit">Perbarui Butir Soal</x-tombol>
                </div>
            </form>
        </x-kartu>
    </div>
</x-layouts.admin>
