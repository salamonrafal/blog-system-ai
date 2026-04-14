import { registerI18nListener } from './i18n.js';
import { qs, qsa } from './shared.js';

const customSelectRegistry = new WeakMap();
let documentClickHandlerRegistered = false;
let customSelectInstanceCounter = 0;
let floatingPanelSyncFrame = 0;
let floatingPanelListenersRegistered = false;

function getOpenCustomSelectInstances(){
  return qsa('.app-select.is-open')
    .map((openSelect)=> {
      const select = qs('select.article-editor-select', openSelect);
      return select instanceof HTMLSelectElement ? customSelectRegistry.get(select) : null;
    })
    .filter(Boolean);
}

function syncFloatingCustomSelectPanels(){
  floatingPanelSyncFrame = 0;
  getOpenCustomSelectInstances().forEach((instance)=> instance.updateFloatingPanelPosition());
}

function scheduleFloatingCustomSelectPanelsSync(){
  if(0 !== floatingPanelSyncFrame) return;
  floatingPanelSyncFrame = window.requestAnimationFrame(syncFloatingCustomSelectPanels);
}

function handleFloatingPanelViewportChange(){
  scheduleFloatingCustomSelectPanelsSync();
}

function updateFloatingPanelListeners(){
  const hasOpenPanels = getOpenCustomSelectInstances().some((instance)=> instance.isFloatingPanelActive());

  if(hasOpenPanels && !floatingPanelListenersRegistered){
    window.addEventListener('resize', handleFloatingPanelViewportChange);
    window.addEventListener('scroll', handleFloatingPanelViewportChange, true);
    floatingPanelListenersRegistered = true;
    return;
  }

  if(!hasOpenPanels && floatingPanelListenersRegistered){
    window.removeEventListener('resize', handleFloatingPanelViewportChange);
    window.removeEventListener('scroll', handleFloatingPanelViewportChange, true);
    if(0 !== floatingPanelSyncFrame){
      window.cancelAnimationFrame(floatingPanelSyncFrame);
      floatingPanelSyncFrame = 0;
    }
    floatingPanelListenersRegistered = false;
  }
}

function isEnhanceableSelect(select){
  return select instanceof HTMLSelectElement
    && select.classList.contains('article-editor-select')
    && !select.multiple
    && select.size <= 1;
}

function buildOptionItems(select){
  return [...select.options]
    .map((option, index)=> ({
      value: option.value,
      label: option.textContent ?? '',
      disabled: option.disabled,
      selected: option.selected,
      index,
    }));
}

function getActiveOption(panel){
  return qs('.app-select-option.is-active', panel);
}

