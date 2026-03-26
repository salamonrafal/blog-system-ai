import { qs, qsa } from './shared.js';

export function syncTopbarHeight(){
  const topbar = qs('.topbar');
  if(!topbar) return;

  const height = Math.ceil(topbar.getBoundingClientRect().height);
  document.documentElement.style.setProperty('--topbar-height', `${height}px`);
}

export function setupTooltips(){
  const existingTooltip = qs('.app-tooltip');
  if(existingTooltip){
    existingTooltip.remove();
  }

  const tooltip = document.createElement('div');
  tooltip.className = 'app-tooltip';
  tooltip.setAttribute('hidden', '');
  tooltip.setAttribute('aria-hidden', 'true');
  document.body.appendChild(tooltip);

  let activeTrigger = null;

  const positionTooltip = (trigger)=>{
    if(!trigger || tooltip.hasAttribute('hidden')) return;

    const rect = trigger.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();
    const top = window.scrollY + rect.bottom + 10;
    const maxLeft = window.scrollX + window.innerWidth - tooltipRect.width - 12;
    const minLeft = window.scrollX + 12;
    const centeredLeft = window.scrollX + rect.left + (rect.width / 2) - (tooltipRect.width / 2);
    const left = Math.min(Math.max(centeredLeft, minLeft), maxLeft);

    tooltip.style.top = `${top}px`;
    tooltip.style.left = `${left}px`;
  };

  const showTooltip = (trigger)=>{
    const text = trigger.getAttribute('data-tooltip');
    if(!text) return;

    activeTrigger = trigger;
    tooltip.classList.toggle('is-wide', trigger.getAttribute('data-tooltip-wide') === 'true');
    tooltip.classList.toggle('is-multiline', trigger.getAttribute('data-tooltip-multiline') === 'true');
    tooltip.textContent = text;
    tooltip.removeAttribute('hidden');
    tooltip.setAttribute('aria-hidden', 'false');
    positionTooltip(trigger);
  };

  const hideTooltip = ()=>{
    tooltip.setAttribute('hidden', '');
    tooltip.setAttribute('aria-hidden', 'true');
    tooltip.classList.remove('is-wide');
    tooltip.classList.remove('is-multiline');
    activeTrigger = null;
  };

  qsa('[data-tooltip]').forEach((element)=>{
    element.removeAttribute('title');
    element.addEventListener('mouseenter', ()=> showTooltip(element));
    element.addEventListener('mouseleave', hideTooltip);
    element.addEventListener('focus', ()=> showTooltip(element));
    element.addEventListener('blur', hideTooltip);
  });

  window.addEventListener('scroll', ()=>{
    if(activeTrigger) positionTooltip(activeTrigger);
  }, { passive: true });

  window.addEventListener('resize', ()=>{
    if(activeTrigger) positionTooltip(activeTrigger);
  });
}

export function setupNav(){
  const currentPath = location.pathname.toLowerCase();
  const currentOrigin = window.location.origin.toLowerCase();

  qsa('.nav a, .mobile-drawer a').forEach((anchor)=>{
    const href = anchor.getAttribute('href') || '';
    if(!href) return;

    let targetUrl;
    let targetPath = '';

    try{
      targetUrl = new URL(href, window.location.origin);
      targetPath = targetUrl.pathname.toLowerCase();
    }catch(err){
      return;
    }

    const isSameOrigin = targetUrl.origin.toLowerCase() === currentOrigin;
    const isBlogLink = targetPath === '/';
    const isActive = isBlogLink
      ? isSameOrigin && (currentPath === '/' || currentPath.startsWith('/article/'))
      : isSameOrigin && targetPath === currentPath;

    if(isActive){
      anchor.classList.add('active');
    }
  });

  const burger = qs('[data-action="toggle-menu"]');
  const drawer = qs('.mobile-drawer');
  if(!burger || !drawer) return;

  burger.addEventListener('click', ()=>{
    drawer.classList.toggle('open');
  });

  qsa('a', drawer).forEach((anchor)=>{
    anchor.addEventListener('click', ()=>{
      drawer.classList.remove('open');
    });
  });

  document.addEventListener('click', (event)=>{
    if(!drawer.contains(event.target) && event.target !== burger){
      drawer.classList.remove('open');
    }
  });
}

function fastScrollToTop(){
  if(window.matchMedia('(prefers-reduced-motion: reduce)').matches){
    window.scrollTo(0, 0);
    return;
  }

  const startY = window.scrollY || window.pageYOffset;
  if(startY <= 0) return;

  const duration = 220;
  const start = performance.now();

  function step(now){
    const elapsed = now - start;
    const progress = Math.min(elapsed / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 4);
    window.scrollTo(0, Math.round(startY * (1 - eased)));
    if(progress < 1){
      requestAnimationFrame(step);
    }
  }

  requestAnimationFrame(step);
}

export function setupBackToTop(){
  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'back-to-top';
  button.setAttribute('data-i18n-aria', 'back_to_top');
  button.innerHTML = '<span class="back-to-top-icon" aria-hidden="true"></span><span class="back-to-top-label" data-i18n="back_to_top"></span>';
  document.body.appendChild(button);

  const toggleVisibility = ()=>{
    const shouldShow = (window.scrollY || window.pageYOffset) > 600;
    button.classList.toggle('is-visible', shouldShow);
  };

  window.addEventListener('scroll', toggleVisibility, { passive: true });
  window.addEventListener('resize', toggleVisibility);
  button.addEventListener('click', fastScrollToTop);
  toggleVisibility();
}

export function setupFlashNotices(){
  qsa('[data-action="dismiss-flash"]').forEach((button)=>{
    button.addEventListener('click', ()=>{
      const flash = button.closest('.flash');
      if(!flash) return;
      flash.setAttribute('hidden', '');
    });
  });
}
