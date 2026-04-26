import { fireEvent, screen } from '@testing-library/dom';
import { describe, expect, it, vi } from 'vitest';

import { setupCustomSelects } from '../../public/assets/js/modules/custom-select.js';

function renderSelect(){
  document.body.innerHTML = `
    <label for="status">Status</label>
    <select id="status" class="article-editor-select" aria-required="true">
      <option value="draft">Draft</option>
      <option value="published">Published</option>
      <option value="archived" disabled>Archived</option>
    </select>
  `;

  const select = document.querySelector('select');
  const changeListener = vi.fn();
  select.addEventListener('change', changeListener);

  setupCustomSelects();

  return { changeListener, select };
}

describe('setupCustomSelects', ()=>{
  it('enhances native selects with an accessible trigger and available options', ()=>{
    const { select } = renderSelect();

    const trigger = screen.getByRole('combobox', { name: 'Status' });

    expect(select.classList.contains('app-select-native')).toBe(true);
    expect(select.getAttribute('aria-hidden')).toBe('true');
    expect(trigger.getAttribute('aria-expanded')).toBe('false');
    expect(trigger.getAttribute('aria-required')).toBe('true');
    expect(trigger.textContent).toContain('Draft');
  });

  it('opens the option panel and selects an available option', ()=>{
    const { changeListener, select } = renderSelect();
    const trigger = screen.getByRole('combobox', { name: 'Status' });

    fireEvent.click(trigger);

    expect(trigger.getAttribute('aria-expanded')).toBe('true');
    expect(screen.getByRole('option', { name: 'Published' })).toBeInstanceOf(HTMLButtonElement);
    expect(screen.queryByRole('option', { name: 'Archived' })).toBeNull();

    fireEvent.click(screen.getByRole('option', { name: 'Published' }));

    expect(select.value).toBe('published');
    expect(changeListener).toHaveBeenCalledTimes(1);
    expect(trigger.getAttribute('aria-expanded')).toBe('false');
    expect(trigger.textContent).toContain('Published');
  });

  it('syncs the custom trigger when the native select changes', ()=>{
    const { select } = renderSelect();
    const trigger = screen.getByRole('combobox', { name: 'Status' });

    select.value = 'published';
    fireEvent.change(select);

    expect(trigger.textContent).toContain('Published');
  });
});
