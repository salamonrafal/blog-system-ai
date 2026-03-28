import { getTranslation } from './i18n.js';
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
  const stack = qs('.flash-stack');
  const notificationsEndpoint = document.body.getAttribute('data-user-notifications-endpoint');

  const hideFlash = (flash)=>{
    flash.setAttribute('hidden', '');
  };

  const bindFlash = (flash)=>{
    if(!flash || flash.getAttribute('data-flash-bound') === 'true') return;
    flash.setAttribute('data-flash-bound', 'true');

    const button = qs('[data-action="dismiss-flash"]', flash);
    if(button){
      button.addEventListener('click', ()=>{
        hideFlash(flash);
      });
    }

    if(flash.getAttribute('data-autohide') === 'true'){
      window.setTimeout(()=>{
        hideFlash(flash);
      }, 10000);
    }
  };

  const appendNotificationFlash = (notification)=>{
    if(!stack || !notification || !notification.translation_key) return;

    const flash = document.createElement('div');
    flash.className = notification.type === 'error' ? 'flash error' : 'flash';
    flash.setAttribute('data-autohide', 'true');
    flash.setAttribute('role', notification.type === 'error' ? 'alert' : 'status');

    const message = document.createElement('span');
    message.className = 'flash-message';
    message.setAttribute('data-i18n', notification.translation_key);
    message.textContent = getTranslation(notification.translation_key);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'flash-close';
    button.setAttribute('data-action', 'dismiss-flash');
    button.setAttribute('data-i18n-aria', 'admin_close_alert');
    button.setAttribute('aria-label', getTranslation('admin_close_alert'));
    button.innerHTML = '<span aria-hidden="true">&times;</span>';

    const actions = document.createElement('div');
    actions.className = 'flash-actions';

    if(notification.action_url && notification.action_label_translation_key){
      const link = document.createElement('a');
      link.className = 'button secondary flash-link';
      link.href = notification.action_url;
      link.setAttribute('data-i18n', notification.action_label_translation_key);
      link.textContent = getTranslation(notification.action_label_translation_key);
      actions.appendChild(link);
    }

    actions.appendChild(button);

    flash.appendChild(message);
    flash.appendChild(actions);
    stack.appendChild(flash);
    bindFlash(flash);
  };

  const pollNotifications = async ()=>{
    if(!notificationsEndpoint) return;

    try{
      const response = await fetch(notificationsEndpoint, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
      });

      if(!response.ok) return;

      const payload = await response.json();
      const notifications = Array.isArray(payload.notifications) ? payload.notifications : [];
      notifications.forEach((notification)=>{
        appendNotificationFlash(notification);
      });
    }catch(err){
    }
  };

  let pollTimeoutId = null;
  let isPolling = false;

  const clearPolling = ()=>{
    if(pollTimeoutId !== null){
      window.clearTimeout(pollTimeoutId);
      pollTimeoutId = null;
    }
  };

  const scheduleNextPoll = ()=>{
    clearPolling();
    if(document.visibilityState !== 'visible') return;

    pollTimeoutId = window.setTimeout(()=>{
      void runPollingCycle();
    }, 5000);
  };

  const runPollingCycle = async ()=>{
    if(isPolling || document.visibilityState !== 'visible') return;

    isPolling = true;
    try{
      await pollNotifications();
    }finally{
      isPolling = false;
      scheduleNextPoll();
    }
  };

  qsa('.flash').forEach((flash)=>{
    bindFlash(flash);
  });

  if(!notificationsEndpoint){
    return;
  }

  document.addEventListener('visibilitychange', ()=>{
    if(document.visibilityState === 'visible'){
      void runPollingCycle();
      return;
    }

    clearPolling();
  });

  void runPollingCycle();
}
