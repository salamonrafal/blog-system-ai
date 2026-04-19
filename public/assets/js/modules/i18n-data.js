const validationI18n = globalThis.__APP_VALIDATION_I18N__ ?? { pl: {}, en: {} };
const appI18n = globalThis.__APP_I18N__ ?? { pl: {}, en: {} };

export const i18n = {
  pl: {
    ...appI18n.pl,
    ...validationI18n.pl,
  },
  en: {
    ...appI18n.en,
    ...validationI18n.en,
  },
};
