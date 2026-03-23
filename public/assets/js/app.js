/* Rafal Salamon – Terminal Portfolio */

const i18n = {
  pl: {
    nav_home: "Start",
    nav_about: "O mnie",
    nav_projects: "Moje projekty",
    nav_tools: "Moje narzędzia",
    nav_blog: "Blog",
    nav_contact: "Kontakt",
    chip_lang: "Język",
    chip_theme: "Motyw",
    chip_color: "Kolor",
    chip_prefs: "Preferencje",
    prefs_lang_hint: "Przełącza język strony",
    prefs_theme_hint: "Przełącza motyw jasny/ciemny",
    prefs_color_hint: "Zmienia kolor akcentu interfejsu",
    home_title: "Senior PHP Developer\nFullstack Developer",
    home_tip: "skrót do języka i motywu",
    home_links_about: "O mnie",
    home_links_projects: "Projekty",
    home_links_tools: "Narzędzia",
    home_links_contact: "Kontakt",
    home_links_github: "GitHub",
    home_links_linkedin: "LinkedIn",
    home_links_mail: "E‑mail",
    home_recent: "Szybki skrót",

    term_lines: [
      {t:"info", v:"$ whoami"},
      {t:"out",  v:"Rafał Salamon"},
      {t:"info", v:"$ cat profile.txt"},
      {t:"out",  v:"Senior PHP Developer • Fullstack • Software Engineer"},
      {t:"out",  v:"Buduję i rozwijam aplikacje webowe, dbając o jakość, refactoring i komunikację z biznesem."},
      {t:"info", v:"$ ls experience/"},
      {t:"out",  v:"Rubix (2022–now)  StepStone (2008–2022)  SBG (2007–2008)"},
      {t:"info", v:"$ open projects"},
      {t:"out",  v:"➡ Przejdź do /projects i zobacz publiczne repozytoria + projekty komercyjne (opisowe)."}
    ],

    about_h2: "O mnie",
    about_p1: "Jestem programistą z wieloletnim doświadczeniem w tworzeniu i utrzymaniu aplikacji webowych. Najczęściej działam po stronie backendu (PHP), ale dobrze czuję się także w obszarze frontendu i integracji.",
    about_p2: "W pracy stawiam na analizę biznesową i techniczną, czytelny kod, refactoring, testowalność oraz sprawną komunikację między zespołem a interesariuszami.",
    about_special: "Obszary specjalizacji",
    about_skills: "Technologie (wybrane)",
    about_exp: "Doświadczenie",
    about_edu: "Wykształcenie",
    about_train: "Szkolenia / certyfikaty",
    about_lang: "Języki",
    about_interests: "Zainteresowania",

    projects_h2: "Moje projekty",
    projects_public: "Publiczne (GitHub)",
    projects_commercial: "Komercyjne (opisowe)",
    projects_note: "Część projektów realizowałem w środowisku komercyjnym — z oczywistych powodów bez linków do kodu. Poniżej podaję zakres i mój wkład.",

    tools_h2: "Moje narzędzia",
    tools_p: "Stos technologiczny, z którym pracuję na co dzień — od backendu, przez CI/CD, po organizację pracy.",

    contact_h2: "Kontakt",
    contact_p: "Chcesz porozmawiać o współpracy lub projekcie? Napisz, chętnie omówię szczegóły i możliwe kierunki działania.",
    contact_name: "Imię i nazwisko",
    contact_email: "E‑mail",
    contact_msg: "Wiadomość",
    contact_send: "Utwórz e‑mail",
    contact_copy: "Kopiuj adres",
    contact_copy_hint: "Kopiuj adres e‑mail",
    contact_copied: "Skopiowano!",
    privacy_title: "Prywatność",
    privacy_text: "Ta strona używa cookies i podobnych technologii do działania serwisu oraz analityki.",
    privacy_accept: "Akceptuję",
    privacy_decline: "Odrzuć",
    back_to_top: "Przenieś do Góry",
    preview_iteration_details: "Opis iteracji",
    preview_show_details: "Pokaż opis",
    preview_hide_details: "Ukryj opis",
    preview_enter_fullscreen: "Pełny ekran",
    preview_exit_fullscreen: "Zamknij pełny ekran",
    blog_read_article: "Czytaj artykuł",
    blog_no_articles: "Brak opublikowanych artykułów.",
    blog_create_first_article: "Utwórz pierwszy artykuł",
    blog_feed_title: "Blog o programowaniu, technologii i praktyce tworzenia produktów",
    blog_feed_lede: "Znajdziesz tutaj artykuły o PHP, web developmencie, architekturze aplikacji, jakości kodu i codziennej pracy nad nowoczesnymi produktami cyfrowymi.",
    blog_reader_view: "Widok czytelnika",
    blog_articles_ready: "Artykuły gotowe dla czytelników",
    blog_articles_ready_lede: "Opublikowane treści są zebrane w jednym głównym panelu pod topbarem, a ta sekcja zachowuje dodatkowy kontekst i kontrolki.",
    blog_published_articles_count: "Opublikowane artykuły w bieżącym widoku",
    blog_configured_languages: "Skonfigurowane warianty językowe",
    blog_filters: "Filtry",
    blog_all_languages: "Wszystkie języki",
    blog_create_article: "Utwórz artykuł",
    blog_manage_articles: "Zarządzaj artykułami",
    blog_article_body: "Treść artykułu",
    blog_article_label_suffix: "artykuł",
    blog_article_fallback_excerpt: "Strona opublikowanej treści wygenerowana z widoku bloga Twig.",
    blog_back_to_articles: "Wróć do artykułów",
    blog_edit_in_admin: "Edytuj w panelu admina",
    blog_metadata: "Metadane",
    blog_article_language: "Język publikacji",
    blog_publication_status: "Bieżący status publikacji",
    blog_public_date: "Główna data publiczna",
    blog_reader_slug: "Slug widoczny dla czytelnika",
    blog_pagination_previous: "Poprzednia",
    blog_pagination_next: "Następna",
    admin_shortcut_new_article: "Utwórz nowy artykuł",
    admin_shortcut_dashboard: "Panel główny",
    admin_shortcut_articles: "Lista artykułów",
    admin_shortcut_settings_title: "Szybkie ustawienia",
    admin_shortcut_remember_device: "Zapamiętaj urządzenie",
    admin_shortcut_forget_device: "Nie zapamiętuj urządzenia",
    admin_shortcut_blog_settings: "Ustawienia bloga",
    admin_shortcut_queue_status: "Stan kolejek",
    admin_shortcut_exports: "Eksporty",
    admin_shortcut_login_device: "Zaloguj się",
    admin_shortcut_logout: "Wyloguj",
    admin_dashboard_title: "Centrum operacji treści",
    admin_dashboard_lede: "Panel administracyjny dopasowany do układu aplikacji, gotowy na workflow redakcyjny, moderację i publikację.",
    admin_blog_settings_title: "Ustawienia bloga",
    admin_blog_settings_lede: "Zarządzaj danymi, które wpływają na nazwę bloga i metadane strony głównej widoczne w wyszukiwarkach oraz po udostępnieniu linku.",
    admin_queue_status_title: "Stan kolejek",
    admin_queue_status_lede: "Lista pokazuje elementy gotowe do przetworzenia przez zadanie w tle. Widoczne są tylko wpisy oczekujące w kolejce eksportu.",
    admin_queue_table_id: "ID",
    admin_queue_table_type: "Typ kolejki",
    admin_queue_table_created: "Dodano",
    admin_queue_type_article_export: "Eksport artykułu",
    admin_queue_no_items: "Brak elementów oczekujących na przetworzenie.",
    admin_queue_delete: "Usuń z kolejki",
    admin_queue_clear: "Wyczyść kolejkę",
    admin_queue_clear_popup_title: "Wyczyścić kolejkę?",
    admin_queue_clear_popup_text: "Ta operacja usunie wszystkie oczekujące elementy kolejki. Upewnij się, że zadanie w tle nie będzie ich już potrzebowało.",
    admin_queue_clear_popup_cancel: "Przerwij",
    admin_queue_clear_popup_confirm: "Wyczyść kolejkę",
    admin_exports_title: "Eksporty",
    admin_exports_lede: "Lista pokazuje gotowe paczki eksportu artykułów zapisane przez zadanie działające w tle. Każdy wpis zawiera plik gotowy do ponownego importu.",
    admin_exports_table_id: "ID",
    admin_exports_table_type: "Typ",
    admin_exports_table_status: "Status",
    admin_exports_table_articles: "Artykuły",
    admin_exports_table_file: "Plik",
    admin_exports_table_created: "Utworzono",
    admin_exports_type_articles: "Eksport artykułów",
    admin_exports_status_new: "Nowy",
    admin_exports_status_downloaded: "Pobrany",
    admin_exports_download: "Pobierz plik eksportu",
    admin_exports_no_items: "Brak gotowych eksportów.",
    admin_exports_clear: "Usuń wszystkie eksporty",
    admin_exports_clear_popup_title: "Usunąć wszystkie eksporty?",
    admin_exports_clear_popup_text: "Ta operacja usunie wszystkie rekordy eksportów oraz powiązane pliki z dysku.",
    admin_exports_clear_popup_cancel: "Przerwij",
    admin_exports_clear_popup_confirm: "Usuń wszystkie eksporty",
    admin_exports_delete_popup_title: "Usunąć eksport?",
    admin_exports_delete_popup_text: "Ta operacja usunie rekord eksportu i powiązany plik z dysku.",
    admin_exports_delete_popup_cancel: "Przerwij",
    admin_exports_delete_popup_confirm: "Usuń eksport",
    admin_browse_articles: "Przeglądaj artykuły",
    admin_edit_article: "Edytuj artykuł",
    admin_quick_actions: "Szybkie akcje",
    admin_quick_new_article: "Nowy artykuł",
    admin_quick_new_article_desc: "Rozpocznij nowy szkic z automatycznie generowanym slugiem.",
    admin_quick_manage_articles: "Zarządzaj artykułami",
    admin_quick_manage_articles_desc: "Przeglądaj szkice, wpisy opublikowane i aktualizuj treści.",
    admin_quick_open_blog: "Otwórz publiczny blog",
    admin_quick_open_blog_desc: "Wróć do widoku, który widzą już czytelnicy.",
    admin_article_index_title: "Zarządzanie artykułami",
    admin_article_index_lede: "Wszystkie artykuły w jednym miejscu, niezależnie od statusu. Tabela poniżej utrzymuje widok administracyjny w zwartej formie.",
    admin_article_bulk_export: "Eksportuj zaznaczone",
    admin_article_select: "Zaznacz artykuł",
    admin_article_select_all: "Zaznacz wszystkie artykuły",
    admin_table_title: "Tytuł",
    admin_table_language: "Język",
    admin_table_status: "Status",
    admin_table_slug: "Slug",
    admin_table_updated: "Aktualizacja",
    admin_table_action: "Akcja",
    admin_close_alert: "Zamknij alert",
    admin_table_edit: "Edytuj",
    admin_table_export: "Eksport",
    admin_table_publish: "Opublikuj",
    admin_table_archive: "Archiwizuj",
    admin_table_delete: "Usuń",
    admin_delete_popup_title: "Usunąć artykuł?",
    admin_delete_popup_text: "Ta operacja jest nieodwracalna. Artykuł zostanie trwale usunięty.",
    admin_delete_popup_cancel: "Przerwij",
    admin_delete_popup_confirm: "Usuń",
    admin_table_no_articles: "Nie znaleziono artykułów.",
    admin_new_article_title: "Utwórz artykuł",
    admin_new_article_lede: "Nowe wpisy domyślnie zaczynają jako szkice i przed zapisaniem dostają unikalny slug.",
    admin_edit_article_title: "Edytuj artykuł",
    admin_edit_article_lede: "Aktualizuj treść, metadane i stan publikacji, zachowując istniejący slug.",
    admin_permanent_slug: "Stały slug",
    admin_current_workflow_status: "Bieżący status workflow",
    admin_last_update: "Data ostatniej aktualizacji",
    form_title: "Tytuł",
    form_language: "Język",
    form_excerpt: "Krótki opis",
    form_headline_image_enabled: "Włącz grafikę nagłówkową",
    form_headline_image: "Grafika nagłówkowa",
    form_content: "Treść",
    form_status: "Status",
    form_publish_date: "Data publikacji",
    form_placeholder_title: "Wpisz tytuł artykułu",
    form_placeholder_excerpt: "Wpisz krótki opis do list i podglądów",
    form_placeholder_headline_image: "Wklej URL grafiki lub ścieżkę /assets/...",
    form_hint_headline_image_enabled: "Gdy pole grafiki pozostanie puste, zostanie użyty domyślny obrazek systemowy.",
    form_hint_headline_image: "Podaj pełny URL albo ścieżkę zaczynającą się od /, np. /assets/img/article-cover.jpg. Puste pole przy włączonym przełączniku użyje grafiki domyślnej.",
    form_placeholder_content: "Wpisz pełną treść artykułu",
    form_submit_create: "Utwórz artykuł",
    form_submit_update: "Zapisz zmiany",
    form_submit_save: "Zapisz artykuł",
    form_cancel: "Anuluj",
    login_eyebrow: "Dostęp administratora",
    login_title: "Zaloguj się, aby zarządzać treścią",
    login_lede: "Ten ekran logowania korzysta z tego samego języka wizualnego co reszta aplikacji.",
    login_email: "Email",
    login_password: "Hasło",
    login_submit: "Zaloguj się",
    login_back_to_blog: "Wróć do bloga",
    article_language_pl: "Polski (PL)",
    article_language_en: "Angielski (EN)",
    article_status_draft: "Szkic",
    article_status_review: "W recenzji",
    article_status_published: "Opublikowany",
    article_status_archived: "Zarchiwizowany",
    validation_article_title_required: "Tytuł artykułu jest wymagany.",
    validation_article_title_too_long: "Tytuł artykułu może mieć maksymalnie 255 znaków.",
    validation_article_slug_too_long: "Slug artykułu może mieć maksymalnie 255 znaków.",
    validation_article_excerpt_too_long: "Krótki opis może mieć maksymalnie 320 znaków.",
    validation_article_headline_image_too_long: "Grafika nagłówkowa może mieć maksymalnie 500 znaków.",
    validation_article_headline_image_invalid: "Podaj pełny URL obrazu albo ścieżkę zaczynającą się od /.",
    validation_article_content_required: "Treść artykułu jest wymagana.",
    validation_article_published_at_invalid: "Podaj prawidłową datę publikacji.",

    footer: "Tworzę rzeczy, które są czytelne, solidne i praktyczne."
  },
  en: {
    nav_home: "Home",
    nav_about: "About",
    nav_projects: "Projects",
    nav_tools: "Tools",
    nav_blog: "Blog",
    nav_contact: "Contact",
    chip_lang: "Language",
    chip_theme: "Theme",
    chip_color: "Color",
    chip_prefs: "Preferences",
    prefs_lang_hint: "Switches the page language",
    prefs_theme_hint: "Toggles light and dark theme",
    prefs_color_hint: "Changes the interface accent color",
    home_title: "Senior PHP Developer\nFullstack Developer",
    home_tip: "shortcut for language and theme",
    home_links_about: "About",
    home_links_projects: "Projects",
    home_links_tools: "Tools",
    home_links_contact: "Contact",
    home_links_github: "GitHub",
    home_links_linkedin: "LinkedIn",
    home_links_mail: "E‑mail",
    home_recent: "Quick jump",

    term_lines: [
      {t:"info", v:"$ whoami"},
      {t:"out",  v:"Rafal Salamon"},
      {t:"info", v:"$ cat profile.txt"},
      {t:"out",  v:"Senior PHP Developer • Fullstack • Software Engineer"},
      {t:"out",  v:"I build and maintain web applications, focusing on quality, refactoring and strong business communication."},
      {t:"info", v:"$ ls experience/"},
      {t:"out",  v:"Rubix (2022–now)  StepStone (2008–2022)  SBG (2007–2008)"},
      {t:"info", v:"$ open projects"},
      {t:"out",  v:"➡ Go to /projects to see public repositories + described commercial work."}
    ],

    about_h2: "About",
    about_p1: "I am a developer with many years of experience building and maintaining web applications. I often work backend-first (PHP), but I'm also comfortable with frontend and integrations.",
    about_p2: "I value business & technical analysis, clean code, refactoring, testability, and smooth communication between the technical team and stakeholders.",
    about_special: "Focus areas",
    about_skills: "Technologies (selected)",
    about_exp: "Experience",
    about_edu: "Education",
    about_train: "Training / certificates",
    about_lang: "Languages",
    about_interests: "Interests",

    projects_h2: "Projects",
    projects_public: "Public (GitHub)",
    projects_commercial: "Commercial (described)",
    projects_note: "Some work was delivered in commercial environments — understandably without code links. Below is scope and my contribution.",

    tools_h2: "Tools",
    tools_p: "My day-to-day stack — from backend and CI/CD to work organization.",

    contact_h2: "Contact",
    contact_p: "Want to discuss a role or a project? Send a message — I’ll get back as soon as possible.",
    contact_name: "Full name",
    contact_email: "E‑mail",
    contact_msg: "Message",
    contact_send: "Compose e‑mail",
    contact_copy: "Copy address",
    contact_copy_hint: "Copy e-mail address",
    contact_copied: "Copied!",
    privacy_title: "Privacy",
    privacy_text: "This website uses cookies and similar technologies for site operation and analytics.",
    privacy_accept: "Accept",
    privacy_decline: "Decline",
    back_to_top: "Back to Top",
    preview_iteration_details: "Iteration details",
    preview_show_details: "Show details",
    preview_hide_details: "Hide details",
    preview_enter_fullscreen: "Full screen",
    preview_exit_fullscreen: "Exit full screen",
    blog_read_article: "Read article",
    blog_no_articles: "No published articles yet.",
    blog_create_first_article: "Create the first article",
    blog_feed_title: "A blog about programming, technology and product-building practice",
    blog_feed_lede: "Here you'll find articles about PHP, web development, application architecture, code quality, and the day-to-day work behind modern digital products.",
    blog_reader_view: "Reader view",
    blog_articles_ready: "Articles ready for readers",
    blog_articles_ready_lede: "Published content is grouped into one main feed panel below the topbar, while this section keeps the supporting context and controls.",
    blog_published_articles_count: "Published articles in the current view",
    blog_configured_languages: "Configured language variants",
    blog_filters: "Filters",
    blog_all_languages: "All languages",
    blog_create_article: "Create article",
    blog_manage_articles: "Manage articles",
    blog_article_body: "Article body",
    blog_article_label_suffix: "article",
    blog_article_fallback_excerpt: "Published content page generated from the Twig blog view.",
    blog_back_to_articles: "Back to articles",
    blog_edit_in_admin: "Edit in admin",
    blog_metadata: "Metadata",
    blog_article_language: "Publication language",
    blog_publication_status: "Current publication status",
    blog_public_date: "Primary public date",
    blog_reader_slug: "Reader-facing slug",
    blog_pagination_previous: "Previous",
    blog_pagination_next: "Next",
    admin_shortcut_new_article: "Create new article",
    admin_shortcut_dashboard: "Dashboard",
    admin_shortcut_articles: "Article list",
    admin_shortcut_settings_title: "Quick settings",
    admin_shortcut_remember_device: "Remember this device",
    admin_shortcut_forget_device: "Forget this device",
    admin_shortcut_blog_settings: "Blog settings",
    admin_shortcut_queue_status: "Queue status",
    admin_shortcut_exports: "Exports",
    admin_shortcut_login_device: "Login",
    admin_shortcut_logout: "Log out",
    admin_dashboard_title: "Content operations hub",
    admin_dashboard_lede: "An admin dashboard aligned with the app layout, ready for editorial workflows, moderation and publishing.",
    admin_blog_settings_title: "Blog settings",
    admin_blog_settings_lede: "Manage the data that controls the blog name and the homepage metadata shown in search engines and link previews.",
    admin_queue_status_title: "Queue status",
    admin_queue_status_lede: "This list shows items ready to be processed by a background job. Only pending export queue entries are visible.",
    admin_queue_table_id: "ID",
    admin_queue_table_type: "Queue type",
    admin_queue_table_created: "Queued at",
    admin_queue_type_article_export: "Article export",
    admin_queue_no_items: "There are no items waiting to be processed.",
    admin_queue_delete: "Remove from queue",
    admin_queue_clear: "Clear queue",
    admin_queue_clear_popup_title: "Clear the queue?",
    admin_queue_clear_popup_text: "This operation will remove all pending queue items. Make sure the background job will no longer need them.",
    admin_queue_clear_popup_cancel: "Cancel",
    admin_queue_clear_popup_confirm: "Clear queue",
    admin_exports_title: "Exports",
    admin_exports_lede: "This list shows article export bundles produced by the background job. Each entry points to a file ready to be imported again.",
    admin_exports_table_id: "ID",
    admin_exports_table_type: "Type",
    admin_exports_table_status: "Status",
    admin_exports_table_articles: "Articles",
    admin_exports_table_file: "File",
    admin_exports_table_created: "Created",
    admin_exports_type_articles: "Article export",
    admin_exports_status_new: "New",
    admin_exports_status_downloaded: "Downloaded",
    admin_exports_download: "Download export file",
    admin_exports_no_items: "No exports are available yet.",
    admin_exports_clear: "Delete all exports",
    admin_exports_clear_popup_title: "Delete all exports?",
    admin_exports_clear_popup_text: "This action will remove all export records and their files from disk.",
    admin_exports_clear_popup_cancel: "Cancel",
    admin_exports_clear_popup_confirm: "Delete all exports",
    admin_exports_delete_popup_title: "Delete export?",
    admin_exports_delete_popup_text: "This action will remove the export record and its file from disk.",
    admin_exports_delete_popup_cancel: "Cancel",
    admin_exports_delete_popup_confirm: "Delete export",
    admin_browse_articles: "Browse articles",
    admin_edit_article: "Edit article",
    admin_quick_actions: "Quick actions",
    admin_quick_new_article: "New article",
    admin_quick_new_article_desc: "Start a fresh draft with an auto-generated slug.",
    admin_quick_manage_articles: "Manage articles",
    admin_quick_manage_articles_desc: "Review drafts, published entries and update content.",
    admin_quick_open_blog: "Open public blog",
    admin_quick_open_blog_desc: "Jump back to the view readers can already see.",
    admin_article_index_title: "Article management",
    admin_article_index_lede: "All articles in one place, regardless of status. The table below keeps the admin view compact.",
    admin_article_bulk_export: "Export selected",
    admin_article_select: "Select article",
    admin_article_select_all: "Select all articles",
    admin_table_title: "Title",
    admin_table_language: "Language",
    admin_table_status: "Status",
    admin_table_slug: "Slug",
    admin_table_updated: "Updated",
    admin_table_action: "Action",
    admin_close_alert: "Close alert",
    admin_table_edit: "Edit",
    admin_table_export: "Export",
    admin_table_publish: "Publish",
    admin_table_archive: "Archive",
    admin_table_delete: "Delete",
    admin_delete_popup_title: "Delete article?",
    admin_delete_popup_text: "This operation cannot be undone. The article will be permanently deleted.",
    admin_delete_popup_cancel: "Cancel",
    admin_delete_popup_confirm: "Delete",
    admin_table_no_articles: "No articles found.",
    admin_new_article_title: "Create article",
    admin_new_article_lede: "New entries start as drafts by default and receive a unique slug before saving.",
    admin_edit_article_title: "Edit article",
    admin_edit_article_lede: "Update content, metadata and publication state while keeping the existing slug.",
    admin_permanent_slug: "Permanent slug",
    admin_current_workflow_status: "Current workflow status",
    admin_last_update: "Last update timestamp",
    form_title: "Title",
    form_language: "Language",
    form_excerpt: "Short summary",
    form_headline_image_enabled: "Enable headline image",
    form_headline_image: "Headline image",
    form_content: "Content",
    form_status: "Status",
    form_publish_date: "Publish date",
    form_placeholder_title: "Enter article title",
    form_placeholder_excerpt: "Write a short summary for listings and previews",
    form_placeholder_headline_image: "Paste an image URL or a /assets/... path",
    form_hint_headline_image_enabled: "If the image field stays empty, the default system image will be used.",
    form_hint_headline_image: "Use a full URL or a path starting with /, for example /assets/img/article-cover.jpg. Leaving it empty while enabled will use the default image.",
    form_placeholder_content: "Write the full article content here",
    form_submit_create: "Create article",
    form_submit_update: "Save changes",
    form_submit_save: "Save article",
    form_cancel: "Cancel",
    login_eyebrow: "Admin access",
    login_title: "Sign in to manage content",
    login_lede: "This login screen uses the same visual language as the rest of the app.",
    login_email: "Email",
    login_password: "Password",
    login_submit: "Login",
    login_back_to_blog: "Back to blog",
    article_language_pl: "Polski (PL)",
    article_language_en: "English (EN)",
    article_status_draft: "Draft",
    article_status_review: "In review",
    article_status_published: "Published",
    article_status_archived: "Archived",
    validation_article_title_required: "Article title is required.",
    validation_article_title_too_long: "Article title can be at most 255 characters long.",
    validation_article_slug_too_long: "Article slug can be at most 255 characters long.",
    validation_article_excerpt_too_long: "Short summary can be at most 320 characters long.",
    validation_article_headline_image_too_long: "Headline image can be at most 500 characters long.",
    validation_article_headline_image_invalid: "Provide a full image URL or a path starting with /.",
    validation_article_content_required: "Article content is required.",
    validation_article_published_at_invalid: "Enter a valid publication date.",

    footer: "I build things that are clear, robust, and practical."
  }
};

