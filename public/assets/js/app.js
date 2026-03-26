import {
  setupAdminShortcuts,
  setupArticleBulkExport,
  setupCharacterCounters,
  setupDashboardCarousels,
  setupDeleteConfirmation,
  setupExportClearConfirmation,
  setupExportDeleteConfirmation,
  setupHeadlineImageToggle,
  setupImportClearConfirmation,
  setupImportDeleteConfirmation,
  setupQueueClearConfirmation,
  setupUserDeleteConfirmation,
} from './modules/admin.js';
import { setupArticleMarkupEditor } from './modules/editor.js';
import { applyI18n } from './modules/i18n.js';
import { setupActions } from './modules/interactions.js';
import { setupBackToTop, setupFlashNotices, setupNav, setupTooltips, syncTopbarHeight } from './modules/layout.js';
import { getAccent, getLang, getTheme, persistUserLanguage, persistUserTimeZone, setAccent, setTheme } from './modules/preferences.js';
import { setupImagePreview } from './modules/preview.js';
import { setupPrivacyNotice } from './modules/privacy.js';

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
  setupActions({ applyI18n });
  setupCharacterCounters();
  setupHeadlineImageToggle();
  setupDashboardCarousels();
  setupArticleBulkExport();
  setupArticleMarkupEditor();
  setupImagePreview();
  setupDeleteConfirmation();
  setupUserDeleteConfirmation();
  setupExportDeleteConfirmation();
  setupExportClearConfirmation();
  setupImportDeleteConfirmation();
  setupImportClearConfirmation();
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
