import { fireEvent, screen } from '@testing-library/dom';
import { describe, expect, it, vi } from 'vitest';

import { setupPrivacyNotice } from '../../public/assets/js/modules/privacy.js';

describe('setupPrivacyNotice', ()=>{
  it('renders the privacy notice when no consent is stored', ()=>{
    setupPrivacyNotice();

    const popup = screen.getByRole('dialog', { name: 'Prywatność' });

    expect(popup.classList.contains('privacy-popup')).toBe(true);
    expect(screen.getByRole('button', { name: 'Akceptuję' })).toBeInstanceOf(HTMLButtonElement);
    expect(screen.getByRole('button', { name: 'Odrzuć' })).toBeInstanceOf(HTMLButtonElement);
  });

  it('does not render another notice when consent already exists', ()=>{
    localStorage.setItem('privacy-consent', 'accepted');

    setupPrivacyNotice();

    expect(document.querySelector('.privacy-popup')).toBeNull();
  });

  it('stores accepted consent and removes the notice after the closing delay', ()=>{
    vi.useFakeTimers();
    setupPrivacyNotice();

    const popup = document.querySelector('.privacy-popup');
    fireEvent.click(screen.getByRole('button', { name: 'Akceptuję' }));

    expect(localStorage.getItem('privacy-consent')).toBe('accepted');
    expect(popup.classList.contains('is-hidden')).toBe(true);

    vi.advanceTimersByTime(180);

    expect(document.querySelector('.privacy-popup')).toBeNull();
  });

  it('stores declined consent when dismissed with Escape', ()=>{
    vi.useFakeTimers();
    setupPrivacyNotice();

    const popup = document.querySelector('.privacy-popup');
    fireEvent.keyDown(popup, { key: 'Escape' });

    expect(localStorage.getItem('privacy-consent')).toBe('declined');

    vi.advanceTimersByTime(180);

    expect(document.querySelector('.privacy-popup')).toBeNull();
  });
});
