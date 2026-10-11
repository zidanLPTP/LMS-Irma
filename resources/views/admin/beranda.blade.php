<x-layouts.induk judul="Dasbor Admin">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-ink">Dasbor Pengelola</h1>
            <p class="text-muted text-sm mt-1">Selamat datang, {{ auth()->user()->nama_lengkap }}. Panel pengelolaan media penelitian.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-kartu class="p-5">
                <span class="text-xs font-bold text-muted uppercase tracking-wider">Peran Anda</span>
                <p class="text-2xl font-extrabold text-green-700 mt-1">Administrator</p>
            </x-kartu>
            <x-kartu class="p-5">
                <span class="text-xs font-bold text-muted uppercase tracking-wider">Status LMS</span>
                <p class="text-2xl font-extrabold text-sky-700 mt-1">Aktif</p>
            </x-kartu>
            <x-kartu class="p-5">
                <span class="text-xs font-bold text-muted uppercase tracking-wider">Logika Kuis</span>
                <p class="text-2xl font-extrabold text-ink mt-1">TDD Siap</p>
            </x-kartu>
        </div>
    </div>
</x-layouts.induk>
