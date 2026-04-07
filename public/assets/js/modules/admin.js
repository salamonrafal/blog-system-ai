import { applyI18n, getTranslation, registerI18nListener } from './i18n.js';
import { createMediaImagePicker } from './media-image-picker.js';
import { getLang, isAdminDeviceRemembered, isAdminShortcutsCollapsed, isAdminShortcutsDocked, setAdminDeviceRemembered, setAdminShortcutsCollapsed, setAdminShortcutsDocked } from './preferences.js';
import { assignFilesToInput, lockDocumentScroll, normalizeHexColor, qs, qsa, unlockDocumentScroll } from './shared.js';

function isDesktopAdminShortcutsViewport(){
  return window.matchMedia('(min-width: 769px)').matches;
}

function shouldUseCollapsedAdminSubmenuPopover(submenu){
  if(!(submenu instanceof HTMLElement)) return false;

  const shortcuts = submenu.closest('.admin-shortcuts');
  return shortcuts instanceof HTMLElement
    && shortcuts.classList.contains('is-docked')
    && shortcuts.classList.contains('is-collapsed')
    && isDesktopAdminShortcutsViewport();
}

function syncCollapsedSubmenuAccessibility(shortcuts){
  if(!(shortcuts instanceof HTMLElement)) return;

  const isCollapsed = shortcuts.classList.contains('is-docked')
    && shortcuts.classList.contains('is-collapsed')
    && isDesktopAdminShortcutsViewport();

  qsa('[data-admin-shortcuts-submenu]', shortcuts).forEach((submenu)=>{
    const trigger = qs('[data-action="toggle-admin-submenu"]', submenu);
    const panel = qs('.admin-shortcuts-submenu-panel', submenu);
    if(!(trigger instanceof HTMLElement) || !(panel instanceof HTMLElement)) return;

    const originalControls = trigger.dataset.originalAriaControls || trigger.getAttribute('aria-controls') || panel.id;
    if(originalControls){
      trigger.dataset.originalAriaControls = originalControls;
    }

    if(isCollapsed){
      if(!submenu.classList.contains('is-open')){
        trigger.removeAttribute('aria-controls');
      }
      return;
    }

    if(originalControls){
      trigger.setAttribute('aria-controls', originalControls);
    }
  });
}

function syncCollapsedShortcutTooltips(shortcuts){
  if(!shortcuts) return;

  const isCollapsed = shortcuts.classList.contains('is-docked') && shortcuts.classList.contains('is-collapsed');
  qsa('.admin-shortcuts-link, .admin-shortcuts-desktop-collapse, .admin-shortcuts-desktop-dock', shortcuts).forEach((element)=>{
    if(!(element instanceof HTMLElement)) return;

    if(!isCollapsed){
      element.removeAttribute('data-tooltip');
      return;
    }

    const label = qsa('span', element)
      .map((span)=> span.textContent ? span.textContent.trim() : '')
      .find((text)=> text.length > 0 && !/^\d+$/.test(text));
    const badge = qs('.admin-shortcuts-badge', element);
    const badgeValue = badge?.textContent?.trim();
    const fallbackLabel = element.getAttribute('aria-label')?.trim();
    const tooltipLabel = label || fallbackLabel;
    const tooltip = badgeValue ? `${tooltipLabel}: ${badgeValue}` : tooltipLabel;

    if(tooltip){
      element.setAttribute('data-tooltip', tooltip);
    }else{
      element.removeAttribute('data-tooltip');
    }
  });

  qsa('.admin-shortcuts-settings .admin-shortcuts-link', shortcuts).forEach((element)=>{
    if(!(element instanceof HTMLElement)) return;
    const tooltipKey = element.getAttribute('data-collapsed-tooltip-key');

    if(!isCollapsed){
      element.removeAttribute('data-tooltip');
      element.removeAttribute('aria-label');
      return;
    }

    const label = tooltipKey ? getTranslation(tooltipKey) : qsa('span', element)
      .map((span)=> span.textContent ? span.textContent.trim() : '')
      .find((text)=> text.length > 0 && !/^\d+$/.test(text));

    if(label){
      element.setAttribute('data-tooltip', label);
      element.setAttribute('aria-label', label);
    }
  });
}