function qs(sel, root=document){ return root.querySelector(sel); }
function qsa(sel, root=document){ return [...root.querySelectorAll(sel)]; }
let terminalRenderId = 0;
const adminDeviceStorageKey = 'admin_device_remembered';
const userLanguageCookieName = 'user_language';
const userTimezoneCookieName = 'user_timezone';

function syncTopbarHeight(){
  const topbar = qs('.topbar');
  if(!topbar) return;
  const height = Math.ceil(topbar.getBoundingClientRect().height);
  document.documentElement.style.setProperty('--topbar-height', `${height}px`);
}

function getLang(){
  const stored = localStorage.getItem('lang');
  if(stored) return stored;
  const n = (navigator.language || 'pl').toLowerCase();
  return n.startsWith('pl') ? 'pl' : 'en';
}

function persistUserLanguage(lang){
  const normalizedLang = lang === 'en' ? 'en' : 'pl';
  const secure = window.location.protocol === 'https:' ? '; Secure' : '';
  document.cookie = `${userLanguageCookieName}=${encodeURIComponent(normalizedLang)}; Max-Age=31536000; Path=/; SameSite=Lax${secure}`;
}

function setLang(lang){
  localStorage.setItem('lang', lang);
  persistUserLanguage(lang);
  applyI18n(lang);
}

