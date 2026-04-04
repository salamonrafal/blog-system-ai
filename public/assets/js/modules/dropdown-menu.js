import { qs } from './shared.js';

export function createDropdownMenu(root){
  if(!root) return null;

  const trigger = qs('[data-dropdown-menu-trigger]', root);
  const panel = qs('[data-dropdown-menu-panel]', root);

  if(!trigger || !panel) return null;

  const hideTooltip = ()=>{
    document.dispatchEvent(new Event('app:hide-tooltip'));
    const activeTooltip = qs('.app-tooltip');
    if(activeTooltip){
      activeTooltip.setAttribute('hidden', '');
      activeTooltip.setAttribute('aria-hidden', 'true');
      activeTooltip.classList.remove('is-wide');
      activeTooltip.classList.remove('is-wrap');
      activeTooltip.classList.remove('is-multiline');
      activeTooltip.textContent = '';
    }
    trigger.blur();
  };

  const suspendTooltip = ()=>{
    const tooltip = trigger.getAttribute('data-tooltip');
    if(tooltip !== null){
      trigger.setAttribute('data-suspended-tooltip', tooltip);
      trigger.removeAttribute('data-tooltip');
    }
  };

  const restoreTooltip = ()=>{
    const tooltip = trigger.getAttribute('data-suspended-tooltip');
    if(tooltip === null) return;
    trigger.setAttribute('data-tooltip', tooltip);
    trigger.removeAttribute('data-suspended-tooltip');
  };

  const isOpen = ()=> root.classList.contains('is-open');

  const close = ({ restoreFocus = false } = {})=>{
    root.classList.remove('is-open');
    panel.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    hideTooltip();
    requestAnimationFrame(()=>{
      restoreTooltip();
    });

    if(restoreFocus){
      trigger.focus({ preventScroll: true });
    }
  };

  const open = ()=>{
    hideTooltip();
    suspendTooltip();
    root.classList.add('is-open');
    panel.hidden = false;
    trigger.setAttribute('aria-expanded', 'true');
  };

  const toggle = ()=>{
    if(isOpen()){
      close();
      return;
    }

    open();
  };

  trigger.addEventListener('mousedown', ()=>{
    hideTooltip();
  });

  trigger.addEventListener('click', (event)=>{
    event.preventDefault();
    toggle();
  });

  document.addEventListener('click', (event)=>{
    if(!isOpen()) return;
    if(root.contains(event.target)) return;
    close();
  });

  document.addEventListener('keydown', (event)=>{
    if(!isOpen() || event.key !== 'Escape') return;
    event.preventDefault();
    close({ restoreFocus: true });
  });

  return {
    close,
    isOpen,
    open,
    panel,
    root,
    toggle,
    trigger,
  };
}
