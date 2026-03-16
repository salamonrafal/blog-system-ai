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
    admin_shortcut_new_article: "Utwórz nowy artykuł",
    admin_shortcut_dashboard: "Panel główny",
    admin_shortcut_articles: "Lista artykułów",
    admin_shortcut_logout: "Wyloguj",
    admin_dashboard_title: "Centrum operacji treści",
    admin_dashboard_lede: "Panel administracyjny dopasowany do układu aplikacji, gotowy na workflow redakcyjny, moderację i publikację.",
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
    admin_table_title: "Tytuł",
    admin_table_language: "Język",
    admin_table_status: "Status",
    admin_table_slug: "Slug",
    admin_table_updated: "Aktualizacja",
    admin_table_action: "Akcja",
    admin_table_edit: "Edytuj",
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
    form_content: "Treść",
    form_status: "Status",
    form_publish_date: "Data publikacji",
    form_placeholder_title: "Wpisz tytuł artykułu",
    form_placeholder_excerpt: "Wpisz krótki opis do list i podglądów",
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
    admin_shortcut_new_article: "Create new article",
    admin_shortcut_dashboard: "Dashboard",
    admin_shortcut_articles: "Article list",
    admin_shortcut_logout: "Log out",
    admin_dashboard_title: "Content operations hub",
    admin_dashboard_lede: "An admin dashboard aligned with the app layout, ready for editorial workflows, moderation and publishing.",
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
    admin_table_title: "Title",
    admin_table_language: "Language",
    admin_table_status: "Status",
    admin_table_slug: "Slug",
    admin_table_updated: "Updated",
    admin_table_action: "Action",
    admin_table_edit: "Edit",
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
    form_content: "Content",
    form_status: "Status",
    form_publish_date: "Publish date",
    form_placeholder_title: "Enter article title",
    form_placeholder_excerpt: "Write a short summary for listings and previews",
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

    footer: "I build things that are clear, robust, and practical."
  }
};

function qs(sel, root=document){ return root.querySelector(sel); }
function qsa(sel, root=document){ return [...root.querySelectorAll(sel)]; }
let terminalRenderId = 0;

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

function setLang(lang){ localStorage.setItem('lang', lang); applyI18n(lang); }

function applyLangVisibility(lang){
  qsa('[data-lang]').forEach(el=>{
    const l = el.getAttribute('data-lang');
    el.style.display = (l === lang) ? '' : 'none';
  });
}

function getTheme(){ return localStorage.getItem('theme') || 'dark'; }
function getAccent(){ return localStorage.getItem('accent') || '#39ff14'; }

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
      const isTopPrefsControl = !!el.closest('.preferences-chip');
      if(isTopPrefsControl){
        el.removeAttribute('title');
      }else{
        // Native tooltip fallback outside topbar preferences.
        el.setAttribute('title', t[key]);
      }
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
  const path = (location.pathname.split('/').pop() || 'index.html').toLowerCase();
  qsa('.nav a, .mobile-drawer a').forEach(a=>{
    const href = (a.getAttribute('href')||'').toLowerCase();
    if(href === path) a.classList.add('active');
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
        copyBtn.setAttribute('title', i18n[lang].contact_copied);
        setTimeout(()=>{
          const hint = i18n[getLang()].contact_copy_hint;
          copyBtn.setAttribute('data-tooltip', hint);
          copyBtn.setAttribute('title', hint);
        }, 1200);
      }else{
        const lang = getLang();
        copyBtn.setAttribute('data-tooltip', i18n[lang].contact_copy_hint);
        copyBtn.setAttribute('title', i18n[lang].contact_copy_hint);
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
  setupNav();
  setupBackToTop();
  setTheme(getTheme());
  setAccent(getAccent());
  const lang = getLang();
  applyI18n(lang);
  setupActions();
  setupImagePreview();
  setupPrivacyNotice();
  syncTopbarHeight();

  window.addEventListener('resize', syncTopbarHeight);
  window.addEventListener('orientationchange', syncTopbarHeight);
  if(window.visualViewport){
    window.visualViewport.addEventListener('resize', syncTopbarHeight);
  }
}

document.addEventListener('DOMContentLoaded', init);
