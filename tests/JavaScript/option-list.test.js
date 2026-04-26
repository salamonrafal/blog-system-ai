import { fireEvent, screen } from '@testing-library/dom';
import { describe, expect, it, vi } from 'vitest';

import { setupOptionLists } from '../../public/assets/js/modules/option-list.js';

function renderOptionList(){
  document.body.innerHTML = `
    <label for="keywords">Keywords</label>
    <div data-option-list>
      <select id="keywords" multiple data-option-list-select aria-required="true">
        <option value="ai">AI</option>
        <option value="php">PHP</option>
        <option value="draft" disabled>Draft only</option>
      </select>
      <div data-option-list-shell hidden>
        <input type="text" data-option-list-input>
        <div data-option-list-selected></div>
        <div data-option-list-results hidden></div>
      </div>
    </div>
  `;

  const select = document.querySelector('select');
  const changeListener = vi.fn();
  select.addEventListener('change', changeListener);

  setupOptionLists();

  return {
    changeListener,
    input: document.querySelector('[data-option-list-input]'),
    results: document.querySelector('[data-option-list-results]'),
    select,
    selected: document.querySelector('[data-option-list-selected]'),
    shell: document.querySelector('[data-option-list-shell]'),
  };
}

describe('setupOptionLists', ()=>{
  it('enhances the native multi-select with combobox semantics', ()=>{
    const { input, select, shell } = renderOptionList();

    expect(shell.hidden).toBe(false);
    expect(select.classList.contains('app-option-list-native')).toBe(true);
    expect(select.getAttribute('aria-hidden')).toBe('true');
    expect(input.getAttribute('role')).toBe('combobox');
    expect(input.getAttribute('aria-required')).toBe('true');
  });

  it('filters available options and selects an item by click', ()=>{
    const { changeListener, input, results, select, selected } = renderOptionList();

    input.focus();
    input.value = 'ph';
    fireEvent.input(input);

    expect(results.hidden).toBe(false);
    expect(screen.getByRole('option', { name: 'PHP' })).toBeInstanceOf(HTMLButtonElement);
    expect(screen.queryByRole('option', { name: 'Draft only' })).toBeNull();

    fireEvent.click(screen.getByRole('option', { name: 'PHP' }));

    expect(select.options[1].selected).toBe(true);
    expect(changeListener).toHaveBeenCalledTimes(1);
    expect(input.value).toBe('');
    expect(selected.textContent).toContain('PHP');
  });

  it('removes the last selected item with Backspace on an empty input', ()=>{
    const { input, select, selected } = renderOptionList();
    select.options[0].selected = true;
    fireEvent.change(select);

    expect(selected.textContent).toContain('AI');

    input.focus();
    fireEvent.keyDown(input, { key: 'Backspace' });

    expect(select.options[0].selected).toBe(false);
    expect(selected.textContent).not.toContain('AI');
  });

  it('moves and chooses the active result with the keyboard', ()=>{
    const { changeListener, input, select } = renderOptionList();

    input.focus();
    input.value = 'a';
    fireEvent.input(input);
    fireEvent.keyDown(input, { key: 'ArrowDown' });
    fireEvent.keyDown(input, { key: 'Enter' });

    expect(select.options[0].selected).toBe(true);
    expect(changeListener).toHaveBeenCalledTimes(1);
  });
});
