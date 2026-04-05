import { getLang } from './preferences.js';
import { i18n, registerI18nListener } from './i18n.js';
import { lockDocumentScroll, qs, qsa, unlockDocumentScroll } from './shared.js';

export function setupImagePreview(){
  const triggers = qsa('[data-action="open-image-preview"]');
  if(!triggers.length) return;

  const mobilePreviewQuery = window.matchMedia('(max-width: 700px)');
  const modal = document.createElement('div');
  modal.className = 'image-preview-modal';
  modal.setAttribute('hidden', '');
  modal.setAttribute('aria-hidden', 'true');
  modal.innerHTML = `
    <div class="image-preview-dialog" role="dialog" aria-modal="true">
      <button type="button" class="image-preview-nav image-preview-prev" data-action="preview-prev"><span class="image-preview-nav-icon" aria-hidden="true">‹</span></button>
      <button type="button" class="image-preview-fullscreen" data-action="toggle-preview-fullscreen">⛶</button>
      <button type="button" class="image-preview-close" data-action="close-image-preview">×</button>
      <figure class="image-preview-frame">
        <h2 class="image-preview-title"></h2>
        <img src="" alt="" />
        <section class="image-preview-meta">
          <div class="image-preview-meta-bar">
            <span class="image-preview-meta-label"></span>
            <button type="button" class="image-preview-meta-toggle" data-action="toggle-preview-caption"></button>
          </div>
          <div class="image-preview-caption">
            <div class="image-preview-caption-step"></div>
            <h3 class="image-preview-caption-title"></h3>
            <p class="image-preview-caption-text"></p>
          </div>
        </section>
      </figure>
      <button type="button" class="image-preview-nav image-preview-next" data-action="preview-next"><span class="image-preview-nav-icon" aria-hidden="true">›</span></button>
    </div>
  `;
  document.body.appendChild(modal);

  const dialog = qs('.image-preview-dialog', modal);
  const image = qs('.image-preview-frame img', modal);
  const closeButton = qs('[data-action="close-image-preview"]', modal);
  const fullscreenButton = qs('[data-action="toggle-preview-fullscreen"]', modal);
  const prevButton = qs('[data-action="preview-prev"]', modal);
  const nextButton = qs('[data-action="preview-next"]', modal);
  const previewTitle = qs('.image-preview-title', modal);
  const meta = qs('.image-preview-meta', modal);
  const metaLabel = qs('.image-preview-meta-label', modal);
  const metaToggle = qs('[data-action="toggle-preview-caption"]', modal);
  const captionStep = qs('.image-preview-caption-step', modal);
  const captionTitle = qs('.image-preview-caption-title', modal);
  const captionText = qs('.image-preview-caption-text', modal);
  let lastTrigger = null;
  let currentIndex = -1;
  let captionCollapsed = false;

  const getPreviewText = (key)=>{
    const lang = getLang();
    return (i18n[lang] && i18n[lang][key]) || i18n.pl[key] || '';
  };

  const syncAccessibilityLabels = ()=>{
    dialog.setAttribute('aria-label', getPreviewText('preview_dialog_label'));
    if(prevButton){
      prevButton.setAttribute('aria-label', getPreviewText('preview_previous_image'));
    }
    if(nextButton){
      nextButton.setAttribute('aria-label', getPreviewText('preview_next_image'));
    }
    if(closeButton){
      closeButton.setAttribute('aria-label', getPreviewText('preview_close'));
      closeButton.setAttribute('title', getPreviewText('preview_close'));
    }
  };

  syncAccessibilityLabels();

  const syncCaptionToggle = ()=>{
    if(!metaToggle) return;
    metaToggle.textContent = getPreviewText(captionCollapsed ? 'preview_show_details' : 'preview_hide_details');
    metaToggle.setAttribute('aria-expanded', captionCollapsed ? 'false' : 'true');
    dialog.classList.toggle('caption-collapsed', captionCollapsed);
  };

  const syncFullscreenToggle = ()=>{
    if(!fullscreenButton) return;
    const isFullscreen = dialog.classList.contains('is-fullscreen');
    fullscreenButton.textContent = isFullscreen ? '🗗' : '⛶';
    fullscreenButton.setAttribute('aria-label', getPreviewText(isFullscreen ? 'preview_exit_fullscreen' : 'preview_enter_fullscreen'));
    fullscreenButton.setAttribute('title', getPreviewText(isFullscreen ? 'preview_exit_fullscreen' : 'preview_enter_fullscreen'));
  };

  const syncCaptionContent = (trigger)=>{
    if(!trigger || !meta || !captionStep || !captionTitle || !captionText || !previewTitle) return;

    const entry = trigger.closest('.timeline-entry');
    if(!entry) return;

    const lang = getLang();
    const fallbackLang = lang === 'pl' ? 'en' : 'pl';
    const titleScope = qsa(`[data-lang="${lang}"]`, entry).find((scope)=> qs('.timeline-title', scope))
      || qsa(`[data-lang="${fallbackLang}"]`, entry).find((scope)=> qs('.timeline-title', scope));
    const textScope = qsa(`[data-lang="${lang}"]`, entry).find((scope)=> qs('p', scope))
      || qsa(`[data-lang="${fallbackLang}"]`, entry).find((scope)=> qs('p', scope));
    const titleElement = titleScope ? qs('.timeline-title', titleScope) : qs('.timeline-title', entry);
    const stepElement = qs('.timeline-step-label', entry);
    const textElement = textScope ? qs('p', textScope) : null;

    metaLabel.textContent = stepElement ? stepElement.textContent.trim() : '';
    captionStep.textContent = '';
    previewTitle.textContent = titleElement ? titleElement.textContent.trim() : '';
    captionTitle.textContent = '';
    captionText.textContent = textElement ? textElement.textContent.trim() : '';
  };

  const syncNav = ()=>{
    const hasPrev = currentIndex > 0;
    const hasNext = currentIndex >= 0 && currentIndex < triggers.length - 1;

    if(prevButton){
      prevButton.disabled = !hasPrev;
      prevButton.setAttribute('aria-hidden', hasPrev ? 'false' : 'true');
    }

    if(nextButton){
      nextButton.disabled = !hasNext;
      nextButton.setAttribute('aria-hidden', hasNext ? 'false' : 'true');
    }
  };

  const syncPreviewI18n = ()=>{
    syncAccessibilityLabels();
    syncCaptionToggle();
    syncFullscreenToggle();

    if(!modal.hasAttribute('hidden') && lastTrigger){
      syncCaptionContent(lastTrigger);
    }
  };

  registerI18nListener(syncPreviewI18n);

  const closePreview = ()=>{
    modal.setAttribute('hidden', '');
    modal.setAttribute('aria-hidden', 'true');
    unlockDocumentScroll();
    dialog.classList.remove('is-fullscreen');
    syncFullscreenToggle();
    if(lastTrigger){
      lastTrigger.focus();
    }
  };

  const openPreview = (trigger)=>{
    const src = trigger.getAttribute('data-image-src');
    const alt = trigger.getAttribute('data-image-alt') || '';
    if(!src) return;

    lastTrigger = trigger;
    currentIndex = triggers.indexOf(trigger);
    image.src = src;
    image.alt = alt;
    syncCaptionContent(trigger);
    syncAccessibilityLabels();
    syncNav();
    syncCaptionToggle();
    syncFullscreenToggle();
    modal.removeAttribute('hidden');
    modal.setAttribute('aria-hidden', 'false');
    lockDocumentScroll();
    closeButton.focus();
  };

  const openAdjacent = (direction)=>{
    const nextIndex = currentIndex + direction;
    const nextTrigger = triggers[nextIndex];
    if(nextTrigger){
      openPreview(nextTrigger);
    }
  };

  triggers.forEach((trigger)=>{
    trigger.addEventListener('click', (event)=>{
      if(mobilePreviewQuery.matches){
        event.preventDefault();
        return;
      }

      openPreview(trigger);
    });
  });

  if(closeButton){
    closeButton.addEventListener('click', closePreview);
  }
  if(fullscreenButton){
    fullscreenButton.addEventListener('click', ()=>{
      dialog.classList.toggle('is-fullscreen');
      syncFullscreenToggle();
    });
  }
  if(prevButton){
    prevButton.addEventListener('click', ()=>{
      openAdjacent(-1);
    });
  }
  if(nextButton){
    nextButton.addEventListener('click', ()=>{
      openAdjacent(1);
    });
  }
  if(metaToggle && meta){
    metaToggle.addEventListener('click', ()=>{
      captionCollapsed = !captionCollapsed;
      meta.classList.toggle('is-collapsed', captionCollapsed);
      syncCaptionToggle();
    });
  }

  modal.addEventListener('click', (event)=>{
    if(event.target === modal){
      closePreview();
    }
  });

  dialog.addEventListener('click', (event)=>{
    event.stopPropagation();
  });

  document.addEventListener('keydown', (event)=>{
    if(modal.hasAttribute('hidden')) return;

    if(event.key === 'Escape'){
      closePreview();
    }else if(event.key === 'ArrowLeft'){
      openAdjacent(-1);
    }else if(event.key === 'ArrowRight'){
      openAdjacent(1);
    }
  });
}
