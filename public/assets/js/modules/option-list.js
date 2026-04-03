import { getTranslation, registerI18nListener } from './i18n.js';
import { qs, qsa } from './shared.js';

const optionListRegistry = new WeakMap();
let documentClickHandlerRegistered = false;

function getOptionAttribute(root, option, configAttr){
  const attributeName = root.getAttribute(configAttr) || '';
  return attributeName ? (option.getAttribute(attributeName) || '') : '';
}

function getOptions(root, select){
  return [...select.options].map((option)=> {
    const metaKey = getOptionAttribute(root, option, 'data-option-list-meta-key-attribute');
    return {
      value: option.value,
      label: getOptionAttribute(root, option, 'data-option-list-label-attribute') || option.textContent || '',
      meta: metaKey ? getTranslation(metaKey) : '',
      filterValue: getOptionAttribute(root, option, 'data-option-list-filter-attribute'),
      selected: option.selected,
      disabled: option.disabled,
      option,
    };
  });
}

function matchesFilter(root, item){
  const filterSourceId = root.getAttribute('data-option-list-filter-source-id');
  const sharedValue = root.getAttribute('data-option-list-shared-value') || '';
  if(!filterSourceId) return true;

  const source = document.getElementById(filterSourceId);
  if(!(source instanceof HTMLSelectElement)) return true;

  const currentValue = source.value;
  return item.filterValue === currentValue || item.filterValue === sharedValue;
}

function createMessage(text){
  const element = document.createElement('div');
  element.className = 'app-option-list-message';
  element.setAttribute('role', 'option');
  element.setAttribute('aria-disabled', 'true');
  element.textContent = text;

  return element;
}