function applyLangVisibility(lang){
  qsa('[data-lang]').forEach(el=>{
    const l = el.getAttribute('data-lang');
    el.style.display = (l === lang) ? '' : 'none';
  });
}

function getTheme(){ return localStorage.getItem('theme') || 'dark'; }
function getAccent(){ return localStorage.getItem('accent') || '#39ff14'; }
function getTranslation(key, lang = getLang()){
  return (i18n[lang] && i18n[lang][key]) || i18n.pl[key] || '';
}

function isAdminDeviceRemembered(){
  return localStorage.getItem(adminDeviceStorageKey) === '1';
}

function setAdminDeviceRemembered(remembered){
  if(remembered){
    localStorage.setItem(adminDeviceStorageKey, '1');
    return;
  }

  localStorage.removeItem(adminDeviceStorageKey);
}

function getBrowserTimeZone(){
  try{
    const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    return typeof timeZone === 'string' && timeZone.trim() !== '' ? timeZone.trim() : null;
  }catch(err){
    return null;
  }
}

function persistUserTimeZone(){
  const timeZone = getBrowserTimeZone();
  if(!timeZone) return;

  const secure = window.location.protocol === 'https:' ? '; Secure' : '';
  document.cookie = `${userTimezoneCookieName}=${encodeURIComponent(timeZone)}; Max-Age=31536000; Path=/; SameSite=Lax${secure}`;
}

