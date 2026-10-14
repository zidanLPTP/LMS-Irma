<x-layouts.induk judul="Halaman Tidak Ditemukan (404)">
    <div class="max-w-md mx-auto my-auto py-12">
        <x-kartu class="p-6 sm:p-8 text-center space-y-5">
            <div class="inline-flex p-3 bg-amber-100 rounded-2xl text-amber-700">
                <x-ikon nama="file-question" ukuran="36" kelas="text-amber-700" />
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-extrabold text-ink">Halaman Tidak Ditemukan</h1>
                <p class="text-sm text-muted leading-relaxed">
                    Halaman atau materi yang kamu tuju tidak ditemukan atau tautan sudah dipindahkan. Silakan periksa kembali alamat URL kamu.
                </p>
            </div>

            <div class="pt-4 border-t border-border flex flex-col gap-2">
                @auth
                    @if (auth()->user()->apakahAdmin())
                        <x-tombol tipe="utama" href="{{ route('admin.beranda') }}" class="w-full justify-center !min-h-[44px] text-sm font-bold">
                            <span>Kembali ke Dasbor Admin</span>
                            <x-ikon nama="arrow-right" ukuran="16" />
                        </x-tombol>
                    @else
                        <x-tombol tipe="utama" href="{{ route('siswa.beranda') }}" class="w-full justify-center !min-h-[44px] text-sm font-bold">
                            <span>Kembali ke Beranda Siswa</span>
                            <x-ikon nama="arrow-right" ukuran="16" />
                        </x-tombol>
                    @endif
                @else
                    <x-tombol tipe="utama" href="{{ route('masuk') }}" class="w-full justify-center !min-h-[44px] text-sm font-bold">
                        <span>Ke Halaman Masuk</span>
                        <x-ikon nama="arrow-right" ukuran="16" />
                    </x-tombol>
                @endauth
            </div>
        </x-kartu>
    </div>
</x-layouts.induk>
