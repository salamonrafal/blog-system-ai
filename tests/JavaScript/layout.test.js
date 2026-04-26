import { fireEvent } from '@testing-library/dom';
import { describe, expect, it, vi } from 'vitest';

import {
  setupAriaDisabledActions,
  setupBackToTop,
  syncTopbarHeight,
} from '../../public/assets/js/modules/layout.js';

describe('syncTopbarHeight', ()=>{
  it('stores the rounded topbar height as a CSS variable', ()=>{
    document.body.innerHTML = '<header class="topbar"></header>';
    const topbar = document.querySelector('.topbar');
    vi.spyOn(topbar, 'getBoundingClientRect').mockReturnValue({
      height: 42.2,
    });

    syncTopbarHeight();

    expect(document.documentElement.style.getPropertyValue('--topbar-height')).toBe('43px');
  });
});

describe('setupAriaDisabledActions', ()=>{
  it('prevents disabled action clicks', ()=>{
    setupAriaDisabledActions();
    document.body.innerHTML = '<a href="/admin" aria-disabled="true" data-disabled-action>Admin</a>';

    const event = new MouseEvent('click', { bubbles: true, cancelable: true });
    const allowed = document.querySelector('a').dispatchEvent(event);

    expect(allowed).toBe(false);
    expect(event.defaultPrevented).toBe(true);
  });

  it('prevents keyboard activation for disabled actions', ()=>{
    setupAriaDisabledActions();
    document.body.innerHTML = '<button type="button" aria-disabled="true" data-disabled-action>Save</button>';

    const event = new KeyboardEvent('keydown', {
      key: 'Enter',
      bubbles: true,
      cancelable: true,
    });
    const allowed = document.querySelector('button').dispatchEvent(event);

    expect(allowed).toBe(false);
    expect(event.defaultPrevented).toBe(true);
  });
});

describe('setupBackToTop', ()=>{
  it('creates a hidden back-to-top button at the top of the page', ()=>{
    vi.spyOn(window, 'scrollY', 'get').mockReturnValue(0);

    setupBackToTop();

    const button = document.querySelector('.back-to-top');

    expect(button).toBeInstanceOf(HTMLButtonElement);
    expect(button.classList.contains('is-visible')).toBe(false);
  });

  it('shows the back-to-top button after scrolling past the threshold', ()=>{
    vi.spyOn(window, 'scrollY', 'get').mockReturnValue(601);

    setupBackToTop();

    const button = document.querySelector('.back-to-top');
    fireEvent.scroll(window);

    expect(button.classList.contains('is-visible')).toBe(true);
  });
});
