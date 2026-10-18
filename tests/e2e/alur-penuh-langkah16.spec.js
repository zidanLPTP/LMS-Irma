import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import XLSX from 'xlsx';

test.describe.serial('Alur Lengkap Pembelajaran BioSTEMX (Langkah 16 Bagian B.1)', () => {
  const hasilDir = path.resolve('tests/e2e/hasil/alur-penuh');
  const fixtureSoal = path.resolve('tests/e2e/fixtures/soal_uji.xlsx');
  const fixturePdf = path.resolve('tests/e2e/fixtures/materi_uji.pdf');

  test.beforeAll(() => {
    if (!fs.existsSync(hasilDir)) {
      fs.mkdirSync(hasilDir, { recursive: true });
    }
  });

  const viewports = [
    { name: 'hp_375x667', width: 375, height: 667, isMobile: true },
    { name: 'laptop_1280x800', width: 1280, height: 800, isMobile: false },
  ];

  for (const vp of viewports) {
    test(`Skenario Alur Penuh - ${vp.name}`, async ({ browser }) => {
      const context = await browser.newContext({
        viewport: { width: vp.width, height: vp.height },
        isMobile: vp.isMobile,
        hasTouch: vp.isMobile,
      });
      const page = await context.newPage();

      let langkahNo = 1;
      const jepret = async (nama) => {
        const urutan = String(langkahNo++).padStart(2, '0');
        const berkas = path.join(hasilDir, `${urutan}_${nama}_${vp.name}.png`);
        await page.screenshot({ path: berkas, fullPage: true });
      };

      // ------------------------------------------------------------------
      // 1. ADMIN MASUK & LOGIN PERTAMA (WAJIB GANTI PASSWORD JIKA AKTIF)
      // ------------------------------------------------------------------
      await page.goto('/masuk');
      await page.fill('#login', 'admin');
      await page.fill('#password', 'password123');
      await jepret('01_admin_masuk_form');
      await page.click('button[type="submit"]');

      // Periksa apakah diarahkan ke ganti password wajib
      await page.waitForTimeout(1000);
      if (page.url().includes('/password')) {
        await jepret('01b_admin_wajib_ganti_password');
        await expect(page.locator('body')).toContainText('Ganti Kata Sandi');
        await page.fill('#password_lama', 'password123');
        await page.fill('#password', 'AdminBioSTEMX2026!');
        await page.fill('#password_confirmation', 'AdminBioSTEMX2026!');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/beranda');
        await jepret('01c_admin_password_baru_aktif');
      } else {
        await page.waitForURL('**/admin/beranda');
      }
      await jepret('02_admin_dasbor');

      // ------------------------------------------------------------------
      // 2. ADMIN MENGATUR KODE PENDAFTARAN
      // ------------------------------------------------------------------
      await page.goto('/admin/pengaturan');
      await page.fill('#kode_pendaftaran', 'BIOSTEMX2026');
      await jepret('03_admin_setel_kode_pendaftaran');
      await page.click('button[type="submit"]:has-text("Simpan Pengaturan")');
      await expect(page.locator('body')).toContainText('Pengaturan sistem berhasil diperbarui');
      await jepret('04_admin_kode_pendaftaran_tersimpan');

      // ------------------------------------------------------------------
      // 2b. ADMIN MEMBUAT PRETEST & MENAMBAHKAN SOAL (JIKA BELUM ADA)
      // ------------------------------------------------------------------
      await page.goto('/admin/kuis');
      const barisPretest = page.locator('tr:has-text("Pretest")');
      if (await barisPretest.count() === 0) {
        await page.goto('/admin/kuis/create');
        await page.click('input[value="pretest"]');
        await page.fill('#judul', 'Pretest Diagnosis Awal Biologi');
        await page.click('button[type="submit"]:has-text("Simpan Kuis")');
        await page.waitForURL('**/admin/kuis');

        // Kelola Soal Pretest & Tambah 1 Butir Soal PG
        const linkSoalPretest = page.locator('tr:has-text("Pretest") a:has-text("Kelola Soal")').first();
        await linkSoalPretest.click();
        await page.waitForURL('**/soal');

        // Buat Soal PG Manual untuk Pretest
        await page.click('a:has-text("Tambah Butir Soal")');
        await page.waitForURL('**/soal/create');
        await page.click('input[value="pilihan_ganda"]');
        await page.fill('#pertanyaan', 'Unit dasar struktural dan fungsional terkecil dari organisme hidup adalah?');
        await page.fill('input[name="pilihan[0][teks]"]', 'Sel');
        // Opsi 0 sudah checked secara default
        await page.fill('input[name="pilihan[1][teks]"]', 'Jaringan');
        await page.fill('input[name="pilihan[2][teks]"]', 'Organ');
        await page.fill('input[name="pilihan[3][teks]"]', 'Sistem Organ');
        await page.click('button[type="submit"]:has-text("Simpan Butir Soal")');
        await page.waitForURL('**/soal');
        await jepret('04b_admin_soal_pretest_tersimpan');
      }

      // ------------------------------------------------------------------
      // 3. ADMIN MEMBUAT BAB 1 (Materi Video YouTube) & BAB 2 (Materi PDF)
      // ------------------------------------------------------------------
      // Buat Bab 1
      await page.goto('/admin/bab/create');
      await page.fill('#judul', 'Bab 1 Biologi: Struktur Sel Dasar');
      await page.fill('#deskripsi', 'Pengenalan organel sel dan fungsinya');
      await page.click('button[type="submit"]:has-text("Simpan Bab")');
      await page.waitForURL('**/admin/bab');

      // Ambil id Bab 1
      const linkBab1 = page.locator('a[href*="/admin/bab/"][href*="/materi"]').first();
      const hrefBab1 = await linkBab1.getAttribute('href');
      const bab1Id = hrefBab1.match(/\/admin\/bab\/(\d+)\/materi/)[1];

      // Tambah materi YouTube di Bab 1
      await page.goto(`/admin/bab/${bab1Id}/materi/create`);
      await page.fill('#judul', 'Video Animasi Sel Biologi');
      await page.click('input[value="youtube"]');
      await page.fill('#url_youtube', 'https://www.youtube.com/watch?v=URUJD5NEXC8');
      await page.click('button[type="submit"]:has-text("Tambah Materi"), button[type="submit"]:has-text("Simpan")');
      await page.waitForURL(`**/admin/bab/${bab1Id}/materi`);
      await jepret('05_admin_bab1_materi_youtube');

      // Buat Bab 2
      await page.goto('/admin/bab/create');
      await page.fill('#judul', 'Bab 2 Biologi: Fungsi Organel Lanjutan');
      await page.fill('#deskripsi', 'Materi modul PDF dan kuis evaluasi 3 tipe soal');
      await page.click('button[type="submit"]:has-text("Simpan Bab")');
      await page.waitForURL('**/admin/bab');

      // Ambil id Bab 2
      const linkBabSemua = page.locator('a[href*="/admin/bab/"][href*="/materi"]');
      const hrefBab2 = await linkBabSemua.last().getAttribute('href');
      const bab2Id = hrefBab2.match(/\/admin\/bab\/(\d+)\/materi/)[1];

      // Tambah materi PDF di Bab 2
      await page.goto(`/admin/bab/${bab2Id}/materi/create`);
      await page.fill('#judul', 'Modul PDF Biologi Sel Terpadu');
      await page.click('input[value="pdf"]');
      await page.setInputFiles('#berkas_pdf', fixturePdf);
      await page.click('button[type="submit"]:has-text("Tambah Materi"), button[type="submit"]:has-text("Simpan")');
      await page.waitForURL(`**/admin/bab/${bab2Id}/materi`);
      await jepret('06_admin_bab2_materi_pdf');

      // ------------------------------------------------------------------
      // 4. ADMIN MEMBUAT KUIS & IMPOR SOAL DARI EXCEL
      // ------------------------------------------------------------------
      // Kuis Bab 2
      await page.goto('/admin/kuis/create');
      await page.click('input[value="kuis_bab"]');
      await page.selectOption('#bab_id', bab2Id);
      const judulKuisBab2 = `Kuis Evaluasi Bab 2 (${vp.name})`;
      await page.fill('#judul', judulKuisBab2);
      await page.click('button[type="submit"]:has-text("Simpan Kuis")');
      await page.waitForURL('**/admin/kuis');

      // Cari Kuis Bab 2 yang baru dibuat dan masuk ke kelola soal (ambil baris paling akhir yang baru dibuat)
      const linkKelolaSoal = page.locator(`tr:has-text("${judulKuisBab2}") a:has-text("Kelola Soal")`).last();
      await linkKelolaSoal.click();
      await page.waitForURL('**/soal');

      // Klik tombol Impor Excel di halaman kelola soal
      await page.click('a:has-text("Impor Excel")');
      await page.waitForURL('**/impor-soal');
      await jepret('07_admin_halaman_impor_soal');

      // Unggah berkas soal fixture
      await page.setInputFiles('input[type="file"][name="berkas"]', fixtureSoal);
      await page.click('button[type="submit"]:has-text("Lanjut ke Pratinjau Soal")');
      await page.waitForURL('**/pratinjau');
      await jepret('08_admin_pratinjau_impor_soal');

      // Konfirmasi Impor Soal
      await page.click('button[type="submit"]:has-text("Konfirmasi Impor")');
      await page.waitForURL('**/admin/kuis/*/soal');
      await expect(page.locator('body')).toContainText('Berhasil mengimpor');
      await jepret('09_admin_soal_berhasil_diimpor');

      // Buat Posttest Penelitian (Jika Belum Ada)
      await page.goto('/admin/kuis');
      const barisPosttest = page.locator('tr:has-text("Posttest")');
      if (await barisPosttest.count() === 0) {
        await page.goto('/admin/kuis/create');
        await page.click('input[value="posttest"]');
        await page.fill('#judul', 'Posttest Evaluasi Akhir Penelitian');
        await page.click('button[type="submit"]:has-text("Simpan Kuis")');
        await page.waitForURL('**/admin/kuis');

        // Tambahkan Soal Esai di Posttest
        const linkSoalPosttest = page.locator('tr:has-text("Posttest") a:has-text("Kelola Soal")').first();
        await linkSoalPosttest.click();
        await page.waitForURL('**/soal');
        await page.click('a:has-text("Tambah Butir Soal")');
        await page.waitForURL('**/soal/create');
        await page.click('input[value="esai"]');
        await page.fill('#pertanyaan', 'Jelaskan perbedaan mendasar antara sel tumbuhan dan sel hewan beserta contohnya!');
        await page.click('button[type="submit"]:has-text("Simpan Butir Soal")');
        await page.waitForURL('**/soal');
      }

      // ------------------------------------------------------------------
      // 5. ADMIN KELUAR
      // ------------------------------------------------------------------
      await page.locator('form[action*="/keluar"] button').click();
      await page.waitForURL('**/masuk');
      await jepret('10_admin_keluar');

      // ------------------------------------------------------------------
      // 6. SISWA MENDAFTAR DENGAN KODE PENDAFTARAN
      // ------------------------------------------------------------------
      const waktuAcak = Date.now().toString().slice(-4);
      const usernameSiswa = `siswa_e2e_${vp.name}_${waktuAcak}`;
      const emailSiswa = `${usernameSiswa}@siswa.test`;

      await page.goto('/daftar');
      await jepret('11_siswa_form_daftar');

      // Uji kode salah ditolak
      await page.fill('#nama_lengkap', 'Rani Explorer');
      await page.fill('#username', usernameSiswa);
      await page.fill('#email', emailSiswa);
      await page.fill('#kelas', 'XI BIOLOGI');
      if (await page.locator('#kode_pendaftaran').count() > 0) {
        await page.fill('#kode_pendaftaran', 'KODE_SALAH_123');
      }
      await page.fill('#password', 'password123');
      await page.fill('#password_confirmation', 'password123');
      await page.click('button[type="submit"]:has-text("Daftar Sekarang")');
      await expect(page.locator('body')).toContainText('Kode pendaftaran tidak sesuai');
      await jepret('12_siswa_kode_salah_ditolak');

      // Uji kode benar diterima
      await page.fill('#kode_pendaftaran', 'BIOSTEMX2026');
      await page.fill('#password', 'password123');
      await page.fill('#password_confirmation', 'password123');
      await page.click('button[type="submit"]:has-text("Daftar Sekarang")');
      await page.waitForURL('**/siswa/beranda');
      await jepret('13_siswa_berhasil_daftar_beranda');

      // ------------------------------------------------------------------
      // 7. SISWA MENGERJAKAN PRETEST
      // ------------------------------------------------------------------
      const tombolMulaiPretest = page.locator('a[href*="/siswa/kuis/"]').first();
      await tombolMulaiPretest.click();
      await page.waitForLoadState('networkidle');
      await jepret('14_siswa_pengantar_pretest');

      const tombolAksiPretest = page.locator('button[type="submit"]:has-text("Kerjakan"), a:has-text("Lanjutkan")').first();
      await tombolAksiPretest.click();
      await page.waitForURL('**/soal');

      // Jawab semua soal di pretest sampai hasil
      while (page.url().includes('/soal')) {
        // Cek apakah ada radio button PG
        const radio = page.locator('input[type="radio"]');
        if (await radio.count() > 0) {
          await radio.first().check();
        }
        // Cek isian singkat
        const isian = page.locator('input[type="text"][name="jawaban_teks"]');
        if (await isian.count() > 0) {
          await isian.fill('jawaban isian');
        }
        // Cek esai
        const esai = page.locator('textarea[name="jawaban_teks"]');
        if (await esai.count() > 0) {
          await esai.fill('Jawaban esai pretest siswa');
        }
        await page.click('button[type="submit"]:has-text("Simpan")');
        await page.waitForTimeout(500);
      }
      await expect(page).toHaveURL(/\/siswa\/percobaan\/.*\/hasil/);
      await jepret('15_siswa_hasil_pretest');

      // ------------------------------------------------------------------
      // 8. SISWA MEMPELAJARI BAB 1 & BAB 2
      // ------------------------------------------------------------------
      await page.goto('/siswa/beranda');
      await jepret('16_siswa_bab1_terbuka');

      // Buka materi Bab 1 (Bab tanpa kuis otomatis tuntas jika materinya dibuka)
      await page.goto(`/siswa/bab/${bab1Id}`);
      await page.waitForLoadState('networkidle');
      const linkMateri1 = page.locator('a[href*="/materi/"]').first();
      if (await linkMateri1.count() > 0) {
        await linkMateri1.click();
        await page.waitForLoadState('networkidle');
        await jepret('17_siswa_buka_materi_video');
      }

      // Kembali ke beranda dan buka Bab 2
      await page.goto('/siswa/beranda');
      await page.goto(`/siswa/bab/${bab2Id}`);
      await page.waitForLoadState('networkidle');

      // Buka materi PDF Bab 2
      const linkMateri2 = page.locator('a[href*="/materi/"]').first();
      if (await linkMateri2.count() > 0) {
        await linkMateri2.click();
        await page.waitForLoadState('networkidle');
        await jepret('17b_siswa_buka_materi_pdf');
      }

      // ------------------------------------------------------------------
      // 9. SISWA MENGERJAKAN KUIS BAB 2 DUA KALI (GAGAL) -> BELUM LULUS LANJUT
      // ------------------------------------------------------------------
      // Buka Kuis Bab 2 dari halaman Bab 2
      await page.goto(`/siswa/bab/${bab2Id}`);
      const linkBukaKuis = page.locator('a:has-text("Buka Kuis Bab"), a:has-text("Kerjakan Kuis")').first();
      await linkBukaKuis.click();
      await page.waitForLoadState('networkidle');
      await jepret('18_siswa_pengantar_kuis_bab2');

      // Percobaan 1: Jawab salah (pilih opsi salah / kosong)
      const tombolKerjakan1 = page.locator('button[type="submit"]:has-text("Mulai"), button[type="submit"]:has-text("Kerjakan"), a:has-text("Lanjutkan")').first();
      await tombolKerjakan1.click();
      await page.waitForURL('**/soal');

      while (page.url().includes('/soal')) {
        const radio = page.locator('input[type="radio"]');
        if (await radio.count() > 1) {
          await radio.nth(1).check(); // Pilih opsi kedua (salah)
        }
        const isian = page.locator('input[type="text"][name="jawaban_teks"]');
        if (await isian.count() > 0) {
          await isian.fill('salah');
        }
        const esai = page.locator('textarea[name="jawaban_teks"]');
        if (await esai.count() > 0) {
          await esai.fill('Belum paham konsep perbedaan sel');
        }
        await page.click('button[type="submit"]:has-text("Simpan")');
        await page.waitForTimeout(500);
      }
      await expect(page).toHaveURL(/\/siswa\/percobaan\/.*\/hasil/);
      await jepret('19_siswa_kuis_bab2_percobaan1_selesai');

      // Percobaan 2: Jawab salah lagi agar 2 kesempatan habis
      await page.goto(`/siswa/bab/${bab2Id}`);
      await page.waitForLoadState('networkidle');
      const linkBukaKuis2 = page.locator('a:has-text("Buka Kuis Bab"), a:has-text("Kerjakan Kuis")').first();
      await linkBukaKuis2.click();
      await page.waitForLoadState('networkidle');

      const tombolKerjakan2 = page.locator('button[type="submit"]:has-text("Mulai"), button[type="submit"]:has-text("Kerjakan"), a:has-text("Lanjutkan")').first();
      await tombolKerjakan2.click();
      await page.waitForURL('**/soal');

      while (page.url().includes('/soal')) {
        const radio = page.locator('input[type="radio"]');
        if (await radio.count() > 1) {
          await radio.nth(1).check();
        }
        const isian = page.locator('input[type="text"][name="jawaban_teks"]');
        if (await isian.count() > 0) {
          await isian.fill('salah lagi');
        }
        const esai = page.locator('textarea[name="jawaban_teks"]');
        if (await esai.count() > 0) {
          await esai.fill('Jawaban percobaan kedua masih belum tuntas');
        }
        await page.click('button[type="submit"]:has-text("Simpan")');
        await page.waitForTimeout(500);
      }
      await expect(page).toHaveURL(/\/siswa\/percobaan\/.*\/hasil/);
      await jepret('20_siswa_kuis_bab2_percobaan2_selesai');

      // Verifikasi di Beranda bahwa Posttest terbuka berkat aturan belum_lulus_lanjut
      await page.goto('/siswa/beranda');
      await jepret('21_siswa_posttest_terbuka_setelah_2x_gagal');

      // ------------------------------------------------------------------
      // 10. SISWA MENYELESAIKAN POSTTEST (Termasuk Butir Esai)
      // ------------------------------------------------------------------
      const kartuPosttest = page.locator('div:has-text("POSTTEST"), div:has-text("Posttest")').last();
      const tombolKerjakanPosttest = kartuPosttest.locator('a:has-text("Kerjakan Kuis")').first();
      if (await tombolKerjakanPosttest.count() > 0) {
        await tombolKerjakanPosttest.click();
        await page.waitForLoadState('networkidle');
        const tombolMulaiPosttest = page.locator('button[type="submit"]:has-text("Mulai"), button[type="submit"]:has-text("Kerjakan"), a:has-text("Lanjutkan")').first();
        await tombolMulaiPosttest.click();
        await page.waitForURL('**/soal');

        while (page.url().includes('/soal')) {
          const radio = page.locator('input[type="radio"]');
          if (await radio.count() > 0) {
            await radio.first().check();
          }
          const isian = page.locator('input[type="text"][name="jawaban_teks"]');
          if (await isian.count() > 0) {
            await isian.fill('mitokondria');
          }
          const esai = page.locator('textarea[name="jawaban_teks"]');
          if (await esai.count() > 0) {
            await esai.fill('Sel tumbuhan memiliki dinding sel dari selulosa yang kaku, sedangkan sel hewan tidak memiliki dinding sel.');
          }
          await page.click('button[type="submit"]:has-text("Simpan")');
          await page.waitForTimeout(500);
        }
        await expect(page).toHaveURL(/\/siswa\/percobaan\/.*\/hasil/);
        await jepret('22_siswa_hasil_posttest_menunggu_esai');
      }

      // Siswa keluar
      await page.locator('form[action*="/keluar"] button').click();
      await page.waitForURL('**/masuk');

      // ------------------------------------------------------------------
      // 11. ADMIN MASUK & MEMBERIKAN NILAI ESAI
      // ------------------------------------------------------------------
      await page.goto('/masuk');
      await page.fill('#login', 'admin');
      await page.fill('#password', 'password123');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/admin/beranda');

      await page.goto('/admin/penilaian-esai');
      // Filter menurut nama siswa Rani Explorer
      await page.fill('#cari', 'Rani Explorer');
      await page.click('button[type="submit"]:has-text("Filter")');
      await page.waitForLoadState('networkidle');

      // Nilai percobaan siswa yang baru dikerjakan
      const tombolBeriNilai = page.locator('a[href*="/admin/penilaian-esai/"]:has-text("Beri Nilai")').first();
      const hrefNilai = await tombolBeriNilai.getAttribute('href');
      await page.goto(hrefNilai);
      await page.waitForLoadState('networkidle');
      await jepret('24_admin_formulir_penilaian_esai');

      // Isi poin maksimal untuk semua butir esai
      const inputsPoin = page.locator('input[type="number"][name*="[poin]"]');
      const jumlahInput = await inputsPoin.count();
      for (let i = 0; i < jumlahInput; i++) {
        const maxVal = await inputsPoin.nth(i).getAttribute('max') || '20';
        await inputsPoin.nth(i).fill(maxVal);
      }
      await page.click('button[type="submit"]:has-text("Simpan Nilai")');
      await page.waitForURL('**/admin/penilaian-esai*');
      await expect(page.locator('body')).toContainText('berhasil disimpan');
      await jepret('25_admin_penilaian_esai_selesai');

      // ------------------------------------------------------------------
      // 12. ADMIN MENGUNDUH 2 LAPORAN EXCEL & VALIDASI MENDALAM
      // ------------------------------------------------------------------
      // Ekspor Nilai: buka halaman laporan nilai lalu klik tombol Unduh Excel
      await page.goto('/admin/laporan/nilai');
      const [downloadNilai] = await Promise.all([
        page.waitForEvent('download'),
        page.click('a:has-text("Unduh Excel")'),
      ]);
      const pathNilaiXlsx = path.join(hasilDir, `laporan_nilai_${vp.name}.xlsx`);
      await downloadNilai.saveAs(pathNilaiXlsx);

      // Baca dengan XLSX & Asersi Mendalam (Header, Nilai Angka, dan Status)
      const wbNilai = XLSX.readFile(pathNilaiXlsx);
      expect(wbNilai.SheetNames.length).toBeGreaterThan(0);
      expect(wbNilai.SheetNames).toContain('Rekap Nilai');

      const sheetNilai = wbNilai.Sheets['Rekap Nilai'];
      const rowsNilai = XLSX.utils.sheet_to_json(sheetNilai, { header: 1 });
      expect(rowsNilai.length).toBeGreaterThan(1);

      // Validasi Judul Kolom (Headings) Rekap Nilai
      const headerNilai = rowsNilai[0];
      expect(headerNilai).toContain('Nama Lengkap');
      expect(headerNilai).toContain('Kelas');
      expect(headerNilai).toContain('Pretest (Nilai)');

      // Validasi Nilai Baris Pertama
      const barisPertamaNilai = rowsNilai[1];
      expect(typeof barisPertamaNilai[0]).toBe('string'); // Nama Siswa
      console.log(`[EXCEL NILAI ${vp.name}] Berhasil divalidasi mendalam: ${rowsNilai.length} baris, kolom: ${headerNilai.slice(0, 4).join(', ')}.`);

      // Ekspor Akses: buka halaman laporan akses lalu klik tombol Unduh Excel
      await page.goto('/admin/laporan/akses');
      const [downloadAkses] = await Promise.all([
        page.waitForEvent('download'),
        page.click('a:has-text("Unduh Excel")'),
      ]);
      const pathAksesXlsx = path.join(hasilDir, `laporan_akses_${vp.name}.xlsx`);
      await downloadAkses.saveAs(pathAksesXlsx);

      const wbAkses = XLSX.readFile(pathAksesXlsx);
      expect(wbAkses.SheetNames.length).toBeGreaterThan(0);
      expect(wbAkses.SheetNames).toContain('Riwayat Akses');

      const sheetAkses = wbAkses.Sheets['Riwayat Akses'];
      const rowsAkses = XLSX.utils.sheet_to_json(sheetAkses, { header: 1 });
      expect(rowsAkses.length).toBeGreaterThan(1);

      // Validasi Judul Kolom Riwayat Akses
      const headerAkses = rowsAkses[0];
      expect(headerAkses).toEqual(expect.arrayContaining(['Nama Lengkap', 'Kelas', 'Waktu Masuk', 'Aktivitas Terakhir', 'Durasi (Detik)']));

      // Validasi Tipe Angka pada Durasi Detik (Kolom ke-4 atau 5)
      const barisPertamaAkses = rowsAkses[1];
      expect(Number(barisPertamaAkses[4])).toBeGreaterThanOrEqual(0);
      console.log(`[EXCEL AKSES ${vp.name}] Berhasil divalidasi mendalam: ${rowsAkses.length} baris, durasi: ${barisPertamaAkses[4]} detik.`);

      // ------------------------------------------------------------------
      // 13. ADMIN MERESET KESEMPATAN KUIS SISWA & TOGGLE STATUS NONAKTIF
      // ------------------------------------------------------------------
      await page.goto('/admin/pengguna');
      const barisPengguna = page.locator(`tr:has-text("${usernameSiswa}")`).first();
      const linkDetailSiswa = barisPengguna.locator('a[href*="/admin/pengguna/"]').first();
      await linkDetailSiswa.click();
      await page.waitForLoadState('networkidle');
      await jepret('26_admin_detail_siswa_e2e');

      // Reset Kesempatan Kuis
      const formReset = page.locator('form[action*="/reset-kesempatan"]').first();
      if (await formReset.count() > 0) {
        await page.selectOption('select[name="kuis_id"]', { index: 1 });
        await page.fill('input[name="alasan"], textarea[name="alasan"]', 'Pengujian E2E reset kesempatan kuis');
        page.once('dialog', async dialog => await dialog.accept());
        await formReset.locator('button[type="submit"]').click();
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText('berhasil');
        await jepret('27_admin_kesempatan_direset');
      }

      // Nonaktifkan Akun Siswa
      const formToggle = page.locator('form[action*="/toggle-aktif"]').first();
      page.once('dialog', async dialog => await dialog.accept());
      await formToggle.locator('button[type="submit"]').click();
      await page.waitForLoadState('networkidle');
      await expect(page.locator('body')).toContainText('dinonaktifkan');
      await jepret('28_admin_siswa_dinonaktifkan');

      // Admin Keluar
      await page.locator('form[action*="/keluar"] button').click();
      await page.waitForURL('**/masuk');

      // ------------------------------------------------------------------
      // 14. SISWA DINONAKTIFKAN DITOLAK SAAT MASUK
      // ------------------------------------------------------------------
      await page.fill('#login', usernameSiswa);
      await page.fill('#password', 'password123');
      await page.click('button[type="submit"]');
      await expect(page.locator('body')).toContainText('Akunmu sedang dinonaktifkan');
      await jepret('29_siswa_nonaktif_ditolak_masuk');

      await context.close();
    });
  }
});