function createOptionList(root){
  const select = qs('[data-option-list-select]', root);
  const shell = qs('[data-option-list-shell]', root);
  const input = qs('[data-option-list-input]', root);
  const selectedContainer = qs('[data-option-list-selected]', root);
  const resultsContainer = qs('[data-option-list-results]', root);
  if(!(select instanceof HTMLSelectElement) || !(input instanceof HTMLInputElement) || !selectedContainer || !resultsContainer || !shell) return null;

  let activeIndex = -1;
  let cachedItems = [];
  const label = select.id ? document.querySelector(`label[for="${select.id}"]`) : null;
  const labelId = label instanceof HTMLLabelElement
    ? (label.id || `${select.id}--label`)
    : '';
  const resultsId = select.id ? `${select.id}--results` : '';
  const baseDescribedBy = input.getAttribute('aria-describedby') || '';

  select.classList.add('app-option-list-native');
  select.tabIndex = -1;
  select.setAttribute('aria-hidden', 'true');
  shell.hidden = false;
  input.setAttribute('role', 'combobox');
  input.setAttribute('aria-autocomplete', 'list');
  input.setAttribute('aria-expanded', 'false');
  if(resultsId){
    resultsContainer.id = resultsId;
    input.setAttribute('aria-controls', resultsId);
  }
  resultsContainer.setAttribute('role', 'listbox');

  if(label instanceof HTMLLabelElement && labelId){
    label.id = labelId;
    input.setAttribute('aria-labelledby', labelId);
    label.addEventListener('click', (event)=>{
      event.preventDefault();
      input.focus({ preventScroll: true });
    });
  }

  const t = (keyAttr, fallback = '')=>{
    const key = root.getAttribute(keyAttr);
    return key ? getTranslation(key) : fallback;
  };

  const syncAccessibilityState = ()=>{
    const describedBy = [baseDescribedBy, select.getAttribute('aria-describedby') || '']
      .filter(Boolean)
      .filter((value, index, values)=> values.indexOf(value) === index)
      .join(' ');
    const required = select.getAttribute('aria-required');
    const invalid = select.getAttribute('aria-invalid');
    const ariaLabel = select.getAttribute('aria-label');

    if(describedBy){
      input.setAttribute('aria-describedby', describedBy);
    } else {
      input.removeAttribute('aria-describedby');
    }

    if(required){
      input.setAttribute('aria-required', required);
    } else {
      input.removeAttribute('aria-required');
    }

    if(invalid){
      input.setAttribute('aria-invalid', invalid);
    } else {
      input.removeAttribute('aria-invalid');
    }

    if(labelId){
      input.removeAttribute('aria-label');
      return;
    }

    if(ariaLabel){
      input.setAttribute('aria-label', ariaLabel);
      return;
    }

    input.removeAttribute('aria-label');
  };

  const syncActiveDescendant = ()=>{
    const activeItem = qsa('[data-option-list-item]', resultsContainer)[activeIndex];
    if(activeItem?.id){
      input.setAttribute('aria-activedescendant', activeItem.id);
      return;
    }

    input.removeAttribute('aria-activedescendant');
  };

  const hideResults = ()=>{
    resultsContainer.hidden = true;
    input.setAttribute('aria-expanded', 'false');
    activeIndex = -1;
    syncActiveDescendant();
  };

  const refreshItems = ()=>{
    cachedItems = getOptions(root, select);
  };

  const renderSelected = ()=>{
    selectedContainer.replaceChildren();
    const selectedItems = cachedItems.filter((item)=> item.selected);
    const isDisabled = select.disabled;

    selectedItems.forEach((item)=>{
      const chip = document.createElement('span');
      chip.className = 'app-option-list-chip';

      const label = document.createElement('span');
      label.className = 'app-option-list-chip-label';
      label.textContent = item.label;

      if(item.meta){
        const meta = document.createElement('span');
        meta.className = 'app-option-list-chip-meta';
        meta.textContent = item.meta;
        label.appendChild(meta);
      }

      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'app-option-list-chip-remove';
      remove.textContent = '×';
      remove.disabled = isDisabled;
      remove.setAttribute('aria-label', `${t('data-option-list-remove-label-key', 'Remove')} ${item.label}`);
      remove.addEventListener('click', ()=>{
        if(select.disabled) return;
        item.option.selected = false;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        sync();
        input.focus({ preventScroll: true });
      });

      chip.append(label, remove);
      selectedContainer.appendChild(chip);
    });
  };

  const renderResults = ()=>{
    const query = input.value.trim().toLowerCase();
    const shouldShowResults = document.activeElement === input && query.length >= 1;
    if(select.disabled){
      hideResults();
      return;
    }

    const availableItems = cachedItems
      .filter((item)=> !item.disabled && !item.selected)
      .filter((item)=> matchesFilter(root, item));
    const matchingItems = availableItems.filter((item)=> item.label.toLowerCase().includes(query));

    resultsContainer.replaceChildren();
    activeIndex = -1;
    syncActiveDescendant();

    if(!shouldShowResults){
      hideResults();
      return;
    }

    if(!availableItems.length){
      resultsContainer.appendChild(createMessage(t('data-option-list-empty-key', '')));
      resultsContainer.hidden = false;
      input.setAttribute('aria-expanded', 'true');
      return;
    }

    if(!matchingItems.length){
      resultsContainer.appendChild(createMessage(t('data-option-list-no-results-key', '')));
      resultsContainer.hidden = false;
      input.setAttribute('aria-expanded', 'true');
      return;
    }

    matchingItems.forEach((item, index)=>{
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'app-option-list-item';
      button.id = resultsId ? `${resultsId}--option-${index}` : `app-option-list-option-${index}`;
      button.setAttribute('data-option-list-item', 'true');
      button.setAttribute('data-value', item.value);
      button.setAttribute('role', 'option');
      button.setAttribute('aria-selected', 'false');

      const name = document.createElement('span');
      name.className = 'app-option-list-item-name';
      name.textContent = item.label;

      const meta = document.createElement('span');
      meta.className = 'app-option-list-item-meta';
      meta.textContent = item.meta;

      button.append(name, meta);

      button.addEventListener('click', ()=>{
        if(select.disabled) return;
        item.option.selected = true;
        input.value = '';
        select.dispatchEvent(new Event('change', { bubbles: true }));
        sync();
        input.focus({ preventScroll: true });
      });

      button.addEventListener('mouseenter', ()=>{
        qsa('[data-option-list-item]', resultsContainer).forEach((element)=> element.classList.remove('is-active'));
        button.classList.add('is-active');
        activeIndex = index;
        syncActiveDescendant();
      });

      resultsContainer.appendChild(button);
    });

    resultsContainer.hidden = false;
    input.setAttribute('aria-expanded', 'true');
  };

  const sync = ()=>{
    refreshItems();
    syncAccessibilityState();
    input.disabled = select.disabled;
    renderSelected();
    renderResults();
  };

  const moveActive = (direction)=>{
    const items = qsa('[data-option-list-item]', resultsContainer);
    if(!items.length) return;

    activeIndex = (activeIndex + direction + items.length) % items.length;
    items.forEach((item)=> {
      item.classList.remove('is-active');
      item.setAttribute('aria-selected', 'false');
    });
    items[activeIndex]?.classList.add('is-active');
    items[activeIndex]?.setAttribute('aria-selected', 'true');
    items[activeIndex]?.scrollIntoView({ block: 'nearest' });
    syncActiveDescendant();
  };

  input.addEventListener('focus', ()=> {
    if(select.disabled) return;
    renderResults();
  });

  input.addEventListener('input', ()=> {
    if(select.disabled) return;
    renderResults();
  });

  input.addEventListener('blur', ()=> {
    window.setTimeout(()=>{
      if(root.contains(document.activeElement)) return;
      hideResults();
    }, 0);
  });

  input.addEventListener('keydown', (event)=>{
    if(select.disabled) return;

    if(event.key === 'ArrowDown'){
      event.preventDefault();
      moveActive(1);
      return;
    }

    if(event.key === 'ArrowUp'){
      event.preventDefault();
      moveActive(-1);
      return;
    }

    if(event.key === 'Enter'){
      const activeItem = qsa('[data-option-list-item]', resultsContainer)[activeIndex];
      if(activeItem){
        event.preventDefault();
        activeItem.click();
      }
      return;
    }

    if(event.key === 'Backspace' && '' === input.value){
      const selectedItems = cachedItems.filter((item)=> item.selected);
      const lastSelected = selectedItems[selectedItems.length - 1];
      if(lastSelected){
        lastSelected.option.selected = false;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        sync();
      }
    }
  });

  select.addEventListener('change', sync);

  const filterSourceId = root.getAttribute('data-option-list-filter-source-id');
  if(filterSourceId){
    const filterSource = document.getElementById(filterSourceId);
    filterSource?.addEventListener('change', ()=> {
      renderResults();
    });
  }

  const instance = { sync };
  optionListRegistry.set(root, instance);
  sync();
  hideResults();

  return instance;
}

function registerDocumentClickHandler(){
  if(documentClickHandlerRegistered) return;

  document.addEventListener('click', (event)=>{
    qsa('[data-option-list]').forEach((root)=>{
      if(root.contains(event.target)) return;

      const resultsContainer = qs('[data-option-list-results]', root);
      const input = qs('[data-option-list-input]', root);
      if(resultsContainer){
        resultsContainer.hidden = true;
      }
      if(input instanceof HTMLInputElement){
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
      }
    });
  });

  documentClickHandlerRegistered = true;
}

export function setupOptionLists(){
  registerDocumentClickHandler();

  qsa('[data-option-list]').forEach((root)=>{
    const existingInstance = optionListRegistry.get(root);
    if(existingInstance){
      existingInstance.sync();
      return;
    }

    createOptionList(root);
  });
}

registerI18nListener(()=>{
  qsa('[data-option-list]').forEach((root)=>{
    optionListRegistry.get(root)?.sync();
  });
});
