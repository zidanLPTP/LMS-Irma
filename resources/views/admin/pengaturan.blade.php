<x-layouts.admin :judul="__('pengaturan.judul_halaman')">
    <div class="space-y-6 max-w-4xl">
        <!-- Header Halaman -->
        <div>
            <h1 class="text-2xl font-extrabold text-ink">{{ __('pengaturan.judul_halaman') }}</h1>
            <p class="text-sm text-muted mt-1">{{ __('pengaturan.deskripsi_halaman') }}</p>
        </div>

        @if ($adaPercobaanSiswa)
            <x-pita-info tipe="peringatan">
                {{ __('pengaturan.peringatan_percobaan_ada') }}
            </x-pita-info>
        @endif

        @if ($errors->any())
            <x-pita-info tipe="galat">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-pita-info>
        @endif

        <form method="POST" action="{{ route('admin.pengaturan.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Identitas Situs -->
            <x-kartu class="p-6 space-y-4">
                <h2 class="text-base font-extrabold text-ink border-b border-border pb-2">Identitas Situs</h2>

                <div>
                    <label for="nama_situs" class="block text-sm font-bold text-ink mb-1">
                        {{ __('pengaturan.nama_situs') }}
                    </label>
                    <input type="text" id="nama_situs" name="nama_situs"
                        value="{{ old('nama_situs', $pengaturan['nama_situs']) }}" required
                        class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                </div>

                <div>
                    <label for="logo" class="block text-sm font-bold text-ink mb-1">
                        {{ __('pengaturan.logo_situs') }}
                    </label>
                    <p class="text-xs text-muted mb-3">{{ __('pengaturan.logo_keterangan') }}</p>

                    @if ($pengaturan['logo_situs'] && file_exists(public_path($pengaturan['logo_situs'])))
                        <div class="flex items-center gap-4 mb-4 p-3 bg-surface rounded-xl border border-border">
                            <img src="{{ asset($pengaturan['logo_situs']) }}" alt="Logo Saat Ini" class="h-12 w-auto max-w-[140px] object-contain rounded-lg border border-border bg-white p-1">
                            <span class="text-xs text-muted font-bold">Logo saat ini sedang aktif</span>
                        </div>
                    @endif

                    <input type="file" id="logo" name="logo" accept=".png,.jpg,.jpeg,.webp" aria-label="{{ __('pengaturan.logo_situs') }}"
                        class="block w-full text-sm text-ink file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-green-100 file:text-green-800 hover:file:bg-green-200 cursor-pointer">
                </div>
            </x-kartu>

            <!-- Kebijakan Kuis & Nilai -->
            <x-kartu class="p-6 space-y-4">
                <h2 class="text-base font-extrabold text-ink border-b border-border pb-2">Kebijakan Kuis & Ketuntasan</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="nilai_lulus" class="block text-sm font-bold text-ink mb-1">
                            {{ __('pengaturan.nilai_lulus') }}
                        </label>
                        <input type="number" id="nilai_lulus" name="nilai_lulus" min="0" max="100" step="1"
                            value="{{ old('nilai_lulus', (int) $pengaturan['nilai_lulus']) }}" required
                            class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                        <p class="text-xs text-muted mt-1">{{ __('pengaturan.nilai_lulus_keterangan') }}</p>
                    </div>

                    <div>
                        <label for="batas_waktu_per_soal" class="block text-sm font-bold text-ink mb-1">
                            {{ __('pengaturan.batas_waktu_per_soal') }}
                        </label>
                        <input type="number" id="batas_waktu_per_soal" name="batas_waktu_per_soal" min="10" max="600" step="5"
                            value="{{ old('batas_waktu_per_soal', (int) $pengaturan['batas_waktu_per_soal']) }}" required
                            class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                        <p class="text-xs text-muted mt-1">{{ __('pengaturan.batas_waktu_keterangan') }}</p>
                    </div>

                    <div>
                        <label for="maksimal_kesempatan" class="block text-sm font-bold text-ink mb-1">
                            {{ __('pengaturan.maksimal_kesempatan') }}
                        </label>
                        <input type="number" id="maksimal_kesempatan" name="maksimal_kesempatan" min="1" max="5" step="1"
                            value="{{ old('maksimal_kesempatan', (int) $pengaturan['maksimal_kesempatan']) }}" required
                            class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                        <p class="text-xs text-muted mt-1">{{ __('pengaturan.maksimal_kesempatan_keterangan') }}</p>
                    </div>
                </div>
            </x-kartu>

            <!-- Kontrol Pendaftaran Siswa -->
            <x-kartu class="p-6 space-y-4">
                <h2 class="text-base font-extrabold text-ink border-b border-border pb-2">Kontrol Pendaftaran Siswa</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="pendaftaran_siswa" class="block text-sm font-bold text-ink mb-1">
                            Status Pendaftaran Siswa
                        </label>
                        <select id="pendaftaran_siswa" name="pendaftaran_siswa" required
                            class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                            <option value="buka" {{ old('pendaftaran_siswa', $pengaturan['pendaftaran_siswa']) === 'buka' ? 'selected' : '' }}>Buka (Siswa dapat mendaftar mandiri)</option>
                            <option value="tutup" {{ old('pendaftaran_siswa', $pengaturan['pendaftaran_siswa']) === 'tutup' ? 'selected' : '' }}>Tutup (Pendaftaran mandiri dinonaktifkan)</option>
                        </select>
                        <p class="text-xs text-muted mt-1">Bila ditutup, halaman pendaftaran menampilkan pesan ramah bahwa pendaftaran sedang ditutup.</p>
                    </div>

                    <div>
                        <label for="kode_pendaftaran" class="block text-sm font-bold text-ink mb-1">
                            Kode Pendaftaran Khusus <span class="font-normal text-muted">(opsional)</span>
                        </label>
                        <input type="text" id="kode_pendaftaran" name="kode_pendaftaran"
                            value="{{ old('kode_pendaftaran', $pengaturan['kode_pendaftaran']) }}" placeholder="Kosongkan jika pendaftaran terbuka bebas"
                            class="w-full rounded-xl border border-border px-3.5 py-2.5 text-ink bg-white focus:outline-none focus:ring-2 focus:ring-green-700 text-sm">
                        <p class="text-xs text-muted mt-1">Bila diisi, siswa wajib memasukkan kode ini saat mendaftar. Kode ini dirahasiakan dan tidak tampil ke publik.</p>
                    </div>
                </div>
            </x-kartu>

            <div class="flex justify-end">
                <x-tombol tipe="utama" type="submit" class="!px-6">
                    Simpan Pengaturan
                </x-tombol>
            </div>
        </form>

        <!-- Riwayat Perubahan Pengaturan -->
        <x-kartu class="p-6 space-y-4">
            <h2 class="text-base font-extrabold text-ink border-b border-border pb-2">{{ __('pengaturan.riwayat.judul') }}</h2>

            @if ($riwayatPengaturan->isEmpty())
                <p class="text-sm text-muted py-4 text-center">{{ __('pengaturan.riwayat.kosong') }}</p>
            @else
                <div class="overflow-x-auto focus:outline-none focus:ring-1 focus:ring-green-700 rounded-xl" tabindex="0" role="region" aria-label="{{ __('pengaturan.riwayat.judul') }}">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-border text-muted uppercase font-bold">
                                <th class="py-2.5 px-3">{{ __('pengaturan.riwayat.waktu') }}</th>
                                <th class="py-2.5 px-3">{{ __('pengaturan.riwayat.diubah_oleh') }}</th>
                                <th class="py-2.5 px-3">{{ __('pengaturan.riwayat.kunci') }}</th>
                                <th class="py-2.5 px-3">{{ __('pengaturan.riwayat.nilai_lama') }}</th>
                                <th class="py-2.5 px-3">{{ __('pengaturan.riwayat.nilai_baru') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($riwayatPengaturan as $item)
                                <tr>
                                    <td class="py-2.5 px-3 whitespace-nowrap">{{ $item->created_at->translatedFormat('d/m/Y H:i:s') }}</td>
                                    <td class="py-2.5 px-3 whitespace-nowrap font-bold">{{ $item->admin?->nama_lengkap ?? 'Admin' }}</td>
                                    <td class="py-2.5 px-3 whitespace-nowrap font-mono text-green-800">{{ $item->kunci }}</td>
                                    <td class="py-2.5 px-3 max-w-[150px] truncate">{{ $item->nilai_lama ?? '-' }}</td>
                                    <td class="py-2.5 px-3 max-w-[150px] truncate font-bold text-ink">{{ $item->nilai_baru ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-kartu>
    </div>
</x-layouts.admin>
