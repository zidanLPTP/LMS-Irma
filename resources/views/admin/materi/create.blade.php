<x-layouts.admin judul="Tambah Materi: {{ $bab->judul }}">
    <div class="max-w-2xl mx-auto space-y-6" x-data="{ tipeKonten: '{{ old('tipe', 'pdf') }}' }">
        <div>
            <a href="{{ route('admin.bab.materi.index', $bab) }}" class="text-xs font-bold text-green-700 hover:underline mb-1 inline-block">
                &larr; Kembali ke Materi Bab
            </a>
            <h1 class="text-2xl font-extrabold text-ink">Tambah Materi Baru</h1>
            <p class="text-sm text-muted mt-1">Tambahkan materi slide presentasi (PDF) atau video pembelajaran untuk {{ $bab->judul }}.</p>
        </div>

        <!-- Petunjuk Ekspor PPT ke PDF -->
        <x-pita-info tipe="info">
            <span class="font-bold block mb-1">Petunjuk untuk Irma:</span>
            Shared hosting tidak mendukung konversi otomatis PPT. Silakan buka slide PowerPoint Anda di laptop, klik <strong>File &rarr; Save As / Export &rarr; PDF (*.pdf)</strong>, lalu unggah berkas PDF tersebut di sini. Maksimal ukuran 20 MB.
        </x-pita-info>

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
            <form method="POST" action="{{ route('admin.bab.materi.store', $bab) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label for="judul" class="block text-sm font-bold text-ink mb-1">Judul Materi</label>
                    <input type="text" id="judul" name="judul" value="{{ old('judul') }}" required placeholder="Contoh: Slide Pembelahan Mitosis dan Meiosis"
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-bold text-ink mb-1">Tipe Konten</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 p-3 rounded-xl border border-border cursor-pointer hover:bg-green-50" :class="{ 'border-green-700 bg-green-50': tipeKonten === 'pdf' }">
                            <input type="radio" name="tipe" value="pdf" x-model="tipeKonten" class="text-green-700 focus:ring-green-700">
                            <span class="text-sm font-bold text-ink">Slide PDF</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 rounded-xl border border-border cursor-pointer hover:bg-sky-50" :class="{ 'border-sky-700 bg-sky-50': tipeKonten === 'youtube' }">
                            <input type="radio" name="tipe" value="youtube" x-model="tipeKonten" class="text-sky-700 focus:ring-sky-700">
                            <span class="text-sm font-bold text-ink">Video YouTube</span>
                        </label>
                    </div>
                </div>

                <!-- Input Berkas PDF -->
                <div x-show="tipeKonten === 'pdf'" class="space-y-1">
                    <label for="berkas_pdf" class="block text-sm font-bold text-ink">Berkas PDF</label>
                    <input type="file" id="berkas_pdf" name="berkas_pdf" accept="application/pdf"
                        class="w-full text-sm text-muted file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-green-100 file:text-green-700 hover:file:bg-green-200 cursor-pointer">
                    <p class="text-xs text-muted">Format hanya .pdf, tersimpan aman di storage privat server.</p>
                </div>

                <!-- Input YouTube -->
                <div x-show="tipeKonten === 'youtube'" class="space-y-1">
                    <label for="url_youtube" class="block text-sm font-bold text-ink">Tautan Video YouTube</label>
                    <input type="url" id="url_youtube" name="url_youtube" value="{{ old('url_youtube') }}" placeholder="https://www.youtube.com/watch?v=xxxxxx atau youtu.be/xxxxxx"
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-sky-700 text-sm">
                    <p class="text-xs text-muted">Sistem otomatis menyajikan melalui domain aman youtube-nocookie.com.</p>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-border">
                    <x-tombol tipe="garis" href="{{ route('admin.bab.materi.index', $bab) }}">Batal</x-tombol>
                    <x-tombol tipe="utama" type="submit">Simpan Materi</x-tombol>
                </div>
            </form>
        </x-kartu>
    </div>
</x-layouts.admin>
