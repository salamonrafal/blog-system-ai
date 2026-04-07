import { getTranslation, registerI18nListener } from './i18n.js';
import { lockDocumentScroll, qs, qsa, unlockDocumentScroll } from './shared.js';

export function createMediaImagePicker({
  modal,
  dialog,
  searchInput,
  sortSelect,
  grid,
  emptyResults,
  endpoint,
  getCurrentValue,
  onSelect,
  selectLabelKey = 'form_headline_image_picker_select',
  emptyKey = 'form_headline_image_picker_empty',
  noResultsKey = 'form_headline_image_picker_no_results',
}){
  if(!(modal instanceof HTMLElement) || !(grid instanceof HTMLElement) || typeof onSelect !== 'function'){
    return null;
  }

  let lastTrigger = null;
  let pickerAbortController = null;
  let searchDebounceId = 0;

  const normalizeSearchTerm = (value)=> String(value || '').trim();
  const getCloseButtons = ()=> qsa('[data-action="close-media-image-picker"]', modal);
  const getSelectButtons = ()=> qsa('[data-action="select-media-image"]', modal);
  const getCards = ()=> qsa('[data-media-image-card]', modal);

  const syncSelectedState = ()=>{
    const currentValue = typeof getCurrentValue === 'function'
      ? String(getCurrentValue() || '').trim()
      : '';

    getSelectButtons().forEach((button)=>{
      if(!(button instanceof HTMLElement)) return;

      const isSelected = (button.getAttribute('data-image-path') || '') === currentValue;
      button.classList.toggle('is-selected', isSelected);
      button.setAttribute('aria-selected', isSelected ? 'true' : 'false');

      const card = button.closest('.media-gallery-card');
      if(card instanceof HTMLElement){
        card.classList.toggle('is-selected', isSelected);
      }
    });
  };

  const renderCards = (images)=>{
    grid.replaceChildren();

    images.forEach((image)=>{
      const card = document.createElement('article');
      card.className = 'media-gallery-card headline-image-picker-card';
      card.dataset.mediaImageCard = 'true';

      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'media-gallery-preview headline-image-picker-preview';
      button.dataset.action = 'select-media-image';
      button.dataset.imagePath = image.publicPath || '';
      button.dataset.imageName = image.displayName || '';
      button.setAttribute('role', 'option');
      button.setAttribute('aria-selected', 'false');

      const img = document.createElement('img');
      img.src = image.publicPath || '';
      img.alt = image.displayName || '';
      img.loading = 'lazy';

      const overlay = document.createElement('span');
      overlay.className = 'headline-image-picker-overlay';

      const meta = document.createElement('span');
      meta.className = 'media-gallery-meta headline-image-picker-meta';

      const name = document.createElement('strong');
      name.className = 'media-gallery-name';
      name.textContent = image.displayName || '';

      const mime = document.createElement('span');
      mime.className = 'meta';
      mime.textContent = String(image.mimeType || '').replace(/^image\//, '').toUpperCase();

      const fileSize = document.createElement('span');
      fileSize.className = 'meta';
      fileSize.textContent = image.formattedFileSize || '';

      const actions = document.createElement('span');
      actions.className = 'headline-image-picker-actions';

      const selectLabel = document.createElement('span');
      selectLabel.className = 'button headline-image-picker-select-button';
      selectLabel.textContent = getTranslation(selectLabelKey);

      meta.append(name, mime, fileSize);
      actions.append(selectLabel);
      overlay.append(meta, actions);
      button.append(img, overlay);
      card.append(button);
      grid.append(card);
    });

    syncSelectedState();
  };

  const syncEmptyState = ()=>{
    if(!(emptyResults instanceof HTMLElement)) return;

    emptyResults.hidden = getCards().length > 0;
    emptyResults.textContent = searchInput instanceof HTMLInputElement && searchInput.value.trim() !== ''
      ? getTranslation(noResultsKey)
      : getTranslation(emptyKey);
  };

  const loadPickerImages = async ()=>{
    if(!(searchInput instanceof HTMLInputElement) || '' === endpoint) return;

    if(pickerAbortController instanceof AbortController){
      pickerAbortController.abort();
    }

    const params = new URLSearchParams({
      q: normalizeSearchTerm(searchInput.value),
      sort: sortSelect instanceof HTMLSelectElement ? sortSelect.value : 'desc',
    });

    pickerAbortController = new AbortController();

    try{
      const response = await fetch(`${endpoint}?${params.toString()}`, {
        method: 'GET',
        headers: {
          Accept: 'application/json',
        },
        signal: pickerAbortController.signal,
      });

      if(!response.ok){
        throw new Error(`Unexpected picker response: ${response.status}`);
      }

      const payload = await response.json();
      const images = Array.isArray(payload?.images) ? payload.images : [];
      renderCards(images);
    }catch(error){
      if(error instanceof DOMException && error.name === 'AbortError'){
        return;
      }

      renderCards([]);
    }

    syncEmptyState();
  };

  const close = ()=>{
    modal.setAttribute('hidden', '');
    modal.setAttribute('aria-hidden', 'true');
    unlockDocumentScroll();
    window.clearTimeout(searchDebounceId);

    if(pickerAbortController instanceof AbortController){
      pickerAbortController.abort();
      pickerAbortController = null;
    }

    if(lastTrigger instanceof HTMLElement){
      lastTrigger.focus({ preventScroll: true });
    }
  };

  const open = (trigger)=>{
    if(!modal.hasAttribute('hidden')){
      lastTrigger = trigger instanceof HTMLElement ? trigger : lastTrigger;
      syncSelectedState();
      return;
    }

    lastTrigger = trigger instanceof HTMLElement ? trigger : null;
    syncSelectedState();
    modal.removeAttribute('hidden');
    modal.setAttribute('aria-hidden', 'false');
    lockDocumentScroll();

    const focusTarget = searchInput instanceof HTMLElement
      ? searchInput
      : getSelectButtons().find((button)=> button instanceof HTMLElement && button.classList.contains('is-selected'))
      || getSelectButtons().find((button)=> button instanceof HTMLElement)
      || getCloseButtons().find((button)=> button instanceof HTMLElement);
    focusTarget?.focus({ preventScroll: true });

    void loadPickerImages();
  };

  const requestPickerImages = ()=>{
    window.clearTimeout(searchDebounceId);
    searchDebounceId = window.setTimeout(()=>{
      void loadPickerImages();
    }, 160);
  };

  getCloseButtons().forEach((button)=>{
    button.addEventListener('click', close);
  });

  searchInput?.addEventListener('input', requestPickerImages);
  searchInput?.addEventListener('search', requestPickerImages);
  searchInput?.addEventListener('keydown', (event)=>{
    if(event.key === 'Enter'){
      event.preventDefault();
      void loadPickerImages();
    }
  });

  sortSelect?.addEventListener('change', ()=>{
    void loadPickerImages();
  });

  grid.addEventListener('click', (event)=>{
    const target = event.target instanceof Element ? event.target : null;
    const button = target ? target.closest('[data-action="select-media-image"]') : null;
    if(!(button instanceof HTMLElement)) return;

    onSelect({
      path: button.getAttribute('data-image-path') || '',
      name: button.getAttribute('data-image-name') || '',
    });
    close();
  });

  if(dialog instanceof HTMLElement){
    dialog.addEventListener('click', (event)=>{
      event.stopPropagation();
    });
  }

  modal.addEventListener('click', (event)=>{
    if(event.target === modal){
      close();
    }
  });

  modal.addEventListener('keydown', (event)=>{
    if(modal.hasAttribute('hidden')) return;
    if(event.key === 'Escape'){
      event.preventDefault();
      close();
    }
  });

  syncEmptyState();
  registerI18nListener(()=>{
    syncEmptyState();
    getSelectButtons().forEach((button)=>{
      const label = qs('.headline-image-picker-select-button', button);
      if(label instanceof HTMLElement){
        label.textContent = getTranslation(selectLabelKey);
      }
    });
  });

  return {
    open,
    close,
    syncSelectedState,
  };
}
