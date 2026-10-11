<x-layouts.admin judul="Buat Kuis Baru">
    <div class="max-w-2xl mx-auto space-y-6" x-data="{ tipeKuis: '{{ old('tipe_kuis', 'kuis_bab') }}' }">
        <div>
            <a href="{{ route('admin.kuis.index') }}" class="text-xs font-bold text-green-700 hover:underline mb-1 inline-block">
                &larr; Kembali ke Daftar Kuis
            </a>
            <h1 class="text-2xl font-extrabold text-ink">Buat Kuis Baru</h1>
            <p class="text-sm text-muted mt-1">Atur kuis bab pembelajaran, pretest diagnosis, atau posttest akhir penelitian.</p>
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
            <form method="POST" action="{{ route('admin.kuis.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-bold text-ink mb-1">Tipe Kuis</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="flex flex-col items-center justify-center p-3 rounded-xl border border-border cursor-pointer hover:bg-green-50 text-center" :class="{ 'border-green-700 bg-green-50': tipeKuis === 'kuis_bab' }">
                            <input type="radio" name="tipe_kuis" value="kuis_bab" x-model="tipeKuis" class="text-green-700 focus:ring-green-700 mb-1">
                            <span class="text-xs font-bold text-ink">Kuis Bab</span>
                        </label>
                        <label class="flex flex-col items-center justify-center p-3 rounded-xl border border-border cursor-pointer hover:bg-yellow-50 text-center" :class="{ 'border-warning bg-[#FEF3C7]': tipeKuis === 'pretest' }">
                            <input type="radio" name="tipe_kuis" value="pretest" x-model="tipeKuis" class="text-warning focus:ring-warning mb-1">
                            <span class="text-xs font-bold text-ink">Pretest (1x)</span>
                        </label>
                        <label class="flex flex-col items-center justify-center p-3 rounded-xl border border-border cursor-pointer hover:bg-sky-50 text-center" :class="{ 'border-sky-700 bg-sky-50': tipeKuis === 'posttest' }">
                            <input type="radio" name="tipe_kuis" value="posttest" x-model="tipeKuis" class="text-sky-700 focus:ring-sky-700 mb-1">
                            <span class="text-xs font-bold text-ink">Posttest (1x)</span>
                        </label>
                    </div>
                </div>

                <div x-show="tipeKuis === 'kuis_bab'">
                    <label for="bab_id" class="block text-sm font-bold text-ink mb-1">Tautkan ke Bab Pembelajaran</label>
                    <select id="bab_id" name="bab_id"
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                        <option value="">-- Pilih Bab --</option>
                        @foreach ($daftarBab as $bab)
                            <option value="{{ $bab->id }}" {{ old('bab_id') == $bab->id ? 'selected' : '' }}>
                                {{ $bab->judul }} (Urutan {{ $bab->urutan }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="judul" class="block text-sm font-bold text-ink mb-1">Judul Kuis</label>
                    <input type="text" id="judul" name="judul" value="{{ old('judul') }}" required placeholder="Contoh: Kuis Bab 1: Struktur Sel"
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="batas_waktu_per_soal" class="block text-sm font-bold text-ink mb-1">
                            Waktu per Soal (Detik)
                            <span class="font-normal text-muted block text-xs">Kosongkan untuk memakai default ({{ $pengaturanDefault['batas_waktu_per_soal'] }}s)</span>
                        </label>
                        <input type="number" id="batas_waktu_per_soal" name="batas_waktu_per_soal" value="{{ old('batas_waktu_per_soal') }}" min="10" max="600" placeholder="{{ $pengaturanDefault['batas_waktu_per_soal'] }}"
                            class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                    </div>

                    <div>
                        <label for="nilai_lulus" class="block text-sm font-bold text-ink mb-1">
                            Nilai Kelulusan (KKM)
                            <span class="font-normal text-muted block text-xs">Kosongkan untuk memakai default ({{ $pengaturanDefault['nilai_lulus'] }})</span>
                        </label>
                        <input type="number" step="0.01" id="nilai_lulus" name="nilai_lulus" value="{{ old('nilai_lulus') }}" min="0" max="100" placeholder="{{ $pengaturanDefault['nilai_lulus'] }}"
                            class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                    </div>
                </div>

                <div x-show="tipeKuis === 'kuis_bab'">
                    <label for="maksimal_kesempatan" class="block text-sm font-bold text-ink mb-1">
                        Maksimal Kesempatan
                        <span class="font-normal text-muted block text-xs">Kosongkan untuk memakai default ({{ $pengaturanDefault['maksimal_kesempatan'] }}x)</span>
                    </label>
                    <input type="number" id="maksimal_kesempatan" name="maksimal_kesempatan" value="{{ old('maksimal_kesempatan') }}" min="1" max="10" placeholder="{{ $pengaturanDefault['maksimal_kesempatan'] }}"
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                </div>

                <div x-show="tipeKuis !== 'kuis_bab'" class="p-3 bg-sky-50 rounded-xl text-xs text-sky-700 font-semibold">
                    Pretest dan Posttest secara aturan sistem hanya boleh dikerjakan 1 kali (maksimal kesempatan tidak dapat diubah).
                </div>

                <div class="pt-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-ink font-semibold text-sm">
                        <input type="checkbox" name="acak_soal" value="1" {{ old('acak_soal', true) ? 'checked' : '' }}
                            class="rounded border-border text-green-700 focus:ring-green-700">
                        <span>Acak urutan soal (soal esai akan tetap otomatis di akhir)</span>
                    </label>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-border">
                    <x-tombol tipe="garis" href="{{ route('admin.kuis.index') }}">Batal</x-tombol>
                    <x-tombol tipe="utama" type="submit">Simpan Kuis</x-tombol>
                </div>
            </form>
        </x-kartu>
    </div>
</x-layouts.admin>
