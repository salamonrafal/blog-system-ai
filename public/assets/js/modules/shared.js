export function qs(sel, root = document){
  return root.querySelector(sel);
}

export function qsa(sel, root = document){
  return [...root.querySelectorAll(sel)];
}

function getScrollLockCount(doc){
  const parsedCount = Number(doc.dataset.scrollLockCount);
  return Number.isFinite(parsedCount) && parsedCount > 0 ? parsedCount : 0;
}

export function lockDocumentScroll(){
  const doc = document.documentElement;
  const body = document.body;
  if(!body) return;

  const currentLocks = getScrollLockCount(doc);
  if(currentLocks === 0){
    doc.dataset.scrollLockHtmlOverflow = doc.style.overflow || '';
    doc.dataset.scrollLockBodyOverflow = body.style.overflow || '';
    doc.style.overflow = 'hidden';
    body.style.overflow = 'hidden';
  }

  doc.dataset.scrollLockCount = String(currentLocks + 1);
}

export function unlockDocumentScroll(){
  const doc = document.documentElement;
  const body = document.body;
  if(!body) return;

  const currentLocks = getScrollLockCount(doc);
  if(currentLocks === 0) return;

  if(currentLocks <= 1){
    doc.style.overflow = doc.dataset.scrollLockHtmlOverflow || '';
    body.style.overflow = doc.dataset.scrollLockBodyOverflow || '';
    delete doc.dataset.scrollLockCount;
    delete doc.dataset.scrollLockHtmlOverflow;
    delete doc.dataset.scrollLockBodyOverflow;
    return;
  }

  doc.dataset.scrollLockCount = String(currentLocks - 1);
}

export function hideAppTooltip({ blurTarget = null } = {}){
  document.dispatchEvent(new Event('app:hide-tooltip'));
  if(blurTarget instanceof HTMLElement){
    blurTarget.blur();
  }
}

export function suspendElementTooltip(element){
  if(!(element instanceof HTMLElement)) return;

  const tooltip = element.getAttribute('data-tooltip');
  if(tooltip !== null){
    element.setAttribute('data-suspended-tooltip', tooltip);
    element.removeAttribute('data-tooltip');
  }
}

export function restoreElementTooltip(element){
  if(!(element instanceof HTMLElement)) return;

  const tooltip = element.getAttribute('data-suspended-tooltip');
  if(tooltip === null) return;

  if(element.getAttribute('data-tooltip') === null){
    element.setAttribute('data-tooltip', tooltip);
  }
  element.removeAttribute('data-suspended-tooltip');
}

export function sleep(ms){
  return new Promise((resolve)=> setTimeout(resolve, ms));
}

export function copyTextToClipboard(text){
  if(navigator.clipboard && window.isSecureContext){
    return navigator.clipboard.writeText(text).then(()=> true).catch(()=> false);
  }

  const textarea = document.createElement('textarea');
  textarea.value = text;
  textarea.setAttribute('readonly', '');
  textarea.style.position = 'fixed';
  textarea.style.top = '-9999px';
  textarea.style.left = '-9999px';
  document.body.appendChild(textarea);
  textarea.select();
  textarea.setSelectionRange(0, textarea.value.length);

  let copied = false;
  try{
    copied = document.execCommand('copy');
  }catch(err){
    copied = false;
  }

  document.body.removeChild(textarea);
  return Promise.resolve(copied);
}

export function normalizeHexColor(value){
  if(typeof value !== 'string') return null;
  const normalizedValue = value.trim().toLowerCase();
  return /^#[0-9a-f]{6}$/.test(normalizedValue) ? normalizedValue : null;
}

export function hexToRgb(hex){
  const normalizedValue = normalizeHexColor(hex);
  if(!normalizedValue) return null;

  return {
    r: parseInt(normalizedValue.slice(1, 3), 16),
    g: parseInt(normalizedValue.slice(3, 5), 16),
    b: parseInt(normalizedValue.slice(5, 7), 16),
  };
}

export function getContrastColor(rgb){
  if(!rgb) return '#0d1520';

  const toLinear = (channel)=>{
    const value = channel / 255;
    return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
  };

  const luminance = (0.2126 * toLinear(rgb.r))
    + (0.7152 * toLinear(rgb.g))
    + (0.0722 * toLinear(rgb.b));

  return luminance > 0.45 ? '#0d1520' : '#f6f8fb';
}

export function getBrowserTimeZone(){
  try{
    const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    return typeof timeZone === 'string' && timeZone.trim() !== '' ? timeZone.trim() : null;
  }catch(err){
    return null;
  }
}

export function formatDateTime(value, {
  locale,
  options = {},
} = {}){
  if(!value) return '';

  const date = value instanceof Date ? value : new Date(value);
  if(Number.isNaN(date.getTime())) return '';

  const preferredLocale = typeof locale === 'string' && locale.trim() !== ''
    ? locale.trim()
    : document?.documentElement?.lang || navigator.language || undefined;

  try{
    return new Intl.DateTimeFormat(preferredLocale, options).format(date);
  }catch(err){
    try{
      return new Intl.DateTimeFormat(undefined, options).format(date);
    }catch(formatError){
      return '';
    }
  }
}

export function assignFilesToInput(input, files){
  if(!(input instanceof HTMLInputElement) || !(files instanceof FileList) || files.length === 0){
    return false;
  }

  if(typeof DataTransfer === 'function'){
    try{
      const dataTransfer = new DataTransfer();
      [...files].forEach((file)=>{
        dataTransfer.items.add(file);
      });
      input.files = dataTransfer.files;
      return input.files instanceof FileList && input.files.length > 0;
    }catch(err){
    }
  }

  try{
    input.files = files;
    return input.files instanceof FileList && input.files.length > 0;
  }catch(err){
    return false;
  }
}
