import { fireEvent, waitFor } from '@testing-library/dom';
import { describe, expect, it, vi } from 'vitest';

import { setupAdminListingFilters } from '../../public/assets/js/modules/admin.js';

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

  it('loads author options from the search endpoint', async ()=>{
    vi.useFakeTimers();
    document.body.innerHTML = `
      <form>
        <input type="hidden" name="author" value="" data-listing-filter-input="author">
        <div data-listing-filter-dropdown="author" data-listing-filter-endpoint="/admin/articles/author-filter" data-listing-filter-no-results-i18n="admin_article_filter_author_no_results" data-listing-filter-no-results="No authors">
          <button type="button" data-listing-filter-trigger>Authors</button>
          <div class="article-index-filter-options" hidden>
            <input type="search" data-listing-filter-search-input>
            <button type="button" data-listing-filter-option data-value="">All authors</button>
            <div data-listing-filter-results></div>
          </div>
        </div>
      </form>
    `;
    const submit = vi.spyOn(HTMLFormElement.prototype, 'submit').mockImplementation(()=> {});
    const fetchMock = vi.spyOn(window, 'fetch').mockResolvedValue({
      ok: true,
      json: async ()=> ({
        options: [
          { id: 12, label: 'Author Name' },
        ],
      }),
    });

    setupAdminListingFilters();
    fireEvent.input(document.querySelector('[data-listing-filter-search-input]'), {
      target: { value: 'auth' },
    });
    await vi.advanceTimersByTimeAsync(180);

    await waitFor(()=>{
      expect(fetchMock).toHaveBeenCalledWith('/admin/articles/author-filter?q=auth', expect.objectContaining({
        method: 'GET',
      }));
      expect(document.querySelector('[data-listing-filter-results] [data-value="12"]')?.textContent).toBe('Author Name');
    });

    fireEvent.click(document.querySelector('[data-listing-filter-results] [data-value="12"]'));

    expect(document.querySelector('[name="author"]').value).toBe('12');
    expect(submit).toHaveBeenCalledTimes(1);

    vi.useRealTimers();
  });

  it('keeps current author options when the search endpoint fails', async ()=>{
    vi.useFakeTimers();
    document.body.innerHTML = `
      <form>
        <input type="hidden" name="author" value="" data-listing-filter-input="author">
        <div data-listing-filter-dropdown="author" data-listing-filter-endpoint="/admin/articles/author-filter" data-listing-filter-no-results-i18n="admin_article_filter_author_no_results" data-listing-filter-no-results="No authors">
          <button type="button" data-listing-filter-trigger>Authors</button>
          <div class="article-index-filter-options" hidden>
            <input type="search" data-listing-filter-search-input>
            <button type="button" data-listing-filter-option data-value="">All authors</button>
            <div data-listing-filter-results>
              <button type="button" data-listing-filter-option data-value="7">Existing Author</button>
            </div>
          </div>
        </div>
      </form>
    `;
    vi.spyOn(window, 'fetch').mockResolvedValue({
      ok: false,
      status: 500,
      json: async ()=> ({}),
    });

    setupAdminListingFilters();
    fireEvent.input(document.querySelector('[data-listing-filter-search-input]'), {
      target: { value: 'broken' },
    });
    await vi.advanceTimersByTimeAsync(180);

    await waitFor(()=>{
      expect(window.fetch).toHaveBeenCalled();
    });

    expect(document.querySelector('[data-listing-filter-results] [data-value="7"]')?.textContent).toBe('Existing Author');
    expect(document.querySelector('.article-index-filter-empty')).toBeNull();

    vi.useRealTimers();
  });

  it('does not keep a pending debounced author search after Enter', async ()=>{
    vi.useFakeTimers();
    document.body.innerHTML = `
      <form>
        <input type="hidden" name="author" value="" data-listing-filter-input="author">
        <div data-listing-filter-dropdown="author" data-listing-filter-endpoint="/admin/articles/author-filter" data-listing-filter-no-results-i18n="admin_article_filter_author_no_results" data-listing-filter-no-results="No authors">
          <button type="button" data-listing-filter-trigger>Authors</button>
          <div class="article-index-filter-options" hidden>
            <input type="search" data-listing-filter-search-input>
            <button type="button" data-listing-filter-option data-value="">All authors</button>
            <div data-listing-filter-results></div>
          </div>
        </div>
      </form>
    `;
    const fetchMock = vi.spyOn(window, 'fetch').mockResolvedValue({
      ok: true,
      json: async ()=> ({
        options: [
          { id: 12, label: 'Author Name' },
        ],
      }),
    });
    const searchInput = document.querySelector('[data-listing-filter-search-input]');

    setupAdminListingFilters();
    fireEvent.input(searchInput, {
      target: { value: 'auth' },
    });
    fireEvent.keyDown(searchInput, { key: 'Enter' });

    await waitFor(()=>{
      expect(fetchMock).toHaveBeenCalledTimes(1);
    });

    await vi.advanceTimersByTimeAsync(180);

    expect(fetchMock).toHaveBeenCalledTimes(1);

    vi.useRealTimers();
  });

  it('ignores stale author search results when a newer query has started', async ()=>{
    vi.useFakeTimers();
    document.body.innerHTML = `
      <form>
        <input type="hidden" name="author" value="" data-listing-filter-input="author">
        <div data-listing-filter-dropdown="author" data-listing-filter-endpoint="/admin/articles/author-filter" data-listing-filter-no-results-i18n="admin_article_filter_author_no_results" data-listing-filter-no-results="No authors">
          <button type="button" data-listing-filter-trigger>Authors</button>
          <div class="article-index-filter-options" hidden>
            <input type="search" data-listing-filter-search-input>
            <button type="button" data-listing-filter-option data-value="">All authors</button>
            <div data-listing-filter-results></div>
          </div>
        </div>
      </form>
    `;
    let resolveFirstJson;
    vi.spyOn(window, 'fetch').mockImplementation((url)=> {
      if(String(url).includes('q=old')){
        return Promise.resolve({
          ok: true,
          json: ()=> new Promise((resolve)=>{
            resolveFirstJson = resolve;
          }),
        });
      }

      return Promise.resolve({
        ok: true,
        json: async ()=> ({
          options: [
            { id: 22, label: 'New Author' },
          ],
        }),
      });
    });

    setupAdminListingFilters();
    fireEvent.input(document.querySelector('[data-listing-filter-search-input]'), {
      target: { value: 'old' },
    });
    await vi.advanceTimersByTimeAsync(180);

    fireEvent.input(document.querySelector('[data-listing-filter-search-input]'), {
      target: { value: 'new' },
    });
    await vi.advanceTimersByTimeAsync(180);

    await waitFor(()=>{
      expect(document.querySelector('[data-listing-filter-results] [data-value="22"]')?.textContent).toBe('New Author');
    });

    resolveFirstJson({
      options: [
        { id: 11, label: 'Old Author' },
      ],
    });

    await waitFor(()=>{
      expect(document.querySelector('[data-listing-filter-results] [data-value="22"]')?.textContent).toBe('New Author');
      expect(document.querySelector('[data-listing-filter-results] [data-value="11"]')).toBeNull();
    });

    vi.useRealTimers();
  });

  it('renders remote empty states with an i18n key', async ()=>{
    vi.useFakeTimers();
    document.body.innerHTML = `
      <form>
        <input type="hidden" name="author" value="" data-listing-filter-input="author">
        <div data-listing-filter-dropdown="author" data-listing-filter-endpoint="/admin/articles/author-filter" data-listing-filter-no-results-i18n="admin_article_filter_author_no_results" data-listing-filter-no-results="No authors">
          <button type="button" data-listing-filter-trigger>Authors</button>
          <div class="article-index-filter-options" hidden>
            <input type="search" data-listing-filter-search-input>
            <button type="button" data-listing-filter-option data-value="">All authors</button>
            <div data-listing-filter-results></div>
          </div>
        </div>
      </form>
    `;
    vi.spyOn(window, 'fetch').mockResolvedValue({
      ok: true,
      json: async ()=> ({
        options: [],
      }),
    });

    setupAdminListingFilters();
    fireEvent.input(document.querySelector('[data-listing-filter-search-input]'), {
      target: { value: 'missing' },
    });
    await vi.advanceTimersByTimeAsync(180);

    await waitFor(()=>{
      expect(document.querySelector('.article-index-filter-empty')?.getAttribute('data-i18n')).toBe('admin_article_filter_author_no_results');
      expect(document.querySelector('.article-index-filter-empty')?.textContent).toBe('No authors');
    });

    vi.useRealTimers();
  });
});
