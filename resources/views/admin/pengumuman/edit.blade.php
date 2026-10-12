<x-layouts.admin judul="Ubah Pengumuman">
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-extrabold text-ink">Ubah Pengumuman</h1>
            <p class="text-sm text-muted mt-1">Perbarui isi atau status tayang pengumuman.</p>
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

        <form method="POST" action="{{ route('admin.pengumuman.update', $pengumuman) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <x-kartu class="p-6 space-y-4">
                <div>
                    <label for="judul" class="block text-sm font-bold text-ink mb-1">
                        {{ __('pengumuman.label_judul') }} <span class="text-rose-600">*</span>
                    </label>
                    <input type="text" id="judul" name="judul" value="{{ old('judul', $pengumuman->judul) }}" required
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                </div>

                <div>
                    <label for="isi" class="block text-sm font-bold text-ink mb-1">
                        {{ __('pengumuman.label_isi') }} <span class="text-rose-600">*</span>
                    </label>
                    <p class="text-xs text-muted mb-2">{{ __('pengumuman.keterangan_isi') }}</p>
                    <textarea id="isi" name="isi" rows="6" required
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">{{ old('isi', $pengumuman->isi) }}</textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="status" class="block text-sm font-bold text-ink mb-1">
                            {{ __('pengumuman.label_status') }} <span class="text-rose-600">*</span>
                        </label>
                        <select id="status" name="status" required
                            class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                            <option value="terbit" {{ old('status', $pengumuman->status) === 'terbit' ? 'selected' : '' }}>Terbitkan Sekarang</option>
                            <option value="draf" {{ old('status', $pengumuman->status) === 'draf' ? 'selected' : '' }}>Simpan sebagai Draf</option>
                        </select>
                    </div>

                    <div>
                        <label for="tanggal_kedaluwarsa" class="block text-sm font-bold text-ink mb-1">
                            {{ __('pengumuman.label_tanggal_kedaluwarsa') }}
                        </label>
                        <input type="date" id="tanggal_kedaluwarsa" name="tanggal_kedaluwarsa"
                            value="{{ old('tanggal_kedaluwarsa', $pengumuman->tanggal_kedaluwarsa?->toDateString()) }}"
                            class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                    </div>
                </div>

                <div class="pt-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="disematkan" value="1"
                            {{ old('disematkan', $pengumuman->disematkan) ? 'checked' : '' }}
                            class="rounded border-border text-green-700 focus:ring-green-700">
                        <span class="text-sm font-bold text-ink">{{ __('pengumuman.label_disematkan') }}</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-border flex items-center justify-between">
                    <a href="{{ route('admin.pengumuman.index') }}" class="text-sm font-bold text-muted hover:text-ink">
                        Batal
                    </a>
                    <x-tombol tipe="utama" type="submit" class="!px-6">
                        Simpan Perubahan
                    </x-tombol>
                </div>
            </x-kartu>
        </form>
    </div>
</x-layouts.admin>
