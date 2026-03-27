import { applyI18n, getTranslation, registerI18nListener } from './i18n.js';
import { getLang, isAdminDeviceRemembered, setAdminDeviceRemembered } from './preferences.js';
import { qs, qsa } from './shared.js';

export function syncAdminShortcuts(){
  qsa('[data-admin-shortcuts]').forEach((menu)=>{
    const shortcuts = menu.closest('.admin-shortcuts');
    if(!shortcuts) return;

    const isAuthenticated = menu.getAttribute('data-authenticated') === 'true';
    if(!isAuthenticated){
      shortcuts.hidden = !isAdminDeviceRemembered();
      if(!isAdminDeviceRemembered()){
        shortcuts.removeAttribute('open');
      }
      return;
    }

    const rememberButton = qs('[data-action="toggle-device-login"]', menu);
    if(!rememberButton) return;

    const remembered = isAdminDeviceRemembered();
    const label = qs('[data-device-remember-label]', rememberButton);
    const labelKey = remembered ? 'admin_shortcut_forget_device' : 'admin_shortcut_remember_device';

    rememberButton.classList.toggle('is-active', remembered);
    rememberButton.setAttribute('aria-pressed', remembered ? 'true' : 'false');

    if(label){
      label.setAttribute('data-i18n', labelKey);
      label.textContent = getTranslation(labelKey);
    }
  });
}

export function setupAdminShortcuts(){
  registerI18nListener(syncAdminShortcuts);
  syncAdminShortcuts();

  const closeAdminSubmenu = (submenu)=>{
    if(!submenu) return;
    submenu.classList.remove('is-open');
    const trigger = qs('[data-action="toggle-admin-submenu"]', submenu);
    if(trigger){
      trigger.setAttribute('aria-expanded', 'false');
    }
  };

  const openAdminSubmenu = (submenu)=>{
    if(!submenu) return;
    submenu.classList.add('is-open');
    const trigger = qs('[data-action="toggle-admin-submenu"]', submenu);
    if(trigger){
      trigger.setAttribute('aria-expanded', 'true');
    }
  };

  qsa('[data-admin-shortcuts-submenu]').forEach((submenu)=>{
    const trigger = qs('[data-action="toggle-admin-submenu"]', submenu);
    if(!trigger) return;

    trigger.addEventListener('click', (event)=>{
      event.preventDefault();
      const isOpen = submenu.classList.contains('is-open');

      qsa('[data-admin-shortcuts-submenu].is-open').forEach((openSubmenu)=>{
        if(openSubmenu !== submenu){
          closeAdminSubmenu(openSubmenu);
        }
      });

      if(isOpen){
        closeAdminSubmenu(submenu);
      }else{
        openAdminSubmenu(submenu);
      }
    });
  });

  qsa('[data-action="toggle-device-login"]').forEach((button)=>{
    button.addEventListener('click', ()=>{
      setAdminDeviceRemembered(!isAdminDeviceRemembered());
      syncAdminShortcuts();
    });
  });

  document.addEventListener('click', (event)=>{
    qsa('[data-admin-shortcuts-submenu].is-open').forEach((submenu)=>{
      if(!submenu.contains(event.target)){
        closeAdminSubmenu(submenu);
      }
    });
  });

  document.addEventListener('keydown', (event)=>{
    if(event.key !== 'Escape') return;

    qsa('[data-admin-shortcuts-submenu].is-open').forEach((submenu)=>{
      closeAdminSubmenu(submenu);
    });
  });
}

export function setupCharacterCounters(){
  qsa('[data-character-count-input]').forEach((input)=>{
    const field = input.closest('.article-editor-field');
    const counter = field ? qs('[data-character-count-target]', field) : null;
    if(!counter) return;

    const maxLength = Number(input.getAttribute('maxlength'));
    if(!Number.isFinite(maxLength) || maxLength <= 0) return;

    const updateCount = ()=>{
      counter.textContent = `${input.value.length} / ${maxLength}`;
    };

    updateCount();
    input.addEventListener('input', updateCount);
  });
}

export function setupHeadlineImageToggle(){
  qsa('[data-headline-image-toggle]').forEach((toggle)=>{
    const section = toggle.closest('[data-headline-image-section]');
    const panel = section ? qs('[data-headline-image-panel]', section) : null;
    if(!panel) return;

    const syncVisibility = ()=>{
      const isEnabled = Boolean(toggle.checked);
      panel.hidden = !isEnabled;
      panel.classList.toggle('is-hidden', !isEnabled);
      toggle.setAttribute('aria-expanded', isEnabled ? 'true' : 'false');
    };

    syncVisibility();
    toggle.addEventListener('change', syncVisibility);
  });
}

