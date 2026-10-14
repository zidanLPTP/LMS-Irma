<x-layouts.induk judul="Kendala Sistem (500)">
    <div class="max-w-md mx-auto my-auto py-12">
        <x-kartu class="p-6 sm:p-8 text-center space-y-5">
            <div class="inline-flex p-3 bg-red-100 rounded-2xl text-red-700">
                <x-ikon nama="alert-triangle" ukuran="36" kelas="text-red-700" />
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-extrabold text-ink">Terjadi Kendala pada Sistem</h1>
                <p class="text-sm text-muted leading-relaxed">
                    Mohon maaf, sistem sedang mengalami kendala teknis internal. Tim teknis telah mencatat kejadian ini untuk segera ditangani.
                </p>
            </div>

            <div class="pt-4 border-t border-border flex flex-col gap-2">
                <x-tombol tipe="utama" href="/" class="w-full justify-center !min-h-[44px] text-sm font-bold">
                    <span>Kembali ke Halaman Awal</span>
                    <x-ikon nama="arrow-right" ukuran="16" />
                </x-tombol>
            </div>
        </x-kartu>
    </div>
</x-layouts.induk>
