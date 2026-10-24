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
    function row(p, i) {
        const [done, total] = p.progress.split("/").map(Number);
        const tr = document.createElement("tr");
        tr.className = p.status;
        const cell = (content) => {
            const td = document.createElement("td");
            td.append(content);
            tr.append(td);
        };
        cell(String(i + 1));
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
        return tr;
    }
    async function poll() {
        try {
            const data = await cbtFetch(window.monitorEndpoint);
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
            el("display-body").replaceChildren(...data.participants.map(row));
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
