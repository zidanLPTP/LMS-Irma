import { test, expect } from '@playwright/test';

test.describe('Pemindaian Pelanggaran CSP (Langkah 15 Bagian E)', () => {
  const resolusiLayar = [
    { nama: 'HP (375x667)', width: 375, height: 667 },
    { nama: 'Laptop (1280x800)', width: 1280, height: 800 },
  ];

  for (const resolusi of resolusiLayar) {
    test(`Scan CSP Admin & Siswa pada resolusi ${resolusi.nama}`, async ({ page }) => {
      await page.setViewportSize({ width: resolusi.width, height: resolusi.height });

      const daftarPelanggaran = [];
      page.on('console', (msg) => {
        const text = msg.text();
        if (text.toLowerCase().includes('content security policy') || text.toLowerCase().includes('violates the following content security policy')) {
          daftarPelanggaran.push(text);
        }
      });

      // 1. Halaman Masuk
      await page.goto('/masuk');
      await page.waitForLoadState('networkidle');

      // 2. Masuk sebagai Admin Demo
      await page.fill('input[name="login"]', 'irma_admin');
      await page.fill('input[name="password"]', 'password123');
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');

      // Halaman Dasbor Admin
      await page.goto('/admin/beranda');
      await page.waitForLoadState('networkidle');

      // Halaman Laporan Nilai Admin
      await page.goto('/admin/laporan/nilai');
      await page.waitForLoadState('networkidle');

      // Logout Admin
      await page.goto('/masuk');

      // 3. Masuk sebagai Siswa Demo
      await page.fill('input[name="login"]', 'budi_pratama');
      await page.fill('input[name="password"]', 'password123');
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');

      // Halaman Beranda Siswa
      await page.goto('/siswa/beranda');
      await page.waitForLoadState('networkidle');

      // Halaman Nilai Saya Siswa
      await page.goto('/siswa/nilai-saya');
      await page.waitForLoadState('networkidle');

      // Halaman Profil Siswa
      await page.goto('/siswa/profil');
      await page.waitForLoadState('networkidle');

      console.log(`[CSP SCAN ${resolusi.nama}] Total pelanggaran CSP tercatat: ${daftarPelanggaran.length}`);
      if (daftarPelanggaran.length > 0) {
        console.log(`Rincian pelanggaran (${resolusi.nama}):\n`, daftarPelanggaran.join('\n'));
      }

      // Pastikan tidak ada pelanggaran CSP yang memblokir fungsionalitas
      expect(daftarPelanggaran.length).toBe(0);
    });
  }
});
