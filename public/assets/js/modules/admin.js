import { applyI18n, getTranslation, registerI18nListener } from './i18n.js';
import { getLang, isAdminDeviceRemembered, setAdminDeviceRemembered } from './preferences.js';
import { normalizeHexColor, qs, qsa } from './shared.js';

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

export function setupOptionalColorFields(){
  qsa('[data-optional-color-field]').forEach((field)=>{
    const hiddenInput = qs('[data-optional-color-value]', field.parentElement);
    const picker = qs('[data-optional-color-picker]', field);
    const pickerShell = qs('[data-optional-color-picker-shell]', field);
    const clearButton = qs('[data-action="clear-optional-color"]', field);
    if(!hiddenInput || !picker || !pickerShell || !clearButton) return;

    const fallbackColor = '#39ff14';

    const sync = ()=>{
      const normalizedColor = normalizeHexColor(hiddenInput.value);
      const isBlank = null === normalizedColor;
      picker.value = normalizedColor ?? fallbackColor;
      picker.classList.toggle('is-blank', isBlank);
      pickerShell.classList.toggle('is-blank', isBlank);
      clearButton.hidden = isBlank;
      clearButton.setAttribute('aria-hidden', isBlank ? 'true' : 'false');
    };

    sync();

    picker.addEventListener('input', ()=>{
      hiddenInput.value = normalizeHexColor(picker.value) ?? '';
      sync();
    });

    clearButton.addEventListener('click', ()=>{
      hiddenInput.value = '';
      sync();
    });
  });
}

