(() => {
    "use strict";
    const el = (id) => document.getElementById(id),
        app = el("exam-app"),
        key = "cbt-attempt-" + app.dataset.attempt;
    let questions = [],
        answers = {},
        pending = {},
        index = 0,
        busy = false,
        submitting = false,
        remaining = 0,
        anchor = performance.now(),
        expired = false,
        ready = false;
    try {
        pending = JSON.parse(localStorage.getItem(key) || "{}");
    } catch {
        pending = {};
    }
    const remember = () => {
        try {
            localStorage.setItem(key, JSON.stringify(pending));
        } catch {
            el("save-status").textContent =
                "Penyimpanan browser tidak tersedia. Pastikan koneksi server stabil.";
        }
    };
    function render() {
        if (!ready) return;
        const q = questions[index],
            a = pending[q.id] || answers[q.id] || {};
        el("question-number").textContent =
            "SOAL " + (index + 1) + " DARI " + questions.length;
        el("question-text").textContent = q.text;
        if (el("question-media")) {
            el("question-media").hidden = !q.image_url;
            const image = el("question-image");
            if (q.image_url) { image.src = q.image_url; image.alt = q.image_alt || "Gambar pendukung soal"; }
            else image.removeAttribute("src");
            el("question-image-alt").textContent = q.image_alt || "";
        }
        el("options").replaceChildren();
        q.options.forEach((o, i) => {
            const label = document.createElement("label");
            label.className =
                "option" + (a.answer_option_id === o.id ? " selected" : "");
            const radio = document.createElement("input");
            radio.type = "radio";
            radio.name = "answer";
            radio.checked = a.answer_option_id === o.id;
            radio.disabled = expired || submitting;
            radio.addEventListener("change", () =>
                edit({ answer_option_id: o.id }),
            );
            const letter = document.createElement("strong");
            letter.textContent = String.fromCharCode(65 + i) + ".";
            const text = document.createElement("span");
            text.textContent = o.text;
            label.append(radio, letter, text);
            el("options").append(label);
        });
        el("question-grid").replaceChildren();
        let answered = 0;
        questions.forEach((q, i) => {
            const a = pending[q.id] || answers[q.id] || {};
            if (a.answer_option_id) answered++;
            const b = document.createElement("button");
            b.type = "button";
            b.textContent = i + 1;
            b.className =
                (a.answer_option_id ? "answered " : "") +
                (index === i ? "current" : "");
            b.setAttribute(
                "aria-label",
                "Soal " +
                    (i + 1) +
                    (a.answer_option_id ? ", sudah dijawab" : ""),
            );
            b.setAttribute("aria-current", index === i ? "true" : "false");
            b.onclick = () => {
                index = i;
                render();
            };
            el("question-grid").append(b);
        });
        el("previous").disabled = index === 0;
        el("next").disabled = index === questions.length - 1;
        el("progress").textContent =
            answered +
            "/" +
            questions.length +
            " terjawab";
        el("summary-answered").textContent = answered;
        el("summary-blank").textContent = questions.length - answered;
    }
    function edit(changes) {
        if (expired || submitting) return;
        const id = questions[index].id;
        const current = pending[id] ||
            answers[id] || {
                answer_option_id: null,
                is_flagged: false,
                revision: 0,
            };
        pending[id] = {
            ...current,
            ...changes,
            is_flagged: false,
            question_id: id,
            revision: current.revision + 1,
        };
        remember();
        el("save-status").textContent = "Menyimpan…";
        render();
        flush();
    }
    async function flush() {
        if (busy || !ready || expired) return;
        busy = true;
        try {
            while (Object.keys(pending).length) {
                const id = Object.keys(pending)[0],
                    payload = { ...pending[id] };
                const data = await cbtFetch("/exam/answer", {
                    method: "POST",
                    body: JSON.stringify(payload),
                });
                if (data.status !== "in_progress") {
                    expired = true;
                    location.href = "/finished";
                    return;
                }
                answers[id] = payload;
                if (pending[id]?.revision === payload.revision)
                    delete pending[id];
                remember();
            }
            el("network-warning").hidden = true;
            el("save-status").textContent = "✓ Semua jawaban tersimpan";
        } catch (e) {
            el("network-warning").hidden = false;
            el("save-status").textContent = "Belum tersimpan: " + e.message;
        } finally {
            busy = false;
            render();
        }
    }
    async function sync() {
        try {
            const before = performance.now();
            const data = await cbtFetch("/exam/state");
            if (data.status !== "in_progress") {
                location.href = "/finished";
                return;
            }
            questions = data.questions;
            answers = Object.fromEntries(
                data.answers.map((a) => [a.question_id, a]),
            );
            remaining = Math.max(
                0,
                data.deadline_at -
                    data.server_now -
                    (performance.now() - before) / 2,
            );
            anchor = performance.now();
            for (const id of Object.keys(pending)) {
                if (!questions.some((q) => String(q.id) === id)) {
                    delete pending[id];
                    continue;
                }
                pending[id].revision = Math.max(
                    pending[id].revision,
                    (answers[id]?.revision || 0) + 1,
                );
            }
            remember();
            ready = true;
            el("finish").disabled = false;
            render();
            flush();
        } catch (e) {
            el("save-status").textContent =
                "Server tidak terhubung: " + e.message;
        } finally {
            setTimeout(sync, 10000);
        }
    }
    async function submit(force = false) {
        if (submitting) return;
        if (!force && (busy || Object.keys(pending).length)) {
            el("submit-message").textContent =
                "Masih ada jawaban yang belum tersimpan. Tunggu atau periksa koneksi lalu coba lagi.";
            flush();
            return;
        }
        submitting = true;
        el("confirm-submit").disabled = true;
        el("finish").disabled = true;
        el("submit-message").textContent = "Mengumpulkan jawaban…";
        render();
        try {
            await cbtFetch("/exam/submit", { method: "POST", body: "{}" });
            try {
                localStorage.removeItem(key);
            } catch {}
            location.href = "/finished";
        } catch (e) {
            el("submit-message").textContent =
                "Gagal mengumpulkan: " + e.message;
            el("save-status").textContent =
                "Submit belum terkonfirmasi. Mencoba kembali…";
            submitting = false;
            el("confirm-submit").disabled = false;
            el("finish").disabled = false;
            if (force) setTimeout(() => submit(true), 3000);
        }
    }
    el("previous").onclick = () => {
        if (index > 0) {
            index--;
            render();
        }
    };
    el("next").onclick = () => {
        if (index < questions.length - 1) {
            index++;
            render();
        }
    };
    el("finish").disabled = true;
    el("finish").onclick = () => {
        render();
        el("submit-message").textContent = Object.keys(pending).length
            ? "Ada jawaban yang belum tersimpan."
            : "Semua jawaban telah tersimpan ke server.";
        el("submit-dialog").showModal();
    };
    el("cancel-submit").onclick = () => el("submit-dialog").close();
    el("confirm-submit").onclick = () => submit();
    el("retry-save").onclick = () => {
        sync();
    };
    window.addEventListener("online", flush);
    window.addEventListener("beforeunload", (e) => {
        if (Object.keys(pending).length) {
            e.preventDefault();
            e.returnValue = "";
        }
    });
    setInterval(() => {
        if (!ready) return;
        const ms = Math.max(0, remaining - (performance.now() - anchor)),
            s = Math.ceil(ms / 1000);
        el("timer").textContent = [
            Math.floor(s / 3600),
            Math.floor(s / 60) % 60,
            s % 60,
        ]
            .map((x) => String(x).padStart(2, "0"))
            .join(":");
        if (ms === 0 && !expired) {
            expired = true;
            submit(true);
        }
    }, 200);
    setInterval(() => {
        if (Object.keys(pending).length && !expired) flush();
    }, 3000);
    sync();
})();
