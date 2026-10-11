# Panduan Menjalankan Playwright E2E Test

Jalankan tes antarmuka browser otomatis (smoke test mobile 375x667):
1. Pastikan server lokal aktif: `php artisan serve --port=8000`
2. Siapkan data pengujian demo: `php artisan migrate:fresh --seed && php artisan db:seed --class=DemoSeeder`
3. Jalankan tes Playwright: `npx playwright test`
4. Hasil tangkapan layar otomatis tersimpan di folder `tests/e2e/hasil/`
5. Untuk melihat laporan interaktif Playwright: `npx playwright show-report`
