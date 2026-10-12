import { test, expect } from '@playwright/test';

test.describe('Detak Jantung Heartbeat Siswa (E2E)', () => {
  test('Siswa demo masuk, minimal dua POST /detak 204, dan berhenti saat diam 31 menit lalu lanjut setelah interaksi', async ({ page }) => {
    await page.clock.install();

    let jumlahDetak = 0;
    page.on('response', (response) => {
      if (response.url().includes('/detak') && response.request().method() === 'POST' && response.status() === 204) {
        jumlahDetak++;
      }
    });

    // 1. Masuk sebagai siswa demo
    await page.goto('/masuk');
    await page.fill('input[name="login"]', 'budi_pratama');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');

    // Tunggu request /detak pertama saat beranda dimuat
    await page.waitForResponse(
      (res) => res.url().includes('/detak') && res.request().method() === 'POST' && res.status() === 204
    );

    expect(jumlahDetak).toBeGreaterThanOrEqual(1);

    // 2. Majukan waktu 50 detik (melewati interval 45 detik)
    // Tunggu response /detak kedua
    const responsePromise = page.waitForResponse(
      (res) => res.url().includes('/detak') && res.request().method() === 'POST' && res.status() === 204
    );
    await page.clock.runFor(50000);
    await responsePromise;

    expect(jumlahDetak).toBeGreaterThanOrEqual(2);

    // 3. Majukan waktu 31 menit tanpa interaksi
    await page.clock.runFor(31 * 60 * 1000);
    const detakSaatDiam = jumlahDetak;

    // Majukan lagi 2 menit dalam keadaan diam -> tidak boleh ada detak baru
    await page.clock.runFor(2 * 60 * 1000);
    expect(jumlahDetak).toBe(detakSaatDiam);

    // 4. Lakukan interaksi (klik mouse) untuk membangunkan dari status diam
    const detakBangunPromise = page.waitForResponse(
      (res) => res.url().includes('/detak') && res.request().method() === 'POST' && res.status() === 204
    );
    await page.mouse.click(150, 150);
    await page.clock.runFor(500);
    await detakBangunPromise;

    expect(jumlahDetak).toBeGreaterThan(detakSaatDiam);
  });
});
