import { getTranslation } from './i18n.js';

export function createSwitchButton({
  action,
  className,
  tooltipKey,
  checked = false,
  compact = false,
}){
  const button = document.createElement('button');
  button.type = 'button';
  button.className = `app-switch${compact ? ' app-switch-compact' : ''}${className ? ` ${className}` : ''}`;
  button.setAttribute('role', 'switch');
  button.setAttribute('data-action', action);
  button.setAttribute('data-i18n-tooltip', tooltipKey);
  button.setAttribute('data-i18n-aria', tooltipKey);
  button.setAttribute('data-tooltip', getTranslation(tooltipKey));
  button.setAttribute('aria-label', getTranslation(tooltipKey));
  button.setAttribute('aria-checked', checked ? 'true' : 'false');
  button.classList.toggle('is-active', checked);

  const slider = document.createElement('span');
  slider.className = 'app-switch-slider';
  slider.setAttribute('aria-hidden', 'true');
  button.appendChild(slider);

  return button;
}

export function syncSwitchButtonState(button, enabled, {
  enabledTooltipKey = null,
  disabledTooltipKey = null,
} = {}){
  if(!(button instanceof HTMLElement)) return;

  const tooltipKey = enabled ? enabledTooltipKey : disabledTooltipKey;
  if(tooltipKey){
    const label = getTranslation(tooltipKey);
    button.setAttribute('data-i18n-tooltip', tooltipKey);
    button.setAttribute('data-i18n-aria', tooltipKey);
    button.setAttribute('data-tooltip', label);
    button.setAttribute('aria-label', label);
  }

  button.setAttribute('aria-checked', enabled ? 'true' : 'false');
  button.classList.toggle('is-active', enabled);
}
