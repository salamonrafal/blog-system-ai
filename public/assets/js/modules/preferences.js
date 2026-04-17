import { getBrowserTimeZone, getContrastColor, hexToRgb, normalizeHexColor, qsa } from './shared.js';

const ADMIN_DEVICE_STORAGE_KEY = 'admin_device_remembered';
const ADMIN_SHORTCUTS_DOCKED_STORAGE_KEY = 'admin_shortcuts_docked';
const ADMIN_SHORTCUTS_COLLAPSED_STORAGE_KEY = 'admin_shortcuts_collapsed';
const USER_LANGUAGE_COOKIE_NAME = 'user_language';
const USER_THEME_COOKIE_NAME = 'user_theme';
const USER_ACCENT_COOKIE_NAME = 'user_accent';
const USER_TIMEZONE_COOKIE_NAME = 'user_timezone';
const COOKIE_MAX_AGE = 31536000;
const DEFAULT_THEME = 'dark';
const DEFAULT_ACCENT = '#39ff14';

function normalizeLanguage(lang){
  return lang === 'en' ? 'en' : 'pl';
}

function normalizeTheme(theme){
  return theme === 'light' ? 'light' : 'dark';
}

function getPreferenceCookieDomain(){
  const domain = document.documentElement?.dataset?.preferenceCookieDomain;
  if(typeof domain !== 'string' || domain.trim() === ''){
    return null;
  }

  const normalizedDomain = domain.trim().toLowerCase().replace(/\.+$/, '');
  const normalizedHostname = window.location.hostname.trim().toLowerCase().replace(/\.+$/, '');
  if(normalizedDomain === '' || normalizedHostname === ''){
    return null;
  }

  const bareDomain = normalizedDomain.replace(/^\./, '');
  if(!/^[a-z0-9.-]+$/.test(bareDomain) || bareDomain.includes('..') || !bareDomain.includes('.')){
    return null;
  }

  const normalizedCookieDomain = `.${bareDomain}`;
  if(normalizedHostname !== bareDomain && !normalizedHostname.endsWith(normalizedCookieDomain)){
    return null;
  }

  return normalizedCookieDomain;
}

function readCookie(name){
  const escapedName = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const match = document.cookie.match(new RegExp(`(?:^|; )${escapedName}=([^;]*)`));

  return match ? decodeURIComponent(match[1]) : null;
}

function persistCookie(name, value, { shareAcrossSubdomains = false } = {}){
  const secure = window.location.protocol === 'https:' ? '; Secure' : '';
  const domain = shareAcrossSubdomains ? getPreferenceCookieDomain() : null;
  const domainAttribute = domain ? `; Domain=${domain}` : '';

  document.cookie = `${name}=${encodeURIComponent(value)}; Max-Age=${COOKIE_MAX_AGE}; Path=/; SameSite=Lax${domainAttribute}${secure}`;
}

export function getLang(){
  const storedLang = readCookie(USER_LANGUAGE_COOKIE_NAME);
  if(storedLang){
    const normalizedStoredLang = normalizeLanguage(storedLang);
    return normalizedStoredLang;
  }

  const navigatorLanguage = (navigator.language || 'pl').toLowerCase();
  return normalizeLanguage(navigatorLanguage.startsWith('pl') ? 'pl' : 'en');
}

export function persistUserLanguage(lang){
  const normalizedLang = normalizeLanguage(lang);
  persistCookie(USER_LANGUAGE_COOKIE_NAME, normalizedLang, { shareAcrossSubdomains: true });
}

export function setLangPreference(lang){
  const normalizedLang = normalizeLanguage(lang);
  persistUserLanguage(normalizedLang);
}

export function getTheme(){
  return normalizeTheme(readCookie(USER_THEME_COOKIE_NAME) || DEFAULT_THEME);
}

export function getAccent(){
  return normalizeHexColor(readCookie(USER_ACCENT_COOKIE_NAME)) || DEFAULT_ACCENT;
}

export function setTheme(theme){
  const normalizedTheme = normalizeTheme(theme);

  persistCookie(USER_THEME_COOKIE_NAME, normalizedTheme, { shareAcrossSubdomains: true });
  document.documentElement.setAttribute('data-theme', normalizedTheme);

  qsa('[data-action="toggle-theme"]').forEach((button)=>{
    button.textContent = normalizedTheme === 'dark' ? '🌙' : '☀️';
  });
}

export function setAccent(color){
  const accent = normalizeHexColor(color) || DEFAULT_ACCENT;
  const rgb = hexToRgb(accent);
  const accentContrast = getContrastColor(rgb);

  persistCookie(USER_ACCENT_COOKIE_NAME, accent, { shareAcrossSubdomains: true });
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

export function isAdminShortcutsDocked(){
  return localStorage.getItem(ADMIN_SHORTCUTS_DOCKED_STORAGE_KEY) === '1';
}

export function setAdminShortcutsDocked(docked){
  if(docked){
    localStorage.setItem(ADMIN_SHORTCUTS_DOCKED_STORAGE_KEY, '1');
    return;
  }

  localStorage.removeItem(ADMIN_SHORTCUTS_DOCKED_STORAGE_KEY);
}

export function isAdminShortcutsCollapsed(){
  return localStorage.getItem(ADMIN_SHORTCUTS_COLLAPSED_STORAGE_KEY) === '1';
}

export function setAdminShortcutsCollapsed(collapsed){
  if(collapsed){
    localStorage.setItem(ADMIN_SHORTCUTS_COLLAPSED_STORAGE_KEY, '1');
    return;
  }

  localStorage.removeItem(ADMIN_SHORTCUTS_COLLAPSED_STORAGE_KEY);
}

export function persistUserTimeZone(){
  const timeZone = getBrowserTimeZone();
  if(!timeZone) return;

  persistCookie(USER_TIMEZONE_COOKIE_NAME, timeZone);
}
