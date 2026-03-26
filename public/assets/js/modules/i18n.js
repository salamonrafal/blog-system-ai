import { i18n } from './i18n-data.js';
import { getLang } from './preferences.js';
import { qs, qsa, sleep } from './shared.js';

const i18nApplyListeners = [];
let terminalRenderId = 0;

export { i18n };

export function registerI18nListener(listener){
  if(typeof listener === 'function'){
    i18nApplyListeners.push(listener);
  }
}

export function getTranslation(key, lang = getLang()){
  return (i18n[lang] && i18n[lang][key]) || i18n.pl[key] || '';
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

export function applyI18n(lang){
  const translations = i18n[lang] || i18n.pl;
  document.documentElement.lang = lang;
  applyLangVisibility(lang);

  qsa('[data-i18n]').forEach((element)=>{
    const key = element.getAttribute('data-i18n');
    if(translations[key] !== undefined){
      element.textContent = translations[key];
    }
  });

  qsa('[data-i18n-title]').forEach((element)=>{
    const key = element.getAttribute('data-i18n-title');
    if(translations[key] !== undefined){
      element.setAttribute('title', translations[key]);
      element.setAttribute('aria-label', translations[key]);
    }
  });

  qsa('[data-i18n-aria]').forEach((element)=>{
    const key = element.getAttribute('data-i18n-aria');
    if(translations[key] !== undefined){
      element.setAttribute('aria-label', translations[key]);
    }
  });

  qsa('[data-i18n-tooltip]').forEach((element)=>{
    const key = element.getAttribute('data-i18n-tooltip');
    if(translations[key] !== undefined){
      element.setAttribute('data-tooltip', translations[key]);
      element.removeAttribute('title');
    }
  });

  qsa('[data-i18n-placeholder]').forEach((element)=>{
    const key = element.getAttribute('data-i18n-placeholder');
    if(translations[key] !== undefined){
      element.setAttribute('placeholder', translations[key]);
    }
  });

  const terminal = qs('#terminal');
  if(terminal){
    terminal.innerHTML = '';
    terminalRenderId += 1;
    typeTerminal(terminal, translations.term_lines, terminalRenderId);
  }

  qsa('[data-action="toggle-lang"]').forEach((button)=>{
    button.textContent = lang === 'pl' ? 'PL' : 'EN';
  });

  i18nApplyListeners.forEach((listener)=> listener(lang));
}