function syncActiveAdminShortcutSubmenus(shortcuts){
  if(!(shortcuts instanceof HTMLElement)) return;

  const isDockedExpanded = shortcuts.classList.contains('is-docked')
    && !shortcuts.classList.contains('is-collapsed')
    && isDesktopAdminShortcutsViewport();

  if(!isDockedExpanded) return;

  const activeSubmenu = qsa('[data-admin-shortcuts-submenu][data-route-active="true"]', shortcuts)
    .find((submenu)=> submenu instanceof HTMLElement);
  if(!(activeSubmenu instanceof HTMLElement)) return;

  const hasOpenSubmenu = qsa('[data-admin-shortcuts-submenu].is-open', shortcuts).length > 0;
  if(hasOpenSubmenu) return;

  activeSubmenu.classList.add('is-open');
  const trigger = qs('[data-action="toggle-admin-submenu"]', activeSubmenu);
  if(trigger instanceof HTMLElement){
    trigger.setAttribute('aria-expanded', 'true');
    if(trigger.dataset.originalAriaControls){
      trigger.setAttribute('aria-controls', trigger.dataset.originalAriaControls);
    }
  }
}

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
    const remembered = isAdminDeviceRemembered();
    if(rememberButton){
      const label = qs('[data-device-remember-label]', rememberButton);
      const labelKey = remembered ? 'admin_shortcut_forget_device' : 'admin_shortcut_remember_device';

      rememberButton.classList.toggle('is-active', remembered);
      rememberButton.setAttribute('aria-pressed', remembered ? 'true' : 'false');

      if(label){
        label.setAttribute('data-i18n', labelKey);
        label.textContent = getTranslation(labelKey);
      }
    }

    const dockButton = qs('[data-action="toggle-admin-shortcuts-dock"]', menu);
    const collapseButton = qs('[data-action="toggle-admin-shortcuts-collapse"]', menu);
    if(dockButton){
      const shouldDock = isAdminShortcutsDocked();
      const isDesktopViewport = isDesktopAdminShortcutsViewport();
      const isDocked = shouldDock && isDesktopViewport;
      const labelKey = shouldDock ? 'admin_shortcut_undock' : 'admin_shortcut_dock';
      const isCollapsed = isDocked && isAdminShortcutsCollapsed();

      shortcuts.classList.toggle('is-docked', isDocked);
      shortcuts.classList.toggle('is-collapsed', isCollapsed);
      dockButton.setAttribute('aria-pressed', shouldDock ? 'true' : 'false');
      dockButton.setAttribute('data-i18n-aria', labelKey);
      dockButton.setAttribute('aria-label', getTranslation(labelKey));
      dockButton.classList.toggle('is-active', shouldDock);

      if(collapseButton){
        const collapseLabelKey = isCollapsed ? 'admin_shortcut_expand' : 'admin_shortcut_collapse';
        collapseButton.hidden = !isDocked;
        collapseButton.setAttribute('aria-pressed', isCollapsed ? 'true' : 'false');
        collapseButton.setAttribute('data-i18n-aria', collapseLabelKey);
        collapseButton.setAttribute('aria-label', getTranslation(collapseLabelKey));
        collapseButton.classList.toggle('is-active', isCollapsed);
      }

      if(isDocked){
        shortcuts.setAttribute('open', '');
      }else{
        shortcuts.removeAttribute('open');
        shortcuts.classList.remove('is-collapsed');
      }
    }else{
      shortcuts.classList.remove('is-docked');
      shortcuts.classList.remove('is-collapsed');
    }

    syncCollapsedSubmenuAccessibility(shortcuts);
    syncCollapsedShortcutTooltips(shortcuts);
    syncActiveAdminShortcutSubmenus(shortcuts);
  });
}

