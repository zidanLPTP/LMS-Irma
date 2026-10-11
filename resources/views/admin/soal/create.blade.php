<x-layouts.admin judul="Tambah Soal: {{ $kuis->judul }}">
    <div class="max-w-3xl mx-auto space-y-6" x-data="{ tipeSoal: '{{ old('tipe_soal', 'pilihan_ganda') }}' }">
        <div>
            <a href="{{ route('admin.kuis.soal.index', $kuis) }}" class="text-xs font-bold text-green-700 hover:underline mb-1 inline-block">
                &larr; Kembali ke Daftar Soal
            </a>
            <h1 class="text-2xl font-extrabold text-ink">Tambah Butir Soal Baru</h1>
            <p class="text-sm text-muted mt-1">Tambahkan butir soal ke {{ $kuis->judul }}.</p>
        </div>

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
            <form method="POST" action="{{ route('admin.kuis.soal.store', $kuis) }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-bold text-ink mb-1">Tipe Soal</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="flex flex-col items-center justify-center p-3 rounded-xl border border-border cursor-pointer hover:bg-green-50 text-center" :class="{ 'border-green-700 bg-green-50': tipeSoal === 'pilihan_ganda' }">
                            <input type="radio" name="tipe_soal" value="pilihan_ganda" x-model="tipeSoal" class="text-green-700 focus:ring-green-700 mb-1">
                            <span class="text-xs font-bold text-ink">Pilihan Ganda</span>
                        </label>
                        <label class="flex flex-col items-center justify-center p-3 rounded-xl border border-border cursor-pointer hover:bg-sky-50 text-center" :class="{ 'border-sky-700 bg-sky-50': tipeSoal === 'isian' }">
                            <input type="radio" name="tipe_soal" value="isian" x-model="tipeSoal" class="text-sky-700 focus:ring-sky-700 mb-1">
                            <span class="text-xs font-bold text-ink">Isian Singkat</span>
                        </label>
                        <label class="flex flex-col items-center justify-center p-3 rounded-xl border border-border cursor-pointer hover:bg-yellow-50 text-center" :class="{ 'border-warning bg-[#FEF3C7]': tipeSoal === 'esai' }">
                            <input type="radio" name="tipe_soal" value="esai" x-model="tipeSoal" class="text-warning focus:ring-warning mb-1">
                            <span class="text-xs font-bold text-ink">Esai (Di Akhir)</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label for="pertanyaan" class="block text-sm font-bold text-ink mb-1">Teks Pertanyaan</label>
                    <textarea id="pertanyaan" name="pertanyaan" rows="3" required placeholder="Tuliskan pertanyaan soal di sini..."
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">{{ old('pertanyaan') }}</textarea>
                </div>

                <div>
                    <label for="bobot_poin" class="block text-sm font-bold text-ink mb-1">Bobot Poin</label>
                    <input type="number" id="bobot_poin" name="bobot_poin" value="{{ old('bobot_poin', 1) }}" min="1" required
                        class="w-32 rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                    <p class="text-xs text-muted mt-1">Bobot minimal 1 poin (default 1).</p>
                </div>

                <!-- Bagian Opsi Pilihan Ganda -->
                <div x-show="tipeSoal === 'pilihan_ganda'" class="space-y-3 pt-2 border-t border-border">
                    <span class="block text-sm font-bold text-ink">Opsi Pilihan Jawaban (Minimal 2 opsi, pilih TEPAT SATU yang benar)</span>
                    @for ($i = 0; $i < 4; $i++)
                        <div class="flex items-center gap-3">
                            <input type="radio" name="pilihan[{{ $i }}][apakah_benar]" value="1" {{ old("pilihan.{$i}.apakah_benar") ? 'checked' : ($i === 0 ? 'checked' : '') }}
                                class="text-green-700 focus:ring-green-700 cursor-pointer" title="Tandai sebagai jawaban benar">
                            <input type="text" name="pilihan[{{ $i }}][teks]" value="{{ old("pilihan.{$i}.teks") }}" placeholder="Teks opsi {{ chr(65 + $i) }}"
                                class="flex-1 rounded-xl border border-border px-3.5 py-2 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                        </div>
                    @endfor
                </div>

                <!-- Bagian Kunci Isian Singkat -->
                <div x-show="tipeSoal === 'isian'" class="space-y-3 pt-2 border-t border-border">
                    <span class="block text-sm font-bold text-ink">Kunci Jawaban Isian Singkat (Minimal 1 kunci, boleh beberapa variasi)</span>
                    <p class="text-xs text-muted">Sistem otomatis mencocokkan tanpa membedakan huruf besar/kecil dan spasi ganda.</p>
                    @for ($i = 0; $i < 3; $i++)
                        <div>
                            <input type="text" name="kunci_isian[{{ $i }}]" value="{{ old("kunci_isian.{$i}") }}" placeholder="Variasi kunci jawaban yang benar {{ $i + 1 }}"
                                class="w-full rounded-xl border border-border px-3.5 py-2 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-sky-700 text-sm">
                        </div>
                    @endfor
                </div>

                <!-- Bagian Esai -->
                <div x-show="tipeSoal === 'esai'" class="pt-2 border-t border-border">
                    <div class="p-3 bg-yellow-50 rounded-xl text-xs text-warning font-semibold">
                        Soal bertipe esai tidak membutuhkan kunci jawaban otomatis. Irma akan menilai jawaban esai secara manual dari halaman penilaian esai setelah kuis diserahkan.
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-border">
                    <x-tombol tipe="garis" href="{{ route('admin.kuis.soal.index', $kuis) }}">Batal</x-tombol>
                    <x-tombol tipe="utama" type="submit">Simpan Butir Soal</x-tombol>
                </div>
            </form>
        </x-kartu>
    </div>
</x-layouts.admin>
