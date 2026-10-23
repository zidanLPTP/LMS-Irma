import { test, expect } from '@playwright/test';

test.describe('Pengujian Interaksi SweetAlert2 Pengganti Alert Bawaan', () => {
    test('Modal konfirmasi SweetAlert2 muncul saat klik hapus dan dapat dibatalkan', async ({ page }) => {
        // 1. Masuk sebagai Admin
        await page.goto('/masuk');
        await page.fill('#login', 'admin');
        await page.fill('#password', 'password123');
        await page.click('button[type="submit"]');

        await page.waitForTimeout(1000);
        if (page.url().includes('/password')) {
            await page.fill('#password_lama', 'password123');
            await page.fill('#password', 'AdminBioSTEMX2026!');
            await page.fill('#password_confirmation', 'AdminBioSTEMX2026!');
            await page.click('button[type="submit"]');
            await page.waitForURL('**/admin/beranda');
        } else {
            await page.waitForURL('**/admin/**');
        }

        // 2. Kunjungi halaman Bab dan Materi
        await page.goto('/admin/bab');
        await page.waitForLoadState('networkidle');

        // 3. Cari form hapus bab dengan atribut data-konfirmasi
        const formHapus = page.locator('form[data-konfirmasi]').first();
        expect(await formHapus.count()).toBeGreaterThan(0);

        const pesanKonfirmasi = await formHapus.getAttribute('data-konfirmasi');
        expect(pesanKonfirmasi).toContain('Apakah kamu yakin ingin menghapus bab ini?');

        // 4. Klik tombol Hapus bab
        const tombolHapus = formHapus.locator('button[type="submit"]');
        await tombolHapus.scrollIntoViewIfNeeded();
        await tombolHapus.click();

        // 5. Verifikasi popup SweetAlert2 muncul menggantikan confirm bawaan
        const popupSwal = page.locator('.swal2-popup.swal2-biostemx-popup');
        await expect(popupSwal).toBeVisible({ timeout: 5000 });

        // Verifikasi teks judul dan teks konfirmasi
        await expect(page.locator('.swal2-title')).toHaveText('Konfirmasi Tindakan');
        await expect(page.locator('.swal2-html-container')).toContainText('Apakah kamu yakin ingin menghapus bab ini?');

        // Verifikasi tombol Batal dan tombol Ya, Hapus terlihat
        const tombolBatal = page.locator('button.swal2-cancel');
        const tombolKonfirmasi = page.locator('button.swal2-confirm');
        await expect(tombolBatal).toBeVisible();
        await expect(tombolKonfirmasi).toBeVisible();
        await expect(tombolKonfirmasi).toHaveText('Ya, Hapus');

        // 6. Klik tombol Batal
        await tombolBatal.click();

        // 7. Verifikasi popup SweetAlert2 tertutup dan form tidak disubmit
        await expect(popupSwal).not.toBeVisible({ timeout: 3000 });
        expect(page.url()).toContain('/admin/bab');
    });

    test('Modal konfirmasi SweetAlert2 menghapus data ketika dikonfirmasi', async ({ page }) => {
        // 1. Masuk sebagai Admin
        await page.goto('/masuk');
        await page.fill('#login', 'admin');
        await page.fill('#password', 'password123');
        await page.click('button[type="submit"]');

        await page.waitForTimeout(1000);
        if (page.url().includes('/password')) {
            await page.fill('#password_lama', 'password123');
            await page.fill('#password', 'AdminBioSTEMX2026!');
            await page.fill('#password_confirmation', 'AdminBioSTEMX2026!');
            await page.click('button[type="submit"]');
            await page.waitForURL('**/admin/beranda');
        } else {
            await page.waitForURL('**/admin/**');
        }

        // 2. Buat Bab Uji Baru
        await page.goto('/admin/bab/create');
        await page.fill('#judul', 'Bab Uji Hapus SweetAlert');
        await page.fill('#deskripsi', 'Pengujian penghapusan bab dengan SweetAlert2');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/bab');

        // Pastikan bab uji muncul
        await expect(page.locator('body')).toContainText('Bab Uji Hapus SweetAlert');

        // 3. Cari baris bab uji dan tombol hapusnya
        const barisBab = page.locator('tr, div').filter({ hasText: 'Bab Uji Hapus SweetAlert' }).first();
        const formHapus = barisBab.locator('form[data-konfirmasi]').first();
        const tombolHapus = formHapus.locator('button[type="submit"]');

        await tombolHapus.scrollIntoViewIfNeeded();
        await tombolHapus.click();

        // 4. Verifikasi popup SweetAlert2 muncul
        const popupSwal = page.locator('.swal2-popup.swal2-biostemx-popup');
        await expect(popupSwal).toBeVisible({ timeout: 5000 });

        // 5. Klik Ya, Hapus di SweetAlert2
        const tombolKonfirmasi = page.locator('button.swal2-confirm');
        await tombolKonfirmasi.click();

        // 6. Verifikasi penghapusan berhasil diproses
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toContainText('berhasil');
    });
});