function normalizeHexColor(value){
  if(typeof value !== 'string') return null;
  const v = value.trim().toLowerCase();
  return /^#[0-9a-f]{6}$/.test(v) ? v : null;
}

function hexToRgb(hex){
  const norm = normalizeHexColor(hex);
  if(!norm) return null;
  return {
    r: parseInt(norm.slice(1, 3), 16),
    g: parseInt(norm.slice(3, 5), 16),
    b: parseInt(norm.slice(5, 7), 16)
  };
}

function getContrastColor(rgb){
  if(!rgb) return '#0d1520';
  const toLinear = (channel)=>{
    const value = channel / 255;
    return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
  };
  const luminance = (0.2126 * toLinear(rgb.r)) + (0.7152 * toLinear(rgb.g)) + (0.0722 * toLinear(rgb.b));
  return luminance > 0.45 ? '#0d1520' : '#f6f8fb';
}

function setTheme(theme){
  localStorage.setItem('theme', theme);
  document.documentElement.setAttribute('data-theme', theme);
  qsa('[data-action="toggle-theme"]').forEach(btn=>{
    btn.textContent = theme === 'dark' ? '🌙' : '☀️';
  });
}

function setAccent(color){
  const accent = normalizeHexColor(color) || '#39ff14';
  const rgb = hexToRgb(accent);
  const accentContrast = getContrastColor(rgb);
  localStorage.setItem('accent', accent);
  document.documentElement.style.setProperty('--accent', accent);
  document.documentElement.style.setProperty('--accent-contrast', accentContrast);
  document.documentElement.style.setProperty('--link', accent);
  if(rgb){
    document.documentElement.style.setProperty('--link-bg', `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, .12)`);
    document.documentElement.style.setProperty('--scan', `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, .06)`);
  }
  qsa('[data-action="accent-color"]').forEach(colorInput=>{
    colorInput.value = accent;
  });
}

function applyI18n(lang){
  const t = i18n[lang] || i18n.pl;
  document.documentElement.lang = lang;
  applyLangVisibility(lang);

  qsa('[data-i18n]').forEach(el=>{
    const key = el.getAttribute('data-i18n');
    if(t[key] !== undefined) el.textContent = t[key];
  });
  qsa('[data-i18n-title]').forEach(el=>{
    const key = el.getAttribute('data-i18n-title');
    if(t[key] !== undefined){
      el.setAttribute('title', t[key]);
      el.setAttribute('aria-label', t[key]);
    }
  });
  qsa('[data-i18n-aria]').forEach(el=>{
    const key = el.getAttribute('data-i18n-aria');
    if(t[key] !== undefined) el.setAttribute('aria-label', t[key]);
  });
  qsa('[data-i18n-tooltip]').forEach(el=>{
    const key = el.getAttribute('data-i18n-tooltip');
    if(t[key] !== undefined){
      el.setAttribute('data-tooltip', t[key]);
      el.removeAttribute('title');
    }
  });
  qsa('[data-i18n-placeholder]').forEach(el=>{
    const key = el.getAttribute('data-i18n-placeholder');
    if(t[key] !== undefined) el.setAttribute('placeholder', t[key]);
  });

  const term = qs('#terminal');
  if(term){
    term.innerHTML = '';
    terminalRenderId += 1;
    typeTerminal(term, t.term_lines, terminalRenderId);
  }

  qsa('[data-action="toggle-lang"]').forEach(langBtn=>{
    langBtn.textContent = lang === 'pl' ? 'PL' : 'EN';
  });

  syncAdminShortcuts();

}

function setupTooltips(){
  const existingTooltip = qs('.app-tooltip');
  if(existingTooltip){
    existingTooltip.remove();
  }

  const tooltip = document.createElement('div');
  tooltip.className = 'app-tooltip';
  tooltip.setAttribute('hidden', '');
  tooltip.setAttribute('aria-hidden', 'true');
  document.body.appendChild(tooltip);

  let activeTrigger = null;

  const positionTooltip = (trigger)=>{
    if(!trigger || tooltip.hasAttribute('hidden')) return;
    const rect = trigger.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();
    const top = window.scrollY + rect.bottom + 10;
    const maxLeft = window.scrollX + window.innerWidth - tooltipRect.width - 12;
    const minLeft = window.scrollX + 12;
    const centeredLeft = window.scrollX + rect.left + (rect.width / 2) - (tooltipRect.width / 2);
    const left = Math.min(Math.max(centeredLeft, minLeft), maxLeft);

    tooltip.style.top = `${top}px`;
    tooltip.style.left = `${left}px`;
  };

  const showTooltip = (trigger)=>{
    const text = trigger.getAttribute('data-tooltip');
    if(!text) return;
    activeTrigger = trigger;
    tooltip.textContent = text;
    tooltip.removeAttribute('hidden');
    tooltip.setAttribute('aria-hidden', 'false');
    positionTooltip(trigger);
  };

  const hideTooltip = ()=>{
    tooltip.setAttribute('hidden', '');
    tooltip.setAttribute('aria-hidden', 'true');
    activeTrigger = null;
  };

  qsa('[data-tooltip]').forEach((el)=>{
    el.removeAttribute('title');
    el.addEventListener('mouseenter', ()=> showTooltip(el));
    el.addEventListener('mouseleave', hideTooltip);
    el.addEventListener('focus', ()=> showTooltip(el));
    el.addEventListener('blur', hideTooltip);
  });

  window.addEventListener('scroll', ()=>{
    if(activeTrigger) positionTooltip(activeTrigger);
  }, { passive: true });

  window.addEventListener('resize', ()=>{
    if(activeTrigger) positionTooltip(activeTrigger);
  });
}

function sleep(ms){ return new Promise(r=>setTimeout(r, ms)); }

function copyTextToClipboard(text){
  if(navigator.clipboard && window.isSecureContext){
    return navigator.clipboard.writeText(text).then(()=>true).catch(()=>false);
  }
  const ta = document.createElement('textarea');
  ta.value = text;
  ta.setAttribute('readonly', '');
  ta.style.position = 'fixed';
  ta.style.top = '-9999px';
  ta.style.left = '-9999px';
  document.body.appendChild(ta);
  ta.select();
  ta.setSelectionRange(0, ta.value.length);
  let ok = false;
  try{
    ok = document.execCommand('copy');
  }catch(err){
    ok = false;
  }
  document.body.removeChild(ta);
  return Promise.resolve(ok);
}

async function typeTerminal(root, lines, renderId){
  for(const line of lines){
    if(renderId !== terminalRenderId) return;
    const row = document.createElement('div');
    row.className = 'line';
    const p = document.createElement('span');
    p.className = 'prompt';
    p.textContent = line.t === 'info' ? '> ' : '  ';
    const text = document.createElement('span');
    text.className = line.t === 'info' ? 'cmd' : 'out';
    row.appendChild(p);
    row.appendChild(text);
    root.appendChild(row);
    root.scrollTop = root.scrollHeight;

    const str = line.v;
    for(let i=0;i<str.length;i++){
      if(renderId !== terminalRenderId) return;
      text.textContent += str[i];
      root.scrollTop = root.scrollHeight;
      await sleep(12 + Math.random()*18);
    }
    if(renderId !== terminalRenderId) return;
    await sleep(180);
  }
  if(renderId !== terminalRenderId) return;
  const c = document.createElement('span');
  c.className='cursor';
  root.appendChild(c);
  root.scrollTop = root.scrollHeight;
}

