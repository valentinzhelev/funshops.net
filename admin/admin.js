/* =============================================================
   АДМИН ПАНЕЛ — SPA логика
   ============================================================= */
(function () {
    "use strict";

    const ASSET_BASE = (typeof window.SITE_ASSET_BASE === "string" && window.SITE_ASSET_BASE)
        ? window.SITE_ASSET_BASE
        : "../";
    const asset = p => !p ? "" : (/^https?:/i.test(p) ? p : ASSET_BASE + String(p).replace(/^\//, ""));
    const videoPreviewUrl = p => {
        if (!p) return "";
        if (/^https?:/i.test(p)) return p;
        const path = String(p).replace(/^\//, "").replace(/\\/g, "/");
        return ASSET_BASE + "video.php?f=" + encodeURIComponent(path) + "&v=" + Date.now();
    };
    const MAX_VIDEO_SEC = 300;
    async function readVideoDuration(file) {
        return new Promise((resolve, reject) => {
            const v = document.createElement("video");
            v.preload = "metadata";
            const url = URL.createObjectURL(file);
            v.onloadedmetadata = () => {
                URL.revokeObjectURL(url);
                const d = v.duration;
                if (!Number.isFinite(d) || d <= 0) reject(new Error("Не може да се прочете видеото."));
                else resolve(d);
            };
            v.onerror = () => {
                URL.revokeObjectURL(url);
                reject(new Error("Невалиден видео файл."));
            };
            v.src = url;
        });
    }
    /* ---------------------- Икони ---------------------- */
    const ICONS = {
        grid:'<path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/>',
        box:'<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
        tag:'<path d="M20 12 12 20l-8-8V4h8z"/><circle cx="7.5" cy="7.5" r="1.3"/>',
        cart:'<circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h2.2l2.1 12.3a1.5 1.5 0 0 0 1.5 1.2h8.9a1.5 1.5 0 0 0 1.5-1.1L21 7H5.3"/>',
        edit:'<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        cog:'<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-2.7 1.1V21a2 2 0 1 1-4 0v-.1A1.6 1.6 0 0 0 7 19.4a1.6 1.6 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.6 1.6 0 0 0 2.6 14H2.5a2 2 0 1 1 0-4h.1A1.6 1.6 0 0 0 4.6 7a1.6 1.6 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.6 1.6 0 0 0 10 2.6V2.5a2 2 0 1 1 4 0v.1A1.6 1.6 0 0 0 17 4.6a1.6 1.6 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.6 1.6 0 0 0-.3 1.8V9c.6.2 1 .8 1 1.5"/>',
        globe:'<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18 14 14 0 0 1 0-18z"/>',
        menu:'<path d="M4 7h16M4 12h16M4 17h16"/>',
        x:'<path d="M6 6l12 12M18 6 6 18"/>',
        plus:'<path d="M12 5v14M5 12h14"/>',
        search:'<circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/>',
        trash:'<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>',
        copy:'<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/>',
        eye:'<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>',
        check:'<path d="M20 6 9 17l-5-5"/>',
        users:'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
        money:'<rect x="2" y="5" width="20" height="14" rx="3"/><circle cx="12" cy="12" r="3"/><path d="M6 9v6M18 9v6"/>',
        bag:'<path d="M6 2 4 6v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6l-2-4z"/><path d="M4 6h16M16 10a4 4 0 0 1-8 0"/>',
        calendar:'<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        clock:'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        film:'<rect x="2" y="3" width="20" height="18" rx="2"/><path d="M7 3v18M17 3v18M2 8h5M2 16h5M17 8h5M17 16h5"/>',
        up:'<path d="M12 19V5M5 12l7-7 7 7"/>',
        left:'<path d="M15 18l-6-6 6-6"/>',
        right:'<path d="M9 18l6-6-6-6"/>',
        back:'<path d="M19 12H5M12 19l-7-7 7-7"/>'
    };
    function svg(name, w) { return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" ${w?`width="${w}" height="${w}"`:''}>${ICONS[name]||''}</svg>`; }
    function hydrateIcons(scope) { (scope||document).querySelectorAll('[data-i]').forEach(el => { el.innerHTML = svg(el.dataset.i); }); }

    /* ---------------------- API ---------------------- */
    async function api(action, opts = {}) {
        const url = `api.php?action=${action}` + (opts.query || "");
        const init = { method: opts.method || "GET", headers: {} };
        if (opts.body !== undefined) {
            init.method = "POST";
            init.headers["Content-Type"] = "application/json";
            init.headers["X-CSRF-Token"] = window.CSRF;
            init.body = JSON.stringify(opts.body);
        }
        if (opts.form) {
            init.method = "POST";
            init.headers["X-CSRF-Token"] = window.CSRF;
            init.body = opts.form;
        }
        const res = await fetch(url, init);
        if (res.status === 401) { toast("Сесията изтече. Влезте отново.", "err"); setTimeout(() => location.href = "login.php", 1200); throw new Error("401"); }
        const text = await res.text();
        let data;
        try { data = text ? JSON.parse(text) : { ok: false, error: "Празен отговор" }; }
        catch (e) { throw new Error("Невалиден отговор от сървъра"); }
        if (!data.ok) throw new Error(data.error || "Грешка");
        return data;
    }

    /* ---------------------- Toast ---------------------- */
    function toast(msg, type) {
        const wrap = document.getElementById("toastWrap");
        const el = document.createElement("div");
        el.className = "toast " + (type || "ok");
        el.innerHTML = `<span class="ni">${svg(type === "err" ? "x" : "check", 18)}</span><span>${msg}</span>`;
        wrap.appendChild(el);
        requestAnimationFrame(() => el.classList.add("show"));
        setTimeout(() => { el.classList.remove("show"); setTimeout(() => el.remove(), 400); }, 2800);
    }
    const esc = s => String(s ?? "").replace(/[&<>"]/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]));
    const money = n => (Math.round(Number(n) * 100) / 100).toFixed(2) + " €";
    const PRICE_COMMA_MSG = "Цената се пише само с точка (.), напр. 12.50 — не със запетая.";

    function sanitizePriceText(s) {
        s = String(s).replace(/,/g, ".").replace(/[^\d.]/g, "");
        const dot = s.indexOf(".");
        if (dot === -1) return s;
        return s.slice(0, dot + 1) + s.slice(dot + 1).replace(/\./g, "").slice(0, 2);
    }

    function parsePriceInput(val) {
        const s = String(val).trim();
        if (!s) return { ok: true, value: 0 };
        if (s.includes(",")) return { ok: false, reason: "comma" };
        if (!/^\d+(\.\d{1,2})?$/.test(s)) return { ok: false, reason: "invalid" };
        return { ok: true, value: parseFloat(s) };
    }

    function bindPriceInput(el) {
        const warnComma = () => toast(PRICE_COMMA_MSG, "err");
        el.addEventListener("keydown", e => {
            if (e.key === ",") { e.preventDefault(); warnComma(); }
        });
        el.addEventListener("input", () => {
            if (!el.value.includes(",")) {
                const clean = sanitizePriceText(el.value);
                if (clean !== el.value) el.value = clean;
                return;
            }
            el.value = sanitizePriceText(el.value);
            warnComma();
        });
        el.addEventListener("paste", e => {
            const t = (e.clipboardData || window.clipboardData).getData("text") || "";
            if (!/,/.test(t)) return;
            e.preventDefault();
            const start = el.selectionStart ?? el.value.length;
            const end = el.selectionEnd ?? el.value.length;
            el.value = sanitizePriceText(el.value.slice(0, start) + t + el.value.slice(end));
            warnComma();
        });
    }

    /* ---------------------- Модал ---------------------- */
    const modalScrim = document.getElementById("modalScrim");
    function openModal(title, html) {
        document.getElementById("modalTitle").textContent = title;
        document.getElementById("modalBody").innerHTML = html;
        modalScrim.classList.add("open");
        hydrateIcons(document.getElementById("modalBody"));
    }
    function closeModal() { modalScrim.classList.remove("open"); document.getElementById("modalBody").innerHTML = ""; }
    document.getElementById("modalClose").addEventListener("click", closeModal);
    modalScrim.addEventListener("click", e => { if (e.target === modalScrim) closeModal(); });

    /* ---------------------- Кеш на данни ---------------------- */
    let CATS = [], CAT_COUNTS = {};
    async function loadCats() { const d = await api("categories_list"); CATS = d.categories; CAT_COUNTS = d.counts; return d; }

    /* ---------------------- Рутер ---------------------- */
    const titles = { dashboard: "Табло", products: "Продукти", categories: "Категории", orders: "Поръчки", settings: "Настройки" };
    const views = {};
    function setActive(view) {
        document.querySelectorAll(".nav-item").forEach(n => n.classList.toggle("active", n.dataset.view === view));
        document.getElementById("viewTitle").textContent = titles[view] || "";
    }
    async function route() {
        const raw = location.hash.replace("#", "") || "dashboard";
        const content = document.getElementById("content");
        content.innerHTML = `<div class="loader">Зареждане…</div>`;
        closeSidebar();

        if (raw.startsWith("product/")) {
            const id = Number(raw.split("/")[1]);
            setActive("products");
            document.getElementById("viewTitle").textContent = id ? "Редакция на продукт" : "Нов продукт";
            try { await views.productEdit(content, id); }
            catch (e) { content.innerHTML = `<div class="empty">Грешка: ${esc(e.message)}</div>`; }
            hydrateIcons(content);
            return;
        }

        const view = raw;
        if (!views[view]) { location.hash = "dashboard"; return; }
        setActive(view);
        document.getElementById("viewTitle").textContent = titles[view] || "";
        try { await views[view](content); } catch (e) { content.innerHTML = `<div class="empty">Грешка: ${esc(e.message)}</div>`; }
        hydrateIcons(content);
    }
    window.addEventListener("hashchange", route);

    /* =========================================================
       ТАБЛО
       ========================================================= */
    let charts = {};
    views.dashboard = async (root) => {
        const s = await api("stats");
        const k = s.kpi, v = s.visits_kpi;
        root.innerHTML = `
          <div class="kpi-grid">
            ${kpiCard("blue","users", v.today, "Посещения днес", `Тази седмица: ${v.week}`)}
            ${kpiCard("amber","calendar", v.month, "Посещения (30 дни)", `За година: ${v.year}`)}
            ${kpiCard("purple","bag", k.total, "Поръчки общо", `Очаквани: ${k.pending} · Изпълнени: ${k.fulfilled}`)}
            ${kpiCard("green","money", money(k.revenue_fulfilled), "Приход (изпълнени)", `Потенциален: ${money(k.revenue_all)}`)}
          </div>

          <div class="kpi-grid section-gap">
            ${kpiCard("amber","clock", k.pending, "Очаквани поръчки", "Изискват обработка")}
            ${kpiCard("green","check", k.fulfilled, "Изпълнени поръчки", "Завършени успешно")}
            ${kpiCard("red","x", k.cancelled, "Отказани", "Отменени поръчки")}
            ${kpiCard("blue","box", s.products.available + " / " + s.products.total, "Налични продукти", "Активни в магазина")}
          </div>

          <div class="grid-2 section-gap">
            <div class="card">
              <div class="card-head"><h2>Посещения</h2>
                <div class="range-tabs" id="visitRange">
                  <button data-d="7">7 дни</button><button data-d="30" class="active">30 дни</button>
                  <button data-d="90">3 месеца</button><button data-d="365">Година</button>
                </div>
              </div>
              <div class="card-body"><div class="chart-wrap"><canvas id="visitsChart"></canvas></div></div>
            </div>
            <div class="card">
              <div class="card-head"><h2>Поръчки по статус</h2></div>
              <div class="card-body"><div class="chart-wrap"><canvas id="ordersDonut"></canvas></div></div>
            </div>
          </div>

          <div class="card section-gap">
            <div class="card-head"><h2>Поръчки във времето</h2>
              <div class="range-tabs" id="orderRange">
                <button data-d="30" class="active">30 дни</button><button data-d="90">3 месеца</button><button data-d="365">Година</button>
              </div>
            </div>
            <div class="card-body"><div class="chart-wrap small"><canvas id="ordersChart"></canvas></div></div>
          </div>`;

        Object.values(charts).forEach(c => c && c.destroy()); charts = {};

        const drawVisits = (days) => {
            const { labels, values } = series(s.visits_daily, days, "count");
            if (charts.visits) charts.visits.destroy();
            charts.visits = new Chart(document.getElementById("visitsChart"), {
                type: "line",
                data: { labels, datasets: [{ label: "Посетители", data: values, borderColor: "#2f7ce0",
                    backgroundColor: grad("#2f7ce0"), fill: true, tension: .35, pointRadius: days > 60 ? 0 : 3, borderWidth: 2.5 }] },
                options: baseOpts()
            });
        };
        const drawOrders = (days) => {
            const { labels, values } = series(s.orders_daily, days, "total");
            if (charts.orders) charts.orders.destroy();
            charts.orders = new Chart(document.getElementById("ordersChart"), {
                type: "bar",
                data: { labels, datasets: [{ label: "Поръчки", data: values, backgroundColor: "#e0a82e", borderRadius: 6, maxBarThickness: 26 }] },
                options: baseOpts()
            });
        };
        charts.donut = new Chart(document.getElementById("ordersDonut"), {
            type: "doughnut",
            data: { labels: ["Очаквани", "Изпълнени", "Отказани"],
                datasets: [{ data: [k.pending, k.fulfilled, k.cancelled], backgroundColor: ["#e0a82e", "#1f9d57", "#cbd2e0"], borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: "62%", plugins: { legend: { position: "bottom", labels: { padding: 16, font: { family: "Manrope", weight: "600" } } } } }
        });

        drawVisits(30); drawOrders(30);
        bindRange("visitRange", drawVisits);
        bindRange("orderRange", drawOrders);
    };

    function kpiCard(color, icon, val, label, sub) {
        return `<div class="kpi c-${color}"><div class="kpi-ic">${svg(icon)}</div>
            <div class="kpi-val">${val}</div><div class="kpi-label">${label}</div><div class="kpi-sub">${sub || ""}</div></div>`;
    }
    function bindRange(id, fn) {
        const box = document.getElementById(id);
        box.querySelectorAll("button").forEach(b => b.addEventListener("click", () => {
            box.querySelectorAll("button").forEach(x => x.classList.remove("active"));
            b.classList.add("active"); fn(Number(b.dataset.d));
        }));
    }
    function grad(hex) {
        const c = document.createElement("canvas").getContext("2d");
        const g = c.createLinearGradient(0, 0, 0, 300);
        g.addColorStop(0, hex + "55"); g.addColorStop(1, hex + "05"); return g;
    }
    function baseOpts() {
        return { responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { grid: { display: false }, ticks: { font: { family: "Manrope" }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
                      y: { beginAtZero: true, ticks: { precision: 0, font: { family: "Manrope" } }, grid: { color: "#eef1f7" } } } };
    }
    /* Изгражда серия от map (date=>num|obj) за последните N дни; за 365 групира по месеци */
    function series(map, days, field) {
        if (days >= 365) {
            const labels = [], values = [];
            for (let i = 11; i >= 0; i--) {
                const d = new Date(); d.setMonth(d.getMonth() - i);
                const ym = d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0");
                let sum = 0;
                Object.keys(map).forEach(key => { if (key.startsWith(ym)) sum += valOf(map[key], field); });
                labels.push(d.toLocaleDateString("bg-BG", { month: "short", year: "2-digit" }));
                values.push(sum);
            }
            return { labels, values };
        }
        const labels = [], values = [];
        for (let i = days - 1; i >= 0; i--) {
            const d = new Date(); d.setDate(d.getDate() - i);
            const key = d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0") + "-" + String(d.getDate()).padStart(2, "0");
            labels.push(d.toLocaleDateString("bg-BG", { day: "numeric", month: "short" }));
            values.push(valOf(map[key], field));
        }
        return { labels, values };
    }
    function valOf(entry, field) { if (entry == null) return 0; if (typeof entry === "number") return entry; return Number(entry[field] || 0); }

    /* =========================================================
       ПРОДУКТИ
       ========================================================= */
    let PRODUCTS = [], PRODUCTS_META = null;
    views.products = async (root) => {
        const [pd] = await Promise.all([api("products_list"), loadCats()]);
        PRODUCTS = pd.products;
        PRODUCTS_META = pd.meta || null;
        root.innerHTML = `
          <div class="toolbar">
            <div class="search"><span class="ni" data-i="search"></span><input id="pSearch" placeholder="Търси по име, категория…"></div>
            <select class="input" id="pCat" style="max-width:220px"><option value="">Всички категории</option>${CATS.map(c => `<option>${esc(c)}</option>`).join("")}</select>
            <button class="btn btn-primary" id="addProduct">${svg("plus", 18)} Добави продукт</button>
          </div>
          <div class="card"><div class="table-wrap"><table class="tbl">
            <thead><tr><th>Снимка</th><th>Име</th><th>Цена</th><th>Категория</th><th>Снимки</th><th>Статус</th><th style="text-align:right">Действия</th></tr></thead>
            <tbody id="pBody"></tbody></table></div><div class="empty" id="pEmpty" hidden>Няма продукти.</div></div>`;

        const render = () => {
            const q = (document.getElementById("pSearch").value || "").toLowerCase();
            const cat = document.getElementById("pCat").value;
            let list = PRODUCTS.filter(p => {
                if (cat && p.category !== cat) return false;
                if (q && !((p.name + " " + (p.category||"") + " " + (p.tags||[]).join(" ")).toLowerCase().includes(q))) return false;
                return true;
            });
            const body = document.getElementById("pBody");
            document.getElementById("pEmpty").hidden = list.length > 0;
            if (!list.length) {
                const emptyEl = document.getElementById("pEmpty");
                if (PRODUCTS_META && (PRODUCTS_META.size > 0 || PRODUCTS_META.root_size > 0)) {
                    emptyEl.innerHTML = `Няма продукти в админ панела, но <code>products.json</code> съществува на сървъра (${PRODUCTS_META.root_size || PRODUCTS_META.size} bytes).<br>
                    Проверете правата на файла (644) и <code>admin/config.php</code> — път: <code>${esc(PRODUCTS_META.path)}</code>`;
                } else if (PRODUCTS_META && !PRODUCTS_META.readable && PRODUCTS_META.exists) {
                    emptyEl.innerHTML = `PHP не може да прочете <code>products.json</code>. Задайте права <b>644</b> на файла в cPanel File Manager.`;
                } else {
                    emptyEl.innerHTML = "Няма продукти.";
                }
                emptyEl.hidden = false;
            }
            body.innerHTML = list.map(p => {
                const img = (p.images || []).find(s => !String(s).endsWith(".mp4")) || "";
                return `<tr>
                  <td><img class="tbl-img" src="${asset(img)}" alt="" loading="lazy" onerror="this.style.opacity=.3"></td>
                  <td><b>${esc(p.name)}</b><div class="tbl-id">${p.id}</div></td>
                  <td class="price-cell">${esc(p.price)} €</td>
                  <td>${esc(p.category || "—")}</td>
                  <td>${(p.images || []).filter(s => !String(s).endsWith(".mp4")).length} бр.</td>
                  <td>${p.available ? '<span class="badge ok">Наличен</span>' : '<span class="badge off">Изчерпан</span>'}</td>
                  <td><div class="cell-actions">
                    <button class="btn btn-ghost btn-sm" data-act="toggle" data-id="${p.id}">${p.available ? "Изчерпан" : "Върни"}</button>
                    <button class="btn btn-blue btn-sm" data-act="edit" data-id="${p.id}">Редактирай</button>
                    <button class="btn btn-ghost btn-sm" data-act="dup" data-id="${p.id}">${svg("copy",16)}</button>
                    <button class="btn btn-danger btn-sm" data-act="del" data-id="${p.id}">${svg("trash",16)}</button>
                  </div></td></tr>`;
            }).join("");
            hydrateIcons(body);
            body.querySelectorAll("[data-act]").forEach(b => b.addEventListener("click", () => productAction(b.dataset.act, Number(b.dataset.id))));
        };

        document.getElementById("pSearch").addEventListener("input", render);
        document.getElementById("pCat").addEventListener("change", render);
        document.getElementById("addProduct").addEventListener("click", () => { location.hash = "product/0"; });
        render();
        window.__renderProducts = render;
    };

    async function productAction(act, id) {
        const p = PRODUCTS.find(x => x.id === id);
        if (act === "edit") return (location.hash = "product/" + id);
        if (act === "toggle") { await api("product_toggle", { body: { id } }); p.available = !p.available; toast("Статусът е обновен."); window.__renderProducts(); }
        if (act === "dup") { const d = await api("product_duplicate", { body: { id } }); PRODUCTS.push(d.product); toast("Дублиран продукт."); window.__renderProducts(); }
        if (act === "del") {
            if (!confirm("Сигурни ли сте, че искате да изтриете „" + p.name + "“?")) return;
            await api("product_delete", { body: { id } });
            PRODUCTS = PRODUCTS.filter(x => x.id !== id); toast("Изтрит продукт."); window.__renderProducts();
        }
    }

    function productFolderFromName(name) {
        name = String(name || "").trim();
        if (!/^\d+$/.test(name)) return "";
        return name.length <= 2 ? name.padStart(2, "0") : name;
    }

    function productMediaSubdir(p, category) {
        const all = [...(p.images || []), p.video].filter(Boolean);
        for (const path of all) {
            const m = String(path).match(/^images\/(products|packages)\/(\d+)\//);
            if (m) return m[1] + "/" + m[2];
        }
        const folder = productFolderFromName(p.name);
        if (!folder) return "";
        const cat = category || p.category || "";
        return /опаков/i.test(cat) ? "packages/" + folder : "products/" + folder;
    }

    views.productEdit = async (root, id) => {
        await loadCats();
        if (!PRODUCTS.length) {
            const pd = await api("products_list");
            PRODUCTS = pd.products;
        }
        const existing = id ? PRODUCTS.find(x => x.id === id) : null;
        if (id && !existing) {
            root.innerHTML = `<div class="empty">Продуктът не е намерен. <button class="btn btn-ghost" id="pBack">Назад</button></div>`;
            document.getElementById("pBack").addEventListener("click", () => { location.hash = "products"; });
            return;
        }
        const p = existing || { id: 0, name: "", price: "", category: CATS[0] || "", tags: [], description: "", images: [], video: null, available: true };
        const tags = Array.isArray(p.tags) ? p.tags.join(", ") : (p.tags || "");
        let images = [...(p.images || [])].filter(s => !String(s).endsWith(".mp4"));
        let video = p.video || "";

        root.innerHTML = `
          <div class="edit-toolbar">
            <button type="button" class="btn btn-ghost" id="pBack">${svg("back", 18)} Назад към продуктите</button>
            <button type="button" class="btn btn-primary" id="pSaveTop">${svg("check", 18)} Запази</button>
          </div>
          <form id="pForm" class="edit-form">
            <div class="info-box">
              <strong>Как да добавите снимки и видео</strong>
              <ol>
                <li><b>Код на продукта</b> — номерът на бутилката, <b>само с цифри</b> (напр. <b>01</b>, <b>64</b>, <b>094</b>). Без букви и текст.</li>
                <li>Първо въведете кода горе, <b>после</b> качете снимки и видео — иначе качването няма да тръгне.</li>
                <li><b>Първата снимка</b> (маркирана „Основна“) се вижда в каталога. Подредете със стрелките ← →.</li>
                <li>Можете да добавите няколко снимки наведнъж. Видеото е по избор.</li>
                <li>Накрая натиснете <b>Запази продукта</b>.</li>
              </ol>
            </div>
            <div class="card"><div class="card-head"><h2>Основна информация</h2></div>
              <div class="card-body">
                <div class="form-grid">
                  <div class="field"><label>Код на продукта *</label><input class="input" name="name" value="${esc(p.name)}" required placeholder="напр. 01, 64 или 094"><span class="hint">Само цифри — номерът на бутилката</span></div>
                  <div class="field"><label>Цена (€) <span class="hint">само с точка — 12.50</span></label><input class="input" name="price" id="pPrice" type="text" inputmode="decimal" autocomplete="off" value="${esc(p.price)}" placeholder="12.50"></div>
                  <div class="field"><label>Категория</label><select class="input" name="category" id="pCategory">${CATS.map(c => `<option ${c === p.category ? "selected" : ""}>${esc(c)}</option>`).join("")}</select></div>
                  <div class="field field-availability"><label>Наличност</label><label class="switch"><input type="checkbox" name="available" ${p.available ? "checked" : ""}><span class="track"></span><span class="switch-label" id="availTxt">${p.available ? "Наличен" : "Изчерпан"}</span></label></div>
                  <div class="field full"><label>Тагове <span class="hint">(разделени със запетая)</span></label><input class="input" name="tags" value="${esc(tags)}" placeholder="Бутилки, Подаръци"></div>
                  <div class="field full"><label>Описание</label><textarea class="input" name="description" rows="10" placeholder="Размери, детайли, бележки…">${esc(p.description)}</textarea></div>
                </div>
              </div>
            </div>
            <div class="card section-gap"><div class="card-head"><h2>Снимки <span class="hint" id="imgCount"></span></h2></div>
              <div class="card-body">
                <p class="hint" style="margin-bottom:12px">Премахване и подреждане със стрелките под всяка снимка. Записват се на <span id="mediaStorageHint">сървъра</span>.</p>
                <div class="thumbs thumbs-lg" id="imgThumbs"></div>
                <div class="uploader" id="imgDrop" style="margin-top:14px">${svg("up")} <div>Добави снимки — клик или пусни файлове тук</div><input type="file" id="imgInput" accept="image/*" multiple hidden></div>
              </div>
            </div>
            <div class="card section-gap"><div class="card-head"><h2>Видео</h2></div>
              <div class="card-body">
                <p class="hint" style="margin-bottom:12px">По избор — показва се на страницата на продукта. Формати: mp4, webm, mov, m4v, avi, mkv (най-надеждно: mp4 H.264). Макс. 5 минути.</p>
                <div id="vidBox"></div>
                <div class="row-gap" style="margin-top:12px">
                  <div class="uploader uploader-inline" id="vidDrop">${svg("film")} <span id="vidLabel">Качи видео</span><input type="file" id="vidInput" accept="video/mp4,video/webm,video/quicktime,video/x-msvideo,video/x-matroska,.mp4,.webm,.mov,.m4v,.avi,.mkv" hidden></div>
                  <button type="button" class="btn btn-ghost btn-sm" id="vidRemove" ${video ? "" : "hidden"}>Премахни видеото</button>
                </div>
              </div>
            </div>
            <div class="edit-toolbar edit-toolbar-bottom">
              <button type="button" class="btn btn-ghost" id="pCancel">Отказ</button>
              <button type="submit" class="btn btn-primary">${svg("check", 18)} Запази продукта</button>
            </div>
          </form>`;

        const thumbsEl = document.getElementById("imgThumbs");
        const imgCount = document.getElementById("imgCount");
        const vidBox = document.getElementById("vidBox");
        const vidLabel = document.getElementById("vidLabel");
        const vidRemove = document.getElementById("vidRemove");
        const catEl = document.getElementById("pCategory");
        const getSubdir = () => productMediaSubdir({ ...p, name: document.querySelector('[name="name"]').value, category: catEl.value }, catEl.value);
        const requireSubdir = () => {
            const sub = getSubdir();
            const name = document.querySelector('[name="name"]').value.trim();
            if (!sub) {
                if (!/^\d+$/.test(name)) {
                    throw new Error("Първо въведете код на продукта — само цифри (напр. 01, 64 или 094).");
                }
                throw new Error("Неуспешно определяне на папка за снимки.");
            }
            return sub;
        };

        const renderVideo = () => {
            if (video) {
                const bust = videoPreviewUrl(video);
                const ext = String(video).split(".").pop().toLowerCase();
                const mime = { mp4: "video/mp4", m4v: "video/mp4", webm: "video/webm", mov: "video/quicktime", avi: "video/x-msvideo", mkv: "video/x-matroska" }[ext] || "video/mp4";
                vidBox.innerHTML = `<video class="vid-preview" controls preload="metadata" playsinline><source src="${bust}" type="${mime}"></video><p class="hint">${esc(video)}</p>`;
                vidLabel.textContent = "Смени видеото";
                vidRemove.hidden = false;
            } else {
                vidBox.innerHTML = `<p class="hint">Няма качено видео.</p>`;
                vidLabel.textContent = "Качи видео";
                vidRemove.hidden = true;
            }
        };

        const renderThumbs = () => {
            imgCount.textContent = images.length ? `(${images.length})` : "";
            thumbsEl.innerHTML = images.length ? images.map((src, i) => `
              <div class="thumb-item thumb-item-lg">
                <img src="${asset(src)}" alt="Снимка ${i + 1}" loading="lazy" onerror="this.style.opacity=.3">
                <div class="thumb-actions">
                  <button type="button" class="thumb-btn" data-act="left" data-i="${i}" title="Наляво" ${i === 0 ? "disabled" : ""}>${svg("left", 14)}</button>
                  <button type="button" class="thumb-btn" data-act="right" data-i="${i}" title="Надясно" ${i === images.length - 1 ? "disabled" : ""}>${svg("right", 14)}</button>
                  <button type="button" class="thumb-btn danger" data-act="del" data-i="${i}" title="Премахни">${svg("x", 14)}</button>
                </div>
                ${i === 0 ? '<span class="thumb-main">Основна</span>' : ""}
              </div>`).join("") : `<p class="hint">Няма снимки. Добавете от бутона по-долу.</p>`;
            thumbsEl.querySelectorAll(".thumb-btn").forEach(b => {
                b.addEventListener("click", () => {
                    const i = Number(b.dataset.i);
                    if (b.dataset.act === "del") { images.splice(i, 1); renderThumbs(); return; }
                    if (b.dataset.act === "left" && i > 0) { [images[i - 1], images[i]] = [images[i], images[i - 1]]; renderThumbs(); }
                    if (b.dataset.act === "right" && i < images.length - 1) { [images[i + 1], images[i]] = [images[i], images[i + 1]]; renderThumbs(); }
                });
            });
        };
        renderThumbs();
        renderVideo();

        api("settings_get").then(d => {
            const el = document.getElementById("mediaStorageHint");
            if (el && d.media_storage) el.textContent = d.media_storage;
        }).catch(() => {});

        const imgInput = document.getElementById("imgInput");
        const imgDrop = document.getElementById("imgDrop");
        imgDrop.addEventListener("click", () => imgInput.click());
        imgInput.addEventListener("change", async () => {
            if (!imgInput.files.length) return;
            try {
                images.push(...await uploadFiles(imgInput.files, "image", requireSubdir()));
                renderThumbs();
                toast("Снимките са добавени.");
            } catch (e) { toast(e.message, "err"); }
            imgInput.value = "";
        });
        dragDrop(imgDrop, async files => { images.push(...await uploadFiles(files, "image", requireSubdir())); renderThumbs(); });

        const vidInput = document.getElementById("vidInput");
        document.getElementById("vidDrop").addEventListener("click", () => vidInput.click());
        vidInput.addEventListener("change", async () => {
            if (!vidInput.files.length) return;
            try {
                const dur = await readVideoDuration(vidInput.files[0]);
                if (dur > MAX_VIDEO_SEC) {
                    throw new Error("Видеото е по-дълго от 5 минути (" + Math.ceil(dur / 60) + " мин).");
                }
                video = (await uploadFiles(vidInput.files, "video", requireSubdir()))[0];
                renderVideo();
                toast("Видеото е качено.");
            } catch (e) { toast(e.message, "err"); }
            vidInput.value = "";
        });
        vidRemove.addEventListener("click", () => { video = ""; renderVideo(); });

        document.querySelector('[name="available"]').addEventListener("change", e => {
            document.getElementById("availTxt").textContent = e.target.checked ? "Наличен" : "Изчерпан";
        });
        bindPriceInput(document.getElementById("pPrice"));

        const goBack = () => { location.hash = "products"; };
        document.getElementById("pBack").addEventListener("click", goBack);
        document.getElementById("pCancel").addEventListener("click", goBack);

        const saveProduct = async () => {
            const f = document.getElementById("pForm");
            const priceParsed = parsePriceInput(f.price.value);
            if (!priceParsed.ok) {
                toast(priceParsed.reason === "comma" ? PRICE_COMMA_MSG : "Невалидна цена — само цифри и точка, напр. 12.50.", "err");
                f.price.focus();
                return;
            }
            const body = {
                id: p.id || 0,
                name: f.name.value.trim(),
                price: priceParsed.value,
                category: f.category.value,
                tags: f.tags.value,
                description: f.description.value,
                available: f.available.checked,
                images,
                video: video || null
            };
            if (!body.name) { toast("Името е задължително.", "err"); return; }
            const d = await api("product_save", { body });
            if (p.id === 0 || d.created) PRODUCTS.push(d.product);
            else { const idx = PRODUCTS.findIndex(x => x.id === d.product.id); if (idx > -1) PRODUCTS[idx] = d.product; }
            toast("Продуктът е запазен.");
            location.hash = "products";
        };

        document.getElementById("pSaveTop").addEventListener("click", () => saveProduct().catch(e => toast(e.message, "err")));
        document.getElementById("pForm").addEventListener("submit", e => {
            e.preventDefault();
            saveProduct().catch(err => toast(err.message, "err"));
        });
    };

    async function uploadFiles(fileList, kind, subdir) {
        const fd = new FormData();
        [...fileList].forEach(f => fd.append("files[]", f));
        let q = "&kind=" + kind;
        if (subdir) q += "&subdir=" + encodeURIComponent(subdir);
        const d = await api("upload", { form: fd, query: q });
        return d.paths;
    }
    function dragDrop(el, onFiles) {
        ["dragenter", "dragover"].forEach(ev => el.addEventListener(ev, e => { e.preventDefault(); el.classList.add("drag"); }));
        ["dragleave", "drop"].forEach(ev => el.addEventListener(ev, e => { e.preventDefault(); el.classList.remove("drag"); }));
        el.addEventListener("drop", async e => { if (e.dataTransfer.files.length) { try { await onFiles(e.dataTransfer.files); toast("Качено."); } catch (err) { toast(err.message, "err"); } } });
    }

    /* =========================================================
       КАТЕГОРИИ
       ========================================================= */
    views.categories = async (root) => {
        await loadCats();
        root.innerHTML = `
          <div class="card" style="max-width:680px">
            <div class="card-head"><h2>Категории</h2></div>
            <div class="card-body">
              <div class="toolbar" style="margin-bottom:18px">
                <input class="input" id="newCat" placeholder="Име на нова категория" style="flex:1">
                <button class="btn btn-primary" id="addCat">${svg("plus",18)} Добави</button>
              </div>
              <div class="cat-rows" id="catRows"></div>
            </div>
          </div>`;
        const render = () => {
            document.getElementById("catRows").innerHTML = CATS.map(c => `
              <div class="cat-row">
                <span class="c-name">${esc(c)}</span>
                <span class="c-count">${CAT_COUNTS[c] || 0} продукта</span>
                <div class="c-actions">
                  <button class="btn btn-ghost btn-sm" data-act="rename" data-c="${esc(c)}">${svg("edit",16)}</button>
                  <button class="btn btn-danger btn-sm" data-act="del" data-c="${esc(c)}">${svg("trash",16)}</button>
                </div>
              </div>`).join("") || `<div class="empty">Няма категории.</div>`;
            hydrateIcons(document.getElementById("catRows"));
            document.querySelectorAll("#catRows [data-act]").forEach(b => b.addEventListener("click", () => catAction(b.dataset.act, b.dataset.c)));
        };
        document.getElementById("addCat").addEventListener("click", async () => {
            const name = document.getElementById("newCat").value.trim();
            if (!name) return;
            const d = await api("category_add", { body: { name } }); CATS = d.categories;
            document.getElementById("newCat").value = ""; toast("Добавена категория."); render();
        });
        render();
        async function catAction(act, name) {
            if (act === "del") {
                if (!confirm("Изтриване на категория „" + name + "“?")) return;
                const d = await api("category_delete", { body: { name } }); CATS = d.categories; toast("Изтрита."); render();
            }
            if (act === "rename") {
                const to = prompt("Ново име за „" + name + "“:", name);
                if (!to || to === name) return;
                const d = await api("category_rename", { body: { from: name, to } }); CATS = d.categories; toast("Преименувана."); render();
            }
        }
    };

    /* =========================================================
       ПОРЪЧКИ
       ========================================================= */
    const ST = { pending: "Очаквана", fulfilled: "Изпълнена", cancelled: "Отказана" };
    views.orders = async (root) => {
        const d = await api("orders_list");
        let orders = d.orders;
        root.innerHTML = `
          <div class="toolbar">
            <div class="range-tabs" id="ordFilter">
              <button data-s="all" class="active">Всички</button>
              <button data-s="pending">Очаквани</button>
              <button data-s="fulfilled">Изпълнени</button>
              <button data-s="cancelled">Отказани</button>
            </div>
            <span class="hint" style="margin-left:auto">Обновява се автоматично на всеки 8 сек.</span>
          </div>
          <div id="ordList"></div>`;
        let filter = "all";
        let highlightId = 0;
        const render = (flashNew, hiId) => {
            if (hiId) highlightId = hiId;
            const list = orders.filter(o => filter === "all" || (o.status || "pending") === filter);
            const wrap = document.getElementById("ordList");
            const openIds = wrap ? new Set([...wrap.querySelectorAll(".order-card.open")].map(c => c.dataset.id)) : new Set();
            if (!list.length) { wrap.innerHTML = `<div class="empty">Няма поръчки в тази категория.</div>`; return; }
            wrap.innerHTML = list.map(o => {
                const st = o.status || "pending";
                const isNew = flashNew && highlightId && Number(o.id) === Number(highlightId);
                return `<div class="order-card${isNew ? " is-new" : ""}" data-id="${o.id}">
                  <div class="order-top">
                    <span class="badge ${st}">${ST[st]}</span>
                    <div><div class="o-name">${esc(o.name)}</div><div class="o-meta">${esc(o.date || "")} · ${o.products.length} продукта</div></div>
                    <span class="o-total">${money(o.total)}</span>
                    <button class="btn btn-ghost btn-sm o-expand">${svg("eye",16)} Детайли</button>
                  </div>
                  <div class="order-detail">
                    <div class="order-products">${o.products.map(p => `<div class="op"><span>${esc(p.title)}</span><b>${money(p.price)}</b></div>`).join("")}</div>
                    <div class="order-info-grid">
                      <div><b>Телефон</b>${esc(o.phone) || "—"}</div>
                      <div><b>Имейл</b>${esc(o.email) || "—"}</div>
                      <div><b>Доставка</b>${esc(o.delivery) || "—"}</div>
                      <div><b>Град</b>${esc(o.city) || "—"}</div>
                      <div><b>Адрес</b>${esc(o.address) || "—"}</div>
                      <div><b>Коментар</b>${esc(o.comment) || "—"}</div>
                    </div>
                    <div class="row-gap" style="margin-top:14px">
                      <button class="btn btn-green btn-sm" data-st="fulfilled" data-id="${o.id}" title="Маркирай поръчката като изпълнена">${svg("check",16)} Изпълнена</button>
                      <button class="btn btn-ghost btn-sm tip-btn" data-st="pending" data-id="${o.id}" data-tip="Връща поръчката в списъка с нови поръчки. Продуктите остават заети, докато не откажете поръчката.">Върни в очакване</button>
                      <button class="btn btn-outline btn-sm" data-st="cancelled" data-id="${o.id}" title="Отказва поръчката и освобождава бутилките за други клиенти">Откажи</button>
                      <button class="btn btn-danger btn-sm" data-del="${o.id}" style="margin-left:auto" title="Премахва поръчката от списъка. При очакваща/отказана поръчка освобождава бутилките.">${svg("trash",16)} Изтрий</button>
                    </div>
                  </div>
                </div>`;
            }).join("");
            hydrateIcons(wrap);
            openIds.forEach(id => {
                const card = wrap.querySelector(`.order-card[data-id="${CSS.escape(id)}"]`);
                if (card) card.classList.add("open");
            });
            wrap.querySelectorAll(".o-expand").forEach(b => b.addEventListener("click", () => b.closest(".order-card").classList.toggle("open")));
            wrap.querySelectorAll("[data-st]").forEach(b => b.addEventListener("click", async () => {
                await api("order_status", { body: { id: Number(b.dataset.id), status: b.dataset.st } });
                const o = orders.find(x => x.id === Number(b.dataset.id)); o.status = b.dataset.st;
                toast(b.dataset.st === "cancelled" ? "Поръчката е отказана. Бутилките са свободни." : "Статусът е обновен.");
                render();
                updateOrderBadge(orders);
            }));
            wrap.querySelectorAll("[data-del]").forEach(b => b.addEventListener("click", async () => {
                if (!confirm("Изтриване на поръчката?")) return;
                await api("order_delete", { body: { id: Number(b.dataset.del) } });
                orders = orders.filter(x => x.id !== Number(b.dataset.del));
                toast("Поръчката е изтрита.");
                render();
                updateOrderBadge(orders);
            }));
            if (flashNew) setTimeout(() => wrap.querySelectorAll(".order-card.is-new").forEach(c => c.classList.remove("is-new")), 8000);
        };
        window.__ordersRender = render;
        document.querySelectorAll("#ordFilter button").forEach(b => b.addEventListener("click", () => {
            document.querySelectorAll("#ordFilter button").forEach(x => x.classList.remove("active"));
            b.classList.add("active"); filter = b.dataset.s; render();
        }));
        render(); updateOrderBadge(orders);
        window.__ordersRefresh = async (flash, hiId, orderData) => {
            if (Array.isArray(orderData)) orders = orderData;
            else {
                const fresh = await api("orders_list");
                orders = fresh.orders;
            }
            render(flash, hiId);
            updateOrderBadge(orders);
        };
    };
    function updateOrderBadge(orders) {
        const pending = orders.filter(o => (o.status || "pending") === "pending").length;
        const b = document.getElementById("ordBadge");
        b.hidden = pending === 0; b.textContent = pending;
        b.classList.toggle("pulse", pending > 0);
    }

    function contentPathGet(obj, path) {
        return String(path).split(".").reduce((o, k) => (o && o[k] !== undefined ? o[k] : ""), obj);
    }

    function contentPathSet(obj, path, val) {
        const parts = String(path).split(".");
        let cur = obj;
        for (let i = 0; i < parts.length - 1; i++) {
            if (!cur[parts[i]] || typeof cur[parts[i]] !== "object") cur[parts[i]] = {};
            cur = cur[parts[i]];
        }
        cur[parts[parts.length - 1]] = val;
    }

    function collectContentSection(root, sectionId) {
        const sec = (window.CONTENT_SECTIONS || []).find(s => s.id === sectionId);
        if (!sec) return {};
        const out = {};
        sec.fields.forEach(f => {
            const el = root.querySelector(`[data-content-path="${f.path}"]`);
            if (el) contentPathSet(out, f.path, el.value);
        });
        return out;
    }

    function buildContentField(f, content) {
        const val = contentPathGet(content, f.path);
        const rows = f.rows || 3;
        const hint = f.hint ? `<span class="hint">${esc(f.hint)}</span>` : "";
        if (f.type === "textarea") {
            return `<div class="field"><label>${esc(f.label)}</label><textarea class="input" data-content-path="${esc(f.path)}" rows="${rows}">${esc(val)}</textarea>${hint}</div>`;
        }
        return `<div class="field"><label>${esc(f.label)}</label><input class="input" data-content-path="${esc(f.path)}" value="${esc(val)}">${hint}</div>`;
    }

    /* =========================================================
       НАСТРОЙКИ
       ========================================================= */
    views.settings = async (root) => {
        const [d, cd, rd] = await Promise.all([api("settings_get"), api("content_get"), api("reservations_list")]);
        const s = d.settings;
        const content = cd.content || {};
        const resList = rd.reservations || [];
        const resHtml = resList.length
            ? `<ul class="res-list">${resList.map(r => `<li><b>№ ${esc(r.product_name)}</b> — още ~${r.minutes_left} мин.</li>`).join("")}</ul>`
            : `<p class="hint">Няма активни резервации в колички.</p>`;

        const textSections = (window.CONTENT_SECTIONS || []).map(sec => `
          <details class="cms-section card"${sec.id === "contacts" ? " open" : ""}>
            <summary class="card-head cms-summary"><h2>${esc(sec.title)}</h2>
              <button type="button" class="btn btn-primary btn-sm cms-save" data-section="${esc(sec.id)}">${svg("check",16)} Запази</button>
            </summary>
            <div class="card-body">
              ${sec.hint ? `<p class="hint" style="margin-bottom:14px">${esc(sec.hint)}</p>` : ""}
              ${sec.fields.map(f => buildContentField(f, content)).join("")}
            </div>
          </details>`).join("");

        root.innerHTML = `
          <p class="hint" style="margin-bottom:16px;line-height:1.55">
            Тук редактирате <strong>само текстовете</strong> на сайта — без HTML код. За правни страници заглавията на секции се пишат като <code>===Заглавие===</code> на отделен ред.
          </p>
          <div class="cms-sections">${textSections}</div>
          <div class="card section-gap"><div class="card-head"><h2>Технически настройки</h2><button class="btn btn-primary btn-sm" id="saveSet">${svg("check",16)} Запази</button></div>
            <div class="card-body">
              <div class="field"><label class="switch"><input type="checkbox" id="setEmail" ${s.email_notifications ? "checked" : ""}><span class="track"></span> Имейл известия за нови поръчки</label></div>
              <div class="hint" style="margin-top:14px; line-height:1.5">
                <strong>Известия на телефон:</strong> в <code>admin/config.php</code> попълнете
                <strong>Telegram</strong> (бот + chat id) или <strong>ntfy.sh</strong> topic.
              </div>
            </div>
          </div>
          <div class="card section-gap"><div class="card-head"><h2>Поддръжка</h2></div>
            <div class="card-body">
              <p class="hint" style="margin-bottom:14px">Изчистете кеша, ако промените не се виждат веднага в сайта.</p>
              <button class="btn btn-outline" id="clearCache">${svg("cog",18)} Изчисти кеша</button>
            </div>
          </div>
          <div class="card section-gap"><div class="card-head"><h2>Резервации в колички (30 мин)</h2>
            <button class="btn btn-danger btn-sm" id="clearReservations" ${resList.length ? "" : "disabled"}>Изчисти всички</button>
          </div>
            <div class="card-body">
              <p class="hint" style="margin-bottom:12px">Продуктите се блокират временно, когато някой ги добави в количка.</p>
              ${resHtml}
            </div>
          </div>`;

        root.querySelectorAll(".cms-save").forEach(btn => {
            btn.addEventListener("click", async (e) => {
                e.preventDefault();
                e.stopPropagation();
                const sectionId = btn.dataset.section;
                const payload = collectContentSection(root, sectionId);
                await api("content_save", { body: payload });
                toast("Текстовете са запазени.");
            });
        });

        root.querySelectorAll(".cms-summary").forEach(sum => {
            sum.addEventListener("click", (e) => {
                if (e.target.closest(".cms-save")) e.preventDefault();
            });
        });

        document.getElementById("saveSet").addEventListener("click", async () => {
            await api("settings_save", { body: {
                email_notifications: document.getElementById("setEmail").checked
            }});
            toast("Настройките са запазени.");
        });
        document.getElementById("clearCache").addEventListener("click", async () => { await api("clear_cache", { body: {} }); toast("Кешът е изчистен."); });
        document.getElementById("clearReservations")?.addEventListener("click", async () => {
            if (!confirm("Изчисти всички активни резервации? Продуктите ще станат отново свободни за поръчка.")) return;
            await api("reservations_clear", { body: {} });
            toast("Резервациите са изчистени.");
            views.settings(root);
        });
    };

    /* ---------------------- Сайдбар (мобилно) ---------------------- */
    const sidebar = document.getElementById("sidebar");
    const scrim = document.getElementById("sideScrim");
    function closeSidebar() { sidebar.classList.remove("open"); scrim.classList.remove("show"); }
    document.getElementById("menuBtn").addEventListener("click", () => { sidebar.classList.add("open"); scrim.classList.add("show"); });
    scrim.addEventListener("click", closeSidebar);

    /* ---------------------- Старт ---------------------- */
    document.getElementById("todayLabel").textContent = new Date().toLocaleDateString("bg-BG", { weekday: "long", day: "numeric", month: "long", year: "numeric" });
    hydrateIcons(document);
    let lastPollLatestId = 0;
    let lastPollPending = -1;
    let lastPollTotal = -1;
    async function pollOrdersLive() {
        try {
            const d = await api("orders_poll");
            const prevLatest = lastPollLatestId;
            const hasNew = d.latest_id > prevLatest;
            const onOrders = (location.hash.replace("#", "") || "dashboard").split("/")[0] === "orders";

            lastPollLatestId = Math.max(prevLatest, d.latest_id || 0);
            lastPollPending = d.pending ?? 0;
            lastPollTotal = d.total ?? 0;
            updateOrderBadge(d.orders || []);

            if (onOrders && typeof window.__ordersRefresh === "function") {
                await window.__ordersRefresh(hasNew, hasNew ? d.latest_id : 0, d.orders);
            } else if (hasNew) {
                toast("Нова поръчка!", "ok");
            }
        } catch (e) { /* ignore */ }
    }
    api("orders_poll").then(d => {
        lastPollLatestId = d.latest_id || 0;
        lastPollPending = d.pending ?? 0;
        lastPollTotal = d.total ?? 0;
        updateOrderBadge(d.orders || []);
    }).catch(() => {});
    setInterval(pollOrdersLive, 8000);
    if (!location.hash) location.hash = "dashboard";
    route();
})();
