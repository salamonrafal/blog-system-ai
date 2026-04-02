import { getTranslation, registerI18nListener } from './i18n.js';
import { qs, qsa } from './shared.js';

const optionListRegistry = new WeakMap();

function getOptions(select){
  return [...select.options].map((option)=> {
    const metaKey = option.getAttribute('data-keyword-scope-key') || '';
    return {
      value: option.value,
      label: option.dataset.keywordName || option.textContent || '',
      meta: metaKey ? getTranslation(metaKey) : '',
      filterValue: option.getAttribute('data-keyword-language') || '',
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
  element.textContent = text;

  return element;
}

function createOptionList(root){
  const select = qs('[data-option-list-select]', root);
  const input = qs('[data-option-list-input]', root);
  const selectedContainer = qs('[data-option-list-selected]', root);
  const resultsContainer = qs('[data-option-list-results]', root);
  if(!(select instanceof HTMLSelectElement) || !(input instanceof HTMLInputElement) || !selectedContainer || !resultsContainer) return null;

  let activeIndex = -1;

  const t = (keyAttr, fallback = '')=>{
    const key = root.getAttribute(keyAttr);
    return key ? getTranslation(key) : fallback;
  };

  const renderSelected = ()=>{
    selectedContainer.replaceChildren();
    const selectedItems = getOptions(select).filter((item)=> item.selected);

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
      remove.setAttribute('aria-label', `${t('data-option-list-remove-label-key', 'Remove')} ${item.label}`);
      remove.addEventListener('click', ()=>{
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
    const availableItems = getOptions(select)
      .filter((item)=> !item.disabled && !item.selected)
      .filter((item)=> matchesFilter(root, item));
    const matchingItems = availableItems.filter((item)=> item.label.toLowerCase().includes(query));

    resultsContainer.replaceChildren();
    activeIndex = -1;

    if(!shouldShowResults){
      resultsContainer.hidden = true;
      return;
    }

    if(!availableItems.length){
      resultsContainer.appendChild(createMessage(t('data-option-list-empty-key', '')));
      resultsContainer.hidden = false;
      return;
    }

    if(!matchingItems.length){
      resultsContainer.appendChild(createMessage(t('data-option-list-no-results-key', '')));
      resultsContainer.hidden = false;
      return;
    }

    matchingItems.forEach((item, index)=>{
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'app-option-list-item';
      button.setAttribute('data-option-list-item', 'true');
      button.setAttribute('data-value', item.value);

      const name = document.createElement('span');
      name.className = 'app-option-list-item-name';
      name.textContent = item.label;

      const meta = document.createElement('span');
      meta.className = 'app-option-list-item-meta';
      meta.textContent = item.meta;

      button.append(name, meta);

      button.addEventListener('click', ()=>{
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
      });

      resultsContainer.appendChild(button);
    });

    resultsContainer.hidden = false;
  };

  const sync = ()=>{
    renderSelected();
    renderResults();
  };

  const moveActive = (direction)=>{
    const items = qsa('[data-option-list-item]', resultsContainer);
    if(!items.length) return;

    activeIndex = (activeIndex + direction + items.length) % items.length;
    items.forEach((item)=> item.classList.remove('is-active'));
    items[activeIndex]?.classList.add('is-active');
    items[activeIndex]?.scrollIntoView({ block: 'nearest' });
  };

  input.addEventListener('focus', ()=> {
    renderResults();
  });

  input.addEventListener('input', ()=> {
    renderResults();
  });

  input.addEventListener('blur', ()=> {
    window.setTimeout(()=>{
      if(root.contains(document.activeElement)) return;
      resultsContainer.hidden = true;
    }, 0);
  });

  input.addEventListener('keydown', (event)=>{
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
      const selectedItems = getOptions(select).filter((item)=> item.selected);
      const lastSelected = selectedItems[selectedItems.length - 1];
      if(lastSelected){
        lastSelected.option.selected = false;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        sync();
      }
    }
  });

  document.addEventListener('click', (event)=>{
    if(root.contains(event.target)) return;
    resultsContainer.hidden = true;
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
  renderSelected();
  resultsContainer.hidden = true;

  return instance;
}

export function setupOptionLists(){
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