function createCustomSelect(select){
  const wrapper = document.createElement('div');
  wrapper.className = 'app-select';
  const instanceId = ++customSelectInstanceCounter;
  const triggerId = select.id ? `${select.id}--trigger` : '';
  const panelId = select.id ? `${select.id}--panel` : '';
  const label = select.id ? document.querySelector(`label[for="${select.id}"]`) : null;
  const labelId = label instanceof HTMLLabelElement
    ? (label.id || `${select.id}--label`)
    : '';

  const trigger = document.createElement('button');
  trigger.type = 'button';
  trigger.className = 'app-select-trigger';
  trigger.setAttribute('role', 'combobox');
  trigger.setAttribute('aria-haspopup', 'listbox');
  trigger.setAttribute('aria-expanded', 'false');
  if(triggerId) trigger.id = triggerId;
  if(panelId) trigger.setAttribute('aria-controls', panelId);
  if(labelId) trigger.setAttribute('aria-labelledby', labelId);

  const triggerLabel = document.createElement('span');
  triggerLabel.className = 'app-select-trigger-label';

  const triggerCaret = document.createElement('span');
  triggerCaret.className = 'app-select-trigger-caret';
  triggerCaret.setAttribute('aria-hidden', 'true');
  triggerCaret.innerHTML = '<svg viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"></path></svg>';

  trigger.append(triggerLabel, triggerCaret);

  const panel = document.createElement('div');
  panel.className = 'app-select-panel';
  panel.setAttribute('role', 'listbox');
  if(panelId) panel.id = panelId;
  panel.hidden = true;

  const parent = select.parentNode;
  if(!parent) return null;
  parent.insertBefore(wrapper, select);
  wrapper.appendChild(select);
  wrapper.append(trigger, panel);

  select.classList.add('app-select-native');
  select.tabIndex = -1;
  select.setAttribute('aria-hidden', 'true');
  if(label instanceof HTMLLabelElement && labelId){
    label.id = labelId;
    label.addEventListener('click', (event)=>{
      event.preventDefault();
      if(trigger.disabled) return;
      trigger.focus({ preventScroll: true });
      trigger.click();
    });
  }

  const syncInvalidState = ()=>{
    wrapper.classList.toggle('is-invalid', select.getAttribute('aria-invalid') === 'true');
  };

  const syncAccessibilityState = ()=>{
    const describedBy = select.getAttribute('aria-describedby');
    const required = select.getAttribute('aria-required');
    const invalid = select.getAttribute('aria-invalid');
    const ariaLabel = select.getAttribute('aria-label');

    if(describedBy){
      trigger.setAttribute('aria-describedby', describedBy);
    } else {
      trigger.removeAttribute('aria-describedby');
    }

    if(required){
      trigger.setAttribute('aria-required', required);
    } else {
      trigger.removeAttribute('aria-required');
    }

    if(invalid){
      trigger.setAttribute('aria-invalid', invalid);
    } else {
      trigger.removeAttribute('aria-invalid');
    }

    if(labelId){
      trigger.removeAttribute('aria-label');
    } else if(ariaLabel){
      trigger.setAttribute('aria-label', ariaLabel);
    } else {
      trigger.removeAttribute('aria-label');
    }
  };

  const syncActiveDescendant = ()=>{
    const activeOption = getActiveOption(panel);
    if(activeOption?.id){
      trigger.setAttribute('aria-activedescendant', activeOption.id);
      return;
    }

    trigger.removeAttribute('aria-activedescendant');
  };

  let floatingPanelOriginalParent = null;
  let floatingPanelOriginalNextSibling = null;

  const isFloatingPanelActive = ()=> panel.classList.contains('is-floating');

  const updateFloatingPanelPosition = ()=>{
    if(!isFloatingPanelActive()) return;

    const triggerRect = trigger.getBoundingClientRect();
    const panelRect = panel.getBoundingClientRect();
    const viewportPadding = 12;
    const minimumWidth = triggerRect.width;
    const preferredWidth = Math.max(panelRect.width, minimumWidth);
    const maxWidth = Math.max(minimumWidth, window.innerWidth - (viewportPadding * 2));
    const width = Math.min(preferredWidth, maxWidth);
    const maxLeft = window.scrollX + window.innerWidth - width - viewportPadding;
    const left = Math.max(window.scrollX + viewportPadding, Math.min(window.scrollX + triggerRect.left, maxLeft));
    const availableHeight = Math.max(160, window.innerHeight - triggerRect.bottom - 20);

    panel.style.width = `${width}px`;
    panel.style.left = `${left}px`;
    panel.style.top = `${window.scrollY + triggerRect.bottom + 8}px`;
    panel.style.maxHeight = `${Math.min(320, availableHeight)}px`;
  };

  const floatPanel = ()=>{
    if(isFloatingPanelActive()) return;

    floatingPanelOriginalParent = panel.parentElement;
    floatingPanelOriginalNextSibling = panel.nextSibling;
    panel.classList.add('is-floating');
    document.body.appendChild(panel);
    updateFloatingPanelListeners();
    updateFloatingPanelPosition();
  };

  const restorePanel = ()=>{
    if(!isFloatingPanelActive() || !floatingPanelOriginalParent) return;

    if(floatingPanelOriginalNextSibling && floatingPanelOriginalNextSibling.parentNode === floatingPanelOriginalParent){
      floatingPanelOriginalParent.insertBefore(panel, floatingPanelOriginalNextSibling);
    }else{
      floatingPanelOriginalParent.appendChild(panel);
    }

    panel.classList.remove('is-floating');
    panel.style.removeProperty('width');
    panel.style.removeProperty('left');
    panel.style.removeProperty('top');
    panel.style.removeProperty('max-height');
    floatingPanelOriginalParent = null;
    floatingPanelOriginalNextSibling = null;
    updateFloatingPanelListeners();
  };

  const close = ()=>{
    wrapper.classList.remove('is-open');
    panel.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    qsa('.app-select-option.is-active', panel).forEach((option)=> option.classList.remove('is-active'));
    syncActiveDescendant();
    restorePanel();
  };

  const open = ()=>{
    if(select.disabled) return;
    wrapper.classList.add('is-open');
    panel.hidden = false;
    trigger.setAttribute('aria-expanded', 'true');
    floatPanel();
  };

  const sync = ()=>{
    syncInvalidState();
    syncAccessibilityState();
    trigger.disabled = select.disabled;
    trigger.classList.toggle('is-disabled', select.disabled);

    const items = buildOptionItems(select);
    const selectedItem = items.find((item)=> item.selected) ?? items[0] ?? null;
    triggerLabel.textContent = selectedItem ? selectedItem.label : '';
    panel.replaceChildren();

    items.filter((item)=> !item.disabled).forEach((item)=>{
      const optionButton = document.createElement('button');
      optionButton.type = 'button';
      optionButton.className = 'app-select-option';
      optionButton.id = panelId
        ? `${panelId}--option-${item.index}`
        : `app-select-option-${instanceId}-${item.index}`;
      optionButton.setAttribute('role', 'option');
      optionButton.setAttribute('data-value', item.value);
      optionButton.setAttribute('aria-selected', item.selected ? 'true' : 'false');
      optionButton.classList.toggle('is-selected', item.selected);
      optionButton.textContent = item.label;

      optionButton.addEventListener('click', ()=>{
        select.value = item.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        sync();
        close();
        trigger.focus({ preventScroll: true });
      });

      optionButton.addEventListener('mouseenter', ()=>{
        qsa('.app-select-option.is-active', panel).forEach((option)=> option.classList.remove('is-active'));
        optionButton.classList.add('is-active');
        syncActiveDescendant();
      });

      panel.appendChild(optionButton);
    });

    syncActiveDescendant();
  };

  const focusOptionByOffset = (offset)=>{
    const options = qsa('.app-select-option', panel);
    if(!options.length) return;

    const currentIndex = options.findIndex((option)=> option.classList.contains('is-active'));
    const selectedIndex = options.findIndex((option)=> option.classList.contains('is-selected'));
    const startIndex = currentIndex >= 0 ? currentIndex : (selectedIndex >= 0 ? selectedIndex : 0);
    const nextIndex = (startIndex + offset + options.length) % options.length;

    options.forEach((option)=> option.classList.remove('is-active'));
    options[nextIndex]?.classList.add('is-active');
    options[nextIndex]?.scrollIntoView({ block: 'nearest' });
    syncActiveDescendant();
  };

  trigger.addEventListener('click', ()=>{
    if(wrapper.classList.contains('is-open')){
      close();
      return;
    }

    qsa('.app-select.is-open').forEach((openSelect)=>{
      if(openSelect !== wrapper){
        const instance = customSelectRegistry.get(qs('select', openSelect));
        instance?.close();
      }
    });

    open();
  });

  trigger.addEventListener('keydown', (event)=>{
    if(event.key === 'ArrowDown'){
      event.preventDefault();
      if(!wrapper.classList.contains('is-open')) open();
      focusOptionByOffset(1);
      return;
    }

    if(event.key === 'ArrowUp'){
      event.preventDefault();
      if(!wrapper.classList.contains('is-open')) open();
      focusOptionByOffset(-1);
      return;
    }

    if(event.key === 'Escape'){
      close();
      return;
    }

    if((event.key === 'Enter' || event.key === ' ') && wrapper.classList.contains('is-open')){
      const activeOption = getActiveOption(panel);
      if(activeOption){
        event.preventDefault();
        activeOption.click();
        return;
      }
    }
  });

  panel.addEventListener('keydown', (event)=>{
    if(event.key === 'ArrowDown'){
      event.preventDefault();
      focusOptionByOffset(1);
      return;
    }

    if(event.key === 'ArrowUp'){
      event.preventDefault();
      focusOptionByOffset(-1);
      return;
    }

    if(event.key === 'Escape'){
      event.preventDefault();
      close();
      trigger.focus({ preventScroll: true });
      return;
    }

    if(event.key === 'Enter' || event.key === ' '){
      const activeOption = getActiveOption(panel);
      if(!activeOption) return;
      event.preventDefault();
      activeOption.click();
    }
  });

  select.addEventListener('change', sync);
  select.addEventListener('custom-select:sync', sync);

  const instance = {
    close,
    isFloatingPanelActive,
    panel,
    sync,
    updateFloatingPanelPosition,
    wrapper,
  };
  customSelectRegistry.set(select, instance);
  sync();

  return instance;
}

function registerDocumentClickHandler(){
  if(documentClickHandlerRegistered) return;

  document.addEventListener('click', (event)=>{
    qsa('.app-select.is-open').forEach((openSelect)=>{
      if(openSelect.contains(event.target)) return;

      const select = qs('select.article-editor-select', openSelect);
      if(!(select instanceof HTMLSelectElement)) return;

      const instance = customSelectRegistry.get(select);
      if(instance?.panel?.contains(event.target)) return;
      instance?.close();
    });
  });

  documentClickHandlerRegistered = true;
}

export function setupCustomSelects(){
  registerDocumentClickHandler();

  qsa('select.article-editor-select').forEach((select)=>{
    if(!isEnhanceableSelect(select)) return;

    const existingInstance = customSelectRegistry.get(select);
    if(existingInstance){
      existingInstance.sync();
      return;
    }

    createCustomSelect(select);
  });
}

registerI18nListener(()=>{
  qsa('select.article-editor-select').forEach((select)=>{
    const instance = customSelectRegistry.get(select);
    instance?.sync();
  });
});
