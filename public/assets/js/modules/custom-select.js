import { registerI18nListener } from './i18n.js';
import { qs, qsa } from './shared.js';

const customSelectRegistry = new WeakMap();

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

function createCustomSelect(select){
  const wrapper = document.createElement('div');
  wrapper.className = 'app-select';

  const trigger = document.createElement('button');
  trigger.type = 'button';
  trigger.className = 'app-select-trigger';
  trigger.setAttribute('aria-haspopup', 'listbox');
  trigger.setAttribute('aria-expanded', 'false');

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
  panel.hidden = true;

  const parent = select.parentNode;
  if(!parent) return null;
  parent.insertBefore(wrapper, select);
  wrapper.appendChild(select);
  wrapper.append(trigger, panel);

  select.classList.add('app-select-native');
  select.tabIndex = -1;
  select.setAttribute('aria-hidden', 'true');

  const syncInvalidState = ()=>{
    wrapper.classList.toggle('is-invalid', select.getAttribute('aria-invalid') === 'true');
  };

  const close = ()=>{
    wrapper.classList.remove('is-open');
    panel.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    qsa('.app-select-option.is-active', panel).forEach((option)=> option.classList.remove('is-active'));
  };

  const open = ()=>{
    if(select.disabled) return;
    wrapper.classList.add('is-open');
    panel.hidden = false;
    trigger.setAttribute('aria-expanded', 'true');
  };

  const sync = ()=>{
    syncInvalidState();
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
      });

      panel.appendChild(optionButton);
    });
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
      const activeOption = qs('.app-select-option.is-active', panel);
      if(!activeOption) return;
      event.preventDefault();
      activeOption.click();
    }
  });

  select.addEventListener('change', sync);

  const handleDocumentClick = (event)=>{
    if(wrapper.contains(event.target)) return;
    close();
  };

  document.addEventListener('click', handleDocumentClick);

  const instance = { sync, close };
  customSelectRegistry.set(select, instance);
  sync();

  return instance;
}

export function setupCustomSelects(){
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
