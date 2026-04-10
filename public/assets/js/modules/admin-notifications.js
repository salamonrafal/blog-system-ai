import { getTranslation, registerI18nListener } from './i18n.js';
import { getLang } from './preferences.js';
import { formatDateTime, qs, qsa } from './shared.js';

export function createAdminNotificationsController({
  recentNotificationsEndpoint,
  notificationsCsrfToken,
  onBadgesUpdated = ()=>{},
} = {}){
  let recentNotificationsRequest = null;
  let adminNotificationsPanelHeight = 0;
  let latestAdminNotificationsRequestId = 0;

  const formatAdminNotificationDate = (value)=> formatDateTime(value, {
    locale: getLang(),
    options: {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    },
  });

  const renderAdminNotificationsState = (panel, translationKey)=>{
    if(!(panel instanceof HTMLElement)) return;
    panel.innerHTML = '';

    const placeholder = document.createElement('div');
    placeholder.className = 'admin-shortcuts-notifications-placeholder';
    placeholder.setAttribute('data-i18n', translationKey);
    placeholder.textContent = getTranslation(translationKey);
    panel.appendChild(placeholder);
  };

  const formatAdminNotificationsBadgeLabel = (count)=>{
    const normalizedCount = Number.isFinite(Number(count)) ? Math.max(0, Number(count)) : 0;
    const locale = getLang();
    const pluralCategory = new Intl.PluralRules(locale).select(normalizedCount);
    const translationKey = `admin_shortcut_notifications_badge_${pluralCategory}`;
    const fallbackTranslationKey = 'admin_shortcut_notifications_badge_other';
    const template = getTranslation(translationKey, locale) || getTranslation(fallbackTranslationKey, locale);

    return template.replace('{{count}}', `${normalizedCount}`);
  };

  const syncAdminNotificationsBadgeLabel = ()=>{
    qsa('[data-admin-notifications-badge]').forEach((badge)=>{
      if(!(badge instanceof HTMLElement)) return;

      const count = Number(badge.textContent || '0');
      badge.setAttribute('aria-label', formatAdminNotificationsBadgeLabel(count));
    });
  };

  const syncAdminNotificationsBadge = (totalCount)=>{
    const normalizedCount = Number.isFinite(Number(totalCount)) ? Math.max(0, Number(totalCount)) : 0;

    qsa('[data-admin-notifications-badge]').forEach((badge)=>{
      if(!(badge instanceof HTMLElement)) return;

      badge.textContent = `${normalizedCount}`;
      badge.hidden = normalizedCount <= 0;
      badge.setAttribute('aria-label', formatAdminNotificationsBadgeLabel(normalizedCount));
    });

    onBadgesUpdated();
  };

  const setAdminNotificationsLoadingState = (panel, isLoading)=>{
    if(!(panel instanceof HTMLElement)) return;

    const container = panel.closest('.admin-shortcuts-notifications-panel');
    if(!(container instanceof HTMLElement)) return;

    if(isLoading){
      adminNotificationsPanelHeight = container.offsetHeight;
      if(adminNotificationsPanelHeight > 0){
        container.style.height = `${adminNotificationsPanelHeight}px`;
      }
      container.classList.add('is-loading');
      return;
    }

    container.classList.remove('is-loading');
    window.requestAnimationFrame(()=>{
      container.style.height = '';
    });
  };

  const renderAdminNotifications = (panel, notifications)=>{
    if(!(panel instanceof HTMLElement)) return;

    if(!Array.isArray(notifications) || notifications.length === 0){
      renderAdminNotificationsState(panel, 'admin_shortcut_notifications_empty');
      return;
    }

    panel.innerHTML = '';

    notifications.forEach((notification)=>{
      const item = document.createElement('div');
      item.className = `admin-shortcuts-link admin-shortcuts-sublink admin-shortcuts-notification-item admin-shortcuts-notification-item--${notification.type === 'error' ? 'error' : 'success'}${notification.is_read ? ' is-read' : ''}`;
      item.setAttribute('data-notification-id', `${notification.id ?? ''}`);

      const icon = document.createElement('span');
      icon.className = 'admin-shortcuts-link-icon';
      icon.setAttribute('aria-hidden', 'true');
      icon.innerHTML = notification.type === 'error'
        ? '<svg viewBox="0 0 32 32"><circle cx="16" cy="16" r="10"></circle><path d="M16 11v6"></path><path d="M16 21h.01"></path></svg>'
        : '<svg viewBox="0 0 32 32"><path d="M9 16l5 5 9-10"></path></svg>';

      const content = document.createElement('span');
      content.className = 'admin-shortcuts-notification-content';

      const title = document.createElement('span');
      title.className = 'admin-shortcuts-notification-title';
      title.setAttribute('data-i18n', notification.translation_key);
      title.textContent = getTranslation(notification.translation_key);

      const meta = document.createElement('span');
      meta.className = 'admin-shortcuts-notification-meta';
      meta.textContent = formatAdminNotificationDate(notification.created_at);

      const actions = document.createElement('div');
      actions.className = 'admin-shortcuts-notification-actions';

      const toggleReadButton = document.createElement('button');
      toggleReadButton.type = 'button';
      toggleReadButton.className = 'admin-shortcuts-notification-action';
      toggleReadButton.setAttribute('data-action', 'toggle-admin-notification-read');
      toggleReadButton.setAttribute('data-url', notification.toggle_read_url || '');
      toggleReadButton.setAttribute('data-i18n-aria', notification.is_read ? 'admin_shortcut_notifications_mark_unread' : 'admin_shortcut_notifications_mark_read');
      toggleReadButton.setAttribute('data-i18n-tooltip', notification.is_read ? 'admin_shortcut_notifications_mark_unread' : 'admin_shortcut_notifications_mark_read');
      toggleReadButton.setAttribute('data-tooltip', getTranslation(notification.is_read ? 'admin_shortcut_notifications_mark_unread' : 'admin_shortcut_notifications_mark_read'));
      toggleReadButton.setAttribute('aria-label', getTranslation(notification.is_read ? 'admin_shortcut_notifications_mark_unread' : 'admin_shortcut_notifications_mark_read'));
      toggleReadButton.innerHTML = notification.is_read
        ? '<span class="admin-shortcuts-link-icon" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M4 16s4.5-7 12-7 12 7 12 7-4.5 7-12 7-12-7-12-7"></path><circle cx="16" cy="16" r="3.5"></circle><path d="M8 24 24 8"></path></svg></span>'
        : '<span class="admin-shortcuts-link-icon" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M4 16s4.5-7 12-7 12 7 12 7-4.5 7-12 7-12-7-12-7"></path><circle cx="16" cy="16" r="3.5"></circle></svg></span>';

      const deleteButton = document.createElement('button');
      deleteButton.type = 'button';
      deleteButton.className = 'admin-shortcuts-notification-action admin-shortcuts-notification-action-danger';
      deleteButton.setAttribute('data-action', 'delete-admin-notification');
      deleteButton.setAttribute('data-url', notification.delete_url || '');
      deleteButton.setAttribute('data-i18n-aria', 'admin_shortcut_notifications_delete');
      deleteButton.setAttribute('data-i18n-tooltip', 'admin_shortcut_notifications_delete');
      deleteButton.setAttribute('data-tooltip', getTranslation('admin_shortcut_notifications_delete'));
      deleteButton.setAttribute('aria-label', getTranslation('admin_shortcut_notifications_delete'));
      deleteButton.innerHTML = '<span class="admin-shortcuts-link-icon" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M8 10h16"></path><path d="M12 10V8h8v2"></path><path d="M11 10l1 14h8l1-14"></path><path d="M14 14v6"></path><path d="M18 14v6"></path></svg></span>';

      actions.appendChild(toggleReadButton);
      actions.appendChild(deleteButton);

      content.appendChild(title);
      content.appendChild(meta);
      item.appendChild(icon);
      item.appendChild(content);
      item.appendChild(actions);
      panel.appendChild(item);
    });
  };

  const runAdminNotificationAction = async (url, method)=>{
    if(!url || !notificationsCsrfToken) return false;

    try{
      const response = await fetch(url, {
        method,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'X-CSRF-Token': notificationsCsrfToken,
        },
        cache: 'no-store',
        credentials: 'same-origin',
      });

      return response.ok;
    }catch(error){
      return false;
    }
  };

  const loadRecentNotifications = async (force = false)=>{
    const panel = qs('[data-admin-notifications-panel]');
    if(!(panel instanceof HTMLElement) || !recentNotificationsEndpoint){
      return Promise.resolve();
    }

    if(recentNotificationsRequest && !force){
      return recentNotificationsRequest;
    }

    const requestId = ++latestAdminNotificationsRequestId;

    const hasRenderedNotifications = panel.children.length > 0;
    if(!hasRenderedNotifications){
      renderAdminNotificationsState(panel, 'admin_shortcut_notifications_loading');
    }
    setAdminNotificationsLoadingState(panel, true);

    const requestPromise = fetch(recentNotificationsEndpoint, {
      method: 'GET',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      cache: 'no-store',
      credentials: 'same-origin',
    })
      .then(async (response)=>{
        if(!response.ok){
          throw new Error(`Unexpected response: ${response.status}`);
        }

        const payload = await response.json();
        if(requestId !== latestAdminNotificationsRequestId){
          return;
        }

        syncAdminNotificationsBadge(payload.total_count);
        renderAdminNotifications(panel, Array.isArray(payload.notifications) ? payload.notifications : []);
      })
      .catch(()=>{
        if(requestId !== latestAdminNotificationsRequestId){
          return;
        }

        renderAdminNotificationsState(panel, 'admin_shortcut_notifications_error');
      })
      .finally(()=>{
        if(requestId === latestAdminNotificationsRequestId){
          setAdminNotificationsLoadingState(panel, false);
        }

        if(recentNotificationsRequest === requestPromise){
          recentNotificationsRequest = null;
        }
      });

    recentNotificationsRequest = requestPromise;

    return requestPromise;
  };

  const refreshNotificationsViews = (afterRefresh = ()=>{})=>{
    void loadRecentNotifications(true).then(()=>{
      afterRefresh();
    });
  };

  const handleActionClick = (target, refreshHandler = ()=>{})=>{
    if(!(target instanceof Element)) return false;

    const notificationAction = target.closest('[data-action="toggle-admin-notification-read"], [data-action="delete-admin-notification"], [data-action="delete-all-admin-notifications"]');
    if(!(notificationAction instanceof HTMLButtonElement)) return false;

    const action = notificationAction.getAttribute('data-action');
    const url = notificationAction.getAttribute('data-url') || '';
    const method = action === 'toggle-admin-notification-read' ? 'POST' : 'DELETE';

    void runAdminNotificationAction(url, method).then((succeeded)=>{
      if(succeeded){
        refreshHandler();
      }
    });

    return true;
  };

  const handleRefreshClick = (target, refreshHandler = ()=>{})=>{
    if(!(target instanceof Element)) return false;

    const refreshButton = target.closest('[data-action="refresh-admin-notifications"]');
    if(!(refreshButton instanceof HTMLButtonElement)) return false;

    refreshHandler();

    return true;
  };

  const setup = ()=>{
    if(recentNotificationsEndpoint){
      void loadRecentNotifications();
    }

    registerI18nListener(()=>{
      syncAdminNotificationsBadgeLabel();
      onBadgesUpdated();
    });
  };

  return {
    handleActionClick,
    handleRefreshClick,
    loadRecentNotifications,
    refreshNotificationsViews,
    setup,
  };
}