function setupNav(){
  const currentPath = location.pathname.toLowerCase();
  const currentOrigin = window.location.origin.toLowerCase();
  qsa('.nav a, .mobile-drawer a').forEach(a=>{
    const href = a.getAttribute('href') || '';
    if(!href) return;

    let targetUrl;
    let targetPath = '';
    try{
      targetUrl = new URL(href, window.location.origin);
      targetPath = targetUrl.pathname.toLowerCase();
    }catch(err){
      return;
    }

    const isSameOrigin = targetUrl.origin.toLowerCase() === currentOrigin;
    const isBlogLink = targetPath === '/';
    const isActive = isBlogLink
      ? isSameOrigin && (currentPath === '/' || currentPath.startsWith('/article/'))
      : isSameOrigin && targetPath === currentPath;

    if(isActive) a.classList.add('active');
  });

  const burger = qs('[data-action="toggle-menu"]');
  const drawer = qs('.mobile-drawer');
  if(burger && drawer){
    burger.addEventListener('click', ()=> drawer.classList.toggle('open'));
    qsa('a', drawer).forEach(a=> a.addEventListener('click', ()=> drawer.classList.remove('open')));
    document.addEventListener('click', (e)=>{
      if(!drawer.contains(e.target) && e.target !== burger) drawer.classList.remove('open');
    });
  }
}

function setupActions(){
  qsa('[data-action="toggle-lang"]').forEach(langBtn=>{
    langBtn.addEventListener('click', ()=>{
      const next = (getLang() === 'pl') ? 'en' : 'pl';
      setLang(next);
    });
  });

  qsa('[data-action="toggle-theme"]').forEach(themeBtn=>{
    themeBtn.addEventListener('click', ()=>{
      const next = (getTheme() === 'dark') ? 'light' : 'dark';
      setTheme(next);
    });
  });

  document.addEventListener('keydown', (e)=>{
    if(e.target && ['INPUT','TEXTAREA'].includes(e.target.tagName)) return;
    if(e.key.toLowerCase() === 'l') setLang(getLang() === 'pl' ? 'en' : 'pl');
    if(e.key.toLowerCase() === 't') setTheme(getTheme() === 'dark' ? 'light' : 'dark');
  });

  const copyBtn = qs('[data-action="copy-email"]');
  if(copyBtn){
    copyBtn.addEventListener('click', async ()=>{
      const email = copyBtn.getAttribute('data-email');
      const copied = await copyTextToClipboard(email);
      if(copied){
        const lang = getLang();
        copyBtn.setAttribute('data-tooltip', i18n[lang].contact_copied);
        setTimeout(()=>{
          const hint = i18n[getLang()].contact_copy_hint;
          copyBtn.setAttribute('data-tooltip', hint);
        }, 1200);
      }else{
        const lang = getLang();
        copyBtn.setAttribute('data-tooltip', i18n[lang].contact_copy_hint);
      }
    });
  }

  qsa('[data-action="accent-color"]').forEach(colorInput=>{
    colorInput.addEventListener('input', (e)=> setAccent(e.target.value));
  });

  const mailForm = qs('#contact-form');
  if(mailForm){
    mailForm.addEventListener('submit', (e)=>{
      e.preventDefault();
      const name = qs('#c_name').value.trim();
      const email = qs('#c_email').value.trim();
      const msg = qs('#c_msg').value.trim();
      const to = mailForm.getAttribute('data-to');
      const subject = encodeURIComponent(`[Portfolio] Message from ${name || 'visitor'}`);
      const body = encodeURIComponent(`${msg}\n\n---\nFrom: ${name}\nReply-to: ${email}`);
      window.location.href = `mailto:${to}?subject=${subject}&body=${body}`;
    });
  }
}

function syncAdminShortcuts(){
  qsa('[data-admin-shortcuts]').forEach(menu=>{
    const shortcuts = menu.closest('.admin-shortcuts');
    if(!shortcuts) return;

    const isAuthenticated = menu.getAttribute('data-authenticated') === 'true';
    if(!isAuthenticated){
      shortcuts.hidden = !isAdminDeviceRemembered();
      if(!isAdminDeviceRemembered()) shortcuts.removeAttribute('open');
      return;
    }

    const rememberButton = qs('[data-action="toggle-device-login"]', menu);
    if(!rememberButton) return;

    const remembered = isAdminDeviceRemembered();
    const label = qs('[data-device-remember-label]', rememberButton);
    const labelKey = remembered ? 'admin_shortcut_forget_device' : 'admin_shortcut_remember_device';

    rememberButton.classList.toggle('is-active', remembered);
    rememberButton.setAttribute('aria-pressed', remembered ? 'true' : 'false');

    if(label){
      label.setAttribute('data-i18n', labelKey);
      label.textContent = getTranslation(labelKey);
    }
  });
}

function setupAdminShortcuts(){
  syncAdminShortcuts();

  qsa('[data-action="toggle-device-login"]').forEach(button=>{
    button.addEventListener('click', ()=>{
      setAdminDeviceRemembered(!isAdminDeviceRemembered());
      syncAdminShortcuts();
    });
  });
}

function setupCharacterCounters(){
  qsa('[data-character-count-input]').forEach((input)=>{
    const counter = qs('[data-character-count-target]', input.closest('.article-editor-field'));
    if(!counter) return;

    const maxLength = Number(input.getAttribute('maxlength'));
    if(!Number.isFinite(maxLength) || maxLength <= 0) return;

    const updateCount = ()=>{
      counter.textContent = `${input.value.length} / ${maxLength}`;
    };

    updateCount();
    input.addEventListener('input', updateCount);
  });
}

function setupHeadlineImageToggle(){
  qsa('[data-headline-image-toggle]').forEach((toggle)=>{
    const section = toggle.closest('[data-headline-image-section]');
    const panel = section ? qs('[data-headline-image-panel]', section) : null;
    if(!panel) return;

    const syncVisibility = ()=>{
      const isEnabled = !!toggle.checked;
      panel.hidden = !isEnabled;
      panel.classList.toggle('is-hidden', !isEnabled);
      toggle.setAttribute('aria-expanded', isEnabled ? 'true' : 'false');
    };

    syncVisibility();
    toggle.addEventListener('change', syncVisibility);
  });
}

function setupArticleBulkExport(){
  const selectAll = qs('[data-article-select-all]');
  const submit = qs('[data-article-bulk-submit]');
  const checkboxes = qsa('[data-article-select-item]');
  if(!selectAll || !submit || !checkboxes.length) return;

  const syncState = ()=>{
    const checkedCount = checkboxes.filter((checkbox)=> checkbox.checked).length;
    selectAll.checked = checkedCount === checkboxes.length;
    selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
    submit.disabled = checkedCount === 0;
  };

  selectAll.addEventListener('change', ()=>{
    checkboxes.forEach((checkbox)=>{
      checkbox.checked = selectAll.checked;
    });
    syncState();
  });

  checkboxes.forEach((checkbox)=>{
    checkbox.addEventListener('change', syncState);
  });

  syncState();
}

