import { ensureTranslations, hasLoadedTranslations, i18n } from './i18n-data.js';
import { getLang } from './preferences.js';
import { qs, qsa, sleep } from './shared.js';

const i18nApplyListeners = [];
let i18nApplyId = 0;
let terminalRenderId = 0;

export { i18n };

export function registerI18nListener(listener){
  if(typeof listener === 'function'){
    i18nApplyListeners.push(listener);
  }
}

export function getTranslation(key, lang = getLang()){
  const normalizedLang = lang === 'en' ? 'en' : 'pl';
  return (i18n[normalizedLang] && i18n[normalizedLang][key])
    || (i18n.pl && i18n.pl[key])
    || '';
}

function parseStructuredTranslation(template){
  if(typeof template !== 'string'){
    return template;
  }

  const normalized = template.trim();
  if(normalized === '' || (!normalized.startsWith('[') && !normalized.startsWith('{'))){
    return template;
  }

  try{
    return JSON.parse(normalized);
  }catch(_error){
    return template;
  }
}

function interpolateTranslation(template, element){
  if(typeof template !== 'string' || !(element instanceof Element)){
    return template;
  }

  return template.replace(/\{\{\s*([a-zA-Z0-9_-]+)\s*\}\}/g, (_match, parameterName)=>{
    return element.getAttribute(`data-i18n-param-${parameterName}`) || '';
  });
}

function applyLangVisibility(lang){
  qsa('[data-lang]').forEach((element)=>{
    const elementLang = element.getAttribute('data-lang');
    element.style.display = elementLang === lang ? '' : 'none';
  });
}

async function typeTerminal(root, lines, renderId){
  for(const line of lines){
    if(renderId !== terminalRenderId) return;

    const row = document.createElement('div');
    row.className = 'line';

    const prompt = document.createElement('span');
    prompt.className = 'prompt';
    prompt.textContent = line.t === 'info' ? '> ' : '  ';

    const text = document.createElement('span');
    text.className = line.t === 'info' ? 'cmd' : 'out';

    row.appendChild(prompt);
    row.appendChild(text);
    root.appendChild(row);
    root.scrollTop = root.scrollHeight;

    const value = line.v;
    for(let index = 0; index < value.length; index += 1){
      if(renderId !== terminalRenderId) return;
      text.textContent += value[index];
      root.scrollTop = root.scrollHeight;
      await sleep(12 + Math.random() * 18);
    }

    if(renderId !== terminalRenderId) return;
    await sleep(180);
  }

  if(renderId !== terminalRenderId) return;

  const cursor = document.createElement('span');
  cursor.className = 'cursor';
  root.appendChild(cursor);
  root.scrollTop = root.scrollHeight;
}

function resolveTerminalLines(lang){
  const structuredLines = parseStructuredTranslation(getTranslation('term_lines', lang));

  return Array.isArray(structuredLines) ? structuredLines : [];
}

function renderI18n(normalizedLang){
  const translations = i18n[normalizedLang] || i18n.pl || {};
  document.documentElement.lang = normalizedLang;
  applyLangVisibility(normalizedLang);

  const pageTitleKey = document.body?.getAttribute('data-page-title-i18n');
  if(pageTitleKey && translations[pageTitleKey] !== undefined){
    document.title = interpolateTranslation(translations[pageTitleKey], document.body);
  }

  qsa('[data-i18n]').forEach((element)=>{
    const key = element.getAttribute('data-i18n');
    if(translations[key] !== undefined){
      element.textContent = interpolateTranslation(translations[key], element);
    }
  });

  qsa('[data-i18n-title]').forEach((element)=>{
    const key = element.getAttribute('data-i18n-title');
    if(translations[key] !== undefined){
      const translation = interpolateTranslation(translations[key], element);
      element.setAttribute('title', translation);
      element.setAttribute('aria-label', translation);
    }
  });

  qsa('[data-i18n-aria]').forEach((element)=>{
    const key = element.getAttribute('data-i18n-aria');
    if(translations[key] !== undefined){
      element.setAttribute('aria-label', interpolateTranslation(translations[key], element));
    }
  });

  qsa('[data-i18n-tooltip]').forEach((element)=>{
    const key = element.getAttribute('data-i18n-tooltip');
    if(translations[key] !== undefined){
      const translation = interpolateTranslation(translations[key], element);
      if(element.hasAttribute('data-suspended-tooltip')){
        element.setAttribute('data-suspended-tooltip', translation);
      }else{
        element.setAttribute('data-tooltip', translation);
      }
      element.removeAttribute('title');
    }
  });

  qsa('[data-i18n-placeholder]').forEach((element)=>{
    const key = element.getAttribute('data-i18n-placeholder');
    if(translations[key] !== undefined){
      element.setAttribute('placeholder', interpolateTranslation(translations[key], element));
    }
  });

  qsa('[data-menu-label-pl][data-menu-label-en]').forEach((element)=>{
    const nextLabel = normalizedLang === 'en'
      ? element.getAttribute('data-menu-label-en')
      : element.getAttribute('data-menu-label-pl');

    if(nextLabel){
      element.textContent = nextLabel;
    }
  });

  const terminal = qs('#terminal');
  if(terminal){
    terminal.innerHTML = '';
    terminalRenderId += 1;
    typeTerminal(terminal, resolveTerminalLines(normalizedLang), terminalRenderId);
  }

  qsa('[data-action="toggle-lang"]').forEach((button)=>{
    button.textContent = normalizedLang === 'pl' ? 'PL' : 'EN';
  });

  i18nApplyListeners.forEach((listener)=> listener(normalizedLang));
}

export async function applyI18n(lang){
  const normalizedLang = lang === 'en' ? 'en' : 'pl';
  const applyId = ++i18nApplyId;
  const hadTranslations = hasLoadedTranslations(normalizedLang);

  renderI18n(normalizedLang);

  if(hadTranslations){
    return;
  }

  await ensureTranslations(normalizedLang);

  if(applyId !== i18nApplyId){
    return;
  }

  renderI18n(normalizedLang);
}
