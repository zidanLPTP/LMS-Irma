<x-layouts.induk judul="Layanan Dalam Pemeliharaan (503)">
    <div class="max-w-md mx-auto my-auto py-12">
        <x-kartu class="p-6 sm:p-8 text-center space-y-5">
            <div class="inline-flex p-3 bg-blue-100 rounded-2xl text-blue-700">
                <x-ikon nama="wrench" ukuran="36" kelas="text-blue-700" />
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-extrabold text-ink">Sedang Dalam Pemeliharaan</h1>
                <p class="text-sm text-muted leading-relaxed">
                    Sistem BioSTEMX sedang dalam proses pemeliharaan rutin atau peningkatan performa. Kami akan segera kembali dalam beberapa saat.
                </p>
            </div>

            <div class="pt-4 border-t border-border flex flex-col gap-2">
                <x-tombol tipe="utama" href="javascript:location.reload()" class="w-full justify-center !min-h-[44px] text-sm font-bold">
                    <span>Cek Kembali Halaman</span>
                    <x-ikon nama="refresh-cw" ukuran="16" />
                </x-tombol>
            </div>
        </x-kartu>
    </div>
</x-layouts.induk>
