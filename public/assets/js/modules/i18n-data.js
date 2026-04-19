const initialLocale = globalThis.__APP_I18N_ACTIVE_LOCALE__ === 'en' ? 'en' : 'pl';
const initialMessages = globalThis.__APP_I18N_INITIAL__ ?? {};
const urlTemplate = typeof globalThis.__APP_I18N_URL_TEMPLATE__ === 'string'
  ? globalThis.__APP_I18N_URL_TEMPLATE__
  : '/i18n/__LANG__.json';

export const i18n = {
  [initialLocale]: initialMessages,
};

const pendingLoads = new Map();

function normalizeLanguage(lang){
  return lang === 'en' ? 'en' : 'pl';
}

function buildCatalogUrl(lang){
  return urlTemplate.replace('__LANG__', normalizeLanguage(lang));
}

export async function ensureTranslations(lang){
  const normalizedLang = normalizeLanguage(lang);

  if(i18n[normalizedLang]){
    return i18n[normalizedLang];
  }

  if(pendingLoads.has(normalizedLang)){
    return pendingLoads.get(normalizedLang);
  }

  const loadPromise = fetch(buildCatalogUrl(normalizedLang), {
    headers: {
      Accept: 'application/json',
    },
    credentials: 'same-origin',
  })
    .then(async (response)=>{
      if(!response.ok){
        throw new Error(`Failed to load translations for ${normalizedLang}`);
      }

      const messages = await response.json();
      i18n[normalizedLang] = messages && typeof messages === 'object' ? messages : {};

      return i18n[normalizedLang];
    })
    .catch(()=>{
      i18n[normalizedLang] = {};
      return i18n[normalizedLang];
    })
    .finally(()=>{
      pendingLoads.delete(normalizedLang);
    });

  pendingLoads.set(normalizedLang, loadPromise);

  return loadPromise;
}
