<x-layouts.induk judul="Sesi Berakhir">
    <div class="max-w-md mx-auto">
        <x-kartu class="p-6 sm:p-8 text-center space-y-5">
            <div class="inline-flex p-3 bg-amber-100 rounded-2xl text-amber-700">
                <x-ikon nama="clock" ukuran="36" kelas="text-amber-700" />
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-extrabold text-ink">Sesi Kamu Sudah Berakhir</h1>
                <p class="text-sm text-muted leading-relaxed">
                    Sesi kamu telah berakhir demi keamanan akun. Silakan masuk kembali untuk melanjutkan aktivitas pembelajaran atau administrasi.
                </p>
            </div>

            <div class="pt-4 border-t border-border">
                <x-tombol tipe="utama" href="{{ route('masuk') }}" class="w-full justify-center !min-h-[44px] text-sm font-bold">
                    <span>Masuk Kembali ke LMS</span>
                    <x-ikon nama="arrow-right" ukuran="16" />
                </x-tombol>
            </div>
        </x-kartu>
    </div>
</x-layouts.induk>
