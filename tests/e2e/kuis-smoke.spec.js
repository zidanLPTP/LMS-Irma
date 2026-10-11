import { test, expect } from '@playwright/test';

test.describe('Smoke Test Engine Kuis Siswa (Mobile 375x667)', () => {
  test('Siswa demo masuk, mengerjakan pretest: jawab PG 1, biarkan timeout PG 2, isi esai, serahkan, lihat hasil', async ({ page }) => {
    // 1. Masuk sebagai siswa demo
    await page.goto('/masuk');
    await expect(page.locator('h1')).toContainText('Masuk ke LMS');

    await page.fill('input[name="login"]', 'ahmad_fauzi');
    await page.fill('input[name="password"]', 'password123');
    await page.screenshot({ path: 'tests/e2e/hasil/01-login-form.png', fullPage: true });

    await page.click('button[type="submit"]');

    // 2. Beranda siswa
    await expect(page).toHaveURL(/\/siswa\/beranda/);
    await expect(page.locator('body')).toContainText('Ahmad Fauzi');
    await page.screenshot({ path: 'tests/e2e/hasil/02-beranda.png', fullPage: true });

    // 3. Masuk ke halaman pengantar Pretest
    // Klik tombol / tautan Mulai Pretest
    const tombolPretest = page.locator('a[href*="/siswa/kuis/"]').first();
    await tombolPretest.click();

    await expect(page.locator('h1')).toContainText('Pretest Diagnosis Awal');
    await page.screenshot({ path: 'tests/e2e/hasil/03-pengantar-pretest.png', fullPage: true });

    // 4. Mulai Percobaan Pretest
    const tombolAksi = page.locator('button[type="submit"]:has-text("Kerjakan"), a:has-text("Lanjutkan")');
    await tombolAksi.first().click();

    // Berada di layar soal 1
    await expect(page).toHaveURL(/\/siswa\/percobaan\/.*\/soal/);
    await page.screenshot({ path: 'tests/e2e/hasil/04-soal-1-pg.png', fullPage: true });

    // Cek apakah soal 1 adalah PG atau teks tertentu
    // Soal 1: "Unit struktural dan fungsional..."
    // Jawab soal 1: Pilih opsi "Sel"
    const pilihanSel = page.locator('label:has-text("Sel")');
    if (await pilihanSel.isVisible()) {
      await pilihanSel.click();
    } else {
      // Jika teracak, klik pilihan pertama
      await page.locator('input[type="radio"]').first().check();
    }

    await page.screenshot({ path: 'tests/e2e/hasil/05-soal-1-terpilih.png', fullPage: true });
    // Kirim jawaban soal 1
    await page.click('button[type="submit"]:has-text("Simpan")');

    // 5. Layar soal 2 (PG kedua)
    await expect(page).toHaveURL(/\/siswa\/percobaan\/.*\/soal/);
    await page.screenshot({ path: 'tests/e2e/hasil/06-soal-2-sebelum-timeout.png', fullPage: true });

    // Biarkan tanpa memilih jawaban (timeout / submit kosong)
    await Promise.all([
      page.waitForNavigation(),
      page.click('button[type="submit"]:has-text("Simpan")'),
    ]);

    // 6. Masuk ke layar soal 3 (Esai)
    await expect(page).toHaveURL(/\/siswa\/percobaan\/.*\/soal/);
    await expect(page.locator('textarea[name="jawaban_teks"]')).toBeVisible();
    await page.screenshot({ path: 'tests/e2e/hasil/07-soal-3-esai.png', fullPage: true });

    // Isi esai
    await page.fill('textarea[name="jawaban_teks"]', 'Sel tumbuhan memiliki dinding sel dan kloroplas untuk fotosintesis, sedangkan sel hewan tidak memilikinya tetapi memiliki sentriol.');
    await page.screenshot({ path: 'tests/e2e/hasil/08-soal-3-esai-diisi.png', fullPage: true });

    // Simpan jawaban esai (menuju penyerahan kuis)
    await page.click('button[type="submit"]:has-text("Simpan")');

    // 7. Halaman Hasil
    await expect(page).toHaveURL(/\/siswa\/percobaan\/.*\/hasil/);
    await expect(page.locator('body')).toContainText('Hasil Pengerjaan Kuis');
    await expect(page.locator('body')).toContainText('menunggu penilaian esai');
    await page.screenshot({ path: 'tests/e2e/hasil/09-halaman-hasil.png', fullPage: true });
  });
});
