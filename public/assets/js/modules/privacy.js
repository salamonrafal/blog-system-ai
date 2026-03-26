import { applyI18n } from './i18n.js';
import { getLang } from './preferences.js';
import { qs } from './shared.js';

export function setupPrivacyNotice(){
  const storageKey = 'privacy-consent';
  const acceptedValue = 'accepted';
  const declinedValue = 'declined';
  const existingConsent = localStorage.getItem(storageKey);
  if(existingConsent === acceptedValue || existingConsent === declinedValue) return;

  const existingPopup = qs('.privacy-popup');
  if(existingPopup){
    applyI18n(getLang());
    return;
  }

  const popupTitleId = 'privacy-popup-title';
  const popupTextId = 'privacy-popup-text';
  const popup = document.createElement('aside');
  popup.className = 'privacy-popup';
  popup.setAttribute('role', 'dialog');
  popup.setAttribute('aria-live', 'polite');
  popup.setAttribute('aria-labelledby', popupTitleId);
  popup.setAttribute('aria-describedby', popupTextId);
  popup.setAttribute('aria-modal', 'false');
  popup.innerHTML = `
    <h2 id="${popupTitleId}" class="privacy-popup-title" data-i18n="privacy_title">Prywatność</h2>
    <p id="${popupTextId}" class="privacy-popup-text" data-i18n="privacy_text">Ta strona używa cookies i podobnych technologii do działania serwisu oraz analityki.</p>
    <div class="privacy-popup-actions">
      <button type="button" class="btn" data-action="privacy-decline" data-i18n="privacy_decline">Odrzuć</button>
      <button type="button" class="btn primary" data-action="privacy-accept" data-i18n="privacy_accept">Akceptuję</button>
    </div>
  `;

  document.body.appendChild(popup);
  applyI18n(getLang());

  let isClosing = false;
  const closePopup = (consentValue)=>{
    if(isClosing) return;
    isClosing = true;
    localStorage.setItem(storageKey, consentValue);
    popup.classList.add('is-hidden');
    window.setTimeout(()=>{
      popup.remove();
    }, 180);
  };

  const acceptButton = qs('[data-action="privacy-accept"]', popup);
  if(acceptButton){
    acceptButton.addEventListener('click', ()=>{
      closePopup(acceptedValue);
    });
    window.requestAnimationFrame(()=>{
      acceptButton.focus({ preventScroll: true });
    });
  }

  const declineButton = qs('[data-action="privacy-decline"]', popup);
  if(declineButton){
    declineButton.addEventListener('click', ()=>{
      closePopup(declinedValue);
    });
  }

  popup.addEventListener('keydown', (event)=>{
    if(event.key === 'Escape'){
      event.preventDefault();
      closePopup(declinedValue);
    }
  });
}