function setupTabbedPanels(rootSelector, tabAttribute, panelAttribute){
  qsa(rootSelector).forEach((root)=>{
    const tabs = qsa(`[${tabAttribute}]`, root);
    const panels = qsa(`[${panelAttribute}]`, root);
    if(!tabs.length || !panels.length) return;

    const activateTab = (name)=>{
      tabs.forEach((tab)=>{
        const isActive = tab.getAttribute(tabAttribute) === name;
        tab.classList.toggle('is-active', isActive);
        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        tab.setAttribute('tabindex', isActive ? '0' : '-1');
      });

      panels.forEach((panel)=>{
        const isActive = panel.getAttribute(panelAttribute) === name;
        panel.classList.toggle('is-active', isActive);
        panel.hidden = !isActive;
      });
    };

    tabs.forEach((tab)=>{
      tab.addEventListener('click', ()=>{
        activateTab(tab.getAttribute(tabAttribute) || '');
      });

      tab.addEventListener('keydown', (event)=>{
        if(event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;

        event.preventDefault();
        const currentIndex = tabs.indexOf(tab);
        const direction = event.key === 'ArrowRight' ? 1 : -1;
        const nextIndex = (currentIndex + direction + tabs.length) % tabs.length;
        const nextTab = tabs[nextIndex];
        if(!nextTab) return;

        activateTab(nextTab.getAttribute(tabAttribute) || '');
        nextTab.focus({ preventScroll: true });
      });
    });

    root.addEventListener('invalid', (event)=>{
      const field = event.target;
      if(!(field instanceof HTMLElement)) return;

      const panel = field.closest(`[${panelAttribute}]`);
      if(!panel) return;

      const panelName = panel.getAttribute(panelAttribute);
      if(!panelName) return;

      activateTab(panelName);
    }, true);
  });
}

export function setupDashboardCarousels(){
  setupTabbedPanels('[data-dashboard-carousel]', 'data-dashboard-carousel-tab', 'data-dashboard-carousel-panel');
}

export function setupTranslationTabs(){
  setupTabbedPanels('[data-translation-tabs]', 'data-translation-tab', 'data-translation-panel');
}

export function setupTopMenuTargetTabs(){
  qsa('[data-menu-target-tabs]').forEach((root)=>{
    const tabs = qsa('[data-menu-target-tab]', root);
    const panels = qsa('[data-menu-target-panel]', root);
    const form = root.closest('form');
    const input = form ? qs('[data-menu-target-input]', form) : null;
    if(!tabs.length || !panels.length || !input) return;

    const activateTab = (value)=>{
      input.value = value;

      tabs.forEach((tab)=>{
        const isActive = tab.getAttribute('data-menu-target-tab') === value;
        tab.classList.toggle('is-active', isActive);
        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        tab.setAttribute('tabindex', isActive ? '0' : '-1');
      });

      panels.forEach((panel)=>{
        const isActive = panel.getAttribute('data-menu-target-panel') === value;
        panel.classList.toggle('is-active', isActive);
        panel.hidden = !isActive;
      });
    };

    tabs.forEach((tab)=>{
      tab.addEventListener('click', ()=>{
        activateTab(tab.getAttribute('data-menu-target-tab') || '');
      });

      tab.addEventListener('keydown', (event)=>{
        if(event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;

        event.preventDefault();
        const currentIndex = tabs.indexOf(tab);
        const direction = event.key === 'ArrowRight' ? 1 : -1;
        const nextIndex = (currentIndex + direction + tabs.length) % tabs.length;
        const nextTab = tabs[nextIndex];
        if(!nextTab) return;

        activateTab(nextTab.getAttribute('data-menu-target-tab') || '');
        nextTab.focus({ preventScroll: true });
      });
    });

    activateTab(root.getAttribute('data-menu-target-active') || input.value || tabs[0]?.getAttribute('data-menu-target-tab') || '');
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
  ['article', 'category'].forEach((scope)=>{
    const selectAll = qs(`[data-select-all="${scope}"]`);
    const submit = qs(`[data-bulk-submit="${scope}"]`);
    const checkboxes = qsa(`[data-select-item="${scope}"]`);
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
  });
}

export function setupAdminListingFilters(){
  const dropdownEntries = qsa('[data-listing-filter-dropdown]').map((dropdown)=> ({
      dropdown,
      floatingPanel: null,
      originalParent: null,
      originalNextSibling: null,
    }));
  if(!dropdownEntries.length) return;

  const getPanel = (entry)=>{
    return entry.floatingPanel || qs('.article-index-filter-options', entry.dropdown);
  };

  let floatingPanelSyncFrame = 0;
  let floatingPanelListenersAttached = false;

  const syncFloatingPanels = ()=>{
    floatingPanelSyncFrame = 0;
    dropdownEntries.forEach((entry)=>{
      if(entry.floatingPanel){
        updateFloatingPanelPosition(entry);
      }
    });
  };

  const scheduleFloatingPanelSync = ()=>{
    if(0 !== floatingPanelSyncFrame) return;

    floatingPanelSyncFrame = window.requestAnimationFrame(syncFloatingPanels);
  };

  const handleViewportChange = ()=>{
    scheduleFloatingPanelSync();
  };

  const updateFloatingPanelListeners = ()=>{
    const hasOpenFloatingPanel = dropdownEntries.some((entry)=> null !== entry.floatingPanel);

    if(hasOpenFloatingPanel && !floatingPanelListenersAttached){
      window.addEventListener('resize', handleViewportChange);
      window.addEventListener('scroll', handleViewportChange, true);
      floatingPanelListenersAttached = true;
      return;
    }

    if(!hasOpenFloatingPanel && floatingPanelListenersAttached){
      window.removeEventListener('resize', handleViewportChange);
      window.removeEventListener('scroll', handleViewportChange, true);
      if(0 !== floatingPanelSyncFrame){
        window.cancelAnimationFrame(floatingPanelSyncFrame);
        floatingPanelSyncFrame = 0;
      }
      floatingPanelListenersAttached = false;
    }
  };

  const updateFloatingPanelPosition = (entry)=>{
    const trigger = qs('[data-listing-filter-trigger]', entry.dropdown);
    const panel = entry.floatingPanel;
    if(!trigger || !panel) return;

    const triggerRect = trigger.getBoundingClientRect();
    const panelRect = panel.getBoundingClientRect();
    const minWidth = triggerRect.width;
    const panelWidth = Math.max(panelRect.width, minWidth);
    const viewportPadding = 12;
    const centeredLeft = window.scrollX + triggerRect.left + ((triggerRect.width - panelWidth) / 2);
    const maxLeft = window.scrollX + window.innerWidth - panelWidth - viewportPadding;
    const clampedLeft = Math.max(window.scrollX + viewportPadding, Math.min(centeredLeft, maxLeft));

    panel.style.width = `${panelWidth}px`;
    panel.style.left = `${clampedLeft}px`;
    panel.style.top = `${window.scrollY + triggerRect.bottom + 4}px`;
  };

  const floatPanel = (entry, panel)=>{
    if(entry.floatingPanel) return;

    entry.originalParent = panel.parentElement;
    entry.originalNextSibling = panel.nextSibling;
    entry.floatingPanel = panel;
    panel.classList.add('is-floating');
    document.body.appendChild(panel);
    updateFloatingPanelListeners();
    updateFloatingPanelPosition(entry);
  };

  const restorePanel = (entry)=>{
    const panel = entry.floatingPanel;
    if(!panel || !entry.originalParent) return;

    if(entry.originalNextSibling && entry.originalNextSibling.parentNode === entry.originalParent){
      entry.originalParent.insertBefore(panel, entry.originalNextSibling);
    }else{
      entry.originalParent.appendChild(panel);
    }

    panel.classList.remove('is-floating');
    panel.style.removeProperty('width');
    panel.style.removeProperty('left');
    panel.style.removeProperty('top');
    entry.floatingPanel = null;
    entry.originalParent = null;
    entry.originalNextSibling = null;
    updateFloatingPanelListeners();
  };

  const closeDropdown = (entry, { restoreFocus = false } = {})=>{
    if(!entry?.dropdown) return;
    entry.dropdown.classList.remove('is-open');
    const trigger = qs('[data-listing-filter-trigger]', entry.dropdown);
    const panel = getPanel(entry);
    if(trigger){
      trigger.setAttribute('aria-expanded', 'false');
      if(restoreFocus){
        trigger.focus({ preventScroll: true });
      }
    }
    if(panel){
      panel.hidden = true;
      panel.setAttribute('aria-hidden', 'true');
    }
    restorePanel(entry);
  };

  const closeOtherDropdowns = (activeEntry = null)=>{
    dropdownEntries.forEach((entry)=>{
      if(entry !== activeEntry){
        closeDropdown(entry);
      }
    });
  };

  dropdownEntries.forEach((entry)=>{
    const { dropdown } = entry;
    const trigger = qs('[data-listing-filter-trigger]', dropdown);
    const hiddenInput = qs('[data-listing-filter-input]', dropdown.closest('form'));
    const panel = qs('.article-index-filter-options', dropdown);
    const options = qsa('[data-listing-filter-option]', dropdown);
    if(!trigger || !hiddenInput || !panel || !options.length) return;

    const open = ()=>{
      dropdown.classList.add('is-open');
      trigger.setAttribute('aria-expanded', 'true');
      panel.hidden = false;
      panel.setAttribute('aria-hidden', 'false');
      floatPanel(entry, panel);
      const selectedOption = qs('.article-index-filter-option.is-selected', panel) || options[0];
      selectedOption?.focus({ preventScroll: true });
    };

    trigger.addEventListener('click', (event)=>{
      event.preventDefault();
      const isOpen = dropdown.classList.contains('is-open');
      closeOtherDropdowns(entry);

      if(isOpen){
        closeDropdown(entry);
      }else{
        open();
      }
    });

    options.forEach((option)=>{
      option.addEventListener('click', ()=>{
        hiddenInput.value = option.getAttribute('data-value') || '';
        dropdown.closest('form')?.submit();
      });
    });
  });

  document.addEventListener('click', (event)=>{
    dropdownEntries.forEach((entry)=>{
      const panel = getPanel(entry);
      if(!entry.dropdown.contains(event.target) && !panel?.contains(event.target)){
        closeDropdown(entry);
      }
    });
  });

  document.addEventListener('keydown', (event)=>{
    if(event.key !== 'Escape') return;

    const activeEntry = dropdownEntries.find((entry)=> entry.dropdown.classList.contains('is-open'));
    if(activeEntry){
      closeDropdown(activeEntry, { restoreFocus: true });
    }else{
      closeOtherDropdowns();
    }
  });
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

export function setupTopMenuDeleteConfirmation(){
  setupDangerConfirmation({
    triggerSelector: '[data-action="confirm-delete-top-menu-item"]',
    modalClass: 'confirm-delete-top-menu-item-modal',
    modalIdPrefix: 'confirm-delete-top-menu-item',
    titleI18n: 'admin_top_menu_delete_popup_title',
    titleFallback: 'Usunąć element menu?',
    textI18n: 'admin_top_menu_delete_popup_text',
    textFallback: 'Ta operacja usunie element z top menu oraz odłączy jego dzieci od struktury nadrzędnej.',
    detailsClass: 'confirm-delete-top-menu-item-name',
    detailsText: (trigger)=> trigger.getAttribute('data-top-menu-item-name') || '',
    cancelAction: 'cancel-delete-top-menu-item',
    submitAction: 'submit-delete-top-menu-item',
    closeAction: 'close-delete-top-menu-item',
    cancelI18n: 'admin_top_menu_delete_popup_cancel',
    cancelFallback: 'Przerwij',
    submitI18n: 'admin_top_menu_delete_popup_confirm',
    submitFallback: 'Usuń element',
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

  setupDangerConfirmation({
    triggerSelector: '[data-action="confirm-clear-top-menu-imports"]',
    modalClass: 'confirm-clear-top-menu-imports-modal',
    modalIdPrefix: 'confirm-clear-top-menu-imports',
    titleI18n: 'admin_top_menu_imports_clear_popup_title',
    titleFallback: 'Usunąć wszystkie importy menu?',
    textI18n: 'admin_top_menu_imports_clear_popup_text',
    textFallback: 'Ta operacja usunie wszystkie rekordy importu menu oraz powiązane pliki z dysku.',
    detailsClass: null,
    detailsText: null,
    cancelAction: 'cancel-clear-top-menu-imports',
    submitAction: 'submit-clear-top-menu-imports',
    closeAction: 'close-clear-top-menu-imports',
    cancelI18n: 'admin_top_menu_imports_clear_popup_cancel',
    cancelFallback: 'Przerwij',
    submitI18n: 'admin_top_menu_imports_clear_popup_confirm',
    submitFallback: 'Usuń wszystko',
    closeI18n: 'admin_close_alert',
    closeFallback: 'Zamknij alert',
  });

  setupDangerConfirmation({
    triggerSelector: '[data-action="confirm-clear-category-imports"]',
    modalClass: 'confirm-clear-category-imports-modal',
    modalIdPrefix: 'confirm-clear-category-imports',
    titleI18n: 'admin_category_imports_clear_popup_title',
    titleFallback: 'Usunąć wszystkie importy kategorii?',
    textI18n: 'admin_category_imports_clear_popup_text',
    textFallback: 'Ta operacja usunie wszystkie rekordy importu kategorii oraz powiązane pliki z dysku.',
    detailsClass: null,
    detailsText: null,
    cancelAction: 'cancel-clear-category-imports',
    submitAction: 'submit-clear-category-imports',
    closeAction: 'close-clear-category-imports',
    cancelI18n: 'admin_category_imports_clear_popup_cancel',
    cancelFallback: 'Przerwij',
    submitI18n: 'admin_category_imports_clear_popup_confirm',
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

export function setupImportMessageDialog(){
  const triggers = qsa('[data-action="show-import-message"]');
  if(!triggers.length) return;

  const existingModal = qs('.import-message-modal');
  if(existingModal){
    existingModal.remove();
  }

  const titleId = 'import-message-title';
  const textId = 'import-message-text';
  const modal = document.createElement('div');
  modal.className = 'confirm-delete-modal import-message-modal';
  modal.setAttribute('hidden', '');
  modal.setAttribute('aria-hidden', 'true');
  modal.innerHTML = `
    <div class="confirm-delete-dialog" role="dialog" aria-modal="true" aria-labelledby="${titleId}" aria-describedby="${textId}">
      <div class="confirm-delete-topbar">
        <div class="confirm-delete-eyebrow">admin://imports</div>
        <button type="button" class="confirm-delete-close" data-action="close-import-message" data-i18n-aria="admin_close_alert" aria-label="Zamknij alert">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <h2 id="${titleId}" class="confirm-delete-title" data-i18n="admin_imports_message_popup_title">Komunikat importu</h2>
      <p id="${textId}" class="confirm-delete-text import-message-text"></p>
      <div class="confirm-delete-actions">
        <button type="button" class="button secondary" data-action="close-import-message" data-i18n="admin_imports_message_popup_close">Zamknij</button>
      </div>
    </div>
  `;

  document.body.appendChild(modal);
  applyI18n(getLang());

  const dialog = qs('.confirm-delete-dialog', modal);
  const text = qs('.import-message-text', modal);
  const closeButtons = qsa('[data-action="close-import-message"]', modal);
  let lastTrigger = null;

  const closeModal = ()=>{
    modal.setAttribute('hidden', '');
    modal.setAttribute('aria-hidden', 'true');
    if(lastTrigger){
      lastTrigger.focus({ preventScroll: true });
    }
  };

  const openModal = (trigger)=>{
    lastTrigger = trigger;
    if(text){
      text.textContent = trigger.getAttribute('data-import-message') || '';
    }

    modal.removeAttribute('hidden');
    modal.setAttribute('aria-hidden', 'false');
    closeButtons[0]?.focus({ preventScroll: true });
  };

  triggers.forEach((trigger)=>{
    trigger.addEventListener('click', ()=>{
      openModal(trigger);
    });
  });

  closeButtons.forEach((button)=>{
    button.addEventListener('click', closeModal);
  });

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