export function setupAdminShortcuts(){
  registerI18nListener(syncAdminShortcuts);
  syncAdminShortcuts();
  let collapsedSubmenuPopover = null;
  let collapsedSubmenuOwner = null;

  const resetCollapsedDesktopSubmenus = ()=>{
    qsa('.admin-shortcuts.is-docked.is-collapsed').forEach((shortcuts)=>{
      if(!isDesktopAdminShortcutsViewport()) return;

      closeCollapsedSubmenuPopover();
      qsa('[data-admin-shortcuts-submenu].is-open', shortcuts).forEach((submenu)=>{
        closeAdminSubmenu(submenu);
      });
    });
  };

  const ensureCollapsedSubmenuPopover = ()=>{
    if(collapsedSubmenuPopover) return collapsedSubmenuPopover;

    const popover = document.createElement('div');
    popover.className = 'admin-shortcuts-collapsed-popover';
    popover.hidden = true;
    document.body.appendChild(popover);
    collapsedSubmenuPopover = popover;
    return popover;
  };

  const closeCollapsedSubmenuPopover = ()=>{
    if(collapsedSubmenuOwner){
      collapsedSubmenuOwner.classList.remove('is-open');
      const ownerTrigger = qs('[data-action="toggle-admin-submenu"]', collapsedSubmenuOwner);
      if(ownerTrigger){
        ownerTrigger.setAttribute('aria-expanded', 'false');
        const ownerShortcuts = collapsedSubmenuOwner.closest('.admin-shortcuts');
        if(
          ownerShortcuts instanceof HTMLElement
          && ownerShortcuts.classList.contains('is-docked')
          && ownerShortcuts.classList.contains('is-collapsed')
          && isDesktopAdminShortcutsViewport()
        ){
          ownerTrigger.removeAttribute('aria-controls');
        }else if(ownerTrigger.dataset.originalAriaControls){
          ownerTrigger.setAttribute('aria-controls', ownerTrigger.dataset.originalAriaControls);
        }
      }
    }

    if(collapsedSubmenuPopover){
      collapsedSubmenuPopover.hidden = true;
      collapsedSubmenuPopover.innerHTML = '';
      collapsedSubmenuPopover.style.removeProperty('top');
      collapsedSubmenuPopover.style.removeProperty('left');
    }

    collapsedSubmenuOwner = null;
  };

  const openCollapsedSubmenuPopover = (submenu)=>{
    const sourcePanel = qs('.admin-shortcuts-submenu-panel', submenu);
    const trigger = qs('[data-action="toggle-admin-submenu"]', submenu);
    if(!(sourcePanel instanceof HTMLElement) || !(trigger instanceof HTMLElement)){
      return;
    }

    const popover = ensureCollapsedSubmenuPopover();
    const clone = sourcePanel.cloneNode(true);
    clone.id = `${sourcePanel.id || 'admin-shortcuts-submenu'}-popover`;
    clone.classList.add('admin-shortcuts-collapsed-popover-panel');

    closeCollapsedSubmenuPopover();
    submenu.classList.add('is-open');
    trigger.setAttribute('aria-expanded', 'true');
    trigger.setAttribute('aria-controls', clone.id);
    collapsedSubmenuOwner = submenu;

    popover.innerHTML = '';
    popover.appendChild(clone);
    popover.hidden = false;

    const triggerRect = trigger.getBoundingClientRect();
    const popoverRect = popover.getBoundingClientRect();
    const viewportPadding = 12;
    const left = Math.min(
      Math.max(triggerRect.right + 8, viewportPadding),
      window.innerWidth - popoverRect.width - viewportPadding
    );
    const top = Math.max(
      viewportPadding,
      Math.min(triggerRect.top, window.innerHeight - popoverRect.height - viewportPadding)
    );

    popover.style.left = `${left}px`;
    popover.style.top = `${top}px`;
  };

  const closeAdminShortcuts = (shortcuts)=>{
    if(!shortcuts) return;
    if(shortcuts.classList.contains('is-docked') && isDesktopAdminShortcutsViewport()){
      closeCollapsedSubmenuPopover();
      qsa('[data-admin-shortcuts-submenu].is-open', shortcuts).forEach((submenu)=>{
        closeAdminSubmenu(submenu);
      });
      return;
    }

    shortcuts.removeAttribute('open');
    closeCollapsedSubmenuPopover();
    qsa('[data-admin-shortcuts-submenu].is-open', shortcuts).forEach((submenu)=>{
      closeAdminSubmenu(submenu);
    });
  };

  const closeAdminSubmenu = (submenu)=>{
    if(!submenu) return;
    if(collapsedSubmenuOwner === submenu){
      closeCollapsedSubmenuPopover();
      return;
    }

    submenu.classList.remove('is-open');
    const trigger = qs('[data-action="toggle-admin-submenu"]', submenu);
    if(trigger){
      trigger.setAttribute('aria-expanded', 'false');
      if(trigger.dataset.originalAriaControls){
        trigger.setAttribute('aria-controls', trigger.dataset.originalAriaControls);
      }
    }
  };

  const resetOpenAdminSubmenus = ()=>{
    closeCollapsedSubmenuPopover();
    qsa('[data-admin-shortcuts-submenu].is-open').forEach((submenu)=>{
      closeAdminSubmenu(submenu);
    });
  };

  const openAdminSubmenu = (submenu)=>{
    if(!submenu) return;
    if(shouldUseCollapsedAdminSubmenuPopover(submenu)){
      openCollapsedSubmenuPopover(submenu);
      return;
    }

    submenu.classList.add('is-open');
    const trigger = qs('[data-action="toggle-admin-submenu"]', submenu);
    if(trigger){
      trigger.setAttribute('aria-expanded', 'true');
      if(trigger.dataset.originalAriaControls){
        trigger.setAttribute('aria-controls', trigger.dataset.originalAriaControls);
      }
    }
  };

  qsa('[data-admin-shortcuts-submenu]').forEach((submenu)=>{
    const trigger = qs('[data-action="toggle-admin-submenu"]', submenu);
    if(!trigger) return;

    trigger.addEventListener('click', (event)=>{
      event.preventDefault();
      event.stopPropagation();
      const isOpen = shouldUseCollapsedAdminSubmenuPopover(submenu)
        ? collapsedSubmenuOwner === submenu && !!collapsedSubmenuPopover && !collapsedSubmenuPopover.hidden
        : submenu.classList.contains('is-open');

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

  qsa('.admin-shortcuts').forEach((shortcuts)=>{
    const summary = qs('.admin-shortcuts-toggle', shortcuts);
    const menu = qs('[data-admin-shortcuts]', shortcuts);
    if(!summary || !menu) return;

    const closeButton = qs('[data-action="close-admin-shortcuts"]', menu);
    if(closeButton){
      closeButton.addEventListener('click', ()=>{
        closeAdminShortcuts(shortcuts);
      });
    }

    const dockButton = qs('[data-action="toggle-admin-shortcuts-dock"]', menu);
    if(dockButton){
      dockButton.addEventListener('click', (event)=>{
        event.preventDefault();
        event.stopPropagation();

        const nextDocked = !shortcuts.classList.contains('is-docked');
        setAdminShortcutsDocked(nextDocked);

        shortcuts.classList.toggle('is-docked', nextDocked && isDesktopAdminShortcutsViewport());
        shortcuts.toggleAttribute('open', nextDocked && isDesktopAdminShortcutsViewport());

        if(!nextDocked){
          setAdminShortcutsCollapsed(false);
          shortcuts.classList.remove('is-collapsed');
        }

        syncAdminShortcuts();
      });
    }

    const collapseButton = qs('[data-action="toggle-admin-shortcuts-collapse"]', menu);
    if(collapseButton){
      collapseButton.addEventListener('click', (event)=>{
        event.preventDefault();
        event.stopPropagation();

        if(!shortcuts.classList.contains('is-docked')) return;
        resetOpenAdminSubmenus();
        setAdminShortcutsCollapsed(!shortcuts.classList.contains('is-collapsed'));
        syncAdminShortcuts();
        resetCollapsedDesktopSubmenus();
      });
    }

    menu.addEventListener('click', (event)=>{
      const target = event.target;
      if(!(target instanceof Element)) return;
      if(!target.closest('a[href]')) return;
      if(shortcuts.classList.contains('is-docked') && isDesktopAdminShortcutsViewport()) return;

      closeAdminShortcuts(shortcuts);
    });
  });

  document.addEventListener('click', (event)=>{
    qsa('.admin-shortcuts[open]').forEach((shortcuts)=>{
      if(shortcuts.classList.contains('is-docked')) return;
      if(!shortcuts.contains(event.target)){
        closeAdminShortcuts(shortcuts);
      }
    });

    if(
      collapsedSubmenuPopover
      && !collapsedSubmenuPopover.hidden
      && !collapsedSubmenuPopover.contains(event.target)
      && !(collapsedSubmenuOwner && collapsedSubmenuOwner.contains(event.target))
    ){
      closeCollapsedSubmenuPopover();
    }

    qsa('[data-admin-shortcuts-submenu].is-open').forEach((submenu)=>{
      const shortcuts = submenu.closest('.admin-shortcuts');
      if(
        shortcuts instanceof HTMLElement
        && shortcuts.classList.contains('is-docked')
        && !shortcuts.classList.contains('is-collapsed')
        && isDesktopAdminShortcutsViewport()
      ){
        return;
      }

      if(!submenu.contains(event.target)){
        closeAdminSubmenu(submenu);
      }
    });
  });

  document.addEventListener('keydown', (event)=>{
    if(event.key !== 'Escape') return;

    closeCollapsedSubmenuPopover();

    qsa('.admin-shortcuts[open]').forEach((shortcuts)=>{
      closeAdminShortcuts(shortcuts);
    });

    qsa('[data-admin-shortcuts-submenu].is-open').forEach((submenu)=>{
      closeAdminSubmenu(submenu);
    });
  });

  const syncAdminShortcutsOnViewportChange = ()=>{
    syncAdminShortcuts();
    closeCollapsedSubmenuPopover();
    resetCollapsedDesktopSubmenus();
  };

  window.addEventListener('resize', syncAdminShortcutsOnViewportChange, { passive: true });
  window.addEventListener('orientationchange', syncAdminShortcutsOnViewportChange);
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

export function setupHeadlineImagePicker(){
  qsa('[data-headline-image-section]').forEach((section)=>{
    const input = qs('[data-headline-image-input]', section);
    const toggle = qs('[data-headline-image-toggle]', section);
    const openButton = qs('[data-action="open-headline-image-picker"]', section);
    const clearButton = qs('[data-action="clear-headline-image"]', section);
    const modal = qs('[data-headline-image-modal]', section);
    const dialog = modal ? qs('.headline-image-picker-dialog', modal) : null;
    const searchInput = modal ? qs('[data-headline-image-search-input]', modal) : null;
    const sortSelect = modal ? qs('[data-headline-image-sort-select]', modal) : null;
    const grid = modal ? qs('[data-headline-image-grid]', modal) : null;
    const emptyResults = modal ? qs('[data-headline-image-empty-results]', modal) : null;
    const previewShell = qs('[data-headline-image-preview-shell]', section);
    const previewImage = qs('[data-headline-image-preview]', section);
    const previewPath = qs('[data-headline-image-preview-path]', section);
    const endpoint = modal?.getAttribute('data-headline-image-endpoint') || '';

    if(!(input instanceof HTMLInputElement)) return;

    const syncPreview = ()=>{
      const value = input.value.trim();
      const defaultValue = input.getAttribute('data-default-headline-image') || '';
      const isEnabled = !(toggle instanceof HTMLInputElement) || toggle.checked;
      const previewValue = isEnabled ? (value || defaultValue) : '';
      const hasValue = previewValue.length > 0;

      if(previewShell instanceof HTMLElement){
        previewShell.hidden = !hasValue;
      }

      if(previewImage instanceof HTMLImageElement){
        if(hasValue){
          previewImage.src = previewValue;
          previewImage.alt = getTranslation('form_headline_image_preview_alt');
        }else{
          previewImage.removeAttribute('src');
        }
      }

      if(previewPath instanceof HTMLElement){
        previewPath.textContent = previewValue;
      }
    };

    const updateInputValue = (value)=>{
      input.value = value;
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
      syncPreview();
    };

    const picker = createMediaImagePicker({
      modal,
      dialog,
      searchInput,
      sortSelect,
      grid,
      emptyResults,
      endpoint,
      getCurrentValue: ()=> input.value.trim(),
      onSelect: ({ path })=>{
        updateInputValue(path);
      },
    });

    openButton?.addEventListener('click', ()=>{
      picker?.open(openButton);
    });

    clearButton?.addEventListener('click', ()=>{
      updateInputValue('');
    });

    toggle?.addEventListener('change', syncPreview);

    syncPreview();
    registerI18nListener(()=>{
      syncPreview();
      picker?.syncSelectedState();
    });
  });
}

export function setupOptionalColorFields(){
  qsa('[data-optional-color-field]').forEach((field)=>{
    const valueInput = qs('[data-optional-color-value]', field.parentElement);
    const picker = qs('[data-optional-color-picker]', field);
    const pickerShell = qs('[data-optional-color-picker-shell]', field);
    const clearButton = qs('[data-action="clear-optional-color"]', field);
    if(!(valueInput instanceof HTMLInputElement) || !picker || !pickerShell || !clearButton) return;

    const fallbackColor = '#39ff14';
    const label = valueInput.id
      ? document.querySelector(`label[for="${valueInput.id}"]`)
      : null;
    const labelId = label instanceof HTMLLabelElement
      ? (label.id || `${valueInput.id}--label`)
      : '';
    field.hidden = false;
    valueInput.classList.add('sr-only');
    valueInput.tabIndex = -1;
    valueInput.setAttribute('aria-hidden', 'true');

    if(label instanceof HTMLLabelElement && labelId){
      label.id = labelId;
      picker.setAttribute('aria-labelledby', labelId);
      label.addEventListener('click', (event)=>{
        event.preventDefault();
        picker.focus({ preventScroll: true });
      });
    }

    const syncAccessibilityState = ()=>{
      const describedBy = valueInput.getAttribute('aria-describedby');
      const required = valueInput.getAttribute('aria-required');
      const invalid = valueInput.getAttribute('aria-invalid');

      if(describedBy){
        picker.setAttribute('aria-describedby', describedBy);
      } else {
        picker.removeAttribute('aria-describedby');
      }

      if(required){
        picker.setAttribute('aria-required', required);
      } else {
        picker.removeAttribute('aria-required');
      }

      if(invalid){
        picker.setAttribute('aria-invalid', invalid);
      } else {
        picker.removeAttribute('aria-invalid');
      }
    };

    const sync = ()=>{
      const rawValue = valueInput.value.trim();
      const normalizedColor = normalizeHexColor(rawValue);
      const hasInvalidValue = '' !== rawValue && null === normalizedColor;
      const isBlank = '' === rawValue || hasInvalidValue;
      const isDisabled = valueInput.disabled;
      picker.value = normalizedColor ?? fallbackColor;
      picker.classList.toggle('is-blank', isBlank);
      pickerShell.classList.toggle('is-blank', isBlank);
      picker.disabled = isDisabled;
      clearButton.disabled = isDisabled;
      clearButton.hidden = '' === rawValue;
      clearButton.setAttribute('aria-hidden', clearButton.hidden ? 'true' : 'false');
      syncAccessibilityState();
    };

    sync();

    picker.addEventListener('input', ()=>{
      if(valueInput.disabled) return;
      valueInput.value = normalizeHexColor(picker.value) ?? '';
      sync();
    });

    clearButton.addEventListener('click', ()=>{
      if(valueInput.disabled) return;
      valueInput.value = '';
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

export function setupArticleKeywordDeleteConfirmation(){
  setupDangerConfirmation({
    triggerSelector: '[data-action="confirm-delete-article-keyword"]',
    modalClass: 'confirm-delete-article-keyword-modal',
    modalIdPrefix: 'confirm-delete-article-keyword',
    titleI18n: 'admin_article_keywords_delete_popup_title',
    titleFallback: 'Usunąć słowo kluczowe?',
    textI18n: 'admin_article_keywords_delete_popup_text',
    textFallback: 'Ta operacja trwale usunie słowo kluczowe z panelu administracyjnego.',
    detailsClass: 'confirm-delete-article-keyword-name',
    detailsText: (trigger)=> trigger.getAttribute('data-article-keyword-name') || '',
    cancelAction: 'cancel-delete-article-keyword',
    submitAction: 'submit-delete-article-keyword',
    closeAction: 'close-delete-article-keyword',
    cancelI18n: 'admin_article_keywords_delete_popup_cancel',
    cancelFallback: 'Przerwij',
    submitI18n: 'admin_article_keywords_delete_popup_confirm',
    submitFallback: 'Usuń słowo kluczowe',
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

  setupDangerConfirmation({
    triggerSelector: '[data-action="confirm-clear-media-gallery"]',
    modalClass: 'confirm-clear-media-gallery-modal',
    modalIdPrefix: 'confirm-clear-media-gallery',
    titleI18n: 'admin_media_clear_popup_title',
    titleFallback: 'Wyczyścić galerię?',
    textI18n: 'admin_media_clear_popup_text',
    textFallback: 'Ta operacja usunie wszystkie obsługiwane obrazki z galerii.',
    detailsClass: null,
    detailsText: null,
    cancelAction: 'cancel-clear-media-gallery',
    submitAction: 'submit-clear-media-gallery',
    closeAction: 'close-clear-media-gallery',
    cancelI18n: 'admin_media_clear_popup_cancel',
    cancelFallback: 'Przerwij',
    submitI18n: 'admin_media_clear_popup_confirm',
    submitFallback: 'Wyczyść galerię',
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

  setupDangerConfirmation({
    triggerSelector: '[data-action="confirm-delete-media-image"]',
    modalClass: 'confirm-delete-media-image-modal',
    modalIdPrefix: 'confirm-delete-media-image',
    titleI18n: 'admin_media_delete_popup_title',
    titleFallback: 'Usunąć obrazek?',
    textI18n: 'admin_media_delete_popup_text',
    textFallback: 'Ta operacja usunie obrazek z galerii.',
    detailsClass: 'confirm-delete-media-image-name',
    detailsText: (trigger)=> trigger.getAttribute('data-media-file') || '',
    cancelAction: 'cancel-delete-media-image',
    submitAction: 'submit-delete-media-image',
    closeAction: 'close-delete-media-image',
    cancelI18n: 'admin_media_delete_popup_cancel',
    cancelFallback: 'Przerwij',
    submitI18n: 'admin_media_delete_popup_confirm',
    submitFallback: 'Usuń obrazek',
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

export function setupMediaRenameDialog(){
  const triggers = qsa('[data-action="edit-media-name"]');
  if(!triggers.length) return;

  const existingModal = qs('.media-rename-modal');
  if(existingModal){
    existingModal.remove();
  }

  const titleId = 'media-rename-title';
  const textId = 'media-rename-text';
  const modal = document.createElement('div');
  modal.className = 'confirm-delete-modal media-rename-modal';
  modal.setAttribute('hidden', '');
  modal.setAttribute('aria-hidden', 'true');
  modal.innerHTML = `
    <div class="confirm-delete-dialog media-rename-dialog" role="dialog" aria-modal="true" aria-labelledby="${titleId}" aria-describedby="${textId}">
      <div class="confirm-delete-topbar">
        <div class="confirm-delete-eyebrow">admin://media</div>
        <button type="button" class="confirm-delete-close" data-action="close-media-rename" data-i18n-aria="admin_close_alert" aria-label="${getTranslation('admin_close_alert')}">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <h2 id="${titleId}" class="confirm-delete-title" data-i18n="admin_media_rename_popup_title">${getTranslation('admin_media_rename_popup_title')}</h2>
      <p id="${textId}" class="confirm-delete-text" data-i18n="admin_media_rename_popup_text">${getTranslation('admin_media_rename_popup_text')}</p>
      <label class="sr-only" for="media-rename-input" data-i18n="admin_media_custom_name">${getTranslation('admin_media_custom_name')}</label>
      <input id="media-rename-input" class="article-editor-input" type="text" maxlength="255" data-i18n-placeholder="admin_media_custom_name_placeholder" placeholder="${getTranslation('admin_media_custom_name_placeholder')}">
      <div class="confirm-delete-actions">
        <button type="button" class="button secondary" data-action="cancel-media-rename" data-i18n="admin_media_rename_popup_cancel">${getTranslation('admin_media_rename_popup_cancel')}</button>
        <button type="button" class="button" data-action="submit-media-rename" data-i18n="admin_media_custom_name_save">${getTranslation('admin_media_custom_name_save')}</button>
      </div>
    </div>
  `;

  document.body.appendChild(modal);
  applyI18n(getLang());

  const dialog = qs('.media-rename-dialog', modal);
  const input = qs('#media-rename-input', modal);
  const closeButton = qs('[data-action="close-media-rename"]', modal);
  const cancelButton = qs('[data-action="cancel-media-rename"]', modal);
  const submitButton = qs('[data-action="submit-media-rename"]', modal);
  let activeForm = null;
  let activeHiddenInput = null;
  let lastTrigger = null;

  const closeModal = ()=>{
    modal.setAttribute('hidden', '');
    modal.setAttribute('aria-hidden', 'true');
    activeForm = null;
    activeHiddenInput = null;
    if(lastTrigger){
      lastTrigger.focus({ preventScroll: true });
    }
  };

  const openModal = (trigger)=>{
    const actions = trigger.closest('.actions');
    const form = actions?.previousElementSibling;
    const hiddenInput = form instanceof HTMLFormElement ? qs('.media-gallery-rename-input', form) : null;

    if(!(form instanceof HTMLFormElement) || !(hiddenInput instanceof HTMLInputElement) || !(input instanceof HTMLInputElement)){
      return;
    }

    activeForm = form;
    activeHiddenInput = hiddenInput;
    lastTrigger = trigger;

    input.value = trigger.getAttribute('data-media-custom-name') || trigger.getAttribute('data-media-original-name') || '';

    modal.removeAttribute('hidden');
    modal.setAttribute('aria-hidden', 'false');
    input.focus({ preventScroll: true });
    input.select();
  };

  triggers.forEach((trigger)=>{
    trigger.addEventListener('click', ()=>{
      openModal(trigger);
    });
  });

  closeButton?.addEventListener('click', closeModal);
  cancelButton?.addEventListener('click', closeModal);

  submitButton?.addEventListener('click', ()=>{
    if(activeForm instanceof HTMLFormElement && activeHiddenInput instanceof HTMLInputElement && input instanceof HTMLInputElement){
      activeHiddenInput.value = input.value.trim();
      activeForm.submit();
    }
  });

  dialog?.addEventListener('click', (event)=>{
    event.stopPropagation();
  });

  modal.addEventListener('click', (event)=>{
    if(event.target === modal){
      closeModal();
    }
  });

  document.addEventListener('keydown', (event)=>{
    if(modal.hasAttribute('hidden')) return;

    if(event.key === 'Escape'){
      event.preventDefault();
      closeModal();
    }

    if(event.key === 'Enter' && event.target === input){
      event.preventDefault();
      submitButton?.click();
    }
  });
}

export function setupMediaGalleryDropSlot(){
  const uploadForm = qs('[data-gallery-upload-form]');
  const uploadInput = qs('[data-gallery-upload-input]');

  qsa('[data-media-gallery-drop-slot]').forEach((slot)=>{
    if(!(slot instanceof HTMLElement)) return;

    let dragDepth = 0;
    const setActive = (isActive)=>{
      slot.classList.toggle('is-drag-over', isActive);
    };

    ['dragenter', 'dragover'].forEach((eventName)=>{
      slot.addEventListener(eventName, (event)=>{
        event.preventDefault();

        if(eventName === 'dragenter'){
          dragDepth += 1;
        }

        setActive(true);
      });
    });

    slot.addEventListener('dragleave', (event)=>{
      event.preventDefault();
      dragDepth = Math.max(0, dragDepth - 1);

      if(dragDepth === 0){
        setActive(false);
      }
    });

    slot.addEventListener('drop', (event)=>{
      event.preventDefault();
      event.stopPropagation();
      dragDepth = 0;
      setActive(false);

      if(!(uploadForm instanceof HTMLFormElement) || !(uploadInput instanceof HTMLInputElement)){
        return;
      }

      const files = event.dataTransfer?.files;
      if(!files || files.length === 0){
        return;
      }

      if(!assignFilesToInput(uploadInput, files)){
        return;
      }

      slot.classList.add('is-uploading');
      if(typeof uploadForm.requestSubmit === 'function'){
        uploadForm.requestSubmit();
        return;
      }

      uploadForm.submit();
    });
  });
}

export function setupMediaGallerySorting(){
  const form = qs('[data-media-gallery-search-form]');
  const sortSelect = qs('[data-media-gallery-sort-select]', form);

  if(!(form instanceof HTMLFormElement) || !(sortSelect instanceof HTMLSelectElement)){
    return;
  }

  sortSelect.addEventListener('change', ()=>{
    if(typeof form.requestSubmit === 'function'){
      form.requestSubmit();
      return;
    }

    form.submit();
  });
}
