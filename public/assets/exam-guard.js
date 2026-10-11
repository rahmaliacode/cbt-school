(() => {
    'use strict';
    const app = document.getElementById('exam-app');
    if (!app) return;
    let active = true, sending = false, opened = false, fallback = false;
    const key = 'cbt-focus-' + app.dataset.attempt;
    const examUrl = location.href;
    let queue = [];
    try { queue = JSON.parse(localStorage.getItem(key) || '[]'); if (!Array.isArray(queue)) queue = []; } catch {}
    const persist = () => { try { localStorage.setItem(key, JSON.stringify(queue)); } catch {} };
    const gate = document.createElement('section');
    gate.className = 'exam-focus-gate'; gate.setAttribute('role', 'dialog'); gate.setAttribute('aria-modal', 'true'); gate.setAttribute('aria-labelledby', 'focus-title');
    gate.innerHTML = '<div class="card"><p class="eyebrow">MODE FOKUS UJIAN</p><h2 id="focus-title">Tetap di halaman ujian</h2><p id="focus-message" role="status">Aktifkan layar penuh untuk mengerjakan soal. Perpindahan tab atau keluar dari layar penuh dicatat untuk ditinjau panitia.</p><p class="small muted">Waktu ujian tetap berjalan. Website tidak dapat mengunci perangkat sepenuhnya.</p><button type="button" id="focus-resume">Aktifkan layar penuh & lanjutkan</button><button type="button" id="focus-fallback" class="secondary" hidden>Lanjutkan dengan pengawasan panitia</button><p class="small muted" id="focus-log-status"></p></div>';
    document.body.append(gate);
    const message = gate.querySelector('#focus-message');
    const logStatus = gate.querySelector('#focus-log-status');
    const resume = gate.querySelector('#focus-resume');
    const fallbackButton = gate.querySelector('#focus-fallback');
    const columns = app.querySelector('.exam-columns');
    const show = text => {
        if (!active) return;
        if (text) message.textContent = text;
        gate.hidden = false;
        if (columns) columns.inert = true;
        // Close image/submit overlays before showing the focus warning.
        document.querySelectorAll('dialog[open]').forEach(dialog => dialog.close());
        if (document.visibilityState !== 'hidden') resume.focus();
    };
    const hide = () => { gate.hidden = true; if (columns) columns.inert = false; };
    async function flush() {
        if (sending || !active || !queue.length || typeof window.cbtFetch !== 'function') return;
        sending = true;
        const batch = queue.slice(0, 20);
        try {
            const response = await window.cbtFetch('/exam/focus-events', {method: 'POST', body: JSON.stringify({events: batch}), keepalive: true});
            if (response.status && response.status !== 'in_progress') { finish(); return; }
            const ids = new Set(batch.map(event => event.id));
            queue = queue.filter(event => !ids.has(event.id)); persist();
            logStatus.textContent = queue.length ? 'Catatan sedang disinkronkan…' : 'Catatan tersimpan.';
        } catch {
            logStatus.textContent = 'Catatan belum terkirim. Akan dicoba lagi saat koneksi tersedia.';
        } finally { sending = false; }
    }
    const uuid = () => crypto.randomUUID ? crypto.randomUUID() : '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, c => (Number(c) ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> Number(c) / 4).toString(16));
    const recent = new Map();
    function record(type) {
        if (!active) return;
        const now = Date.now();
        if (now - (recent.get(type) || 0) < 1500) return;
        recent.set(type, now);
        // Keep a bounded retry queue; the server also limits each attempt's audit records.
        if (queue.length < 100) queue.push({id: uuid(), type});
        persist(); flush();
    }
    function finish() {
        active = false; hide();
        window.removeEventListener('beforeunload', beforeLeave);
        if (document.fullscreenElement) document.exitFullscreen?.().catch(() => {});
        try { localStorage.removeItem(key); } catch {}
    }
    function beforeLeave(event) {
        if (!active) return;
        event.preventDefault(); event.returnValue = '';
    }
    window.CbtGuard = {finish};
    resume.onclick = async () => {
        if (!active) return;
        try {
            if (!document.fullscreenElement) await document.documentElement.requestFullscreen();
            fallback = false; opened = true; hide(); record('fullscreen_enter');
        } catch {
            message.textContent = 'Browser ini tidak mengizinkan layar penuh. Hubungi panitia atau lanjutkan dengan pengawasan; perpindahan halaman tetap dicatat.';
            fallbackButton.hidden = false; record('fullscreen_unavailable');
        }
    };
    fallbackButton.onclick = () => { fallback = true; opened = true; hide(); record('supervised_mode'); };
    document.addEventListener('fullscreenchange', () => {
        if (active && opened && !fallback && !document.fullscreenElement) {
            record('fullscreen_exit'); show('Kamu keluar dari layar penuh. Aktifkan kembali untuk melanjutkan; catatan dikirim ke panitia.');
        }
    });
    document.addEventListener('visibilitychange', () => {
        if (!active || !opened) return;
        if (document.visibilityState === 'hidden') { record('page_hidden'); show('Halaman ujian sempat ditinggalkan. Kembali ke mode fokus untuk melanjutkan.'); }
        else if (!gate.hidden) { resume.focus(); flush(); }
    });
    window.addEventListener('blur', () => {
        if (active && opened && gate.hidden && document.visibilityState !== 'hidden') {
            record('window_blur'); show('Jendela ujian kehilangan fokus. Kembali ke mode fokus untuk melanjutkan.');
        }
    });
    document.addEventListener('click', event => {
        if (active && event.target.closest('a[href], #demo-toolbar [data-action]')) {
            event.preventDefault(); event.stopImmediatePropagation(); record('navigation_blocked');
            show('Selesaikan dan kumpulkan jawaban sebelum membuka halaman lain.');
        }
    }, true);
    document.addEventListener('keydown', event => {
        if (!active) return;
        if (event.key === 'F5' || ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'r') || (event.altKey && event.key === 'ArrowLeft')) {
            event.preventDefault(); record('shortcut_blocked'); show('Memuat ulang atau kembali ke halaman lain dibatasi selama ujian.');
        }
    }, true);
    // A single history entry prevents ordinary Back navigation within this tab.
    history.pushState({cbtExam: true}, '', examUrl);
    window.addEventListener('popstate', () => {
        if (!active) return;
        history.pushState({cbtExam: true}, '', examUrl); record('navigation_blocked');
        show('Tombol kembali dibatasi. Kumpulkan jawaban sebelum keluar dari ujian.');
    });
    window.addEventListener('beforeunload', beforeLeave);
    window.addEventListener('online', flush);
    setInterval(flush, 5000);
    show(); record('exam_open');
})();
