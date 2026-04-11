import {
  setupAdminShortcuts,
  setupAdminListingFilters,
  setupArticleKeywordDeleteConfirmation,
  setupArticleBulkExport,
  setupCharacterCounters,
  setupDashboardCarousels,
  setupDeleteConfirmation,
  setupCategoryDeleteConfirmation,
  setupCategoryTranslationCopy,
  setupExportClearConfirmation,
  setupExportDeleteConfirmation,
  setupHeadlineImageToggle,
  setupHeadlineImagePicker,
  setupImportClearConfirmation,
  setupImportDeleteConfirmation,
  setupImportMessageDialog,
  setupMediaGalleryDropSlot,
  setupMediaGallerySorting,
  setupMediaRenameDialog,
  setupOptionalColorFields,
  setupQueueClearConfirmation,
  setupTopMenuDeleteConfirmation,
  setupTopMenuTreeSorting,
  setupTopMenuTargetTabs,
  setupTranslationTabs,
  setupUserAvatarPreview,
  setupUserDeleteConfirmation,
} from './modules/admin.js';
import { setupCustomSelects } from './modules/custom-select.js';
import { setupFileUploads } from './modules/file-upload.js';
import { setupOptionLists } from './modules/option-list.js';
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
  setupCustomSelects();
  setupFileUploads();
  setupOptionLists();
  setupActions({ applyI18n });
  setupCharacterCounters();
  setupHeadlineImageToggle();
  setupHeadlineImagePicker();
  setupOptionalColorFields();
  setupTranslationTabs();
  setupTopMenuTargetTabs();
  setupTopMenuTreeSorting();
  setupCategoryTranslationCopy();
  setupDashboardCarousels();
  setupArticleBulkExport();
  setupAdminListingFilters();
  setupArticleMarkupEditor();
  setupImagePreview();
  setupDeleteConfirmation();
  setupArticleKeywordDeleteConfirmation();
  setupCategoryDeleteConfirmation();
  setupTopMenuDeleteConfirmation();
  setupUserAvatarPreview();
  setupUserDeleteConfirmation();
  setupExportDeleteConfirmation();
  setupExportClearConfirmation();
  setupImportDeleteConfirmation();
  setupImportClearConfirmation();
  setupImportMessageDialog();
  setupMediaGalleryDropSlot();
  setupMediaGallerySorting();
  setupMediaRenameDialog();
  setupQueueClearConfirmation();
  syncTopbarHeight();

  window.addEventListener('resize', syncTopbarHeight);
  window.addEventListener('orientationchange', syncTopbarHeight);
  if(window.visualViewport){
    window.visualViewport.addEventListener('resize', syncTopbarHeight);
  }
}

document.addEventListener('DOMContentLoaded', init);
