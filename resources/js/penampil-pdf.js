import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

// Set worker src dari Vite asset URL lokal
pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorker;

window.initPenampilPdf = function (canvasId, pdfUrl, statusInfoId, btnPrevId, btnNextId, wadahId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const statusInfo = document.getElementById(statusInfoId);
    const btnPrev = document.getElementById(btnPrevId);
    const btnNext = document.getElementById(btnNextId);
    const wadah = document.getElementById(wadahId) || canvas.parentElement;

    let docPdf = null;
    let halamanSekarang = 1;
    let totalHalaman = 0;
    let sedangMerender = false;
    let antreanHalaman = null;

    function perbaruiTombol() {
        if (btnPrev) btnPrev.disabled = (halamanSekarang <= 1);
        if (btnNext) btnNext.disabled = (halamanSekarang >= totalHalaman);
        if (statusInfo) {
            statusInfo.textContent = `Halaman ${halamanSekarang} dari ${totalHalaman}`;
        }
    }

    function renderHalaman(nomorHalaman) {
        sedangMerender = true;
        if (statusInfo) {
            statusInfo.textContent = `Memuat halaman ${nomorHalaman}...`;
        }

        docPdf.getPage(nomorHalaman).then(function (page) {
            const wadahLebar = wadah.clientWidth || 600;
            const viewportAwal = page.getViewport({ scale: 1.0 });
            // Sesuaikan scale agar lebar pas wadah di HP/layar kecil (maks scale 2 untuk resolusi tajam)
            const scale = Math.min((wadahLebar - 16) / viewportAwal.width, 2.0);
            const viewport = page.getViewport({ scale: Math.max(scale, 0.6) });

            canvas.height = viewport.height;
            canvas.width = viewport.width;

            const renderContext = {
                canvasContext: ctx,
                viewport: viewport,
            };

            const renderTask = page.render(renderContext);
            renderTask.promise.then(function () {
                sedangMerender = false;
                perbaruiTombol();
                if (antreanHalaman !== null) {
                    const antrean = antreanHalaman;
                    antreanHalaman = null;
                    renderHalaman(antrean);
                }
            });
        }).catch(function (error) {
            sedangMerender = false;
            if (statusInfo) {
                statusInfo.textContent = 'Gagal memuat halaman.';
            }
        });
    }

    function antrekanHalaman(nomorHalaman) {
        if (sedangMerender) {
            antreanHalaman = nomorHalaman;
        } else {
            renderHalaman(nomorHalaman);
        }
    }

    if (btnPrev) {
        btnPrev.addEventListener('click', function () {
            if (halamanSekarang <= 1) return;
            halamanSekarang--;
            antrekanHalaman(halamanSekarang);
        });
    }

    if (btnNext) {
        btnNext.addEventListener('click', function () {
            if (halamanSekarang >= totalHalaman) return;
            halamanSekarang++;
            antrekanHalaman(halamanSekarang);
        });
    }

    // Muat dokumen PDF
    const loadingTask = pdfjsLib.getDocument({
        url: pdfUrl,
        withCredentials: true,
    });

    loadingTask.promise.then(function (pdf) {
        docPdf = pdf;
        totalHalaman = pdf.numPages;
        renderHalaman(halamanSekarang);
    }).catch(function (error) {
        if (statusInfo) {
            statusInfo.textContent = 'Gagal memuat berkas PDF materi. Silakan refresh halaman atau hubungi admin.';
            statusInfo.classList.add('text-red-700');
        }
    });
};
