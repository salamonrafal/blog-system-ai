import { describe, expect, it, vi } from 'vitest';

import {
  getAccent,
  getLang,
  getTheme,
  isAdminDeviceRemembered,
  isAdminShortcutsCollapsed,
  isAdminShortcutsDocked,
  persistUserLanguage,
  persistUserTimeZone,
  setAccent,
  setAdminDeviceRemembered,
  setAdminShortcutsCollapsed,
  setAdminShortcutsDocked,
  setLangPreference,
  setTheme,
} from '../../public/assets/js/modules/preferences.js';

describe('language preferences', ()=>{
  it('reads the persisted language from cookies', ()=>{
    persistUserLanguage('en');

    expect(document.cookie).toContain('user_language=en');
    expect(getLang()).toBe('en');
  });

  it('normalizes unsupported languages to Polish', ()=>{
    setLangPreference('de');

    expect(document.cookie).toContain('user_language=pl');
    expect(getLang()).toBe('pl');
  });

  it('falls back to the browser language when no cookie exists', ()=>{
    vi.spyOn(navigator, 'language', 'get').mockReturnValue('en-US');

    expect(getLang()).toBe('en');
  });
});

describe('theme preferences', ()=>{
  it('defaults to the dark theme', ()=>{
    expect(getTheme()).toBe('dark');
  });

  it('persists and applies the light theme', ()=>{
    document.body.innerHTML = '<button data-action="toggle-theme"></button>';

    setTheme('light');

    expect(getTheme()).toBe('light');
    expect(document.cookie).toContain('user_theme=light');
    expect(document.documentElement.getAttribute('data-theme')).toBe('light');
    expect(document.querySelector('[data-action="toggle-theme"]').textContent).not.toBe('');
  });

  it('normalizes unsupported themes to dark', ()=>{
    setTheme('sepia');

    expect(getTheme()).toBe('dark');
    expect(document.documentElement.getAttribute('data-theme')).toBe('dark');
  });
});

describe('accent preferences', ()=>{
  it('defaults to the configured accent color', ()=>{
    expect(getAccent()).toBe('#39ff14');
  });

  it('persists valid accent colors and updates dependent UI state', ()=>{
    document.body.innerHTML = '<input type="color" data-action="accent-color">';

    setAccent('#336699');

    expect(getAccent()).toBe('#336699');
    expect(document.cookie).toContain('user_accent=%23336699');
    expect(document.documentElement.style.getPropertyValue('--accent')).toBe('#336699');
    expect(document.documentElement.style.getPropertyValue('--accent-contrast')).toBe('#f6f8fb');
    expect(document.documentElement.style.getPropertyValue('--link')).toBe('#336699');
    expect(document.documentElement.style.getPropertyValue('--link-bg')).toBe('rgba(51, 102, 153, .12)');
    expect(document.documentElement.style.getPropertyValue('--scan')).toBe('rgba(51, 102, 153, .06)');
    expect(document.querySelector('[data-action="accent-color"]').value).toBe('#336699');
  });

  it('normalizes invalid accent colors to the default accent', ()=>{
    setAccent('not-a-color');

    expect(getAccent()).toBe('#39ff14');
    expect(document.documentElement.style.getPropertyValue('--accent')).toBe('#39ff14');
  });
});

describe('admin localStorage preferences', ()=>{
  it('persists the remembered admin device flag', ()=>{
    expect(isAdminDeviceRemembered()).toBe(false);

    setAdminDeviceRemembered(true);
    expect(isAdminDeviceRemembered()).toBe(true);
    expect(localStorage.getItem('admin_device_remembered')).toBe('1');

    setAdminDeviceRemembered(false);
    expect(isAdminDeviceRemembered()).toBe(false);
    expect(localStorage.getItem('admin_device_remembered')).toBeNull();
  });

  it('persists admin shortcut dock and collapse flags', ()=>{
    setAdminShortcutsDocked(true);
    setAdminShortcutsCollapsed(true);

    expect(isAdminShortcutsDocked()).toBe(true);
    expect(isAdminShortcutsCollapsed()).toBe(true);
    expect(localStorage.getItem('admin_shortcuts_docked')).toBe('1');
    expect(localStorage.getItem('admin_shortcuts_collapsed')).toBe('1');

    setAdminShortcutsDocked(false);
    setAdminShortcutsCollapsed(false);

    expect(isAdminShortcutsDocked()).toBe(false);
    expect(isAdminShortcutsCollapsed()).toBe(false);
  });
});

describe('time zone preferences', ()=>{
  it('persists the browser time zone when it is available', ()=>{
    const originalDateTimeFormat = Intl.DateTimeFormat;
    vi.spyOn(Intl, 'DateTimeFormat').mockImplementation((...args)=>{
      const formatter = originalDateTimeFormat(...args);
      vi.spyOn(formatter, 'resolvedOptions').mockReturnValue({
        ...formatter.resolvedOptions(),
        timeZone: 'Europe/Warsaw',
      });

      return formatter;
    });

    persistUserTimeZone();

    expect(document.cookie).toContain('user_timezone=Europe%2FWarsaw');
  });
});
