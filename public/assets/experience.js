(() => {
  "use strict";
  function scoreSample(total, correct, wrong, rules) {
    if (
      ![total, correct, wrong].every(Number.isInteger) ||
      total < 1 ||
      correct < 0 ||
      wrong < 0 ||
      correct + wrong > total
    )
      return {
        error: "Jumlah benar + salah tidak boleh melebihi jumlah soal.",
      };
    const blank = total - correct - wrong;
    return {
      blank,
      score:
        Math.round(
          (correct * rules.correct +
            wrong * rules.wrong +
            blank * rules.blank) *
            100,
        ) / 100,
    };
  }
  const paths = {
    dashboard: "M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z",
    subjects:
      "M4 4h6a4 4 0 0 1 4 4v13a4 4 0 0 0-4-3H4z M14 8a4 4 0 0 1 4-4h3v14h-3a4 4 0 0 0-4 3",
    participants:
      "M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M20 21v-2a4 4 0 0 0-3-3.9 M16 3.1a4 4 0 0 1 0 7.8",
    questions: "M5 3h12l3 3v15H5z M9 8h7 M9 12h7 M9 16h4",
    sessions: "M8 2v4 M16 2v4 M3 10h18 M3 4h18v17H3z M8 14h2 M14 14h2",
    monitor: "M3 12h4l3-7 4 14 3-7h4",
    winners: "M8 3h8v7a4 4 0 0 1-8 0V3 M8 5H4v3a4 4 0 0 0 4 4 M16 5h4v3a4 4 0 0 1-4 4 M12 14v5 M8 21h8",
    results: "M5 21V11h4v10 M11 21V3h4v18 M17 21v-7h4v7",
    plan: "M4 5h16 M4 12h16 M4 19h16 M8 2v20",
  };
  function icon(key) {
    return `<svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="${paths[key] || paths.sessions}"/></svg>`;
  }
  function enhance(scope = document) {
    for (const a of scope.querySelectorAll("[data-nav-icon]")) {
      if (!a.dataset.iconAdded) {
        a.insertAdjacentHTML("afterbegin", icon(a.dataset.navIcon));
        a.dataset.iconAdded = "1";
      }
    }
    const form = scope.querySelector("#session, [data-session-form]"),
      sim = scope.querySelector("[data-score-simulator]");
    if (form && sim && !sim.dataset.enhanced) {
      sim.dataset.enhanced = "1";
      const get = (name) => form.elements.namedItem(name),
        number = (name, fallback) => Number(get(name)?.value ?? fallback),
        totalName = get("count") ? "count" : "question_count";
      const correctCount = sim.querySelector("[data-sample-correct]"),
        wrongCount = sim.querySelector("[data-sample-wrong]"),
        out = sim.querySelector("output"),
        eq = sim.querySelector("[data-equation]"),
        error = sim.querySelector("[data-validation]");
      let oldTotal = number(totalName, 10);
      correctCount.value = Math.floor(oldTotal * 0.7);
      wrongCount.value = Math.floor(oldTotal * 0.2);
      const update = () => {
        const total = number(totalName, 10);
        if (total !== oldTotal) {
          correctCount.value = Math.floor(total * 0.7);
          wrongCount.value = Math.floor(total * 0.2);
          oldTotal = total;
        }
        const rules = {
          correct: number(get("correct") ? "correct" : "correct_score", 4),
          wrong: number(get("wrong") ? "wrong" : "incorrect_score", -1),
          blank: number(get("blank") ? "blank" : "blank_score", 0),
        };
        const result = scoreSample(
          total,
          Number(correctCount.value),
          Number(wrongCount.value),
          rules,
        );
        error.textContent = result.error || "";
        out.textContent = result.error
          ? "—"
          : new Intl.NumberFormat("id-ID", { maximumFractionDigits: 2 }).format(
              result.score,
            );
        eq.textContent = result.error
          ? "Periksa jumlah jawaban contoh."
          : `${correctCount.value} benar × ${rules.correct} + ${wrongCount.value} salah × ${rules.wrong} + ${result.blank} kosong × ${rules.blank}`;
        const meta = sim.querySelector("[data-preview-meta]");
        if (meta)
          meta.textContent = `${total} soal · ${number(get("duration") ? "duration" : "duration_minutes", 90)} menit · ${number("option_count", 4)} opsi`;
        const title = sim.querySelector("[data-preview-title]");
        if (title) title.textContent = get("name")?.value.trim() || "Sesi baru";
      };
      form.addEventListener("input", update);
      form.addEventListener("change", update);
      sim.addEventListener("input", update);
      update();
    }
    for (const box of scope.querySelectorAll("[data-search-table]")) {
      if (box.dataset.enhanced) continue;
      box.dataset.enhanced = "1";
      const input = box.querySelector("[data-search-input]"),
        count = box.querySelector("[data-search-count]"),
        rows = Array.from(box.querySelectorAll("tbody tr, [data-search-entry]"));
      if (!input) continue;
      const update = () => {
        let visible = 0;
        for (const r of rows) {
          const hit = r.textContent
            .toLocaleLowerCase("id")
            .includes(input.value.trim().toLocaleLowerCase("id"));
          r.hidden = !hit;
          if (hit) visible++;
        }
        if (count) count.textContent = `${visible} dari ${rows.length} baris`;
        const empty = box.querySelector("[data-search-empty]");
        if (empty) empty.hidden = visible !== 0;
      };
      input.addEventListener("input", update);
      update();
    }
  }
  window.CbtUX = { scoreSample, icon, enhance };
  document.addEventListener("DOMContentLoaded", () => enhance());
})();
