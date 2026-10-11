/**
 * Detak Jantung (Heartbeat) Aktivitas Browser Siswa LMS Penelitian
 * 
 * Aturan:
 * - Dikirim setiap intervalDetak detik (default 45s) HANYA saat tab terlihat (document.visibilityState === 'visible')
 * - Dikirim 1x saat inisialisasi dan saat tab kembali terlihat (visibilitychange -> visible)
 * - Saat pagehide: kirim via navigator.sendBeacon dengan _token (hanya memperbarui aktivitas, TIDAK menutup sesi)
 * - Berhenti jika server mengembalikan 401 atau 419 (sesi habis/tidak valid)
 * - Abaikan kegagalan jaringan tanpa mengulang cepat
 */
document.addEventListener('DOMContentLoaded', () => {
    const elMetaCsrf = document.querySelector('meta[name="csrf-token"]');
    if (!elMetaCsrf) return;

    const tokenCsrf = elMetaCsrf.getAttribute('content');
    const urlDetak = document.body.dataset.urlDetak || '/detak';
    const intervalDetak = parseInt(document.body.dataset.intervalDetak || '45', 10) * 1000;

    let idTimer = null;
    let sesiBerhenti = false;

    // Fungsi kirim detak jantung via fetch keepalive
    async function kirimDetak() {
        if (sesiBerhenti) return;
        if (document.visibilityState !== 'visible') return;

        try {
            const respons = await fetch(urlDetak, {
                method: 'POST',
                keepalive: true,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': tokenCsrf,
                },
                body: JSON.stringify({}),
            });

            if (respons.status === 401 || respons.status === 419) {
                sesiBerhenti = true;
                if (idTimer) clearInterval(idTimer);
            }
        } catch (galat) {
            // Abaikan kegagalan jaringan tanpa mengulang cepat
        }
    }

    // Fungsi mulai interval detak
    function mulaiInterval() {
        if (idTimer) clearInterval(idTimer);
        idTimer = setInterval(() => {
            if (document.visibilityState === 'visible') {
                kirimDetak();
            }
        }, intervalDetak);
    }

    // 1. Kirim sekali saat halaman pertama kali dimuat jika tab terlihat
    if (document.visibilityState === 'visible') {
        kirimDetak();
    }
    mulaiInterval();

    // 2. Pantau perubahan visibilitas tab (visibilitychange)
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            kirimDetak();
            mulaiInterval();
        } else {
            if (idTimer) {
                clearInterval(idTimer);
                idTimer = null;
            }
        }
    });

    // 3. Saat pagehide (perpindahan halaman atau tutup tab), kirim update via navigator.sendBeacon
    window.addEventListener('pagehide', () => {
        if (sesiBerhenti) return;

        const dataForm = new FormData();
        dataForm.append('_token', tokenCsrf);

        if (navigator.sendBeacon) {
            navigator.sendBeacon(urlDetak, dataForm);
        } else {
            fetch(urlDetak, {
                method: 'POST',
                keepalive: true,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': tokenCsrf,
                },
            }).catch(() => {});
        }
    });
});
