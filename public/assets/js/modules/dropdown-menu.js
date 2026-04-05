import { qs } from './shared.js';

const dropdownMenuInstances = new Set();
let dropdownMenuGlobalsBound = false;

function pruneDisconnectedDropdownMenus(){
  dropdownMenuInstances.forEach((instance)=>{
    if(instance.root.isConnected) return;
    dropdownMenuInstances.delete(instance);
  });
}

function bindDropdownMenuGlobals(){
  if(dropdownMenuGlobalsBound) return;

  document.addEventListener('click', (event)=>{
    pruneDisconnectedDropdownMenus();
    dropdownMenuInstances.forEach((instance)=>{
      if(!instance.isOpen()) return;
      if(instance.root.contains(event.target)) return;
      instance.close();
    });
  });

  document.addEventListener('keydown', (event)=>{
    if(event.key !== 'Escape') return;

    pruneDisconnectedDropdownMenus();
    let handled = false;
    dropdownMenuInstances.forEach((instance)=>{
      if(!instance.isOpen()) return;
      handled = true;
      instance.close({ restoreFocus: true });
    });

    if(handled){
      event.preventDefault();
    }
  });

  dropdownMenuGlobalsBound = true;
}

export function createDropdownMenu(root){
  if(!root) return null;

  const trigger = qs('[data-dropdown-menu-trigger]', root);
  const panel = qs('[data-dropdown-menu-panel]', root);

  if(!trigger || !panel) return null;

  bindDropdownMenuGlobals();

  const hideTooltip = ()=>{
    document.dispatchEvent(new Event('app:hide-tooltip'));
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
  let restoreTooltipFrame = 0;

  const close = ({ restoreFocus = false } = {})=>{
    if(restoreTooltipFrame){
      cancelAnimationFrame(restoreTooltipFrame);
      restoreTooltipFrame = 0;
    }

    root.classList.remove('is-open');
    panel.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    hideTooltip();
    restoreTooltipFrame = requestAnimationFrame(()=>{
      restoreTooltipFrame = 0;
      if(isOpen()) return;
      restoreTooltip();
    });

    if(restoreFocus){
      trigger.focus({ preventScroll: true });
    }
  };

  const open = ()=>{
    if(restoreTooltipFrame){
      cancelAnimationFrame(restoreTooltipFrame);
      restoreTooltipFrame = 0;
    }

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

  const destroy = ()=>{
    if(restoreTooltipFrame){
      cancelAnimationFrame(restoreTooltipFrame);
      restoreTooltipFrame = 0;
    }
    close();
    dropdownMenuInstances.delete(instance);
  };

  const instance = {
    close,
    destroy,
    isOpen,
    open,
    panel,
    root,
    toggle,
    trigger,
  };

  dropdownMenuInstances.add(instance);

  return instance;
}
