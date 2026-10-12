<x-layouts.siswa judul="Ganti Kata Sandi">
    <div class="max-w-md mx-auto py-8">
        <div class="bg-white rounded-2xl border border-border p-6 shadow-sm space-y-5">
            <div class="text-center space-y-1">
                <div class="w-12 h-12 bg-green-100 text-green-700 rounded-full flex items-center justify-center mx-auto mb-2">
                    <x-ikon nama="lock" ukuran="24" />
                </div>
                <h1 class="text-xl font-black text-ink">Ganti Kata Sandi</h1>
                @if ($wajib)
                    <p class="text-xs text-amber-700 font-bold bg-amber-50 p-2 rounded-xl border border-amber-200">
                        Admin telah mereset kata sandi Anda. Anda wajib membuat kata sandi baru sebelum dapat mengakses materi belajar.
                    </p>
                @else
                    <p class="text-xs text-muted">Perbarui kata sandi akun LMS Anda secara berkala demi keamanan.</p>
                @endif
            </div>

            <form method="POST" action="{{ route('siswa.password.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="password_lama" class="block text-xs font-bold text-muted uppercase tracking-wider mb-1">Kata Sandi Saat Ini</label>
                    <input type="password" id="password_lama" name="password_lama" required autocomplete="current-password" class="w-full px-3.5 py-2.5 text-sm bg-bg border border-border rounded-xl focus:outline-none focus:ring-2 focus:ring-green-700">
                    @error('password_lama')
                        <p class="text-xs text-red-600 mt-1 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-muted uppercase tracking-wider mb-1">Kata Sandi Baru</label>
                    <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" class="w-full px-3.5 py-2.5 text-sm bg-bg border border-border rounded-xl focus:outline-none focus:ring-2 focus:ring-green-700">
                    <p class="text-[11px] text-muted mt-1">Minimal 8 karakter.</p>
                    @error('password')
                        <p class="text-xs text-red-600 mt-1 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-muted uppercase tracking-wider mb-1">Ulangi Kata Sandi Baru</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password" class="w-full px-3.5 py-2.5 text-sm bg-bg border border-border rounded-xl focus:outline-none focus:ring-2 focus:ring-green-700">
                </div>

                <div class="pt-3">
                    <button type="submit" class="w-full py-3 bg-green-700 text-white rounded-xl text-sm font-bold hover:bg-green-800 shadow-sm transition-colors">
                        Simpan Kata Sandi Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.siswa>
