import { test, expect } from '@playwright/test';
import path from 'path';

test('Jepret header siswa 375px', async ({ browser }) => {
  const context = await browser.newContext({
    viewport: { width: 375, height: 667 },
  });
  const page = await context.newPage();

  // Masuk sebagai siswa demo
  await page.goto('http://127.0.0.1:8000/masuk');
  await page.fill('input[name="login"]', 'budi_pratama');
  await page.fill('input[name="password"]', 'password123');
  await page.click('button[type="submit"]');

  await page.waitForURL('**/siswa/beranda');
  await expect(page.locator('header')).toBeVisible();

  // Jepret kondisi header tertutup
  await page.screenshot({
    path: path.resolve('tests/e2e/hasil/tinjauan/header_siswa_375px_ciut.png'),
    fullPage: false,
  });

  // Buka tombol cari di mobile
  const tombolCari = page.locator('button[aria-label="Buka pencarian"]');
  if (await tombolCari.isVisible()) {
    await tombolCari.click();
    await page.waitForTimeout(300);
    await page.screenshot({
      path: path.resolve('tests/e2e/hasil/tinjauan/header_siswa_375px_cari_terbuka.png'),
      fullPage: false,
    });
  }
});
