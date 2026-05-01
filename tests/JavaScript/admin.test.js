import { fireEvent } from '@testing-library/dom';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { setupAdminListingFilters } from '../../public/assets/js/modules/admin.js';

afterEach(()=>{
  vi.restoreAllMocks();
});

describe('setupAdminListingFilters', ()=>{
  it('updates the hidden input that belongs to the clicked dropdown', ()=>{
    document.body.innerHTML = `
      <form>
        <input type="hidden" name="category" value="" data-listing-filter-input="category">
        <input type="hidden" name="status" value="" data-listing-filter-input="status">
        <div data-listing-filter-dropdown="category">
          <button type="button" data-listing-filter-trigger>Categories</button>
          <div class="article-index-filter-options" hidden>
            <button type="button" data-listing-filter-option data-value="7">AI</button>
          </div>
        </div>
        <div data-listing-filter-dropdown="status">
          <button type="button" data-listing-filter-trigger>Statuses</button>
          <div class="article-index-filter-options" hidden>
            <button type="button" data-listing-filter-option data-value="draft">Draft</button>
          </div>
        </div>
      </form>
    `;
    const submit = vi.spyOn(HTMLFormElement.prototype, 'submit').mockImplementation(()=> {});

    setupAdminListingFilters();
    fireEvent.click(document.querySelector('[data-listing-filter-dropdown="status"] [data-listing-filter-option]'));

    expect(document.querySelector('[name="category"]').value).toBe('');
    expect(document.querySelector('[name="status"]').value).toBe('draft');
    expect(submit).toHaveBeenCalledTimes(1);
  });
});
