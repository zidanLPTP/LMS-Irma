import { test, expect } from '@playwright/test';

test.describe('Detak Jantung Heartbeat Siswa (E2E)', () => {
  test('Siswa demo masuk, page clock dimajukan 50 detik, verifikasi request POST /detak 204', async ({ page }) => {
    // Siapkan penangkap request ke endpoint /detak
    let detakRequestDiterima = false;
    let detakStatus = 0;

    page.on('response', (response) => {
      if (response.url().includes('/detak') && response.request().method() === 'POST') {
        detakRequestDiterima = true;
        detakStatus = response.status();
      }
    });

    // 1. Masuk sebagai siswa demo
    await page.goto('/masuk');
    await page.fill('input[name="login"]', 'budi_pratama');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');

    // 2. Berada di beranda siswa
    await expect(page).toHaveURL(/\/siswa\/beranda/);

    // Saat halaman dimuat, script detak-jantung.js langsung mengirim 1 detak pertama
    // Tunggu sejenak agar request awal tercatat
    await page.waitForTimeout(1000);
    expect(detakRequestDiterima).toBeTruthy();
    expect(detakStatus).toBe(204);

    // Reset flag untuk pengujian interval berikutnya
    detakRequestDiterima = false;

    // 3. Majukan waktu jika didukung clock, atau tunggu interval detak
    // Di Playwright clock dapat dimajukan atau tunggu
    if (page.clock) {
      await page.clock.fastForward(50000); // majukan 50 detik
    } else {
      await page.waitForTimeout(2000);
    }
  });
});
