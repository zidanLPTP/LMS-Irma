import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import fs from 'fs';
import path from 'path';

test.describe('Audit Aksesibilitas WCAG (Langkah 16 Bagian B.2)', () => {
  const outputDir = path.resolve('tests/e2e/hasil/aksesibilitas');

  test.beforeAll(() => {
    if (!fs.existsSync(outputDir)) {
      fs.mkdirSync(outputDir, { recursive: true });
    }
  });

  const daftarHalaman = [
    { nama: 'Halaman Masuk', url: '/masuk', peran: 'tamu' },
    { nama: 'Halaman Pendaftaran Siswa', url: '/daftar', peran: 'tamu' },
    { nama: 'Beranda Siswa', url: '/siswa/beranda', peran: 'siswa' },
    { nama: 'Nilai Siswa', url: '/siswa/nilai', peran: 'siswa' },
    { nama: 'Profil Siswa', url: '/siswa/profil', peran: 'siswa' },
    { nama: 'Notifikasi Siswa', url: '/siswa/notifikasi', peran: 'siswa' },
    { nama: 'Dasbor Admin', url: '/admin/beranda', peran: 'admin' },
    { nama: 'Kelola Pengguna Admin', url: '/admin/pengguna', peran: 'admin' },
    { nama: 'Laporan Akses Admin', url: '/admin/laporan/akses', peran: 'admin' },
    { nama: 'Laporan Nilai Admin', url: '/admin/laporan/nilai', peran: 'admin' },
    { nama: 'Pengaturan Admin', url: '/admin/pengaturan', peran: 'admin' },
  ];

  for (const item of daftarHalaman) {
    test(`Audit Axe-core: ${item.nama} (${item.url})`, async ({ page }) => {
      if (item.peran === 'admin') {
        await page.goto('/masuk');
        await page.fill('#login', 'admin');
        await page.fill('#password', 'password123');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/beranda');
      } else if (item.peran === 'siswa') {
        await page.goto('/masuk');
        await page.fill('#login', 'ahmad_fauzi');
        await page.fill('#password', 'password123');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/siswa/beranda');
      }

      await page.goto(item.url);
      await page.waitForLoadState('networkidle');

      const hasilAxe = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
        .analyze();

      const pelanggaranKritis = hasilAxe.violations.filter(
        v => v.impact === 'critical' || v.impact === 'serious'
      );

      const ringkasanHasil = {
        halaman: item.nama,
        url: item.url,
        totalPelanggaran: hasilAxe.violations.length,
        pelanggaranKritisDanSerius: pelanggaranKritis.length,
        daftarPelanggaran: hasilAxe.violations.map(v => ({
          id: v.id,
          impact: v.impact,
          description: v.description,
          help: v.help,
          nodesCount: v.nodes.length,
          targetContoh: v.nodes[0]?.target || [],
        })),
      };

      const fileLog = path.join(outputDir, `${item.nama.toLowerCase().replace(/[^a-z0-9]/g, '_')}.json`);
      fs.writeFileSync(fileLog, JSON.stringify(ringkasanHasil, null, 2));

      console.log(`[AXE AUDIT] ${item.nama}: ${hasilAxe.violations.length} total isu (${pelanggaranKritis.length} serius/kritis)`);
    });
  }
});