function setupArticleMarkupEditor(){
  const editors = qsa('[data-markup-editor]');
  if(!editors.length) return;

  const preserveEditorView = (textarea, selectionStart, selectionEnd)=>{
    const { scrollTop, scrollLeft } = textarea;
    textarea.focus({ preventScroll: true });
    textarea.setSelectionRange(selectionStart, selectionEnd);
    textarea.scrollTop = scrollTop;
    textarea.scrollLeft = scrollLeft;
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
  };

  const insertText = (textarea, text, cursorOffset = text.length)=>{
    const start = textarea.selectionStart ?? 0;
    const end = textarea.selectionEnd ?? 0;
    const nextValue = `${textarea.value.slice(0, start)}${text}${textarea.value.slice(end)}`;
    textarea.value = nextValue;
    const caret = start + cursorOffset;
    preserveEditorView(textarea, caret, caret);
  };

  const wrapSelection = (textarea, before, after = before, fallback = '')=>{
    const start = textarea.selectionStart ?? 0;
    const end = textarea.selectionEnd ?? 0;
    const selected = textarea.value.slice(start, end) || fallback;
    const nextValue = `${textarea.value.slice(0, start)}${before}${selected}${after}${textarea.value.slice(end)}`;
    textarea.value = nextValue;
    const caretStart = start + before.length;
    const caretEnd = caretStart + selected.length;
    preserveEditorView(textarea, caretStart, caretEnd);
  };

  const transformSelectedLines = (textarea, transform)=>{
    const start = textarea.selectionStart ?? 0;
    const end = textarea.selectionEnd ?? 0;
    const blockStart = textarea.value.lastIndexOf('\n', Math.max(0, start - 1)) + 1;
    const blockEndIndex = textarea.value.indexOf('\n', end);
    const blockEnd = blockEndIndex === -1 ? textarea.value.length : blockEndIndex;
    const selectedBlock = textarea.value.slice(blockStart, blockEnd);
    const nextBlock = transform(selectedBlock || '');
    textarea.value = `${textarea.value.slice(0, blockStart)}${nextBlock}${textarea.value.slice(blockEnd)}`;
    preserveEditorView(textarea, blockStart, blockStart + nextBlock.length);
  };

  editors.forEach((textarea)=>{
    const field = textarea.closest('.article-editor-field');
    const toolbar = qs('[data-markup-toolbar]', field);
    const helpModal = qs('[data-markup-help-modal]', field);
    const helpDialog = qs('.article-editor-help-dialog', helpModal);
    const helpClose = qs('[data-markup-help-close]', helpModal);
    const helpTabs = qsa('[data-markup-help-tab]', helpModal);
    const helpPanels = qsa('[data-markup-help-panel]', helpModal);
    let lastHelpTrigger = null;
    if(!toolbar) return;

    const activateHelpTab = (name)=>{
      if(!helpTabs.length || !helpPanels.length) return;

      helpTabs.forEach((tab)=>{
        const isActive = tab.getAttribute('data-markup-help-tab') === name;
        tab.classList.toggle('is-active', isActive);
        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        tab.setAttribute('tabindex', isActive ? '0' : '-1');
      });

      helpPanels.forEach((panel)=>{
        const isActive = panel.getAttribute('data-markup-help-panel') === name;
        panel.classList.toggle('is-active', isActive);
        panel.hidden = !isActive;
      });
    };

    const closeHelpModal = ()=>{
      if(!helpModal) return;
      helpModal.setAttribute('hidden', '');
      helpModal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      if(lastHelpTrigger) lastHelpTrigger.focus({ preventScroll: true });
      lastHelpTrigger = null;
    };

    const openHelpModal = (trigger)=>{
      if(!helpModal) return;
      lastHelpTrigger = trigger;
      activateHelpTab('basic');
      helpModal.removeAttribute('hidden');
      helpModal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      if(helpClose) helpClose.focus({ preventScroll: true });
    };

    toolbar.addEventListener('mousedown', (event)=>{
      const button = event.target.closest('[data-markup-action]');
      if(!button) return;
      const action = button.getAttribute('data-markup-action');
      if(action && action !== 'help'){
        event.preventDefault();
      }
    });

    toolbar.addEventListener('click', (event)=>{
      const button = event.target.closest('[data-markup-action]');
      if(!button) return;

      const action = button.getAttribute('data-markup-action');
      if(!action) return;

      if(action === 'help'){
        openHelpModal(button);
        return;
      }
      if(action === 'bold') return wrapSelection(textarea, '**', '**', 'pogrubienie');
      if(action === 'italic') return wrapSelection(textarea, '*', '*', 'kursywa');
      if(action === 'underline') return wrapSelection(textarea, '++', '++', 'podkreslenie');
      if(action === 'inline-code') return wrapSelection(textarea, '`', '`', 'kod');
      if(action === 'line-break') return insertText(textarea, "\\\n");
      if(action === 'separator') return insertText(textarea, "\n---\n");
      if(action === 'table') return insertText(textarea, "| Kolumna A | Kolumna B |\n| --- | --- |\n| Wartosc 1 | Wartosc 2 |");
      if(action === 'preformatted'){
        return transformSelectedLines(textarea, (value)=> `:::pre\n${value || 'wpisz tekst zachowujacy uklad'}\n:::`);
      }
      if(action === 'quote') return transformSelectedLines(textarea, (value)=> value.split('\n').map((line)=> `> ${line.replace(/^\s*>\s?/, '')}`).join('\n'));
      if(action === 'bullet-list') return transformSelectedLines(textarea, (value)=> value.split('\n').map((line)=> `- ${line.replace(/^\s*[-*]\s+/, '').trim() || 'element listy'}`).join('\n'));
      if(action === 'numbered-list') return transformSelectedLines(textarea, (value)=> value.split('\n').map((line, index)=> `${index + 1}. ${line.replace(/^\s*\d+\.\s+/, '').trim() || 'element listy'}`).join('\n'));
      if(action === 'heading'){
        const level = button.getAttribute('data-markup-level') || '1';
        return transformSelectedLines(textarea, (value)=> {
          const prefix = '#'.repeat(Number(level));
          return `${prefix} ${value.replace(/^\s*#{1,7}\s+/, '').trim() || 'Naglowek'}`;
        });
      }
      if(action === 'align'){
        const align = button.getAttribute('data-markup-align') || 'left';
        return transformSelectedLines(textarea, (value)=> `:::${align}\n${value.trim() || 'Wpisz tresc'}\n:::`);
      }
      if(action === 'code-block'){
        return wrapSelection(textarea, "```\n", "\n```", "wpisz kod");
      }
      if(action === 'link'){
        const url = window.prompt('Podaj adres URL linku:', 'https://');
        if(!url) return;
        return wrapSelection(textarea, '[', `](${url})`, 'tekst linku');
      }
      if(action === 'image'){
        const url = window.prompt('Podaj adres URL obrazka:', 'https://');
        if(!url) return;
        return wrapSelection(textarea, '![', `](${url})`, 'opis obrazka');
      }
    });

    if(helpClose){
      helpClose.addEventListener('click', closeHelpModal);
    }

    if(helpModal){
      helpModal.addEventListener('click', (event)=>{
        if(event.target === helpModal){
          closeHelpModal();
        }
      });
    }

    if(helpDialog){
      helpDialog.addEventListener('click', (event)=>{
        event.stopPropagation();
      });
    }

    helpTabs.forEach((tab)=>{
      tab.addEventListener('click', ()=>{
        activateHelpTab(tab.getAttribute('data-markup-help-tab') || 'basic');
      });

      tab.addEventListener('keydown', (event)=>{
        if(event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
        event.preventDefault();
        const currentIndex = helpTabs.indexOf(tab);
        const direction = event.key === 'ArrowRight' ? 1 : -1;
        const nextIndex = (currentIndex + direction + helpTabs.length) % helpTabs.length;
        const nextTab = helpTabs[nextIndex];
        if(!nextTab) return;
        activateHelpTab(nextTab.getAttribute('data-markup-help-tab') || 'basic');
        nextTab.focus({ preventScroll: true });
      });
    });

    document.addEventListener('keydown', (event)=>{
      if(!helpModal || helpModal.hasAttribute('hidden')) return;
      if(event.key === 'Escape'){
        event.preventDefault();
        closeHelpModal();
      }
    });
  });
}

function setupImagePreview(){
  const triggers = qsa('[data-action="open-image-preview"]');
  if(!triggers.length) return;
  const mobilePreviewQuery = window.matchMedia('(max-width: 700px)');

  const modal = document.createElement('div');
  modal.className = 'image-preview-modal';
  modal.setAttribute('hidden', '');
  modal.setAttribute('aria-hidden', 'true');
  modal.innerHTML = `
    <div class="image-preview-dialog" role="dialog" aria-modal="true" aria-label="Image preview">
      <button type="button" class="image-preview-nav image-preview-prev" data-action="preview-prev" aria-label="Previous image"><span class="image-preview-nav-icon" aria-hidden="true">‹</span></button>
      <button type="button" class="image-preview-fullscreen" data-action="toggle-preview-fullscreen" aria-label="Full screen">⛶</button>
      <button type="button" class="image-preview-close" data-action="close-image-preview" aria-label="Close preview">×</button>
      <figure class="image-preview-frame">
        <h2 class="image-preview-title"></h2>
        <img src="" alt="" />
        <section class="image-preview-meta">
          <div class="image-preview-meta-bar">
            <span class="image-preview-meta-label"></span>
            <button type="button" class="image-preview-meta-toggle" data-action="toggle-preview-caption"></button>
          </div>
          <div class="image-preview-caption">
            <div class="image-preview-caption-step"></div>
            <h3 class="image-preview-caption-title"></h3>
            <p class="image-preview-caption-text"></p>
          </div>
        </section>
      </figure>
      <button type="button" class="image-preview-nav image-preview-next" data-action="preview-next" aria-label="Next image"><span class="image-preview-nav-icon" aria-hidden="true">›</span></button>
    </div>
  `;
  document.body.appendChild(modal);

  const dialog = qs('.image-preview-dialog', modal);
  const image = qs('.image-preview-frame img', modal);
  const closeBtn = qs('[data-action="close-image-preview"]', modal);
  const fullscreenBtn = qs('[data-action="toggle-preview-fullscreen"]', modal);
  const prevBtn = qs('[data-action="preview-prev"]', modal);
  const nextBtn = qs('[data-action="preview-next"]', modal);
  const previewTitle = qs('.image-preview-title', modal);
  const meta = qs('.image-preview-meta', modal);
  const metaLabel = qs('.image-preview-meta-label', modal);
  const metaToggle = qs('[data-action="toggle-preview-caption"]', modal);
  const captionStep = qs('.image-preview-caption-step', modal);
  const captionTitle = qs('.image-preview-caption-title', modal);
  const captionText = qs('.image-preview-caption-text', modal);
  let lastTrigger = null;
  let currentIndex = -1;
  let captionCollapsed = false;

  const getPreviewText = (key)=>{
    const lang = getLang();
    return (i18n[lang] && i18n[lang][key]) || i18n.pl[key] || '';
  };

  const syncCaptionToggle = ()=>{
    if(!metaToggle) return;
    metaToggle.textContent = getPreviewText(captionCollapsed ? 'preview_show_details' : 'preview_hide_details');
    metaToggle.setAttribute('aria-expanded', captionCollapsed ? 'false' : 'true');
    dialog.classList.toggle('caption-collapsed', captionCollapsed);
  };

  const syncFullscreenToggle = ()=>{
    if(!fullscreenBtn) return;
    const isFullscreen = dialog.classList.contains('is-fullscreen');
    fullscreenBtn.textContent = isFullscreen ? '🗗' : '⛶';
    fullscreenBtn.setAttribute('aria-label', getPreviewText(isFullscreen ? 'preview_exit_fullscreen' : 'preview_enter_fullscreen'));
    fullscreenBtn.setAttribute('title', getPreviewText(isFullscreen ? 'preview_exit_fullscreen' : 'preview_enter_fullscreen'));
  };

  const syncCaptionContent = (trigger)=>{
    if(!trigger || !meta || !captionStep || !captionTitle || !captionText || !previewTitle) return;
    const entry = trigger.closest('.timeline-entry');
    if(!entry) return;
    const lang = getLang();
    const fallbackLang = lang === 'pl' ? 'en' : 'pl';
    const titleScope = qsa(`[data-lang="${lang}"]`, entry).find((scope)=> qs('.timeline-title', scope))
      || qsa(`[data-lang="${fallbackLang}"]`, entry).find((scope)=> qs('.timeline-title', scope));
    const textScope = qsa(`[data-lang="${lang}"]`, entry).find((scope)=> qs('p', scope))
      || qsa(`[data-lang="${fallbackLang}"]`, entry).find((scope)=> qs('p', scope));
    const titleEl = titleScope ? qs('.timeline-title', titleScope) : qs('.timeline-title', entry);
    const stepEl = qs('.timeline-step-label', entry);
    const textEl = textScope ? qs('p', textScope) : null;

    const stepText = stepEl ? stepEl.textContent.trim() : '';
    const titleText = titleEl ? titleEl.textContent.trim() : '';
    metaLabel.textContent = stepText;
    captionStep.textContent = '';
    previewTitle.textContent = titleText;
    captionTitle.textContent = '';
    captionText.textContent = textEl ? textEl.textContent.trim() : '';
  };

  const syncNav = ()=>{
    const hasPrev = currentIndex > 0;
    const hasNext = currentIndex >= 0 && currentIndex < triggers.length - 1;
    if(prevBtn){
      prevBtn.disabled = !hasPrev;
      prevBtn.setAttribute('aria-hidden', hasPrev ? 'false' : 'true');
    }
    if(nextBtn){
      nextBtn.disabled = !hasNext;
      nextBtn.setAttribute('aria-hidden', hasNext ? 'false' : 'true');
    }
  };

  const closePreview = ()=>{
    modal.setAttribute('hidden', '');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    dialog.classList.remove('is-fullscreen');
    syncFullscreenToggle();
    if(lastTrigger) lastTrigger.focus();
  };

  const openPreview = (trigger)=>{
    const src = trigger.getAttribute('data-image-src');
    const alt = trigger.getAttribute('data-image-alt') || '';
    if(!src) return;
    lastTrigger = trigger;
    currentIndex = triggers.indexOf(trigger);
    image.src = src;
    image.alt = alt;
    syncCaptionContent(trigger);
    syncNav();
    syncCaptionToggle();
    syncFullscreenToggle();
    modal.removeAttribute('hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    closeBtn.focus();
  };

  const openAdjacent = (direction)=>{
    const nextIndex = currentIndex + direction;
    const nextTrigger = triggers[nextIndex];
    if(!nextTrigger) return;
    openPreview(nextTrigger);
  };

  triggers.forEach(trigger=>{
    trigger.addEventListener('click', (e)=>{
      if(mobilePreviewQuery.matches){
        e.preventDefault();
        return;
      }
      openPreview(trigger);
    });
  });

  if(closeBtn){
    closeBtn.addEventListener('click', closePreview);
  }
  if(fullscreenBtn){
    fullscreenBtn.addEventListener('click', ()=>{
      dialog.classList.toggle('is-fullscreen');
      syncFullscreenToggle();
    });
  }
  if(prevBtn){
    prevBtn.addEventListener('click', ()=> openAdjacent(-1));
  }
  if(nextBtn){
    nextBtn.addEventListener('click', ()=> openAdjacent(1));
  }
  if(metaToggle && meta){
    metaToggle.addEventListener('click', ()=>{
      captionCollapsed = !captionCollapsed;
      meta.classList.toggle('is-collapsed', captionCollapsed);
      syncCaptionToggle();
    });
  }

  modal.addEventListener('click', (e)=>{
    if(e.target === modal) closePreview();
  });

  dialog.addEventListener('click', (e)=>{
    e.stopPropagation();
  });

  document.addEventListener('keydown', (e)=>{
    if(modal.hasAttribute('hidden')) return;
    if(e.key === 'Escape'){
      closePreview();
    }else if(e.key === 'ArrowLeft'){
      openAdjacent(-1);
    }else if(e.key === 'ArrowRight'){
      openAdjacent(1);
    }
  });
}

function fastScrollToTop(){
  if(window.matchMedia('(prefers-reduced-motion: reduce)').matches){
    window.scrollTo(0, 0);
    return;
  }

  const startY = window.scrollY || window.pageYOffset;
  if(startY <= 0) return;

  const duration = 220;
  const start = performance.now();

  function step(now){
    const elapsed = now - start;
    const progress = Math.min(elapsed / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 4);
    window.scrollTo(0, Math.round(startY * (1 - eased)));
    if(progress < 1) requestAnimationFrame(step);
  }

  requestAnimationFrame(step);
}

function setupBackToTop(){
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'back-to-top';
  btn.setAttribute('data-i18n-aria', 'back_to_top');
  btn.innerHTML = '<span class="back-to-top-icon" aria-hidden="true"></span><span class="back-to-top-label" data-i18n="back_to_top"></span>';
  document.body.appendChild(btn);

  const toggleVisibility = ()=>{
    const shouldShow = (window.scrollY || window.pageYOffset) > 600;
    btn.classList.toggle('is-visible', shouldShow);
  };

  window.addEventListener('scroll', toggleVisibility, { passive: true });
  window.addEventListener('resize', toggleVisibility);
  btn.addEventListener('click', fastScrollToTop);
  toggleVisibility();
}

function setupFlashNotices(){
  qsa('[data-action="dismiss-flash"]').forEach((button)=>{
    button.addEventListener('click', ()=>{
      const flash = button.closest('.flash');
      if(!flash) return;
      flash.setAttribute('hidden', '');
    });
  });
}

function setupDangerConfirmation(config){
  const triggers = qsa(config.triggerSelector);
  if(!triggers.length) return;

  const existingModal = qs(`.${config.modalClass}`);
  if(existingModal){
    existingModal.remove();
  }

  const titleId = `${config.modalIdPrefix}-title`;
  const textId = `${config.modalIdPrefix}-text`;
  const modal = document.createElement('div');
  modal.className = `confirm-delete-modal ${config.modalClass}`;
  modal.setAttribute('hidden', '');
  modal.setAttribute('aria-hidden', 'true');
  modal.innerHTML = `
    <div class="confirm-delete-dialog" role="alertdialog" aria-modal="false" aria-labelledby="${titleId}" aria-describedby="${textId}">
      <div class="confirm-delete-topbar">
        <div class="confirm-delete-eyebrow">admin://danger-zone</div>
        <button type="button" class="confirm-delete-close" data-action="${config.closeAction}" data-i18n-aria="${config.closeI18n}" aria-label="${config.closeFallback}">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <h2 id="${titleId}" class="confirm-delete-title" data-i18n="${config.titleI18n}">${config.titleFallback}</h2>
      <p id="${textId}" class="confirm-delete-text" data-i18n="${config.textI18n}">${config.textFallback}</p>
      ${config.detailsClass ? `<p class="${config.detailsClass}"></p>` : ''}
      <div class="confirm-delete-actions">
        <button type="button" class="button secondary" data-action="${config.cancelAction}" data-i18n="${config.cancelI18n}">${config.cancelFallback}</button>
        <button type="button" class="button button-danger" data-action="${config.submitAction}" data-i18n="${config.submitI18n}">${config.submitFallback}</button>
      </div>
    </div>
  `;

  document.body.appendChild(modal);
  applyI18n(getLang());

  const dialog = qs('.confirm-delete-dialog', modal);
  const closeButton = qs(`[data-action="${config.closeAction}"]`, modal);
  const cancelButton = qs(`[data-action="${config.cancelAction}"]`, modal);
  const submitButton = qs(`[data-action="${config.submitAction}"]`, modal);
  const details = config.detailsClass ? qs(`.${config.detailsClass}`, modal) : null;
  let activeForm = null;
  let lastTrigger = null;

  const closeModal = ()=>{
    modal.setAttribute('hidden', '');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    if(lastTrigger) lastTrigger.focus({ preventScroll: true });
    activeForm = null;
  };

  const openModal = (trigger)=>{
    activeForm = trigger.closest('form');
    lastTrigger = trigger;
    if(details){
      details.textContent = config.detailsText ? config.detailsText(trigger) : '';
    }
    modal.removeAttribute('hidden');
    modal.setAttribute('aria-hidden', 'false');
    if(closeButton) closeButton.focus({ preventScroll: true });
  };

  triggers.forEach((trigger)=>{
    trigger.addEventListener('click', ()=>{
      openModal(trigger);
    });
  });

  if(closeButton){
    closeButton.addEventListener('click', closeModal);
  }

  if(cancelButton){
    cancelButton.addEventListener('click', closeModal);
  }

  if(submitButton){
    submitButton.addEventListener('click', ()=>{
      if(activeForm) activeForm.submit();
    });
  }

  if(dialog){
    dialog.addEventListener('click', (event)=>{
      event.stopPropagation();
    });
  }

  document.addEventListener('keydown', (event)=>{
    if(modal.hasAttribute('hidden')) return;
    if(event.key === 'Escape'){
      event.preventDefault();
      closeModal();
    }
  });
}

function setupDeleteConfirmation(){
  setupDangerConfirmation({
    triggerSelector: '[data-action="confirm-delete-article"]',
    modalClass: 'confirm-delete-article-modal',
    modalIdPrefix: 'confirm-delete-article',
    titleI18n: 'admin_delete_popup_title',
    titleFallback: 'Usunac artykul?',
    textI18n: 'admin_delete_popup_text',
    textFallback: 'Ta operacja jest nieodwracalna. Artykul zostanie trwale usuniety.',
    detailsClass: 'confirm-delete-article-name',
    detailsText: (trigger)=> trigger.getAttribute('data-article-title') || '',
    cancelAction: 'cancel-delete',
    submitAction: 'submit-delete',
    closeAction: 'close-delete',
    cancelI18n: 'admin_delete_popup_cancel',
    cancelFallback: 'Przerwij',
    submitI18n: 'admin_delete_popup_confirm',
    submitFallback: 'Usun',
    closeI18n: 'admin_close_alert',
    closeFallback: 'Zamknij alert',
  });
}

function setupQueueClearConfirmation(){
  setupDangerConfirmation({
    triggerSelector: '[data-action="confirm-clear-queue"]',
    modalClass: 'confirm-clear-queue-modal',
    modalIdPrefix: 'confirm-clear-queue',
    titleI18n: 'admin_queue_clear_popup_title',
    titleFallback: 'Wyczyscic kolejke?',
    textI18n: 'admin_queue_clear_popup_text',
    textFallback: 'Ta operacja usunie wszystkie oczekujace elementy kolejki. Upewnij sie, ze zadanie w tle nie bedzie ich juz potrzebowalo.',
    detailsClass: null,
    detailsText: null,
    cancelAction: 'cancel-clear-queue',
    submitAction: 'submit-clear-queue',
    closeAction: 'close-clear-queue',
    cancelI18n: 'admin_queue_clear_popup_cancel',
    cancelFallback: 'Przerwij',
    submitI18n: 'admin_queue_clear_popup_confirm',
    submitFallback: 'Wyczyść kolejkę',
    closeI18n: 'admin_close_alert',
    closeFallback: 'Zamknij alert',
  });
}

function setupExportDeleteConfirmation(){
  setupDangerConfirmation({
    triggerSelector: '[data-action="confirm-delete-export"]',
    modalClass: 'confirm-delete-export-modal',
    modalIdPrefix: 'confirm-delete-export',
    titleI18n: 'admin_exports_delete_popup_title',
    titleFallback: 'Usunac eksport?',
    textI18n: 'admin_exports_delete_popup_text',
    textFallback: 'Ta operacja usunie rekord eksportu i powiazany plik z dysku.',
    detailsClass: 'confirm-delete-export-name',
    detailsText: (trigger)=> trigger.getAttribute('data-export-file') || '',
    cancelAction: 'cancel-delete-export',
    submitAction: 'submit-delete-export',
    closeAction: 'close-delete-export',
    cancelI18n: 'admin_exports_delete_popup_cancel',
    cancelFallback: 'Przerwij',
    submitI18n: 'admin_exports_delete_popup_confirm',
    submitFallback: 'Usun eksport',
    closeI18n: 'admin_close_alert',
    closeFallback: 'Zamknij alert',
  });
}

function setupExportClearConfirmation(){
  setupDangerConfirmation({
    triggerSelector: '[data-action="confirm-clear-exports"]',
    modalClass: 'confirm-clear-exports-modal',
    modalIdPrefix: 'confirm-clear-exports',
    titleI18n: 'admin_exports_clear_popup_title',
    titleFallback: 'Usunac wszystkie eksporty?',
    textI18n: 'admin_exports_clear_popup_text',
    textFallback: 'Ta operacja usunie wszystkie rekordy eksportow oraz powiazane pliki z dysku.',
    detailsClass: null,
    detailsText: null,
    cancelAction: 'cancel-clear-exports',
    submitAction: 'submit-clear-exports',
    closeAction: 'close-clear-exports',
    cancelI18n: 'admin_exports_clear_popup_cancel',
    cancelFallback: 'Przerwij',
    submitI18n: 'admin_exports_clear_popup_confirm',
    submitFallback: 'Usun wszystkie eksporty',
    closeI18n: 'admin_close_alert',
    closeFallback: 'Zamknij alert',
  });
}

function setupPrivacyNotice(){
  const storageKey = 'privacy-consent';
  const acceptedValue = 'accepted';
  const declinedValue = 'declined';
  const existingConsent = localStorage.getItem(storageKey);
  if(existingConsent === acceptedValue || existingConsent === declinedValue) return;

  const existingPopup = qs('.privacy-popup');
  if(existingPopup){
    applyI18n(getLang());
    return;
  }

  const popupTitleId = 'privacy-popup-title';
  const popupTextId = 'privacy-popup-text';
  const popup = document.createElement('aside');
  popup.className = 'privacy-popup';
  popup.setAttribute('role', 'dialog');
  popup.setAttribute('aria-live', 'polite');
  popup.setAttribute('aria-labelledby', popupTitleId);
  popup.setAttribute('aria-describedby', popupTextId);
  popup.setAttribute('aria-modal', 'false');

  popup.innerHTML = `
    <h2 id="${popupTitleId}" class="privacy-popup-title" data-i18n="privacy_title">Prywatność</h2>
    <p id="${popupTextId}" class="privacy-popup-text" data-i18n="privacy_text">Ta strona używa cookies i podobnych technologii do działania serwisu oraz analityki.</p>
    <div class="privacy-popup-actions">
      <button type="button" class="btn" data-action="privacy-decline" data-i18n="privacy_decline">Odrzuć</button>
      <button type="button" class="btn primary" data-action="privacy-accept" data-i18n="privacy_accept">Akceptuję</button>
    </div>
  `;

  document.body.appendChild(popup);
  applyI18n(getLang());

  let isClosing = false;
  const closePopup = (consentValue)=>{
    if(isClosing) return;
    isClosing = true;
    localStorage.setItem(storageKey, consentValue);
    popup.classList.add('is-hidden');
    window.setTimeout(()=>{
      popup.remove();
    }, 180);
  };

  const acceptBtn = qs('[data-action="privacy-accept"]', popup);
  if(acceptBtn){
    acceptBtn.addEventListener('click', ()=> closePopup(acceptedValue));
    window.requestAnimationFrame(()=> acceptBtn.focus({ preventScroll: true }));
  }

  const declineBtn = qs('[data-action="privacy-decline"]', popup);
  if(declineBtn) declineBtn.addEventListener('click', ()=> closePopup(declinedValue));

  popup.addEventListener('keydown', (event)=>{
    if(event.key === 'Escape'){
      event.preventDefault();
      closePopup(declinedValue);
    }
  });
}

function init(){
  syncTopbarHeight();
  persistUserLanguage(getLang());
  persistUserTimeZone();
  setupNav();
  setupBackToTop();
  setTheme(getTheme());
  setAccent(getAccent());
  const lang = getLang();
  applyI18n(lang);
  setupTooltips();
  setupFlashNotices();
  setupAdminShortcuts();
  setupActions();
  setupCharacterCounters();
  setupHeadlineImageToggle();
  setupArticleBulkExport();
  setupArticleMarkupEditor();
  setupImagePreview();
  setupDeleteConfirmation();
  setupExportDeleteConfirmation();
  setupExportClearConfirmation();
  setupQueueClearConfirmation();
  setupPrivacyNotice();
  syncTopbarHeight();

  window.addEventListener('resize', syncTopbarHeight);
  window.addEventListener('orientationchange', syncTopbarHeight);
  if(window.visualViewport){
    window.visualViewport.addEventListener('resize', syncTopbarHeight);
  }
}

document.addEventListener('DOMContentLoaded', init);
