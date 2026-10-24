(() => {
    const body = document.getElementById("monitor-body"),
        status = document.getElementById("monitor-status");
    const labels = {
        not_started: "Belum mulai",
        in_progress: "Mengerjakan",
        submitted: "Selesai",
        timed_out: "Waktu habis",
    };
    const cell = (tr, text) => {
        const td = document.createElement("td");
        td.textContent = text;
        tr.append(td);
        return td;
    };
    const pill = (td, text, tone) => {
        const span = document.createElement("span");
        span.className = "pill " + tone;
        span.textContent = text;
        td.replaceChildren(span);
    };
    const tones = {
        not_started: "pending",
        in_progress: "running",
        submitted: "final",
        timed_out: "ended",
    };
    async function poll() {
        try {
            const data = await cbtFetch(window.monitorEndpoint);
            const count = (id, predicate) => {
                const target = document.getElementById(id);
                if (target) target.textContent = data.participants.filter(predicate).length;
            };
            count("monitor-waiting", p => p.status === "not_started");
            count("monitor-active", p => p.status === "in_progress");
            count("monitor-submitted", p => ["submitted", "timed_out"].includes(p.status));
            count("monitor-disconnected", p => p.status === "in_progress" && p.connection === "Terputus");
            body.replaceChildren();
            data.participants.forEach((p) => {
                const tr = document.createElement("tr");
                cell(tr, p.participant_number + " · " + p.name);
                pill(cell(tr, ""), labels[p.status], tones[p.status]);
                cell(tr, p.progress);
                const sec = Math.floor(p.remaining_seconds);
                cell(
                    tr,
                    [
                        Math.floor(sec / 3600),
                        Math.floor(sec / 60) % 60,
                        sec % 60,
                    ]
                        .map((x) => String(x).padStart(2, "0"))
                        .join(":"),
                );
                const time = (v) =>
                    v
                        ? new Date(v).toLocaleTimeString("id-ID", {
                              timeZone: "Asia/Jakarta",
                          })
                        : "—";
                cell(tr, time(p.started_at) + " / " + time(p.submitted_at));
                pill(cell(tr, ""), p.connection, p.connection === "Terputus" ? "danger" : "neutral");
                cell(tr, p.focus_signals ? p.focus_signals + ' catatan · tinjau' : '—');
                const td = cell(tr, "");
                if (p.id && data.status === "running") {
                    const button = document.createElement("button");
                    button.textContent = "Reset";
                    button.className = "danger small-button";
                    button.onclick = async () => {
                        const reason = prompt(
                            "Alasan reset resmi (minimal 5 karakter). Jawaban dan hasil attempt ini akan dihapus; catatan audit disimpan.",
                        );
                        if (!reason) return;
                        if (!confirm("Konfirmasi reset " + p.name + "?"))
                            return;
                        try {
                            await cbtFetch(
                                "/admin/attempts/" + p.id + "/reset",
                                {
                                    method: "POST",
                                    body: JSON.stringify({ reason }),
                                },
                            );
                            poll();
                        } catch (e) {
                            alert(e.message);
                        }
                    };
                    td.append(button);
                }
                body.append(tr);
            });
            status.textContent =
                "Diperbarui " +
                new Date(data.server_now).toLocaleTimeString("id-ID", {
                    timeZone: "Asia/Jakarta",
                }) +
                " WIB · Polling setiap 5 detik";
        } catch (e) {
            status.textContent =
                "Koneksi gagal: " + e.message + " · Mencoba kembali…";
        } finally {
            setTimeout(poll, 5000);
        }
    }
    poll();
})();
