(() => {
    "use strict";
    const el = (id) => document.getElementById(id);
    const labels = {
        not_started: "Belum mulai",
        in_progress: "Mengerjakan",
        submitted: "Selesai",
        timed_out: "Waktu habis",
    };
    const tones = {
        not_started: "pending",
        in_progress: "running",
        submitted: "final",
        timed_out: "ended",
    };
    let endsAt = null,
        offset = 0,
        sessionStatus = "draft";
    function tick() {
        const timer = el("display-timer");
        if (sessionStatus !== "running" || !endsAt) {
            timer.textContent =
                sessionStatus === "ended" ? "Selesai" : "Belum dimulai";
            return;
        }
        const s = Math.max(
            0,
            Math.ceil((endsAt - (Date.now() + offset)) / 1000),
        );
        timer.textContent = [Math.floor(s / 3600), Math.floor(s / 60) % 60, s % 60]
            .map((x) => String(x).padStart(2, "0"))
            .join(":");
        timer.classList.toggle("low", s <= 300);
    }
    // Same as the @score Blade directive: 4.00 -> "4", 2.50 -> "2.5".
    const formatScore = (v) => String(Number(Number(v).toFixed(2)));
    function row(p, position, showScores) {
        const done = p.answered,
            total = p.total;
        const tr = document.createElement("tr");
        tr.className = p.status;
        const cell = (content) => {
            const td = document.createElement("td");
            td.append(content);
            tr.append(td);
            return td;
        };
        cell(String(position));
        cell(p.participant_number);
        cell(p.name);
        const status = document.createElement("span");
        status.className = "pill " + tones[p.status];
        status.textContent = labels[p.status];
        cell(status);
        const progress = document.createElement("div");
        progress.className = "display-progress";
        const track = document.createElement("div");
        track.className = "progress-track";
        const fill = document.createElement("div");
        fill.className = "progress-fill";
        fill.style.width = (total ? (done / total) * 100 : 0) + "%";
        track.append(fill);
        const count = document.createElement("span");
        count.textContent = done + "/" + total;
        progress.append(track, count);
        cell(progress);
        if (showScores) {
            const td = cell(p.score === null ? "—" : formatScore(p.score));
            td.className = "display-score";
            if (p.provisional) {
                const tag = document.createElement("small");
                tag.textContent = "sementara";
                td.append(tag);
            }
        }
        return tr;
    }
    async function poll() {
        try {
            const data = await cbtFetch(window.displayEndpoint);
            offset = Date.parse(data.server_now) - Date.now();
            endsAt = data.ends_at ? Date.parse(data.ends_at) : null;
            sessionStatus = data.status;
            const count = (id, predicate) =>
                (el(id).textContent = data.participants.filter(predicate).length);
            count("display-waiting", (p) => p.status === "not_started");
            count("display-active", (p) => p.status === "in_progress");
            count("display-submitted", (p) =>
                ["submitted", "timed_out"].includes(p.status),
            );
            const showScores = data.scores_visible;
            el("display-score-head").hidden = !showScores;
            el("display-score-note").hidden = !showScores || sessionStatus === "ended";
            el("display-first-head").textContent = showScores ? "Peringkat" : "No";
            // Server sorts by score when scores are visible; equal scores share a rank (1, 1, 3).
            let rank = 0;
            const rows = data.participants.map((p, i) => {
                const prev = data.participants[i - 1];
                if (!showScores || p.score === null) rank = i + 1;
                else if (!prev || prev.score !== p.score) rank = i + 1;
                return row(p, showScores && p.score === null ? "—" : rank, showScores);
            });
            el("display-body").replaceChildren(...rows);
            el("display-status").textContent =
                "Diperbarui " +
                new Date(data.server_now).toLocaleTimeString("id-ID", {
                    timeZone: "Asia/Jakarta",
                }) +
                " WIB";
            tick();
        } catch (e) {
            el("display-status").textContent =
                "Koneksi gagal: " + e.message + " · Mencoba kembali…";
        } finally {
            setTimeout(poll, 5000);
        }
    }
    setInterval(tick, 1000);
    poll();
})();
