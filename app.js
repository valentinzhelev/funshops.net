/* =============================================================
   МОЯТ ЗАБАВЕН МАГАЗИН — shared app logic
   Навигация, футър, количка, известия, анимации, езици
   ============================================================= */
(function () {
    "use strict";

    /* ---------------------------------------------------------
       CDN (Bunny) или локални images/ — виж config.js
    --------------------------------------------------------- */
    const ASSET_BASE = typeof window.SITE_ASSET_BASE === "string" ? window.SITE_ASSET_BASE : "";

    function applyStaticAssets() {
        if (!ASSET_BASE) return;
        document.querySelectorAll('img[src^="images/"], video source[src^="images/"]').forEach(el => {
            const src = el.getAttribute("src");
            if (src && !/^https?:/i.test(src)) el.src = ASSET_BASE + src.replace(/^\//, "");
        });
    }

    // Сайтът е български по подразбиране (българска аудитория и съдържание).
    const LANG = "bg";
    const isAdmin = window.location.search.includes("admin=1");

    /* ---------------------- Помощни ---------------------- */
    function asset(p) {
        if (!p) return "";
        if (/^https?:/i.test(p)) return p;
        const base = typeof window.SITE_ASSET_BASE === "string" ? window.SITE_ASSET_BASE : ASSET_BASE;
        return base + String(p).replace(/^\//, "").replace(/\\\//g, "/");
    }
    /** Видео винаги от същия сървър — качва се локално, не през CDN. */
    function videoAsset(p) {
        if (!p) return "";
        if (/^https?:/i.test(p)) return p;
        return String(p).replace(/^\//, "").replace(/\\/g, "/");
    }
    function videoMime(path) {
        const ext = String(path || "").split(".").pop().toLowerCase();
        const map = {
            mp4: "video/mp4", m4v: "video/mp4", webm: "video/webm",
            mov: "video/quicktime", avi: "video/x-msvideo", mkv: "video/x-matroska"
        };
        return map[ext] || "video/mp4";
    }
    function t(bg, en) { return LANG === "bg" ? bg : en; }
    function money(n) { return Number(n).toFixed(2) + " €"; }
    function getCart() { try { return JSON.parse(localStorage.getItem("cart")) || []; } catch (e) { return []; } }
    function getList(k) { try { return JSON.parse(localStorage.getItem(k)) || []; } catch (e) { return []; } }
    function setJSON(k, v) { localStorage.setItem(k, JSON.stringify(v)); }

    const RES_TTL_MIN = 30;
    let reservationsCache = [];
    let reservationsPromise = null;

    function getSessionId() {
        let id = localStorage.getItem("shopSessionId");
        if (!id || id.length < 8) {
            id = "s" + Date.now().toString(36) + Math.random().toString(36).slice(2, 11);
            localStorage.setItem("shopSessionId", id);
        }
        return id;
    }

    async function fetchReservations(force) {
        if (reservationsPromise && !force) return reservationsPromise;
        reservationsPromise = fetch("reserve.php?session_id=" + encodeURIComponent(getSessionId()) + "&nocache=" + Date.now())
            .then(r => r.json())
            .then(d => {
                reservationsCache = (d.ok && d.reservations) ? d.reservations : [];
                return reservationsCache;
            })
            .catch(() => reservationsCache);
        return reservationsPromise;
    }

    function isReservedByOther(productId) {
        return reservationsCache.some(r => String(r.product_id) === String(productId) && !r.mine);
    }

    function isReservedByMe(productId) {
        return reservationsCache.some(r => String(r.product_id) === String(productId) && r.mine);
    }

    function isInCart(productId) {
        const id = Number(productId);
        return getCart().some(i => Number(i.id) === id);
    }

    function isProductBlocked(productId) {
        return isReservedByOther(productId) || getList("soldProducts").includes(productId);
    }

    function isCatalogHidden(productId) {
        return isInCart(productId) || isReservedByMe(productId);
    }

    async function reserveApi(body) {
        const res = await fetch("reserve.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(Object.assign({ session_id: getSessionId() }, body))
        });
        const text = await res.text();
        try {
            return text ? JSON.parse(text) : { ok: false, error: t("Празен отговор от сървъра.", "Empty server response.") };
        } catch (e) {
            return { ok: false, error: t("Грешка от сървъра при резервация.", "Server error during reservation.") };
        }
    }

    async function syncCartReservations() {
        const ids = getCart().map(i => i.id);
        const d = await reserveApi({ action: "sync", product_ids: ids });
        if (d.ok && d.reservations) reservationsCache = d.reservations;
        return d;
    }

    /* ---------------------- Иконки (SVG) ---------------------- */
    const I = {
        bottle: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2h4v3l1.2 2.4a4 4 0 0 1 .8 2.4V20a2 2 0 0 1-2 2H10a2 2 0 0 1-2-2V9.8a4 4 0 0 1 .8-2.4L10 5z"/><path d="M8.6 13h6.8"/></svg>',
        cart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h2.2l2.1 12.3a1.5 1.5 0 0 0 1.5 1.2h8.9a1.5 1.5 0 0 0 1.5-1.1L21 7H5.3"/></svg>',
        menu: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
        x: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>',
        check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
        trash: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>',
        arrow: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
        heart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-4.6-9.3-9A5 5 0 0 1 12 6a5 5 0 0 1 9.3 6c-2.3 4.4-9.3 9-9.3 9z"/></svg>',
        phone: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.4-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg>',
        mail: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>',
        pin: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="2.6"/></svg>',
        clock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
        truck: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M1 3h13v13H1zM14 8h4l3 3v5h-7"/><circle cx="6" cy="19" r="1.6"/><circle cx="17" cy="19" r="1.6"/></svg>',
        hand: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6 11V6a2 2 0 0 1 4 0v4M10 10V4a2 2 0 0 1 4 0v6M14 10V6a2 2 0 0 1 4 0v8a7 7 0 0 1-7 7h-1a7 7 0 0 1-6-3.5L2 14a2 2 0 0 1 3-2.6L6 12"/></svg>',
        shield: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v6c0 5 3.5 8.5 8 11 4.5-2.5 8-6 8-11V5z"/><path d="m9 12 2 2 4-4"/></svg>',
        globe: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18 14 14 0 0 1 0-18z"/></svg>',
        leaf: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 4 13c0-6 7-10 16-10 0 9-4 16-9 16z"/><path d="M4 21c2-5 6-8 10-9"/></svg>',
        search: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/></svg>'
    };

    const NAV = [
        { href: "index.html",    bg: "Начало",   en: "Home" },
        { href: "uvod.html",     bg: "Увод",     en: "About" },
        { href: "products.html", bg: "Продукти", en: "Products" },
        { href: "contacts.html", bg: "Контакти", en: "Contacts" },
        { href: "delivery.html", bg: "Доставка", en: "Delivery" }
    ];

    const page = (location.pathname.split("/").pop() || "index.html").toLowerCase();

    /* ---------------------- Навигация ---------------------- */
    function buildNav() {
        const links = NAV.map(n =>
            `<a href="${n.href}" class="${page === n.href ? "active" : ""}">${t(n.bg, n.en)}</a>`).join("");
        const mlinks = NAV.map(n =>
            `<a href="${n.href}" class="${page === n.href ? "active" : ""}">${t(n.bg, n.en)}</a>`).join("");

        const mainEl = document.querySelector("main");
        if (mainEl && !mainEl.id) mainEl.id = "main";

        const header = document.createElement("header");
        header.className = "nav";
        header.innerHTML = `
          <div class="container">
            <a href="index.html" class="brand" aria-label="Моят Забавен Магазин">
              <img class="brand-logo brand-logo--light" src="${asset("images/logo_full.png")}" alt="Моят Забавен Магазин">
              <img class="brand-logo brand-logo--ink" src="${asset("images/logo_full_ink.png")}" alt="" aria-hidden="true">
            </a>
            <nav class="nav-links">${links}</nav>
            <div class="nav-actions">
              <a href="cart.html" class="cart-link" id="cartLink" aria-label="${t("Количка","Cart")}">
                ${I.cart}<span class="cart-badge" id="cartBadge">0</span>
              </a>
              <button class="nav-toggle" id="navToggle" aria-label="Меню">${I.menu}</button>
            </div>
          </div>`;
        document.body.prepend(header);

        const skip = document.createElement("a");
        skip.className = "skip-link";
        skip.href = "#main";
        skip.textContent = t("Към съдържанието", "Skip to content");
        document.body.prepend(skip);

        const mm = document.createElement("div");
        mm.className = "mobile-menu";
        mm.id = "mobileMenu";
        mm.innerHTML = `<div class="mobile-panel">
            <button class="close-x" id="mmClose">${I.x}</button>
            ${mlinks}
            <a href="cart.html">${t("Количка","Cart")}</a>
          </div>`;
        document.body.appendChild(mm);

        const hasHero = !!document.querySelector(".hero");
        const onScroll = () => header.classList.toggle("scrolled", !hasHero || window.scrollY > 30);
        onScroll();
        window.addEventListener("scroll", onScroll, { passive: true });

        const toggle = document.getElementById("navToggle");
        const close = document.getElementById("mmClose");
        toggle.addEventListener("click", () => mm.classList.add("open"));
        close.addEventListener("click", () => mm.classList.remove("open"));
        mm.addEventListener("click", e => { if (e.target === mm) mm.classList.remove("open"); });
    }

    /* ---------------------- Футър ---------------------- */
    function buildFooter(contacts, siteContent) {
        const c = contacts || {};
        const site = siteContent || {};
        const phone = c.phone || "+359 899 518 271";
        const phoneLink = c.phone_link || "+359899518271";
        const email = c.email || "orders@funshops.net";
        const address = c.address || t("Стара Загора, България", "Stara Zagora, Bulgaria");
        const hours = c.hours || "09:00 – 18:00";
        const tagline = site.footer_tagline || t(
            "Ръчно изработени подаръчни бутилки с български фолклорен мотив — създадени с любов в Стара Загора, без лепила и компромиси.",
            "Handmade gift bottles with Bulgarian folk motifs — crafted with love in Stara Zagora, without glue or compromise."
        );
        const f = document.createElement("footer");
        f.className = "site-footer";
        f.innerHTML = `
          <div class="shevitsa"></div>
          <div class="container">
            <div class="footer-grid">
              <div class="footer-brand">
                <a href="index.html" class="footer-logo" aria-label="Моят Забавен Магазин">
                  <img src="${asset("images/logo_full.png")}" alt="Моят Забавен Магазин">
                </a>
                <p>${tagline}</p>
              </div>
              <div class="footer-col">
                <h4>${t("Магазин","Shop")}</h4>
                <a href="products.html">${t("Всички продукти","All products")}</a>
                <a href="uvod.html">${t("За майстора","About the maker")}</a>
                <a href="delivery.html">${t("Доставка","Delivery")}</a>
                <a href="cart.html">${t("Количка","Cart")}</a>
              </div>
              <div class="footer-col">
                <h4>${t("Контакти","Contacts")}</h4>
                <a href="tel:${phoneLink}" class="hl">${phone}</a>
                <a href="mailto:${email}">${email}</a>
                <p>${address}</p>
                <p>${t("Работно време","Working hours")}: <span class="hl">${hours}</span></p>
              </div>
            </div>
            <div class="footer-bottom">
              <span>© 2026 ${t("Моят Забавен Магазин. Всички права запазени.","My Fun Store. All rights reserved.")}</span>
              <div class="legal-links">
                <a href="privacy.html">${t("Поверителност","Privacy")}</a>
                <a href="cookies.html">${t("Бисквитки","Cookies")}</a>
                <a href="privacy-settings.html">${t("Настройки","Settings")}</a>
                <a href="terms.html">${t("Общи условия","Terms")}</a>
              </div>
            </div>
          </div>`;
        document.body.appendChild(f);
    }

    /* ---------------------- Известия (toast) ---------------------- */
    let toastWrap;
    function toast(msg, type) {
        if (!toastWrap) {
            toastWrap = document.createElement("div");
            toastWrap.className = "toast-wrap";
            document.body.appendChild(toastWrap);
        }
        const el = document.createElement("div");
        el.className = "toast " + (type || "");
        el.innerHTML = `<span class="ic">${type === "warn" ? I.bottle : I.check}</span><span>${msg}</span>`;
        toastWrap.appendChild(el);
        requestAnimationFrame(() => el.classList.add("show"));
        setTimeout(() => { el.classList.remove("show"); setTimeout(() => el.remove(), 400); }, 2600);
    }

    /* ---------------------- Количка ---------------------- */
    function cartCount() { return getCart().length; }
    function updateBadge(bump) {
        const b = document.getElementById("cartBadge");
        if (!b) return;
        const n = cartCount();
        b.textContent = n;
        b.classList.toggle("show", n > 0);
        if (bump && n > 0) { b.classList.remove("bump"); void b.offsetWidth; b.classList.add("bump"); }
        renderDrawer();
    }

    async function addToCart(product) {
        const cart = getCart();
        if (isProductBlocked(product.id)) {
            toast(t("Този продукт е резервиран от друг клиент.", "This product is reserved by another customer."), "warn");
            return false;
        }
        if (cart.some(i => i.id === product.id)) {
            toast(t("Вече е в количката.", "Already in your cart."), "warn");
            return false;
        }
        const hold = await reserveApi({ action: "hold", product_id: product.id });
        if (!hold.ok) {
            await fetchReservations(true);
            toast(hold.error || t("Неуспешна резервация.", "Reservation failed."), "warn");
            return false;
        }
        const firstImage = (product.images || []).find(s => !String(s).endsWith(".mp4")) || (product.images || [])[0];
        cart.push({ id: product.id, title: product.name, price: parseFloat(product.price), image: firstImage });
        setJSON("cart", cart);
        await fetchReservations(true);
        updateBadge(true);
        document.dispatchEvent(new CustomEvent("cart:changed"));
        document.dispatchEvent(new CustomEvent("reservations:changed"));
        toast(t("Добавено в количката! Резервацията е за " + RES_TTL_MIN + " мин.", "Added to cart! Reserved for " + RES_TTL_MIN + " min."), "ok");
        openDrawer();
        return true;
    }

    async function removeFromCart(id) {
        setJSON("cart", getCart().filter(i => i.id !== id));
        await reserveApi({ action: "release", product_id: id });
        await fetchReservations(true);
        updateBadge();
        document.dispatchEvent(new CustomEvent("cart:changed"));
    }

    /* ---------------------- Cart drawer ---------------------- */
    let drawer, scrim;
    function ensureDrawer() {
        if (drawer) return;
        scrim = document.createElement("div");
        scrim.className = "drawer-scrim";
        scrim.id = "drawerScrim";
        document.body.appendChild(scrim);

        drawer = document.createElement("aside");
        drawer.className = "drawer";
        drawer.id = "cartDrawer";
        drawer.innerHTML = `
          <div class="drawer-head">
            <h3>${t("Количка","Cart")}</h3>
            <button class="cart-link" id="drawerClose" style="width:40px;height:40px">${I.x}</button>
          </div>
          <div class="drawer-body" id="drawerBody"></div>
          <div class="drawer-foot" id="drawerFoot"></div>`;
        document.body.appendChild(drawer);
        scrim.addEventListener("click", closeDrawer);
        document.getElementById("drawerClose").addEventListener("click", closeDrawer);
    }
    function openDrawer() { ensureDrawer(); renderDrawer(); scrim.classList.add("open"); drawer.classList.add("open"); }
    function closeDrawer() { if (drawer) { scrim.classList.remove("open"); drawer.classList.remove("open"); } }
    function renderDrawer() {
        if (!drawer) return;
        const body = document.getElementById("drawerBody");
        const foot = document.getElementById("drawerFoot");
        const cart = getCart();
        if (!cart.length) {
            body.innerHTML = `<div class="drawer-empty">${I.cart}<p>${t("Количката е празна.","Your cart is empty.")}</p>
              <a href="products.html" class="btn btn-amber" style="margin-top:14px">${t("Към продуктите","Browse products")}</a></div>`;
            foot.innerHTML = "";
            return;
        }
        body.innerHTML = cart.map(i => `
          <div class="di">
            <img src="${asset(i.image)}" alt="${i.title}" loading="lazy">
            <div>
              <div class="di-name">${i.title}</div>
              <div class="di-price">${money(i.price)}</div>
            </div>
            <button class="di-remove" data-id="${i.id}" aria-label="Премахни">${I.trash}</button>
          </div>`).join("");
        const total = cart.reduce((s, i) => s + Number(i.price), 0);
        foot.innerHTML = `
          <div class="drawer-total"><span>${t("Общо","Total")}</span><b>${money(total)}</b></div>
          <a href="cart.html" class="btn btn-amber btn-block">${t("Към количката","View cart")} ${I.arrow}</a>`;
        body.querySelectorAll(".di-remove").forEach(btn =>
            btn.addEventListener("click", () => { removeFromCart(Number(btn.dataset.id)); renderDrawer(); }));
    }

    /* ---------------------- Reveal анимации ---------------------- */
    function initReveal() {
        const els = document.querySelectorAll(".reveal, .reveal-zoom");
        if (!("IntersectionObserver" in window)) { els.forEach(e => e.classList.add("in")); return; }
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add("in"); io.unobserve(e.target); } });
        }, { threshold: 0.12, rootMargin: "0px 0px -8% 0px" });
        els.forEach(e => io.observe(e));
    }
    // Наблюдава динамично добавени .reveal елементи
    function observeNew(scope) {
        const els = (scope || document).querySelectorAll(".reveal:not(.in), .reveal-zoom:not(.in)");
        if (!("IntersectionObserver" in window)) { els.forEach(e => e.classList.add("in")); return; }
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add("in"); io.unobserve(e.target); } });
        }, { threshold: 0.1 });
        els.forEach(e => io.observe(e));
    }

    /* ---------------------- Брояч (count up) ---------------------- */
    function initCounters() {
        const nums = document.querySelectorAll("[data-count]");
        if (!nums.length) return;
        const run = (el) => {
            const target = parseFloat(el.dataset.count);
            const suffix = el.dataset.suffix || "";
            const dur = 1400; const start = performance.now();
            const step = (now) => {
                const p = Math.min((now - start) / dur, 1);
                const eased = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.round(target * eased) + suffix;
                if (p < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        };
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) { run(e.target); io.unobserve(e.target); } });
        }, { threshold: 0.6 });
        nums.forEach(n => io.observe(n));
    }

    /* ---------------------- Cookie banner ---------------------- */
    function initCookies() {
        if (localStorage.getItem("cookieAccepted")) return;
        fetchContent().then(data => {
            const msg = (data.site && data.site.cookie_banner) || t(
                "Този сайт използва само основни бисквитки, за да работи коректно — например за количката.",
                "This site uses only essential cookies to function properly — for example, for the cart."
            );
            showCookieBanner(msg);
        }).catch(() => showCookieBanner(t(
            "Този сайт използва само основни бисквитки, за да работи коректно — например за количката.",
            "This site uses only essential cookies to function properly — for example, for the cart."
        )));
    }

    function showCookieBanner(msg) {
        if (localStorage.getItem("cookieAccepted") || document.querySelector(".cookie")) return;
        const c = document.createElement("div");
        c.className = "cookie";
        c.innerHTML = `
          <p>${escHtml(msg)}</p>
          <div class="row">
            <button class="btn btn-amber btn-sm" id="ckOk">${t("Приемам","Accept")}</button>
            <a href="cookies.html" class="btn btn-ghost btn-sm">${t("Научи повече","Learn more")}</a>
          </div>`;
        document.body.appendChild(c);
        requestAnimationFrame(() => setTimeout(() => c.classList.add("show"), 600));
        document.getElementById("ckOk").addEventListener("click", () => {
            localStorage.setItem("cookieAccepted", "true");
            c.classList.remove("show");
            setTimeout(() => c.remove(), 500);
        });
    }

    /* ---------------------- Админ режим (запазен) ---------------------- */
    function initAdmin() {
        if (!isAdmin) return;
        document.querySelectorAll("a[href]").forEach(a => {
            const href = a.getAttribute("href");
            if (!href || /^(https?:|mailto:|tel:|#)/.test(href) || href.includes("admin=1")) return;
            a.href = href + (href.includes("?") ? "&" : "?") + "admin=1";
        });
        const back = document.createElement("a");
        back.id = "adminBack";
        back.href = "admin/admin-panel.html";
        back.textContent = "← Назад към админ панела";
        document.body.appendChild(back);
    }

    /* ---------------------- Session cart cleanup (запазено) ---------------------- */
    function sessionCleanup() {
        if (!sessionStorage.getItem("sessionActive")) {
            localStorage.removeItem("cart");
            reserveApi({ action: "clear" }).catch(() => {});
        }
        sessionStorage.setItem("sessionActive", "true");
    }

    /* ---------------------- Зареждане на продукти ---------------------- */
    let productsPromise = null;
    function fetchProducts() {
        if (productsPromise) return productsPromise;
        productsPromise = fetch("products.php?nocache=" + Date.now())
            .then(r => r.ok ? r.json() : fetch("products.json?nocache=" + Date.now()).then(r2 => r2.json()))
            .then(data => { window.products = data; return data; })
            .catch(err => { console.error("Грешка при зареждане на продуктите:", err); return []; });
        return productsPromise;
    }
    function fetchCategories() {
        return fetch("categories.json?nocache=" + Date.now())
            .then(r => r.json())
            .catch(() => []);
    }
    let contentPromise = null;
    let contentDefaultsPromise = null;

    function deepMerge(a, b) {
        if (!a || typeof a !== "object") return b;
        if (!b || typeof b !== "object") return a;
        const out = Array.isArray(a) ? a.slice() : Object.assign({}, a);
        Object.keys(b).forEach(k => {
            if (b[k] && typeof b[k] === "object" && !Array.isArray(b[k]) && out[k] && typeof out[k] === "object" && !Array.isArray(out[k])) {
                out[k] = deepMerge(out[k], b[k]);
            } else if (b[k] !== undefined) {
                out[k] = b[k];
            }
        });
        return out;
    }

    function contentGet(obj, path) {
        return String(path || "").split(".").reduce((o, k) => (o && o[k] !== undefined ? o[k] : undefined), obj);
    }

    function escHtml(s) {
        return String(s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }

    function parsePipeLines(str) {
        return String(str || "").split("\n").map(l => l.trim()).filter(Boolean).map(line => {
            const i = line.indexOf("|");
            if (i < 0) return { title: line, text: "" };
            return { title: line.slice(0, i).trim(), text: line.slice(i + 1).trim() };
        });
    }

    function applyPipeSteps(container, str) {
        if (!container) return;
        const steps = parsePipeLines(str);
        container.querySelectorAll(".step, .flow-step").forEach((el, i) => {
            if (!steps[i]) return;
            const h = el.querySelector("h3");
            const p = el.querySelector("p");
            if (h) h.textContent = steps[i].title;
            if (p) p.textContent = steps[i].text;
        });
    }

    function applyLineList(ul, str) {
        if (!ul) return;
        const items = String(str || "").split("\n").map(l => l.trim()).filter(Boolean);
        ul.innerHTML = items.map(t => `<li>${escHtml(t)}</li>`).join("");
    }

    function applyChips(container, str) {
        if (!container) return;
        const items = String(str || "").split("\n").map(l => l.trim()).filter(Boolean);
        container.innerHTML = items.map((t, i) => `<span class="chip${i ? "" : ""}">${escHtml(t)}</span>`).join("");
    }

    function applyInfoCards(container, str) {
        if (!container) return;
        const cards = parsePipeLines(str);
        container.querySelectorAll(".info-card").forEach((el, i) => {
            if (!cards[i]) return;
            const h = el.querySelector("h3");
            const p = el.querySelector("p");
            if (h) h.textContent = cards[i].title;
            if (p) p.textContent = cards[i].text;
        });
    }

    function renderLegalPlain(text) {
        const lines = String(text || "").split("\n");
        let html = "";
        let buf = [];
        const flush = () => {
            if (buf.length) html += `<p>${escHtml(buf.join(" "))}</p>`;
            buf = [];
        };
        lines.forEach(line => {
            const m = line.match(/^===\s*(.+?)\s*===$/);
            if (m) { flush(); html += `<h3>${escHtml(m[1])}</h3>`; return; }
            if (!line.trim()) { flush(); return; }
            buf.push(line.trim());
        });
        flush();
        return html;
    }

    function renderUvodBody(container, u) {
        if (!container || !u) return;
        const chips = String(u.occasions || "").split("\n").map(l => l.trim()).filter(Boolean);
        const chipColors = ["red", "green", "amber", "red"];
        const bodyParas = String(u.body || "").split(/\n\s*\n/).map(p => p.trim()).filter(Boolean);
        let html = "";
        if (u.greeting) html += `<p class="lead-line reveal"><strong>${escHtml(u.greeting)}</strong></p>`;
        if (u.intro) html += `<p class="reveal">${escHtml(u.intro)}</p>`;
        if (u.occasions_label) html += `<p class="reveal">${escHtml(u.occasions_label)}</p>`;
        if (chips.length) {
            html += `<div class="occasions">${chips.map((c, i) =>
                `<span class="chip ${chipColors[i % chipColors.length]} reveal${i ? " d" + i : ""}">${escHtml(c)}</span>`
            ).join("")}</div>`;
        }
        html += `<div class="divider" style="margin:30px 0"><span>✦</span></div>`;
        bodyParas.forEach(p => { html += `<p class="reveal">${escHtml(p)}</p>`; });
        if (u.closing) html += `<p class="lead-line reveal" style="margin-top:26px">${escHtml(u.closing)}</p>`;
        html += `<div style="text-align:center;margin-top:34px" class="reveal">
            <a href="products.html" class="btn btn-amber">Разгледай продуктите</a>
            <a href="contacts.html" class="btn btn-ghost">Свържи се с мен</a>
        </div>`;
        container.innerHTML = html;
        observeNew(container);
    }

    function applyCmsContent(data) {
        if (!data) return;
        document.querySelectorAll("[data-cms]").forEach(el => {
            const val = contentGet(data, el.getAttribute("data-cms"));
            if (val == null || val === "") return;
            if (el.tagName === "INPUT" || el.tagName === "TEXTAREA") el.value = val;
            else el.textContent = val;
        });
        document.querySelectorAll("[data-cms-html]").forEach(el => {
            const val = contentGet(data, el.getAttribute("data-cms-html"));
            if (val == null || val === "") return;
            el.innerHTML = renderLegalPlain(val);
            observeNew(el);
        });
        const flowEl = document.querySelector("[data-cms-flow]");
        if (flowEl) applyPipeSteps(flowEl, contentGet(data, flowEl.getAttribute("data-cms-flow")));
        const stepsEl = document.querySelector("[data-cms-steps]");
        if (stepsEl) applyPipeSteps(stepsEl, contentGet(data, stepsEl.getAttribute("data-cms-steps")));
        const bgList = document.querySelector("[data-cms-list='delivery.bg_items']");
        if (bgList) applyLineList(bgList, contentGet(data, "delivery.bg_items"));
        const worldList = document.querySelector("[data-cms-list='delivery.world_items']");
        if (worldList) applyLineList(worldList, contentGet(data, "delivery.world_items"));
        const bgChips = document.querySelector("[data-cms-chips='delivery.bg_chips']");
        if (bgChips) applyChips(bgChips, contentGet(data, "delivery.bg_chips"));
        const worldChips = document.querySelector("[data-cms-chips='delivery.world_chips']");
        if (worldChips) applyChips(worldChips, contentGet(data, "delivery.world_chips"));
        const infoCards = document.querySelector("[data-cms-info-cards]");
        if (infoCards) applyInfoCards(infoCards, contentGet(data, infoCards.getAttribute("data-cms-info-cards")));
        const uvodEl = document.getElementById("uvodContent");
        if (uvodEl && data.uvod) renderUvodBody(uvodEl, data.uvod);
    }

    function fetchContentDefaults() {
        if (!contentDefaultsPromise) {
            contentDefaultsPromise = fetch("content.defaults.json?nocache=" + Date.now())
                .then(r => r.json())
                .catch(() => ({}));
        }
        return contentDefaultsPromise;
    }

    function fetchContent(force) {
        if (contentPromise && !force) return contentPromise;
        contentPromise = Promise.all([
            fetch("content.json?nocache=" + Date.now()).then(r => r.json()).catch(() => ({})),
            fetchContentDefaults()
        ]).then(([saved, defaults]) => deepMerge(defaults, saved));
        return contentPromise;
    }

    function initCms() {
        if (!document.querySelector("[data-cms], [data-cms-html], [data-cms-flow], [data-cms-steps], #uvodContent")) return;
        fetchContent().then(data => {
            applyCmsContent(data);
            document.dispatchEvent(new CustomEvent("cms:ready", { detail: data }));
        });
    }

    /* ---------------------- Записване на посещение (запазено) ---------------------- */
    function saveVisit() {
        if (isAdmin) return;
        fetch("save_visit.php?nocache=" + Date.now()).catch(() => {});
    }

    /* ---------------------- Публичен API ---------------------- */
    window.Shop = {
        asset, videoAsset, videoMime, t, money, LANG, I, RES_TTL_MIN,
        getCart, getList, getSessionId,
        fetchReservations, isReservedByOther, isReservedByMe, isProductBlocked, isInCart, isCatalogHidden,
        addToCart, removeFromCart, updateBadge, syncCartReservations,
        openDrawer, closeDrawer,
        toast, observeNew,
        fetchProducts, fetchCategories, fetchContent, fetchContentDefaults,
        contentGet, applyCmsContent, renderLegalPlain, renderUvodBody, escHtml,
        productHref: (id) => "product.html?id=" + id,
        productTitle: (name) => /^\d+$/.test(String(name).trim()) ? "№ " + name : String(name)
    };

    /* ---------------------- Favicon ---------------------- */
    function setFavicon() {
        if (document.querySelector('link[rel="icon"]')) return;
        const l = document.createElement("link");
        l.rel = "icon"; l.type = "image/png"; l.href = asset("images/logo_bottle.png");
        document.head.appendChild(l);
    }

    /* ---------------------- Init ---------------------- */
    document.addEventListener("DOMContentLoaded", () => {
        applyStaticAssets();
        sessionCleanup();
        setFavicon();
        buildNav();
        applyStaticAssets();
        if (!document.body.hasAttribute("data-no-footer")) {
            fetchContent().then(data => { buildFooter(data.contacts, data.site); applyStaticAssets(); });
        }
        updateBadge();
        initCms();
        fetchReservations().then(() => syncCartReservations()).catch(() => {});
        setInterval(() => fetchReservations(true).then(() => document.dispatchEvent(new CustomEvent("reservations:changed"))), 45000);
        initReveal();
        initCounters();
        initCookies();
        initAdmin();
        saveVisit();
        document.addEventListener("cart:changed", () => updateBadge());
    });
})();
