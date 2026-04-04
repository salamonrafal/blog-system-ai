import { registerI18nListener } from './i18n.js';
import { qs, qsa } from './shared.js';

const customSelectRegistry = new WeakMap();
let documentClickHandlerRegistered = false;
let customSelectInstanceCounter = 0;

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

  const close = ()=>{
    wrapper.classList.remove('is-open');
    panel.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    qsa('.app-select-option.is-active', panel).forEach((option)=> option.classList.remove('is-active'));
    syncActiveDescendant();
  };

  const open = ()=>{
    if(select.disabled) return;
    wrapper.classList.add('is-open');
    panel.hidden = false;
    trigger.setAttribute('aria-expanded', 'true');
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

  const instance = { sync, close };
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

      customSelectRegistry.get(select)?.close();
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