export function setupDashboardCarousels(){
  qsa('[data-dashboard-carousel]').forEach((carousel)=>{
    const tabs = qsa('[data-dashboard-carousel-tab]', carousel);
    const panels = qsa('[data-dashboard-carousel-panel]', carousel);
    if(!tabs.length || !panels.length) return;

    const activateTab = (name)=>{
      tabs.forEach((tab)=>{
        const isActive = tab.getAttribute('data-dashboard-carousel-tab') === name;
        tab.classList.toggle('is-active', isActive);
        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        tab.setAttribute('tabindex', isActive ? '0' : '-1');
      });

      panels.forEach((panel)=>{
        const isActive = panel.getAttribute('data-dashboard-carousel-panel') === name;
        panel.classList.toggle('is-active', isActive);
        panel.hidden = !isActive;
      });
    };

    tabs.forEach((tab)=>{
      tab.addEventListener('click', ()=>{
        activateTab(tab.getAttribute('data-dashboard-carousel-tab') || '');
      });

      tab.addEventListener('keydown', (event)=>{
        if(event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;

        event.preventDefault();
        const currentIndex = tabs.indexOf(tab);
        const direction = event.key === 'ArrowRight' ? 1 : -1;
        const nextIndex = (currentIndex + direction + tabs.length) % tabs.length;
        const nextTab = tabs[nextIndex];
        if(!nextTab) return;

        activateTab(nextTab.getAttribute('data-dashboard-carousel-tab') || '');
        nextTab.focus({ preventScroll: true });
      });
    });
  });
}

export function setupTranslationTabs(){
  qsa('[data-translation-tabs]').forEach((tabsRoot)=>{
    const tabs = qsa('[data-translation-tab]', tabsRoot);
    const panels = qsa('[data-translation-panel]', tabsRoot);
    if(!tabs.length || !panels.length) return;

    const activateTab = (name)=>{
      tabs.forEach((tab)=>{
        const isActive = tab.getAttribute('data-translation-tab') === name;
        tab.classList.toggle('is-active', isActive);
        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        tab.setAttribute('tabindex', isActive ? '0' : '-1');
      });

      panels.forEach((panel)=>{
        const isActive = panel.getAttribute('data-translation-panel') === name;
        panel.classList.toggle('is-active', isActive);
        panel.hidden = !isActive;
      });
    };

    tabs.forEach((tab)=>{
      tab.addEventListener('click', ()=>{
        activateTab(tab.getAttribute('data-translation-tab') || '');
      });

      tab.addEventListener('keydown', (event)=>{
        if(event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;

        event.preventDefault();
        const currentIndex = tabs.indexOf(tab);
        const direction = event.key === 'ArrowRight' ? 1 : -1;
        const nextIndex = (currentIndex + direction + tabs.length) % tabs.length;
        const nextTab = tabs[nextIndex];
        if(!nextTab) return;

        activateTab(nextTab.getAttribute('data-translation-tab') || '');
        nextTab.focus({ preventScroll: true });
      });
    });
  });
}

export function setupCategoryTranslationCopy(){
  qsa('[data-translation-tabs]').forEach((tabsRoot)=>{
    const basicTitle = qs('[data-category-basic-title]', tabsRoot);
    const basicDescription = qs('[data-category-basic-description]', tabsRoot);
    if(!basicTitle || !basicDescription) return;

    qsa('[data-action="copy-basic-translation"]', tabsRoot).forEach((button)=>{
      button.addEventListener('click', ()=>{
        const language = button.getAttribute('data-copy-target-language');
        if(!language) return;

        const titleField = qs(`[data-category-translation-title="${language}"]`, tabsRoot);
        const descriptionField = qs(`[data-category-translation-description="${language}"]`, tabsRoot);
        if(!titleField || !descriptionField) return;

        titleField.value = basicTitle.value;
        descriptionField.value = basicDescription.value;
        titleField.dispatchEvent(new Event('input', { bubbles: true }));
        descriptionField.dispatchEvent(new Event('input', { bubbles: true }));
        titleField.dispatchEvent(new Event('change', { bubbles: true }));
        descriptionField.dispatchEvent(new Event('change', { bubbles: true }));
      });
    });
  });
}

export function setupArticleBulkExport(){
  const selectAll = qs('[data-article-select-all]');
  const submit = qs('[data-article-bulk-submit]');
  const checkboxes = qsa('[data-article-select-item]');
  if(!selectAll || !submit || !checkboxes.length) return;

  const syncState = ()=>{
    const checkedCount = checkboxes.filter((checkbox)=> checkbox.checked).length;
    selectAll.checked = checkedCount === checkboxes.length;
    selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
    submit.disabled = checkedCount === 0;
  };

  selectAll.addEventListener('change', ()=>{
    checkboxes.forEach((checkbox)=>{
      checkbox.checked = selectAll.checked;
    });
    syncState();
  });

  checkboxes.forEach((checkbox)=>{
    checkbox.addEventListener('change', syncState);
  });

  syncState();
}

function setupDangerConfirmation(config){
  const triggers = qsa(config.triggerSelector);
  if(!triggers.length) return;

  const existingModal = qs(`.${config.modalClass}`);
  if(existingModal){
    existingModal.remove();
  }

  const titleId = `${config.modalIdPrefix}-title`;
  const textId = `${config.modalIdPrefix}-text`;
  const modal = document.createElement('div');
  modal.className = `confirm-delete-modal ${config.modalClass}`;
  modal.setAttribute('hidden', '');
  modal.setAttribute('aria-hidden', 'true');
  modal.innerHTML = `
    <div class="confirm-delete-dialog" role="alertdialog" aria-modal="false" aria-labelledby="${titleId}" aria-describedby="${textId}">
      <div class="confirm-delete-topbar">
        <div class="confirm-delete-eyebrow">admin://danger-zone</div>
        <button type="button" class="confirm-delete-close" data-action="${config.closeAction}" data-i18n-aria="${config.closeI18n}" aria-label="${config.closeFallback}">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <h2 id="${titleId}" class="confirm-delete-title" data-i18n="${config.titleI18n}">${config.titleFallback}</h2>
      <p id="${textId}" class="confirm-delete-text" data-i18n="${config.textI18n}">${config.textFallback}</p>
      ${config.detailsClass ? `<p class="${config.detailsClass}"></p>` : ''}
      <div class="confirm-delete-actions">
        <button type="button" class="button secondary" data-action="${config.cancelAction}" data-i18n="${config.cancelI18n}">${config.cancelFallback}</button>
        <button type="button" class="button button-danger" data-action="${config.submitAction}" data-i18n="${config.submitI18n}">${config.submitFallback}</button>
      </div>
    </div>
  `;

  document.body.appendChild(modal);
  applyI18n(getLang());

  const dialog = qs('.confirm-delete-dialog', modal);
  const closeButton = qs(`[data-action="${config.closeAction}"]`, modal);
  const cancelButton = qs(`[data-action="${config.cancelAction}"]`, modal);
  const submitButton = qs(`[data-action="${config.submitAction}"]`, modal);
  const details = config.detailsClass ? qs(`.${config.detailsClass}`, modal) : null;
  let activeForm = null;
  let lastTrigger = null;

  const closeModal = ()=>{
    modal.setAttribute('hidden', '');
    modal.setAttribute('aria-hidden', 'true');
    if(lastTrigger){
      lastTrigger.focus({ preventScroll: true });
    }
    activeForm = null;
  };

  const openModal = (trigger)=>{
    activeForm = trigger.closest('form');
    lastTrigger = trigger;

    if(details){
      details.textContent = config.detailsText ? config.detailsText(trigger) : '';
    }

    modal.removeAttribute('hidden');
    modal.setAttribute('aria-hidden', 'false');

    if(closeButton){
      closeButton.focus({ preventScroll: true });
    }
  };

  triggers.forEach((trigger)=>{
    trigger.addEventListener('click', ()=>{
      openModal(trigger);
    });
  });

  if(closeButton){
    closeButton.addEventListener('click', closeModal);
  }

  if(cancelButton){
    cancelButton.addEventListener('click', closeModal);
  }

  if(submitButton){
    submitButton.addEventListener('click', ()=>{
      if(activeForm){
        activeForm.submit();
      }
    });
  }

  if(dialog){
    dialog.addEventListener('click', (event)=>{
      event.stopPropagation();
    });
  }

  document.addEventListener('keydown', (event)=>{
    if(modal.hasAttribute('hidden')) return;
    if(event.key === 'Escape'){
      event.preventDefault();
      closeModal();
    }
  });
}

export function setupDeleteConfirmation(){
  setupDangerConfirmation({
    triggerSelector: '[data-action="confirm-delete-article"]',
    modalClass: 'confirm-delete-article-modal',
    modalIdPrefix: 'confirm-delete-article',
    titleI18n: 'admin_delete_popup_title',
    titleFallback: 'Usunąć artykuł?',
    textI18n: 'admin_delete_popup_text',
    textFallback: 'Ta operacja jest nieodwracalna. Artykuł zostanie trwale usunięty.',
    detailsClass: 'confirm-delete-article-name',
    detailsText: (trigger)=> trigger.getAttribute('data-article-title') || '',
    cancelAction: 'cancel-delete',
    submitAction: 'submit-delete',
    closeAction: 'close-delete',
    cancelI18n: 'admin_delete_popup_cancel',
    cancelFallback: 'Przerwij',
    submitI18n: 'admin_delete_popup_confirm',
    submitFallback: 'Usuń',
    closeI18n: 'admin_close_alert',
    closeFallback: 'Zamknij alert',
  });
}

export function setupUserDeleteConfirmation(){
  setupDangerConfirmation({
    triggerSelector: '[data-action="confirm-delete-user"]',
    modalClass: 'confirm-delete-user-modal',
    modalIdPrefix: 'confirm-delete-user',
    titleI18n: 'admin_users_delete_popup_title',
    titleFallback: 'Usunąć użytkownika?',
    textI18n: 'admin_users_delete_popup_text',
    textFallback: 'Ta operacja usunie konto użytkownika z panelu administracyjnego.',
    detailsClass: 'confirm-delete-user-name',
    detailsText: (trigger)=> trigger.getAttribute('data-user-name') || '',
    cancelAction: 'cancel-delete-user',
    submitAction: 'submit-delete-user',
    closeAction: 'close-delete-user',
    cancelI18n: 'admin_users_delete_popup_cancel',
    cancelFallback: 'Przerwij',
    submitI18n: 'admin_users_delete_popup_confirm',
    submitFallback: 'Usuń użytkownika',
    closeI18n: 'admin_close_alert',
    closeFallback: 'Zamknij alert',
  });
}

export function setupCategoryDeleteConfirmation(){
  setupDangerConfirmation({
    triggerSelector: '[data-action="confirm-delete-category"]',
    modalClass: 'confirm-delete-category-modal',
    modalIdPrefix: 'confirm-delete-category',
    titleI18n: 'admin_categories_delete_popup_title',
    titleFallback: 'Usunąć kategorię?',
    textI18n: 'admin_categories_delete_popup_text',
    textFallback: 'Ta operacja trwale usunie kategorię z panelu administracyjnego.',
    detailsClass: 'confirm-delete-category-name',
    detailsText: (trigger)=> trigger.getAttribute('data-category-name') || '',
    cancelAction: 'cancel-delete-category',
    submitAction: 'submit-delete-category',
    closeAction: 'close-delete-category',
    cancelI18n: 'admin_categories_delete_popup_cancel',
    cancelFallback: 'Przerwij',
    submitI18n: 'admin_categories_delete_popup_confirm',
    submitFallback: 'Usuń kategorię',
    closeI18n: 'admin_close_alert',
    closeFallback: 'Zamknij alert',
  });
}

export function setupQueueClearConfirmation(){
  setupDangerConfirmation({
    triggerSelector: '[data-action="confirm-clear-queue"]',
    modalClass: 'confirm-clear-queue-modal',
    modalIdPrefix: 'confirm-clear-queue',
    titleI18n: 'admin_queue_clear_popup_title',
    titleFallback: 'Wyczyścić kolejkę?',
    textI18n: 'admin_queue_clear_popup_text',
    textFallback: 'Ta operacja usunie wszystkie oczekujące elementy kolejki. Upewnij się, że zadanie w tle nie będzie ich już potrzebowało.',
    detailsClass: null,
    detailsText: null,
    cancelAction: 'cancel-clear-queue',
    submitAction: 'submit-clear-queue',
    closeAction: 'close-clear-queue',
    cancelI18n: 'admin_queue_clear_popup_cancel',
    cancelFallback: 'Przerwij',
    submitI18n: 'admin_queue_clear_popup_confirm',
    submitFallback: 'Wyczyść kolejkę',
    closeI18n: 'admin_close_alert',
    closeFallback: 'Zamknij alert',
  });
}

export function setupExportDeleteConfirmation(){
  setupDangerConfirmation({
    triggerSelector: '[data-action="confirm-delete-export"]',
    modalClass: 'confirm-delete-export-modal',
    modalIdPrefix: 'confirm-delete-export',
    titleI18n: 'admin_exports_delete_popup_title',
    titleFallback: 'Usunąć eksport?',
    textI18n: 'admin_exports_delete_popup_text',
    textFallback: 'Ta operacja usunie rekord eksportu i powiązany plik z dysku.',
    detailsClass: 'confirm-delete-export-name',
    detailsText: (trigger)=> trigger.getAttribute('data-export-file') || '',
    cancelAction: 'cancel-delete-export',
    submitAction: 'submit-delete-export',
    closeAction: 'close-delete-export',
    cancelI18n: 'admin_exports_delete_popup_cancel',
    cancelFallback: 'Przerwij',
    submitI18n: 'admin_exports_delete_popup_confirm',
    submitFallback: 'Usuń eksport',
    closeI18n: 'admin_close_alert',
    closeFallback: 'Zamknij alert',
  });
}

export function setupExportClearConfirmation(){
  setupDangerConfirmation({
    triggerSelector: '[data-action="confirm-clear-exports"]',
    modalClass: 'confirm-clear-exports-modal',
    modalIdPrefix: 'confirm-clear-exports',
    titleI18n: 'admin_exports_clear_popup_title',
    titleFallback: 'Usunąć wszystkie eksporty?',
    textI18n: 'admin_exports_clear_popup_text',
    textFallback: 'Ta operacja usunie wszystkie rekordy eksportów oraz powiązane pliki z dysku.',
    detailsClass: null,
    detailsText: null,
    cancelAction: 'cancel-clear-exports',
    submitAction: 'submit-clear-exports',
    closeAction: 'close-clear-exports',
    cancelI18n: 'admin_exports_clear_popup_cancel',
    cancelFallback: 'Przerwij',
    submitI18n: 'admin_exports_clear_popup_confirm',
    submitFallback: 'Usuń wszystkie eksporty',
    closeI18n: 'admin_close_alert',
    closeFallback: 'Zamknij alert',
  });
}

export function setupImportClearConfirmation(){
  setupDangerConfirmation({
    triggerSelector: '[data-action="confirm-clear-imports"]',
    modalClass: 'confirm-clear-imports-modal',
    modalIdPrefix: 'confirm-clear-imports',
    titleI18n: 'admin_imports_clear_popup_title',
    titleFallback: 'Usunąć wszystkie importy?',
    textI18n: 'admin_imports_clear_popup_text',
    textFallback: 'Ta operacja usunie wszystkie rekordy importów oraz powiązane pliki z dysku.',
    detailsClass: null,
    detailsText: null,
    cancelAction: 'cancel-clear-imports',
    submitAction: 'submit-clear-imports',
    closeAction: 'close-clear-imports',
    cancelI18n: 'admin_imports_clear_popup_cancel',
    cancelFallback: 'Przerwij',
    submitI18n: 'admin_imports_clear_popup_confirm',
    submitFallback: 'Usuń wszystko',
    closeI18n: 'admin_close_alert',
    closeFallback: 'Zamknij alert',
  });
}

export function setupImportDeleteConfirmation(){
  setupDangerConfirmation({
    triggerSelector: '[data-action="confirm-delete-import"]',
    modalClass: 'confirm-delete-import-modal',
    modalIdPrefix: 'confirm-delete-import',
    titleI18n: 'admin_imports_delete_popup_title',
    titleFallback: 'Usunąć import?',
    textI18n: 'admin_imports_delete_popup_text',
    textFallback: 'Ta operacja usunie rekord importu i powiązany plik z dysku.',
    detailsClass: 'confirm-delete-import-name',
    detailsText: (trigger)=> trigger.getAttribute('data-import-file') || '',
    cancelAction: 'cancel-delete-import',
    submitAction: 'submit-delete-import',
    closeAction: 'close-delete-import',
    cancelI18n: 'admin_imports_delete_popup_cancel',
    cancelFallback: 'Przerwij',
    submitI18n: 'admin_imports_delete_popup_confirm',
    submitFallback: 'Usuń import',
    closeI18n: 'admin_close_alert',
    closeFallback: 'Zamknij alert',
  });
}
