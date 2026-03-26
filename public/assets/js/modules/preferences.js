import { getBrowserTimeZone, getContrastColor, hexToRgb, normalizeHexColor, qsa } from './shared.js';

const ADMIN_DEVICE_STORAGE_KEY = 'admin_device_remembered';
const USER_LANGUAGE_COOKIE_NAME = 'user_language';
const USER_TIMEZONE_COOKIE_NAME = 'user_timezone';

export function getLang(){
  const storedLang = localStorage.getItem('lang');
  if(storedLang) return storedLang;

  const navigatorLanguage = (navigator.language || 'pl').toLowerCase();
  return navigatorLanguage.startsWith('pl') ? 'pl' : 'en';
}

export function persistUserLanguage(lang){
  const normalizedLang = lang === 'en' ? 'en' : 'pl';
  const secure = window.location.protocol === 'https:' ? '; Secure' : '';
  document.cookie = `${USER_LANGUAGE_COOKIE_NAME}=${encodeURIComponent(normalizedLang)}; Max-Age=31536000; Path=/; SameSite=Lax${secure}`;
}

export function setLangPreference(lang){
  localStorage.setItem('lang', lang);
  persistUserLanguage(lang);
}

export function getTheme(){
  return localStorage.getItem('theme') || 'dark';
}

export function getAccent(){
  return localStorage.getItem('accent') || '#39ff14';
}

export function setTheme(theme){
  localStorage.setItem('theme', theme);
  document.documentElement.setAttribute('data-theme', theme);

  qsa('[data-action="toggle-theme"]').forEach((button)=>{
    button.textContent = theme === 'dark' ? '🌙' : '☀️';
  });
}

export function setAccent(color){
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

  qsa('[data-action="accent-color"]').forEach((colorInput)=>{
    colorInput.value = accent;
  });
}

export function isAdminDeviceRemembered(){
  return localStorage.getItem(ADMIN_DEVICE_STORAGE_KEY) === '1';
}

export function setAdminDeviceRemembered(remembered){
  if(remembered){
    localStorage.setItem(ADMIN_DEVICE_STORAGE_KEY, '1');
    return;
  }

  localStorage.removeItem(ADMIN_DEVICE_STORAGE_KEY);
}

export function persistUserTimeZone(){
  const timeZone = getBrowserTimeZone();
  if(!timeZone) return;

  const secure = window.location.protocol === 'https:' ? '; Secure' : '';
  document.cookie = `${USER_TIMEZONE_COOKIE_NAME}=${encodeURIComponent(timeZone)}; Max-Age=31536000; Path=/; SameSite=Lax${secure}`;
}
