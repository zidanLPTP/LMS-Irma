<x-layouts.admin judul="Dasbor Admin">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-ink">Dasbor Pengelola</h1>
            <p class="text-muted text-sm mt-1">Selamat datang, {{ auth()->user()->nama_lengkap }}. Panel pengelolaan media penelitian.</p>
        </div>

        @php
            $antreanEsai = \App\Models\PercobaanKuis::where('status_pengerjaan', 'selesai')
                ->where('dibatalkan', false)
                ->where('esai_menunggu_nilai', true)
                ->count();
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-kartu class="p-5 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-muted uppercase tracking-wider">Penilaian Esai</span>
                    <p class="text-3xl font-black {{ $antreanEsai > 0 ? 'text-amber-600' : 'text-green-700' }} mt-1">
                        {{ $antreanEsai }}
                    </p>
                    <span class="text-xs text-muted">menunggu dinilai</span>
                </div>
                <x-tombol tipe="garis" href="{{ route('admin.penilaian-esai.index') }}" class="!min-h-[38px] !px-3 text-xs">
                    Lihat Antrean
                </x-tombol>
            </x-kartu>

            <x-kartu class="p-5">
                <span class="text-xs font-bold text-muted uppercase tracking-wider">Peran Anda</span>
                <p class="text-2xl font-extrabold text-green-700 mt-1">Administrator</p>
            </x-kartu>

            <x-kartu class="p-5">
                <span class="text-xs font-bold text-muted uppercase tracking-wider">Status LMS</span>
                <p class="text-2xl font-extrabold text-sky-700 mt-1">Aktif</p>
            </x-kartu>
        </div>
    </div>
</x-layouts.admin>
