/* Схема за редактиране на текстове — само plain text, без HTML */
window.CONTENT_SECTIONS = [
    {
        id: "contacts", title: "Контакти", hint: "Телефон, имейл и адрес — показват се във футъра и на страницата „Контакти“.",
        fields: [
            { path: "contacts.phone", label: "Телефон (показван)", type: "text" },
            { path: "contacts.phone_link", label: "Телефон (за обаждане, без интервали)", type: "text" },
            { path: "contacts.email", label: "Имейл", type: "text" },
            { path: "contacts.person", label: "Лице за контакт", type: "text" },
            { path: "contacts.address", label: "Адрес", type: "text" },
            { path: "contacts.hours", label: "Работно време", type: "text" }
        ]
    },
    {
        id: "site", title: "Общи текстове", hint: "Футър и банер за бисквитки.",
        fields: [
            { path: "site.footer_tagline", label: "Текст под логото във футъра", type: "textarea", rows: 3 },
            { path: "site.cookie_banner", label: "Текст на банера за бисквитки", type: "textarea", rows: 2 }
        ]
    },
    {
        id: "home", title: "Начална страница", hint: "Главната страница на сайта.",
        fields: [
            { path: "home.hero_eyebrow", label: "Горен етикет (hero)", type: "text" },
            { path: "home.hero_title_script", label: "Заглавие — ръкописна част", type: "text" },
            { path: "home.hero_title_main", label: "Заглавие — основна част", type: "text" },
            { path: "home.hero_lead", label: "Въведение под заглавието", type: "textarea", rows: 3 },
            { path: "home.hero_cta_products", label: "Бутон „Разгледай продуктите“", type: "text" },
            { path: "home.hero_cta_story", label: "Бутон „Историята на майстора“", type: "text" },
            { path: "home.stat_models", label: "Статистика: модела", type: "text" },
            { path: "home.stat_hand", label: "Статистика: ръчна изработка", type: "text" },
            { path: "home.stat_glue", label: "Статистика: лепила", type: "text" },
            { path: "home.stat_delivery", label: "Статистика: доставка (4-та)", type: "text" },
            { path: "home.trust_hand_b", label: "Лента доверие: 100%", type: "text" },
            { path: "home.trust_hand_s", label: "Лента доверие: ръчна", type: "text" },
            { path: "home.trust_glue_b", label: "Лента доверие: без лепила", type: "text" },
            { path: "home.trust_glue_s", label: "Лента доверие: сглобяване", type: "text" },
            { path: "home.trust_models_b", label: "Лента доверие: брой модели", type: "text" },
            { path: "home.trust_models_s", label: "Лента доверие: уникални", type: "text" },
            { path: "home.trust_ship_b", label: "Лента доверие: доставка", type: "text" },
            { path: "home.trust_ship_s", label: "Лента доверие: България/свят", type: "text" },
            { path: "home.story_eyebrow", label: "Секция история: етикет", type: "text" },
            { path: "home.story_title", label: "Секция история: заглавие", type: "text" },
            { path: "home.story_highlight", label: "Секция история: акцент (оранжево)", type: "text" },
            { path: "home.story_p1", label: "Секция история: абзац 1", type: "textarea", rows: 3 },
            { path: "home.story_p2", label: "Секция история: абзац 2", type: "textarea", rows: 3 },
            { path: "home.story_link", label: "Линк „Прочети цялата история“", type: "text" },
            { path: "home.story_sign", label: "Подпис", type: "text" },
            { path: "home.featured_eyebrow", label: "Избрани: етикет", type: "text" },
            { path: "home.featured_title", label: "Избрани: заглавие", type: "text" },
            { path: "home.featured_highlight", label: "Избрани: акцент", type: "text" },
            { path: "home.featured_sub", label: "Избрани: подзаглавие", type: "textarea", rows: 2 },
            { path: "home.featured_btn", label: "Избрани: бутон", type: "text" },
            { path: "home.process_eyebrow", label: "Процес: етикет", type: "text" },
            { path: "home.process_title", label: "Процес: заглавие", type: "text" },
            { path: "home.process_sub", label: "Процес: подзаглавие", type: "textarea", rows: 2 },
            { path: "home.process_steps", label: "Процес: 4 стъпки (Заглавие|Текст на нов ред)", type: "textarea", rows: 6, hint: "Всяка стъпка на нов ред. Формат: Заглавие|Описание" },
            { path: "home.cats_eyebrow", label: "Категории: етикет", type: "text" },
            { path: "home.cats_title", label: "Категории: заглавие", type: "text" },
            { path: "home.cats_highlight", label: "Категории: акцент", type: "text" },
            { path: "home.quote_text", label: "Цитат", type: "textarea", rows: 3 },
            { path: "home.quote_cite", label: "Цитат: автор", type: "text" },
            { path: "home.cta_eyebrow", label: "CTA: етикет", type: "text" },
            { path: "home.cta_title", label: "CTA: заглавие", type: "text" },
            { path: "home.cta_text", label: "CTA: текст", type: "textarea", rows: 3 },
            { path: "home.cta_btn_products", label: "CTA: бутон продукти", type: "text" },
            { path: "home.cta_btn_contact", label: "CTA: бутон контакти", type: "text" }
        ]
    },
    {
        id: "uvod", title: "Страница „Увод“", hint: "Историята на майстора — само текст, без HTML.",
        fields: [
            { path: "uvod.title_script", label: "Заглавие — ръкопис", type: "text" },
            { path: "uvod.title_main", label: "Заглавие — основна част", type: "text" },
            { path: "uvod.master_line", label: "Ред под снимката", type: "text" },
            { path: "uvod.greeting", label: "Поздрав", type: "text" },
            { path: "uvod.intro", label: "Въведение", type: "textarea", rows: 2 },
            { path: "uvod.occasions_label", label: "Етикет за поводи", type: "text" },
            { path: "uvod.occasions", label: "Поводи (един на ред)", type: "textarea", rows: 4 },
            { path: "uvod.body", label: "Основен текст (абзаци разделени с празен ред)", type: "textarea", rows: 14 },
            { path: "uvod.closing", label: "Заключителен ред", type: "textarea", rows: 2 }
        ]
    },
    {
        id: "products", title: "Страница „Продукти“", fields: [
            { path: "products.eyebrow", label: "Етикет", type: "text" },
            { path: "products.title_script", label: "Заглавие — ръкопис", type: "text" },
            { path: "products.title_main", label: "Заглавие — основна част", type: "text" },
            { path: "products.intro", label: "Въведение", type: "textarea", rows: 3 }
        ]
    },
    {
        id: "delivery", title: "Страница „Доставка“", fields: [
            { path: "delivery.eyebrow", label: "Етикет", type: "text" },
            { path: "delivery.title_script", label: "Заглавие", type: "text" },
            { path: "delivery.intro", label: "Въведение", type: "textarea", rows: 3 },
            { path: "delivery.flow", label: "4 стъпки (Заглавие|Текст)", type: "textarea", rows: 5, hint: "Поръчка|..., Опаковане|..." },
            { path: "delivery.bg_title", label: "България: заглавие", type: "text" },
            { path: "delivery.bg_chips", label: "България: етикети (един на ред)", type: "textarea", rows: 2 },
            { path: "delivery.bg_items", label: "България: точки (един на ред)", type: "textarea", rows: 4 },
            { path: "delivery.world_title", label: "Свят: заглавие", type: "text" },
            { path: "delivery.world_chips", label: "Свят: етикети", type: "textarea", rows: 2 },
            { path: "delivery.world_items", label: "Свят: точки", type: "textarea", rows: 4 },
            { path: "delivery.info_cards", label: "3 инфо карти (Заглавие|Текст)", type: "textarea", rows: 4 },
            { path: "delivery.cta_title", label: "CTA: заглавие", type: "text" },
            { path: "delivery.cta_text", label: "CTA: текст", type: "textarea", rows: 2 },
            { path: "delivery.cta_btn", label: "CTA: бутон", type: "text" }
        ]
    },
    {
        id: "contacts_page", title: "Страница „Контакти“", fields: [
            { path: "contacts_page.eyebrow", label: "Етикет", type: "text" },
            { path: "contacts_page.title_script", label: "Заглавие", type: "text" },
            { path: "contacts_page.intro", label: "Въведение", type: "textarea", rows: 3 },
            { path: "contacts_page.panel_title", label: "Панел: заглавие", type: "text" },
            { path: "contacts_page.panel_sub", label: "Панел: подзаглавие", type: "text" },
            { path: "contacts_page.cta_title", label: "CTA: заглавие", type: "text" },
            { path: "contacts_page.cta_text", label: "CTA: текст", type: "textarea", rows: 2 },
            { path: "contacts_page.cta_btn", label: "CTA: бутон", type: "text" }
        ]
    },
    {
        id: "cart", title: "Страница „Количка“", fields: [
            { path: "cart.eyebrow", label: "Етикет", type: "text" },
            { path: "cart.title_script", label: "Заглавие — ръкопис", type: "text" },
            { path: "cart.title_main", label: "Заглавие — основна част", type: "text" },
            { path: "cart.intro", label: "Въведение (резервация 30 мин)", type: "textarea", rows: 3 },
            { path: "cart.empty_title", label: "Празна количка: заглавие", type: "text" },
            { path: "cart.empty_sub", label: "Празна количка: текст", type: "text" },
            { path: "cart.empty_btn", label: "Празна количка: бутон", type: "text" }
        ]
    },
    {
        id: "order", title: "Страница „Поръчка“", fields: [
            { path: "order.eyebrow", label: "Етикет", type: "text" },
            { path: "order.title_script", label: "Заглавие — ръкопис", type: "text" },
            { path: "order.title_main", label: "Заглавие — основна част", type: "text" },
            { path: "order.summary_title", label: "Резюме: заглавие", type: "text" },
            { path: "order.form_title", label: "Форма: заглавие", type: "text" },
            { path: "order.confirm_btn", label: "Бутон потвърждение", type: "text" },
            { path: "order.terms_note", label: "Бележка преди „Общите условия“", type: "text" },
            { path: "order.thank_title", label: "Благодарност: заглавие", type: "text" },
            { path: "order.thank_text", label: "Благодарност: текст", type: "textarea", rows: 3 },
            { path: "order.thank_btn", label: "Благодарност: бутон", type: "text" }
        ]
    },
    {
        id: "legal", title: "Правни страници", hint: "За заглавия на секции използвайте ===Заглавие=== на отделен ред. Абзаците са разделени с празен ред.",
        fields: [
            { path: "legal.terms", label: "Общи условия", type: "textarea", rows: 16 },
            { path: "legal.privacy", label: "Поверителност", type: "textarea", rows: 12 },
            { path: "legal.cookies", label: "Бисквитки", type: "textarea", rows: 10 }
        ]
    },
    {
        id: "shop", title: "Магазин (общо)", fields: [
            { path: "shop.payment_info", label: "Информация за плащане", type: "textarea", rows: 2 },
            { path: "shop.reserved_other", label: "Етикет: резервирана от друг", type: "text" },
            { path: "shop.reserved_mine", label: "Етикет: резервирана за вас", type: "text" }
        ]
    }
];
