import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

/**
 * Konfigurasi tema BioSTEMX untuk SweetAlert2
 */
const SwalBioSTEMX = Swal.mixin({
    showCancelButton: true,
    cancelButtonText: 'Batal',
    confirmButtonText: 'Ya, Lanjutkan',
    reverseButtons: true,
    focusCancel: true,
    buttonsStyling: false,
    customClass: {
        popup: 'swal2-biostemx-popup',
        title: 'swal2-biostemx-title',
        htmlContainer: 'swal2-biostemx-html',
        actions: 'swal2-biostemx-actions',
        confirmButton: 'swal2-biostemx-btn-utama',
        cancelButton: 'swal2-biostemx-btn-batal',
    },
});

/**
 * Helper menampilkan dialog konfirmasi modern dengan SweetAlert2
 *
 * @param {Object} opsi
 * @returns {Promise}
 */
export function konfirmasiAksi({
    judul = 'Konfirmasi Tindakan',
    pesan = 'Apakah Anda yakin ingin melanjutkan?',
    ikon = 'warning',
    teksTombol = 'Ya, Lanjutkan',
    teksBatal = 'Batal',
    adalahBahaya = false,
} = {}) {
    return SwalBioSTEMX.fire({
        title: judul,
        text: pesan,
        icon: ikon,
        confirmButtonText: teksTombol,
        cancelButtonText: teksBatal,
        customClass: {
            popup: 'swal2-biostemx-popup',
            title: 'swal2-biostemx-title',
            htmlContainer: 'swal2-biostemx-html',
            actions: 'swal2-biostemx-actions',
            confirmButton: adalahBahaya ? 'swal2-biostemx-btn-bahaya' : 'swal2-biostemx-btn-utama',
            cancelButton: 'swal2-biostemx-btn-batal',
        },
    });
}

/**
 * Helper alert sukses
 */
export function tampilkanSukses(judul, pesan = '') {
    return Swal.fire({
        icon: 'success',
        title: judul,
        text: pesan,
        confirmButtonText: 'OK',
        buttonsStyling: false,
        customClass: {
            popup: 'swal2-biostemx-popup',
            title: 'swal2-biostemx-title',
            htmlContainer: 'swal2-biostemx-html',
            actions: 'swal2-biostemx-actions',
            confirmButton: 'swal2-biostemx-btn-utama',
        },
    });
}

/**
 * Helper alert galat
 */
export function tampilkanGalat(judul, pesan = '') {
    return Swal.fire({
        icon: 'error',
        title: judul,
        text: pesan,
        confirmButtonText: 'Tutup',
        buttonsStyling: false,
        customClass: {
            popup: 'swal2-biostemx-popup',
            title: 'swal2-biostemx-title',
            htmlContainer: 'swal2-biostemx-html',
            actions: 'swal2-biostemx-actions',
            confirmButton: 'swal2-biostemx-btn-bahaya',
        },
    });
}

/**
 * Helper alert peringatan
 */
export function tampilkanPeringatan(judul, pesan = '') {
    return Swal.fire({
        icon: 'warning',
        title: judul,
        text: pesan,
        confirmButtonText: 'Mengerti',
        buttonsStyling: false,
        customClass: {
            popup: 'swal2-biostemx-popup',
            title: 'swal2-biostemx-title',
            htmlContainer: 'swal2-biostemx-html',
            actions: 'swal2-biostemx-actions',
            confirmButton: 'swal2-biostemx-btn-utama',
        },
    });
}

// Pasang ke window agar dapat diakses jika diperlukan
window.Swal = Swal;
window.konfirmasiAksi = konfirmasiAksi;
window.tampilkanSukses = tampilkanSukses;
window.tampilkanGalat = tampilkanGalat;
window.tampilkanPeringatan = tampilkanPeringatan;

/**
 * Inisialisasi pencegatan otomatis konfirmasi pada formulir dan tombol
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Mencegat submit formulir dengan atribut data-konfirmasi
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        const pesan = form.getAttribute('data-konfirmasi');
        if (!pesan) return;

        // Jika form sudah disetujui sebelumnya, biarkan submit berjalan
        if (form.dataset.dikonfirmasi === 'true') {
            form.removeAttribute('data-dikonfirmasi');
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        const judul = form.getAttribute('data-konfirmasi-judul') || 'Konfirmasi Tindakan';
        const teksTombol = form.getAttribute('data-konfirmasi-tombol') || 'Ya, Lanjutkan';
        const teksBatal = form.getAttribute('data-konfirmasi-batal') || 'Batal';
        const adalahBahaya = form.getAttribute('data-konfirmasi-bahaya') === 'true';
        const ikon = form.getAttribute('data-konfirmasi-ikon') || (adalahBahaya ? 'warning' : 'question');

        konfirmasiAksi({
            judul,
            pesan,
            ikon,
            teksTombol,
            teksBatal,
            adalahBahaya,
        }).then((hasil) => {
            if (hasil.isConfirmed) {
                form.dataset.dikonfirmasi = 'true';
                form.submit();
            }
        });
    });

    // 2. Mencegat klik tombol dengan atribut data-konfirmasi (misal: tombol spesifik dalam form)
    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) return;

        const tombol = target.closest('button[data-konfirmasi], a[data-konfirmasi]');
        if (!tombol) return;

        // Jika tombol berada di dalam form dan form belum disetujui
        if (tombol instanceof HTMLButtonElement && tombol.form) {
            const form = tombol.form;
            if (form.dataset.dikonfirmasi === 'true') {
                form.removeAttribute('data-dikonfirmasi');
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            const pesan = tombol.getAttribute('data-konfirmasi');
            const judul = tombol.getAttribute('data-konfirmasi-judul') || 'Konfirmasi Tindakan';
            const teksTombol = tombol.getAttribute('data-konfirmasi-tombol') || 'Ya, Lanjutkan';
            const teksBatal = tombol.getAttribute('data-konfirmasi-batal') || 'Batal';
            const adalahBahaya = tombol.getAttribute('data-konfirmasi-bahaya') === 'true';
            const ikon = tombol.getAttribute('data-konfirmasi-ikon') || (adalahBahaya ? 'warning' : 'question');

            konfirmasiAksi({
                judul,
                pesan,
                ikon,
                teksTombol,
                teksBatal,
                adalahBahaya,
            }).then((hasil) => {
                if (hasil.isConfirmed) {
                    form.dataset.dikonfirmasi = 'true';
                    if (tombol.name) {
                        const inputTersembunyi = document.createElement('input');
                        inputTersembunyi.type = 'hidden';
                        inputTersembunyi.name = tombol.name;
                        inputTersembunyi.value = tombol.value;
                        form.appendChild(inputTersembunyi);
                    }
                    form.submit();
                }
            });
        }
    });
});
