import {
  setupAdminShortcuts,
  setupAdminListingFilters,
  setupArticleBulkExport,
  setupCharacterCounters,
  setupDashboardCarousels,
  setupDeleteConfirmation,
  setupCategoryDeleteConfirmation,
  setupCategoryTranslationCopy,
  setupExportClearConfirmation,
  setupExportDeleteConfirmation,
  setupHeadlineImageToggle,
  setupImportClearConfirmation,
  setupImportDeleteConfirmation,
  setupImportMessageDialog,
  setupQueueClearConfirmation,
  setupTopMenuDeleteConfirmation,
  setupTopMenuTargetTabs,
  setupTranslationTabs,
  setupUserDeleteConfirmation,
} from './modules/admin.js';
import { setupArticleMarkupEditor } from './modules/editor.js';
import { applyI18n } from './modules/i18n.js';
import { setupActions } from './modules/interactions.js';
import { setupBackToTop, setupBlogCategoryScroller, setupFlashNotices, setupNav, setupTooltips, syncTopbarHeight } from './modules/layout.js';
import { getAccent, getLang, getTheme, persistUserLanguage, persistUserTimeZone, setAccent, setTheme } from './modules/preferences.js';
import { setupImagePreview } from './modules/preview.js';
import { setupPrivacyNotice } from './modules/privacy.js';

function init(){
  persistUserLanguage(getLang());
  persistUserTimeZone();
  setupNav();
  setupBlogCategoryScroller();
  setupBackToTop();
  setTheme(getTheme());
  setAccent(getAccent());
  setupPrivacyNotice();
  const lang = getLang();
  applyI18n(lang);
  setupTooltips();
  setupFlashNotices();
  setupAdminShortcuts();
  setupActions({ applyI18n });
  setupCharacterCounters();
  setupHeadlineImageToggle();
  setupTranslationTabs();
  setupTopMenuTargetTabs();
  setupCategoryTranslationCopy();
  setupDashboardCarousels();
  setupArticleBulkExport();
  setupAdminListingFilters();
  setupArticleMarkupEditor();
  setupImagePreview();
  setupDeleteConfirmation();
  setupCategoryDeleteConfirmation();
  setupTopMenuDeleteConfirmation();
  setupUserDeleteConfirmation();
  setupExportDeleteConfirmation();
  setupExportClearConfirmation();
  setupImportDeleteConfirmation();
  setupImportClearConfirmation();
  setupImportMessageDialog();
  setupQueueClearConfirmation();
  syncTopbarHeight();

  window.addEventListener('resize', syncTopbarHeight);
  window.addEventListener('orientationchange', syncTopbarHeight);
  if(window.visualViewport){
    window.visualViewport.addEventListener('resize', syncTopbarHeight);
  }
}

document.addEventListener('DOMContentLoaded', init);
