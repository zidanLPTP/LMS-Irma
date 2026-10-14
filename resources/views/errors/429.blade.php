<x-layouts.induk judul="Terlalu Banyak Permintaan (429)">
    <div class="max-w-md mx-auto my-auto py-12">
        <x-kartu class="p-6 sm:p-8 text-center space-y-5">
            <div class="inline-flex p-3 bg-amber-100 rounded-2xl text-amber-700">
                <x-ikon nama="hourglass" ukuran="36" kelas="text-amber-700" />
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-extrabold text-ink">Mohon Tunggu Sebentar</h1>
                <p class="text-sm text-muted leading-relaxed">
                    Sistem mendeteksi terlalu banyak permintaan dalam waktu singkat demi keamanan server. Silakan tunggu sekitar satu menit lalu coba kembali.
                </p>
            </div>

            <div class="pt-4 border-t border-border">
                <x-tombol tipe="utama" href="javascript:location.reload()" class="w-full justify-center !min-h-[44px] text-sm font-bold">
                    <span>Muat Ulang Halaman</span>
                    <x-ikon nama="refresh-cw" ukuran="16" />
                </x-tombol>
            </div>
        </x-kartu>
    </div>
</x-layouts.induk>
