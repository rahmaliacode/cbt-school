document.addEventListener("submit", (e) => {
    const text = e.target.dataset.confirm;
    if (text && !confirm(text)) e.preventDefault();
});
window.cbtFetch = async (url, options = {}) => {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 12000);
    try {
        const response = await fetch(url, {
            credentials: "same-origin",
            ...options,
            signal: controller.signal,
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]',
                ).content,
                ...options.headers,
            },
        });
        const data = await response.json();
        if (!response.ok)
            throw new Error(
                Object.values(data.errors || {})
                    .flat()
                    .join(" ") ||
                    data.message ||
                    "Permintaan gagal",
            );
        return data;
    } finally {
        clearTimeout(timeout);
    }
};
