import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';

test.describe('Tinjauan Visual Tampilan LMS', () => {
  const outputDir = path.resolve('tests/e2e/hasil/tinjauan');

  test.beforeAll(() => {
    if (!fs.existsSync(outputDir)) {
      fs.mkdirSync(outputDir, { recursive: true });
    }
  });

  const viewports = [
    { name: 'hp_375x667', width: 375, height: 667 },
    { name: 'laptop_1280x800', width: 1280, height: 800 },
  ];

  for (const vp of viewports) {
    test(`Ambil tangkapan layar semua halaman - ${vp.name}`, async ({ browser }) => {
      const context = await browser.newContext({
        viewport: { width: vp.width, height: vp.height },
      });
      const page = await context.newPage();

      let urutan = 1;
      const jepret = async (nama) => {
        const no = String(urutan++).padStart(2, '0');
        const fileTarget = path.join(outputDir, `${no}_${nama}_${vp.name}.png`);
        await page.screenshot({ path: fileTarget, fullPage: true });
      };

      // ==========================================
      // A. HALAMAN ADMIN
      // ==========================================
      // 1. Masuk sebagai Admin Demo (username: admin)
      await page.goto('/masuk');
      await page.fill('#login', 'admin');
      await page.fill('#password', 'password123');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/admin/beranda');

      // Admin 1: Dasbor
      await jepret('admin_01_dasbor');

      // Admin 2: Bab dan Materi
      await page.goto('/admin/bab');
      await expect(page.locator('h1')).toBeVisible();
      await jepret('admin_02_bab_materi');

      // Admin 3: Kuis dan Soal
      await page.goto('/admin/kuis');
      await expect(page.locator('h1')).toBeVisible();
      await jepret('admin_03_kuis_soal');

      // Admin 4: Penilaian Esai
      await page.goto('/admin/penilaian-esai');
      await expect(page.locator('h1')).toBeVisible();
      await jepret('admin_04_penilaian_esai');

      // Admin 5: Kelola Pengguna
      await page.goto('/admin/pengguna');
      await expect(page.locator('h1')).toBeVisible();
      await jepret('admin_05_kelola_pengguna');

      // Admin 6: Detail Siswa (Ahmad Fauzi)
      const tautanDetailSiswa = page.locator('a[href*="/admin/pengguna/"]').first();
      if (await tautanDetailSiswa.count() > 0) {
        await tautanDetailSiswa.click();
        await page.waitForLoadState('networkidle');
        await jepret('admin_06_detail_siswa');
      }

      // Admin 7: Laporan Akses
      await page.goto('/admin/laporan/akses');
      await expect(page.locator('h1')).toBeVisible();
      await jepret('admin_07_laporan_akses');

      // Admin 8: Laporan Nilai
      await page.goto('/admin/laporan/nilai');
      await expect(page.locator('h1')).toBeVisible();
      await jepret('admin_08_laporan_nilai');

      // Admin 9: Pengaturan
      await page.goto('/admin/pengaturan');
      await expect(page.locator('h1')).toBeVisible();
      await jepret('admin_09_pengaturan');

      // Admin 10: Pengumuman
      await page.goto('/admin/pengumuman');
      await expect(page.locator('h1')).toBeVisible();
      await jepret('admin_10_pengumuman');

      // Admin 11: Impor Siswa
      await page.goto('/admin/impor-siswa');
      await expect(page.locator('h1')).toBeVisible();
      await jepret('admin_11_impor_siswa');

      // Admin 12: Impor Soal Kuis (ambil kuis pertama)
      const kuisPertama = page.locator('a[href*="/impor-soal"]').first();
      await page.goto('/admin/kuis');
      const tautanSoal = page.locator('a[href*="/kuis/"][href*="/soal"]').first();
      if (await tautanSoal.count() > 0) {
        const hrefSoal = await tautanSoal.getAttribute('href');
        const kuisIdMatch = hrefSoal.match(/\/kuis\/(\d+)\/soal/);
        if (kuisIdMatch) {
          await page.goto(`/admin/kuis/${kuisIdMatch[1]}/impor-soal`);
          await expect(page.locator('h1')).toBeVisible();
          await jepret('admin_12_impor_soal');
        }
      }

      // Admin 13: Pratinjau Bab (Bab 1)
      await page.goto('/admin/pratinjau/bab/1');
      await jepret('admin_13_pratinjau_bab');

      // Admin 14: Pratinjau Kuis (Kuis 1)
      await page.goto('/admin/pratinjau/kuis/1');
      await jepret('admin_14_pratinjau_kuis');

      // Logout Admin
      await page.locator('form[action*="/keluar"] button').click();
      await page.waitForURL('**/masuk');

      // Halaman Pendaftaran Siswa (Kontrol Pendaftaran)
      await page.goto('/daftar');
      await expect(page.locator('h1, h2')).toBeVisible();
      await jepret('umum_daftar_siswa');

      // ==========================================
      // B. HALAMAN SISWA
      // ==========================================
      // Masuk sebagai Siswa Demo (ahmad_fauzi)
      await page.goto('/masuk');
      await page.fill('#login', 'ahmad_fauzi');
      await page.fill('#password', 'password123');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/siswa/beranda');

      // Siswa 1: Beranda
      await jepret('siswa_01_beranda');

      // Siswa 2: Halaman Bab & Materi
      const tombolBukaBab = page.locator('a[href*="/siswa/bab/"]').first();
      if (await tombolBukaBab.count() > 0) {
        await tombolBukaBab.click();
        await page.waitForLoadState('networkidle');
        await jepret('siswa_02_halaman_bab');

        // Buka materi pertama
        const tombolMateri = page.locator('a[href*="/materi/"]').first();
        if (await tombolMateri.count() > 0) {
          await tombolMateri.click();
          await page.waitForLoadState('networkidle');
          await jepret('siswa_03_halaman_materi');
        }
      }

      // Siswa 4: Pencarian Siswa
      await page.goto('/siswa/cari?q=sel');
      await expect(page.locator('h1')).toBeVisible();
      await jepret('siswa_04_pencarian');

      // Siswa 5: Pengantar Kuis
      await page.goto('/siswa/beranda');
      const tombolKuis = page.locator('a[href*="/siswa/kuis/"]').first();
      if (await tombolKuis.count() > 0) {
        await tombolKuis.click();
        await page.waitForLoadState('networkidle');
        await jepret('siswa_05_pengantar_kuis');
      }

      // Siswa 6: Nilai Saya
      await page.goto('/siswa/nilai');
      await expect(page.locator('h1')).toBeVisible();
      await jepret('siswa_06_nilai_saya');

      // Siswa 7: Profil
      await page.goto('/siswa/profil');
      await expect(page.locator('h1')).toBeVisible();
      await jepret('siswa_07_profil');

      // Siswa 8: Notifikasi
      await page.goto('/siswa/notifikasi');
      await expect(page.locator('h1')).toBeVisible();
      await jepret('siswa_08_notifikasi');

      // Siswa 9: Pengumuman Siswa
      await page.goto('/siswa/pengumuman');
      await expect(page.locator('h1')).toBeVisible();
      await jepret('siswa_09_pengumuman');

      await context.close();
    });
  }
});
